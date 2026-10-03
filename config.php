<?php
session_start();

// ─── InfinityFree Database Configuration ───────────────────────────────────
// Find these values in your InfinityFree Control Panel → MySQL Databases
// ⚠️  Do NOT use 'localhost' — InfinityFree requires the exact host below.
$host     = 'sql312.infinityfree.com'; // InfinityFree MySQL host
$username = 'if0_43074420';            // MySQL username
$password = 'GamerNujal4422';          // MySQL password
$database = 'if0_43074420_edustaff_db'; // Database name
// ───────────────────────────────────────────────────────────────────────────

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
