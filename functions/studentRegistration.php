<?php 
// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include '../connection/conn.php';

// Initialize message variable
$message = "";

// Process form submission
if (isset($_POST['register'])) {
    // Get form data
    $name = $_POST['name'];
    $school = $_POST['school'];
    $birthday = $_POST['birthday'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $required_hours = $_POST['required_hours'];
    
    try {
        // Insert into interns table
        $stmt = $conn->prepare("INSERT INTO interns (Intern_Name, Intern_School, Intern_BirthDay, Intern_Age, Intern_Gender, Required_Hours_Rendered, Face_Registered) 
                               VALUES (:name, :school, :birthday, :age, :gender, :required_hours, 0)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':school', $school);
        $stmt->bindParam(':birthday', $birthday);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':required_hours', $required_hours);
        $stmt->execute();
        
        // Get the newly inserted intern ID
        $intern_id = $conn->lastInsertId();
        
        $message = "Student registered successfully!";
        
        // Clear form data after successful submission
        $name = $school = $birthday = $age = $gender = "";
        
        // Redirect to face registration if requested
        if (isset($_POST['register_face']) && $_POST['register_face'] == 1) {
            header("Location: face_capture.php?intern_id=" . $intern_id);
            exit();
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - DICT Internship</title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-user-plus text-primary-600 mr-2"></i>
                Student Registration
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
        
        <!-- Registration Form -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <form method="post" class="p-6 space-y-6">
                <div class="space-y-4">
                    <!-- Student Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Student Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="name" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5" placeholder="Enter full name" required>
                        </div>
                    </div>
                    
                    <!-- School -->
                    <div>
                        <label for="school" class="block text-sm font-medium text-gray-700 mb-1">School</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-school text-gray-400"></i>
                            </div>
                            <input type="text" id="school" name="school" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5" placeholder="Enter school name" required>
                        </div>
                    </div>
                    
                    <!-- Birthday -->
                    <div>
                        <label for="birthday" class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-gray-400"></i>
                            </div>
                            <input type="date" id="birthday" name="birthday" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5" required>
                        </div>
                    </div>
                    
                    <!-- Age -->
                    <div>
                        <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-birthday-cake text-gray-400"></i>
                            </div>
                            <input type="number" id="age" name="age" min="16" max="99" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5" placeholder="Age will be calculated" readonly required>
                        </div>
                    </div>
                    
                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-venus-mars text-gray-400"></i>
                            </div>
                            <select id="gender" name="gender" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5" required>
                                <option value="" disabled selected>Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Required Hours -->
                    <div>
                        <label for="required_hours" class="block text-sm font-medium text-gray-700 mb-1">Required Hours to be Rendered</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-hourglass text-gray-400"></i>
                            </div>
                            <input type="number" id="required_hours" name="required_hours" min="1" max="1000" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5" placeholder="Enter required hours" value="240" required>
                            <div class="text-xs text-gray-500 mt-1 ml-1">Default: 240 hours (can be adjusted based on program requirements)</div>
                        </div>
                    </div>
                    
                    <!-- Face Registration Option -->
                    <div class="flex items-center mt-4">
                        <input id="register-face" name="register_face" type="checkbox" value="1" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500">
                        <label for="register-face" class="ml-2 text-sm font-medium text-gray-700">Register face after student creation</label>
                    </div>
                </div>
                
                <!-- Form Buttons -->
                <div class="flex space-x-3 pt-4">
                    <button type="submit" name="register" class="flex-1 bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>
                        Register Student
                    </button>
                    <a href="../index.php" class="flex-1 bg-gray-600 hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center transition duration-300 ease-in-out">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Timesheet
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 text-xs">
            <p>&copy; <?php echo date('Y'); ?> Department of Information and Communication Technology. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        // Auto-calculate age based on birthday
        document.getElementById('birthday').addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            document.getElementById('age').value = age;
        });
        
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
    </script>
</body>
</html>
