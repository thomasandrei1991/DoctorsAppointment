<?php
// Session configuration (set only if session not already started)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aventus_clinic2');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "<br><br>Please make sure:<br>1. XAMPP MySQL is running<br>2. Database 'aventus_clinic2' exists (import database.sql in phpMyAdmin)");
}

// Set charset
$conn->set_charset("utf8");

// Include helper functions
require_once 'functions.php';
?>
