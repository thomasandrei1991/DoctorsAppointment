<?php
session_start();
require_once 'includes/config.php';

// Log the logout action
if (isset($_SESSION['user_id'])) {
    log_action($_SESSION['user_id'], 'logout', 'User logged out');
}

// Destroy the session
session_unset();
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>
