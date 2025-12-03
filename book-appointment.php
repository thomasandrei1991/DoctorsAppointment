<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get available doctors
$doctors = $conn->query("
    SELECT d.id, u.first_name, u.last_name, d.specialty, d.consultation_fee
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    WHERE u.is_active = 1
    ORDER BY u.first_name
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = sanitize_input($_POST['doctor_id']);
    $appointment_date = sanitize_input($_POST['appointment_date']);
    $appointment_time = sanitize_input($_POST['appointment_time']);
    $reason = sanitize_input($_POST['reason']);

    // Combine date and time
    $appointment_datetime = $appointment_date . ' ' . $appointment_time . ':00';

    // Validation
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($appointment_datetime) <= time()) {
        $error = 'Appointment must be in the future.';
    } else {
        // Check if slot is available
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM appointments
            WHERE doctor_id = ? AND appointment_date = ? AND status IN ('pending', 'confirmed')
        ");
        $stmt->bind_param("is", $doctor_id, $appointment_datetime);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            $error = 'This time slot is already booked. Please choose another time.';
        } else {
            // Get patient ID
            $stmt2 = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $patient = $stmt2->get_result()->fetch_assoc();
            $patient_id = $patient['id'];
            $stmt2->close();

            // Book appointment
            $stmt3 = $conn->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt3->bind_param("iiss", $patient_id, $doctor_id, $appointment_datetime, $reason);

            if ($stmt3->execute()) {
                $appointment_id = $conn->insert_id;
                log_action($user_id, 'book_appointment', "Booked appointment with doctor ID: $doctor_id");

                // Send notification to doctor
                $stmt4 = $conn->prepare("SELECT user_id FROM doctors WHERE id = ?");
                $stmt4->bind_param("i", $doctor_id);
                $stmt4->execute();
                $doctor_user = $stmt4->get_result()->fetch_assoc();
                send_notification($doctor_user['user_id'], 'New appointment request received');
                $stmt4->close();

                $success = 'Appointment booked successfully! You will receive a confirmation once the doctor approves it.';
            } else {
                $error = 'Failed to book appointment. Please try again.';
            }
            $stmt3->close();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Book Appointment</h1>
            <nav>
                <a href="patient-dashboard.php">Dashboard</a>
                <a href="book-appointment.php">Book Appointment</a>
                <a href="my-appointments.php">My Appointments</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="appointment-form">
                <h2>Schedule a New Appointment</h2>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="doctor_id">Select Doctor:</label>
                            <select id="doctor_id" name="doctor_id" required>
                                <option value="">Choose a doctor</option>
                                <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                    <option value="<?php echo $doctor['id']; ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> -
                                        <?php echo htmlspecialchars($doctor['specialty']); ?> -
                                        ₱<?php echo number_format($doctor['consultation_fee'], 2); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="appointment_date">Appointment Date:</label>
                            <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="appointment_time">Appointment Time:</label>
                            <select id="appointment_time" name="appointment_time" required>
                                <option value="">Select time</option>
                                <option value="09:00">9:00 AM</option>
                                <option value="09:30">9:30 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="10:30">10:30 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="11:30">11:30 AM</option>
                                <option value="14:00">2:00 PM</option>
                                <option value="14:30">2:30 PM</option>
                                <option value="15:00">3:00 PM</option>
                                <option value="15:30">3:30 PM</option>
                                <option value="16:00">4:00 PM</option>
                                <option value="16:30">4:30 PM</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reason">Reason for Visit:</label>
                            <textarea id="reason" name="reason" rows="3" placeholder="Brief description of your medical concern"></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Book Appointment</button>
                </form>
            </div>

            <div class="appointment-info">
                <h3>Appointment Information</h3>
                <ul>
                    <li>Appointments are subject to doctor availability and confirmation.</li>
                    <li>You will receive a notification once your appointment is confirmed.</li>
                    <li>Please arrive 15 minutes early for your appointment.</li>
                    <li>Cancellation policy: Please cancel at least 24 hours in advance.</li>
                </ul>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>
