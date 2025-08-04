<?php
/**
 * Pause Utility Functions
 * 
 * This file contains functions for handling timesheet pause/resume functionality
 */

/**
 * Start a pause for an intern
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The ID of the intern
 * @return array Result including success status and message
 */
function startPause($conn, $intern_id) {
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    $result = [
        'success' => false,
        'message' => '',
        'message_type' => 'error'
    ];
    
    // Check if there's an active timesheet for today
    $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if there's already an active pause
        if (!isTimeEmpty($timesheet_data['pause_start']) && isTimeEmpty($timesheet_data['pause_end'])) {
            $result['message'] = "You already have an active pause. Please resume your work before starting a new pause.";
            $result['message_type'] = "warning";
            return $result;
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
            
            if ($update_stmt->execute()) {
                $result['success'] = true;
                $result['message'] = "Time paused successfully at " . formatTime($current_time);
                $result['message_type'] = "info";
                $result['pause_active'] = true;
                $result['pause_intern_id'] = $intern_id;
                $result['pause_start'] = $current_time;
            } else {
                $result['message'] = "Failed to record pause time.";
            }
        } else {
            $result['message'] = "No active time-in found. Please time in before pausing.";
        }
    } else {
        $result['message'] = "No timesheet record found for today. Please time in first.";
    }
    
    return $result;
}

/**
 * Resume work after a pause
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The ID of the intern
 * @return array Result including success status and message
 */
function resumeWork($conn, $intern_id) {
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    $result = [
        'success' => false,
        'message' => '',
        'message_type' => 'error'
    ];
    
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
            
            if ($update_stmt->execute()) {
                // Update total hours for the day (subtracting pause duration)
                updateTotalHoursWithPause($conn, $intern_id, $current_date);
                
                // Log the pause session to history if we're using it
                logPauseHistory($conn, $timesheet_data, $intern_id, $current_time, $current_pause_duration);
                
                $result['success'] = true;
                $result['message'] = "Work resumed at " . formatTime($current_time) . ". Total paused time today: " . 
                    formatDuration($accumulated_pause_duration);
                $result['message_type'] = "success";
            } else {
                $result['message'] = "Failed to record work resumption.";
            }
        } else {
            $result['message'] = "No active pause found. Cannot resume work.";
        }
    } else {
        $result['message'] = "No timesheet record found for today.";
    }
    
    return $result;
}

/**
 * Log pause history to database
 * 
 * @param PDO $conn Database connection
 * @param array $timesheet_data Current timesheet data
 * @param string $intern_id The ID of the intern
 * @param string $current_time Current time
 * @param string $current_pause_duration Pause duration for this session
 * @return void
 */
function logPauseHistory($conn, $timesheet_data, $intern_id, $current_time, $current_pause_duration) {
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
        // Silently handle error if the table doesn't exist
    }
}

/**
 * Check if an intern has an active pause
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The ID of the intern
 * @return bool True if pause is active, false otherwise
 */
function hasActivePause($conn, $intern_id) {
    $current_date = date('Y-m-d');
    
    $check_stmt = $conn->prepare("SELECT * FROM timesheet 
                               WHERE intern_id = :intern_id 
                               AND DATE(created_at) = :current_date 
                               AND pause_start IS NOT NULL 
                               AND pause_start != '00:00:00'
                               AND (pause_end IS NULL OR pause_end = '00:00:00')");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    
    return $check_stmt->rowCount() > 0;
}

/**
 * Get total pause duration for an intern for today
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The ID of the intern
 * @return string Total pause duration in HH:MM:SS format
 */
function getTotalPauseDuration($conn, $intern_id) {
    $current_date = date('Y-m-d');
    
    $check_stmt = $conn->prepare("SELECT pause_duration FROM timesheet 
                               WHERE intern_id = :intern_id 
                               AND DATE(created_at) = :current_date");
    $check_stmt->bindParam(':intern_id', $intern_id);
    $check_stmt->bindParam(':current_date', $current_date);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        return isTimeEmpty($row['pause_duration']) ? '00:00:00' : $row['pause_duration'];
    }
    
    return '00:00:00';
}
?>