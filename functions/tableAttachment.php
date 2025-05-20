
<?php
include_once '../connection/conn.php'; // Include your database connection file
// Function to save a note to the database
function saveNote($intern_id, $date, $note) {
    global $conn;
    
    try {
        // Check if a note already exists for this intern and date
        $check_stmt = $conn->prepare("SELECT id FROM intern_notes WHERE intern_id = :intern_id AND note_date = :date");
        $check_stmt->bindParam(':intern_id', $intern_id);
        $check_stmt->bindParam(':date', $date);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing note
            $note_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $update_stmt = $conn->prepare("UPDATE intern_notes SET note_content = :note, updated_at = NOW() WHERE id = :id");
            $update_stmt->bindParam(':note', $note);
            $update_stmt->bindParam(':id', $note_data['id']);
            $result = $update_stmt->execute();
        } else {
            // Insert new note
            $insert_stmt = $conn->prepare("INSERT INTO intern_notes (intern_id, note_date, note_content, created_at, updated_at) 
                                         VALUES (:intern_id, :date, :note, NOW(), NOW())");
            $insert_stmt->bindParam(':intern_id', $intern_id);
            $insert_stmt->bindParam(':date', $date);
            $insert_stmt->bindParam(':note', $note);
            $result = $insert_stmt->execute();
        }
        
        return [
            'success' => true,
            'message' => 'Note saved successfully'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error saving note: ' . $e->getMessage()
        ];
    }
}

// Function to get a note for a specific intern and date
function getNote($intern_id, $date) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT note_content, updated_at FROM intern_notes WHERE intern_id = :intern_id AND note_date = :date");
        $stmt->bindParam(':intern_id', $intern_id);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $note_data = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'success' => true,
                'note' => $note_data['note_content'],
                'updated_at' => $note_data['updated_at']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No note found'
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error retrieving note: ' . $e->getMessage()
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Set JSON content type for all responses
    
    // Save note
    if (isset($_POST['action']) && $_POST['action'] === 'save_note') {
        $intern_id = $_POST['intern_id'];
        $date = $_POST['date'];
        $note = $_POST['note'];
        
        $result = saveNote($intern_id, $date, $note);
        echo json_encode($result);
        exit;
    }
    
    // Get note
    if (isset($_POST['action']) && $_POST['action'] === 'get_note') {
        $intern_id = $_POST['intern_id'];
        $date = $_POST['date'];
        
        $result = getNote($intern_id, $date);
        echo json_encode($result);
        exit;
    }
    
    // If we get here, it's an invalid action
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action specified'
    ]);
    exit;
}

// Create the notes table if it doesn't exist
function createNotesTableIfNotExists() {
    global $conn;
    
    try {
        // Check if the table exists
        $stmt = $conn->prepare("SHOW TABLES LIKE 'intern_notes'");
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Table doesn't exist, create it
            $sql = "CREATE TABLE intern_notes (
                id INT(11) NOT NULL AUTO_INCREMENT,
                intern_id VARCHAR(50) NOT NULL,
                note_date DATE NOT NULL,
                note_content TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY intern_date (intern_id, note_date)
            )";
            
            $conn->exec($sql);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error creating notes table: " . $e->getMessage());
        return false;
    }
}

// Call the function to create the table if needed
createNotesTableIfNotExists();
?>
