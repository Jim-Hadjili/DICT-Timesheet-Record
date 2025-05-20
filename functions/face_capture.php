<?php
// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include '../connection/conn.php';

// Handle the intern_id parameter
$intern_id = isset($_GET['intern_id']) ? intval($_GET['intern_id']) : 0;
$message = "";
$intern_name = "";
$is_new_registration = isset($_GET['new_registration']) && $_GET['new_registration'] == 1;

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
        
        $message = "Face registered successfully! Registration complete.";
        
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
                            <h1 class="text-2xl md:text-3xl font-bold">Face Registration</h1>
                            <p class="text-blue-100">Department of Information and Communications Technology</p>
                        </div>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-medium">Zamboanga City, Philippines</p>
                        <p><?php echo date('F d, Y'); ?> • <span id="current-time"></span></p>
                    </div>
                </div>
            </div>
            <?php if($intern_name): ?>
            <div class="bg-dict-blue bg-opacity-10 p-4 border-t border-dict-blue border-opacity-20">
                <div class="flex items-center">
                    <i class="fas fa-user-circle text-dict-blue text-xl mr-2"></i>
                    <p class="text-dict-blue font-medium">Intern: <?php echo htmlspecialchars($intern_name); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Progress Steps -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden mb-6 p-6">
            <div class="progress-steps">
                <div class="step active">
                    1
                    <span class="step-label">Registration</span>
                </div>
                <div class="step active">
                    2
                    <span class="step-label">Face Capture</span>
                </div>
                <div class="step">
                    3
                    <span class="step-label">Complete</span>
                </div>
            </div>
            
            <div class="text-center text-sm text-gray-600">
                <p>Position your face in the camera frame to capture your face</p>
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
        
        <!-- Face Capture Container -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden card-hover">
            <div class="p-6 md:p-8">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-dict-lightblue rounded-full mb-4">
                        <i class="fas fa-camera text-dict-blue text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Face Registration</h2>
                    <p class="text-gray-600 mt-2">Position your face within the oval to capture your face</p>
                </div>
                
                <!-- Centered Camera View and Controls -->
                <div class="flex flex-col items-center justify-center mb-8">
                    <!-- Camera Preview - Centered -->
                    <div class="relative w-full max-w-[320px] h-[240px] bg-black rounded-lg overflow-hidden shadow-custom-lg mb-6 mx-auto">
                        <video id="video" class="w-full h-full object-cover" autoplay playsinline></video>
                        <canvas id="canvas" class="hidden w-full h-full"></canvas>
                        <div class="face-overlay"></div>
                        <div class="scanning-line"></div>
                        
                        <!-- Status indicator -->
                        <div id="camera-status" class="absolute bottom-2 left-0 right-0 text-center text-white text-sm font-medium bg-black bg-opacity-50 py-1 px-2">
                            <span id="status-text">Camera ready</span>
                        </div>
                    </div>
                    
                    <!-- Camera Controls - Centered below camera -->
                    <div class="flex justify-center space-x-4 w-full max-w-[320px]">
                        <button id="capture-btn" class="flex-1 bg-dict-blue hover:bg-blue-700 text-white font-medium rounded-lg px-5 py-3 text-center shadow-lg btn-hover-effect flex items-center justify-center">
                            <i class="fas fa-camera mr-2"></i>
                            Capture Photo
                        </button>
                        
                    </div>

                    <button id="retake-btn" class="hidden w-full max-w-[320px] mt-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg px-5 py-3 text-center shadow-lg btn-hover-effect flex items-center justify-center">
                            <i class="fas fa-redo mr-2"></i>
                            Retake
                        </button>
                    
                    
                    <button id="save-btn" class="hidden w-full max-w-[320px] mt-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg px-5 py-3 text-center shadow-lg btn-hover-effect flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Use Image
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
                                <span>Remove glasses, hats, or other items that cover your face</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-dict-blue mt-1 mr-2"></i>
                                <span>Look directly at the camera</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-dict-blue mt-1 mr-2"></i>
                                <span>Keep a neutral expression</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-dict-blue mt-1 mr-2"></i>
                                <span>Avoid moving during capture</span>
                            </li>
                        </ul>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <div class="flex items-start">
                                <i class="fas fa-lightbulb text-yellow-500 mt-1 mr-2"></i>
                                <p class="text-sm text-gray-700">
                                    Your face image will be used for attendance tracking. The system will recognize you when you check in and out, eliminating the need for manual time entry.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="studentRegistration.php<?php echo $is_new_registration ? '?cancel_registration=' . $intern_id : ''; ?>" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg px-5 py-3 text-center transition duration-300 ease-in-out flex items-center justify-center btn-hover-effect">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Registration
                    </a>
                </div>
                
                <!-- Form for submitting the captured image -->
                <form id="face-form" method="post" class="hidden">
                    <input type="hidden" name="image_data" id="image-data">
                    <input type="hidden" name="save_face" value="1">
                </form>
            </div>
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
                setTimeout(function() {
                    notification.classList.add('fade-out');
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 500);
                }, 3000);
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
            const statusText = document.getElementById('status-text');
            
            // Get access to the webcam
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                statusText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Accessing camera...';
                
                navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 320 },
                        height: { ideal: 240 },
                        facingMode: "user"
                    } 
                })
                .then(function(stream) {
                    video.srcObject = stream;
                    statusText.innerHTML = 'Position your face in the oval';
                })
                .catch(function(error) {
                    console.error("Error accessing the camera: ", error);
                    statusText.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Camera access denied';
                    captureBtn.disabled = true;
                    captureBtn.classList.add('opacity-50', 'cursor-not-allowed');
                });
            } else {
                statusText.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Camera not supported';
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
                saveBtn.style.display = 'flex';
                statusText.innerHTML = 'Photo captured';
                
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
                saveBtn.style.display = 'none';
                statusText.innerHTML = 'Position your face in the oval';
            });
            
            // Save the captured image
            saveBtn.addEventListener('click', function() {
                // Update UI to show processing
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                statusText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving image...';
                
                // Submit the form with the image data
                faceForm.submit();
            });
        });
    </script>
</body>
</html>