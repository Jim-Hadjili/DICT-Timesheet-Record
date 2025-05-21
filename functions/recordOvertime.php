<?php
session_start();
include '../functions/main.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data['action'] === 'record_overtime') {
        $intern_id = $_SESSION['overtime_intern_id'] ?? null;
        
        if (!$intern_id) {
            echo json_encode(['success' => false, 'message' => 'No intern selected']);
            exit;
        }
        
        $current_time = date('H:i:s');
        $today = date('Y-m-d');
        
        try {
            // Check if overtime_start and overtime_end columns exist
            $checkColumns = $conn->prepare("SHOW COLUMNS FROM timesheet LIKE 'overtime_start'");
            $checkColumns->execute();
            
            if ($checkColumns->rowCount() == 0) {
                // Add overtime columns if they don't exist
                $alterTableStmt = $conn->prepare("ALTER TABLE timesheet 
                    ADD COLUMN overtime_start TIME DEFAULT '00:00:00',
                    ADD COLUMN overtime_end TIME DEFAULT '00:00:00',
                    ADD COLUMN overtime_hours TIME DEFAULT '00:00:00'");
                $alterTableStmt->execute();
            }
            
            // Check if the intern has a record for today
            $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :today");
            $check_stmt->bindParam(':intern_id', $intern_id);
            $check_stmt->bindParam(':today', $today);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // Update existing record with overtime start
                $update_stmt = $conn->prepare("UPDATE timesheet SET overtime_start = :overtime_start WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                $update_stmt->bindParam(':overtime_start', $current_time);
                $update_stmt->bindParam(':intern_id', $intern_id);
                $update_stmt->bindParam(':today', $today);
                $update_stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Overtime started']);
            } else {
                // Get intern name and required hours
                $name_stmt = $conn->prepare("SELECT Intern_Name, Required_Hours_Rendered FROM interns WHERE Intern_id = :intern_id");
                $name_stmt->bindParam(':intern_id', $intern_id);
                $name_stmt->execute();
                $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
                $intern_name = $intern_data['Intern_Name'];
                $required_hours = $intern_data['Required_Hours_Rendered'];
                
                // Create a new timesheet record with overtime start
                $insert_stmt = $conn->prepare("INSERT INTO timesheet (intern_id, intern_name, am_timein, am_timeOut, pm_timein, pm_timeout, am_hours_worked, pm_hours_worked, required_hours_rendered, day_total_hours, total_hours_rendered, overtime_start, created_at) 
                                             VALUES (:intern_id, :intern_name, '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', '00:00:00', :required_hours, '00:00:00', '00:00:00', :overtime_start, NOW())");
                $insert_stmt->bindParam(':intern_id', $intern_id);
                $insert_stmt->bindParam(':intern_name', $intern_name);
                $insert_stmt->bindParam(':required_hours', $required_hours);
                $insert_stmt->bindParam(':overtime_start', $current_time);
                $insert_stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'New timesheet created with overtime']);
            }
            
            // Clear the session variables
            unset($_SESSION['show_overtime_modal']);
            unset($_SESSION['overtime_intern_id']);
            
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } elseif ($data['action'] === 'end_overtime') {
        $intern_id = $data['intern_id'] ?? $_SESSION['overtime_intern_id'] ?? null;
        
        if (!$intern_id) {
            echo json_encode(['success' => false, 'message' => 'No intern selected']);
            exit;
        }
        
        $current_time = date('H:i:s');
        $today = date('Y-m-d');
        
        try {
            // Get the overtime start time
            $check_stmt = $conn->prepare("SELECT overtime_start FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :today");
            $check_stmt->bindParam(':intern_id', $intern_id);
            $check_stmt->bindParam(':today', $today);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $timesheet_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
                $overtime_start = $timesheet_data['overtime_start'];
                
                if ($overtime_start != '00:00:00') {
                    // Calculate overtime hours
                    $start = new DateTime($overtime_start);
                    $end = new DateTime($current_time);
                    $interval = $start->diff($end);
                    $overtime_hours = $interval->format('%H:%I:%S');
                    
                    // Update the timesheet with overtime end and hours
                    $update_stmt = $conn->prepare("UPDATE timesheet SET overtime_end = :overtime_end, overtime_hours = :overtime_hours WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                    $update_stmt->bindParam(':overtime_end', $current_time);
                    $update_stmt->bindParam(':overtime_hours', $overtime_hours);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':today', $today);
                    $update_stmt->execute();
                    
                    echo json_encode(['success' => true, 'message' => 'Overtime ended', 'hours' => $overtime_hours]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No overtime start time recorded']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No timesheet found for today']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
