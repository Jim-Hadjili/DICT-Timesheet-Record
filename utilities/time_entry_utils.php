<?php
/**
 * Time Entry Utility Functions
 * 
 * This file contains functions for handling time in and time out operations
 */

/**
 * Record a time in for an intern
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @return array Result with success status and message
 */
function recordTimeIn($conn, $intern_id) {
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    $current_hour = (int)date('H');
    $current_minute = (int)date('i');
    
    $result = [
        'success' => false,
        'message' => '',
        'message_type' => 'error'
    ];
    
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
                
                if($update_stmt->execute()) {
                    $result['success'] = true;
                    $result['message'] = "Morning time-in recorded successfully at " . formatTime($display_time);
                    $result['message_type'] = "success";
                } else {
                    $result['message'] = "Error recording time in.";
                }
            } else {
                $result['message'] = "Morning time-in already recorded for today.";
                $result['message_type'] = "warning";
            }
        } else {
            // Afternoon time-in
            if (isTimeEmpty($timesheet_data['pm_timein'])) {
                // Check if morning time-out is recorded if morning time-in exists
                if (!isTimeEmpty($timesheet_data['am_timein']) && isTimeEmpty($timesheet_data['am_timeOut'])) {
                    $result['message'] = "You must record your morning time-out before timing in for the afternoon session.";
                    $result['message_type'] = "warning";
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
                    
                    if($update_stmt->execute()) {
                        $result['success'] = true;
                        $result['message'] = "Afternoon time-in recorded successfully at " . $display_time;
                        $result['message_type'] = "success";
                    } else {
                        $result['message'] = "Error recording time in.";
                    }
                }
            } else {
                // Check if afternoon time-out is already recorded
                if (!isTimeEmpty($timesheet_data['pm_timeout'])) {
                    $result['message'] = "Your duty for today is already complete. Please return tomorrow morning.";
                    $result['message_type'] = "info"; // Use info type for completed duty
                } else {
                    $result['message'] = "Afternoon time-in already recorded for today.";
                    $result['message_type'] = "warning"; // Use warning type for this message
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
            
            if($insert_stmt->execute()) {
                $result['success'] = true;
                $result['message'] = "Morning time-in recorded successfully at " . formatTime($display_time);
                $result['message_type'] = "success";
                $result['new_record'] = true;
            } else {
                $result['message'] = "Error recording time in.";
            }
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
            
            if($insert_stmt->execute()) {
                $result['success'] = true;
                $result['message'] = "Half-day attendance: Afternoon time-in recorded successfully at " . $display_time;
                $result['message_type'] = "success";
                $result['new_record'] = true;
            } else {
                $result['message'] = "Error recording time in.";
            }
        }
    }
    
    return $result;
}

/**
 * Record a time out for an intern
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @return array Result with success status and message
 */
function recordTimeOut($conn, $intern_id) {
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    
    $result = [
        'success' => false,
        'message' => '',
        'message_type' => 'error'
    ];
    
    // Check if there's an existing timesheet for today
    $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $timesheet = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Handle case: AM time-in with no AM time-out (regardless of PM status)
        if (!isTimeEmpty($timesheet['am_timein']) && isTimeEmpty($timesheet['am_timeOut'])) {
            $has_overtime_started = !isTimeEmpty($timesheet['overtime_start']);
            $standard_end = '17:00:00'; // 5:00 PM - standard end of work day
            
            if ($has_overtime_started) {
                // 1. Calculate regular hours (from AM time-in to 5:00 PM standard end)
                $time_in = new DateTime($timesheet['am_timein']);
                $standard_end_dt = new DateTime($standard_end);
                $regular_interval = $time_in->diff($standard_end_dt);
                $am_hours_worked = sprintf('%02d:%02d:%02d', $regular_interval->h, $regular_interval->i, $regular_interval->s);
                
                // 2. Record overtime hours (from overtime start to current time)
                $overtime_end = $current_time;
                $overtime_start_dt = new DateTime($timesheet['overtime_start']);
                $overtime_end_dt = new DateTime($overtime_end);
                $overtime_interval = $overtime_start_dt->diff($overtime_end_dt);
                $overtime_hours = sprintf('%02d:%02d:%02d', $overtime_interval->h, $overtime_interval->i, $overtime_interval->s);
                
                // 3. Update the timesheet
                $update_stmt = $conn->prepare("UPDATE timesheet SET 
                    am_timeOut = :actual_time_out,
                    am_standard_end = :standard_end,
                    am_hours_worked = :am_hours_worked,
                    overtime_end = :overtime_end,
                    overtime_hours = :overtime_hours
                    WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                    
                $update_stmt->bindParam(':actual_time_out', $current_time); // Use current time instead of standard end
                $update_stmt->bindParam(':standard_end', $standard_end);
                $update_stmt->bindParam(':am_hours_worked', $am_hours_worked);
                $update_stmt->bindParam(':overtime_end', $overtime_end);
                $update_stmt->bindParam(':overtime_hours', $overtime_hours);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                
                if ($update_stmt->execute()) {
                    // Update total hours
                    updateTotalHours($conn, $intern_id, $current_date);
                    
                    $result['success'] = true;
                    $result['message'] = "Time out recorded successfully at " . formatTime($current_time) . 
                        ". Regular hours: " . formatDuration($am_hours_worked) . 
                        ", Overtime: " . formatDuration($overtime_hours);
                    $result['message_type'] = "success";
                } else {
                    $result['message'] = "Error recording time out. Please try again.";
                    $result['message_type'] = "error";
                }
            } else {
                // No overtime - regular AM time out
                // Calculate hours worked
                $time_in = new DateTime($timesheet['am_timein']);
                $time_out = new DateTime($current_time);
                $interval = $time_in->diff($time_out);
                $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                
                // Update the timesheet
                $update_stmt = $conn->prepare("UPDATE timesheet SET 
                    am_timeOut = :time_out,
                    am_hours_worked = :hours_worked
                    WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                    
                $update_stmt->bindParam(':time_out', $current_time);
                $update_stmt->bindParam(':hours_worked', $hours_worked);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                
                if ($update_stmt->execute()) {
                    // Update total hours
                    updateTotalHours($conn, $intern_id, $current_date);
                    
                    $result['success'] = true;
                    $result['message'] = "Morning time-out recorded successfully at " . formatTime($current_time) . 
                        ". Total hours: " . formatDuration($hours_worked);
                    $result['message_type'] = "success";
                } else {
                    $result['message'] = "Error recording time out. Please try again.";
                    $result['message_type'] = "error";
                }
            }
        } 
        // Handle regular PM time-out with potential overtime
        else if (!isTimeEmpty($timesheet['pm_timein']) && isTimeEmpty($timesheet['pm_timeout'])) {
            $has_overtime_started = !isTimeEmpty($timesheet['overtime_start']);
            
            if ($has_overtime_started) {
                $overtime_start = $timesheet['overtime_start'];
                $standard_end = '17:00:00'; // 5:00 PM - standard end of work day
                
                // 1. Calculate regular PM hours (from PM time-in to 5:00 PM standard end)
                $time_in = new DateTime($timesheet['pm_timein']);
                $standard_end_dt = new DateTime($standard_end);
                
                // Handle case where PM time-in might be after standard end time
                if ($time_in > $standard_end_dt) {
                    $pm_hours_worked = '00:00:00';
                } else {
                    $regular_interval = $time_in->diff($standard_end_dt);
                    $pm_hours_worked = sprintf('%02d:%02d:%02d', $regular_interval->h, $regular_interval->i, $regular_interval->s);
                }
                
                // 2. Record overtime hours (from overtime start to current time)
                $overtime_end = $current_time;
                $overtime_start_dt = new DateTime($overtime_start);
                $overtime_end_dt = new DateTime($overtime_end);
                $overtime_interval = $overtime_start_dt->diff($overtime_end_dt);
                $overtime_hours = sprintf('%02d:%02d:%02d', $overtime_interval->h, $overtime_interval->i, $overtime_interval->s);
                
                // 3. Update the timesheet with PM time-out
                $update_stmt = $conn->prepare("UPDATE timesheet SET 
                    pm_timeout = :actual_time_out, 
                    pm_standard_end = :standard_end,
                    pm_hours_worked = :pm_hours_worked,
                    overtime_end = :overtime_end,
                    overtime_hours = :overtime_hours
                    WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                    
                $update_stmt->bindParam(':actual_time_out', $current_time); // Use current time instead of standard end
                $update_stmt->bindParam(':standard_end', $standard_end);
                $update_stmt->bindParam(':pm_hours_worked', $pm_hours_worked);
                $update_stmt->bindParam(':overtime_end', $overtime_end);
                $update_stmt->bindParam(':overtime_hours', $overtime_hours);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                
                if ($update_stmt->execute()) {
                    // Update total hours
                    updateTotalHours($conn, $intern_id, $current_date);
                    
                    $result['success'] = true;
                    $result['message'] = "Time out recorded successfully at " . formatTime($current_time) . 
                        ". Regular hours: " . formatDuration($pm_hours_worked) . 
                        ", Overtime: " . formatDuration($overtime_hours);
                    $result['message_type'] = "success";
                } else {
                    $result['message'] = "Error recording time out. Please try again.";
                    $result['message_type'] = "error";
                }
            } else {
                // No overtime - regular PM time out
                $time_in = new DateTime($timesheet['pm_timein']);
                $time_out = new DateTime($current_time);
                $interval = $time_in->diff($time_out);
                $hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
                
                // Update the timesheet
                $update_stmt = $conn->prepare("UPDATE timesheet SET 
                    pm_timeout = :time_out,
                    pm_hours_worked = :hours_worked
                    WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                    
                $update_stmt->bindParam(':time_out', $current_time);
                $update_stmt->bindParam(':hours_worked', $hours_worked);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':current_date', $current_date);
                
                if ($update_stmt->execute()) {
                    // Update total hours
                    updateTotalHours($conn, $intern_id, $current_date);
                    
                    $result['success'] = true;
                    $result['message'] = "Afternoon time-out recorded successfully at " . formatTime($current_time) . 
                        ". PM hours: " . formatDuration($hours_worked);
                    $result['message_type'] = "success";
                } else {
                    $result['message'] = "Error recording time out. Please try again.";
                    $result['message_type'] = "error";
                }
            }
        } else {
            // Other cases - handle as before
            $result['message'] = "Cannot record time-out. Make sure you have timed in first.";
            $result['message_type'] = "error";
        }
    } else {
        $result['message'] = "No time in record found for today. Please time in first.";
        $result['message_type'] = "error";
    }
    
    return $result;
}

/**
 * Check if an intern is currently timed in
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @return bool True if the intern is timed in, false otherwise
 */
function isTimedIn($conn, $intern_id) {
    $current_date = date('Y-m-d');
    
    $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $timesheet = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if morning session is active
        if (!isTimeEmpty($timesheet['am_timein']) && isTimeEmpty($timesheet['am_timeOut'])) {
            return true;
        }
        
        // Check if afternoon session is active
        if (!isTimeEmpty($timesheet['pm_timein']) && isTimeEmpty($timesheet['pm_timeout'])) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get the current time session for an intern (morning or afternoon)
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @return string|null 'morning', 'afternoon', or null if not timed in
 */
function getCurrentTimeSession($conn, $intern_id) {
    $current_date = date('Y-m-d');
    
    $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $timesheet = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if morning session is active
        if (!isTimeEmpty($timesheet['am_timein']) && isTimeEmpty($timesheet['am_timeOut'])) {
            return 'morning';
        }
        
        // Check if afternoon session is active
        if (!isTimeEmpty($timesheet['pm_timein']) && isTimeEmpty($timesheet['pm_timeout'])) {
            return 'afternoon';
        }
    }
    
    return null;
}
?>