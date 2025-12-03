<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get report data
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch_assoc()['count'];
$total_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.is_active = 1")->fetch_assoc()['count'];
$total_patients = $conn->query("SELECT COUNT(*) as count FROM patients p JOIN users u ON p.user_id = u.id WHERE u.is_active = 1")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$completed_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'")->fetch_assoc()['count'];
$cancelled_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'cancelled'")->fetch_assoc()['count'];

// Get monthly appointment data for the last 6 months
$monthly_data = $conn->query("
    SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, COUNT(*) as count
    FROM appointments
    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Reports</h1>
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
                    <h3>Total Appointments</h3>
                    <p class="stat-number"><?php echo $total_appointments; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Completed Appointments</h3>
                    <p class="stat-number"><?php echo $completed_appointments; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Cancelled Appointments</h3>
                    <p class="stat-number"><?php echo $cancelled_appointments; ?></p>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
        </main>
    </div>

    <script src="js/scripts.js"></script>
    <script>
        // Monthly appointments chart
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $labels = [];
                    while ($row = $monthly_data->fetch_assoc()) {
                        $labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                    }
                    echo implode(',', $labels);
                    $monthly_data->data_seek(0); // Reset pointer
                    ?>
                ],
                datasets: [{
                    label: 'Appointments',
                    data: [
                        <?php
                        $data = [];
                        while ($row = $monthly_data->fetch_assoc()) {
                            $data[] = $row['count'];
                        }
                        echo implode(',', $data);
                        ?>
                    ],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Appointments (Last 6 Months)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
