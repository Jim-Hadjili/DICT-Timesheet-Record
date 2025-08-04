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
    <script src="../assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="../assets/css/intern-registration.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg animate-fade-in">

        
        <!-- Alert Messages -->
        <?php include '../components/alert_message.php'; ?>
        
        <!-- Registration Form -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden transition-all-300 border border-gray-100">
            <!-- Form Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-primary-600 to-primary-700 border-b border-primary-800">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-clipboard-list mr-3"></i>
                    Registration Details
                </h2>
                <p class="text-primary-100 text-sm mt-1">Please fill out all required information</p>
            </div>
            
            <form method="post" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Student Name -->
                    <div class="form-section md:col-span-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                            Intern Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative form-input transition-all-300">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-primary-500"></i>
                            </div>
                            <input type="text" id="name" name="name" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-3 transition-all-300" 
                                placeholder="Enter full name" required>
                        </div>
                    </div>
                    
                    <!-- School -->
                    <div class="form-section md:col-span-2">
                        <label for="school" class="block text-sm font-semibold text-gray-700 mb-1">
                            School <span class="text-red-500">*</span>
                        </label>
                        <div class="relative form-input transition-all-300">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-school text-primary-500"></i>
                            </div>
                            <input type="text" id="school" name="school" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-3 transition-all-300" 
                                placeholder="Enter school name" required>
                        </div>
                    </div>
                    
                    <!-- Birthday -->
                    <div class="form-section">
                        <label for="birthday" class="block text-sm font-semibold text-gray-700 mb-1">
                            Birthday <span class="text-red-500">*</span>
                        </label>
                        <div class="relative form-input transition-all-300">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-primary-500"></i>
                            </div>
                            <input type="date" id="birthday" name="birthday" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-3 transition-all-300" 
                                required>
                        </div>
                    </div>
                    
                    <!-- Age -->
                    <div class="form-section">
                        <label for="age" class="block text-sm font-semibold text-gray-700 mb-1">
                            Age <span class="text-red-500">*</span>
                        </label>
                        <div class="relative form-input transition-all-300">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-birthday-cake text-primary-500"></i>
                            </div>
                            <input type="number" id="age" name="age" min="16" max="99" 
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-3 transition-all-300" 
                                placeholder="Auto-calculated" readonly required>

                        </div>
                    </div>
                    
                    <!-- Gender -->
                    <div class="form-section">
                        <label for="gender" class="block text-sm font-semibold text-gray-700 mb-1">
                            Gender <span class="text-red-500">*</span>
                        </label>
                        <div class="relative form-input transition-all-300">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-venus-mars text-primary-500"></i>
                            </div>
                            <select id="gender" name="gender" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-3 transition-all-300 appearance-none" 
                                required>
                                <option value="" disabled selected>Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Required Hours -->
                    <div class="form-section">
                        <label for="required_hours" class="block text-sm font-semibold text-gray-700 mb-1">
                            Required Hours <span class="text-red-500">*</span>
                        </label>
                        <div class="relative form-input transition-all-300">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-hourglass text-primary-500"></i>
                            </div>
                            <input type="number" id="required_hours" name="required_hours" min="1" max="1000" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-md rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-3 transition-all-300" 
                                placeholder="Enter required hours" value="240" required>
                        </div>
                    </div>
                    
                    <!-- Face Registration Option -->
                    <div class="form-section md:col-span-2 bg-primary-50 p-4 rounded-lg border border-primary-200">
                        <div class="flex items-center">
                            <div class="flex items-center h-5">
                                <input id="register-face" name="register_face" type="checkbox" value="1" checked
                                    class="w-5 h-5 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 
                                    focus:ring-offset-0 transition-all duration-200 ease-in-out cursor-pointer">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="register-face" class="font-medium text-gray-700 cursor-pointer">Register face after Intern creation</label>
                                <p class="text-gray-500">Facial recognition will be used for attendance tracking</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Buttons -->
                <div class="pt-2 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" name="register" 
                            class="flex-1 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 
                            focus:ring-4 focus:ring-primary-300 text-white font-medium rounded-lg text-sm px-5 py-3.5 
                            text-center transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                            <i class="fas fa-save mr-2"></i>
                            Register Intern
                        </button>
                        <a href="../index.php" 
                            class="flex-1 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 
                            font-medium rounded-lg text-sm px-5 py-3.5 text-center transition duration-300 ease-in-out">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Timesheet
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/intern-registration.js"></script>
</body>
</html>
