<?php
session_start();
require_once '../connection/conn.php';
require_once '../functions/main.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data['action'] === 'record_overtime') {
        $intern_id = $data['intern_id'] ?? null;

        if (!$intern_id) {
            echo json_encode(['success' => false, 'message' => 'No intern selected']);
            exit;
        }

        $current_time = date('H:i:s');
        $today = date('Y-m-d');

        try {
            // Check if the intern has a record for today
            $check_stmt = $conn->prepare("SELECT * FROM timesheet WHERE intern_id = :intern_id AND DATE(created_at) = :today");
            $check_stmt->bindParam(':intern_id', $intern_id);
            $check_stmt->bindParam(':today', $today);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
                if ($row['overtime_start'] == '00:00:00' || empty($row['overtime_start'])) {
                    $update_stmt = $conn->prepare("UPDATE timesheet SET overtime_start = :start WHERE intern_id = :intern_id AND DATE(created_at) = :today");
                    $update_stmt->bindParam(':start', $current_time);
                    $update_stmt->bindParam(':intern_id', $intern_id);
                    $update_stmt->bindParam(':today', $today);
                    $update_stmt->execute();

                    $_SESSION['message'] = "Overtime started at " . date('h:i A');
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Overtime already started']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No timesheet record for today']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
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
