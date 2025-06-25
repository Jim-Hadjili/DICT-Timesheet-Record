<?php
// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include '../connection/conn.php';

// Initialize variables
$intern_id = isset($_GET['intern_id']) ? $_GET['intern_id'] : 0;
$message = "";
$intern_name = "";

// Verify intern exists
if ($intern_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
        $stmt->bindParam(':intern_id', $intern_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $intern_name = $result['Intern_Name'];
        } else {
            $message = "Error: Intern not found.";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
    }
}

// Process image upload
if (isset($_POST['save_face'])) {
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
        
        // Create directory if it doesn't exist
        $upload_dir = 'face_images/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Failed to create directory: " . $upload_dir);
            }
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            throw new Exception("Directory is not writable: " . $upload_dir);
        }
        
        // Generate a unique filename
        $filename = $upload_dir . 'face_' . $intern_id . '_' . time() . '.png';
        
        // Save the image file
        $bytes_written = file_put_contents($filename, $data);
        if ($bytes_written === false) {
            throw new Exception("Failed to write image file: " . $filename);
        }
        
        // Verify the file was created and is readable
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new Exception("Failed to create or read image file: " . $filename);
        }
        
        // Check if GD is available
        if (!extension_loaded('gd')) {
            $message = "Warning: GD library is not available. Face recognition may not work properly.";
            $debug_info[] = "Please install the GD library for better face recognition:";
            $debug_info[] = "- For Ubuntu/Debian: sudo apt-get install php-gd";
            $debug_info[] = "- For CentOS/RHEL: sudo yum install php-gd";
            $debug_info[] = "- For Windows with XAMPP: Enable extension=gd in php.ini";
            $debug_info[] = "After installation, restart your web server.";
        }
        
        // Update the database to mark face as registered and store the image path
        $stmt = $conn->prepare("UPDATE interns SET Face_Registered = 1, Face_Image_Path = :face_path WHERE Intern_id = :intern_id");
        $stmt->bindParam(':face_path', $filename);
        $stmt->bindParam(':intern_id', $intern_id);
        $stmt->execute();
        
        $message = "Face registered successfully!";
        
        // Redirect after 2 seconds
        echo "<script>
            setTimeout(function() {
                window.location.href = '../index.php';
            }, 2000);
        </script>";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        // Log the error
        error_log("Face registration error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Registration - DICT Internship</title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-4">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-camera text-primary-600 mr-2"></i>
                Face Registration
            </h1>
            <p class="text-gray-600 mt-2">Department of Information and Communication Technology</p>
            <p class="text-gray-500 text-sm mt-1">Zamboanga City, Philippines • <?php echo date('F d, Y'); ?> • <span id="current-time"></span></p>
            <?php if($intern_name): ?>
            <p class="text-primary-600 font-medium mt-2">Student: <?php echo htmlspecialchars($intern_name); ?></p>
            <?php endif; ?>
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
        
        <!-- Face Capture Container -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 space-y-6">
                <div class="text-center mb-4">
                    <div class="bg-primary-600 text-white py-3 px-4 rounded-lg mb-3">
                        <h2 class="text-xl font-semibold">Capture Your Face</h2>
                    </div>
                    <p class="text-gray-600 text-sm">Position your face within the oval and ensure good lighting</p>
                </div>
                
                <!-- Camera View -->
                <div class="relative mx-auto w-full max-w-[320px] h-[240px] bg-black rounded-lg overflow-hidden">
                    <video id="video" class="w-full h-full object-cover" autoplay playsinline></video>
                    <canvas id="canvas" class="hidden w-full h-full"></canvas>
                    <div class="face-overlay"></div>
                    
                    <!-- Camera Controls -->
                    <div class="absolute bottom-2 left-0 right-0 flex justify-center">
                        <button id="capture-btn" class="bg-primary-600 hover:bg-primary-700 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Reset Button -->
                <div class="text-center mt-4 mb-4">
                    <button id="retake-btn" class="hidden mx-auto bg-gray-600 hover:bg-gray-700 text-white rounded-lg px-4 py-2 inline-flex items-center justify-center">
                        <i class="fas fa-redo mr-2"></i>
                        Reset Camera
                    </button>
                </div>
                
                <!-- Instructions -->
                <div class="bg-gray-50 p-4 rounded-lg text-sm">
                    <h3 class="font-semibold text-gray-700 mb-2"><i class="fas fa-info-circle text-primary-500 mr-1"></i> Instructions:</h3>
                    <ul class="list-disc pl-5 space-y-1 text-gray-600">
                        <li>Ensure your face is clearly visible and well-lit</li>
                        <li>Remove glasses, hats, or other items that cover your face</li>
                        <li>Look directly at the camera</li>
                        <li>Keep a neutral expression</li>
                        <li>Avoid moving during capture</li>
                        <li>Once saved, you will not be able to change this.<br>Please capture your face wisely.</li>
                    </ul>
                </div>
                
                <!-- Form for submitting the captured image -->
                <form id="face-form" method="post" class="hidden">
                    <input type="hidden" name="image_data" id="image-data">
                    <input type="hidden" name="save_face" value="1">
                </form>
                
                <!-- Form Buttons -->
                <div class="flex space-x-3 pt-4">
                    <button id="save-btn" class="hidden flex-1 bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>
                        Save Face Image
                    </button>
                    <a href="studentRegistration.php" class="flex-1 bg-gray-600 hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Registration
                    </a>
                </div>
            </div>
        </div>
        
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
            const saveBtn = document.getElementById('save-btn');
            const faceForm = document.getElementById('face-form');
            const imageData = document.getElementById('image-data');
            
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
                    alert("Unable to access the camera. Please ensure camera permissions are granted and try again.");
                });
            } else {
                alert("Sorry, your browser doesn't support camera access.");
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
                saveBtn.style.display = 'block';
                
                // Store the image data
                imageData.value = canvas.toDataURL('image/png');
            });
            
            // Retake photo
            retakeBtn.addEventListener('click', function() {
                // Show video again
                video.style.display = 'block';
                canvas.style.display = 'none';
                
                // Update UI
                captureBtn.style.display = 'flex';
                retakeBtn.style.display = 'none';
                saveBtn.style.display = 'none';
            });
            
            // Save the captured image
            saveBtn.addEventListener('click', function() {
                // Submit the form with the image data
                faceForm.submit();
            });
        });
    </script>
</body>
</html>
