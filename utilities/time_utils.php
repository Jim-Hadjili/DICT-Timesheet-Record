<?php
/**
 * Time Utility Functions
 * 
 * This file contains functions for handling time calculations and formatting
 */

/**
 * Check if time is empty (00:00:00)
 * 
 * @param string $time The time to check
 * @return bool True if time is empty, false otherwise
 */
function isTimeEmpty($time) {
    return $time == '00:00:00' || $time == '00:00:00.000000' || $time == null;
}

/**
 * Format time for display (for clock times with AM/PM)
 * 
 * @param string $time The time to format
 * @return string Formatted time or dash if empty
 */
function formatTime($time) {
    if (isTimeEmpty($time)) {
        return '-';
    }
    
    // Convert to DateTime object
    $time_obj = new DateTime($time);
    
    // Format as 12-hour time with AM/PM
    return $time_obj->format('h:i A');
}

/**
 * Format time with note about calculation time
 * 
 * @param string $actual_time The actual recorded time
 * @param string $calc_time The time used for calculation
 * @return string Formatted time with note if times differ
 */
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

/**
 * Format duration for display (for hours worked)
 * 
 * @param string $duration The duration in HH:MM:SS format
 * @return string Formatted duration or dash if empty
 */
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

/**
 * Convert time string to seconds
 * 
 * @param string $time Time in HH:MM:SS format
 * @return int Total seconds
 */
function timeToSeconds($time) {
    // Clean up the time string to handle microseconds
    $time = preg_replace('/\.\d+/', '', $time);
    
    $parts = explode(':', $time);
    return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
}

/**
 * Convert seconds to time string
 * 
 * @param int $seconds Total seconds
 * @return string Time in HH:MM:SS format
 */
function secondsToTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

/**
 * Update total hours for an intern for a specific date
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @param string $current_date The date in Y-m-d format
 * @return void
 */
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

/**
 * Update total hours accounting for pauses
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The intern ID
 * @param string $current_date The date in Y-m-d format
 * @return void
 */
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
?>