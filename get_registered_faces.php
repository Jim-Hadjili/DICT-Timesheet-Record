<?php
// Set timezone to Philippines for accurate timestamp logging
date_default_timezone_set('Asia/Manila');

// Include database connection file
include 'connection/conn.php';

// Tell the browser we're sending JSON data
header('Content-Type: application/json');

try {
    // Query to fetch only interns who have registered their faces
    // We only want records with valid face image paths
    $interns_stmt = $conn->prepare("SELECT Intern_id, Intern_Name, Face_Image_Path FROM interns WHERE Face_Registered = 1 AND Face_Image_Path IS NOT NULL AND Face_Image_Path != ''");
    $interns_stmt->execute();
    $interns = $interns_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter out interns whose face image files don't actually exist on the server
    $valid_interns = [];
    foreach ($interns as $intern) {
        $face_path = './uploads/faces/' . $intern['Face_Image_Path'];
        if (file_exists($face_path)) {
            // Keep this intern in our results if their image exists
            $valid_interns[] = $intern;
        } else {
            // Log an error if the file is missing but was in the database
            error_log("Face image file not found: " . $face_path . " for intern ID: " . $intern['Intern_id']);
        }
    }
    
    // Return the list of interns with valid face images
    echo json_encode($valid_interns);
} catch (PDOException $e) {
    // Handle database-specific errors
    error_log("Database error in get_registered_faces.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    // Catch any other unexpected errors
    error_log("General error in get_registered_faces.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while fetching registered faces']);
}
?>
