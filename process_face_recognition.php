<?php
// Start session to store form submission data
session_start();

// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include 'connection/conn.php';

// Helper function to check if time is empty (00:00:00)
function isTimeEmpty($time) {
    return $time == '00:00:00' || $time == '00:00:00.000000' || $time == null || empty($time);
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

// Helper function to update total hours for the day
function updateTotalHours($conn, $intern_id, $current_date) {
    // Get current hours
    $hours_stmt = $conn->prepare("SELECT am_hours_worked, pm_hours_worked, overtime_hours FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $hours_stmt->bindParam(':intern_id', $intern_id);
    $hours_stmt->bindParam(':current_date', $current_date);
    $hours_stmt->execute();
    $hours_data = $hours_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($hours_data) {
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
        overtime_hours = :overtime_hours
        WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $update_stmt->bindParam(':overtime_end', $overtime_end);
    $update_stmt->bindParam(':overtime_hours', $overtime_hours);
    $update_stmt->bindParam(':intern_id', $intern_id);
    $update_stmt->bindParam(':current_date', $current_date);
    
    return $update_stmt->execute();
}

// Function to determine what action should be taken for an intern
function determineAction($conn, $intern_id) {
    $current_date = date('Y-m-d');
    $current_hour = (int)date('H');
    
    // Check if the intern already has a timesheet for today
    $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    $timesheet = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($timesheet) {
        // Check current state and determine next action
        if (!isTimeEmpty($timesheet['am_timein']) && isTimeEmpty($timesheet['am_timeOut']) && $current_hour < 12) {
            return ['action' => 'out', 'session' => 'morning', 'description' => 'You will be timed-out for the morning session'];
        } 
        else if (!isTimeEmpty($timesheet['pm_timein']) && isTimeEmpty($timesheet['pm_timeout']) && $current_hour >= 12) {
            return ['action' => 'out', 'session' => 'afternoon', 'description' => 'You will be timed-out for the afternoon session'];
        }
        else if (isTimeEmpty($timesheet['am_timein']) && $current_hour < 12) {
            return ['action' => 'in', 'session' => 'morning', 'description' => 'You will be timed-in for the morning session'];
        }
        else if (isTimeEmpty($timesheet['pm_timein']) && $current_hour >= 12) {
            // Check if morning time-out is recorded if morning time-in exists
            if (!isTimeEmpty($timesheet['am_timein']) && isTimeEmpty($timesheet['am_timeOut'])) {
                return ['action' => 'error', 'session' => 'none', 'description' => 'You must record your morning time-out first'];
            } else {
                return ['action' => 'in', 'session' => 'afternoon', 'description' => 'You will be timed-in for the afternoon session'];
            }
        }
        else {
            // No appropriate action can be taken
            if (!isTimeEmpty($timesheet['pm_timeout'])) {
                return ['action' => 'complete', 'session' => 'none', 'description' => 'Your duty for today is already complete'];
            } else {
                return ['action' => 'error', 'session' => 'none', 'description' => 'No appropriate action can be taken at this time'];
            }
        }
    } else {
        // No timesheet exists, create new one
        if ($current_hour < 12) {
            return ['action' => 'in', 'session' => 'morning', 'description' => 'You will be timed-in for the morning session'];
        } else {
            return ['action' => 'in', 'session' => 'afternoon', 'description' => 'You will be timed-in for the afternoon session (half-day)'];
        }
    }
}

// Handle action determination request
if (isset($_POST['action']) && $_POST['action'] == 'determine_action' && !empty($_POST['intern_id'])) {
    $intern_id = $_POST['intern_id'];
    
    try {
        $action_info = determineAction($conn, $intern_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'action_info' => $action_info
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error determining action: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Process time in/out via AJAX
if (isset($_POST['action']) && $_POST['action'] == 'process_time' && !empty($_POST['intern_id'])) {
    $intern_id = $_POST['intern_id'];
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    $action_type = 'in'; // Default to time in
    $message = '';
    $success = false;
    
    try {
        // Check if the intern already has a timesheet for today
        $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':current_date', $current_date);
        $check_stmt->execute();
        $timesheet = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get current hour to determine if it's morning or afternoon
        $current_hour = (int)date('H');
        $current_minute = (int)date('i');
        
        if ($timesheet) {
            // Determine whether to time in or time out based on existing records
            if (!isTimeEmpty($timesheet['am_timein']) && isTimeEmpty($timesheet['am_timeOut']) && $current_hour < 12) {
                // Morning time out
                $action_type = 'out';
                $time_in = new DateTime($timesheet['am_timein']);
                $time_out = new DateTime($current_time);
                $interval = $time_in->diff($time_out);
                $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                
                $update_stmt = $conn->prepare("UPDATE timesheet SET am_timeOut = :time, am_hours_worked = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                $update_stmt->bindParam(':time', $current_time);
                $update_stmt->bindParam(':hours', $hours_worked);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                
                if ($update_stmt->execute()) {
                    // Update total hours for the day
                    updateTotalHours($conn, $intern_id, $current_date);
                    $message = "Morning time-out recorded successfully at " . date('h:i A');
                    $success = true;
                } else {
                    $message = "Failed to record morning time-out. Please try again.";
                }
            } 
            else if (!isTimeEmpty($timesheet['pm_timein']) && isTimeEmpty($timesheet['pm_timeout']) && $current_hour >= 12) {
                // Afternoon time out
                $action_type = 'out';
                $time_in = new DateTime($timesheet['pm_timein']);
                $time_out = new DateTime($current_time);
                $interval = $time_in->diff($time_out);
                $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                
                $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timeout = :time, pm_hours_worked = :hours WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                $update_stmt->bindParam(':time', $current_time);
                $update_stmt->bindParam(':hours', $hours_worked);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                
                if ($update_stmt->execute()) {
                    // Update total hours for the day
                    updateTotalHours($conn, $intern_id, $current_date);
                    $message = "Afternoon time-out recorded successfully at " . date('h:i A');
                    $success = true;
                    
                    // Check if there's an active overtime session
                    if(!isTimeEmpty($timesheet['overtime_start']) && isTimeEmpty($timesheet['overtime_end'])) {
                        // Record overtime end
                        if (recordOvertimeEnd($conn, $intern_id, $current_date, $current_time, $timesheet['overtime_start'])) {
                            $message .= " Overtime has been recorded.";
                        }
                        // Update total hours again to include overtime
                        updateTotalHours($conn, $intern_id, $current_date);
                    }
                } else {
                    $message = "Failed to record afternoon time-out. Please try again.";
                }
            }
            else if (isTimeEmpty($timesheet['am_timein']) && $current_hour < 12) {
                // Morning time in
                $action_type = 'in';
                $display_time = $current_time;
                
                // Adjust time to 8:00 AM if earlier (same logic as main.php)
                if ($current_hour < 8 || ($current_hour == 8 && $current_minute == 0)) {
                    $calc_time = '08:00:00';
                } else {
                    $calc_time = $current_time;
                }
                
                $update_stmt = $conn->prepare("UPDATE timesheet SET am_timein = :time, am_timein_display = :display_time WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                $update_stmt->bindParam(':time', $calc_time);
                $update_stmt->bindParam(':display_time', $display_time);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                
                if ($update_stmt->execute()) {
                    $message = "Morning time-in recorded successfully at " . date('h:i A');
                    $success = true;
                } else {
                    $message = "Failed to record morning time-in. Please try again.";
                }
            }
            else if (isTimeEmpty($timesheet['pm_timein']) && $current_hour >= 12) {
                // Check if morning time-out is recorded if morning time-in exists
                if (!isTimeEmpty($timesheet['am_timein']) && isTimeEmpty($timesheet['am_timeOut'])) {
                    $message = "You must record your morning time-out before timing in for the afternoon session.";
                } else {
                    // Afternoon time in
                    $action_type = 'in';
                    
                    // Apply rounding rule for afternoon time-in (same logic as main.php)
                    if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                        $pm_time = '13:00:00';
                    } else {
                        $pm_time = $current_time;
                    }
                    
                    $update_stmt = $conn->prepare("UPDATE timesheet SET pm_timein = :time WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                    $update_stmt->bindParam(':time', $pm_time);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':current_date', $current_date);
                    
                    if ($update_stmt->execute()) {
                        $message = "Afternoon time-in recorded successfully at " . date('h:i A');
                        $success = true;
                    } else {
                        $message = "Failed to record afternoon time-in. Please try again.";
                    }
                }
            }
            else {
                // No appropriate action can be taken
                if (!isTimeEmpty($timesheet['pm_timeout'])) {
                    $message = "Your duty for today is already complete.";
                } else {
                    $message = "No appropriate action can be taken at this time.";
                }
            }
        } else {
            // Create a new timesheet record
            // Get intern name and required hours
            $name_stmt = $conn->prepare("SELECT Intern_Name, Required_Hours_Rendered FROM interns WHERE Intern_id = :intern_id");
            $name_stmt->bindParam(':intern_id', $intern_id);
            $name_stmt->execute();
            $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$intern_data) {
                $message = "Intern not found in the system.";
            } else {
                $intern_name = $intern_data['Intern_Name'];
                $required_hours = $intern_data['Required_Hours_Rendered'];
                
                if ($current_hour < 12) {
                    // Morning time-in
                    $action_type = 'in';
                    $display_time = $current_time;
                    
                    // Adjust time to 8:00 AM if earlier (same logic as main.php)
                    if ($current_hour < 8 || ($current_hour == 8 && $current_minute == 0)) {
                        $calc_time = '08:00:00';
                    } else {
                        $calc_time = $current_time;
                    }
                    
                    $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timein_display, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, created_at) 
                                             VALUES (:intern_id, :intern_name, :am_timein, :display_time, '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', :current_date)");
                    $insert_stmt->bindParam(':intern_id', $intern_id);
                    $insert_stmt->bindParam(':intern_name', $intern_name);
                    $insert_stmt->bindParam(':am_timein', $calc_time);
                    $insert_stmt->bindParam(':display_time', $display_time);
                    $insert_stmt->bindParam(':required_hours', $required_hours);
                    $insert_stmt->bindParam(':current_date', $current_date);
                    
                    if ($insert_stmt->execute()) {
                        $message = "Morning time-in recorded successfully at " . date('h:i A');
                        $success = true;
                    } else {
                        $message = "Failed to create new timesheet record. Please try again.";
                    }
                } else {
                    // Afternoon time-in for a new day (half-day)
                    $action_type = 'in';
                    
                    // Apply rounding rule for afternoon time-in (same logic as main.php)
                    if ($current_hour == 12 || ($current_hour == 13 && $current_minute == 0)) {
                        $pm_time = '13:00:00';
                    } else {
                        $pm_time = $current_time;
                    }
                    
                    $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timein_display, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, created_at) 
                                             VALUES (:intern_id, :intern_name, '00:00:00', '00:00:00', '00:00:00', :pm_time, '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', :current_date)");
                    $insert_stmt->bindParam(':intern_id', $intern_id);
                    $insert_stmt->bindParam(':intern_name', $intern_name);
                    $insert_stmt->bindParam(':pm_time', $pm_time);
                    $insert_stmt->bindParam(':required_hours', $required_hours);
                    $insert_stmt->bindParam(':current_date', $current_date);
                    
                    if ($insert_stmt->execute()) {
                        $message = "Half-day attendance: Afternoon time-in recorded successfully at " . date('h:i A');
                        $success = true;
                    } else {
                        $message = "Failed to create new timesheet record. Please try again.";
                    }
                }
            }
        }
        
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        error_log("Face recognition attendance error: " . $e->getMessage());
    } catch (Exception $e) {
        $message = "System error: " . $e->getMessage();
        error_log("Face recognition system error: " . $e->getMessage());
    }
    
    // Return JSON response for Ajax
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'action' => $action_type
    ]);
    exit;
}

// Return error if not a valid request
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'Invalid request parameters'
]);
exit;
?>
