<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get messages for this user
$stmt = $conn->prepare("
    SELECT m.*, u.first_name, u.last_name, u.role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();

// Mark messages as read
$conn->query("UPDATE messages SET is_read = 1 WHERE receiver_id = $user_id AND is_read = 0");

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = sanitize_input($_POST['receiver_id']);
    $subject = sanitize_input($_POST['subject']);
    $message_text = sanitize_input($_POST['message']);

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $receiver_id, $subject, $message_text);
    $stmt->execute();
    $stmt->close();

    header('Location: messages.php');
    exit();
}

// Get list of users to send messages to (depending on role)
if ($user_role == 'doctor') {
    $stmt = $conn->prepare("SELECT u.id, u.first_name, u.last_name, u.role FROM users u WHERE u.role IN ('patient', 'admin') AND u.id != ?");
} elseif ($user_role == 'patient') {
    $stmt = $conn->prepare("SELECT u.id, u.first_name, u.last_name, u.role FROM users u WHERE u.role IN ('doctor', 'admin') AND u.id != ?");
} else {
    $stmt = $conn->prepare("SELECT u.id, u.first_name, u.last_name, u.role FROM users u WHERE u.id != ?");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recipients = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Messages</h1>
            <nav>
                <?php if ($user_role == 'doctor'): ?>
                    <a href="doctor-dashboard.php">Dashboard</a>
                    <a href="my-appointments.php">My Appointments</a>
                    <a href="patient-records.php">Patient Records</a>
                    <a href="messages.php">Messages</a>
                    <a href="availability.php">Set Availability</a>
                <?php elseif ($user_role == 'patient'): ?>
                    <a href="patient-dashboard.php">Dashboard</a>
                    <a href="book-appointment.php">Book Appointment</a>
                    <a href="messages.php">Messages</a>
                <?php else: ?>
                    <a href="admin-dashboard.php">Dashboard</a>
                    <a href="manage-users.php">Manage Users</a>
                    <a href="manage-appointments.php">Manage Appointments</a>
                    <a href="messages.php">Messages</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="messages-container">
                <div class="messages-list">
                    <h2>Inbox</h2>
                    <?php if ($messages->num_rows > 0): ?>
                        <?php while ($message = $messages->fetch_assoc()): ?>
                            <div class="message-item <?php echo $message['is_read'] ? '' : 'unread'; ?>">
                                <div class="message-header">
                                    <strong>From: <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?> (<?php echo ucfirst($message['role']); ?>)</strong>
                                    <span><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></span>
                                </div>
                                <div class="message-subject">
                                    <strong><?php echo htmlspecialchars($message['subject']); ?></strong>
                                </div>
                                <div class="message-body">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No messages found.</p>
                    <?php endif; ?>
                </div>

                <div class="send-message">
                    <h2>Send New Message</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="receiver_id">To:</label>
                            <select id="receiver_id" name="receiver_id" required>
                                <option value="">Select Recipient</option>
                                <?php while ($recipient = $recipients->fetch_assoc()): ?>
                                    <option value="<?php echo $recipient['id']; ?>">
                                        <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']); ?> (<?php echo ucfirst($recipient['role']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject:</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message:</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>
