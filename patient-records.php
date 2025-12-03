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

// Get patients who have appointments with this doctor
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
           p.date_of_birth, p.gender, p.address, p.medical_history, p.allergies
    FROM users u
    JOIN patients p ON u.id = p.user_id
    JOIN appointments a ON p.id = a.patient_id
    WHERE a.doctor_id = (SELECT id FROM doctors WHERE user_id = ?)
    ORDER BY u.last_name, u.first_name
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patients = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Records - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Patient Records - Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h1>
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
            <div class="patients-list">
                <h2>My Patients</h2>
                <?php if ($patients->num_rows > 0): ?>
                    <div class="patients-grid">
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <div class="patient-card">
                                <div class="patient-header">
                                    <h3><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h3>
                                    <span class="patient-id">ID: <?php echo $patient['id']; ?></span>
                                </div>
                                <div class="patient-info">
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone']); ?></p>
                                    <p><strong>Date of Birth:</strong> <?php echo $patient['date_of_birth'] ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></p>
                                    <p><strong>Gender:</strong> <?php echo ucfirst($patient['gender'] ?? 'N/A'); ?></p>
                                    <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address'] ?? 'N/A'); ?></p>
                                    <p><strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($patient['created_at'])); ?></p>
                                </div>
                                <div class="patient-medical">
                                    <h4>Medical Information</h4>
                                    <p><strong>Medical History:</strong> <?php echo htmlspecialchars($patient['medical_history'] ?? 'None recorded'); ?></p>
                                    <p><strong>Allergies:</strong> <?php echo htmlspecialchars($patient['allergies'] ?? 'None recorded'); ?></p>
                                </div>
                                <div class="patient-actions">
                                    <a href="view-patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-secondary btn-sm">View Full Record</a>
                                    <a href="add-medical-note.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-primary btn-sm">Add Note</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No patients found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>
