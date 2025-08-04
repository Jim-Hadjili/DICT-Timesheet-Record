<?php
session_start();
include '../connection/conn.php';

header('Content-Type: application/json');

// Master PIN constant
define('MASTER_PIN', '0009');

// Get the JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$pin = isset($data['pin']) ? $data['pin'] : '';
$action = isset($data['action']) ? $data['action'] : 'verify_time_edit';

if (empty($pin)) {
    echo json_encode(['success' => false, 'message' => 'No PIN provided']);
    exit;
}

try {
    // Check if there's a custom PIN set (using supervisor_pin as key)
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_pin'");
    $stmt->execute();
    
    $custom_pin_exists = $stmt->rowCount() > 0;
    $supervisor_pin = $custom_pin_exists ? $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] : '';
    
    // Behavior based on action
    switch ($action) {
        case 'verify_time_edit':
            // Standard time edit verification
            if ($custom_pin_exists) {
                // If custom PIN exists, only that PIN works for edits
                if ($pin === $supervisor_pin) {
                    echo json_encode(['success' => true, 'pin_type' => 'supervisor']);
                    exit;
                } else {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Invalid PIN. Please use your custom PIN for time adjustments.'
                    ]);
                    exit;
                }
            } else {
                // If no custom PIN, master PIN can be used directly
                if ($pin === MASTER_PIN) {
                    echo json_encode(['success' => true, 'pin_type' => 'master']);
                    exit;
                } else {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Invalid PIN. Please use the master PIN.'
                    ]);
                    exit;
                }
            }
            break;
            
        case 'verify_master':
            // Master PIN verification for PIN reset
            if ($pin === MASTER_PIN) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid Master PIN']);
                exit;
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}