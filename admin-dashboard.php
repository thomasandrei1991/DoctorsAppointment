<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get dashboard statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch_assoc()['count'];
$total_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.is_active = 1")->fetch_assoc()['count'];
$total_patients = $conn->query("SELECT COUNT(*) as count FROM patients p JOIN users u ON p.user_id = u.id WHERE u.is_active = 1")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) >= CURDATE()")->fetch_assoc()['count'];
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'];

// Get recent activities from audit log
$recent_activities = $conn->query("SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.timestamp DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="admin-dashboard.php">Dashboard</a>
                <a href="manage-users.php">Manage Users</a>
                <a href="manage-appointments.php">Manage Appointments</a>
                <a href="audit-logs.php">Audit Logs</a>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo $total_users; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Doctors</h3>
                    <p class="stat-number"><?php echo $total_doctors; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Patients</h3>
                    <p class="stat-number"><?php echo $total_patients; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Upcoming Appointments</h3>
                    <p class="stat-number"><?php echo $total_appointments; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Appointments</h3>
                    <p class="stat-number"><?php echo $pending_appointments; ?></p>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="recent-activities">
                    <h3>Recent Activities</h3>
                    <ul>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                            <li>
                                <span class="activity-user"><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></span>
                                <span class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></span>
                                <span class="activity-time"><?php echo date('M d, H:i', strtotime($activity['timestamp'])); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <a href="manage-users.php" class="btn btn-secondary">Add New User</a>
                    <a href="manage-appointments.php" class="btn btn-secondary">Schedule Appointment</a>
                    <a href="reports.php" class="btn btn-secondary">Generate Report</a>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="appointmentChart"></canvas>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
    <script>
        // Sample chart for appointments
        const ctx = document.getElementById('appointmentChart').getContext('2d');
        const appointmentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Appointments',
                    data: [12, 19, 3, 5, 2, 3, 9],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Weekly Appointments'
                    }
                }
            }
        });
    </script>
</body>
</html>
