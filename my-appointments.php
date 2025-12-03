<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get doctor's info
$stmt = $conn->prepare("SELECT d.*, u.first_name, u.last_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all appointments for this doctor
$stmt = $conn->prepare("
    SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, u.phone
    FROM appointments a
    JOIN patients pt ON a.patient_id = pt.id
    JOIN users p ON pt.user_id = p.id
    JOIN users u ON pt.user_id = u.id
    WHERE a.doctor_id = (SELECT id FROM doctors WHERE user_id = ?)
    ORDER BY a.appointment_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>My Appointments - Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h1>
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
            <div class="appointments-list">
                <h2>All Appointments</h2>
                <?php if ($appointments->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['phone']); ?></td>
                                    <td><?php echo ucfirst($appointment['status']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['reason'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="view-appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-secondary btn-sm">View</a>
                                        <?php if ($appointment['status'] == 'confirmed'): ?>
                                            <button onclick="completeAppointment(<?php echo $appointment['id']; ?>)" class="btn btn-primary btn-sm">Complete</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No appointments found.</p>
                <?php endif; ?>
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
