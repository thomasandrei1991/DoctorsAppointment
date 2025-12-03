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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $target_user_id = intval($_POST['user_id']);

        switch ($action) {
            case 'activate':
                $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                $stmt->bind_param("i", $target_user_id);
                if ($stmt->execute()) {
                    log_action($user_id, 'activate_user', "Activated user ID: $target_user_id");
                    $success = 'User activated successfully.';
                }
                $stmt->close();
                break;

            case 'deactivate':
                $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $stmt->bind_param("i", $target_user_id);
                if ($stmt->execute()) {
                    log_action($user_id, 'deactivate_user', "Deactivated user ID: $target_user_id");
                    $success = 'User deactivated successfully.';
                }
                $stmt->close();
                break;

            case 'delete':
                if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
                    // Start transaction
                    $conn->begin_transaction();

                    try {
                        // Delete from related tables first
                        $tables = ['patients', 'doctors', 'appointments', 'messages', 'audit_logs', 'notifications'];
                        foreach ($tables as $table) {
                            $column = ($table === 'audit_logs' || $table === 'notifications') ? 'user_id' : 'user_id';
                            $stmt = $conn->prepare("DELETE FROM $table WHERE $column = ?");
                            $stmt->bind_param("i", $target_user_id);
                            $stmt->execute();
                            $stmt->close();
                        }

                        // Delete user
                        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->bind_param("i", $target_user_id);
                        $stmt->execute();
                        $stmt->close();

                        $conn->commit();
                        log_action($user_id, 'delete_user', "Deleted user ID: $target_user_id");
                        $success = 'User deleted successfully.';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = 'Failed to delete user.';
                    }
                } else {
                    // Show confirmation form
                    echo '<div class="modal" id="delete-modal" style="display: block;">
                            <div class="modal-content">
                                <span class="close" onclick="closeModal(\'delete-modal\')">&times;</span>
                                <h3>Confirm Deletion</h3>
                                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                                <form method="POST" action="">
                                    <input type="hidden" name="user_id" value="' . $target_user_id . '">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="confirm_delete" value="yes">
                                    <button type="submit" class="btn btn-danger">Yes, Delete User</button>
                                    <button type="button" class="btn btn-secondary" onclick="closeModal(\'delete-modal\')">Cancel</button>
                                </form>
                            </div>
                          </div>';
                }
                break;
        }
    }
}

// Get all users with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';

$query = "SELECT u.*, COUNT(a.id) as appointment_count FROM users u LEFT JOIN appointments a ON u.id = a.patient_id OR u.id = (SELECT user_id FROM doctors WHERE id = a.doctor_id) WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

if ($role_filter) {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
$count_params = [];
$count_types = '';

if ($search) {
    $count_query .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param]);
    $count_types .= 'ssss';
}

if ($role_filter) {
    $count_query .= " AND u.role = ?";
    $count_params[] = $role_filter;
    $count_types .= 's';
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Manage Users</h1>
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
                            <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <select name="role">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="doctor" <?php echo $role_filter === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                                <option value="patient" <?php echo $role_filter === 'patient' ? 'selected' : ''; ?>>Patient</option>
                            </select>
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
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Appointments</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                <td><?php echo $user['appointment_count']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php if ($user['is_active']): ?>
                                            <button type="submit" name="action" value="deactivate" class="btn btn-secondary btn-sm" onclick="return confirm('Deactivate this user?')">Deactivate</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="activate" class="btn btn-primary btn-sm" onclick="return confirm('Activate this user?')">Activate</button>
                                        <?php endif; ?>
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user permanently?')">Delete</button>
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
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" class="btn btn-secondary <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>
