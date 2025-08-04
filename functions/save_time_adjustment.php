<?php
session_start();
include '../connection/conn.php';

header('Content-Type: application/json');

// Master PIN constant
define('MASTER_PIN', '0009');

// Function to check if time is empty
function isTimeEmpty($time) {
    return $time == '00:00:00' || $time == '00:00:00.000000' || $time == null;
}

// Function to convert time to seconds
function timeToSeconds($time) {
    // Clean up the time string to handle microseconds
    $time = preg_replace('/\.\d+/', '', $time);
    
    $parts = explode(':', $time);
    return ($parts[0] * 3600) + ($parts[1] * 60) + ($parts[2] ?? 0);
}

// Function to convert seconds to time
function secondsToTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

// Verify authorization
$pin = $_POST['supervisor_pin'] ?? '';
$pin_type = $_POST['pin_type'] ?? '';

// Get the required data
$record_id = $_POST['record_id'] ?? '';
$time_field = $_POST['time_field'] ?? '';
$intern_id = $_POST['intern_id'] ?? '';
$new_time = $_POST['new_time'] ?? '';
$record_date = $_POST['record_date'] ?? '';

// Use a default reason since we removed the user input field
$adjustment_reason = $_POST['adjustment_reason'] ?? 'Time adjustment by supervisor';

// Validate inputs
if (empty($record_id) || empty($time_field) || empty($intern_id) || empty($record_date)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Format the new time to include seconds
$formatted_new_time = empty($new_time) ? '00:00:00' : $new_time . ':00';

try {
    // Check authorization
    $is_authorized = false;
    $auth_type = '';
    
    // Check if there's a custom PIN set
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_pin'");
    $stmt->execute();
    
    $custom_pin_exists = $stmt->rowCount() > 0;
    
    if ($custom_pin_exists) {
        $supervisor_pin = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'];
        
        // If custom PIN exists, only it can be used for regular edits
        if ($pin === $supervisor_pin) {
            $is_authorized = true;
            $auth_type = 'supervisor';
        }
    } else {
        // If no custom PIN, master PIN can be used directly
        if ($pin === MASTER_PIN) {
            $is_authorized = true;
            $auth_type = 'master';
        }
    }
    
    if (!$is_authorized) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access. Invalid PIN.']);
        exit;
    }

    // Begin transaction
    $conn->beginTransaction();

    // Get the current time value
    $previous_value_stmt = $conn->prepare("SELECT $time_field FROM timesheet WHERE record_id = :record_id");
    $previous_value_stmt->bindParam(':record_id', $record_id);
    $previous_value_stmt->execute();
    $previous_value = $previous_value_stmt->fetch(PDO::FETCH_ASSOC)[$time_field];

    // Step 1: Update the specific time field
    $field_update_query = "UPDATE timesheet SET {$time_field} = :new_time WHERE record_id = :record_id";
    $update_stmt = $conn->prepare($field_update_query);
    $update_stmt->bindParam(':new_time', $formatted_new_time);
    $update_stmt->bindParam(':record_id', $record_id);
    $update_stmt->execute();

    // Step 2: Get the updated record
    $record_stmt = $conn->prepare("SELECT * FROM timesheet WHERE record_id = :record_id");
    $record_stmt->bindParam(':record_id', $record_id);
    $record_stmt->execute();
    $timesheet = $record_stmt->fetch(PDO::FETCH_ASSOC);

    // Step 3: Recalculate hours worked
    if ($time_field === 'am_timein' || $time_field === 'am_timeOut') {
        // Recalculate morning hours
        if (!isTimeEmpty($timesheet['am_timein']) && !isTimeEmpty($timesheet['am_timeOut'])) {
            $time_in = new DateTime($timesheet['am_timein']);
            $time_out = new DateTime($timesheet['am_timeOut']);
            
            // Make sure time_out is after time_in
            if ($time_out < $time_in) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'AM Time Out cannot be earlier than AM Time In']);
                exit;
            }
            
            $interval = $time_in->diff($time_out);
            $am_hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
            
            // Update AM hours worked
            $am_update_stmt = $conn->prepare("UPDATE timesheet SET am_hours_worked = :hours WHERE record_id = :record_id");
            $am_update_stmt->bindParam(':hours', $am_hours_worked);
            $am_update_stmt->bindParam(':record_id', $record_id);
            $am_update_stmt->execute();
            
            // Update the timesheet object with new calculation
            $timesheet['am_hours_worked'] = $am_hours_worked;
        }
    }
    
    if ($time_field === 'pm_timein' || $time_field === 'pm_timeout') {
        // Recalculate afternoon hours
        if (!isTimeEmpty($timesheet['pm_timein']) && !isTimeEmpty($timesheet['pm_timeout'])) {
            $time_in = new DateTime($timesheet['pm_timein']);
            $time_out = new DateTime($timesheet['pm_timeout']);
            
            // Make sure time_out is after time_in
            if ($time_out < $time_in) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'PM Time Out cannot be earlier than PM Time In']);
                exit;
            }
            
            $interval = $time_in->diff($time_out);
            $pm_hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
            
            // Update PM hours worked
            $pm_update_stmt = $conn->prepare("UPDATE timesheet SET pm_hours_worked = :hours WHERE record_id = :record_id");
            $pm_update_stmt->bindParam(':hours', $pm_hours_worked);
            $pm_update_stmt->bindParam(':record_id', $record_id);
            $pm_update_stmt->execute();
            
            // Update the timesheet object with new calculation
            $timesheet['pm_hours_worked'] = $pm_hours_worked;
        }
    }
    
    if ($time_field === 'overtime_start' || $time_field === 'overtime_end') {
        // Recalculate overtime hours
        if (!isTimeEmpty($timesheet['overtime_start']) && !isTimeEmpty($timesheet['overtime_end'])) {
            $time_in = new DateTime($timesheet['overtime_start']);
            $time_out = new DateTime($timesheet['overtime_end']);
            
            // Handle case where overtime might span midnight
            if ($time_out < $time_in) {
                $time_out->modify('+1 day');
            }
            
            $interval = $time_in->diff($time_out);
            $ot_hours_worked = sprintf('%02d:%02d:%02d', $interval->h, $interval->i, $interval->s);
            
            // Update overtime hours
            $ot_update_stmt = $conn->prepare("UPDATE timesheet SET overtime_hours = :hours, overtime_manual = 1 WHERE record_id = :record_id");
            $ot_update_stmt->bindParam(':hours', $ot_hours_worked);
            $ot_update_stmt->bindParam(':record_id', $record_id);
            $ot_update_stmt->execute();
            
            // Update the timesheet object with new calculation
            $timesheet['overtime_hours'] = $ot_hours_worked;
        }
    }

    // Step 4: Recalculate day total hours (combine AM, PM and overtime hours)
    $am_seconds = !isTimeEmpty($timesheet['am_hours_worked']) ? timeToSeconds($timesheet['am_hours_worked']) : 0;
    $pm_seconds = !isTimeEmpty($timesheet['pm_hours_worked']) ? timeToSeconds($timesheet['pm_hours_worked']) : 0;
    $ot_seconds = isset($timesheet['overtime_hours']) && !isTimeEmpty($timesheet['overtime_hours']) ? 
                  timeToSeconds($timesheet['overtime_hours']) : 0;
    
    // Subtract pause time if present
    $pause_seconds = 0;
    if (isset($timesheet['pause_duration']) && !isTimeEmpty($timesheet['pause_duration'])) {
        $pause_seconds = timeToSeconds($timesheet['pause_duration']);
    }
    
    // Calculate total (ensuring it doesn't go negative)
    $total_seconds = max(0, $am_seconds + $pm_seconds + $ot_seconds - $pause_seconds);
    $total_hours = secondsToTime($total_seconds);
    
    // Update total hours
    $total_update_stmt = $conn->prepare("UPDATE timesheet SET day_total_hours = :hours WHERE record_id = :record_id");
    $total_update_stmt->bindParam(':hours', $total_hours);
    $total_update_stmt->bindParam(':record_id', $record_id);
    $total_update_stmt->execute();

    // Step 5: Log the adjustment
    // Check if we have an adjustment log table; create it if not
    $table_check = $conn->query("SHOW TABLES LIKE 'time_adjustments'");
    if ($table_check->rowCount() == 0) {
        // Create the time adjustments table
        $conn->exec("CREATE TABLE time_adjustments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            record_id INT NOT NULL,
            intern_id INT NOT NULL,
            time_field VARCHAR(50) NOT NULL,
            previous_value TIME,
            new_value TIME NOT NULL,
            adjusted_by VARCHAR(50),
            adjustment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Get the username from session or use auth type as default
    $adjusted_by = isset($_SESSION['username']) ? $_SESSION['username'] : 'Supervisor';
    
    // Insert into adjustment log
    $log_stmt = $conn->prepare("INSERT INTO time_adjustments 
        (record_id, intern_id, time_field, previous_value, new_value, adjusted_by) 
        VALUES (:record_id, :intern_id, :time_field, :previous_value, :new_value, :adjusted_by)");
    
    $log_stmt->bindParam(':record_id', $record_id);
    $log_stmt->bindParam(':intern_id', $intern_id);
    $log_stmt->bindParam(':time_field', $time_field);
    $log_stmt->bindParam(':previous_value', $previous_value);
    $log_stmt->bindParam(':new_value', $formatted_new_time);
    $log_stmt->bindParam(':adjusted_by', $adjusted_by);
    $log_stmt->execute();

    // Commit all changes
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Time adjustment saved successfully']);

} catch (PDOException $e) {
    if ($conn) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error saving adjustment: ' . $e->getMessage()]);
}