<?php
/**
 * Reset Entries Utility Functions
 * 
 * This file contains functions to reset timesheet entries and clear related session variables
 */

/**
 * Resets all timesheet entries for an intern for the current day
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The ID of the intern
 * @return string Success message
 */
function resetEntries($conn, $intern_id) {
    $current_date = date('Y-m-d');
    
    // Get the record ID for today's entry to find associated photos
    $record_id_stmt = $conn->prepare("SELECT record_id FROM timesheet 
                                 WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $record_id_stmt->bindParam(':intern_id', $intern_id);
    $record_id_stmt->bindParam(':current_date', $current_date);
    $record_id_stmt->execute();
    
    if ($record = $record_id_stmt->fetch(PDO::FETCH_ASSOC)) {
        $record_id = $record['record_id'];
        
        // Get the list of photos to delete
        $photos_stmt = $conn->prepare("SELECT photo_path FROM timesheet_photos 
                                  WHERE record_id = :record_id");
        $photos_stmt->bindParam(':record_id', $record_id);
        $photos_stmt->execute();
        
        // Delete each photo file
        while ($photo = $photos_stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($photo['photo_path']) && file_exists($photo['photo_path'])) {
                unlink($photo['photo_path']);
            }
        }
        
        // Delete photo records from database
        $delete_photos_stmt = $conn->prepare("DELETE FROM timesheet_photos 
                                        WHERE record_id = :record_id");
        $delete_photos_stmt->bindParam(':record_id', $record_id);
        $delete_photos_stmt->execute();
    }
    
    // First delete any pause history records for today
    $delete_pause_history_stmt = $conn->prepare("DELETE FROM pause_history 
        WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $delete_pause_history_stmt->bindParam(':intern_id', $intern_id);
    $delete_pause_history_stmt->bindParam(':current_date', $current_date);
    $delete_pause_history_stmt->execute();
    
    // Delete the entire timesheet record instead of resetting values
    $delete_stmt = $conn->prepare("DELETE FROM timesheet 
    WHERE intern_id = :intern_id AND DATE(created_at) = :current_date");
    $delete_stmt->bindParam(':intern_id', $intern_id);
    $delete_stmt->bindParam(':current_date', $current_date);
    $delete_stmt->execute();
    
    // Return a message for the session
    return "Today's timesheet entry has been completely removed.";
}

/**
 * Clear session variables related to timesheet activity
 * 
 * @return void
 */
function clearTimesheetSessionVariables() {
    if(isset($_SESSION['timein_timestamp'])) unset($_SESSION['timein_timestamp']);
    if(isset($_SESSION['timein_intern_id'])) unset($_SESSION['timein_intern_id']);
    if(isset($_SESSION['overtime_active'])) unset($_SESSION['overtime_active']);
    if(isset($_SESSION['overtime_intern_id'])) unset($_SESSION['overtime_intern_id']);
    if(isset($_SESSION['overtime_start'])) unset($_SESSION['overtime_start']);
    if(isset($_SESSION['pause_active'])) unset($_SESSION['pause_active']);
    if(isset($_SESSION['pause_intern_id'])) unset($_SESSION['pause_intern_id']);
    if(isset($_SESSION['pause_start'])) unset($_SESSION['pause_start']);
}
?>