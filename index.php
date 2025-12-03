<?php
session_start();
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect based on role
switch ($_SESSION['role']) {
    case 'admin':
        header('Location: admin-dashboard.php');
        break;
    case 'doctor':
        header('Location: doctor-dashboard.php');
        break;
    case 'patient':
        header('Location: patient-dashboard.php');
        break;
    default:
        header('Location: login.php');
        break;
}
?>
