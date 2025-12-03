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

// Get today's appointments
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, u.phone
    FROM appointments a
    JOIN patients pt ON a.patient_id = pt.id
    JOIN users p ON pt.user_id = p.id
    JOIN users u ON pt.user_id = u.id
    WHERE a.doctor_id = (SELECT id FROM doctors WHERE user_id = ?) AND DATE(a.appointment_date) = ?
    ORDER BY a.appointment_date ASC
");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_appointments = $stmt->get_result();
$stmt->close();

// Get upcoming appointments (next 7 days)
$today_date = date('Y-m-d');
$next_week = date('Y-m-d', strtotime('+7 days'));
$stmt = $conn->prepare("
    SELECT a.*, p.first_name as patient_first, p.last_name as patient_last
    FROM appointments a
    JOIN patients pt ON a.patient_id = pt.id
    JOIN users p ON pt.user_id = p.id
    WHERE a.doctor_id = (SELECT id FROM doctors WHERE user_id = ?) AND DATE(a.appointment_date) BETWEEN ? AND ?
    ORDER BY a.appointment_date ASC
");
$stmt->bind_param("iss", $user_id, $today_date, $next_week);
$stmt->execute();
$upcoming_appointments = $stmt->get_result();
$stmt->close();

// Get unread messages
$result = $conn->query("SELECT COUNT(*) as count FROM messages WHERE receiver_id = $user_id AND is_read = 0");
$row = $result->fetch_assoc();
$unread_messages = $row ? $row['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h1>
            <nav>
                <a href="doctor-dashboard.php">Dashboard</a>
                <a href="my-appointments.php">My Appointments</a>
                <a href="patient-records.php">Patient Records</a>
                <a href="messages.php">Messages <?php if ($unread_messages > 0) echo "($unread_messages)"; ?></a>
                <a href="availability.php">Set Availability</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="dashboard-content">
                <div class="today-appointments">
                    <h3>Today's Appointments</h3>
                    <?php if ($today_appointments->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $today_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('H:i', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['phone']); ?></td>
                                        <td><?php echo ucfirst($appointment['status']); ?></td>
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
                        <p>No appointments scheduled for today.</p>
                    <?php endif; ?>
                </div>

                <div class="upcoming-appointments">
                    <h3>Upcoming Appointments (Next 7 Days)</h3>
                    <?php if ($upcoming_appointments->num_rows > 0): ?>
                        <ul>
                            <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                                <li>
                                    <strong><?php echo date('M d, H:i', strtotime($appointment['appointment_date'])); ?></strong> -
                                    <?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?>
                                    (<?php echo ucfirst($appointment['status']); ?>)
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No upcoming appointments.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="quick-stats">
                <div class="stat-card">
                    <h3>Today's Patients</h3>
                    <p class="stat-number"><?php echo $today_appointments->num_rows; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Upcoming This Week</h3>
                    <p class="stat-number"><?php echo $upcoming_appointments->num_rows; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Unread Messages</h3>
                    <p class="stat-number"><?php echo $unread_messages; ?></p>
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
