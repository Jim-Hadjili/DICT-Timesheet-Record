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
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                    }
                }
            }
        }
    </script>
    <style>
        /* Fade out animation for notifications */
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        .fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }
        
        /* Face overlay for positioning */
        .face-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 100 100"><ellipse cx="50" cy="50" rx="35" ry="45" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-dasharray="5,5"/></svg>');
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-camera text-primary-600 mr-2"></i>
                Face Recognition
            </h1>
            <p class="text-gray-600 mt-2">Department of Information and Communication Technology</p>
            <p class="text-gray-500 text-sm mt-1">Zamboanga City, Philippines • <?php echo date('F d, Y'); ?> • <span id="current-time"></span></p>
        </div>
        
        <!-- Alert Messages -->
        <?php if($message != ""): ?>
        <div id="alert-message" class="mb-6 rounded-lg p-4 <?php echo strpos($message, 'successfully') !== false ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'bg-red-100 text-red-800 border-l-4 border-red-500'; ?> transition-all duration-500 ease-in-out">
            <div class="flex items-center">
                <i class="<?php echo strpos($message, 'successfully') !== false ? 'fas fa-check-circle text-green-500' : 'fas fa-exclamation-circle text-red-500'; ?> mr-2"></i>
                <p><?php echo $message; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($matched_intern): ?>
        <!-- Recognition Result -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
            <div class="p-6">
                <div class="text-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Student Identified</h2>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-center">
                        <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-3xl">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Name</p>
                            <p class="font-medium"><?php echo htmlspecialchars($matched_intern['Intern_Name']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">School</p>
                            <p class="font-medium"><?php echo htmlspecialchars($matched_intern['Intern_School']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Age</p>
                            <p class="font-medium"><?php echo htmlspecialchars($matched_intern['Intern_Age']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Gender</p>
                            <p class="font-medium"><?php echo htmlspecialchars($matched_intern['Intern_Gender']); ?></p>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <a href="../index.php?intern_id=<?php echo $matched_intern['Intern_id']; ?>&face_recognized=1" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out inline-block">
                            <i class="fas fa-clock mr-2"></i>
                            Proceed to Timesheet
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Face Capture Container -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 space-y-6">
                <div class="text-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Scan Your Face</h2>
                    <p class="text-gray-600 text-sm mt-1">Position your face within the oval and ensure good lighting</p>
                </div>
                
                <!-- Camera View -->
                <div class="relative mx-auto w-full max-w-[320px] h-[240px] bg-black rounded-lg overflow-hidden">
                    <video id="video" class="w-full h-full object-cover" autoplay playsinline></video>
                    <canvas id="canvas" class="hidden w-full h-full"></canvas>
                    <div class="face-overlay"></div>
                    <div class="scanning-line"></div>
                    
                    <!-- Recognition Status -->
                    <div id="recognition-status" class="absolute bottom-2 left-0 right-0 text-center text-white text-sm font-medium bg-black bg-opacity-50 py-1">
                        Scanning for faces...
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="bg-gray-50 p-4 rounded-lg text-sm">
                    <h3 class="font-semibold text-gray-700 mb-2"><i class="fas fa-info-circle text-primary-500 mr-1"></i> Instructions:</h3>
                    <ul class="list-disc pl-5 space-y-1 text-gray-600">
                        <li>Ensure your face is clearly visible and well-lit.</li>
                        <li>Look directly at the camera.</li>
                        <li>Keep a neutral expression.</li>
                    </ul>
                </div>
                
                <!-- Form for submitting the captured image -->
                <form id="face-form" method="post" class="hidden">
                    <input type="hidden" name="image_data" id="image-data">
                    <input type="hidden" name="recognize_face" value="1">
                </form>
                
                <!-- Form Buttons -->
                <div class="flex space-x-3 pt-4">
                    <button id="capture-btn" class="flex-1 bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out transform hover:scale-105">
                        <i class="fas fa-camera mr-2"></i>
                        Capture Face
                    </button>
                    <button id="recognize-btn" class="hidden flex-1 bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out transform hover:scale-105">
                        <i class="fas fa-search mr-2"></i>
                        Recognize Face
                    </button>
                    <button id="retake-btn" class="hidden flex-1 bg-gray-600 hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out">
                        <i class="fas fa-redo mr-2"></i>
                        Retake
                    </button>
                    <a href="../index.php" class="flex-1 bg-gray-600 hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 text-xs">
            <p>&copy; <?php echo date('Y'); ?> Department of Information and Communication Technology. All rights reserved.</p>
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
                navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 320 },
                        height: { ideal: 240 },
                        facingMode: "user"
                    } 
                })
                .then(function(stream) {
                    video.srcObject = stream;
                })
                .catch(function(error) {
                    console.error("Error accessing the camera: ", error);
                    recognitionStatus.textContent = "Camera access denied. Please check permissions.";
                    recognitionStatus.classList.add("bg-red-500");
                });
            } else {
                recognitionStatus.textContent = "Your browser doesn't support camera access.";
                recognitionStatus.classList.add("bg-red-500");
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
                retakeBtn.style.display = 'block';
                recognizeBtn.style.display = 'block';
                
                // Store the image data
                imageData.value = canvas.toDataURL('image/png');
            });
            
            // Retake photo
            retakeBtn.addEventListener('click', function() {
                // Show video again
                video.style.display = 'block';
                canvas.style.display = 'none';
                
                // Update UI
                captureBtn.style.display = 'block';
                retakeBtn.style.display = 'none';
                recognizeBtn.style.display = 'none';
            });
            
            // Recognize the captured face
            recognizeBtn.addEventListener('click', function() {
                // Update UI to show processing
                recognizeBtn.disabled = true;
                recognizeBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                
                // Submit the form with the image data
                faceForm.submit();
            });
        });
    </script>
</body>
</html>
