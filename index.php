<?php 
include './main.php';

// Set up our initial variables - we'll use these later
$has_active_pause = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DICT Internship Timesheet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="./assets/images/Dict.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/index.css">


    <!-- Check if intern is currently timed in but not yet timed out -->
    <?php
    // Track if the intern has an active session (timed in but not out)
    $has_active_timein = false;
    $active_timein_session = false;

    if (!empty($selected_intern_id) && isset($current_timesheet) && is_array($current_timesheet)) {
        // Morning session check - has morning time-in but no time-out yet
        if (!isTimeEmpty($current_timesheet['am_timein']) && isTimeEmpty($current_timesheet['am_timeOut'])) {
            $has_active_timein = true;
        }
        // Afternoon session check - has afternoon time-in but no time-out yet
        else if (!isTimeEmpty($current_timesheet['pm_timein']) && isTimeEmpty($current_timesheet['pm_timeout'])) {
            $has_active_timein = true;
        }
        
        // Check if we have a valid timestamp in the session for this intern
        if (isset($_SESSION['timein_timestamp']) && $has_active_timein) {
            $active_timein_session = true;
        }
        
        // Check if the intern is currently on a break (pause started but not ended)
        if (!isTimeEmpty($current_timesheet['pause_start']) && isTimeEmpty($current_timesheet['pause_end'])) {
            $has_active_pause = true;
        }
    }
    
    // Load system settings from database
    $system_settings = [];
    try {
        $settings_stmt = $conn->query("SELECT * FROM system_settings");
        while ($row = $settings_stmt->fetch(PDO::FETCH_ASSOC)) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        // Don't worry if settings aren't available - we'll use defaults
    }
    
    // Set up company info with defaults if settings aren't found
    $company_name = isset($system_settings['company_name']) ? $system_settings['company_name'] : 'DICT Internship Timesheet';
    $company_header = isset($system_settings['company_header']) ? $system_settings['company_header'] : 'Department of Information and Communication Technology';
    $logo_path = isset($system_settings['logo_path']) ? $system_settings['logo_path'] : './assets/images/Dict.png';
    ?>

    <?php if($active_timein_session): ?>
    <input type="hidden" id="php-timein-timestamp" value="<?php echo $_SESSION['timein_timestamp']; ?>">
    <?php endif; ?>

    <script>
    // Save the current intern's ID to keep track between page refreshes
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
        <!-- Header with logo and title -->
        <?php include './components/header.php'; ?>
        
        <!-- System messages and alerts -->
        <?php include './components/alert-message.php'; ?>
        
        <!-- Show active break status if intern is on break -->
        <?php include './components/pause-status.php'; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left sidebar with action buttons -->
            <?php include './components/sidebar-actions.php'; ?>
            
            <!-- Main Content -->
            <div class="lg:col-span-9">
                <!-- Time in/out controls and current status -->
                <?php include './components/time-management.php'; ?>
                
                <!-- Current timesheet info for today -->
                <?php include './components/timesheet-status.php'; ?>
            </div>
        </div>
        
        <!-- Table of past timesheet entries -->
        <?php include './components/timesheet-records.php'; ?>
    </div>
    
    <?php 
    // Include all popup modals the system uses
    include './components/modals/delete-modal.php';
    include './components/modals/reset-modal.php';
    include './components/modals/delete-all-modal.php';
    include './components/modals/export-modal.php';
    include './components/modals/overtime-modal.php';
    include './components/modals/pause-modal.php';
    include './components/modals/settings-modal.php'; 
    include './components/modals/note-modal.php';
    include './components/modals/time-adjustment-modal.php'; 
    include './components/modals/about-us-modal.php';
    ?>

    <!-- Face recognition components for attendance -->
    <?php include './components/modals/face-scanner-modal.php'; ?>

    <!-- Photo capture components for verification -->
    <?php include './components/modals/camera-capture-modal.php'; ?>
    <?php include './components/modals/photo-gallery-modal.php'; ?>

    <link rel="stylesheet" href="./assets/css/face-scanner.css">
    <!-- Load utility scripts first -->
    <script src="./assets/js/utils.js"></script>
    <script src="./assets/js/core.js"></script>
    <script src="./assets/js/live-counters.js"></script>
    
    <!-- Load feature-specific scripts -->
    <script src="./assets/js/delete-student.js"></script>
    <script src="./assets/js/reset-entries.js"></script>
    <script src="./assets/js/export.js"></script>
    <script src="./assets/js/overtime.js"></script>
    <script src="./assets/js/pause.js"></script>
    <script src="./assets/js/settings.js"></script>
    <script src="./assets/js/notes.js"></script>
    <script src="./assets/js/time-adjustments.js"></script>
    <script src="./assets/js/button-controls.js"></script>
    <script src="./assets/js/overtime-counter.js"></script>
    <script src="./assets/js/about-us.js"></script>
    <script src="./assets/js/index.js"></script>
    
    <!-- Load face recognition and camera libraries -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="./assets/js/face-scanner.js"></script>
    <script src="./assets/js/camera-capture.js"></script>
    <script src="./assets/js/photo-gallery.js"></script>

    <!-- Prevent overtime button issues when no intern selected -->
    <script>
    // Catch clicks on the overtime button when it shouldn't be active
    document.addEventListener('click', function(e) {
        const overtimeBtn = document.getElementById('overtime-button');
        const internSelect = document.getElementById('intern-select');
        
        if (e.target === overtimeBtn || overtimeBtn.contains(e.target)) {
            // Block clicks if button should be disabled or no intern selected
            if (overtimeBtn.hasAttribute('disabled') || 
                overtimeBtn.getAttribute('aria-disabled') === 'true' || 
                !internSelect || 
                !internSelect.value) {
                
                e.preventDefault();
                e.stopPropagation();
                console.log("Overtime button click blocked - no intern selected");
                return false;
            }
        }
    }, true); // Use capture phase to catch events early
</script>
</body>
</html>