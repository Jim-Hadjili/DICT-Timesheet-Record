<?php
// Common functions for handling timesheet photos

/**
 * Save a pending photo to the database once record_id is available
 *
 * @param PDO $conn Database connection
 * @param int $intern_id ID of the intern
 * @param int $record_id ID of the timesheet record
 * @return bool True on success, false on failure
 */
function save_pending_photo($conn, $intern_id, $record_id) {
    if (!isset($_SESSION['pending_photo']) || 
        $_SESSION['pending_photo']['intern_id'] != $intern_id ||
        date('Y-m-d', strtotime($_SESSION['pending_photo']['created_at'])) != date('Y-m-d')) {
        return false;
    }
    
    try {
        $insert_stmt = $conn->prepare("INSERT INTO timesheet_photos (intern_id, record_id, photo_path, photo_type) 
                                      VALUES (:intern_id, :record_id, :photo_path, :photo_type)");
        $insert_stmt->bindParam(':intern_id', $_SESSION['pending_photo']['intern_id']);
        $insert_stmt->bindParam(':record_id', $record_id);
        $insert_stmt->bindParam(':photo_path', $_SESSION['pending_photo']['filepath']);
        $insert_stmt->bindParam(':photo_type', $_SESSION['pending_photo']['photo_type']);
        $result = $insert_stmt->execute();
        
        // Clear the pending photo
        unset($_SESSION['pending_photo']);
        
        return $result;
    } catch (Exception $e) {
        error_log("Error saving pending photo: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all photos for a specific timesheet record
 *
 * @param PDO $conn Database connection
 * @param int $record_id ID of the timesheet record
 * @return array Array of photo records
 */
function get_timesheet_photos($conn, $record_id) {
    try {
        $stmt = $conn->prepare("SELECT id, photo_path, photo_type, created_at 
                              FROM timesheet_photos 
                              WHERE record_id = :record_id 
                              ORDER BY created_at ASC");
        $stmt->bindParam(':record_id', $record_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error retrieving photos: " . $e->getMessage());
        return [];
    }
}
?>