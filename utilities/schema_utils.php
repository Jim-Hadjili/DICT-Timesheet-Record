<?php
/**
 * Schema Utility Functions
 * 
 * This file contains functions for managing database schema and migrations
 */

/**
 * Ensure required columns exist in the timesheet table
 * This function runs migrations to add any missing columns
 * 
 * @param PDO $conn Database connection
 * @return array Results of schema checks and migrations
 */
function ensureTimesheetSchema($conn) {
    $results = [
        'changes' => [],
        'errors' => []
    ];
    
    // List of columns to check and add if missing
    $columns = [
        [
            'name' => 'overtime_manual',
            'definition' => 'TINYINT(1) DEFAULT 0',
            'description' => 'Flag to indicate if overtime was manually entered'
        ],
        [
            'name' => 'am_standard_end',
            'definition' => 'TIME DEFAULT NULL',
            'description' => 'Standard end time for morning session'
        ],
        [
            'name' => 'pm_standard_end',
            'definition' => 'TIME DEFAULT NULL',
            'description' => 'Standard end time for afternoon session'
        ],
        [
            'name' => 'am_timein_display',
            'definition' => 'TIME DEFAULT NULL',
            'description' => 'Actual time in for morning display purposes'
        ],
        [
            'name' => 'pause_start',
            'definition' => 'TIME DEFAULT NULL',
            'description' => 'Start time of current pause'
        ],
        [
            'name' => 'pause_end',
            'definition' => 'TIME DEFAULT NULL',
            'description' => 'End time of current pause'
        ],
        [
            'name' => 'pause_duration',
            'definition' => 'TIME DEFAULT \'00:00:00\'',
            'description' => 'Total duration of all pauses for the day'
        ],
        [
            'name' => 'pause_reason',
            'definition' => 'TEXT',
            'description' => 'Reason for the pause'
        ]
    ];
    
    // Check each column and add if missing
    foreach ($columns as $column) {
        try {
            $check_column = $conn->query("SHOW COLUMNS FROM timesheet LIKE '{$column['name']}'");
            if ($check_column->rowCount() == 0) {
                // Column doesn't exist, add it
                $conn->exec("ALTER TABLE timesheet ADD COLUMN {$column['name']} {$column['definition']}");
                $results['changes'][] = "Added column '{$column['name']}': {$column['description']}";
            }
        } catch (PDOException $e) {
            // Log the error but continue with other columns
            $results['errors'][] = "Error checking/adding column '{$column['name']}': " . $e->getMessage();
        }
    }
    
    // Check if pause_history table exists, create if not
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'pause_history'");
        if ($table_check->rowCount() == 0) {
            // Table doesn't exist, create it
            $conn->exec("CREATE TABLE pause_history (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                timesheet_id INT(11) NOT NULL,
                intern_id VARCHAR(50) NOT NULL,
                pause_start TIME NOT NULL,
                pause_end TIME NOT NULL,
                pause_duration TIME NOT NULL,
                pause_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (intern_id) REFERENCES interns(Intern_id) ON DELETE CASCADE
            )");
            $results['changes'][] = "Created pause_history table for tracking pause sessions";
        }
    } catch (PDOException $e) {
        $results['errors'][] = "Error checking/creating pause_history table: " . $e->getMessage();
    }
    
    return $results;
}

/**
 * Print schema migration results in a developer-friendly format
 * 
 * @param array $results Results from ensureTimesheetSchema function
 * @return void
 */
function printSchemaMigrationResults($results) {
    if (!empty($results['changes'])) {
        echo "<div class='alert alert-info'><strong>Database Updates:</strong><ul>";
        foreach ($results['changes'] as $change) {
            echo "<li>$change</li>";
        }
        echo "</ul></div>";
    }
    
    if (!empty($results['errors']) && isset($_GET['debug'])) {
        echo "<div class='alert alert-danger'><strong>Migration Errors:</strong><ul>";
        foreach ($results['errors'] as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul></div>";
    }
}
?>