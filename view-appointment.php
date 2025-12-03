<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    header('Location: doctor-dashboard.php');
    exit();
}

// Get appointment details
$stmt = $conn->prepare("
    SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, u.phone, pt.date_of_birth, pt.gender, pt.medical_history, pt.allergies
    FROM appointments a
    JOIN patients pt ON a.patient_id = pt.id
    JOIN users p ON pt.user_id = p.id
    JOIN users u ON pt.user_id = u.id
    WHERE a.id = ? AND a.doctor_id = (SELECT id FROM doctors WHERE user_id = ?)
");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    header('Location: doctor-dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Appointment Details</h1>
            <nav>
                <a href="doctor-dashboard.php">Dashboard</a>
                <a href="my-appointments.php">My Appointments</a>
                <a href="patient-records.php">Patient Records</a>
                <a href="messages.php">Messages</a>
                <a href="availability.php">Set Availability</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="appointment-details">
                <h2>Appointment Information</h2>
                <div class="info-grid">
                    <div class="info-section">
                        <h3>Appointment Details</h3>
                        <p><strong>Date & Time:</strong> <?php echo date('M d, Y H:i', strtotime($appointment['appointment_date'])); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($appointment['status']); ?></p>
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($appointment['reason'] ?? 'Not specified'); ?></p>
                        <p><strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes'] ?? 'No notes'); ?></p>
                    </div>

                    <div class="info-section">
                        <h3>Patient Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['phone']); ?></p>
                        <p><strong>Date of Birth:</strong> <?php echo $appointment['date_of_birth'] ? date('M d, Y', strtotime($appointment['date_of_birth'])) : 'Not provided'; ?></p>
                        <p><strong>Gender:</strong> <?php echo ucfirst($appointment['gender'] ?? 'Not specified'); ?></p>
                        <p><strong>Medical History:</strong> <?php echo htmlspecialchars($appointment['medical_history'] ?? 'Not provided'); ?></p>
                        <p><strong>Allergies:</strong> <?php echo htmlspecialchars($appointment['allergies'] ?? 'None reported'); ?></p>
                    </div>
                </div>

                <div class="actions">
                    <a href="doctor-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    <?php if ($appointment['status'] == 'confirmed'): ?>
                        <button onclick="completeAppointment(<?php echo $appointment['id']; ?>)" class="btn btn-primary">Mark as Completed</button>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
    <script>
        function completeAppointment(appointmentId) {
            if (confirm('Mark this appointment as completed?')) {
                fetch('complete-appointment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'appointment_id=' + appointmentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error completing appointment');
                    }
                });
            }
        }
    </script>
</body>
</html>
