<?php
// Database check script for Aventus Clinic

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aventus_clinic');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Tables Status:</h2>";

// Get all tables
$result = $conn->query("SHOW TABLES");
$tables = [];
if ($result) {
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
}

$expected_tables = ['users', 'patients', 'doctors', 'appointments', 'messages', 'audit_logs', 'notifications', 'payments', 'feedback'];

echo "<ul>";
foreach ($expected_tables as $table) {
    if (in_array($table, $tables)) {
        echo "<li style='color: green;'>✓ $table - EXISTS</li>";
    } else {
        echo "<li style='color: red;'>✗ $table - MISSING</li>";
    }
}
echo "</ul>";

// Check users table structure if it exists
if (in_array('users', $tables)) {
    echo "<h3>Users Table Structure:</h3>";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
        }
        echo "</table>";
    }
}

// Close connection
$conn->close();
?>
