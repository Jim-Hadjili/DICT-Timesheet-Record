<?php
// Set timezone to Philippines (Zamboanga City)
date_default_timezone_set('Asia/Manila');

include '../connection/conn.php';

// Initialize message variable
$message = "";

// Process form submission
if (isset($_POST['register'])) {
    // Get form data
    $name = $_POST['name'];
    $school = $_POST['school'];
    $birthday = $_POST['birthday'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $required_hours = $_POST['required_hours'];
    
    try {
        // Insert into interns table
        $stmt = $conn->prepare("INSERT INTO interns (Intern_Name, Intern_School, Intern_BirthDay, Intern_Age, Intern_Gender, Required_Hours_Rendered, Face_Registered) 
                               VALUES (:name, :school, :birthday, :age, :gender, :required_hours, 0)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':school', $school);
        $stmt->bindParam(':birthday', $birthday);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':required_hours', $required_hours);
        $stmt->execute();
        
        // Get the newly inserted intern ID
        $intern_id = $conn->lastInsertId();
        
        $message = "Student registered successfully!";
        
        // Clear form data after successful submission
        $name = $school = $birthday = $age = $gender = "";
        
        // Redirect to face registration if requested
        if (isset($_POST['register_face']) && $_POST['register_face'] == 1) {
            header("Location: face_capture.php?intern_id=" . $intern_id);
            exit();
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>