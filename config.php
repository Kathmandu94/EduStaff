<?php
session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP password
$database = 'edustaff_db';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Global helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    // Session timeout after 5 minutes (300 seconds)
    $timeout_duration = 300;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}
?>
