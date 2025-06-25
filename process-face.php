<?php
// Include database connection
include './main.php';

header('Content-Type: application/json');

if (!isset($_POST['recognize_face']) || !isset($_POST['image_data'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

// Get the image data
$image_data = $_POST['image_data'];
$image_data = str_replace('data:image/png;base64,', '', $image_data);
$image_data = str_replace(' ', '+', $image_data);
$decoded_image = base64_decode($image_data);

// Save the image temporarily
$temp_file = './temp/face_' . time() . '.png';
file_put_contents($temp_file, $decoded_image);

// TODO: Integrate with your actual face recognition system
// This is a placeholder that simulates recognition

// For demonstration, let's randomly recognize or fail
$is_recognized = (rand(0, 10) > 3); // 70% chance of recognition

if ($is_recognized) {
    // Get random intern data for demo purposes
    try {
        $stmt = $conn->prepare("SELECT * FROM interns ORDER BY RAND() LIMIT 1");
        $stmt->execute();
        $intern = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($intern) {
            echo json_encode([
                'success' => true,
                'intern' => [
                    'id' => $intern['Intern_id'],
                    'name' => $intern['Intern_First_Name'] . ' ' . $intern['Intern_Last_Name'],
                    'school' => $intern['Intern_School']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No intern data found'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Face not recognized. Please try again.'
    ]);
}

// Delete the temporary file
@unlink($temp_file);
?>