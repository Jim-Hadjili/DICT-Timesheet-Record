<?php 

include './main.php';

// Initialize timesheet statement
$timesheet_stmt = $conn->prepare("SELECT t.*, 
                                 t.record_id as id,
                                 i.Intern_School as intern_school, 
                                 i.Required_Hours_Rendered as required_hours, 
                                 DATE(t.created_at) as render_date, 
                                 t.notes as note
                                 FROM timesheet t 
                                 JOIN interns i ON t.intern_id = i.Intern_id 
                                 WHERE t.intern_id = :intern_id");

// Get sort and filter parameters
$sort_date = $_GET['sort_date'] ?? 'desc';
$filter = $_GET['filter'] ?? '';

// Build the query with sorting and filtering
$timesheet_query = "SELECT t.*, 
                   t.record_id as id,
                   i.Intern_School as intern_school, 
                   i.Required_Hours_Rendered as required_hours, 
                   DATE(t.created_at) as render_date,
                   t.notes as note
                   FROM timesheet t 
                   JOIN interns i ON t.intern_id = i.Intern_id 
                   WHERE t.intern_id = :intern_id";

// Add filter conditions
if ($filter === 'notes') {
    $timesheet_query .= " AND t.notes IS NOT NULL AND t.notes != ''";
} elseif ($filter === 'ot') {
    $timesheet_query .= " AND t.overtime_hours IS NOT NULL AND t.overtime_hours != '00:00:00'";
}

// Add sorting
$timesheet_query .= " ORDER BY t.created_at " . ($sort_date === 'asc' ? 'ASC' : 'DESC');

// Prepare and execute statement if intern is selected
if (!empty($selected_intern_id)) {
    $timesheet_stmt = $conn->prepare($timesheet_query);
    $timesheet_stmt->bindParam(':intern_id', $selected_intern_id);
    $timesheet_stmt->execute();
}

// Initialize important variables at the top of the file
$has_active_pause = false;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DICT Internship Timesheet</title>
    <link rel="icon" href="./assets/images/DICT.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./index.css">


    <!-- Pass the time-in timestamp and intern ID to JavaScript if they exist -->
    <?php
    // Check if the selected intern has timed in today but not yet timed out
    $has_active_timein = false;
    $active_timein_session = false;

    if (!empty($selected_intern_id) && isset($current_timesheet) && is_array($current_timesheet)) {
        // Check if there's a morning time-in without time-out
        if (!isTimeEmpty($current_timesheet['am_timein']) && isTimeEmpty($current_timesheet['am_timeOut'])) {
            $has_active_timein = true;
        }
        // Or if there's an afternoon time-in without time-out
        else if (!isTimeEmpty($current_timesheet['pm_timein']) && isTimeEmpty($current_timesheet['pm_timeout'])) {
            $has_active_timein = true;
        }
        
        // If the session timestamp exists and belongs to this intern
        if (isset($_SESSION['timein_timestamp']) && $has_active_timein) {
            $active_timein_session = true;
        }
        
        // Check if there's an active pause
        if (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
            $has_active_pause = true;
        }
    }
    ?>

    <?php if($active_timein_session): ?>
    <input type="hidden" id="php-timein-timestamp" value="<?php echo $_SESSION['timein_timestamp']; ?>">
    <?php endif; ?>

    <script>
    // Store the current intern ID for later comparison
    document.addEventListener('DOMContentLoaded', function() {
        const hasActiveTimin = <?php echo $has_active_timein ? 'true' : 'false'; ?>;
        const selectedInternId = "<?php echo $selected_intern_id; ?>";
        
        if (hasActiveTimin && selectedInternId) {
            sessionStorage.setItem('timein_intern_id', selectedInternId);
        }
    });
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-center mb-8 border-b pb-4">
            <div class="flex items-center mb-4 md:mb-0">
                <div class="relative w-20 h-20 mr-3">
                    <img src="./assets/images/Dict.png" alt="DICT Logo" class="w-full h-full object-contain">
                    <div class="absolute inset-0 rounded-full shadow-inner"></div>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">DICT Internship Timesheet</h1>
                    <p class="text-sm text-gray-600 flex items-center">
                        <i class="fas fa-map-marker-alt text-primary-500 mr-1"></i>
                        Department of Information and Communication Technology
                    </p>
                </div>
            </div>
            <div class="text-right bg-white shadow-sm rounded-lg px-4 py-2 border border-gray-100">
                <p class="text-sm text-gray-600 flex items-center justify-end">
                    <i class="far fa-calendar-alt text-primary-500 mr-1"></i>
                    <?php echo date('F d, Y'); ?>
                </p>
                <p class="text-lg font-semibold text-primary-600 flex items-center justify-end" id="current-time">
                    <i class="far fa-clock text-primary-500 mr-1"></i>
                    <span>Loading time...</span>
                </p>
            </div>
        </header>
        
        <!-- Alert Messages -->
        <?php if($message != ""): ?>
        <div id="alert-message" class="mb-6 rounded-lg p-0 overflow-hidden shadow-md transition-all duration-500 ease-in-out">
            <div class="flex items-stretch 
                <?php 
                if (strpos($message, 'successfully') !== false) {
                    echo 'bg-green-100';
                } elseif (strpos($message, 'Your duty for today is already complete') !== false) {
                    echo 'bg-blue-100';
                } else {
                    echo 'bg-red-100';
                }
                ?>">
                <div class="flex items-center justify-center px-4 
                    <?php 
                    if (strpos($message, 'successfully') !== false) {
                        echo 'bg-green-500';
                    } elseif (strpos($message, 'Your duty for today is already complete') !== false) {
                        echo 'bg-blue-500';
                    } else {
                        echo 'bg-red-500';
                    }
                    ?>">
                    <i class="<?php 
                        if (strpos($message, 'successfully') !== false) {
                            echo 'fas fa-check-circle text-white';
                        } elseif (strpos($message, 'Your duty for today is already complete') !== false) {
                            echo 'fas fa-info-circle text-white';
                        } else {
                            echo 'fas fa-exclamation-circle text-white';
                        }
                        ?> text-xl"></i>
                </div>
                <div class="flex-1 p-4 
                    <?php 
                    if (strpos($message, 'successfully') !== false) {
                        echo 'text-green-800';
                    } elseif (strpos($message, 'Your duty for today is already complete') !== false) {
                        echo 'text-blue-800';
                    } else {
                        echo 'text-red-800';
                    }
                    ?>">
                    <p class="font-medium"><?php echo $message; ?></p>
                </div>
                <button onclick="document.getElementById('alert-message').classList.add('fade-out')" class="px-4 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Pause Status - Change from yellow to purple/violet -->
        <?php if($has_active_pause): ?>
        <div class="mb-6 rounded-lg overflow-hidden shadow-md">
            <div class="bg-purple-100 border-l-4 border-purple-500 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-pause-circle text-purple-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-purple-700 font-medium">
                            Time is currently paused
                        </p>
                        <p class="text-xs text-purple-600 mt-1">
                            Your timesheet is paused. Time spent on break will not count toward your work hours.
                        </p>
                    </div>
                    <div class="ml-auto pl-3 flex-shrink-0">
                        <span class="bg-purple-200 px-2 py-1 rounded text-purple-800 font-medium text-xs">
                            <span id="status-pause-time">00:00:00</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <script>
        // Add a counter for the status bar pause time
        document.addEventListener('DOMContentLoaded', function() {
            const pauseStartTime = <?php echo strtotime($current_timesheet['pause_start']); ?>;
            const statusPauseTime = document.getElementById('status-pause-time');
            
            if (statusPauseTime) {
                setInterval(function() {
                    const currentTime = Math.floor(Date.now() / 1000);
                    const elapsed = currentTime - pauseStartTime;
                    
                    // Format the time
                    const hours = Math.floor(elapsed / 3600);
                    const minutes = Math.floor((elapsed % 3600) / 60);
                    const seconds = elapsed % 60;
                    
                    statusPauseTime.textContent = 
                        String(hours).padStart(2, '0') + ':' + 
                        String(minutes).padStart(2, '0') + ':' + 
                        String(seconds).padStart(2, '0');
                }, 1000);
            }
        });
        </script>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Sidebar Actions -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3">
                        <h2 class="flex items-center text-lg font-semibold text-white">
                            <i class="fas fa-tasks mr-2"></i>
                            Actions
                        </h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <a href="./functions/studentRegistration.php" class="flex items-center justify-between w-full bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                            <span class="flex items-center">
                                <i class="fas fa-user-plus mr-2"></i>
                                Register Intern
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="#" class="flex items-center justify-between w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                            <span class="flex items-center">
                                <i class="fas fa-camera mr-2"></i>
                                Face Scanner
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    
                    <div class="px-4 pt-2 pb-4">
                        <h3 class="flex items-center text-md font-semibold text-gray-700 mb-3 border-b pb-2">
                            <i class="fas fa-ellipsis-h text-primary-500 mr-2"></i>
                            More Actions
                        </h3>
                        <div class="space-y-2">
                            <button id="delete-button" class="flex items-center justify-between w-full bg-white hover:bg-red-50 text-gray-700 hover:text-red-600 font-medium py-2 px-3 rounded-lg border border-gray-200 hover:border-red-200 transition duration-300 ease-in-out">
                                <span class="flex items-center">
                                    <i class="fas fa-trash-alt text-red-500 mr-2"></i>
                                    Delete Selected Intern
                                </span>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </button>
                            <button id="open-about-us-modal" class="flex items-center justify-between w-full bg-white hover:bg-primary-50 text-gray-700 hover:text-primary-600 font-medium py-2 px-3 rounded-lg border border-gray-200 hover:border-primary-200 transition duration-300 ease-in-out">
                                <span class="flex items-center">
                                    <i class="fas fa-info-circle text-primary-500 mr-2"></i>
                                    About Us
                                </span>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="lg:col-span-9">
                <!-- Intern Selection and Time Management -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3">
                        <h2 class="flex items-center text-lg font-semibold text-white">
                            <i class="fas fa-user-clock mr-2"></i>
                            Intern Time Management
                        </h2>
                    </div>
                    
                    <form method="post" action="index.php" class="p-4 space-y-4">
                        <div class="mb-4">
                            <label for="intern-select" class="block text-sm font-medium text-gray-700 mb-1">Select Intern</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <select name="intern_id" id="intern-select" class="w-full bg-white border border-gray-300 text-gray-700 py-3 pl-10 pr-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-300 ease-in-out appearance-none">
                                    <option value="">Select a student</option>
                                    <?php 
                                    // Reset the pointer to the beginning of the result set
                                    $interns_stmt->execute();
                                    while($intern = $interns_stmt->fetch(PDO::FETCH_ASSOC)): 
                                    ?>
                                    <option value="<?php echo $intern['Intern_id']; ?>" <?php echo ($selected_intern_id == $intern['Intern_id']) ? 'selected' : ''; ?>>
                                        <?php echo $intern['Intern_Name']; ?>
                                        <?php echo isset($intern['Face_Registered']) && $intern['Face_Registered'] ? ' (Face Registered)' : ''; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-6 gap-2">
                            <button type="submit" name="time_in" class="flex items-center justify-center bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-green-500 disabled:hover:to-green-600" <?php echo ($has_active_pause) ? 'disabled' : ''; ?>>
                                <i class="fas fa-sign-in-alt mr-1"></i>
                                Time In
                            </button>
                            <button type="submit" name="time_out" class="flex items-center justify-center bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-red-500 disabled:hover:to-red-600" <?php echo ($has_active_pause) ? 'disabled' : ''; ?>>
                                <i class="fas fa-sign-out-alt mr-1"></i>
                                Time Out
                            </button>
                            <button type="button" id="pause-button" class="flex items-center justify-center bg-gradient-to-r <?php echo ($has_active_pause) ? 'from-green-500 to-green-600 hover:from-green-600 hover:to-green-700' : 'from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700'; ?> text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" <?php echo (!$has_active_timein && !$has_active_pause) ? 'disabled' : ''; ?>>
                                <i class="fas <?php echo ($has_active_pause) ? 'fa-play mr-1' : 'fa-pause mr-1'; ?>"></i>
                                <?php echo ($has_active_pause) ? 'Resume' : 'Pause'; ?>
                            </button>
                            <button type="button" id="overtime-button" class="flex items-center justify-center bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-amber-500 disabled:hover:to-amber-600" <?php echo (!$overtime_enabled || $has_active_pause) ? 'disabled' : ''; ?>>
                                <i class="fas fa-business-time mr-1"></i>
                                Overtime
                            </button>
                            <button type="button" id="reset-button" class="flex items-center justify-center bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg">
                                <i class="fas fa-redo-alt mr-1"></i>
                                Reset
                            </button>
                            <button type="submit" name="export_csv" class="flex items-center justify-center bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-medium py-3 px-2 rounded-lg transition duration-300 ease-in-out hover:shadow-lg">
                                <i class="fas fa-file-export mr-1"></i>
                                Export
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Timesheet Status Card -->
                <?php if(!empty($selected_intern_id) && isset($current_timesheet) && $current_timesheet): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-4 py-3">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                            <h2 class="text-lg font-semibold text-white flex items-center">
                                <i class="fas fa-clipboard-check mr-2"></i>
                                Today's Timesheet Status
                            </h2>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="text-sm bg-white bg-opacity-20 text-white px-3 py-1 rounded-full border border-white border-opacity-30">
                                    <span class="font-medium">Overall:</span> 
                                    <span class="font-bold"><?php echo formatDuration($total_time_rendered); ?></span>
                                </div>
                                <div class="text-sm bg-white bg-opacity-20 text-white px-3 py-1 rounded-full border border-white border-opacity-30">
                                    <span class="font-medium">Required:</span> 
                                    <span class="font-bold"><?php echo $intern_details['Required_Hours_Rendered']; ?> hours</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Morning Status -->
                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($current_timesheet['am_timein']) ? 'border-green-500' : 'border-gray-300'; ?> hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-600">Morning Time-In</h3>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo !isTimeEmpty($current_timesheet['am_timein']) ? 'bg-green-100 text-green-500' : 'bg-gray-100 text-gray-300'; ?>">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 <?php echo !isTimeEmpty($current_timesheet['am_timein']) ? 'text-green-600' : 'text-gray-400'; ?>">
                                    <?php echo formatTime($current_timesheet['am_timein']); ?>
                                </p>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($current_timesheet['am_timeOut']) ? 'border-red-500' : 'border-gray-300'; ?> hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-600">Morning Time-Out</h3>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo !isTimeEmpty($current_timesheet['am_timeOut']) ? 'bg-red-100 text-red-500' : 'bg-gray-100 text-gray-300'; ?>">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 <?php echo !isTimeEmpty($current_timesheet['am_timeOut']) ? 'text-red-600' : 'text-gray-400'; ?>">
                                    <?php echo formatTime($current_timesheet['am_timeOut']); ?>
                                </p>
                            </div>
                            
                            <!-- Afternoon Status -->
                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($current_timesheet['pm_timein']) ? 'border-green-500' : 'border-gray-300'; ?> hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-600">Afternoon Time-In</h3>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo !isTimeEmpty($current_timesheet['pm_timein']) ? 'bg-green-100 text-green-500' : 'bg-gray-100 text-gray-300'; ?>">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 <?php echo !isTimeEmpty($current_timesheet['pm_timein']) ? 'text-green-600' : 'text-gray-400'; ?>">
                                    <?php echo formatTime($current_timesheet['pm_timein']); ?>
                                </p>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo !isTimeEmpty($current_timesheet['pm_timeout']) ? 'border-red-500' : 'border-gray-300'; ?> hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-600">Afternoon Time-Out</h3>
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo !isTimeEmpty($current_timesheet['pm_timeout']) ? 'bg-red-100 text-red-500' : 'bg-gray-100 text-gray-300'; ?>">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 <?php echo !isTimeEmpty($current_timesheet['pm_timeout']) ? 'text-red-600' : 'text-gray-400'; ?>">
                                    <?php echo formatTime($current_timesheet['pm_timeout']); ?>
                                </p>
                            </div>
                            
                            <!-- Overtime Status - Modified to take full width -->
                            <?php if(isset($current_timesheet['overtime_start']) || isset($_SESSION['overtime_active'])): ?>
                            <div class="col-span-1 sm:col-span-2 lg:col-span-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                
                            </div>
                            <?php endif; ?>
                            
                            <!-- Overtime Status - Only show when there's actual overtime activity -->
                            <?php 
                            $has_overtime_activity = (!isTimeEmpty($current_timesheet['overtime_start']) || 
                                                     !isTimeEmpty($current_timesheet['overtime_end']) || 
                                                     (isset($_SESSION['overtime_active']) && $_SESSION['overtime_intern_id'] == $selected_intern_id));

                            if($has_overtime_activity): 
                            ?>
                            <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                                <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo (!isTimeEmpty($current_timesheet['overtime_start']) && isTimeEmpty($current_timesheet['overtime_end'])) ? 'border-amber-500' : 'border-amber-300'; ?> hover:shadow-md transition-all duration-300">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-medium text-gray-600">
                                            <?php echo (!isTimeEmpty($current_timesheet['overtime_start']) && isTimeEmpty($current_timesheet['overtime_end'])) ? 'Active Overtime' : 'Overtime Session'; ?>
                                        </h3>
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo (!isTimeEmpty($current_timesheet['overtime_start']) && isTimeEmpty($current_timesheet['overtime_end'])) ? 'bg-amber-100 text-amber-500' : 'bg-amber-100 text-amber-400'; ?>">
                                            <i class="fas fa-business-time"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                        <div>
                                            <p class="text-xs text-gray-500">Start Time:</p>
                                            <p class="text-lg font-bold <?php echo !isTimeEmpty($current_timesheet['overtime_start']) ? 'text-amber-600' : 'text-gray-400'; ?>">
                                                <?php 
                                                if(!isTimeEmpty($current_timesheet['overtime_start'])) {
                                                    echo formatTime($current_timesheet['overtime_start']);
                                                } elseif(isset($_SESSION['overtime_start']) && $_SESSION['overtime_intern_id'] == $selected_intern_id) {
                                                    echo formatTime($_SESSION['overtime_start']);
                                                } else {
                                                    echo '--:--';
                                                } 
                                                ?>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-xs text-gray-500">End Time:</p>
                                            <p class="text-lg font-bold <?php echo !isTimeEmpty($current_timesheet['overtime_end']) ? 'text-amber-600' : 'text-gray-400'; ?>">
                                                <?php echo formatTime($current_timesheet['overtime_end']); ?>
                                            </p>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Pause Status - Add this after overtime status -->
                            <?php if(isset($current_timesheet['pause_duration']) && !isTimeEmpty($current_timesheet['pause_duration']) || 
                                      (isset($current_timesheet['pause_start']) && !isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end']))): ?>
                            <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                                <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'border-purple-600' : 'border-purple-500'; ?> hover:shadow-md transition-all duration-300">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-medium text-gray-600">
                                            <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'Active Pause' : 'Total Pause Duration'; ?>
                                        </h3>
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'bg-purple-100 text-purple-600' : 'bg-purple-100 text-purple-500'; ?>">
                                            <i class="fas <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'fa-pause-circle' : 'fa-hourglass'; ?>"></i>
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold mt-1 <?php echo (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 'text-purple-600' : 'text-purple-600'; ?>">
                                        <?php 
                                        if(!isTimeEmpty($current_timesheet['pause_duration'])) {
                                            $pause_duration_display = formatDuration($current_timesheet['pause_duration']);
                                            
                                            if(!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
                                                // Active pause - will accumulate more time
                                                echo $pause_duration_display . ' <span class="text-xs text-purple-600">+ counting</span>';
                                                echo '<input type="hidden" id="accumulated-pause-time" value="' . timeToSeconds($current_timesheet['pause_duration']) . '">';
                                            } else {
                                                echo $pause_duration_display;
                                            }
                                        } elseif(!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
                                            echo '<span id="live-pause-duration" class="text-purple-600">00:00:00</span>';
                                            echo '<input type="hidden" id="live-pause-start" value="' . strtotime($current_timesheet['pause_start']) . '">';
                                        } else {
                                            echo '--:--';
                                        }
                                        ?>
                                    </p>
                                    
                                    <?php
                                    // Display number of pauses today
                                    try {
                                        // First check if the table exists
                                        $table_check = $conn->query("SHOW TABLES LIKE 'pause_history'");
                                        if ($table_check->rowCount() > 0) {
                                            $pause_count_stmt = $conn->prepare("SELECT COUNT(*) as pause_count FROM pause_history 
                                                                          WHERE intern_id = :intern_id AND DATE(created_at) = CURRENT_DATE()");
                                            $pause_count_stmt->bindParam(':intern_id', $selected_intern_id);
                                            $pause_count_stmt->execute();
                                            $pause_count = $pause_count_stmt->fetch(PDO::FETCH_ASSOC)['pause_count'];
                                            
                                            if($pause_count > 0 || (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end']))): 
                                                $current_count = (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) ? 1 : 0;
                                                $total_count = $pause_count + $current_count;
                                            ?>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <span class="bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded-full text-xs">
                                                    <?php echo $total_count; ?> pause<?php echo $total_count > 1 ? 's' : ''; ?> today
                                                </span>
                                            </p>
                                            <?php endif;
                                        }
                                    } catch (Exception $e) {
                                        // Ignore errors
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Hours Summary -->
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="bg-gradient-to-r from-primary-50 to-primary-100 rounded-lg p-4 border-l-4 border-primary-500 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-primary-700">Morning Hours</h3>
                                    <div class="w-8 h-8 rounded-full bg-primary-200 flex items-center justify-center text-primary-600">
                                        <i class="fas fa-sun"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 text-primary-800">
                                    <?php echo isTimeEmpty($current_timesheet['am_hours_worked']) ? '-' : formatDuration($current_timesheet['am_hours_worked']); ?>
                                </p>
                            </div>
                            
                            <div class="bg-gradient-to-r from-primary-50 to-primary-100 rounded-lg p-4 border-l-4 border-primary-500 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-primary-700">Afternoon Hours</h3>
                                    <div class="w-8 h-8 rounded-full bg-primary-200 flex items-center justify-center text-primary-600">
                                        <i class="fas fa-moon"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 text-primary-800">
                                    <?php echo isTimeEmpty($current_timesheet['pm_hours_worked']) ? '-' : formatDuration($current_timesheet['pm_hours_worked']); ?>
                                </p>
                            </div>
                            
                            <div class="bg-gradient-to-r from-amber-50 to-amber-100 rounded-lg p-4 border-l-4 border-amber-500 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-amber-700">Overtime Hours</h3>
                                    <div class="w-8 h-8 rounded-full bg-amber-200 flex items-center justify-center text-amber-600">
                                        <i class="fas fa-business-time"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 text-amber-800">
                                    <?php echo isset($current_timesheet['overtime_hours']) && !isTimeEmpty($current_timesheet['overtime_hours']) ? formatDuration($current_timesheet['overtime_hours']) : '-'; ?>
                                </p>
                            </div>
                            
                            <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-4 border-l-4 border-purple-500 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-purple-700">Total Pause</h3>
                                    <div class="w-8 h-8 rounded-full bg-purple-200 flex items-center justify-center text-purple-600">
                                        <i class="fas fa-hourglass"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 text-purple-800">
                                    <?php 
                                    if(!isTimeEmpty($current_timesheet['pause_duration'])) {
                                        $pause_duration_display = formatDuration($current_timesheet['pause_duration']);
                                        
                                        if(!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
                                            // Active pause - will accumulate more time
                                            echo $pause_duration_display . ' <span class="text-xs text-purple-600">+</span>';
                                            echo '<input type="hidden" id="accumulated-pause-time" value="' . timeToSeconds($current_timesheet['pause_duration']) . '">';
                                        } else {
                                            echo $pause_duration_display;
                                        }
                                    } elseif(!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
                                        echo '<span id="live-pause-duration" class="text-purple-600">00:00:00</span>';
                                        echo '<input type="hidden" id="live-pause-start" value="' . strtotime($current_timesheet['pause_start']) . '">';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <div class="bg-gradient-to-r from-primary-100 to-primary-200 rounded-lg p-4 border-l-4 border-primary-600 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-primary-800">Total Hours</h3>
                                    <div class="w-8 h-8 rounded-full bg-primary-300 flex items-center justify-center text-primary-700">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                                <p class="text-xl font-bold mt-1 text-primary-900">
                                    <?php echo isTimeEmpty($current_timesheet['day_total_hours']) ? '-' : formatDuration($current_timesheet['day_total_hours']); ?>
                                </p>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                <?php elseif(!empty($selected_intern_id)): ?>
                <!-- Student selected but no timesheet entries -->
                <div class="bg-white rounded-xl shadow-md p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                        <h2 class="text-lg font-semibold text-gray-800">Today's Timesheet Status</h2>
                        <div class="flex flex-col sm:flex-row gap-2">
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
                    <div class="p-6 text-center">
                        <i class="fas fa-clock text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">No timesheet entries for today</p>
                        <p class="text-sm text-gray-400 mt-1">Use the Time In button to start recording hours</p>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
        </div>
        <!-- Timesheet Records -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center mb-2 sm:mb-0">
                                <i class="fas fa-table text-primary-600 mr-2"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Timesheet Records</h2>
                            </div>
                            <form method="get" class="flex flex-wrap gap-2 items-center justify-end">
                                <?php if (!empty($selected_intern_id)): 
                                    $available_months = getAvailableMonths($conn, $selected_intern_id);
                                    // Only show month filter if there are months available (2 or more)
                                    if (!empty($available_months)):
                                ?>
                                    <label class="text-sm text-gray-700 mr-2">Month:</label>
                                    <select name="month" onchange="this.form.submit()" class="border rounded px-2 py-1 text-sm">
                                        <option value="">All Months</option>
                                        <?php foreach ($available_months as $month): ?>
                                            <option value="<?php echo $month['month_year']; ?>" 
                                                <?php if(($_GET['month'] ?? '') === $month['month_year']) echo 'selected'; ?>>
                                                <?php echo $month['month_display']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php 
                                    endif;
                                endif; 
                                ?>
                                <label class="text-sm text-gray-700 ml-4 mr-2">Sort by date:</label>
                                <select name="sort_date" onchange="this.form.submit()" class="border rounded px-2 py-1 text-sm">
                                    <option value="desc" <?php if(($_GET['sort_date'] ?? 'desc') === 'desc') echo 'selected'; ?>>Newest First</option>
                                    <option value="asc" <?php if(($_GET['sort_date'] ?? '') === 'asc') echo 'selected'; ?>>Oldest First</option>
                                </select>

                                <label class="text-sm text-gray-700 ml-4 mr-2">Show:</label>
                                <select name="filter" onchange="this.form.submit()" class="border rounded px-2 py-1 text-sm">
                                    <option value="">All Records</option>
                                    <option value="notes" <?php if(($_GET['filter'] ?? '') === 'notes') echo 'selected'; ?>>Notes Only</option>
                                    <option value="ot" <?php if(($_GET['filter'] ?? '') === 'ot') echo 'selected'; ?>>OT Only</option>
                                </select>

                                <?php if (!empty($selected_intern_id)): ?>
                                    <input type="hidden" name="intern_id" value="<?php echo htmlspecialchars($selected_intern_id); ?>">
                                <?php endif; ?>
                            </form>
                        </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AM Time In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AM Time Out</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PM Time In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PM Time Out</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AM Hours</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PM Hours</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OT Hours</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pause</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if($timesheet_stmt->rowCount() > 0): ?>
                                    <?php 
                                    // Reset the pointer to the beginning of the result set
                                    $timesheet_stmt->execute();
                                    while($row = $timesheet_stmt->fetch(PDO::FETCH_ASSOC)): 
                                    ?>
                                        <?php if($row['render_date'] != NULL && $row['render_date'] != '0000-00-00'): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                                    <?php echo date('M d, Y', strtotime($row['render_date'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatTime($row['am_timein']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatTime($row['am_timeOut']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatTime($row['pm_timein']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatTime($row['pm_timeout']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if(!isTimeEmpty($row['am_hours_worked'])): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <?php echo formatDuration($row['am_hours_worked']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if(!isTimeEmpty($row['pm_hours_worked'])): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <?php echo formatDuration($row['pm_hours_worked']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if(isset($row['overtime_hours']) && !isTimeEmpty($row['overtime_hours'])): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                        <?php echo formatDuration($row['overtime_hours']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if(isset($row['pause_duration']) && !isTimeEmpty($row['pause_duration'])): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        <?php echo formatDuration($row['pause_duration']); ?>
                                                        <?php if(!empty($row['pause_reason'])): ?>
                                                            <span class="ml-1 cursor-help" title="<?php echo htmlspecialchars($row['pause_reason']); ?>">
                                                                <i class="fas fa-info-circle"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <?php if(!isTimeEmpty($row['day_total_hours'])): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                                        <?php echo formatDuration($row['day_total_hours']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex items-center">
                                                    <button type="button" 
                                                        class="note-button text-xs <?php echo !empty($row['note']) ? 'bg-blue-500 hover:bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'; ?> font-medium py-1 px-2 rounded transition-colors relative"
                                                        data-date="<?php echo date('M d, Y', strtotime($row['render_date'])); ?>"
                                                        data-note="<?php echo htmlspecialchars($row['note'] ?? ''); ?>"
                                                        data-note-id="<?php echo $row['id']; ?>"
                                                        data-intern-name="<?php 
                                                            // Get the currently selected intern's name
                                                            if (!empty($selected_intern_id)) {
                                                                $intern_stmt = $conn->prepare('SELECT Intern_Name FROM interns WHERE Intern_id = :id');
                                                                $intern_stmt->bindParam(':id', $selected_intern_id);
                                                                $intern_stmt->execute();
                                                                $intern_data = $intern_stmt->fetch(PDO::FETCH_ASSOC);
                                                                echo htmlspecialchars($intern_data['Intern_Name'] ?? 'Unknown Intern');
                                                            } else {
                                                                echo 'Unknown Intern';
                                                            }
                                                        ?>">
                                                        <?php echo !empty($row['note']) ? 'View Note' : 'Add Note'; ?>
                                                        <?php if(!empty($row['note'])): ?>
                                                            <span class="absolute -top-1 -right-1 block h-2 w-2 rounded-full bg-red-500"></span>
                                                        <?php endif; ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="px-6 py-10 text-center text-sm text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center text-gray-300 mb-3">
                                                    <i class="fas fa-clipboard-list text-3xl"></i>
                                                </div>
                                                <p class="font-medium text-gray-600">No records found</p>
                                                <?php if(empty($selected_intern_id)): ?>
                                                    <p class="text-xs mt-1 text-gray-500">Please select a student to view their timesheet data</p>
                                                <?php else: ?>
                                                    <p class="text-xs mt-1 text-gray-500">No timesheet entries found for this student</p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
    </div>
    
    <?php 
    // Include all modal components
    include './components/modals/about-us-modal.php';
    include './components/modals/delete-modal.php';
    include './components/modals/reset-modal.php';
    include './components/modals/delete-all-modal.php';
    include './components/modals/export-modal.php';
    include './components/modals/overtime-modal.php';
    include './components/modals/pause-modal.php';
    include './components/modals/notes-modal.php';
    ?>
    
</body>
<script src="./index.js"></script>
<script src="./assets/js/overtime.js"></script>
<script src="./assets/js/pause.js"></script>
<script src="./assets/js/notes.js"></script>
</html>