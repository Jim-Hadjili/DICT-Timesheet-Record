<?php
session_start();
include '../connection/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intern_id = isset($_POST['intern_id']) ? $_POST['intern_id'] : '';
    $note_date = isset($_POST['note_date']) ? $_POST['note_date'] : '';
    $note_content = isset($_POST['note_content']) ? trim($_POST['note_content']) : '';
    $note_id = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';
    
    // Validate inputs
    if (empty($intern_id) || empty($note_date)) {
        $_SESSION['message'] = "Missing required information for note.";
        $_SESSION['message_type'] = "error";
        header("Location: ../index.php?intern_id=" . $intern_id);
        exit();
    }
    
    try {
        // Create the table if it doesn't exist
        $conn->exec("CREATE TABLE IF NOT EXISTS intern_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            intern_id VARCHAR(50) NOT NULL,
            note_date DATE NOT NULL,
            note_content TEXT NOT NULL,
            noted TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY intern_date (intern_id, note_date)
        )");
        
        if ($action === 'delete' && $note_id > 0) {
            // Delete note
            $delete_stmt = $conn->prepare("DELETE FROM intern_notes WHERE id = :note_id AND intern_id = :intern_id");
            $delete_stmt->bindParam(':note_id', $note_id);
            $delete_stmt->bindParam(':intern_id', $intern_id);
            $delete_stmt->execute();
            
            $_SESSION['message'] = "Note has been deleted successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            // Check if note content is not empty
            if (empty($note_content)) {
                $_SESSION['message'] = "Note content cannot be empty.";
                $_SESSION['message_type'] = "error";
                header("Location: ../index.php?intern_id=" . $intern_id);
                exit();
            }
            
            $now = date('Y-m-d H:i:s');
            
            // Check if note exists
            $check_stmt = $conn->prepare("SELECT id FROM intern_notes WHERE intern_id = :intern_id AND note_date = :note_date");
            $check_stmt->bindParam(':intern_id', $intern_id);
            $check_stmt->bindParam(':note_date', $note_date);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // Update existing note
                $note_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
                $note_id = $note_data['id'];
                
                $update_stmt = $conn->prepare("UPDATE intern_notes SET note_content = :note_content, updated_at = :updated_at WHERE id = :note_id");
                $update_stmt->bindParam(':note_content', $note_content);
                $update_stmt->bindParam(':updated_at', $now);
                $update_stmt->bindParam(':note_id', $note_id);
                $update_stmt->execute();
                
                $_SESSION['message'] = "Note has been updated successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                // Insert new note
                $insert_stmt = $conn->prepare("INSERT INTO intern_notes (intern_id, note_date, note_content, created_at, updated_at) VALUES (:intern_id, :note_date, :note_content, :created_at, :updated_at)");
                $insert_stmt->bindParam(':intern_id', $intern_id);
                $insert_stmt->bindParam(':note_date', $note_date);
                $insert_stmt->bindParam(':note_content', $note_content);
                $insert_stmt->bindParam(':created_at', $now);
                $insert_stmt->bindParam(':updated_at', $now);
                $insert_stmt->execute();
                
                $_SESSION['message'] = "Note has been saved successfully.";
                $_SESSION['message_type'] = "success";
            }
            
            // Update the timesheet notes column if it exists
            try {
                $check_column = $conn->query("SHOW COLUMNS FROM timesheet LIKE 'notes'");
                if ($check_column->rowCount() > 0) {
                    $update_timesheet = $conn->prepare("UPDATE timesheet SET notes = :notes WHERE intern_id = :intern_id AND DATE(created_at) = :note_date");
                    $update_timesheet->bindParam(':notes', $note_content);
                    $update_timesheet->bindParam(':intern_id', $intern_id);
                    $update_timesheet->bindParam(':note_date', $note_date);
                    $update_timesheet->execute();
                }
            } catch (PDOException $e) {
                // Ignore errors
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error processing note: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect back to timesheet
    header("Location: ../index.php?intern_id=" . $intern_id);
    exit();
} else {
    // Not a POST request
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = "error";
    header("Location: ../index.php");
    exit();
}
?>