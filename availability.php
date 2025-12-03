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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $availability_data = json_encode($_POST['availability'] ?? []);
    $consultation_fee = floatval($_POST['consultation_fee'] ?? 0);

    $stmt = $conn->prepare("UPDATE doctors SET availability_schedule = ?, consultation_fee = ? WHERE user_id = ?");
    $stmt->bind_param("sdi", $availability_data, $consultation_fee, $user_id);
    $stmt->execute();
    $stmt->close();

    $success = "Availability updated successfully!";
}

// Get current availability
$current_availability = json_decode($doctor['availability_schedule'] ?? '[]', true);
$current_fee = $doctor['consultation_fee'] ?? 0;

// Days of the week
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Availability - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Set Availability - Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h1>
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
            <div class="availability-form">
                <h2>Manage Your Availability</h2>

                <?php if (isset($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="consultation_fee">Consultation Fee ($):</label>
                        <input type="number" id="consultation_fee" name="consultation_fee" step="0.01" min="0" value="<?php echo htmlspecialchars($current_fee); ?>" required>
                    </div>

                    <h3>Weekly Schedule</h3>
                    <p>Set your available time slots for each day. Patients can book appointments during these times.</p>

                    <div class="schedule-grid">
                        <?php for ($i = 0; $i < 7; $i++): ?>
                            <div class="day-schedule">
                                <h4><?php echo $day_names[$i]; ?></h4>
                                <div class="time-slots">
                                    <label>
                                        <input type="checkbox" name="availability[<?php echo $days[$i]; ?>][available]"
                                               value="1" <?php echo isset($current_availability[$days[$i]]['available']) ? 'checked' : ''; ?>>
                                        Available
                                    </label>

                                    <div class="time-inputs" id="times-<?php echo $days[$i]; ?>" style="<?php echo isset($current_availability[$days[$i]]['available']) ? '' : 'display: none;'; ?>">
                                        <label>Start Time:
                                            <input type="time" name="availability[<?php echo $days[$i]; ?>][start_time]"
                                                   value="<?php echo htmlspecialchars($current_availability[$days[$i]]['start_time'] ?? '09:00'); ?>">
                                        </label>
                                        <label>End Time:
                                            <input type="time" name="availability[<?php echo $days[$i]; ?>][end_time]"
                                                   value="<?php echo htmlspecialchars($current_availability[$days[$i]]['end_time'] ?? '17:00'); ?>">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Availability</button>
                </form>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
    <script>
        // Show/hide time inputs when availability checkbox is toggled
        document.querySelectorAll('input[type="checkbox"][name*="available"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const day = this.name.match(/\[(\w+)\]\[available\]/)[1];
                const timeInputs = document.getElementById('times-' + day);
                if (this.checked) {
                    timeInputs.style.display = 'block';
                } else {
                    timeInputs.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
