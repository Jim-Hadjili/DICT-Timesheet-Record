<?php 
// Start session to store form submission data
session_start();

// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include './connection/conn.php';
include './face_config.php'; // Include the configuration file

// Check if required directories exist, if not create them
$required_dirs = ['functions/face_images/', 'functions/temp_faces/'];
$missing_dirs = false;
$failed_dirs = [];

foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        // Try to create the directory with full permissions
        if (!mkdir($dir, 0777, true)) {
            $missing_dirs = true;
            $failed_dirs[] = $dir;
        }
    }
}

if ($missing_dirs) {
    // Log the error and set a message, but don't redirect
    error_log("Failed to create required directories: " . implode(", ", $failed_dirs));
    $message = "Warning: Some required directories could not be created. Face recognition may not work properly.";
}

// Initialize message variable
$message = "";
// Get the selected intern ID from GET, POST, or the form submission
$selected_intern_id = '';
if (isset($_GET['intern_id']) && !empty($_GET['intern_id'])) {
    $selected_intern_id = $_GET['intern_id'];
} elseif (isset($_POST['intern_id']) && !empty($_POST['intern_id'])) {
    $selected_intern_id = $_POST['intern_id'];
}

// Check if face recognition just happened
$face_recognized = isset($_GET['face_recognized']) ? true : false;

// If face was just recognized, set a message
if ($face_recognized && !empty($selected_intern_id)) {
    // Get the intern name
    $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
    $name_stmt->bindParam(':intern_id', $selected_intern_id);
    $name_stmt->execute();
    $name_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
    $recognized_name = $name_data ? $name_data['Intern_Name'] : 'Unknown';
    
    $_SESSION['message'] = "Face recognized: " . $recognized_name . ". Please confirm this is you.";
}

// Check for messages in session (from redirects)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    // Clear the message from session after displaying it
    unset($_SESSION['message']);
}

// Process face recognition time in/out via AJAX
if (isset($_POST['face_recognition']) && isset($_POST['image_data'])) {
    try {
        // Set higher memory limit for image processing
        ini_set('memory_limit', '256M');
        
        // Get the image data
        $img_data = $_POST['image_data'];
        
        // Check if the image data is valid
        if (empty($img_data)) {
            throw new Exception("Empty image data received");
        }
        
        // Remove the data URL prefix and decode base64 data
        $img_data = str_replace('data:image/png;base64,', '', $img_data);
        $img_data = str_replace(' ', '+', $img_data);
        
        // Validate base64 data
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $img_data)) {
            throw new Exception("Invalid base64 data");
        }
        
        // Decode base64 data
        $data = base64_decode($img_data);
        if ($data === false) {
            throw new Exception("Failed to decode base64 data");
        }
        
        // Check if we should skip saving temp files
        $skip_temp_save = isset($_POST['skip_temp_save']) && $_POST['skip_temp_save'] == '1';
        $temp_filename = '';
        
        if (!$skip_temp_save) {
            // Create directory if it doesn't exist
            $upload_dir = 'functions/temp_faces/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Failed to create directory: " . $upload_dir);
                }
            }
            
            // Check if directory is writable
            if (!is_writable($upload_dir)) {
                throw new Exception("Directory is not writable: " . $upload_dir);
            }
            
            // Generate a unique filename for the temporary image
            $temp_filename = $upload_dir . 'temp_' . time() . '.png';
            
            // Save the temporary image file
            $bytes_written = file_put_contents($temp_filename, $data);
            if ($bytes_written === false) {
                throw new Exception("Failed to write image file: " . $temp_filename);
            }
            
            // Verify the file was created and is readable
            if (!file_exists($temp_filename) || !is_readable($temp_filename)) {
                throw new Exception("Failed to create or read image file: " . $temp_filename);
            }
        }
        
        // Check if GD is available and use it if possible
        $use_gd = extension_loaded('gd');
        
        // Debug information
        $debug_info = array();
        
        if (!$use_gd) {
            $debug_info[] = "WARNING: GD library is not available. Using fallback comparison method.";
        } else if (!$skip_temp_save) {
            // Verify the image can be processed by GD
            $image_info = @getimagesize($temp_filename);
            if ($image_info === false) {
                throw new Exception("Invalid image file: " . $temp_filename);
            }
            $debug_info[] = "Image dimensions: " . $image_info[0] . "x" . $image_info[1];
        }
        
        // Get all interns with registered faces
        $stmt = $conn->prepare("SELECT * FROM interns WHERE Face_Registered = 1");
        $stmt->execute();
        $registered_interns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = array('success' => false, 'message' => 'No face recognized');
        $best_match = null;
        $highest_similarity = 0;
        
        // Get minimum similarity threshold from config
        include_once 'face_config.php';
        if (!isset($minimum_similarity_threshold)) {
            $minimum_similarity_threshold = 85; // Default to 85% if not set in config
        }
        
        if ($skip_temp_save) {
            $debug_info[] = "Using in-memory image processing (skipping temp files)";
        } else {
            $debug_info[] = "Temp file created: " . $temp_filename . " (" . round(filesize($temp_filename)/1024) . " KB)";
        }
        $debug_info[] = "Found " . count($registered_interns) . " registered interns";
        $debug_info[] = "Minimum similarity threshold: " . $minimum_similarity_threshold . "%";
        
        // If we have registered interns, perform face matching
        if (count($registered_interns) > 0) {
            // Compare the captured face with stored face images
            foreach ($registered_interns as $intern) {
                $intern_id = $intern['Intern_id'];
                $debug_info[] = "Processing intern ID: " . $intern_id . ", Name: " . $intern['Intern_Name'];
                
                // Check if the intern has a registered face image path
                if (!isset($intern['Face_Image_Path']) || empty($intern['Face_Image_Path'])) {
                    $debug_info[] = "Intern ID " . $intern_id . " has no Face_Image_Path";
                    
                    // Try to find face images using glob pattern
                    $face_dir = 'functions/face_images/';
                    $face_pattern = $face_dir . 'face_' . $intern_id . '_*.png';
                    $face_files = glob($face_pattern);
                    
                    if (!empty($face_files)) {
                        // Use the most recent face image
                        $face_file = end($face_files);
                        $debug_info[] = "Found face image using glob: " . $face_file;
                    } else {
                        // Try alternative directories
                        $alt_patterns = [
                            'face_images/face_' . $intern_id . '_*.png',
                            './functions/face_images/face_' . $intern_id . '_*.png',
                            './face_images/face_' . $intern_id . '_*.png'
                        ];
                        
                        $found = false;
                        foreach ($alt_patterns as $pattern) {
                            $alt_files = glob($pattern);
                            if (!empty($alt_files)) {
                                $face_file = end($alt_files);
                                $debug_info[] = "Found face image using alternative pattern: " . $pattern . " -> " . $face_file;
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            $debug_info[] = "No face images found for Intern ID " . $intern_id . " using any pattern";
                            continue; // Skip if no face image found
                        }
                    }
                } else {
                    $face_file = $intern['Face_Image_Path'];
                    $debug_info[] = "Using Face_Image_Path: " . $face_file;
                    
                    // If the file doesn't exist, check in the functions directory
                    if (!file_exists($face_file)) {
                        $debug_info[] = "File not found at: " . $face_file;
                        
                        // Try different path combinations
                        $possible_paths = [
                            'functions/' . $face_file,
                            './functions/' . $face_file,
                            str_replace('functions/', '', $face_file),
                            './face_images/' . basename($face_file),
                            'functions/face_images/' . basename($face_file)
                        ];
                        
                        $found = false;
                        foreach ($possible_paths as $path) {
                            if (file_exists($path)) {
                                $face_file = $path;
                                $debug_info[] = "Found at alternative path: " . $face_file;
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            // Try to find it using glob as a last resort
                            $face_dir = 'functions/face_images/';
                            $face_pattern = $face_dir . 'face_' . $intern_id . '_*.png';
                            $face_files = glob($face_pattern);
                            
                            if (!empty($face_files)) {
                                // Use the most recent face image
                                $face_file = end($face_files);
                                $debug_info[] = "Found using glob as last resort: " . $face_file;
                                $found = true;
                            }
                        }
                        
                        if (!$found) {
                            $debug_info[] = "Could not find face image for intern ID " . $intern_id . " after trying all paths";
                            continue; // Skip if no face image found
                        }
                    }
                }
                
                // Compare the captured image with the stored face image
                if (file_exists($face_file) && ($skip_temp_save || file_exists($temp_filename))) {
                    $debug_info[] = "Comparing with face file: " . $face_file . " (" . round(filesize($face_file)/1024) . " KB)";
                    
                    // Use a simpler comparison method that's more reliable
                    try {
                        $similarity = 0;
                        
                        if ($use_gd) {
                            // GD-based comparison method
                            if ($skip_temp_save) {
                                // Create image from memory
                                $temp_image = @imagecreatefromstring($data);
                            } else {
                                $temp_image = @imagecreatefrompng($temp_filename);
                            }
                            
                            $stored_image = @imagecreatefrompng($face_file);
                            
                            if (!$temp_image) {
                                $debug_info[] = "Failed to create image from data";
                                continue;
                            }
                            
                            if (!$stored_image) {
                                $debug_info[] = "Failed to create image from stored file: " . $face_file;
                                continue;
                            }
                            
                            // Get image dimensions
                            $temp_width = imagesx($temp_image);
                            $temp_height = imagesy($temp_image);
                            $stored_width = imagesx($stored_image);
                            $stored_height = imagesy($stored_image);
                            
                            $debug_info[] = "Temp image dimensions: " . $temp_width . "x" . $temp_height;
                            $debug_info[] = "Stored image dimensions: " . $stored_width . "x" . $stored_height;
                            
                            // Resize images to the same dimensions for comparison
                            $width = min($temp_width, $stored_width, 100); // Limit to 100px for performance
                            $height = min($temp_height, $stored_height, 100);
                            
                            $temp_resized = imagecreatetruecolor($width, $height);
                            $stored_resized = imagecreatetruecolor($width, $height);
                            
                            imagecopyresampled($temp_resized, $temp_image, 0, 0, 0, 0, $width, $height, $temp_width, $temp_height);
                            imagecopyresampled($stored_resized, $stored_image, 0, 0, 0, 0, $width, $height, $stored_width, $stored_height);
                            
                            // Convert to grayscale for better comparison
                            imagefilter($temp_resized, IMG_FILTER_GRAYSCALE);
                            imagefilter($stored_resized, IMG_FILTER_GRAYSCALE);
                            
                            // Simple pixel comparison
                            $total_pixels = $width * $height;
                            $matching_pixels = 0;
                            $tolerance = 60; // Increased tolerance for better matching
                            
                            for ($y = 0; $y < $height; $y++) {
                                for ($x = 0; $x < $width; $x++) {
                                    $temp_color = imagecolorat($temp_resized, $x, $y) & 0xFF; // Get grayscale value
                                    $stored_color = imagecolorat($stored_resized, $x, $y) & 0xFF;
                                    
                                    if (abs($temp_color - $stored_color) < $tolerance) {
                                        $matching_pixels++;
                                    }
                                }
                            }
                            
                            // Calculate similarity percentage
                            $similarity = ($matching_pixels / $total_pixels) * 100;
                            
                            $debug_info[] = "Intern ID " . $intern_id . " similarity: " . round($similarity, 2) . "% (matching pixels: " . $matching_pixels . "/" . $total_pixels . ")";
                            
                            // Clean up
                            imagedestroy($temp_image);
                            imagedestroy($stored_image);
                            imagedestroy($temp_resized);
                            imagedestroy($stored_resized);
                        } else {
                            // Fallback method without GD
                            if ($skip_temp_save) {
                                // Use direct binary comparison with the data
                                $temp_size = strlen($data);
                                $stored_content = file_get_contents($face_file);
                                $stored_size = strlen($stored_content);
                                
                                // Calculate size similarity (0-100)
                                $size_diff = abs($temp_size - $stored_size);
                                $size_similarity = max(0, 100 - ($size_diff / max($temp_size, $stored_size) * 100));
                                
                                // Calculate a simple hash-based similarity
                                $temp_hash = md5($data);
                                $stored_hash = md5($stored_content);
                            } else {
                                // Compare file sizes and modification times as a basic heuristic
                                $temp_size = filesize($temp_filename);
                                $stored_size = filesize($face_file);
                                
                                // Calculate size similarity (0-100)
                                $size_diff = abs($temp_size - $stored_size);
                                $size_similarity = max(0, 100 - ($size_diff / max($temp_size, $stored_size) * 100));
                                
                                // Compare file contents directly (basic binary comparison)
                                $temp_content = file_get_contents($temp_filename);
                                $stored_content = file_get_contents($face_file);
                                
                                // Calculate a simple hash-based similarity
                                $temp_hash = md5($temp_content);
                                $stored_hash = md5($stored_content);
                            }
                            
                            // Count matching characters in the hash
                            $hash_matches = 0;
                            for ($i = 0; $i < 32; $i++) {
                                if ($temp_hash[$i] === $stored_hash[$i]) {
                                    $hash_matches++;
                                }
                            }
                            $hash_similarity = ($hash_matches / 32) * 100;
                            
                            // Calculate overall similarity (weighted)
                            $similarity = ($size_similarity * 0.3) + ($hash_similarity * 0.7);
                            
                            $debug_info[] = "Intern ID " . $intern_id . " similarity (no GD): " . round($similarity, 2) . 
                                "% (size: " . round($size_similarity, 2) . "%, hash: " . round($hash_similarity, 2) . "%)";
                        }
                        
                        // Keep track of the best match
                        if ($similarity > $highest_similarity) {
                            $highest_similarity = $similarity;
                            $best_match = $intern;
                        }
                    } catch (Exception $e) {
                        $debug_info[] = "Error comparing images: " . $e->getMessage();
                    }
                } else {
                    if (!file_exists($face_file)) {
                        $debug_info[] = "Face file does not exist: " . $face_file;
                    }
                    if (!$skip_temp_save && !file_exists($temp_filename)) {
                        $debug_info[] = "Temp file does not exist: " . $temp_filename;
                    }
                }
            }
            
            // If we found a match with sufficient similarity
            if ($best_match && $highest_similarity >= $minimum_similarity_threshold) {
                $intern_id = $best_match['Intern_id'];
                $intern_name = $best_match['Intern_Name'];
                
                $debug_info[] = "Best match: Intern ID " . $intern_id . ", Name: " . $intern_name . ", Similarity: " . round($highest_similarity, 2) . "%";
                
                // Store the recognized face data in session for confirmation
                $_SESSION['recognized_face'] = [
                    'intern_id' => $intern_id,
                    'intern_name' => $intern_name,
                    'similarity' => round($highest_similarity, 2),
                    'timestamp' => time()
                ];
                
                // Set the pending recognition flag to show confirmation dialog
                $_SESSION['pending_recognition'] = true;
                
                // Clean up the temporary file if it exists
                if (!$skip_temp_save && file_exists($temp_filename)) {
                    @unlink($temp_filename);
                }
                
                $response = array(
                    'success' => true, 
                    'message' => 'Face recognized successfully!',
                    'intern_id' => $intern_id,
                    'intern_name' => $intern_name,
                    'similarity' => round($highest_similarity, 2),
                    'similarityText' => round($highest_similarity, 2) . '%',
                    'needsConfirmation' => true, // Flag to show confirmation dialog
                    'debug' => $debug_info
                );
            } else {
                // Clean up the temporary file if it exists
                if (!$skip_temp_save && file_exists($temp_filename)) {
                    @unlink($temp_filename);
                }
                
                // Determine the appropriate message based on similarity
                $message = '';
                if ($highest_similarity > 0 && $highest_similarity < $minimum_similarity_threshold) {
                    $message = 'Face similarity too low (' . round($highest_similarity, 2) . '%). Please adjust your angle for a better scan.';
                } else {
                    $message = 'No matching face found. Please try again or use manual selection.';
                }
                
                $response = array(
                    'success' => false, 
                    'message' => $message,
                    'similarity' => $highest_similarity > 0 ? round($highest_similarity, 2) . '%' : 'N/A',
                    'threshold' => $minimum_similarity_threshold . '%',
                    'debug' => $debug_info
                );
            }
        } else {
            // Clean up the temporary file if it exists
            if (!$skip_temp_save && file_exists($temp_filename)) {
                @unlink($temp_filename);
            }
            
            $response = array(
                'success' => false, 
                'message' => 'No registered faces found in the system. Please register faces first.',
                'debug' => $debug_info
            );
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        // Log the error to a file for debugging
        error_log("Face recognition error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ));
        exit;
    }
}

// Process face recognition confirmation
if (isset($_POST['confirm_recognition'])) {
    $confirmation = $_POST['confirmation'];
    $intern_id = isset($_POST['intern_id']) ? $_POST['intern_id'] : null;
    
    // Use either session data or posted intern_id
    if (isset($_SESSION['recognized_face'])) {
        $recognized_face = $_SESSION['recognized_face'];
        if (!$intern_id) {
            $intern_id = $recognized_face['intern_id'];
        }
        
        if ($confirmation === 'yes') {
            // User confirmed it's them, proceed with time logging
            $current_time = date('H:i:s');
            $current_hour = (int)date('H');
            $current_minute = (int)date('i');
            
            // Check if the intern already has a record for today
            $today = date('Y-m-d');
            $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :today");
            $check_stmt->bindParam(':intern_id', $intern_id);
            $check_stmt->bindParam(':today', $today);
            $check_stmt->execute();
            
            $action_taken = '';
            
            if ($check_stmt->rowCount() > 0) {
                $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Determine if it's morning or afternoon
                if ($current_hour < 12) {
                    // Morning session
                    if (isTimeEmpty($timesheet_data['am_timein'])) {
                        // Morning time-in
                        $update_stmt = $conn->prepare("UPDATE timesheet SET am_timein = :time WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                        $update_stmt->bindParam(':time', $current_time);
                        $update_stmt->bindParam(':intern_id', $intern_id);
                        $update_stmt->bindParam(':today', $today);
                        $update_stmt->execute();
                        $action_taken = "Morning time-in recorded at " . formatTime($current_time);
                    } else if (isTimeEmpty($timesheet_data['am_timeOut'])) {
                        // Morning time-out
                        // Apply the rounding rule for morning time-out
                        // If time is at or after 12:00 PM, set to 12:00 PM exactly
                        if ($current_hour == 12) {
                            $am_timeout = '12:00:00'; // 12:00 PM exactly
                            $display_time = "12:00 PM";
                        } else {
                            $am_timeout = $current_time;
                            $display_time = formatTime($current_time);
                        }
                        
                        // Calculate hours worked
                        $time_in = new DateTime($timesheet_data['am_timein']);
                        $time_out = new DateTime($am_timeout);
                        $interval = $time_in->diff($time_out);
                        $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                        
                        $update_stmt = $conn->prepare("UPDATE timesheet SET am_timeOut = :time, am_hours_worked = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                        $update_stmt->bindParam(':time', $am_timeout);
                        $update_stmt->bindParam(':hours', $hours_worked);
                        $update_stmt->bindParam(':intern_id', $intern_id);
                        $update_stmt->bindParam(':today', $today);
                        $update_stmt->execute();
                        
                        // Update total hours for the day
                        updateTotalHours($conn, $intern_id, $today);
                        
                        $action_taken = "Morning time-out recorded at " . $display_time . ". Please return after lunch for afternoon time-in.";
                    } else {
                        // Check if all entries for the day are complete
                        if (!isTimeEmpty($timesheet_data['am_timein']) && !isTimeEmpty($timesheet_data['am_timeOut']) && 
                            !isTimeEmpty($timesheet_data['pm_timein']) && !isTimeEmpty($timesheet_data['pm_timeout'])) {
                            $action_taken = "Your daily duty hours are complete. All time entries for today have been finalized. Please return tomorrow morning to record new time entries.";
                        } else {
                            $action_taken = "Morning session already completed. Please proceed with afternoon time-in after lunch.";
                        }
                    }
                } else {
                    // Afternoon session
                    if (isTimeEmpty($timesheet_data['pm_timein'])) {
                        // Afternoon time-in
                        // Apply the rounding rule for afternoon time-in
                        // If time is between 12:00 PM and 1:00 PM, set to 1:00 PM exactly
                        if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                            $pm_time = '13:00:00'; // 1:00 PM exactly
                            $display_time = "1:00 PM";
                        } else {
                            $pm_time = $current_time;
                            $display_time = formatTime($current_time);
                        }
                        
                        $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timein = :time WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                        $update_stmt->bindParam(':time', $pm_time);
                        $update_stmt->bindParam(':intern_id', $intern_id);
                        $update_stmt->bindParam(':today', $today);
                        $update_stmt->execute();
                        $action_taken = "Afternoon time-in recorded at " . $display_time;
                    } else if (isTimeEmpty($timesheet_data['pm_timeout'])) {
                        // Afternoon time-out
                        // Calculate hours worked
                        $time_in = new DateTime($timesheet_data['pm_timein']);
                        $time_out = new DateTime($current_time);
                        $interval = $time_in->diff($time_out);
                        $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                        
                        $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timeout = :time, pm_hours_worked = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                        $update_stmt->bindParam(':time', $current_time);
                        $update_stmt->bindParam(':hours', $hours_worked);
                        $update_stmt->bindParam(':intern_id', $intern_id);
                        $update_stmt->bindParam(':today', $today);
                        $update_stmt->execute();
                        
                        // Update total hours for the day
                        updateTotalHours($conn, $intern_id, $today);
                        
                        $action_taken = "Afternoon time-out recorded at " . formatTime($current_time) . ". Your daily duty hours are now complete. Please return tomorrow morning to record new time entries.";
                    } else {
                        // Check if all entries for the day are complete
                        if (!isTimeEmpty($timesheet_data['am_timein']) && !isTimeEmpty($timesheet_data['am_timeOut']) && 
                            !isTimeEmpty($timesheet_data['pm_timein']) && !isTimeEmpty($timesheet_data['pm_timeout'])) {
                            $action_taken = "Your daily duty hours are complete. All time entries for today have been finalized. Please return tomorrow morning to record new time entries.";
                        } else {
                            $action_taken = "Afternoon session already completed. Your time entries for today are finalized. Please return tomorrow morning to record new time entries.";
                        }
                    }
                }
            } else {
                // Always create a new record for today, regardless of previous days
                $today = date('Y-m-d');
                // Get intern name and required hours
                $name_stmt = $conn->prepare("SELECT Intern_Name, Required_Hours_Rendered FROM interns WHERE Intern_id = :intern_id");
                $name_stmt->bindParam(':intern_id', $intern_id);
                $name_stmt->execute();
                $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
                $intern_name = $intern_data['Intern_Name'];
                $required_hours = $intern_data['Required_Hours_Rendered'];

                // Make sure we're creating a new record for today
                $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, created_at) 
                                             VALUES (:intern_id, :intern_name, :time_value, '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', :today)");
                
                // Determine if it's morning or afternoon
                if ($current_hour < 12) {
                    // Morning time-in
                    $insert_stmt->bindParam(':intern_id', $intern_id);
                    $insert_stmt->bindParam(':intern_name', $recognized_face['intern_name']);
                    $insert_stmt->bindParam(':time_value', $current_time);
                    $insert_stmt->bindParam(':required_hours', $required_hours);
                    $insert_stmt->bindParam(':today', $today);
                    $insert_stmt->execute();
                    $action_taken = "Morning time-in recorded at " . formatTime($current_time);
                } else {
                    // Afternoon time-in
                    // Apply the rounding rule for afternoon time-in
                    // If time is between 12:00 PM and 1:00 PM, set to 1:00 PM exactly
                    if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                        $pm_time = '13:00:00'; // 1:00 PM exactly
                        $display_time = "1:00 PM";
                    } else {
                        $pm_time = $current_time;
                        $display_time = formatTime($current_time);
                    }
                    
                    $insert_stmt->bindParam(':intern_id', $intern_id);
                    $insert_stmt->bindParam(':intern_name', $recognized_face['intern_name']);
                    $insert_stmt->bindParam(':time_value', $pm_time);
                    $insert_stmt->bindParam(':required_hours', $required_hours);
                    $insert_stmt->bindParam(':today', $today);
                    $insert_stmt->execute();
                    $action_taken = "Afternoon time-in recorded at " . $display_time;
                }
            }
            
            $_SESSION['message'] = "Identity confirmed. " . $action_taken;
        } else {
            // User denied it's them
            $_SESSION['message'] = "Identity not confirmed. Please try again or select manually.";
        }
        
        // Clear the recognized face data
        unset($_SESSION['recognized_face']);
        unset($_SESSION['pending_recognition']);
        
        // Redirect to prevent form resubmission
        header("Location: index.php" . ($confirmation === 'yes' ? "?intern_id=" . $intern_id : ""));
        exit();
    } else {
        $_SESSION['message'] = "Recognition session expired. Please try again.";
        header("Location: index.php");
        exit();
    }
}

// Prepare statement to get all interns
$interns_stmt = $conn->prepare("SELECT * FROM interns ORDER BY Intern_Name ASC");
$interns_stmt->execute();

// Initialize timesheet data
$timesheet_data = null;
$intern_details = null;
$total_time_rendered = '00:00:00';
$all_timesheet_records = array(); // Array to store all timesheet records

// If an intern is selected, fetch their timesheet
if (!empty($selected_intern_id)) {
    // Get intern details for display
    $intern_details_stmt = $conn->prepare("SELECT * FROM interns WHERE Intern_id = :intern_id");
    $intern_details_stmt->bindParam(':intern_id', $selected_intern_id);
    $intern_details_stmt->execute();
    $intern_details = $intern_details_stmt->fetch(PDO::FETCH_ASSOC);

    if ($intern_details) {
        // Initialize timesheet statement for the selected intern for today
        $today = date('Y-m-d');
        $timesheet_stmt = $conn->prepare("SELECT t.*, i.Intern_School as intern_school, i.Required_Hours_Rendered as required_hours, DATE(t.created_at) as render_date 
                                         FROM timesheet t 
                                         JOIN interns i ON t.intern_id = i.Intern_id 
                                         WHERE t.intern_id = :intern_id AND DATE(t.created_at) = :today
                                         ORDER BY t.created_at DESC");
        $timesheet_stmt->bindParam(':intern_id', $selected_intern_id);
        $timesheet_stmt->bindParam(':today', $today);
        $timesheet_stmt->execute();
        
        // Get the timesheet data for today
        $timesheet_data = $timesheet_stmt->fetch(PDO::FETCH_ASSOC);

        // Get all timesheet records for this intern (for historical data)
        $all_records_stmt = $conn->prepare("SELECT t.*, i.Intern_School as intern_school, i.Required_Hours_Rendered as required_hours, DATE(t.created_at) as render_date 
                                           FROM timesheet t 
                                           JOIN interns i ON t.intern_id = i.Intern_id 
                                           WHERE t.intern_id = :intern_id 
                                           ORDER BY t.created_at DESC");
        $all_records_stmt->bindParam(':intern_id', $selected_intern_id);
        $all_records_stmt->execute();
        $all_timesheet_records = $all_records_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total time rendered by the intern
        $total_time_stmt = $conn->prepare("SELECT SUM(TIME_TO_SEC(day_total_hours)) as total_seconds FROM timesheet WHERE intern_id = :intern_id");
        $total_time_stmt->bindParam(':intern_id', $selected_intern_id);
        $total_time_stmt->execute();
        $total_time_data = $total_time_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Convert seconds back to time format
        $total_seconds = $total_time_data['total_seconds'] ?: 0;
        $total_time_rendered = secondsToTime($total_seconds);
    }
} else {
    // Clear timesheet data when no student is selected
    $timesheet_data = null;
    $intern_details = null;
    $total_time_rendered = '00:00:00';
    $all_timesheet_records = array();
}

// Process form submissions only on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Time In functionality
    if (isset($_POST['time_in']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $current_time = date('H:i:s');
        $current_hour = (int)date('H');
        $current_minute = (int)date('i');
        $today = date('Y-m-d');

        // Check if the intern already has a record for TODAY specifically
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :today");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':today', $today);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

            // Prevent normal time-in after 5PM, require overtime
            if ($current_hour >= 17) {
                // Only allow overtime time-in after 5PM
                if (isTimeEmpty($timesheet_data['overtime_start'])) {
                    // Start overtime
                    $update_stmt = $conn->prepare("UPDATE timesheet SET overtime_start = :time WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                    $update_stmt->bindParam(':time', $current_time);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':today', $today);
                    $update_stmt->execute();
                    $_SESSION['message'] = "Overtime started at " . formatTime($current_time);
                } else {
                    $_SESSION['message'] = "Overtime already started for today.";
                }
            } else {
                // Normal morning/afternoon time-in logic (unchanged)
                if ($current_hour < 12) {
                    if (isTimeEmpty($timesheet_data['am_timein'])) {
                        $update_stmt = $conn->prepare("UPDATE timesheet SET am_timein = :time WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                        $update_stmt->bindParam(':time', $current_time);
                        $update_stmt->bindParam(':intern_id', $intern_id);
                        $update_stmt->bindParam(':today', $today);
                        $update_stmt->execute();
                        $_SESSION['message'] = "Morning time-in recorded successfully at " . formatTime($current_time);
                    } else {
                        $_SESSION['message'] = "Morning time-in already recorded for today.";
                    }
                } else {
                    if (isTimeEmpty($timesheet_data['pm_timein'])) {
                        // Apply the rounding rule for afternoon time-in
                        if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                            $pm_time = '13:00:00';
                            $display_time = "1:00 PM";
                        } else {
                            $pm_time = $current_time;
                            $display_time = formatTime($current_time);
                        }
                        $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timein = :time WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                        $update_stmt->bindParam(':time', $pm_time);
                        $update_stmt->bindParam(':intern_id', $intern_id);
                        $update_stmt->bindParam(':today', $today);
                        $update_stmt->execute();
                        $_SESSION['message'] = "Afternoon time-in recorded successfully at " . $display_time;
                    } else {
                        $_SESSION['message'] = "Afternoon time-in already recorded for today.";
                    }
                }
            }
        } else {
            // Create a new record for today
            $name_stmt = $conn->prepare("SELECT Intern_Name, Required_Hours_Rendered FROM interns WHERE Intern_id = :intern_id");
            $name_stmt->bindParam(':intern_id', $intern_id);
            $name_stmt->execute();
            $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
            $intern_name = $intern_data['Intern_Name'];
            $required_hours = $intern_data['Required_Hours_Rendered'];

            if ($current_hour >= 17) {
                // Only allow overtime time-in after 5PM
                $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, overtime_start, created_at) 
                    VALUES (:intern_id, :intern_name, '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', :overtime_start, NOW())");
                $insert_stmt->bindParam(':intern_id', $intern_id);
                $insert_stmt->bindParam(':intern_name', $intern_name);
                $insert_stmt->bindParam(':required_hours', $required_hours);
                $insert_stmt->bindParam(':overtime_start', $current_time);
                $insert_stmt->execute();
                $_SESSION['message'] = "Overtime started at " . formatTime($current_time);
            } else if ($current_hour < 12) {
                $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, created_at) 
                    VALUES (:intern_id, :intern_name, :am_timein, '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', NOW())");
                $insert_stmt->bindParam(':intern_id', $intern_id);
                $insert_stmt->bindParam(':intern_name', $intern_name);
                $insert_stmt->bindParam(':am_timein', $current_time);
                $insert_stmt->bindParam(':required_hours', $required_hours);
                $insert_stmt->execute();
                $_SESSION['message'] = "Morning time-in recorded successfully at " . formatTime($current_time);
            } else {
                // Afternoon time-in
                if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                    $pm_time = '13:00:00';
                    $display_time = "1:00 PM";
                } else {
                    $pm_time = $current_time;
                    $display_time = formatTime($current_time);
                }
                $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, created_at) 
                    VALUES (:intern_id, :intern_name, '00:00:00', '00:00:00', :pm_timein, '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', NOW())");
                $insert_stmt->bindParam(':intern_id', $intern_id);
                $insert_stmt->bindParam(':intern_name', $intern_name);
                $insert_stmt->bindParam(':pm_timein', $pm_time);
                $insert_stmt->bindParam(':required_hours', $required_hours);
                $insert_stmt->execute();
                $_SESSION['message'] = "Afternoon time-in recorded successfully at " . $display_time;
            }
        }

        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Time Out functionality
    if (isset($_POST['time_out']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $current_time = date('H:i:s');
        $current_hour = (int)date('H');
        $current_minute = (int)date('i');
        $today = date('Y-m-d');
        
        // Check if the intern has a record for today
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :today");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':today', $today);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Determine if it's morning or afternoon
            if ($current_hour < 12 || ($current_hour == 12 && $current_minute == 0)) {
                // Morning time-out
                if (!isTimeEmpty($timesheet_data['am_timein']) && isTimeEmpty($timesheet_data['am_timeOut'])) {
                    // Apply the rounding rule for morning time-out
                    // If time is at or after 12:00 PM, set to 12:00 PM exactly
                    if ($current_hour == 12) {
                        $am_timeout = '12:00:00'; // 12:00 PM exactly
                        $display_time = "12:00 PM";
                    } else {
                        $am_timeout = $current_time;
                        $display_time = formatTime($current_time);
                    }
                    
                    // Calculate hours worked
                    $time_in = new DateTime($timesheet_data['am_timein']);
                    $time_out = new DateTime($am_timeout);
                    $interval = $time_in->diff($time_out);
                    $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                    
                    $update_stmt = $conn->prepare("UPDATE timesheet SET am_timeOut = :time, am_hours_worked = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                    $update_stmt->bindParam(':time', $am_timeout);
                    $update_stmt->bindParam(':hours', $hours_worked);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':today', $today);
                    $update_stmt->execute();
                    
                    // Update total hours for the day
                    updateTotalHours($conn, $intern_id, $today);
                    
                    $_SESSION['message'] = "Morning time-out recorded successfully at " . $display_time . ". Please return after lunch for afternoon time-in.";
                } else {
                    // Check if all entries for the day are complete
                    if (!isTimeEmpty($timesheet_data['am_timein']) && !isTimeEmpty($timesheet_data['am_timeOut']) && 
                        !isTimeEmpty($timesheet_data['pm_timein']) && !isTimeEmpty($timesheet_data['pm_timeout'])) {
                        $_SESSION['message'] = "Your daily duty hours are complete. All time entries for today have been finalized. Please return tomorrow morning to record new time entries.";
                    } else if (isTimeEmpty($timesheet_data['am_timein'])) {
                        $_SESSION['message'] = "Cannot record morning time-out. You need to time-in first before timing out.";
                    } else {
                        $_SESSION['message'] = "Morning time-out already recorded. Please proceed with afternoon time-in after lunch.";
                    }
                }
            } else {
                // Afternoon session
                if (isTimeEmpty($timesheet_data['pm_timein'])) {
                    // Afternoon time-in
                    // Apply the rounding rule for afternoon time-in
                    // If time is between 12:00 PM and 1:00 PM, set to 1:00 PM exactly
                    if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                        $pm_time = '13:00:00'; // 1:00 PM exactly
                        $display_time = "1:00 PM";
                    } else {
                        $pm_time = $current_time;
                        $display_time = formatTime($current_time);
                    }
                    
                    $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timein = :time WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                    $update_stmt->bindParam(':time', $pm_time);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':today', $today);
                    $update_stmt->execute();
                    $_SESSION['message'] = "Afternoon time-in recorded successfully at " . $display_time;
                } else if (isTimeEmpty($timesheet_data['pm_timeout'])) {
                    // Afternoon time-out
                    // Calculate hours worked
                    $time_in = new DateTime($timesheet_data['pm_timein']);
                    $time_out = new DateTime($current_time);
                    $interval = $time_in->diff($time_out);
                    $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                    
                    $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timeout = :time, pm_hours_worked = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                    $update_stmt->bindParam(':time', $current_time);
                    $update_stmt->bindParam(':hours', $hours_worked);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':today', $today);
                    $update_stmt->execute();
                    
                    // Update total hours for the day
                    updateTotalHours($conn, $intern_id, $today);
                    
                    $_SESSION['message'] = "Afternoon time-out recorded successfully at " . formatTime($current_time) . ". Your daily duty hours are now complete. Please return tomorrow morning to record new time entries.";
                } else {
                    // Check if all entries for the day are complete
                    if (!isTimeEmpty($timesheet_data['am_timein']) && !isTimeEmpty($timesheet_data['am_timeOut']) && 
                        !isTimeEmpty($timesheet_data['pm_timein']) && !isTimeEmpty($timesheet_data['pm_timeout'])) {
                        $_SESSION['message'] = "Your daily duty hours are complete. All time entries for today have been finalized. Please return tomorrow morning to record new time entries.";
                    } else if (isTimeEmpty($timesheet_data['pm_timein'])) {
                        $_SESSION['message'] = "Cannot record afternoon time-out. You need to time-in first before timing out.";
                    } else {
                        $_SESSION['message'] = "Afternoon time-out already recorded. Your time entries for today are finalized. Please return tomorrow morning to record new time entries.";
                    }
                }
            }
        } else {
            $_SESSION['message'] = "No time-in record found for today. Please time in first.";
        }
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Reset entries functionality
    if (isset($_POST['reset_entries']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];

        try {
            $conn->beginTransaction();

            // Delete all timesheet records for this intern
            $delete_timesheet = $conn->prepare("DELETE FROM timesheet WHERE intern_id = :intern_id");
            $delete_timesheet->bindParam(':intern_id', $intern_id, PDO::PARAM_INT);
            $delete_timesheet->execute();

            // Delete all notes for this intern
            $delete_notes = $conn->prepare("DELETE FROM intern_notes WHERE intern_id = :intern_id");
            $delete_notes->bindParam(':intern_id', $intern_id, PDO::PARAM_INT);
            $delete_notes->execute();

            $conn->commit();
            $_SESSION['message'] = "All timesheet records and notes for the selected intern have been reset.";
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['message'] = "Error resetting entries: " . $e->getMessage();
        }

        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Delete student functionality
    if (isset($_POST['delete_student']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Delete from timesheet first (foreign key constraint)
        $delete_timesheet_stmt = $conn->prepare("DELETE FROM timesheet WHERE intern_id = :intern_id");
        $delete_timesheet_stmt->bindParam(':intern_id', $intern_id);
        $delete_timesheet_stmt->execute();
        
        // Then delete from interns
        $delete_intern_stmt = $conn->prepare("DELETE FROM interns WHERE Intern_id = :intern_id");
        $delete_intern_stmt->bindParam(':intern_id', $intern_id);
        $delete_intern_stmt->execute();
        
        $_SESSION['message'] = "Student deleted successfully.";
        
        // Redirect to prevent form resubmission
        header("Location: index.php");
        exit();
    }

    // Export to CSV functionality
    if (isset($_POST['export_csv']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];

        // Fetch all timesheet records for the intern
        $export_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id ORDER BY created_at DESC");
        $export_stmt->bindParam(':intern_id', $intern_id);
        $export_stmt->execute();
        $records = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($records) > 0) {
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="timesheet_export_' . $intern_id . '_' . date('Ymd_His') . '.csv"');

            $output = fopen('php://output', 'w');

            // Output column headers
            fputcsv($output, array_keys($records[0]));

            // Output data rows
            foreach ($records as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
            exit();
        } else {
            $_SESSION['message'] = "No records found to export for this intern.";
            header("Location: index.php?intern_id=" . $intern_id);
            exit();
        }
    }
    
    // If we get here with a POST request but no specific action was taken,
    // redirect to prevent form resubmission on refresh
    if (!empty($selected_intern_id)) {
        header("Location: index.php?intern_id=" . $selected_intern_id);
    } else {
        header("Location: index.php");
    }
    exit();
}

// Helper function to check if time is empty (00:00:00)
function isTimeEmpty($time) {
    return $time == '00:00:00' || $time == '00:00:00.000000' || $time == null;
}

// Helper function to format time (for clock times with AM/PM)
function formatTime($time) {
    if (isTimeEmpty($time)) {
        return '-';
    }
    
    // Convert to DateTime object
    $time_obj = new DateTime($time);
    
    // Format as 12-hour time with AM/PM
    return $time_obj->format('h:i A');
}

// Helper function to format duration (for hours worked)
function formatDuration($duration) {
    if (isTimeEmpty($duration)) {
        return '-';
    }
    
    // Clean up the duration string to handle microseconds
    $duration = preg_replace('/\.\d+/', '', $duration);
    
    // Parse the duration components
    $parts = explode(':', $duration);
    $hours = (int)$parts[0];
    $minutes = (int)$parts[1];
    $seconds = (int)$parts[2];
    
    // Format the duration in a user-friendly way
    if ($hours > 0) {
        return sprintf('%d hr %d min', $hours, $minutes);
    } else if ($minutes > 0) {
        return sprintf('%d min %d sec', $minutes, $seconds);
    } else {
        return sprintf('%d sec', $seconds);
    }
}

// Helper function to update total hours for the day
function updateTotalHours($conn, $intern_id, $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }

    // Get current hours for the specific date, including overtime
    $hours_stmt = $conn->prepare("SELECT am_hours_worked, pm_hours_worked, overtime_hours FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :date");
    $hours_stmt->bindParam(':intern_id', $intern_id);
    $hours_stmt->bindParam(':date', $date);
    $hours_stmt->execute();
    $hours_data = $hours_stmt->fetch(PDO::FETCH_ASSOC);

    if ($hours_data) {
        $am_hours = isTimeEmpty($hours_data['am_hours_worked']) ? '00:00:00' : $hours_data['am_hours_worked'];
        $pm_hours = isTimeEmpty($hours_data['pm_hours_worked']) ? '00:00:00' : $hours_data['pm_hours_worked'];
        $overtime_hours = isTimeEmpty($hours_data['overtime_hours']) ? '00:00:00' : $hours_data['overtime_hours'];

        // Convert to seconds
        $am_seconds = timeToSeconds($am_hours);
        $pm_seconds = timeToSeconds($pm_hours);
        $overtime_seconds = timeToSeconds($overtime_hours);
        $total_seconds = $am_seconds + $pm_seconds + $overtime_seconds;

        // Convert back to time format
        $total_hours = secondsToTime($total_seconds);

        // Update total hours for the specific date
        $update_stmt = $conn->prepare("UPDATE timesheet SET day_total_hours = :total WHERE intern_id = :intern_id AND DATE(created_at) = :date");
        $update_stmt->bindParam(':total', $total_hours);
        $update_stmt->bindParam(':intern_id', $intern_id);
        $update_stmt->bindParam(':date', $date);
        $update_stmt->execute();

        // Update cumulative total hours for the intern
        updateCumulativeTotalHours($conn, $intern_id);
    }
}

// Helper function to update cumulative total hours for an intern
function updateCumulativeTotalHours($conn, $intern_id) {
    // Calculate the sum of all day_total_hours for this intern
    $total_stmt = $conn->prepare("SELECT SUM(TIME_TO_SEC(day_total_hours)) as total_seconds FROM timesheet WHERE intern_id = :intern_id");
    $total_stmt->bindParam(':intern_id', $intern_id);
    $total_stmt->execute();
    $total_data = $total_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($total_data) {
        $total_seconds = $total_data['total_seconds'] ?: 0;
        $total_hours = secondsToTime($total_seconds);
        
        // Update total_hours_rendered for all records of this intern
        $update_stmt = $conn->prepare("UPDATE timesheet SET total_hours_rendered = :total WHERE intern_id = :intern_id");
        $update_stmt->bindParam(':total', $total_hours);
        $update_stmt->bindParam(':intern_id', $intern_id);
        $update_stmt->execute();
    }
}

// Helper function to convert time to seconds
function timeToSeconds($time) {
    // Clean up the time string to handle microseconds
    $time = preg_replace('/\.\d+/', '', $time);
    
    $parts = explode(':', $time);
    return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
}

// Helper function to convert seconds to time
function secondsToTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

// Helper function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Get selected student name for delete modal
$selected_student_name = '';
if (!empty($selected_intern_id)) {
    $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
    $name_stmt->bindParam(':intern_id', $selected_intern_id);
    $name_stmt->execute();
    $name_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
    $selected_student_name = $name_data ? $name_data['Intern_Name'] : '';
}

if (isset($_POST['overtime']) && !empty($_POST['intern_id'])) {
    $intern_id = $_POST['intern_id'];
    $current_time = date('H:i:s');
    $today = date('Y-m-d');

    // Check if the intern already has a record for TODAY
    $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :today");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':today', $today);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if (isTimeEmpty($timesheet_data['overtime_start'])) {
            // FIX: Actually update the overtime_start field!
            $update_stmt = $conn->prepare("UPDATE timesheet SET overtime_start = :time WHERE intern_id = :intern_id AND DATE(created_at) = :today");
            $update_stmt->bindParam(':time', $current_time);
            $update_stmt->bindParam(':intern_id', $intern_id);
            $update_stmt->bindParam(':today', $today);
            $update_stmt->execute();
            $_SESSION['message'] = "Overtime started at " . formatTime($current_time);
        } else {
            $_SESSION['message'] = "Overtime already started for today.";
        }
    } else {
        // Create a new record for today with overtime_start
        $name_stmt = $conn->prepare("SELECT Intern_Name, Required_Hours_Rendered FROM interns WHERE Intern_id = :intern_id");
        $name_stmt->bindParam(':intern_id', $intern_id);
        $name_stmt->execute();
        $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
        $intern_name = $intern_data['Intern_Name'];
        $required_hours = $intern_data['Required_Hours_Rendered'];

        // FIX: Insert overtime_start with the correct value
        $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, overtime_start, overtime_end, overtime_hours, created_at) 
            VALUES (:intern_id, :intern_name, '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', :overtime_start, '00:00:00', '00:00:00', NOW())");
        $insert_stmt->bindParam(':intern_id', $intern_id);
        $insert_stmt->bindParam(':intern_name', $intern_name);
        $insert_stmt->bindParam(':required_hours', $required_hours);
        $insert_stmt->bindParam(':overtime_start', $current_time);
        $insert_stmt->execute();
        $_SESSION['message'] = "Overtime started at " . formatTime($current_time);
    }

    // Redirect to prevent form resubmission
    header("Location: index.php?intern_id=" . $intern_id);
    exit();
}

function calculateOvertime($timesheet) {
    // Only calculate overtime if pm_timeout is set (afternoon is complete)
    if (!isTimeEmpty($timesheet['pm_timeout'])) {
        $pm_timeout = new DateTime($timesheet['pm_timeout']);
        $standard_end_time = new DateTime('17:00:00'); // 5:00 PM standard end time
        
        // Only count overtime if timeout is after standard end time
        if ($pm_timeout > $standard_end_time) {
            $interval = $pm_timeout->diff($standard_end_time);
            return $interval->format('%H:%I:%S');
        }
    }
    
    return '00:00:00';
}

// Add this function to check if it's afternoon (for overtime visibility)
function isAfternoon() {
    $current_hour = (int)date('H');
    return $current_hour >= 12;
}


// Modify your total hours calculation to include overtime
function calculateTotalHours($am_hours, $pm_hours, $overtime_hours) {
    $total_seconds = 0;
    
    // Add AM hours
    if (!isTimeEmpty($am_hours)) {
        list($h, $m, $s) = explode(':', $am_hours);
        $total_seconds += $h * 3600 + $m * 60 + $s;
    }
    
    // Add PM hours
    if (!isTimeEmpty($pm_hours)) {
        list($h, $m, $s) = explode(':', $pm_hours);
        $total_seconds += $h * 3600 + $m * 60 + $s;
    }
    
    // Add overtime hours
    if (!isTimeEmpty($overtime_hours)) {
        list($h, $m, $s) = explode(':', $overtime_hours);
        $total_seconds += $h * 3600 + $m * 60 + $s;
    }
    
    return gmdate('H:i:s', $total_seconds);
}

// Check if timeout is being processed
if (isset($_POST['time_out']) && !empty($_POST['intern_id'])) {
    $intern_id = $_POST['intern_id'];
    $current_time = date('H:i:s');
    $today = date('Y-m-d');

    // End overtime if it was started but not ended
    $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :today");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':today', $today);
    $check_stmt->execute();
    $row = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['overtime_start']) && ($row['overtime_end'] == '00:00:00' || empty($row['overtime_end']))) {
        $overtime_start = strtotime($row['overtime_start']);
        $overtime_end = strtotime($current_time);
        $overtime_seconds = $overtime_end - $overtime_start;
        $overtime_time = gmdate('H:i:s', $overtime_seconds);

        $update_stmt = $conn->prepare("UPDATE timesheet SET overtime_end = :end, overtime_hours = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :today");
        $update_stmt->bindParam(':end', $current_time);
        $update_stmt->bindParam(':hours', $overtime_time);
        $update_stmt->bindParam(':intern_id', $intern_id);
        $update_stmt->bindParam(':today', $today);
        $update_stmt->execute();

        // Update total hours for the day to include overtime
        updateTotalHours($conn, $intern_id, $today);
    }
}
