<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = intval($_POST['appointment_id']);

    // Verify the appointment belongs to this doctor
    $stmt = $conn->prepare("
        SELECT a.id FROM appointments a
        WHERE a.id = ? AND a.doctor_id = (SELECT id FROM doctors WHERE user_id = ?) AND a.status = 'confirmed'
    ");
    $stmt->bind_param("ii", $appointment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update appointment status to completed
        $stmt = $conn->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $appointment_id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            // Log the action
            log_action($user_id, 'complete_appointment', "Completed appointment ID: $appointment_id");

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update appointment']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found or not authorized']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
