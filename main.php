<?php 
// Start session to store form submission data
session_start();

// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include 'connection/conn.php';

// Make sure the timesheet table has the overtime_manual column
try {
    $check_column = $conn->query("SHOW COLUMNS FROM timesheet LIKE 'overtime_manual'");
    if ($check_column->rowCount() == 0) {
        // Column doesn't exist, add it
        $conn->exec("ALTER TABLE timesheet ADD COLUMN overtime_manual TINYINT(1) DEFAULT 0");
    }
} catch (PDOException $e) {
    // Silently handle error - we'll try again next time
}

// Initialize message variable
$message = ""; // Make sure this is empty by default
$selected_intern_id = isset($_GET['intern_id']) ? $_GET['intern_id'] : (isset($_POST['intern_id']) ? $_POST['intern_id'] : '');

// Check for messages in session (from redirects)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    // Clear the message from session after displaying it
    unset($_SESSION['message']);
}

// Prepare statement to get all interns
$interns_stmt = $conn->prepare("SELECT * FROM interns ORDER BY Intern_Name ASC");
$interns_stmt->execute();

// Initialize timesheet statement
$timesheet_stmt = $conn->prepare("SELECT t.*, 
                                 t.record_id as id,
                                 i.Intern_School as intern_school, 
                                 i.Required_Hours_Rendered as required_hours, 
                                 DATE(t.created_at) as render_date, 
                                 t.notes as note
                                 FROM timesheet t 
                                 JOIN interns i ON t.intern_id = i.Intern_id 
                                 WHERE t.intern_id = :intern_id 
                                 ORDER BY t.created_at DESC");

// If an intern is selected, fetch their timesheet
if (!empty($selected_intern_id)) {
    $timesheet_stmt->bindParam(':intern_id', $selected_intern_id);
    $timesheet_stmt->execute();
    
    // Get intern details for display
    $intern_details_stmt = $conn->prepare("SELECT * FROM interns WHERE Intern_id = :intern_id");
    $intern_details_stmt->bindParam(':intern_id', $selected_intern_id);
    $intern_details_stmt->execute();
    $intern_details = $intern_details_stmt->fetch(PDO::FETCH_ASSOC);

    // Get today's timesheet for the selected intern
    $today_timesheet_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = CURRENT_DATE()");
    $today_timesheet_stmt->bindParam(':intern_id', $selected_intern_id);
    $today_timesheet_stmt->execute();
    $current_timesheet = $today_timesheet_stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate total time rendered by the intern
    $total_time_stmt = $conn->prepare("SELECT SUM(TIME_TO_SEC(day_total_hours)) as total_seconds FROM timesheet WHERE intern_id = :intern_id");
    $total_time_stmt->bindParam(':intern_id', $selected_intern_id);
    $total_time_stmt->execute();
    $total_time_data = $total_time_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Convert seconds back to time format
    $total_seconds = $total_time_data['total_seconds'] ?: 0;
    $total_time_rendered = secondsToTime($total_seconds);
} else {
    // Empty result set if no intern selected
    $timesheet_stmt = $conn->prepare("SELECT 1 WHERE 0");
    $timesheet_stmt->execute();
}

// Process form submissions only on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Time In functionality
    if (isset($_POST['time_in']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $current_time = date('H:i:s');
        $current_date = date('Y-m-d');
        $current_hour = (int)date('H');
        $current_minute = (int)date('i');
        
        // Check if the intern already has a record for today
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':current_date', $current_date);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Determine if it's morning or afternoon
            if ($current_hour < 12) {
                // Morning time-in
                if (isTimeEmpty($timesheet_data['am_timein'])) {
                    // Store the actual time for display
                    $display_time = $current_time;
                    
                    // If time-in is at 7:30 AM or earlier, adjust to 8:00 AM for calculation
                    if ($current_hour < 8 || ($current_hour == 8 && $current_minute == 0)) {
                        $calc_time = '08:00:00'; // Set to 8:00 AM for calculation
                    } else {
                        $calc_time = $current_time; // Use actual time
                    }
                    
                    $update_stmt = $conn->prepare("UPDATE timesheet SET am_timein = :time, am_timein_display = :display_time WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                    $update_stmt->bindParam(':time', $calc_time);
                    $update_stmt->bindParam(':display_time', $display_time);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':current_date', $current_date);
                    $update_stmt->execute();
                    
                    $_SESSION['message'] = "Morning time-in recorded successfully at " . formatTime($display_time);
                } else {
                    $_SESSION['message'] = "Morning time-in already recorded for today.";
                }
            } else {
                // Afternoon time-in
                if (isTimeEmpty($timesheet_data['pm_timein'])) {
                    // Check if morning time-out is recorded if morning time-in exists
                    if (!isTimeEmpty($timesheet_data['am_timein']) && isTimeEmpty($timesheet_data['am_timeOut'])) {
                        $_SESSION['message'] = "You must record your morning time-out before timing in for the afternoon session.";
                    } else {
                        // Apply the rounding rule for afternoon time-in
                        // If time is between 12:00 PM and 1:00 PM, set to 1:00 PM exactly
                        if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                            $pm_time = '13:00:00'; // 1:00 PM exactly
                            $display_time = "1:00 PM";
                        } else {
                            $pm_time = $current_time;
                            $display_time = formatTime($current_time);
                        }
                        
                        $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timein = :time WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                        $update_stmt->bindParam(':time', $pm_time);
                        $update_stmt->bindParam(':intern_id', $intern_id);
                        $update_stmt->bindParam(':current_date', $current_date);
                        $update_stmt->execute();
                        
                        $_SESSION['message'] = "Afternoon time-in recorded successfully at " . $display_time;
                    }
                } else {
                    // Check if afternoon time-out is already recorded
                    if (!isTimeEmpty($timesheet_data['pm_timeout'])) {
                        $_SESSION['message'] = "Your duty for today is already complete. Please return tomorrow morning.";
                        $_SESSION['message_type'] = "info"; // Use info type for completed duty
                    } else {
                        $_SESSION['message'] = "Afternoon time-in already recorded for today.";
                        $_SESSION['message_type'] = "warning"; // Use warning type for this message
                    }
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
                // Store the actual time for display
                $display_time = $current_time;
                
                // If time-in is at 7:30 AM or earlier, adjust to 8:00 AM for calculation
                if ($current_hour < 8 || ($current_hour == 8 && $current_minute == 0)) {
                    $calc_time = '08:00:00'; // Set to 8:00 AM for calculation
                } else {
                    $calc_time = $current_time; // Use actual time
                }
                
                $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timein_display, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, created_at) 
                                             VALUES (:intern_id, :intern_name, :am_timein, :display_time, '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', :current_date)");
                $insert_stmt->bindParam(':intern_id', $intern_id);
                $insert_stmt->bindParam(':intern_name', $intern_name);
                $insert_stmt->bindParam(':am_timein', $calc_time);
                $insert_stmt->bindParam(':display_time', $display_time);
                $insert_stmt->bindParam(':required_hours', $required_hours);
                $insert_stmt->bindParam(':current_date', $current_date);
                $insert_stmt->execute();
                
                $_SESSION['message'] = "Morning time-in recorded successfully at " . formatTime($display_time);
            } else {
                // Afternoon-only attendance (half-day)
                // Create a new record with empty morning entries
                // Apply the rounding rule for afternoon time-in
                if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                    $pm_time = '13:00:00'; // 1:00 PM exactly
                    $display_time = "1:00 PM";
                } else {
                    $pm_time = $current_time;
                    $display_time = formatTime($current_time);
                }
                
                $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timein_display, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, created_at) 
                                             VALUES (:intern_id, :intern_name, '00:00:00', '00:00:00', '00:00:00', :pm_time, '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', :current_date)");
                $insert_stmt->bindParam(':intern_id', $intern_id);
                $insert_stmt->bindParam(':intern_name', $intern_name);
                $insert_stmt->bindParam(':pm_time', $pm_time);
                $insert_stmt->bindParam(':required_hours', $required_hours);
                $insert_stmt->bindParam(':current_date', $current_date);
                $insert_stmt->execute();
                
                $_SESSION['message'] = "Half-day attendance: Afternoon time-in recorded successfully at " . $display_time;
            }
        }
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Time Out functionality
    if (isset($_POST['time_out']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $current_time = date('H:i:s');
        $current_date = date('Y-m-d');
        $current_hour = (int)date('H');
        $current_minute = (int)date('i');
        
        // Check if the intern has a record for today
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':current_date', $current_date);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // First check if morning time-in exists but no time-out yet
            if (!isTimeEmpty($timesheet_data['am_timein']) && isTimeEmpty($timesheet_data['am_timeOut'])) {
                // Allow timing out at any time after morning time-in
                // This handles the case where an intern works straight through without a break
                
                // Use the actual time-in for display, but calculation time for hours calculation
                $am_timein = isset($timesheet_data['am_timein_display']) && !isTimeEmpty($timesheet_data['am_timein_display']) 
                    ? $timesheet_data['am_timein_display'] 
                    : $timesheet_data['am_timein'];
                
                // Calculate hours worked using the calculation time (not display time)
                $time_in = new DateTime($timesheet_data['am_timein']);
                $time_out = new DateTime($current_time);
                $interval = $time_in->diff($time_out);
                $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                
                $update_stmt = $conn->prepare("UPDATE timesheet SET am_timeOut = :time, am_hours_worked = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                $update_stmt->bindParam(':time', $current_time);
                $update_stmt->bindParam(':hours', $hours_worked);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                $update_stmt->execute();
                
                // Update total hours for the day
                updateTotalHours($conn, $intern_id, $current_date);
                
                $_SESSION['message'] = "Time-out recorded successfully at " . formatTime($current_time);
                $_SESSION['message_type'] = "success";
            } 
            // If afternoon time-in exists but no time-out yet
            else if (!isTimeEmpty($timesheet_data['pm_timein']) && isTimeEmpty($timesheet_data['pm_timeout'])) {
                // Calculate hours worked
                $time_in = new DateTime($timesheet_data['pm_timein']);
                $time_out = new DateTime($current_time);
                $interval = $time_in->diff($time_out);
                $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                
                $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timeout = :time, pm_hours_worked = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                $update_stmt->bindParam(':time', $current_time);
                $update_stmt->bindParam(':hours', $hours_worked);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                $update_stmt->execute();
                
                // Update total hours for the day
                updateTotalHours($conn, $intern_id, $current_date);
                
                $_SESSION['message'] = "Afternoon time-out recorded successfully at " . formatTime($current_time);
                $_SESSION['message_type'] = "success";
                
                // Check if there's an active overtime session
                if(isset($timesheet_data['overtime_start']) && !isTimeEmpty($timesheet_data['overtime_start']) && isTimeEmpty($timesheet_data['overtime_end'])) {
                    // Record overtime end and calculate overtime hours
                    recordOvertimeEnd($conn, $intern_id, $current_date, $current_time, $timesheet_data['overtime_start']);
                }
            } else {
                // Check if the intern has already completed their duty for the day
                if (!isTimeEmpty($timesheet_data['pm_timeout']) || (!isTimeEmpty($timesheet_data['am_timeOut']) && isTimeEmpty($timesheet_data['pm_timein']))) {
                    $_SESSION['message'] = "Your duty for today is already complete. Please return tomorrow morning.";
                    $_SESSION['message_type'] = "info"; // Use info type for completed duty
                } else {
                    $_SESSION['message'] = "Cannot record time-out. Make sure you have timed in first.";
                    $_SESSION['message_type'] = "error"; // Use error type for this message
                }
            }
        } else {
            $_SESSION['message'] = "No time-in record found for today. Please time in first.";
        }
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Reset entries functionality
    if (isset($_POST['reset_entries']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $current_date = date('Y-m-d');
        
        // First delete any pause history records for today
        $delete_pause_history_stmt = $conn->prepare("DELETE FROM pause_history 
            WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $delete_pause_history_stmt->bindParam(':intern_id', $intern_id);
        $delete_pause_history_stmt->bindParam(':current_date', $current_date);
        $delete_pause_history_stmt->execute();
        
        // Then reset the timesheet
        $reset_stmt = $conn->prepare("UPDATE timesheet SET 
            am_timein = '00:00:00', 
            am_timeOut = '00:00:00', 
            pm_timein = '00:00:00', 
            pm_timeout = '00:00:00', 
            am_hours_worked = '00:00:00', 
            pm_hours_worked = '00:00:00', 
            day_total_hours = '00:00:00',
            overtime_start = '00:00:00',
            overtime_end = '00:00:00',
            overtime_hours = '00:00:00',
            pause_start = '00:00:00',
            pause_end = '00:00:00',
            pause_duration = '00:00:00',
            pause_reason = NULL
            WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $reset_stmt->bindParam(':intern_id', $intern_id);
        $reset_stmt->bindParam(':current_date', $current_date);
        $reset_stmt->execute();
        
        $_SESSION['message'] = "Today's timesheet entries reset successfully.";
        
        // Also clear any session variables related to timein, overtime or pause
        if(isset($_SESSION['timein_timestamp'])) unset($_SESSION['timein_timestamp']);
        if(isset($_SESSION['timein_intern_id'])) unset($_SESSION['timein_intern_id']);
        if(isset($_SESSION['overtime_active'])) unset($_SESSION['overtime_active']);
        if(isset($_SESSION['overtime_intern_id'])) unset($_SESSION['overtime_intern_id']);
        if(isset($_SESSION['overtime_start'])) unset($_SESSION['overtime_start']);
        if(isset($_SESSION['pause_active'])) unset($_SESSION['pause_active']);
        if(isset($_SESSION['pause_intern_id'])) unset($_SESSION['pause_intern_id']);
        if(isset($_SESSION['pause_start'])) unset($_SESSION['pause_start']);
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Delete student functionality
    if (isset($_POST['delete_student']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Delete from timesheet first (foreign key constraint)
        $delete_timesheet_stmt = $conn->prepare("DELETE FROM timesheet WHERE intern_id = :intern_id");
        $delete_timesheet_stmt->bindParam(':intern_id', $intern_id);
        $delete_timesheet_stmt->execute();
        
        // Then delete from interns
        $delete_intern_stmt = $conn->prepare("DELETE FROM interns WHERE Intern_id = :intern_id");
        $delete_intern_stmt->bindParam(':intern_id', $intern_id);
        $delete_intern_stmt->execute();
        
        $_SESSION['message'] = "Student deleted successfully.";
        
        // Redirect to prevent form resubmission
        header("Location: index.php");
        exit();
    }

    // Export to CSV functionality
    if (isset($_POST['export_csv']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Get intern name for the filename
        $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
        $name_stmt->bindParam(':intern_id', $intern_id);
        $name_stmt->execute();
        $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
        $intern_name = $intern_data['Intern_Name'];
        
        // Fetch timesheet data
        $export_stmt = $conn->prepare("SELECT t.*, i.Intern_School as intern_school, DATE(NOW()) as render_date 
                                      FROM timesheet t 
                                      JOIN interns i ON t.intern_id = i.Intern_id 
                                      WHERE t.intern_id = :intern_id");
        $export_stmt->bindParam(':intern_id', $intern_id);
        $export_stmt->execute();
        
        // Create CSV content
        $filename = str_replace(' ', '_', $intern_name) . "_timesheet_" . date('Y-m-d') . ".csv";
        $csv_content = "Date,Student Name,School,AM Time In,AM Time Out,PM Time In,PM Time Out,AM Hours,PM Hours,Overtime Hours,Total Hours\n";
        
        while ($row = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
            $csv_content .= date('Y-m-d', strtotime($row['created_at'])) . ",";
            $csv_content .= $row['intern_name'] . ",";
            $csv_content .= $row['intern_school'] . ",";
            $csv_content .= formatTime($row['am_timein']) . ",";
            $csv_content .= formatTime($row['am_timeOut']) . ",";
            $csv_content .= formatTime($row['pm_timein']) . ",";
            $csv_content .= formatTime($row['pm_timeout']) . ",";
            $csv_content .= isTimeEmpty($row['am_hours_worked']) ? "-," : formatDuration($row['am_hours_worked']) . ",";
            $csv_content .= isTimeEmpty($row['pm_hours_worked']) ? "-," : formatDuration($row['pm_hours_worked']) . ",";
            $csv_content .= (isset($row['overtime_hours']) && !isTimeEmpty($row['overtime_hours'])) ? formatDuration($row['overtime_hours']) . "," : "-,";
            $csv_content .= isTimeEmpty($row['day_total_hours']) ? "-\n" : formatDuration($row['day_total_hours']) . "\n";
        }
        
        // Output CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Output CSV content
        echo $csv_content;
        exit();
    }
    
    // Delete all timesheet records for an intern
    if (isset($_POST['delete_all_records']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        
        // Get intern name for the message
        $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
        $name_stmt->bindParam(':intern_id', $intern_id);
        $name_stmt->execute();
        $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
        $intern_name = $intern_data['Intern_Name'];
        
        // Delete all timesheet records for this intern
        $delete_all_stmt = $conn->prepare("DELETE FROM timesheet WHERE intern_id = :intern_id");
        $delete_all_stmt->bindParam(':intern_id', $intern_id);
        $delete_all_stmt->execute();
        
        $_SESSION['message'] = "All timesheet records for " . $intern_name . " have been deleted.";
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Overtime functionality
    if(isset($_POST['overtime']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');
        
        // Check if there's an existing timesheet for today
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':current_date', $current_date);
        $check_stmt->execute();
        $timesheet = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if($timesheet) {
            // Check if overtime has already started
            if(!isTimeEmpty($timesheet['overtime_start']) && isTimeEmpty($timesheet['overtime_end'])) {
                $_SESSION['message'] = "Overtime has already started at " . formatTime($timesheet['overtime_start']) . ". Use the 'Time Out' button to finish recording overtime.";
                $_SESSION['message_type'] = "warning";
                header("Location: index.php?intern_id=" . $intern_id);
                exit();
            }
            
            // Check if PM timein exists but no timeout yet
            if(!isTimeEmpty($timesheet['pm_timein']) && isTimeEmpty($timesheet['pm_timeout'])) {
                // Get the overtime option selected
                $overtime_option = isset($_POST['overtime_option']) ? $_POST['overtime_option'] : 'default';
                
                // OPTION 1: Start from 5:00 PM
                if($overtime_option == 'default') {
                    $overtime_start = '17:00:00'; // 5:00 PM
                    
                    // Update the database
                    $update_stmt = $conn->prepare("UPDATE timesheet SET 
                        overtime_start = :overtime_start
                        WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                    $update_stmt->bindParam(':overtime_start', $overtime_start);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':current_date', $current_date);
                    
                    if($update_stmt->execute()) {
                        $_SESSION['message'] = "Overtime started successfully at 5:00 PM. Time out when finished to record your overtime hours.";
                        $_SESSION['message_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error starting overtime. Please try again.";
                        $_SESSION['message_type'] = "error";
                    }
                } 
                // OPTION 2: Specify custom start time
                else if($overtime_option == 'manual' && !empty($_POST['manual_overtime_time'])) {
                    $overtime_start = date('H:i:s', strtotime($_POST['manual_overtime_time']));
                    
                    // Update the database
                    $update_stmt = $conn->prepare("UPDATE timesheet SET 
                        overtime_start = :overtime_start
                        WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                    $update_stmt->bindParam(':overtime_start', $overtime_start);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':current_date', $current_date);
                    
                    if($update_stmt->execute()) {
                        $_SESSION['message'] = "Overtime started at " . formatTime($overtime_start) . ". Time out when finished to record your overtime hours.";
                        $_SESSION['message_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error starting overtime. Please try again.";
                        $_SESSION['message_type'] = "error";
                    }
                } 
                // OPTION 3: Manual hours entry
                else if($overtime_option == 'hours') {
                    $hours = isset($_POST['overtime_hours']) ? intval($_POST['overtime_hours']) : 0;
                    $minutes = isset($_POST['overtime_minutes']) ? intval($_POST['overtime_minutes']) : 0;
                    
                    if($hours == 0 && $minutes == 0) {
                        $_SESSION['message'] = "Please enter valid overtime hours and/or minutes.";
                        $_SESSION['message_type'] = "error";
                    } else {
                        // Convert to time format for storage
                        $overtime_seconds = ($hours * 3600) + ($minutes * 60);
                        $overtime_hours = secondsToTime($overtime_seconds);
                        
                        // Get current time for PM timeout
                        $current_time_for_timeout = date('H:i:s');
                        
                        // Calculate hours worked for PM session
                        $time_in = new DateTime($timesheet['pm_timein']);
                        $time_out = new DateTime($current_time_for_timeout);
                        $interval = $time_in->diff($time_out);
                        $pm_hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                        
                        // Use NULL for overtime start/end to indicate manual entry
                        $null_time = NULL;
                        
                        // Update the database with PM timeout and overtime hours
                        $update_stmt = $conn->prepare("UPDATE timesheet SET 
                            pm_timeout = :pm_timeout,
                            pm_hours_worked = :pm_hours_worked,
                            overtime_start = NULL,
                            overtime_end = NULL,
                            overtime_hours = :overtime_hours,
                            overtime_manual = 1
                            WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                        $update_stmt->bindParam(':pm_timeout', $current_time_for_timeout);
                        $update_stmt->bindParam(':pm_hours_worked', $pm_hours_worked);
                        $update_stmt->bindParam(':overtime_hours', $overtime_hours);
                        $update_stmt->bindParam(':intern_id', $intern_id);
                        $update_stmt->bindParam(':current_date', $current_date);
                        
                        if($update_stmt->execute()) {
                            // Update total hours for the day (including overtime)
                            updateTotalHours($conn, $intern_id, $current_date);
                            
                            // Format the duration for the message
                            $duration_text = '';
                            if($hours > 0) {
                                $duration_text .= $hours . ' hour' . ($hours > 1 ? 's' : '');
                            }
                            if($minutes > 0) {
                                if($hours > 0) $duration_text .= ' and ';
                                $duration_text .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                            }
                            
                            $_SESSION['message'] = "You have been timed out and overtime of {$duration_text} has been recorded successfully.";
                            $_SESSION['message_type'] = "success";
                        } else {
                            $_SESSION['message'] = "Error recording overtime. Please try again.";
                            $_SESSION['message_type'] = "error";
                        }
                    }
                } else {
                    $_SESSION['message'] = "Invalid overtime option selected.";
                    $_SESSION['message_type'] = "error";
                }
            } else if(!isTimeEmpty($timesheet['pm_timeout'])) {
                $_SESSION['message'] = "Cannot start overtime. You have already timed out for today.";
                $_SESSION['message_type'] = "error";
            } else {
                $_SESSION['message'] = "Cannot start overtime. You must time in for the afternoon session first.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "No timesheet record found for today. Please time in first.";
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Pause time functionality
    if (isset($_POST['pause_time']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $current_time = date('H:i:s');
        $current_date = date('Y-m-d');
        
        // Check if there's an active timesheet for today
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':current_date', $current_date);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if there's already an active pause
            if (!isTimeEmpty($timesheet_data['pause_start']) && isTimeEmpty($timesheet_data['pause_end'])) {
                $_SESSION['message'] = "You already have an active pause. Please resume your work before starting a new pause.";
                $_SESSION['message_type'] = "warning";
            } 
            // Check if there's an active time-in
            else if (
                (!isTimeEmpty($timesheet_data['am_timein']) && isTimeEmpty($timesheet_data['am_timeOut'])) || 
                (!isTimeEmpty($timesheet_data['pm_timein']) && isTimeEmpty($timesheet_data['pm_timeout']))
            ) {
                // Reset pause fields for a new pause
                $update_stmt = $conn->prepare("UPDATE timesheet SET 
                    pause_start = :pause_time,
                    pause_end = '00:00:00'
                    WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                $update_stmt->bindParam(':pause_time', $current_time);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                $update_stmt->execute();
                
                $_SESSION['message'] = "Time paused successfully at " . formatTime($current_time);
                $_SESSION['message_type'] = "info";
                $_SESSION['pause_active'] = true;
                $_SESSION['pause_intern_id'] = $intern_id;
                $_SESSION['pause_start'] = $current_time;
            } else {
                $_SESSION['message'] = "No active time-in found. Please time in before pausing.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "No timesheet record found for today. Please time in first.";
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Resume time functionality
    if (isset($_POST['resume_time']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $current_time = date('H:i:s');
        $current_date = date('Y-m-d');
        
        // Check if there's an active timesheet with pause for today
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':current_date', $current_date);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if there's an active pause
            if (!isTimeEmpty($timesheet_data['pause_start']) && isTimeEmpty($timesheet_data['pause_end'])) {
                // Calculate pause duration for this session
                $pause_start = new DateTime($timesheet_data['pause_start']);
                $pause_end = new DateTime($current_time);
                $interval = $pause_start->diff($pause_end);
                $current_pause_duration = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                
                // Get existing pause duration
                $existing_pause_duration = isTimeEmpty($timesheet_data['pause_duration']) ? 
                    '00:00:00' : $timesheet_data['pause_duration'];
                
                // Calculate accumulated pause duration
                $total_pause_seconds = timeToSeconds($existing_pause_duration) + timeToSeconds($current_pause_duration);
                $accumulated_pause_duration = secondsToTime($total_pause_seconds);
                
                // Record pause end time and accumulated duration
                $update_stmt = $conn->prepare("UPDATE timesheet SET 
                    pause_end = :pause_end,
                    pause_duration = :pause_duration
                    WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                $update_stmt->bindParam(':pause_end', $current_time);
                $update_stmt->bindParam(':pause_duration', $accumulated_pause_duration);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                $update_stmt->execute();
                
                // Update total hours for the day (subtracting pause duration)
                updateTotalHoursWithPause($conn, $intern_id, $current_date);
                
                // Log the pause session to history if we're using it
                try {
                    // First check if the table exists
                    $table_check = $conn->query("SHOW TABLES LIKE 'pause_history'");
                    if ($table_check->rowCount() > 0) {
                        $insert_history_stmt = $conn->prepare("INSERT INTO pause_history 
                            (timesheet_id, intern_id, pause_start, pause_end, pause_duration, pause_reason) 
                            VALUES 
                            (:timesheet_id, :intern_id, :pause_start, :pause_end, :pause_duration, :pause_reason)");
                        $insert_history_stmt->bindParam(':timesheet_id', $timesheet_data['id']);
                        $insert_history_stmt->bindParam(':intern_id', $intern_id);
                        $insert_history_stmt->bindParam(':pause_start', $timesheet_data['pause_start']);
                        $insert_history_stmt->bindParam(':pause_end', $current_time);
                        $insert_history_stmt->bindParam(':pause_duration', $current_pause_duration);
                        $insert_history_stmt->bindParam(':pause_reason', $timesheet_data['pause_reason']);
                        $insert_history_stmt->execute();
                    }
                } catch (PDOException $e) {
                    // Ignore errors if the table doesn't exist
                }
                
                $_SESSION['message'] = "Work resumed at " . formatTime($current_time) . ". Total paused time today: " . 
                    formatDuration($accumulated_pause_duration);
                $_SESSION['message_type'] = "success";
                
                // Clear pause session variables
                unset($_SESSION['pause_active']);
                unset($_SESSION['pause_intern_id']);
                unset($_SESSION['pause_start']);
            } else {
                $_SESSION['message'] = "No active pause found. Cannot resume work.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "No timesheet record found for today.";
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // Note functionality
    if (isset($_POST['note_action']) && !empty($_POST['timesheet_id']) && !empty($_POST['intern_id'])) {
        $intern_id = $_POST['intern_id'];
        $timesheet_id = $_POST['timesheet_id'];
        $action = $_POST['note_action'];
        
        // Check if the timesheet exists and belongs to the intern
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE record_id = :id AND intern_id = :intern_id");
        $check_stmt->bindParam(':id', $timesheet_id);
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Add or update note
            if ($action === 'add' || $action === 'update') {
                $note_content = $_POST['note_content'];
                
                // Make sure the note column exists
                try {
                    $check_column = $conn->query("SHOW COLUMNS FROM timesheet LIKE 'notes'");
                    if ($check_column->rowCount() == 0) {
                        // Column doesn't exist, add it
                        $conn->exec("ALTER TABLE timesheet ADD COLUMN notes TEXT");
                    }
                } catch (PDOException $e) {
                    // Silently handle error
                }
                
                // Update the note
                $update_stmt = $conn->prepare("UPDATE timesheet SET notes = :note WHERE record_id = :id");
                $update_stmt->bindParam(':note', $note_content);
                $update_stmt->bindParam(':id', $timesheet_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = $action === 'add' ? "Note added successfully." : "Note updated successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error saving note. Please try again.";
                    $_SESSION['message_type'] = "error";
                }
            } 
            // Delete note
            else if ($action === 'delete') {
                $update_stmt = $conn->prepare("UPDATE timesheet SET notes = NULL WHERE record_id = :id");
                $update_stmt->bindParam(':id', $timesheet_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = "Note deleted successfully.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error deleting note. Please try again.";
                    $_SESSION['message_type'] = "error";
                }
            }
        } else {
            $_SESSION['message'] = "Invalid timesheet record.";
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect to prevent form resubmission
        header("Location: index.php?intern_id=" . $intern_id);
        exit();
    }

    // If we get here with a POST request but no specific action was taken,
    // redirect to prevent form resubmission on refresh
    if (!empty($selected_intern_id)) {
        header("Location: index.php?intern_id=" . $selected_intern_id);
    } else {
        header("Location: index.php");
    }
    exit();
}

// Helper function to record overtime end and calculate hours
function recordOvertimeEnd($conn, $intern_id, $current_date, $end_time, $start_time) {
    // Record the overtime end time
    $overtime_end = $end_time;
    
    // Calculate overtime hours
    $start_dt = new DateTime($current_date . ' ' . $start_time);
    $end_dt = new DateTime($current_date . ' ' . $end_time);
    
    // Handle case where overtime spans midnight
    if($end_dt < $start_dt) {
        $end_dt->modify('+1 day');
    }
    
    $interval = $start_dt->diff($end_dt);
    $overtime_hours = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
    
    // Update the timesheet with overtime end and hours
    $update_stmt = $conn->prepare("UPDATE timesheet SET 
        overtime_end = :overtime_end,
        overtime_hours = :overtime_hours,
        day_total_hours = ADDTIME(day_total_hours, :overtime_hours)
        WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $update_stmt->bindParam(':overtime_end', $overtime_end);
    $update_stmt->bindParam(':overtime_hours', $overtime_hours);
    $update_stmt->bindParam(':intern_id', $intern_id);
    $update_stmt->bindParam(':current_date', $current_date);
    
    if($update_stmt->execute()) {
        // Format the duration for the message
        $hours = $interval->h;
        $minutes = $interval->i;
        
        $duration_text = '';
        if($hours > 0) {
            $duration_text .= $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        if($minutes > 0) {
            if($hours > 0) $duration_text .= ' and ';
            $duration_text .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        
        // Append to session message
        $_SESSION['message'] .= " Overtime of {$duration_text} has been recorded.";
    }
}

// Helper function to check if time is empty (00:00:00)
function isTimeEmpty($time) {
    return $time == '00:00:00' || $time == '00:00:00.000000' || $time == null;
}

// Helper function to check if overtime was manually entered
function isOvertimeManual($timesheet) {
    return isset($timesheet['overtime_manual']) && $timesheet['overtime_manual'] == 1;
}

// Helper function to format time (for clock times with AM/PM)
function formatTime($time) {
    if (isTimeEmpty($time)) {
        return '-';
    }
    
    // Convert to DateTime object
    $time_obj = new DateTime($time);
    
    // Format as 12-hour time with AM/PM
    return $time_obj->format('h:i A');
}

// Add a new function to show both actual and calculation times
function formatTimeWithNote($actual_time, $calc_time) {
    if (isTimeEmpty($actual_time)) {
        return '-';
    }
    
    $actual_formatted = formatTime($actual_time);
    
    // If the times are different, show both
    if ($actual_time != $calc_time && !isTimeEmpty($calc_time)) {
        $calc_formatted = formatTime($calc_time);
        return '<span class="text-gray-800">' . $actual_formatted . '</span> <span class="text-xs text-gray-500">(counted as ' . $calc_formatted . ')</span>';
    }
    
    return $actual_formatted;
}

// Helper function to format duration (for hours worked)
function formatDuration($duration) {
    if (isTimeEmpty($duration)) {
        return '-';
    }
    
    // Clean up the duration string to handle microseconds
    $duration = preg_replace('/\.\d+/', '', $duration);
    
    // Parse the duration components
    $parts = explode(':', $duration);
    $hours = (int)$parts[0];
    $minutes = (int)$parts[1];
    $seconds = (int)$parts[2];
    
    // Format the duration in a user-friendly way
    if ($hours > 0) {
        return sprintf('%d hr %d min', $hours, $minutes);
    } else if ($minutes > 0) {
        return sprintf('%d min %d sec', $minutes, $seconds);
    } else {
        return sprintf('%d sec', $seconds);
    }
}

// Helper function to update total hours for the day
function updateTotalHours($conn, $intern_id, $current_date) {
    // Get current hours
    $hours_stmt = $conn->prepare("SELECT am_hours_worked, pm_hours_worked, overtime_hours FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $hours_stmt->bindParam(':intern_id', $intern_id);
    $hours_stmt->bindParam(':current_date', $current_date);
    $hours_stmt->execute();
    $hours_data = $hours_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate total hours
    $am_hours = isTimeEmpty($hours_data['am_hours_worked']) ? '00:00:00' : $hours_data['am_hours_worked'];
    $pm_hours = isTimeEmpty($hours_data['pm_hours_worked']) ? '00:00:00' : $hours_data['pm_hours_worked'];
    $overtime_hours = isset($hours_data['overtime_hours']) && !isTimeEmpty($hours_data['overtime_hours']) ? $hours_data['overtime_hours'] : '00:00:00';
    
    // Convert to seconds
    $am_seconds = timeToSeconds($am_hours);
    $pm_seconds = timeToSeconds($pm_hours);
    $overtime_seconds = timeToSeconds($overtime_hours);
    $total_seconds = $am_seconds + $pm_seconds + $overtime_seconds;
    
    // Convert back to time format
    $total_hours = secondsToTime($total_seconds);
    
    // Update total hours
    $update_stmt = $conn->prepare("UPDATE timesheet SET day_total_hours = :total WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $update_stmt->bindParam(':total', $total_hours);
    $update_stmt->bindParam(':intern_id', $intern_id);
    $update_stmt->bindParam(':current_date', $current_date);
    $update_stmt->execute();
}

// Helper function to update total hours, accounting for pauses
function updateTotalHoursWithPause($conn, $intern_id, $current_date) {
    // Get current hours and pause duration
    $hours_stmt = $conn->prepare("SELECT am_hours_worked, pm_hours_worked, overtime_hours, pause_duration FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $hours_stmt->bindParam(':intern_id', $intern_id);
    $hours_stmt->bindParam(':current_date', $current_date);
    $hours_stmt->execute();
    $hours_data = $hours_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate total hours
    $am_hours = isTimeEmpty($hours_data['am_hours_worked']) ? '00:00:00' : $hours_data['am_hours_worked'];
    $pm_hours = isTimeEmpty($hours_data['pm_hours_worked']) ? '00:00:00' : $hours_data['pm_hours_worked'];
    $overtime_hours = isset($hours_data['overtime_hours']) && !isTimeEmpty($hours_data['overtime_hours']) ? $hours_data['overtime_hours'] : '00:00:00';
    $pause_duration = isTimeEmpty($hours_data['pause_duration']) ? '00:00:00' : $hours_data['pause_duration'];
    
    // Convert to seconds
    $am_seconds = timeToSeconds($am_hours);
    $pm_seconds = timeToSeconds($pm_hours);
    $overtime_seconds = timeToSeconds($overtime_hours);
    $pause_seconds = timeToSeconds($pause_duration);
    
    // Calculate total - subtract pause duration
    $total_seconds = $am_seconds + $pm_seconds + $overtime_seconds - $pause_seconds;
    $total_seconds = max(0, $total_seconds); // Ensure it doesn't go negative
    
    // Convert back to time format
    $total_hours = secondsToTime($total_seconds);
    
    // Update total hours
    $update_stmt = $conn->prepare("UPDATE timesheet SET day_total_hours = :total WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $update_stmt->bindParam(':total', $total_hours);
    $update_stmt->bindParam(':intern_id', $intern_id);
    $update_stmt->bindParam(':current_date', $current_date);
    $update_stmt->execute();
}

// Helper function to convert time to seconds
function timeToSeconds($time) {
    // Clean up the time string to handle microseconds
    $time = preg_replace('/\.\d+/', '', $time);
    
    $parts = explode(':', $time);
    return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
}

// Helper function to convert seconds to time
function secondsToTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

// Get selected student name for delete modal
$selected_student_name = '';
if (!empty($selected_intern_id)) {
    $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
    $name_stmt->bindParam(':intern_id', $selected_intern_id);
    $name_stmt->execute();
    $name_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
    $selected_student_name = $name_data ? $name_data['Intern_Name'] : '';
}

// Determine if overtime button should be enabled
$overtime_enabled = false;
$current_time = new DateTime();
$five_pm = new DateTime();
$five_pm->setTime(17, 0, 0); // 5:00 PM

// Check if current time is after 5:00 PM and intern hasn't timed out for afternoon
if($current_time >= $five_pm && 
   !empty($selected_intern_id) && 
   isset($current_timesheet) && 
   $current_timesheet && 
   !isTimeEmpty($current_timesheet['pm_timein']) && 
   isTimeEmpty($current_timesheet['pm_timeout'])) {
    $overtime_enabled = true;
}
?>
