
<?php
// Start session
session_start();

// Database connection
include 'connection/conn.php';

try {
    // Check if pause_history table exists
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'pause_history'");
    if ($checkTable->rowCount() > 0) {
        $tableExists = true;
    }
    
    if (!$tableExists) {
        // Create pause_history table
        $conn->exec("CREATE TABLE pause_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            timesheet_id INT,
            intern_id VARCHAR(50),
            pause_start TIME,
            pause_end TIME,
            pause_duration TIME,
            pause_reason TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (intern_id) REFERENCES interns(Intern_id)
        )");
        
        echo "Pause history table created successfully.<br>";
    } else {
        echo "Pause history table already exists.<br>";
    }
    
    echo "Migration completed successfully.";
    
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>