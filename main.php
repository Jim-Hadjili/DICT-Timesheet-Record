<?php 
// Start session to keep track of user data across pages
session_start();

// Set timezone to Philippines for consistent time tracking
date_default_timezone_set('Asia/Manila');

// Include core system files
include 'connection/conn.php';
include 'timesheet_photos.php';

// Include helper functions organized by category
include 'utilities/schema_utils.php';  // Database structure management
include 'utilities/reset_entries.php';
include 'utilities/session_handler.php';
include 'utilities/time_utils.php';
include 'utilities/overtime_utils.php';
include 'utilities/delete_utils.php';
include 'utilities/export_utils.php';
include 'utilities/pause_utils.php';
include 'utilities/time_entry_utils.php';

// Make sure our database has all the tables and fields it needs
$schema_results = ensureTimesheetSchema($conn);

// Initialize message variable for notifications
$message = ""; // Default to empty (no messages)
$selected_intern_id = isset($_GET['intern_id']) ? $_GET['intern_id'] : (isset($_POST['intern_id']) ? $_POST['intern_id'] : '');

// Check if we have any messages from previous operations
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    // Once displayed, remove the message from session
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get all interns for the dropdown menu
$interns_stmt = $conn->prepare("SELECT * FROM interns ORDER BY Intern_Name ASC");
$interns_stmt->execute();

// Set up query to fetch timesheet records with school info
$timesheet_stmt = $conn->prepare("SELECT t.*, i.Intern_School as intern_school, i.Required_Hours_Rendered as required_hours, DATE(t.created_at) as render_date 
                                 FROM timesheet t 
                                 JOIN interns i ON t.intern_id = i.Intern_id 
                                 WHERE t.intern_id = :intern_id 
                                 ORDER BY t.created_at DESC");

// If a specific intern is selected, load their information
if (!empty($selected_intern_id)) {
    $timesheet_stmt->bindParam(':intern_id', $selected_intern_id);
    $timesheet_stmt->execute();
    
    // Get personal details of the selected intern
    $intern_details_stmt = $conn->prepare("SELECT * FROM interns WHERE Intern_id = :intern_id");
    $intern_details_stmt->bindParam(':intern_id', $selected_intern_id);
    $intern_details_stmt->execute();
    $intern_details = $intern_details_stmt->fetch(PDO::FETCH_ASSOC);

    // Get today's timesheet entry for the selected intern
    $today_timesheet_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = CURRENT_DATE()");
    $today_timesheet_stmt->bindParam(':intern_id', $selected_intern_id);
    $today_timesheet_stmt->execute();
    $current_timesheet = $today_timesheet_stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate total hours the intern has rendered so far
    $total_time_stmt = $conn->prepare("SELECT SUM(TIME_TO_SEC(day_total_hours)) as total_seconds FROM timesheet WHERE intern_id = :intern_id");
    $total_time_stmt->bindParam(':intern_id', $selected_intern_id);
    $total_time_stmt->execute();
    $total_time_data = $total_time_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Convert the total seconds to hours:minutes:seconds format
    $total_seconds = $total_time_data['total_seconds'] ?: 0;
    $total_time_rendered = secondsToTime($total_seconds);
} else {
    // No intern selected, return empty result
    $timesheet_stmt = $conn->prepare("SELECT 1 WHERE 0");
    $timesheet_stmt->execute();
}

// Handle all form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Time In functionality
    if (isset($_POST['time_in']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Record the time-in event
        $result = recordTimeIn($conn, $intern_id);
        
        // Save result message for display after redirect
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['message_type'];
        
        // If this is a new record, attach any pending photo
        if ($result['success'] && isset($result['new_record']) && $result['new_record'] && isset($_SESSION['pending_photo'])) {
            // Get the newly created timesheet record ID
            $new_record_stmt = $conn->prepare("SELECT record_id FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = CURRENT_DATE()");
            $new_record_stmt->bindParam(':intern_id', $intern_id);
            $new_record_stmt->execute();
            $new_record = $new_record_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($new_record && isset($new_record['record_id'])) {
                // Save the pending photo with the timesheet record
                save_pending_photo($conn, $intern_id, $new_record['record_id']);
            }
        }

        // Redirect to prevent form resubmission on refresh
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Time Out functionality
    if (isset($_POST['time_out']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Record the time-out event
        $result = recordTimeOut($conn, $intern_id);
        
        // Save result message for display after redirect
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['message_type'];
        
        // Clean up any session variables that are no longer needed
        if(isset($_SESSION['timein_timestamp'])) unset($_SESSION['timein_timestamp']);
        if(isset($_SESSION['timein_intern_id'])) unset($_SESSION['timein_intern_id']);
        if(isset($_SESSION['overtime_active'])) unset($_SESSION['overtime_active']);
        if(isset($_SESSION['overtime_intern_id'])) unset($_SESSION['overtime_intern_id']);
        if(isset($_SESSION['overtime_start'])) unset($_SESSION['overtime_start']);
        
        // Redirect to prevent form resubmission on refresh
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }
    
    // Reset entries functionality (clears timesheet for today)
    if (isset($_POST['reset_entries']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Reset today's entries for this intern
        $_SESSION['message'] = resetEntries($conn, $intern_id);
        
        // Clear related session variables
        clearTimesheetSessionVariables();
        
        // Redirect to prevent form resubmission on refresh
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Delete student functionality (removes intern completely)
    if (isset($_POST['delete_student']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Delete the intern and their records
        $_SESSION['message'] = deleteStudent($conn, $intern_id);
        
        // Redirect to main page (no intern selected)
        header("Location: index.php");
        exit();
    }

    // Delete all timesheet records for an intern (keeps intern profile)
    if (isset($_POST['delete_all_records']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Delete all timesheet entries for this intern
        $_SESSION['message'] = deleteAllRecords($conn, $intern_id);
        
        // Redirect back to the intern's page
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Export to CSV functionality
    if (isset($_POST['export_csv']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Generate CSV data for this intern
        $csv_data = exportInternTimesheetToCSV($conn, $intern_id);
        
        // Start the download process
        outputCSVForDownload($csv_data['filename'], $csv_data['content']);
        // This function ends with exit() so no more code runs
    }
    
    // Overtime functionality
    if (isset($_POST['overtime']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $overtime_option = isset($_POST['overtime_option']) ? $_POST['overtime_option'] : 'default';
        
        // Get parameters for overtime calculation
        $params = [
            'manual_overtime_time' => isset($_POST['manual_overtime_time']) ? $_POST['manual_overtime_time'] : '',
            'overtime_hours' => isset($_POST['overtime_hours']) ? $_POST['overtime_hours'] : 0,
            'overtime_minutes' => isset($_POST['overtime_minutes']) ? $_POST['overtime_minutes'] : 0,
        ];
        
        // Start the overtime period
        $result = startOvertime($conn, $intern_id, $overtime_option, $params);
        
        // Save result message for display after redirect
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['message_type'];
        
        // If overtime was started successfully, save session variables
        if ($result['success'] && isset($result['overtime_active']) && $result['overtime_active']) {
            $_SESSION['overtime_active'] = true;
            $_SESSION['overtime_intern_id'] = $result['overtime_intern_id'];
            $_SESSION['overtime_start'] = $result['overtime_start'];
        }
        
        // Redirect to prevent form resubmission on refresh
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Pause time functionality (for breaks)
    if (isset($_POST['pause_time']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Record the start of a break
        $result = startPause($conn, $intern_id);
        
        // Save result message for display after redirect
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['message_type'];
        
        // If pause was started successfully, save session variables
        if ($result['success'] && isset($result['pause_active']) && $result['pause_active']) {
            $_SESSION['pause_active'] = true;
            $_SESSION['pause_intern_id'] = $result['pause_intern_id'];
            $_SESSION['pause_start'] = $result['pause_start'];
        }
        
        // Redirect to prevent form resubmission on refresh
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Resume time functionality (end break)
    if (isset($_POST['resume_time']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // End the current break period
        $result = resumeWork($conn, $intern_id);
        
        // Save result message for display after redirect
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['message_type'];
        
        // If work was resumed successfully, clear pause session variables
        if ($result['success']) {
            unset($_SESSION['pause_active']);
            unset($_SESSION['pause_intern_id']);
            unset($_SESSION['pause_start']);
        }
        
        // Redirect to prevent form resubmission on refresh
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Export all attendance records to CSV
    if (isset($_POST['export_all'])) {
        // Get optional date range filters
        $date_from = !empty($_POST['date_from']) ? $_POST['date_from'] : null;
        $date_to = !empty($_POST['date_to']) ? $_POST['date_to'] : null;
        
        // Generate CSV with all attendance data
        $csv_data = exportAttendanceSummaryToCSV($conn, $date_from, $date_to);
        
        // Start the download process
        outputCSVForDownload($csv_data['filename'], $csv_data['content']);
        // This function ends with exit() so no more code runs
    }
    
    // If we got here after processing a POST request but didn't handle a specific action,
    // redirect to prevent form resubmission on refresh
    if (!empty($selected_intern_id)) {
        header("Location: index.php?intern_id=" . $selected_intern_id);
    } else {
        header("Location: index.php");
    }
    exit();
}

// Get selected student name for confirmation dialogs
$selected_student_name = '';
if (!empty($selected_intern_id)) {
    $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
    $name_stmt->bindParam(':intern_id', $selected_intern_id);
    $name_stmt->execute();
    $name_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
    $selected_student_name = $name_data ? $name_data['Intern_Name'] : '';
}

// Check if overtime feature should be available for this intern
$overtime_enabled = isOvertimeEligible($conn, $selected_intern_id);
?>
