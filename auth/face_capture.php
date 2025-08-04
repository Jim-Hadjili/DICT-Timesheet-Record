<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

include '../connection/conn.php';

// Initialize variables
$message = "";
$intern_id = isset($_GET['intern_id']) ? $_GET['intern_id'] : 0;
$intern_name = "";

// Verify intern exists
if ($intern_id) {
    try {
        $stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_ID = :intern_id");
        $stmt->bindParam(':intern_id', $intern_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $intern_name = $result['Intern_Name'];
        } else {
            // Redirect if intern not found
            header("Location: internRegistration.php");
            exit();
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
} else {
    // Redirect if no intern ID provided
    header("Location: internRegistration.php");
    exit();
}

// Process face image submission
if (isset($_POST['save_face'])) {
    try {
        $image_data = $_POST['image_data'];
        
        // Remove the data URL prefix
        $image_parts = explode(",", $image_data);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : "";
        
        // Generate a unique filename for the face image
        $filename = 'face_' . $intern_id . '_' . time() . '.png';
        $filepath = '../uploads/faces/' . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists('../uploads/faces/')) {
            mkdir('../uploads/faces/', 0777, true);
        }
        
        // Save the image to the filesystem
        $success = file_put_contents($filepath, base64_decode($image_base64));
        
        if ($success) {
            // Update the intern record with the face image path
            $stmt = $conn->prepare("UPDATE interns SET Face_Image_Path = :face_image, Face_Registered = 1 WHERE Intern_ID = :intern_id");
            $stmt->bindParam(':face_image', $filename);
            $stmt->bindParam(':intern_id', $intern_id);
            $stmt->execute();
            
            $message = "Face registered successfully!";
            
            // Redirect to success page after a short delay (for showing message)
            header("Refresh: 2; URL=../index.php");
        } else {
            $message = "Error saving face image.";
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
    <title>Face Registration - DICT Internship</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/face-registration.css">
    <!-- Face API Library -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl animate-fade-in">
        
        <!-- Alert Messages -->
        <?php if($message): ?>
        <div id="alert-message" class="mb-6 rounded-xl p-4 shadow-md transition-all duration-500 ease-in-out animate-slide-up
            <?php echo strpos($message, 'successfully') !== false 
                ? 'bg-green-100 text-green-800 border border-green-200' 
                : 'bg-red-100 text-red-800 border border-red-200'; ?>">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="<?php echo strpos($message, 'successfully') !== false 
                        ? 'fas fa-check-circle text-green-500 text-xl' 
                        : 'fas fa-exclamation-circle text-red-500 text-xl'; ?>"></i>
                </div>
                <div class="ml-3">
                    <p class="font-medium"><?php echo $message; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Face Capture Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden transition-all-300 border border-gray-100">
            <!-- Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-primary-600 to-primary-700 border-b border-primary-800">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-camera mr-3"></i>
                    Face Registration
                </h2>
                <p class="text-primary-100 text-sm mt-1">
                    Set up facial recognition for <strong><?php echo htmlspecialchars($intern_name); ?></strong>
                </p>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Instructions -->
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-0.5">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Instructions</h3>
                            <div class="text-sm text-blue-700 mt-1">
                                <ul class="list-disc space-y-1 pl-5">
                                    <li>Position your face in the center of the camera frame</li>
                                    <li>Make sure your face is well-lit and clearly visible</li>
                                    <li>Remove glasses, masks, or other face coverings</li>
                                    <li>Look directly at the camera with a neutral expression</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Camera View -->
                <div class="relative mx-auto w-full max-w-md aspect-[4/3] bg-gray-900 rounded-lg overflow-hidden shadow-lg border-2 border-primary-500">
                    <!-- Loading indicator -->
                    <div id="loading-indicator" class="absolute inset-0 bg-black bg-opacity-75 flex items-center justify-center text-white z-20">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                            <p>Loading face detection...</p>
                        </div>
                    </div>
                    
                    <!-- Face detection overlay -->
                    <div id="face-detection-overlay" class="absolute inset-0 z-10 pointer-events-none"></div>
                    
                    <!-- Video element -->
                    <video id="video-element" class="h-full w-full object-cover" autoplay muted></video>
                    
                    <!-- Capture indicator -->
                    <div id="capture-indicator" class="hidden absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-30">
                        <div class="text-center">
                            <i class="fas fa-check-circle text-green-500 text-5xl mb-3"></i>
                            <p class="text-xl font-bold text-green-700">Face Captured!</p>
                        </div>
                    </div>
                    
                    <!-- Canvas for processing -->
                    <canvas id="canvas-element" class="hidden"></canvas>
                </div>
                
                <!-- Quality Indicators -->
                <div class="grid grid-cols-3 gap-4 mt-2">
                    <div class="face-quality-indicator" id="lighting-quality">
                        <div class="flex items-center">
                            <i class="fas fa-lightbulb text-gray-400 mr-2"></i>
                            <span>Lighting</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                            <div class="quality-bar bg-gray-400 h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="face-quality-indicator" id="position-quality">
                        <div class="flex items-center">
                            <i class="fas fa-bullseye text-gray-400 mr-2"></i>
                            <span>Position</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                            <div class="quality-bar bg-gray-400 h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="face-quality-indicator" id="clarity-quality">
                        <div class="flex items-center">
                            <i class="fas fa-eye text-gray-400 mr-2"></i>
                            <span>Clarity</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                            <div class="quality-bar bg-gray-400 h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview -->
                <div id="preview-container" class="hidden mt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Preview</h3>
                    <div class="flex items-center space-x-4">
                        <img id="preview-image" class="w-32 h-32 rounded-lg border-2 border-primary-500 object-cover" />
                        <div>
                            <p class="text-sm text-gray-600">This is the face image that will be used for recognition.</p>
                            <button id="retake-btn" type="button" class="mt-2 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-redo-alt mr-2"></i>
                                Retake
                            </button>
                        </div>
                    </div>
                </div>
                
                <form id="face-form" method="post" class="mt-6">
                    <input type="hidden" name="image_data" id="image-data">
                    
                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                        <!-- Capture Button -->
                        <button id="capture-btn" type="button" 
                            class="flex-1 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 
                            focus:ring-4 focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-3.5 
                            text-center transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                            <i class="fas fa-camera mr-2"></i>
                            Capture Face
                        </button>
                        
                        <!-- Save Button (Initially Hidden) -->
                        <button id="save-btn" type="submit" name="save_face" 
                            class="hidden flex-1 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 
                            focus:ring-4 focus:ring-green-300 text-white font-medium rounded-lg text-sm px-5 py-3.5 
                            text-center transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                            <i class="fas fa-save mr-2"></i>
                            Save & Continue
                        </button>
                        
                        <!-- Cancel Button -->
                        <a href="../index.php" 
                            class="flex-1 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 
                            font-medium rounded-lg text-sm px-5 py-3.5 text-center transition duration-300 ease-in-out">
                            <i class="fas fa-times mr-2"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/face-registration.js"></script>
</body>
</html>