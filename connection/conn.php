<?php
$host = 'localhost';        // Database host (usually localhost)
$dbname = 'ojt_timesheet_db';           // Database name
$username = 'root';         // Database username (default is 'root' for XAMPP)
$password = '';             // Database password (default is empty for XAMPP)

try {
    // Establish database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection error
    echo "Connection failed: " . $e->getMessage();
    exit();
}
