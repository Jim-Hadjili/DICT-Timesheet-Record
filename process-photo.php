<?php
// Start session for potential messages
session_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Include database connection
include 'connection/conn.php';

// Function to check if the request is POST
function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Function to generate a unique filename
function generateUniqueFilename($intern_id, $action) {
    $timestamp = time();
    return "face_{$intern_id}_{$timestamp}.png";
}

// Function to determine the photo type based on time and action
function getPhotoType($action) {
    $hour = (int)date('H');
    
    if ($action === 'time_in') {
        if ($hour < 12) {
            return 'morning_time_in';
        } else {
            return 'afternoon_time_in';
        }
    } else if ($action === 'time_out') {
        if ($hour < 12) {
            return 'morning_time_out';
        } else {
            return 'afternoon_time_out';
        }
    }
    
    return 'unknown';
}

// Main process
if (isPostRequest()) {
    $response = [
        'success' => false,
        'message' => 'An error occurred while processing your request.'
    ];
    
    try {
        // Get the POST data
        $intern_id = isset($_POST['intern_id']) ? $_POST['intern_id'] : null;
        $action = isset($_POST['action']) ? $_POST['action'] : null;
        $photo_data = isset($_POST['photo_data']) ? $_POST['photo_data'] : null;
        
        // Validate inputs
        if (!$intern_id || !$action || !$photo_data) {
            $response['message'] = 'Missing required parameters.';
            echo json_encode($response);
            exit();
        }
        
        // Extract the base64 data
        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        // Check if the uploads directory exists, if not create it
        $upload_dir = 'uploads/faces/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate a unique filename
        $filename = generateUniqueFilename($intern_id, $action);
        $filepath = $upload_dir . $filename;
        
        // Save the image
        if (file_put_contents($filepath, $photo_binary)) {
            // Determine the photo type
            $photo_type = getPhotoType($action);
            
            // Create or check for timesheet record table
            $check_table_sql = "SHOW TABLES LIKE 'timesheet_photos'";
            $check_result = $conn->query($check_table_sql);
            
            if ($check_result->rowCount() === 0) {
                // Create the table
                $create_table_sql = "CREATE TABLE timesheet_photos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    intern_id INT NOT NULL,
                    record_id INT,
                    photo_path VARCHAR(255) NOT NULL,
                    photo_type VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (intern_id) REFERENCES interns(Intern_id)
                )";
                $conn->exec($create_table_sql);
            }
            
            // Get the current timesheet record ID
            $today = date('Y-m-d');
            $record_stmt = $conn->prepare("SELECT record_id FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :date");
            $record_stmt->bindParam(':intern_id', $intern_id);
            $record_stmt->bindParam(':date', $today);
            $record_stmt->execute();
            $record = $record_stmt->fetch(PDO::FETCH_ASSOC);
            $record_id = $record ? $record['record_id'] : null;
            
            // Store the filepath in session to handle first-time case
            // where record doesn't exist yet
            $_SESSION['pending_photo'] = [
                'filepath' => $filepath,
                'photo_type' => $photo_type,
                'intern_id' => $intern_id,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert the photo record if record_id exists
            if ($record_id) {
                $insert_stmt = $conn->prepare("INSERT INTO timesheet_photos (intern_id, record_id, photo_path, photo_type) VALUES (:intern_id, :record_id, :photo_path, :photo_type)");
                $insert_stmt->bindParam(':intern_id', $intern_id);
                $insert_stmt->bindParam(':record_id', $record_id);
                $insert_stmt->bindParam(':photo_path', $filepath);
                $insert_stmt->bindParam(':photo_type', $photo_type);
                $insert_stmt->execute();
                
                $photo_id = $conn->lastInsertId();
                
                // Clear pending photo since we've saved it properly
                unset($_SESSION['pending_photo']);
            } else {
                // For the first time-in, we don't have a record_id yet
                // We'll store the photo details in session and save it after the record is created
                $photo_id = 'pending';
            }
            
            $response['success'] = true;
            $response['message'] = 'Photo captured and saved successfully.';
            $response['photo_id'] = $photo_id;
            $response['filepath'] = $filepath;
        } else {
            $response['message'] = 'Failed to save the photo.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    // Not a POST request
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}
?>