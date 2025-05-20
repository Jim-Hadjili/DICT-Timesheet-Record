<?php
// Start session to store messages
session_start();

// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

// Define the required directories
$required_dirs = [
    'functions/face_images/',
    'functions/temp_faces/'
];

// Track success/failure
$created_dirs = [];
$failed_dirs = [];

// Try to create each directory
foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        // Try to create the directory with full permissions
        if (mkdir($dir, 0777, true)) {
            $created_dirs[] = $dir;
        } else {
            $failed_dirs[] = $dir;
        }
    }
}

// Set message based on results
if (empty($failed_dirs)) {
    if (empty($created_dirs)) {
        $_SESSION['message'] = "All required directories already exist.";
    } else {
        $_SESSION['message'] = "Successfully created directories: " . implode(", ", $created_dirs);
    }
} else {
    $_SESSION['message'] = "Failed to create directories: " . implode(", ", $failed_dirs) . 
                          ". Please check permissions or create them manually.";
}

// Redirect back to the main page
header("Location: index.php");
exit();
?>
