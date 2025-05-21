<?php
session_start();
header('Content-Type: application/json');

// TODO: Replace with your actual authentication/user logic
$user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);

    require_once '../includes/db.php'; // Adjust path as needed

    if ($data['action'] === 'start_overtime') {
        $now = date('H:i:s');
        $stmt = $conn->prepare("UPDATE timesheet SET overtime_start = ? WHERE record_id = ?");
        $stmt->bind_param('si', $now, $record_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($data['action'] === 'end_overtime') {
        $now = date('H:i:s');
        $stmt = $conn->prepare("SELECT overtime_start FROM timesheet WHERE record_id = ?");
        $stmt->bind_param('i', $record_id);
        $stmt->execute();
        $stmt->bind_result($overtime_start);
        $stmt->fetch();
        $stmt->close();

        if ($overtime_start && $overtime_start != '00:00:00') {
            $start = new DateTime($overtime_start);
            $end = new DateTime($now);
            $interval = $start->diff($end);
            $overtime_hours = $interval->format('%H:%I:%S');

            $stmt = $conn->prepare("UPDATE timesheet SET overtime_end = ?, overtime_hours = ? WHERE record_id = ?");
            $stmt->bind_param('ssi', $now, $overtime_hours, $record_id);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Unauthorized or bad request']);
exit;