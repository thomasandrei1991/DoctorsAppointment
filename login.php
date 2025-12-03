<?php
session_start();
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, password, role, is_active FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if ($user['is_active'] && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                log_action($user['id'], 'login', 'User logged in successfully');

                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin-dashboard.php');
                        break;
                    case 'doctor':
                        header('Location: doctor-dashboard.php');
                        break;
                    case 'patient':
                        header('Location: patient-dashboard.php');
                        break;
                }
                exit();
            } else {
                $error = 'Invalid username/email or password.';
                log_action(null, 'failed_login', "Failed login attempt for: $username");
            }
        } else {
            $error = 'Invalid username/email or password.';
            log_action(null, 'failed_login', "Failed login attempt for: $username");
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
    <title>Login - Aventus Clinic</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="login-container">
        <h2>Login to Aventus Clinic</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>
