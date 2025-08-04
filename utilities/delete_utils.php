<?php
/**
 * Delete Utility Functions
 * 
 * This file contains functions for deleting interns, timesheet records, and associated files
 */

/**
 * Delete a student and all their associated records and photos
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The ID of the intern to delete
 * @return string Success message
 */
function deleteStudent($conn, $intern_id) {
    // Get intern name for the message
    $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
    $name_stmt->bindParam(':intern_id', $intern_id);
    $name_stmt->execute();
    $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
    $intern_name = $intern_data ? $intern_data['Intern_Name'] : 'Unknown Intern';
    
    // Delete all associated photos from the uploads folder
    $photos_stmt = $conn->prepare("SELECT photo_path FROM timesheet_photos WHERE intern_id = :intern_id");
    $photos_stmt->bindParam(':intern_id', $intern_id);
    $photos_stmt->execute();
    
    while ($photo = $photos_stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($photo['photo_path']) && file_exists($photo['photo_path'])) {
            unlink($photo['photo_path']);
        }
    }
    
    // Delete face registration photos
    $face_photos = glob('./uploads/faces/face_' . $intern_id . '_*.png');
    foreach ($face_photos as $face_photo) {
        if (file_exists($face_photo)) {
            unlink($face_photo);
        }
    }
    
    // Delete from timesheet_photos first (foreign key constraint)
    $delete_photos_stmt = $conn->prepare("DELETE FROM timesheet_photos WHERE intern_id = :intern_id");
    $delete_photos_stmt->bindParam(':intern_id', $intern_id);
    $delete_photos_stmt->execute();
    
    // Delete from pause_history if exists
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'pause_history'");
        if ($table_check->rowCount() > 0) {
            $delete_pause_history_stmt = $conn->prepare("DELETE FROM pause_history WHERE intern_id = :intern_id");
            $delete_pause_history_stmt->bindParam(':intern_id', $intern_id);
            $delete_pause_history_stmt->execute();
        }
    } catch (PDOException $e) {
        // Silently handle error if table doesn't exist
    }
    
    // Delete from timesheet
    $delete_timesheet_stmt = $conn->prepare("DELETE FROM timesheet WHERE intern_id = :intern_id");
    $delete_timesheet_stmt->bindParam(':intern_id', $intern_id);
    $delete_timesheet_stmt->execute();
    
    // Then delete from interns
    $delete_intern_stmt = $conn->prepare("DELETE FROM interns WHERE Intern_id = :intern_id");
    $delete_intern_stmt->bindParam(':intern_id', $intern_id);
    $delete_intern_stmt->execute();
    
    return "Student and all associated records/photos deleted successfully.";
}

/**
 * Delete all timesheet records for an intern
 * 
 * @param PDO $conn Database connection
 * @param string $intern_id The ID of the intern
 * @return string Success message
 */
function deleteAllRecords($conn, $intern_id) {
    // Get intern name for the message
    $name_stmt = $conn->prepare("SELECT Intern_Name FROM interns WHERE Intern_id = :intern_id");
    $name_stmt->bindParam(':intern_id', $intern_id);
    $name_stmt->execute();
    $intern_data = $name_stmt->fetch(PDO::FETCH_ASSOC);
    $intern_name = $intern_data['Intern_Name'];
    
    // Get all record IDs for this intern
    $records_stmt = $conn->prepare("SELECT record_id FROM timesheet WHERE intern_id = :intern_id");
    $records_stmt->bindParam(':intern_id', $intern_id);
    $records_stmt->execute();
    
    // Get all photo paths that need to be deleted
    $photos_to_delete = [];
    while ($record = $records_stmt->fetch(PDO::FETCH_ASSOC)) {
        $photos_stmt = $conn->prepare("SELECT photo_path FROM timesheet_photos 
                                  WHERE record_id = :record_id");
        $photos_stmt->bindParam(':record_id', $record['record_id']);
        $photos_stmt->execute();
        
        while ($photo = $photos_stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($photo['photo_path'])) {
                $photos_to_delete[] = $photo['photo_path'];
            }
        }
    }
    
    // Delete all photos from the uploads folder
    $deleted_photo_count = 0;
    foreach ($photos_to_delete as $photo_path) {
        if (file_exists($photo_path)) {
            unlink($photo_path);
            $deleted_photo_count++;
        }
    }
    
    // Delete all photo records from the database
    $delete_photos_stmt = $conn->prepare("DELETE FROM timesheet_photos WHERE intern_id = :intern_id");
    $delete_photos_stmt->bindParam(':intern_id', $intern_id);
    $delete_photos_stmt->execute();
    
    // Delete from pause_history if exists
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'pause_history'");
        if ($table_check->rowCount() > 0) {
            $delete_pause_history_stmt = $conn->prepare("DELETE FROM pause_history WHERE intern_id = :intern_id");
            $delete_pause_history_stmt->bindParam(':intern_id', $intern_id);
            $delete_pause_history_stmt->execute();
        }
    } catch (PDOException $e) {
        // Silently handle error if table doesn't exist
    }
    
    // Delete all timesheet records
    $delete_all_stmt = $conn->prepare("DELETE FROM timesheet WHERE intern_id = :intern_id");
    $delete_all_stmt->bindParam(':intern_id', $intern_id);
    $delete_all_stmt->execute();
    
    return "All timesheet records for " . $intern_name . " have been deleted. " . 
        ($deleted_photo_count > 0 ? $deleted_photo_count . " photo(s) were also removed." : "");
}

/**
 * Delete files from a directory that match a pattern
 * 
 * @param string $pattern Glob pattern to match files
 * @return int Number of files deleted
 */
function deleteFilesByPattern($pattern) {
    $files = glob($pattern);
    $count = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    
    return $count;
}
?>