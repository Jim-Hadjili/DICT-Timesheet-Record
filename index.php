<?php

include './functions/main.php';

// Check if the created_at column exists in the timesheet table
try {
    $checkColumnStmt = $conn->prepare("SHOW COLUMNS FROM timesheet LIKE 'created_at'");
    $checkColumnStmt->execute();

    if ($checkColumnStmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $alterTableStmt = $conn->prepare("ALTER TABLE timesheet ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        $alterTableStmt->execute();

        // Update existing records to have today's date
        $updateStmt = $conn->prepare("UPDATE timesheet SET created_at = NOW() WHERE created_at IS NULL");
        $updateStmt->execute();
    }
} catch (PDOException $e) {
    // Log the error but don't stop execution
    error_log("Error checking/adding created_at column: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DICT Internship Timesheet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
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
        
        /* Pulse animation for recognition result */
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }

        /* Fade-out animation for notifications */
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }

        /* Card hover effect */
        .hover-card {
            transition: all 0.3s ease;
        }
        
        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #0ea5e9;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #0284c7;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Face Recognition Camera Modal -->
    <div id="face-recognition-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-70 <?php echo !empty($selected_intern_id) ? 'hidden' : ''; ?>">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-auto overflow-hidden">
            <div class="p-6">
                <div class="text-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-camera text-primary-600 mr-2"></i>
                        Face Recognition
                    </h2>
                    <p class="text-gray-600 text-sm mt-1">Please look at the camera</p>
                </div>
                
                <!-- Camera View -->
                <div class="relative mx-auto w-full max-w-[320px] h-[240px] bg-black rounded-lg overflow-hidden">
                    <video id="video" class="w-full h-full object-cover" autoplay playsinline muted></video>
                    <canvas id="canvas" class="hidden w-full h-full"></canvas>
                    <div class="face-overlay"></div>
                    <div class="scanning-line"></div>
                    
                    <!-- Recognition Status -->
                    <div id="recognition-status" class="absolute bottom-2 left-0 right-0 text-center text-white text-sm font-medium bg-black bg-opacity-50 py-1">
                        Scanning for faces...
                    </div>
                </div>
                
                <!-- Recognition Result -->
                <div id="recognition-result" class="mt-4 p-4 rounded-lg bg-gray-50 hidden">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-xl pulse">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="ml-4">
                            <h3 id="recognized-name" class="font-medium text-gray-900">-</h3>
                            <p id="recognized-action" class="text-sm text-gray-600">-</p>
                        </div>
                    </div>
                </div>
                
                <!-- Skip Button -->
                <div class="mt-4 text-center">
                    <button id="skip-recognition" class="text-gray-600 hover:text-gray-800 text-sm">
                        Skip face recognition
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Face Recognition Confirmation Modal -->
    <div id="face-confirmation-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-70 <?php echo isset($_SESSION['pending_recognition']) && isset($_SESSION['recognized_face']) ? '' : 'hidden'; ?>">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-auto overflow-hidden">
            <div class="p-6">
                <div class="text-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-user-check text-primary-600 mr-2"></i>
                        Confirm Your Identity
                    </h2>
                    <p class="text-gray-600 text-sm mt-1">Is this really you?</p>
                </div>
                
                <?php if (isset($_SESSION['recognized_face'])): ?>
<!-- User Info from Session -->
<div class="mt-4 p-4 rounded-lg bg-gray-50">
    <div class="flex items-center">
        <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-2xl">
            <i class="fas fa-user"></i>
        </div>
        <div class="ml-4">
            <h3 class="font-medium text-gray-900"><?php echo $_SESSION['recognized_face']['intern_name']; ?></h3>
            <p class="text-sm text-gray-600">Similarity: <?php echo $_SESSION['recognized_face']['similarity']; ?>%</p>
        </div>
    </div>
</div>

<!-- Confirmation Buttons -->
<form method="post" class="mt-6 grid grid-cols-2 gap-4">
    <input type="hidden" name="confirm_recognition" value="1">
    <input type="hidden" name="intern_id" value="<?php echo $_SESSION['recognized_face']['intern_id']; ?>">
    <button type="submit" name="confirmation" value="yes" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
        <i class="fas fa-check-circle mr-2"></i>
        Yes, it's me
    </button>
    <button type="submit" name="confirmation" value="no" class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
        <i class="fas fa-times-circle mr-2"></i>
        No, it's not me
    </button>
</form>

<div class="mt-4 text-center">
    <p class="text-sm text-gray-500">If this is not you, you can scan again or select your name manually</p>
</div>
<?php else: ?>
<!-- Fallback for direct JavaScript population -->
<div class="mt-4 p-4 rounded-lg bg-gray-50">
    <div class="flex items-center">
        <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-2xl">
            <i class="fas fa-user"></i>
        </div>
        <div class="ml-4">
            <h3 class="font-medium text-gray-900"></h3>
            <p class="text-sm text-gray-600"></p>
        </div>
    </div>
</div>

<!-- Confirmation Buttons (will be enabled by JavaScript) -->
<form method="post" class="mt-6 grid grid-cols-2 gap-4">
    <input type="hidden" name="confirm_recognition" value="1">
    <input type="hidden" name="intern_id" value="">
    <button type="submit" name="confirmation" value="yes" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
        <i class="fas fa-check-circle mr-2"></i>
        Yes, it's me
    </button>
    <button type="submit" name="confirmation" value="no" class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
        <i class="fas fa-times-circle mr-2"></i>
        No, it's not me
    </button>
</form>

<div class="mt-4 text-center">
    <p class="text-sm text-gray-500">If this is not you, you can scan again or select your name manually</p>
</div>
<?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container mx-auto py-8">
        <!-- Header with Logo -->
        <div class="mb-10 bg-white rounded-xl shadow-md p-6 hover-card">
            <div class="flex flex-col md:flex-row items-center justify-center md:justify-between">
                <div class="flex items-center mb-4 md:mb-0">
                    <!-- Logo Image -->
                    <div class="mr-4">
                        <img src="./assets/images/Dict.png" alt="DICT Logo" class="w-20 h-20 object-contain rounded-full">
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">DICT Internship Timesheet</h1>
                        <p class="text-gray-600">Department of Information and Communication Technology</p>
                    </div>
                </div>
                <div class="text-center md:text-right">
                    <div class="bg-primary-50 text-primary-800 px-4 py-2 rounded-lg inline-block">
                        <p class="text-sm font-medium"><?php echo date('F d, Y'); ?></p>
                        <p class="text-lg font-bold" id="current-time"></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Alert Messages -->
        <?php if ($message != ""): ?>
            <div id="alert-message" class="mb-6 rounded-lg p-4 
    <?php
            if (strpos($message, 'successfully') !== false) {
                echo 'bg-green-100 text-green-800 border-l-4 border-green-500';
            } elseif (strpos($message, 'complete') !== false || strpos($message, 'finalized') !== false) {
                echo 'bg-blue-100 text-blue-800 border-l-4 border-blue-500';
            } else {
                echo 'bg-red-100 text-red-800 border-l-4 border-red-500';
            }
    ?> transition-all duration-500 ease-in-out shadow-md">
                <div class="flex items-center">
                    <i class="
        <?php
            if (strpos($message, 'successfully') !== false) {
                echo 'fas fa-check-circle text-green-500';
            } elseif (strpos($message, 'complete') !== false || strpos($message, 'finalized') !== false) {
                echo 'fas fa-info-circle text-blue-500';
            } else {
                echo 'fas fa-exclamation-circle text-red-500';
            }
        ?> mr-2 text-xl"></i>
                    <p class="font-medium"><?php echo $message; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Sidebar - Actions -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 mb-6 hover-card">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-tasks text-primary-600 mr-2"></i>
                        Actions
                    </h2>
                    <div class="space-y-3">
                        <a href="./functions/studentRegistration.php" class="flex items-center justify-between w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                            <span class="flex items-center">
                                <i class="fas fa-user-plus mr-2"></i>
                                Register Intern
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <button id="open-face-recognition" class="flex items-center justify-between w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                            <span class="flex items-center">
                                <i class="fas fa-camera mr-2"></i>
                                Face Scanner
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Admin Actions (including Delete) -->
                <div class="bg-white rounded-xl shadow-md p-6 hover-card">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-cog text-primary-600 mr-2"></i>
                        Actions
                    </h2>
                    <div class="space-y-3">
                        <!-- Delete Button -->
                        <button id="delete-button" class="flex items-center justify-between w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out border border-gray-300">
                            <span class="flex items-center">
                                <i class="fas fa-trash-alt text-red-600 mr-2"></i>
                                Delete Selected Intern
                            </span>
                            <i class="fas fa-shield-alt text-gray-500"></i>
                        </button>

                        <!-- About Us Button -->
                        <button id="about-us-button" class="flex items-center justify-between w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out border border-gray-300">
                            <span class="flex items-center">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                About Us
                            </span>
                            <i class="fas fa-arrow-right text-gray-500"></i>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Right Content Area -->
            <div class="lg:col-span-3">
                <!-- Intern Selection and Time Actions -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6 hover-card">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-clock text-primary-600 mr-2"></i>
                        Intern Time Management
                    </h2>

                    <form method="post" action="index.php">
                        <div class="mb-4">
                            <label for="intern-select" class="block text-sm font-medium text-gray-700 mb-1">Select Intern</label>
                            <select name="intern_id" id="intern-select" class="w-full bg-white border border-gray-300 text-gray-700 py-3 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-300 ease-in-out">
                                <option value="">Select an Intern</option>
                                <?php
                                // Reset the pointer to the beginning of the result set
                                $interns_stmt->execute();
                                while ($intern = $interns_stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                    <option value="<?php echo $intern['Intern_id']; ?>" <?php echo ($selected_intern_id == $intern['Intern_id']) ? 'selected' : ''; ?>>
                                        <?php echo $intern['Intern_Name']; ?>
                                        <?php echo isset($intern['Face_Registered']) && $intern['Face_Registered'] ? ' (Face Registered)' : ''; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <?php
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

                                        // Determine if it's morning or afternoon
                                        if ($current_hour < 12) {
                                            // Morning time-in
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
                                            // Afternoon time-in
                                            if (isTimeEmpty($timesheet_data['pm_timein'])) {
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
                                            } else {
                                                $_SESSION['message'] = "Afternoon time-in already recorded for today.";
                                            }
                                        }
                                    } else {
                                        // Create a new record for today
                                        // Get intern name and required hours
                                        $name_stmt = $conn->prepare("SELECT Intern_Name, Required_Hours_Rendered FROM interns WHERE Intern_id = :intern_id");
                                        $name_stmt->bindParam(':intern_id', $intern_id);
                                        $name_stmt->execute();
                                        $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
                                        $intern_name = $intern_data['Intern_Name'];
                                        $required_hours = $intern_data['Required_Hours_Rendered'];

                                        // Determine if it's morning or afternoon
                                        if ($current_hour < 12) {
                                            // Morning time-in
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
                                            // Apply the rounding rule for afternoon time-in
                                            // If time is between 12:00 PM and 1:00 PM, set to 1:00 PM exactly
                                            if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                                                $pm_time = '13:00:00'; // 1:00 PM exactly
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
                            }
                            ?>
                            <button type="submit" name="time_in" class="flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Time In
                            </button>
                            <button type="submit" name="time_out" class="flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                Time Out
                            </button>
                            <button type="submit" name="reset_entries" class="flex items-center justify-center bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md" onclick="return confirm('Are you sure you want to reset all entries?')">
                                <i class="fas fa-redo-alt mr-2"></i>
                                Reset
                            </button>
                            <button type="submit" name="export_csv" class="flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                                <i class="fas fa-file-export mr-2"></i>
                                Export
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Timesheet Status Card -->
                <?php if (!empty($selected_intern_id) && $timesheet_data): ?>
                    <div class="bg-white rounded-xl shadow-md p-2 mb-6 hover-card">
                        <div class="flex flex-col md:flex-row justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-calendar-check text-primary-600 mr-2"></i>
                                Today's Timesheet Status
                            </h2>
                            <div class="flex flex-col md:flex-row gap-2 mt-2 md:mt-0">
                                <div class="text-sm bg-green-50 text-green-800 px-3 py-1 rounded-full border border-green-200">
                                    <span class="font-medium">Overall Time:</span>
                                    <span class="font-bold"><?php echo formatDuration($total_time_rendered); ?></span>
                                </div>
                                <div class="text-sm bg-primary-50 text-primary-800 px-3 py-1 rounded-full border border-primary-200">
                                    <span class="font-medium">Required Hours:</span>
                                    <span class="font-bold"><?php echo $timesheet_data['required_hours']; ?> hours</span>
                                </div>
                            </div>
                        </div>

                        <!-- Intern Info Card -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4 border-l-4 border-primary-500">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-xl">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900"><?php echo $timesheet_data['intern_name']; ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo $timesheet_data['intern_school']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Morning Status -->
                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($timesheet_data['am_timein']) ? 'border-green-500' : 'border-gray-300'; ?> hover-card">
                                <h3 class="text-sm font-medium text-gray-600">Morning Time-In</h3>
                                <p class="text-lg font-bold <?php echo !isTimeEmpty($timesheet_data['am_timein']) ? 'text-green-600' : 'text-gray-400'; ?>">
                                    <?php echo formatTime($timesheet_data['am_timein']); ?>
                                </p>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($timesheet_data['am_timeOut']) ? 'border-red-500' : 'border-gray-300'; ?> hover-card">
                                <h3 class="text-sm font-medium text-gray-600">Morning Time-Out</h3>
                                <p class="text-lg font-bold <?php echo !isTimeEmpty($timesheet_data['am_timeOut']) ? 'text-red-600' : 'text-gray-400'; ?>">
                                    <?php echo formatTime($timesheet_data['am_timeOut']); ?>
                                </p>
                            </div>

                            <!-- Afternoon Status -->
                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($timesheet_data['pm_timein']) ? 'border-green-500' : 'border-gray-300'; ?> hover-card">
                                <h3 class="text-sm font-medium text-gray-600">Afternoon Time-In</h3>
                                <p class="text-lg font-bold <?php echo !isTimeEmpty($timesheet_data['pm_timein']) ? 'text-green-600' : 'text-gray-400'; ?>">
                                    <?php echo formatTime($timesheet_data['pm_timein']); ?>
                                </p>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($timesheet_data['pm_timeout']) ? 'border-red-500' : 'border-gray-300'; ?> hover-card">
                                <h3 class="text-sm font-medium text-gray-600">Afternoon Time-Out</h3>
                                <p class="text-lg font-bold <?php echo !isTimeEmpty($timesheet_data['pm_timeout']) ? 'text-red-600' : 'text-gray-400'; ?>">
                                    <?php echo formatTime($timesheet_data['pm_timeout']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Hours Summary -->
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-primary-50 rounded-lg p-4 border-l-4 border-primary-500 hover-card">
                                <h3 class="text-sm font-medium text-primary-700">Morning Hours</h3>
                                <p class="text-lg font-bold text-primary-800">
                                    <?php echo isTimeEmpty($timesheet_data['am_hours_worked']) ? '-' : formatDuration($timesheet_data['am_hours_worked']); ?>
                                </p>
                            </div>

                            <div class="bg-primary-50 rounded-lg p-4 border-l-4 border-primary-500 hover-card">
                                <h3 class="text-sm font-medium text-primary-700">Afternoon Hours</h3>
                                <p class="text-lg font-bold text-primary-800">
                                    <?php echo isTimeEmpty($timesheet_data['pm_hours_worked']) ? '-' : formatDuration($timesheet_data['pm_hours_worked']); ?>
                                </p>
                            </div>

                            <div class="bg-primary-100 rounded-lg p-4 border-l-4 border-primary-600 hover-card">
                                <h3 class="text-sm font-medium text-primary-800">Total Hours Today</h3>
                                <p class="text-lg font-bold text-primary-900">
                                    <?php echo isTimeEmpty($timesheet_data['day_total_hours']) ? '-' : formatDuration($timesheet_data['day_total_hours']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($selected_intern_id) && $intern_details): ?>
                    <!-- Intern selected but no timesheet entries -->
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6 hover-card">
                        <div class="flex flex-col md:flex-row justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-calendar-check text-primary-600 mr-2"></i>
                                Today's Timesheet Status
                            </h2>
                            <div class="flex flex-col md:flex-row gap-2 mt-2 md:mt-0">
                                <div class="text-sm bg-green-50 text-green-800 px-3 py-1 rounded-full border border-green-200">
                                    <span class="font-medium">Overall Time:</span>
                                    <span class="font-bold"><?php echo isset($total_time_rendered) ? formatDuration($total_time_rendered) : '0 hr 0 min'; ?></span>
                                </div>
                                <div class="text-sm bg-primary-50 text-primary-800 px-3 py-1 rounded-full border border-primary-200">
                                    <span class="font-medium">Required Hours:</span>
                                    <span class="font-bold"><?php echo $intern_details['Required_Hours_Rendered']; ?> hours</span>
                                </div>
                            </div>
                        </div>

                        <!-- Intern Info Card -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4 border-l-4 border-primary-500">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-xl">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900"><?php echo $intern_details['Intern_Name']; ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo $intern_details['Intern_School']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 text-center bg-gray-50 rounded-lg">
                            <i class="fas fa-clock text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500 font-medium">No timesheet entries for today</p>
                            <p class="text-sm text-gray-400 mt-1">Use the Time In button to start recording hours</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- No Intern selected -->
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6 hover-card">
                        <div class="p-6 text-center">
                            <i class="fas fa-user-clock text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500 font-medium">No Intern selected</p>
                            <p class="text-sm text-gray-400 mt-1">Please select an Intern from the dropdown or use face recognition</p>
                        </div>
                    </div>
                <?php endif; ?>


            </div>

        </div>
        <!-- Timesheet Table -->
        <?php

        include './includes/timeSheetRecords.php';

        ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden">
        <!-- Modal Overlay -->
        <div class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
            <!-- Modal Container -->
             <div class="modal-container bg-white rounded-lg shadow-xl max-w-md w-full mx-auto overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-red-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-white">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Confirm Deletion
                        </h3>
                        <button id="close-modal" class="text-white hover:text-gray-200 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4">
                    <div class="flex items-center mb-4 text-red-600">
                        <i class="fas fa-trash-alt text-3xl mr-3"></i>
                        <div>
                            <h4 class="font-bold text-lg">Delete Intern Record</h4>
                            <p class="text-gray-700">This action cannot be undone.</p>
                        </div>
                    </div>

                    <p class="text-gray-700 mb-4">
                        Are you sure you want to delete <span id="student-name" class="font-semibold"><?php echo htmlspecialchars($selected_student_name); ?></span>?
                    </p>
                    <p class="text-gray-600 text-sm mb-4">
                        All timesheet records associated with this Intern will also be permanently deleted.
                    </p>

                    <!-- Warning Box -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    This will permanently remove the Intern and all their data from the system.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                    <button id="cancel-delete" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button id="confirm-delete" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 ease-in-out">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="./assets/js/face-recognition.js"></script>
    <script src="./assets/js/index.js"></script>
</body>

</html>