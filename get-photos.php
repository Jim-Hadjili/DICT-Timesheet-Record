<?php
// Start user session to track logged-in status
session_start();

// Set Philippines timezone for consistent timestamp handling
date_default_timezone_set('Asia/Manila');

// Get database connection
include 'connection/conn.php';

/**
 * Formats time values for display
 * 
 * Converts database time format to readable format or shows dash if empty
 */
function formatTime($time) {
    if (empty($time) || $time == '00:00:00' || $time == '00:00:00.000000') {
        return '-';
    }
    
    $time_obj = new DateTime($time);
    
    return $time_obj->format('h:i A');
}

/**
 * Converts photo type codes into human-readable labels
 */
function getPhotoTypeLabel($type) {
    switch($type) {
        case 'morning_time_in':
            return 'Morning Time-In Photo';
        case 'morning_time_out':
            return 'Morning Time-Out Photo';
        case 'afternoon_time_in':
            return 'Afternoon Time-In Photo';
        case 'afternoon_time_out':
            return 'Afternoon Time-Out Photo';
        default:
            return 'Time Photo';
    }
}

// Setup default response structure
$response = [
    'success' => false,
    'photos' => [],
    'message' => 'No photos found'
];

try {
    // Get request parameters with sensible defaults
    $record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;
    $intern_id = isset($_GET['intern_id']) ? intval($_GET['intern_id']) : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // Method 1: Look up photos by specific timesheet record
    if ($record_id > 0) {
        $sql = "SELECT tp.*, DATE_FORMAT(tp.created_at, '%h:%i %p') as photo_time 
                FROM timesheet_photos tp 
                WHERE tp.record_id = :record_id 
                ORDER BY tp.created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':record_id', $record_id);
    } 
    // Method 2: Look up photos by intern ID and date
    else if ($intern_id > 0 && !empty($date)) {
        $sql = "SELECT tp.*, DATE_FORMAT(tp.created_at, '%h:%i %p') as photo_time 
                FROM timesheet_photos tp 
                JOIN timesheet t ON tp.record_id = t.record_id 
                WHERE tp.intern_id = :intern_id AND DATE(t.created_at) = :date 
                ORDER BY tp.created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':intern_id', $intern_id);
        $stmt->bindParam(':date', $date);
    } else {
        // Neither valid search method provided
        throw new Exception('Invalid parameters');
    }
    
    $stmt->execute();
    
    // If we found matching photos, build the response
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Photos found';
        
        // Add each photo to the response array with relevant details
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $response['photos'][] = [
                'id' => $row['id'],
                'path' => $row['photo_path'],
                'type' => $row['photo_type'],
                'label' => getPhotoTypeLabel($row['photo_type']),
                'time' => $row['photo_time']
            ];
        }
    }
} catch (Exception $e) {
    // Handle any errors that occurred during processing
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Send the JSON response to the client
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>