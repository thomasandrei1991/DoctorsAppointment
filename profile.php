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

// Get patient info
$stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name, u.email, u.phone FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $date_of_birth = sanitize_input($_POST['date_of_birth']);
    $gender = sanitize_input($_POST['gender']);
    $address = sanitize_input($_POST['address']);
    $emergency_contact_name = sanitize_input($_POST['emergency_contact_name']);
    $emergency_contact_phone = sanitize_input($_POST['emergency_contact_phone']);
    $medical_history = sanitize_input($_POST['medical_history']);
    $allergies = sanitize_input($_POST['allergies']);

    try {
        // Start transaction
        $conn->begin_transaction();

        // Update users table
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user information: " . $stmt->error);
        }
        $stmt->close();

        // Update patients table
        $stmt = $conn->prepare("UPDATE patients SET date_of_birth = ?, gender = ?, address = ?, emergency_contact_name = ?, emergency_contact_phone = ?, medical_history = ?, allergies = ? WHERE user_id = ?");
        $stmt->bind_param("sssssssi", $date_of_birth, $gender, $address, $emergency_contact_name, $emergency_contact_phone, $medical_history, $allergies, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update patient information: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        $success = 'Profile updated successfully.';

        // Refresh patient data
        $stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name, u.email, u.phone FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Update Profile</h1>
            <nav>
                <a href="patient-dashboard.php">Dashboard</a>
                <a href="book-appointment.php">Book Appointment</a>
                <a href="medical-records.php">Medical Records</a>
                <a href="messages.php">Messages</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <h2>Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth:</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $patient['date_of_birth']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($patient['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($patient['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($patient['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <h2>Contact Information</h2>
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                    </div>

                    <h2>Emergency Contact</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="emergency_contact_name">Emergency Contact Name:</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Phone:</label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <h2>Medical Information</h2>
                    <div class="form-group">
                        <label for="medical_history">Medical History:</label>
                        <textarea id="medical_history" name="medical_history" rows="4"><?php echo htmlspecialchars($patient['medical_history'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="allergies">Allergies:</label>
                        <textarea id="allergies" name="allergies" rows="3"><?php echo htmlspecialchars($patient['allergies'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>
