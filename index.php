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
   
    <title>DICT Internship Timesheet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/index.css">
    <script src="./assets/js/tailwindConfig.js"></script>
    <link rel="icon" href="./assets/images/Dict.png" type="image/png" sizes="180x180">
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Face Recognition Camera Modal -->
    <?php include './components/faceRecognationModal.php'; ?>

    <div class="container mx-auto px-12 py-8">
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
                        More Actions
                    </h2>
                    <div class="space-y-3">
                        <!-- Delete Button -->
                        <button id="delete-button" disabled class="flex items-center justify-between w-full bg-gray-200 hover:bg-gray-300 text-gray-400 font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out border border-gray-300 opacity-60 cursor-not-allowed">
                            <span class="flex items-center text-left">
                                <i class="fas fa-trash-alt text-red-300 mr-2"></i>
                                Delete Selected Intern
                            </span>
                            <i class="fas fa-shield-alt text-gray-300"></i>
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

                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
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
                            <button type="submit" name="overtime" class="flex items-center justify-center bg-purple-800 hover:bg-purple-900 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out shadow-md">
                                <i class="fas fa-clock mr-2"></i>
                                Overtime
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

                        <!-- Overtime Start/End Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-2">
                            <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-500 hover-card col-span-2">
                                <h3 class="text-sm font-medium text-purple-700">Overtime Start</h3>
                                <p class="text-lg font-bold text-purple-800">
                                    <?php echo formatTime($timesheet_data['overtime_start']); ?>
                                </p>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-500 hover-card col-span-2">
                                <h3 class="text-sm font-medium text-purple-700">Overtime End</h3>
                                <p class="text-lg font-bold text-purple-800">
                                    <?php echo formatTime($timesheet_data['overtime_end']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Hours Summary -->
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
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

                            <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-500 hover-card">
                                <h3 class="text-sm font-medium text-purple-700">Overtime Hours</h3>
                                <p class="text-lg font-bold text-purple-800">
                                    <?php echo isTimeEmpty($timesheet_data['overtime_hours']) ? '-' : formatDuration($timesheet_data['overtime_hours']); ?>
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
        <?php include './includes/timeSheetRecords.php'; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <?php include './components/indexDeleteModal.php'; ?>

    <!-- About Us Modal -->
    <?php include './components/AboutUsModal.php'; ?>

    <!-- Overtime Modal -->
    <?php include './components/afterHoursModal.php'; ?>
    <?php include './components/overtimeWarningModal.php'; ?>
    <?php include './components/overtimeConfirmModal.php'; ?>

    <!-- Reset Modal -->
    <?php include './components/ResetModal.php'; ?>

    <!-- Export Modal -->
    <?php include './components/ExportModal.php'; ?>

    <!-- Select Intern Modal -->
    <div id="select-intern-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
              </div>
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                  Select an Intern
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500">
                    Please select an intern before attempting to delete.
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button id="close-select-intern-modal" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
              OK
            </button>
          </div>
        </div>
      </div>
    </div>

    <?php if (isset($_SESSION['afternoon_already_out'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        document.getElementById('afternoon-already-out-modal').classList.remove('hidden');
    });
</script>
<?php unset($_SESSION['afternoon_already_out']); endif; ?>

    <script src="./assets/js/about_us.js"></script>
    <script src="./assets/js/face-recognition.js"></script>
    <script src="./assets/js/index.js"></script>
</body>

</html>