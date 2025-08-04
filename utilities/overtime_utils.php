<?php
/**
 * Overtime Utility Functions
 * 
 * This file contains functions for handling overtime operations in the timesheet system
 */

/**
 * Check if overtime was manually entered
 * 
 * @param array $timesheet The timesheet data
 * @return bool True if overtime was manually entered, false otherwise
 */
function isOvertimeManual($timesheet) {
    return isset($timesheet['overtime_manual']) && $timesheet['overtime_manual'] == 1;
}

/**
 * Record overtime end and calculate hours
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @param string $current_date The date in Y-m-d format
 * @param string $end_time The end time
 * @param string $start_time The start time
 * @return void
 */
function recordOvertimeEnd($conn, $intern_id, $current_date, $end_time, $start_time) {
    // Record the overtime end time
    $overtime_end = $end_time;
    
    // First, check if we have an AM session without PM session
    $check_stmt = $conn->prepare("SELECT * FROM timesheet 
                                WHERE intern_id = :intern_id 
                                AND DATE(created_at) = :current_date");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If we have AM time-in but no PM time-in and no AM time-out
    if (!isTimeEmpty($timesheet_data['am_timein']) && 
        isTimeEmpty($timesheet_data['pm_timein']) && 
        isTimeEmpty($timesheet_data['am_timeOut'])) {
        
        // Update AM time-out to be the actual end_time
        $standard_end = '17:00:00'; // 5:00 PM
        $update_am_stmt = $conn->prepare("UPDATE timesheet SET 
            am_timeOut = :end_time,
            am_standard_end = :standard_end
            WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $update_am_stmt->bindParam(':end_time', $end_time); // Actual time
        $update_am_stmt->bindParam(':standard_end', $standard_end); // Standard end time
        $update_am_stmt->bindParam(':intern_id', $intern_id);
        $update_am_stmt->bindParam(':current_date', $current_date);
        $update_am_stmt->execute();
        
        // Calculate AM hours worked (from AM time-in to standard end time, not actual end)
        $time_in = new DateTime($timesheet_data['am_timein']);
        $standard_end_dt = new DateTime($standard_end);
        $regular_interval = $time_in->diff($standard_end_dt);
        $am_hours_worked = sprintf('%02d:%02d:%02d', $regular_interval->h, $regular_interval->i, $regular_interval->s);
        
        // Update AM hours worked
        $update_am_hours_stmt = $conn->prepare("UPDATE timesheet SET 
            am_hours_worked = :am_hours_worked
            WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
        $update_am_hours_stmt->bindParam(':am_hours_worked', $am_hours_worked);
        $update_am_hours_stmt->bindParam(':intern_id', $intern_id);
        $update_am_hours_stmt->bindParam(':current_date', $current_date);
        $update_am_hours_stmt->execute();
    }
    
    // Calculate overtime hours (from overtime start to end time)
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
    
    if($update_stmt->execute()) {
        // Now update the total hours for the day to include overtime
        updateTotalHours($conn, $intern_id, $current_date);
        
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
        
        // Return message for session
        return " Overtime of {$duration_text} has been recorded.";
    }
    
    return "";
}

/**
 * Start overtime and record it in the database
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @param string $overtime_option The type of overtime (default, manual, or hours)
 * @param array $params Additional parameters for overtime
 * @return array Results containing success status and message
 */
function startOvertime($conn, $intern_id, $overtime_option = 'default', $params = []) {
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
        
        // Check if overtime was already started
        if (!isTimeEmpty($timesheet['overtime_start']) && isTimeEmpty($timesheet['overtime_end'])) {
            $result['message'] = "Overtime has already been started. Time out to complete your overtime session.";
            return $result;
        }
        
        $overtime_start = '';
        $overtime_manual = 0;
        $overtime_hours = '00:00:00';
        
        switch ($overtime_option) {
            case 'manual':
                // Option 2: Manual start time
                if (!empty($params['manual_overtime_time'])) {
                    $overtime_start = $params['manual_overtime_time'];
                } else {
                    $overtime_start = '17:00:00'; // Default to 5:00 PM if not specified
                }
                break;
                
            case 'hours':
                // Option 3: Manual hours entry
                $overtime_manual = 1;
                $overtime_start = '17:00:00'; // Default start time
                
                // Calculate hours from input
                $hours = !empty($params['overtime_hours']) ? intval($params['overtime_hours']) : 0;
                $minutes = !empty($params['overtime_minutes']) ? intval($params['overtime_minutes']) : 0;
                
                $total_seconds = ($hours * 3600) + ($minutes * 60);
                $overtime_hours = secondsToTime($total_seconds);
                break;
                
            default:
                // Option 1: Start from 5:00 PM (default)
                $overtime_start = '17:00:00';
                break;
        }
        
        // Determine session context
        $has_am_session = !isTimeEmpty($timesheet['am_timein']);
        $has_pm_session = !isTimeEmpty($timesheet['pm_timein']);
        $am_complete = !isTimeEmpty($timesheet['am_timein']) && !isTimeEmpty($timesheet['am_timeOut']);
        $pm_active = !isTimeEmpty($timesheet['pm_timein']) && isTimeEmpty($timesheet['pm_timeout']);
        
        // Record the overtime start
        $update_stmt = $conn->prepare("UPDATE timesheet SET 
            overtime_start = :overtime_start,
            overtime_manual = :overtime_manual,
            overtime_hours = :overtime_hours
            WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
            
        $update_stmt->bindParam(':overtime_start', $overtime_start);
        $update_stmt->bindParam(':overtime_manual', $overtime_manual);
        $update_stmt->bindParam(':overtime_hours', $overtime_hours);
        $update_stmt->bindParam(':intern_id', $intern_id);
        $update_stmt->bindParam(':current_date', $current_date);
        
        if($update_stmt->execute()) {
            // If manual hours were entered, also record the end time and calculate total hours
            if ($overtime_manual == 1) {
                // For manual hours, we need to create an end time based on the start time and duration
                $start_dt = new DateTime($overtime_start);
                $hours_parts = explode(':', $overtime_hours);
                $hours_to_add = intval($hours_parts[0]);
                $minutes_to_add = intval($hours_parts[1]);
                $seconds_to_add = intval($hours_parts[2]);
                
                $end_dt = clone $start_dt;
                $end_dt->add(new DateInterval("PT{$hours_to_add}H{$minutes_to_add}M{$seconds_to_add}S"));
                $overtime_end = $end_dt->format('H:i:s');
                
                // Handle AM-only session specifically for manual overtime
                if ($has_am_session && !$am_complete && !$has_pm_session) {
                    // Update AM time-out to match overtime end time for display
                    // But store standard end time for hours calculation
                    $standard_end = '17:00:00';
                    
                    // Calculate AM hours worked (based on standard end)
                    $time_in = new DateTime($timesheet['am_timein']);
                    $standard_end_dt = new DateTime($standard_end);
                    $regular_interval = $time_in->diff($standard_end_dt);
                    $am_hours_worked = sprintf('%02d:%02d:%02d', $regular_interval->h, $regular_interval->i, $regular_interval->s);
                    
                    $update_am_stmt = $conn->prepare("UPDATE timesheet SET 
                        am_timeOut = :overtime_end,
                        am_standard_end = :standard_end,
                        am_hours_worked = :am_hours_worked,
                        overtime_end = :overtime_end
                        WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                        
                    $update_am_stmt->bindParam(':overtime_end', $overtime_end); // Use the calculated end time
                    $update_am_stmt->bindParam(':standard_end', $standard_end);
                    $update_am_stmt->bindParam(':am_hours_worked', $am_hours_worked);
                    $update_am_stmt->bindParam(':intern_id', $intern_id);
                    $update_am_stmt->bindParam(':current_date', $current_date);
                    $update_am_stmt->execute();
                } else if ($pm_active) {
                    // For active PM session, update PM timeout to match overtime end time
                    $standard_end = '17:00:00';
                    
                    // Calculate PM hours worked (based on standard end)
                    $time_in = new DateTime($timesheet['pm_timein']);
                    $standard_end_dt = new DateTime($standard_end);
                    
                    if ($time_in > $standard_end_dt) {
                        $pm_hours_worked = '00:00:00';
                    } else {
                        $regular_interval = $time_in->diff($standard_end_dt);
                        $pm_hours_worked = sprintf('%02d:%02d:%02d', $regular_interval->h, $regular_interval->i, $regular_interval->s);
                    }
                    
                    $update_pm_stmt = $conn->prepare("UPDATE timesheet SET 
                        pm_timeout = :overtime_end,
                        pm_standard_end = :standard_end,
                        pm_hours_worked = :pm_hours_worked,
                        overtime_end = :overtime_end
                        WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                        
                    $update_pm_stmt->bindParam(':overtime_end', $overtime_end); // Use the calculated end time
                    $update_pm_stmt->bindParam(':standard_end', $standard_end);
                    $update_pm_stmt->bindParam(':pm_hours_worked', $pm_hours_worked);
                    $update_pm_stmt->bindParam(':intern_id', $intern_id);
                    $update_pm_stmt->bindParam(':current_date', $current_date);
                    $update_pm_stmt->execute();
                } else {
                    // For other cases, just update overtime end
                    $update_end_stmt = $conn->prepare("UPDATE timesheet SET 
                        overtime_end = :overtime_end
                        WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
                        
                    $update_end_stmt->bindParam(':overtime_end', $overtime_end);
                    $update_end_stmt->bindParam(':intern_id', $intern_id);
                    $update_end_stmt->bindParam(':current_date', $current_date);
                    $update_end_stmt->execute();
                }
                
                // Update total hours
                updateTotalHours($conn, $intern_id, $current_date);
                
                $result['message'] = "Overtime of " . formatDuration($overtime_hours) . " has been recorded.";
                $result['success'] = true;
                $result['message_type'] = "success";
            } else {
                $result['message'] = "Overtime started at " . formatTime($overtime_start) . ". Time out to complete your overtime session.";
                $result['success'] = true;
                $result['message_type'] = "success";
                $result['overtime_active'] = true;
                $result['overtime_intern_id'] = $intern_id;
                $result['overtime_start'] = $overtime_start;
            }
        } else {
            $result['message'] = "Error recording overtime. Please try again.";
        }
    } else {
        $result['message'] = "No timesheet found for today. Please time in first.";
    }
    
    return $result;
}

/**
 * Check if an intern is eligible to record overtime
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @return bool True if overtime is allowed, false otherwise
 */
function isOvertimeEligible($conn, $intern_id) {
    // Get the current date
    $current_date = date('Y-m-d');
    
    // Check if there's an existing timesheet for today
    $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $timesheet = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if overtime was already started
        if (!isTimeEmpty($timesheet['overtime_start']) && isTimeEmpty($timesheet['overtime_end'])) {
            return false; // Overtime already in progress
        }
        
        // Check if we have an active time session
        if ((!isTimeEmpty($timesheet['am_timein']) && isTimeEmpty($timesheet['am_timeOut'])) || 
            (!isTimeEmpty($timesheet['pm_timein']) && isTimeEmpty($timesheet['pm_timeout']))) {
            return true; // Intern has an active session
        }
    }
    
    return false; // No active timesheet or no active session
}
?>