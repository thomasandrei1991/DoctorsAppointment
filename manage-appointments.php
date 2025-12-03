<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle appointment actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $appointment_id = intval($_POST['appointment_id']);

        switch ($action) {
            case 'confirm':
                $stmt = $conn->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?");
                $stmt->bind_param("i", $appointment_id);
                if ($stmt->execute()) {
                    log_action($user_id, 'confirm_appointment', "Confirmed appointment ID: $appointment_id");
                    $success = 'Appointment confirmed successfully.';

                    // Send notification to patient
                    $stmt2 = $conn->prepare("SELECT patient_id FROM appointments WHERE id = ?");
                    $stmt2->bind_param("i", $appointment_id);
                    $stmt2->execute();
                    $patient = $stmt2->get_result()->fetch_assoc();
                    send_notification($patient['patient_id'], 'Your appointment has been confirmed.');
                    $stmt2->close();
                }
                $stmt->close();
                break;

            case 'cancel':
                $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
                $stmt->bind_param("i", $appointment_id);
                if ($stmt->execute()) {
                    log_action($user_id, 'cancel_appointment', "Cancelled appointment ID: $appointment_id");
                    $success = 'Appointment cancelled successfully.';

                    // Send notification to patient
                    $stmt2 = $conn->prepare("SELECT patient_id FROM appointments WHERE id = ?");
                    $stmt2->bind_param("i", $appointment_id);
                    $stmt2->execute();
                    $patient = $stmt2->get_result()->fetch_assoc();
                    send_notification($patient['patient_id'], 'Your appointment has been cancelled.');
                    $stmt2->close();
                }
                $stmt->close();
                break;

            case 'complete':
                $stmt = $conn->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
                $stmt->bind_param("i", $appointment_id);
                if ($stmt->execute()) {
                    log_action($user_id, 'complete_appointment', "Completed appointment ID: $appointment_id");
                    $success = 'Appointment marked as completed.';
                }
                $stmt->close();
                break;
        }
    }
}

// Get appointments with filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';

$query = "
    SELECT a.*, u.first_name as patient_first, u.last_name as patient_last,
           d.first_name as doctor_first, d.last_name as doctor_last, doc.specialty
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN doctors doc ON a.doctor_id = doc.id
    JOIN users d ON doc.user_id = d.id
    WHERE 1=1
";

$params = [];
$types = '';

if ($status_filter) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($date_filter) {
    $query .= " AND DATE(a.appointment_date) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

$query .= " ORDER BY a.appointment_date DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE 1=1
";

$count_params = [];
$count_types = '';

if ($status_filter) {
    $count_query .= " AND a.status = ?";
    $count_params[] = $status_filter;
    $count_types .= 's';
}

if ($date_filter) {
    $count_query .= " AND DATE(a.appointment_date) = ?";
    $count_params[] = $date_filter;
    $count_types .= 's';
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_appointments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_appointments / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Manage Appointments</h1>
            <nav>
                <a href="admin-dashboard.php">Dashboard</a>
                <a href="manage-users.php">Manage Users</a>
                <a href="manage-appointments.php">Manage Appointments</a>
                <a href="audit-logs.php">Audit Logs</a>
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

            <div class="filters">
                <form method="GET" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date">Date:</label>
                            <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Specialty</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $appointment['id']; ?></td>
                                <td><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo ucfirst($appointment['status']); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <?php if ($appointment['status'] == 'pending'): ?>
                                            <button type="submit" name="action" value="confirm" class="btn btn-primary btn-sm">Confirm</button>
                                            <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                        <?php elseif ($appointment['status'] == 'confirmed'): ?>
                                            <button type="submit" name="action" value="complete" class="btn btn-secondary btn-sm">Complete</button>
                                            <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>" class="btn btn-secondary <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>
