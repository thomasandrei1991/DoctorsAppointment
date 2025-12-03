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

// Get appointment history with medical notes
$stmt = $conn->prepare("
    SELECT a.*, d.first_name as doctor_first, d.last_name as doctor_last, doc.specialty
    FROM appointments a
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    WHERE a.patient_id = (SELECT id FROM patients WHERE user_id = ?) AND a.status = 'completed'
    ORDER BY a.appointment_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$medical_records = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Medical Records</h1>
            <nav>
                <a href="patient-dashboard.php">Dashboard</a>
                <a href="book-appointment.php">Book Appointment</a>
                <a href="medical-records.php">Medical Records</a>
                <a href="messages.php">Messages</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="patient-info">
                <h2>Patient Information</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo $patient['date_of_birth'] ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'Not provided'; ?></p>
                <p><strong>Gender:</strong> <?php echo ucfirst($patient['gender'] ?? 'Not specified'); ?></p>
                <p><strong>ID Type:</strong> <?php echo htmlspecialchars($patient['id_type'] ?? 'Not set'); ?></p>
                <p><strong>ID Verified:</strong> <?php echo $patient['id_verified'] ? 'Yes' : 'No'; ?></p>
                <p><strong>Medical History:</strong> <?php echo htmlspecialchars($patient['medical_history'] ?? 'Not provided'); ?></p>
                <p><strong>Allergies:</strong> <?php echo htmlspecialchars($patient['allergies'] ?? 'None reported'); ?></p>
            </div>

            <div class="medical-history">
                <h2>Appointment History & Medical Notes</h2>
                <?php if ($medical_records->num_rows > 0): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Specialty</th>
                                    <th>Reason</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($record = $medical_records->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['appointment_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['doctor_first'] . ' ' . $record['doctor_last']); ?></td>
                                        <td><?php echo htmlspecialchars($record['specialty']); ?></td>
                                        <td><?php echo htmlspecialchars($record['reason'] ?? 'Not specified'); ?></td>
                                        <td><?php echo htmlspecialchars($record['notes'] ?? 'No notes available'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No medical records available.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>
