<?php 
// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include '../connection/conn.php';

// Initialize message variable
$message = "";
$messageType = ""; // success or error
$formData = [
    'name' => '',
    'school' => '',
    'birthday' => '',
    'age' => '',
    'gender' => '',
    'required_hours' => '240'
];
$cancel_registration = isset($_GET['cancel_registration']) ? intval($_GET['cancel_registration']) : 0;


// If cancellation is requested, delete the Intern record
if ($cancel_registration > 0) {
    try {
        // Delete the Intern record
        $deleteStmt = $conn->prepare("DELETE FROM interns WHERE Intern_id = :intern_id AND Face_Registered = 0");
        $deleteStmt->bindParam(':intern_id', $cancel_registration);
        $deleteStmt->execute();
        
        if ($deleteStmt->rowCount() > 0) {
            $message = "Registration cancelled. Intern record has been removed.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}
// Process form submission
if (isset($_POST['register'])) {
    // Get form data
    $formData = [
        'name' => $_POST['name'],
        'school' => $_POST['school'],
        'birthday' => $_POST['birthday'],
        'age' => $_POST['age'],
        'gender' => $_POST['gender'],
        'required_hours' => $_POST['required_hours']
    ];
    
    try {
        // Check if a Intern with the same name already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM interns WHERE Intern_Name = :name");
        $checkStmt->bindParam(':name', $formData['name']);
        $checkStmt->execute();
        $nameExists = $checkStmt->fetchColumn();
        
        if ($nameExists > 0) {
            // Intern with this name already exists
            $message = "Error: A Intern with the name '" . htmlspecialchars($formData['name']) . "' is already registered.";
            $messageType = "error";
        } else {
            // Insert into interns table
            $stmt = $conn->prepare("INSERT INTO interns (Intern_Name, Intern_School, Intern_BirthDay, Intern_Age, Intern_Gender, Required_Hours_Rendered, Face_Registered) 
                                   VALUES (:name, :school, :birthday, :age, :gender, :required_hours, 0)");
            $stmt->bindParam(':name', $formData['name']);
            $stmt->bindParam(':school', $formData['school']);
            $stmt->bindParam(':birthday', $formData['birthday']);
            $stmt->bindParam(':age', $formData['age']);
            $stmt->bindParam(':gender', $formData['gender']);
            $stmt->bindParam(':required_hours', $formData['required_hours']);
            $stmt->execute();
            
            // Get the newly inserted intern ID
            $intern_id = $conn->lastInsertId();
            
            $message = "Intern registered successfully!";
            $messageType = "success";
            
            // Clear form data after successful submission
            $formData = [
                'name' => '',
                'school' => '',
                'birthday' => '',
                'age' => '',
                'gender' => '',
                'required_hours' => '240'
            ];
            
            // Redirect to face registration if requested
            if (isset($_POST['register_face']) && $_POST['register_face'] == 1) {
                header("Location: face_capture.php?intern_id=" . $intern_id . "&new_registration=1");
                exit();
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Registration - DICT Internship</title>
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

        
        /* Input focus effect */
        .input-focus-effect {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .input-focus-effect:focus {
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.2);
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
        
        /* Checkbox custom style */
        .custom-checkbox {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .custom-checkbox input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #0056b3;
            border-radius: 4px;
            margin-right: 10px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .custom-checkbox input[type="checkbox"]:checked {
            background-color: #0056b3;
        }
        
        .custom-checkbox input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 14px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
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
        
        /* Error shake animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.6s cubic-bezier(.36,.07,.19,.97) both;
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
                            <h1 class="text-2xl md:text-3xl font-bold">Intern Registration</h1>
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
        
        <!-- Alert Messages -->
        <?php if($message != ""): ?>
        <div id="alert-message" class="mb-6 rounded-lg p-4 <?php echo $messageType === 'success' ? 'bg-yellow-100 text-yellow-800 border-l-4 border-yellow-500' : 'bg-red-100 text-red-800 border-l-4 border-red-500 shake'; ?> transition-all duration-500 ease-in-out shadow-custom">
            <div class="flex items-center">
                <i class="<?php echo $messageType === 'success' ? 'fas fa-check-circle text-yellow-500' : 'fas fa-exclamation-circle text-red-500'; ?> mr-2 text-xl"></i>
                <p class="font-medium"><?php echo $message; ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Progress Steps -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden mb-6 p-6">
            <div class="progress-steps">
                <div class="step active">
                    1
                    <span class="step-label">Registration</span>
                </div>
                <div class="step">
                    2
                    <span class="step-label">Face Capture</span>
                </div>
                <div class="step">
                    3
                    <span class="step-label">Complete</span>
                </div>
            </div>
            
            <div class="text-center text-sm text-gray-600">
                <p>Complete the registration form below to create a new Intern profile</p>
            </div>
        </div>
        
        <!-- Registration Form -->
        <div class="bg-white rounded-xl shadow-custom overflow-hidden card-hover">
            <div class="p-6 md:p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-user-plus text-dict-blue mr-2"></i>
                    Intern Information
                </h2>
                
                <form method="post" id="registration-form" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Intern Name -->
                        <div class="form-group">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Intern Name <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-user form-input-icon"></i>
                                <input type="text" id="name" name="name" class="pl-10 w-full rounded-lg border-gray-300 bg-gray-50 p-3 text-sm shadow-sm input-focus-effect" placeholder="Enter full name" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                            </div>
                            <p id="name-error" class="text-red-500 text-xs mt-1 hidden">Please enter a valid name</p>
                        </div>
                        
                        <!-- School -->
                        <div class="form-group">
                            <label for="school" class="block text-sm font-medium text-gray-700 mb-1">School <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-school form-input-icon"></i>
                                <input type="text" id="school" name="school" class="pl-10 w-full rounded-lg border-gray-300 bg-gray-50 p-3 text-sm shadow-sm input-focus-effect" placeholder="Enter school name" value="<?php echo htmlspecialchars($formData['school']); ?>" required>
                            </div>
                        </div>
                        
                        <!-- Birthday -->
                        <div class="form-group">
                            <label for="birthday" class="block text-sm font-medium text-gray-700 mb-1">Birthday <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-calendar-alt form-input-icon"></i>
                                <input type="date" id="birthday" name="birthday" class="pl-10 w-full rounded-lg border-gray-300 bg-gray-50 p-3 text-sm shadow-sm input-focus-effect" value="<?php echo htmlspecialchars($formData['birthday']); ?>" required>
                            </div>
                        </div>
                        
                        <!-- Age -->
                        <div class="form-group">
                            <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-birthday-cake form-input-icon"></i>
                                <input type="number" id="age" name="age" min="16" max="99" class="pl-10 w-full rounded-lg border-gray-300 bg-gray-50 p-3 text-sm shadow-sm input-focus-effect" placeholder="Age will be calculated" value="<?php echo htmlspecialchars($formData['age']); ?>" readonly required>
                            </div>
                        </div>
                        
                        <!-- Gender -->
                        <div class="form-group">
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-venus-mars form-input-icon"></i>
                                <select id="gender" name="gender" class="pl-10 w-full rounded-lg border-gray-300 bg-gray-50 p-3 text-sm shadow-sm input-focus-effect" required>
                                    <option value="" disabled <?php echo $formData['gender'] === '' ? 'selected' : ''; ?>>Select gender</option>
                                    <option value="Male" <?php echo $formData['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $formData['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $formData['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Required Hours -->
                        <div class="form-group">
                            <label for="required_hours" class="block text-sm font-medium text-gray-700 mb-1">Required Hours <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fas fa-hourglass form-input-icon"></i>
                                <input type="number" id="required_hours" name="required_hours" min="1" max="1000" class="pl-10 w-full rounded-lg border-gray-300 bg-gray-50 p-3 text-sm shadow-sm input-focus-effect" placeholder="Enter required hours" value="<?php echo htmlspecialchars($formData['required_hours']); ?>" required>
                                <div class="text-xs text-gray-500 mt-1 ml-1">Default: 240 hours (can be adjusted based on program requirements)</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Face Registration Option -->
<div class="bg-dict-lightblue p-5 rounded-lg border border-dict-blue border-opacity-20 mt-6">
    <div class="flex items-start">
        <div class="flex h-5 items-center">
            <input id="register-face" name="register_face" type="checkbox" value="1" class="h-5 w-5 rounded border-gray-300 text-dict-blue focus:ring-dict-blue" checked>
        </div>
        <div class="ml-3">
            <label for="register-face" class="font-medium text-gray-700">Register face after Intern creation</label>
            <p class="text-sm text-gray-500">Face registration enables automated attendance tracking using facial recognition</p>
        </div>
    </div>
</div>
                    
                    <!-- Form Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <button type="submit" name="register" id="register-btn" class="flex-1 bg-dict-blue hover:bg-blue-700 text-white font-medium rounded-lg px-5 py-3.5 text-center shadow-lg btn-hover-effect flex items-center justify-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Register Intern
                        </button>
                        <a href="../index.php" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg px-5 py-3.5 text-center shadow-lg btn-hover-effect flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Timesheet
                        </a>
                    </div>
                </form>
            </div>
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
        
        // Auto-hide notifications after 5 seconds (increased from 3 to give more time to read error messages)
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.getElementById('alert-message');
            
            if (notification) {
                // Wait 5 seconds before starting the fade-out animation
                setTimeout(function() {
                    notification.classList.add('fade-out');
                    
                    // Remove the notification from the DOM after the animation completes
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 500); // 500ms matches the animation duration
                }, 5000); // 5000ms = 5 seconds
            }
        });
        
        // Enhanced form validation
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            let isValid = true;
            const nameField = document.getElementById('name');
            const nameError = document.getElementById('name-error');
            const schoolField = document.getElementById('school');
            const birthdayField = document.getElementById('birthday');
            const ageField = document.getElementById('age');
            const genderField = document.getElementById('gender');
            
            // Name validation - check for empty and minimum length
            if (!nameField.value.trim() || nameField.value.trim().length < 3) {
                e.preventDefault();
                nameField.classList.add('border-red-500');
                nameError.classList.remove('hidden');
                nameField.focus();
                isValid = false;
            } else {
                nameField.classList.remove('border-red-500');
                nameError.classList.add('hidden');
            }
            
            // School validation
            if (!schoolField.value.trim()) {
                e.preventDefault();
                schoolField.classList.add('border-red-500');
                schoolField.focus();
                isValid = false;
            } else {
                schoolField.classList.remove('border-red-500');
            }
            
            // Birthday validation
            if (!birthdayField.value) {
                e.preventDefault();
                birthdayField.classList.add('border-red-500');
                birthdayField.focus();
                isValid = false;
            } else {
                birthdayField.classList.remove('border-red-500');
            }
            
            // Age validation
            if (!ageField.value || ageField.value < 16) {
                e.preventDefault();
                ageField.classList.add('border-red-500');
                ageField.focus();
                isValid = false;
            } else {
                ageField.classList.remove('border-red-500');
            }
            
            // Gender validation
            if (!genderField.value) {
                e.preventDefault();
                genderField.classList.add('border-red-500');
                genderField.focus();
                isValid = false;
            } else {
                genderField.classList.remove('border-red-500');
            }
            
            if (!isValid) {
                // Add shake animation to the form for invalid submissions
                const form = document.querySelector('.card-hover');
                form.classList.add('shake');
                setTimeout(() => {
                    form.classList.remove('shake');
                }, 600);
            }
            
            return isValid;
        });
        
        // Remove validation error styling on input
        const formInputs = document.querySelectorAll('input, select');
        formInputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('border-red-500');
                const errorElement = document.getElementById(this.id + '-error');
                if (errorElement) {
                    errorElement.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
