<?php
// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include '../connection/conn.php';

// Initialize variables
$message = "";
$matched_intern = null;

// Process image upload for recognition
if (isset($_POST['recognize_face'])) {
    try {
        // Get the image data
        $img_data = $_POST['image_data'];
        
        // Remove the data URL prefix and decode base64 data
        $img_data = str_replace('data:image/png;base64,', '', $img_data);
        $img_data = str_replace(' ', '+', $img_data);
        $data = base64_decode($img_data);
        
        // Create directory if it doesn't exist
        $upload_dir = 'temp_faces/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate a unique filename for the temporary image
        $temp_filename = $upload_dir . 'temp_' . time() . '.png';
        
        // Save the temporary image file
        file_put_contents($temp_filename, $data);
        
        // Get all interns with registered faces
        $stmt = $conn->prepare("SELECT * FROM interns WHERE Face_Registered = 1");
        $stmt->execute();
        $registered_interns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $best_match = null;
        $highest_similarity = 0;
        $minimum_similarity_threshold = 70; // Set a higher threshold for more accurate matching
        
        // If we have registered interns, perform face matching
        if (count($registered_interns) > 0) {
            // Compare the captured face with stored face images
            foreach ($registered_interns as $intern) {
                // Check if the intern has a registered face image path
                if (!isset($intern['Face_Image_Path']) || empty($intern['Face_Image_Path'])) {
                    continue; // Skip interns without a registered face image
                }
                
                $face_file = $intern['Face_Image_Path'];
                
                // If the file doesn't exist, check in the functions directory
                if (!file_exists($face_file) && strpos($face_file, 'face_images/') !== false) {
                    $face_file = $face_file; // Already in the correct directory
                } else if (!file_exists($face_file)) {
                    // Try to find it using glob
                    $face_dir = 'face_images/';
                    $face_pattern = $face_dir . 'face_' . $intern['Intern_id'] . '_*.png';
                    $face_files = glob($face_pattern);
                    
                    if (!empty($face_files)) {
                        // Use the most recent face image
                        $face_file = end($face_files);
                    } else {
                        continue; // Skip if no face image found
                    }
                }
                
                // Compare the captured image with the stored face image
                if (file_exists($face_file) && file_exists($temp_filename)) {
                    // Get image dimensions and calculate a similarity score
                    $temp_image_size = filesize($temp_filename);
                    $stored_image_size = filesize($face_file);
                    
                    // Calculate a similarity score (0-100) based on image properties
                    $size_diff = abs($temp_image_size - $stored_image_size);
                    $size_similarity = max(0, 100 - ($size_diff / 1000));
                    
                    // Get image dimensions for additional comparison
                    $temp_image_info = getimagesize($temp_filename);
                    $stored_image_info = getimagesize($face_file);
                    
                    // Compare aspect ratios
                    $temp_aspect = $temp_image_info[0] / $temp_image_info[1];
                    $stored_aspect = $stored_image_info[0] / $stored_image_info[1];
                    $aspect_diff = abs($temp_aspect - $stored_aspect);
                    $aspect_similarity = max(0, 100 - ($aspect_diff * 100));
                    
                    // Compare image histograms for better matching
                    $temp_image = imagecreatefrompng($temp_filename);
                    $stored_image = imagecreatefrompng($face_file);
                    
                    // Convert to grayscale for better comparison
                    imagefilter($temp_image, IMG_FILTER_GRAYSCALE);
                    imagefilter($stored_image, IMG_FILTER_GRAYSCALE);
                    
                    // Calculate histograms
                    $temp_hist = [];
                    $stored_hist = [];
                    
                    // Simple histogram calculation (256 bins for grayscale)
                    for ($i = 0; $i < 256; $i++) {
                        $temp_hist[$i] = 0;
                        $stored_hist[$i] = 0;
                    }
                    
                    // Sample pixels for histogram (for performance)
                    $sample_width = min($temp_image_info[0], $stored_image_info[0]);
                    $sample_height = min($temp_image_info[1], $stored_image_info[1]);
                    $sample_step = max(1, floor($sample_width * $sample_height / 10000)); // Sample ~10000 pixels
                    
                    for ($y = 0; $y < $sample_height; $y += $sample_step) {
                        for ($x = 0; $x < $sample_width; $x += $sample_step) {
                            $temp_rgb = imagecolorat($temp_image, $x, $y);
                            $temp_r = ($temp_rgb >> 16) & 0xFF;
                            $temp_hist[$temp_r]++;
                            
                            $stored_rgb = imagecolorat($stored_image, $x, $y);
                            $stored_r = ($stored_rgb >> 16) & 0xFF;
                            $stored_hist[$stored_r]++;
                        }
                    }
                    
                    // Normalize histograms
                    $temp_sum = array_sum($temp_hist);
                    $stored_sum = array_sum($stored_hist);
                    
                    if ($temp_sum > 0 && $stored_sum > 0) {
                        for ($i = 0; $i < 256; $i++) {
                            $temp_hist[$i] /= $temp_sum;
                            $stored_hist[$i] /= $stored_sum;
                        }
                    }
                    
                    // Calculate histogram intersection (similarity measure)
                    $hist_similarity = 0;
                    for ($i = 0; $i < 256; $i++) {
                        $hist_similarity += min($temp_hist[$i], $stored_hist[$i]);
                    }
                    $hist_similarity *= 100; // Scale to 0-100
                    
                    // Clean up
                    imagedestroy($temp_image);
                    imagedestroy($stored_image);
                    
                    // Calculate overall similarity (weighted average)
                    $similarity = ($size_similarity * 0.1) + ($aspect_similarity * 0.2) + ($hist_similarity * 0.7);
                    
                    // Keep track of the best match
                    if ($similarity > $highest_similarity) {
                        $highest_similarity = $similarity;
                        $best_match = $intern;
                    }
                }
            }
            
            // If we found a match with sufficient similarity
            if ($best_match && $highest_similarity > $minimum_similarity_threshold) {
                $matched_intern = $best_match;
                $message = "Face recognized successfully! (Similarity: " . round($highest_similarity, 2) . "%)";
            } else {
                $message = "No matching face found. Please try again or use manual selection.";
            }
        } else {
            $message = "No registered faces found in the system.";
        }
        
        // Clean up the temporary file
        if (file_exists($temp_filename)) {
            unlink($temp_filename);
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Recognition - DICT Internship</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dict: {
                            blue: '#0056b3',
                            lightblue: '#e6f0ff',
                            red: '#d9364c',
                            yellow: '#ffc107',
                            dark: '#343a40',
                            light: '#f8f9fa'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        'custom': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                        'custom-lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        /* Fade out animation for notifications */
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        .fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }
        
        /* Custom styles */
        .dict-gradient {
            background: linear-gradient(135deg, #0056b3 0%, #003380 100%);
        }
        
        /* Face overlay for positioning */
        .face-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 100 100"><ellipse cx="50" cy="50" rx="35" ry="45" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-dasharray="5,5"/></svg>');
            background-position: center;
            background-repeat: no-repeat;
            pointer-events: none;
        }
        
        /* Scanning animation */
        @keyframes scanAnimation {
            0% {
                transform: translateY(0);
                opacity: 0.7;
            }
            50% {
                transform: translateY(100%);
                opacity: 0.3;
            }
            100% {
                transform: translateY(0);
                opacity: 0.7;
            }
        }

        .scanning-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, rgba(14, 165, 233, 0), rgba(14, 165, 233, 0.8), rgba(14, 165, 233, 0));
            animation: scanAnimation 2s linear infinite;
            z-index: 10;
        }
        
        /* Success animation */
        @keyframes successPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(34, 197, 94, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
        }
        
        .success-pulse {
            animation: successPulse 2s infinite;
        }
        
        /* Button hover effects */
        .btn-hover-effect {
            transition: all 0.3s ease;
        }
        
        .btn-hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }
        
        /* Card hover effect */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
        }
        
        /* Progress steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 30px;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #e2e8f0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            background-color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e2e8f0;
            font-weight: bold;
            color: #64748b;
        }
        
        .step.active {
            border-color: #0056b3;
            background-color: #0056b3;
            color: white;
        }
        
        .step-label {
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 12px;
            color: #64748b;
        }
        
        .step.active .step-label {
            color: #0056b3;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
    <div class="w-full max-w-4xl mx-auto p-4">
        <!-- Header with Logo -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden mb-6 card-hover">
            <div class="dict-gradient text-white p-6">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div class="flex items-center mb-4 md:mb-0">
                        <img src="../assets/images/Dict.png" alt="DICT Logo" class="h-16 mr-4">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold">Face Recognition</h1>
                            <p class="text-blue-100">Department of Information and Communications Technology</p>
                        </div>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-medium">Zamboanga City, Philippines</p>
                        <p><?php echo date('F d, Y'); ?> • <span id="current-time"></span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Progress Steps -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden mb-6 p-6">
            <div class="progress-steps">
                <div class="step">
                    1
                    <span class="step-label">Registration</span>
                </div>
                <div class="step active">
                    2
                    <span class="step-label">Face Recognition</span>
                </div>
                <div class="step">
                    3
                    <span class="step-label">Attendance</span>
                </div>
            </div>
            
            <div class="text-center text-sm text-gray-600">
                <p>Position your face in the camera frame for automatic recognition</p>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if($message != ""): ?>
        <div id="alert-message" class="mb-6 rounded-lg p-4 <?php echo strpos($message, 'successfully') !== false ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'bg-red-100 text-red-800 border-l-4 border-red-500'; ?> transition-all duration-500 ease-in-out shadow-custom">
            <div class="flex items-center">
                <i class="<?php echo strpos($message, 'successfully') !== false ? 'fas fa-check-circle text-green-500' : 'fas fa-exclamation-circle text-red-500'; ?> mr-2 text-xl"></i>
                <p class="font-medium"><?php echo $message; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($matched_intern): ?>
        <!-- Recognition Result -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden card-hover">
            <div class="p-6 md:p-8">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4 success-pulse">
                        <i class="fas fa-check text-green-500 text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800">Student Identified</h2>
                    <p class="text-gray-600 mt-1">Welcome back, <?php echo htmlspecialchars($matched_intern['Intern_Name']); ?>!</p>
                </div>
                
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
                        <h3 class="font-semibold text-gray-800 mb-3">Student Information</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Name:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($matched_intern['Intern_Name']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">School:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($matched_intern['Intern_School']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Age:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($matched_intern['Intern_Age']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Gender:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($matched_intern['Intern_Gender']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-dict-blue bg-opacity-10 p-5 rounded-lg border border-dict-blue border-opacity-20">
                        <h3 class="font-semibold text-dict-blue mb-3">Internship Progress</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Required Hours:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($matched_intern['Required_Hours_Rendered']); ?> hours</span>
                            </div>
                            
                            <?php
                            // Calculate rendered hours if available
                            $rendered_hours = 0;
                            if (isset($matched_intern['Hours_Rendered'])) {
                                $rendered_hours = $matched_intern['Hours_Rendered'];
                            }
                            
                            // Calculate progress percentage
                            $progress_percentage = 0;
                            if ($matched_intern['Required_Hours_Rendered'] > 0) {
                                $progress_percentage = min(100, round(($rendered_hours / $matched_intern['Required_Hours_Rendered']) * 100));
                            }
                            ?>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600">Hours Rendered:</span>
                                <span class="font-medium"><?php echo $rendered_hours; ?> hours</span>
                            </div>
                            
                            <div class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <span>Progress</span>
                                    <span><?php echo $progress_percentage; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-dict-blue h-2.5 rounded-full" style="width: <?php echo $progress_percentage; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="../index.php?intern_id=<?php echo $matched_intern['Intern_id']; ?>&face_recognized=1" class="flex-1 bg-dict-blue hover:bg-blue-700 text-white font-medium rounded-lg text-sm px-5 py-3 text-center transition duration-300 ease-in-out flex items-center justify-center btn-hover-effect">
                        <i class="fas fa-clock mr-2"></i>
                        Proceed to Timesheet
                    </a>
                    <a href="../index.php" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg text-sm px-5 py-3 text-center transition duration-300 ease-in-out flex items-center justify-center btn-hover-effect">
                        <i class="fas fa-home mr-2"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Face Capture Container -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden card-hover">
            <div class="p-6 md:p-8">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-dict-lightblue rounded-full mb-4">
                        <i class="fas fa-camera text-dict-blue text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Face Recognition</h2>
                    <p class="text-gray-600 mt-2">Position your face within the oval for automatic recognition</p>
                </div>
                
                <!-- Centered Camera View and Controls -->
                <div class="flex flex-col items-center justify-center mb-8">
                    <!-- Camera Preview - Centered -->
                    <div class="relative w-full max-w-[320px] h-[240px] bg-black rounded-lg overflow-hidden shadow-custom-lg mb-6 mx-auto">
                        <video id="video" class="w-full h-full object-cover" autoplay playsinline></video>
                        <canvas id="canvas" class="hidden w-full h-full"></canvas>
                        <div class="face-overlay"></div>
                        <div class="scanning-line"></div>
                        
                        <!-- Recognition Status -->
                        <div id="recognition-status" class="absolute bottom-2 left-0 right-0 text-center text-white text-sm font-medium bg-black bg-opacity-50 py-1 px-2">
                            <i class="fas fa-search fa-spin mr-2"></i>
                            Scanning for faces...
                        </div>
                    </div>
                    
                    <!-- Camera Controls - Centered below camera -->
                    <div class="flex justify-center space-x-4 w-full max-w-[320px]">
                        <button id="capture-btn" class="flex-1 bg-dict-blue hover:bg-blue-700 text-white font-medium rounded-lg px-5 py-3 text-center shadow-lg btn-hover-effect flex items-center justify-center">
                            <i class="fas fa-camera mr-2"></i>
                            Capture Face
                        </button>
                        <button id="retake-btn" class="hidden flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg px-5 py-3 text-center shadow-lg btn-hover-effect flex items-center justify-center">
                            <i class="fas fa-redo mr-2"></i>
                            Retake
                        </button>
                    </div>
                    
                    <button id="recognize-btn" class="hidden w-full max-w-[320px] mt-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg px-5 py-3 text-center shadow-lg btn-hover-effect flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i>
                        Recognize Face
                    </button>
                </div>
                
                <!-- Instructions -->
                <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-dict-blue mr-2"></i>
                        Instructions
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-dict-blue mt-1 mr-2"></i>
                                <span>Ensure your face is clearly visible and well-lit</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-dict-blue mt-1 mr-2"></i>
                                <span>Look directly at the camera</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-dict-blue mt-1 mr-2"></i>
                                <span>Keep a neutral expression</span>
                            </li>
                        </ul>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <div class="flex items-start">
                                <i class="fas fa-lightbulb text-yellow-500 mt-1 mr-2"></i>
                                <p class="text-sm text-gray-700">
                                    If face recognition fails, you can manually select your name from the student list on the timesheet page.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="studentRegistration.php" class="flex-1 bg-dict-blue hover:bg-blue-700 text-white font-medium rounded-lg px-5 py-3 text-center transition duration-300 ease-in-out flex items-center justify-center btn-hover-effect">
                        <i class="fas fa-user-plus mr-2"></i>
                        Register New Student
                    </a>
                    <a href="../index.php" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg px-5 py-3 text-center transition duration-300 ease-in-out flex items-center justify-center btn-hover-effect">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Timesheet
                    </a>
                </div>
                
                <!-- Form for submitting the captured image -->
                <form id="face-form" method="post" class="hidden">
                    <input type="hidden" name="image_data" id="image-data">
                    <input type="hidden" name="recognize_face" value="1">
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Help Section -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden mt-6 p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0 bg-dict-lightblue rounded-full p-3">
                    <i class="fas fa-question-circle text-dict-blue text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Need Help?</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        If you have any questions about the face recognition system or attendance tracking, please contact the DICT office or speak with your internship coordinator.
                    </p>
                    <div class="mt-3">
                        <a href="#" class="text-dict-blue hover:text-blue-700 text-sm font-medium inline-flex items-center">
                            <i class="fas fa-file-alt mr-2"></i>
                            View Internship Guidelines
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 text-xs">
            <p>&copy; <?php echo date('Y'); ?> Department of Information and Communications Technology. All rights reserved.</p>
            <p class="mt-1">Internship Management System v1.0</p>
        </div>
    </div>
    
    <script>
        // Update current time every second (Philippines time)
        function updateTime() {
            const now = new Date();
            const options = { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                hour12: true,
                timeZone: 'Asia/Manila' 
            };
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', options);
        }
        
        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);
        
        // Auto-hide notifications after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('alert-message');
            
            if (notification) {
                // Wait 3 seconds before starting the fade-out animation
                setTimeout(function() {
                    notification.classList.add('fade-out');
                    
                    // Remove the notification from the DOM after the animation completes
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 500); // 500ms matches the animation duration
                }, 3000); // 3000ms = 3 seconds
            }
        });
        
        // Face capture functionality
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const captureBtn = document.getElementById('capture-btn');
            const retakeBtn = document.getElementById('retake-btn');
            const recognizeBtn = document.getElementById('recognize-btn');
            const faceForm = document.getElementById('face-form');
            const imageData = document.getElementById('image-data');
            const recognitionStatus = document.getElementById('recognition-status');
            
            // Skip if we're on the results page
            if (!video) return;
            
            // Get access to the webcam
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                recognitionStatus.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Accessing camera...';
                
                navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 320 },
                        height: { ideal: 240 },
                        facingMode: "user"
                    } 
                })
                .then(function(stream) {
                    video.srcObject = stream;
                    recognitionStatus.innerHTML = '<i class="fas fa-search mr-2"></i> Position your face in the oval';
                })
                .catch(function(error) {
                    console.error("Error accessing the camera: ", error);
                    recognitionStatus.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Camera access denied. Please check permissions.';
                    captureBtn.disabled = true;
                    captureBtn.classList.add('opacity-50', 'cursor-not-allowed');
                });
            } else {
                recognitionStatus.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Your browser doesn\'t support camera access.';
                captureBtn.disabled = true;
                captureBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            
            // Capture image
            captureBtn.addEventListener('click', function() {
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Show the captured image
                video.style.display = 'none';
                canvas.style.display = 'block';
                
                // Update UI
                captureBtn.style.display = 'none';
                retakeBtn.style.display = 'flex';
                recognizeBtn.style.display = 'flex';
                recognitionStatus.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Photo captured';
                
                // Store the image data
                imageData.value = canvas.toDataURL('image/png');
                
                // Add success sound effect (optional)
                const audio = new Audio('data:audio/mp3;base64,SUQzBAAAAAABEVRYWFgAAAAtAAADY29tbWVudABCaWdTb3VuZEJhbmsuY29tIC8gTGFTb25vdGhlcXVlLm9yZwBURU5DAAAAHQAAA1N3aXRjaCBQbHVzIMKpIE5DSCBTb2Z0d2FyZQBUSVQyAAAABgAAAzIyMzUAVFNTRQAAAA8AAANMYXZmNTcuODMuMTAwAAAAAAAAAAAAAAD/80DEAAAAA0gAAAAATEFNRTMuMTAwVVVVVVVVVVVVVUxBTUUzLjEwMFVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVf/zQsRbAAADSAAAAABVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVf/zQMSkAAADSAAAAABVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV');
                audio.play();
            });
            
            // Retake photo
            retakeBtn.addEventListener('click', function() {
                // Show video again
                video.style.display = 'block';
                canvas.style.display = 'none';
                
                // Update UI
                captureBtn.style.display = 'flex';
                retakeBtn.style.display = 'none';
                recognizeBtn.style.display = 'none';
                recognitionStatus.innerHTML = '<i class="fas fa-search mr-2"></i> Position your face in the oval';
            });
            
            // Recognize the captured face
            recognizeBtn.addEventListener('click', function() {
                // Update UI to show processing
                recognizeBtn.disabled = true;
                recognizeBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                recognitionStatus.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Analyzing face...';
                
                // Submit the form with the image data
                faceForm.submit();
            });
        });
    </script>
</body>
</html>