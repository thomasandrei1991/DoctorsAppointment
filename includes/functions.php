<?php
// Helper functions for the Aventus Clinic system

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_random_string($length = 10) {
    return bin2hex(random_bytes($length));
}

function log_action($user_id, $action, $details = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

function get_user_role($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ? $user['role'] : null;
}

function check_permission($required_role) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $user_role = get_user_role($_SESSION['user_id']);
    $roles = ['patient' => 1, 'doctor' => 2, 'admin' => 3];
    return $roles[$user_role] >= $roles[$required_role];
}

function send_notification($user_id, $message, $type = 'info') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $message, $type);
    $stmt->execute();
    $stmt->close();
}

function get_unread_notifications($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    return $count;
}
?>
