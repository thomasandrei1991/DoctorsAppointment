<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get patient info
$stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name, u.email FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get upcoming appointments
$stmt = $conn->prepare("
    SELECT a.*, d.first_name as doctor_first, d.last_name as doctor_last, doc.specialty
    FROM appointments a
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    WHERE a.patient_id = (SELECT id FROM patients WHERE user_id = ?) AND a.appointment_date >= NOW()
    ORDER BY a.appointment_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();
$stmt->close();

// Get appointment history
$stmt = $conn->prepare("
    SELECT a.*, d.first_name as doctor_first, d.last_name as doctor_last, doc.specialty
    FROM appointments a
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    WHERE a.patient_id = (SELECT id FROM patients WHERE user_id = ?) AND a.appointment_date < NOW()
    ORDER BY a.appointment_date DESC LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointment_history = $stmt->get_result();
$stmt->close();

// Get unread messages
$unread_messages = $conn->query("SELECT COUNT(*) as count FROM messages WHERE receiver_id = $user_id AND is_read = 0")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h1>
            <nav>
                <a href="patient-dashboard.php">Dashboard</a>
                <a href="book-appointment.php">Book Appointment</a>
                <a href="medical-records.php">Medical Records</a>
                <a href="messages.php">Messages <?php if ($unread_messages > 0) echo "($unread_messages)"; ?></a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="dashboard-content">
                <div class="upcoming-appointments">
                    <h3>Upcoming Appointments</h3>
                    <?php if ($upcoming_appointments->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Doctor</th>
                                    <th>Specialty</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                                        <td><?php echo ucfirst($appointment['status']); ?></td>
                                        <td>
                                            <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                                <button onclick="cancelAppointment(<?php echo $appointment['id']; ?>)" class="btn btn-danger btn-sm">Cancel</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No upcoming appointments. <a href="book-appointment.php">Book one now</a>.</p>
                    <?php endif; ?>
                </div>

                <div class="appointment-history">
                    <h3>Recent Appointment History</h3>
                    <?php if ($appointment_history->num_rows > 0): ?>
                        <ul>
                            <?php while ($appointment = $appointment_history->fetch_assoc()): ?>
                                <li>
                                    <strong><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></strong> -
                                    Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?>
                                    (<?php echo htmlspecialchars($appointment['specialty']); ?>) -
                                    <span class="status-<?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No appointment history available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <a href="book-appointment.php" class="btn btn-primary">Book New Appointment</a>
                <a href="messages.php" class="btn btn-secondary">Check Messages</a>
                <a href="profile.php" class="btn btn-secondary">Update Profile</a>
            </div>

            <div class="patient-info">
                <h3>Your Information</h3>
                <p><strong>ID Type:</strong> <?php echo htmlspecialchars($patient['id_type'] ?? 'Not set'); ?></p>
                <p><strong>ID Verified:</strong> <?php echo $patient['id_verified'] ? 'Yes' : 'No'; ?></p>
                <p><strong>Medical History:</strong> <?php echo htmlspecialchars($patient['medical_history'] ?? 'Not provided'); ?></p>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
    <script>
        function cancelAppointment(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                fetch('cancel-appointment.php', {
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
                        alert('Error cancelling appointment');
                    }
                });
            }
        }
    </script>
</body>
</html>
