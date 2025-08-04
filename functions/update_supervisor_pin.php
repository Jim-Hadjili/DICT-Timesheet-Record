<?php
session_start();
include '../connection/conn.php';

header('Content-Type: application/json');

// Master PIN constant
define('MASTER_PIN', '0009');

// Get the JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$master_pin = isset($data['master_pin']) ? $data['master_pin'] : '';
$new_pin = isset($data['new_pin']) ? $data['new_pin'] : '';
$use_master_only = isset($data['use_master_only']) ? filter_var($data['use_master_only'], FILTER_VALIDATE_BOOLEAN) : false;

if (empty($master_pin) || $master_pin !== MASTER_PIN) {
    echo json_encode(['success' => false, 'message' => 'Master PIN verification failed']);
    exit;
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Check if there's a custom PIN set
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_pin'");
    $stmt->execute();
    
    $custom_pin_exists = $stmt->rowCount() > 0;
    
    if ($use_master_only) {
        // Remove custom PIN if it exists
        if ($custom_pin_exists) {
            $delete_stmt = $conn->prepare("DELETE FROM system_settings WHERE setting_key = 'system_pin'");
            $delete_stmt->execute();
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Custom PIN removed. System now uses Master PIN only.'
        ]);
        exit;
    } else {
        // Validate new PIN
        if (empty($new_pin) || strlen($new_pin) !== 4 || !ctype_digit($new_pin)) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'New PIN must be exactly 4 digits']);
            exit;
        }
        
        // Update or insert the supervisor PIN
        if ($custom_pin_exists) {
            $update_stmt = $conn->prepare("UPDATE system_settings SET setting_value = :pin WHERE setting_key = 'system_pin'");
            $update_stmt->bindParam(':pin', $new_pin);
            $update_stmt->execute();
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('system_pin', :pin)");
            $insert_stmt->bindParam(':pin', $new_pin);
            $insert_stmt->execute();
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'New PIN set successfully'
        ]);
        exit;
    }
    
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}