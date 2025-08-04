<?php
session_start();
include '../connection/conn.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Master PIN that cannot be changed
define('MASTER_PIN', '0009');

// Check if system_settings table exists, create if it doesn't
try {
    $conn->query("SELECT 1 FROM system_settings LIMIT 1");
} catch (Exception $e) {
    // Table doesn't exist, create it
    try {
        $conn->query("CREATE TABLE system_settings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY (setting_key)
        )");
    } catch (Exception $create_e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to create settings table: ' . $create_e->getMessage()
        ]);
        exit;
    }
}

// Function to get a setting value
function getSetting($conn, $key, $default = null) {
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1");
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['setting_value'];
        }
        return $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Function to save a setting value
function saveSetting($conn, $key, $value) {
    try {
        // Check if setting exists
        $check = $conn->prepare("SELECT 1 FROM system_settings WHERE setting_key = :key");
        $check->bindParam(':key', $key);
        $check->execute();
        
        if ($check->rowCount() > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = :value WHERE setting_key = :key");
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value)");
        }
        
        $stmt->bindParam(':key', $key);
        $stmt->bindParam(':value', $value);
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

// Check if a custom PIN has been set
function hasCustomPin($conn) {
    $pin = getSetting($conn, 'system_pin', '');
    return !empty($pin);
}

// Verify PIN
function verifyPin($conn, $pin) {
    // Always check against master PIN first if no custom PIN exists
    $stored_pin = getSetting($conn, 'system_pin', '');
    
    if (empty($stored_pin)) {
        // If no PIN is set, master PIN is valid
        return $pin === MASTER_PIN;
    }
    
    // If a custom PIN is set, check against that first
    if ($pin === $stored_pin) {
        return true;
    }
    
    // Master PIN is still valid for reset flow
    if (isset($_POST['is_reset_flow']) && $_POST['is_reset_flow'] === 'true' && $pin === MASTER_PIN) {
        return true;
    }
    
    return false;
}

// Handle POST request for saving settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Special case for forgot PIN flow
        $using_master_key = false;
        if (isset($_POST['is_reset_flow']) && $_POST['is_reset_flow'] === 'true' && $_POST['pin'] === MASTER_PIN) {
            $using_master_key = true;
        } else {
            // Normal verification
            $pin = $_POST['pin'] ?? '';
            
            if (!verifyPin($conn, $pin)) {
                $response['message'] = 'Invalid PIN. Settings were not saved.';
                echo json_encode($response);
                exit;
            }
        }
        
        // Process logo upload if present
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../assets/images/uploads/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                if (!mkdir($target_dir, 0755, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }
            
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'company_logo_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            
            // Check if file is an image
            $check = getimagesize($_FILES['logo']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                    $logo_path = './assets/images/uploads/' . $filename;
                    saveSetting($conn, 'logo_path', $logo_path);
                } else {
                    throw new Exception('Error uploading logo.');
                }
            } else {
                throw new Exception('Uploaded file is not a valid image.');
            }
        }
        
        // Save company name
        if (isset($_POST['company_name']) && !empty($_POST['company_name'])) {
            saveSetting($conn, 'company_name', $_POST['company_name']);
        }
        
        // Save company header
        if (isset($_POST['company_header']) && !empty($_POST['company_header'])) {
            saveSetting($conn, 'company_header', $_POST['company_header']);
        }
        
        // Save new PIN if provided
        if (isset($_POST['new_pin']) && !empty($_POST['new_pin']) && 
            isset($_POST['confirm_new_pin']) && $_POST['new_pin'] === $_POST['confirm_new_pin']) {
            saveSetting($conn, 'system_pin', $_POST['new_pin']);
        }
        
        $response['success'] = true;
        $response['message'] = 'Settings saved successfully.';
        $response['logo_path'] = $logo_path;
        $response['company_name'] = $_POST['company_name'] ?? getSetting($conn, 'company_name', '');
        $response['company_header'] = $_POST['company_header'] ?? getSetting($conn, 'company_header', '');
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Check if system has a custom PIN
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check_pin_status') {
    $response = [
        'success' => true,
        'has_custom_pin' => hasCustomPin($conn)
    ];
    echo json_encode($response);
    exit;
}

// Handle verify master key
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'verify_master') {
    $response = ['success' => false, 'message' => ''];
    $master_key = $_GET['key'] ?? '';
    
    if ($master_key === MASTER_PIN) {
        $response['success'] = true;
        $response['message'] = 'Master key verified successfully.';
    } else {
        $response['message'] = 'Invalid master key.';
    }
    
    echo json_encode($response);
    exit;
}

// Default response for invalid requests
echo json_encode(['success' => false, 'message' => 'Invalid request']);