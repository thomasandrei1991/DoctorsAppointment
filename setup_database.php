<?php
// Database setup script for Aventus Clinic

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aventus_clinic2');

// Create connection without selecting database first
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db(DB_NAME);

// Drop existing tables if they exist (in reverse order due to foreign keys)
$drop_tables = [
    'feedback',
    'payments',
    'notifications',
    'audit_logs',
    'messages',
    'appointments',
    'doctors',
    'patients',
    'users'
];

foreach ($drop_tables as $table) {
    $conn->query("DROP TABLE IF EXISTS $table");
}

// Read the SQL file
$sql_file = file_get_contents('database.sql');

// Remove the CREATE DATABASE and USE statements since database already exists
$sql_file = preg_replace('/CREATE DATABASE.*;\s*/i', '', $sql_file);
$sql_file = preg_replace('/USE.*;\s*/i', '', $sql_file);

// Split the SQL file into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql_file)));

// Execute each statement
$errors = [];
foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        if ($conn->query($statement) === FALSE) {
            $errors[] = "Error executing: " . $statement . " - " . $conn->error;
        }
    }
}

// Close connection
$conn->close();

if (empty($errors)) {
    echo "<h2>Database setup completed successfully!</h2>";
    echo "<p>All tables have been created and sample data has been inserted.</p>";
    echo "<p>You can now <a href='register.php'>register a new account</a> or <a href='login.php'>login</a>.</p>";
} else {
    echo "<h2>Some errors occurred during setup:</h2>";
    foreach ($errors as $error) {
        echo "<p>" . htmlspecialchars($error) . "</p>";
    }
}
?>
