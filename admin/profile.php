<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}
$pageTitle = 'Profile';
$currentPage = 'profile';
ob_start();
?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Existing profile content here -->
        <?php
        // Get admin user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if (!$admin) {
            header('Location: ../auth.php?action=login');
            exit;
        }

        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'update_profile':
                    $name = trim($_POST['name']);
                    $email = trim($_POST['email']);
                    $phone = trim($_POST['phone']);

                    if (empty($name)) {
                        $_SESSION['error'] = 'Name is required';
                    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $_SESSION['error'] = 'Invalid email format';
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $name, $email, $phone, $_SESSION['user_id']);

                        if ($stmt->execute()) {
                            $_SESSION['success'] = 'Profile updated successfully!';
                        } else {
                            $_SESSION['error'] = 'Failed to update profile';
                        }
                    }
                    break;

                case 'change_password':
                    $current_password = $_POST['current_password'];
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];

                    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                        $_SESSION['error'] = 'All password fields are required';
                    } elseif (!password_verify($current_password, $admin['password'])) {
                        $_SESSION['error'] = 'Current password is incorrect';
                    } elseif ($new_password !== $confirm_password) {
                        $_SESSION['error'] = 'New passwords do not match';
                    } elseif (strlen($new_password) < 6) {
                        $_SESSION['error'] = 'Password must be at least 6 characters long';
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);

                        if ($stmt->execute()) {
                            $_SESSION['success'] = 'Password changed successfully!';
                        } else {
                            $_SESSION['error'] = 'Failed to change password';
                        }
                    }
                    break;
            }

            header('Location: profile.php');
            exit;
        }

        // Get admin activity
        $stmt = $conn->prepare("
        SELECT * FROM system_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $recent_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get login history
        $stmt = $conn->prepare("
        SELECT * FROM login_history 
        WHERE user_id = ? 
        ORDER BY login_time DESC 
        LIMIT 10
    ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $login_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Admin Profile</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Profile</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?php echo $_SESSION['success'];
                            unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?php echo $_SESSION['error'];
                            unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Profile Information -->
                        <div class="col-md-4">
                            <div class="card card-primary card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <img class="profile-user-img img-fluid img-circle"
                                            src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=007bff&color=fff&size=128"
                                            alt="User profile picture">
                                    </div>
                                    <h3 class="profile-username text-center">
                                        <?php echo htmlspecialchars($admin['name']); ?></h3>
                                    <p class="text-muted text-center">Administrator</p>
                                    <ul class="list-group list-group-unbordered mb-3">
                                        <li class="list-group-item">
                                            <b>Email</b> <a
                                                class="float-right"><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></a>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Phone</b> <a
                                                class="float-right"><?php echo htmlspecialchars($admin['phone'] ?? 'N/A'); ?></a>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Member Since</b> <a
                                                class="float-right"><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></a>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Last Login</b> <a
                                                class="float-right"><?php echo $admin['last_login'] ? date('M j, Y H:i', strtotime($admin['last_login'])) : 'Never'; ?></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Forms -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header p-2">
                                    <ul class="nav nav-pills">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#profile" data-toggle="tab">Profile</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#password" data-toggle="tab">Password</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#activity" data-toggle="tab">Activity</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Profile Tab -->
                                        <div class="active tab-pane" id="profile">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="update_profile">
                                                <div class="form-group">
                                                    <label for="name">Full Name</label>
                                                    <input type="text" class="form-control" id="name" name="name"
                                                        value="<?php echo htmlspecialchars($admin['name']); ?>"
                                                        required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="email">Email Address</label>
                                                    <input type="email" class="form-control" id="email" name="email"
                                                        value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label for="phone">Phone Number</label>
                                                    <input type="text" class="form-control" id="phone" name="phone"
                                                        value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save mr-2"></i>Update Profile
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Password Tab -->
                                        <div class="tab-pane" id="password">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="change_password">
                                                <div class="form-group">
                                                    <label for="current_password">Current Password</label>
                                                    <input type="password" class="form-control" id="current_password"
                                                        name="current_password" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="new_password">New Password</label>
                                                    <input type="password" class="form-control" id="new_password"
                                                        name="new_password" required>
                                                    <small class="form-text text-muted">Password must be at least 6
                                                        characters long</small>
                                                </div>
                                                <div class="form-group">
                                                    <label for="confirm_password">Confirm New Password</label>
                                                    <input type="password" class="form-control" id="confirm_password"
                                                        name="confirm_password" required>
                                                </div>
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="fas fa-key mr-2"></i>Change Password
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Activity Tab -->
                                        <div class="tab-pane" id="activity">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5><i class="fas fa-history mr-2"></i>Recent Activity</h5>
                                                    <div class="timeline">
                                                        <?php foreach ($recent_activity as $activity): ?>
                                                            <div class="time-label">
                                                                <span
                                                                    class="bg-red"><?php echo date('M j, Y', strtotime($activity['created_at'])); ?></span>
                                                            </div>
                                                            <div>
                                                                <i class="fas fa-user bg-blue"></i>
                                                                <div class="timeline-item">
                                                                    <span class="time">
                                                                        <i class="fas fa-clock"></i>
                                                                        <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                                                                    </span>
                                                                    <h3 class="timeline-header">
                                                                        <?php echo htmlspecialchars($activity['action']); ?>
                                                                    </h3>
                                                                    <div class="timeline-body">
                                                                        <?php echo htmlspecialchars($activity['details']); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5><i class="fas fa-sign-in-alt mr-2"></i>Login History</h5>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Time</th>
                                                                    <th>IP Address</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($login_history as $login): ?>
                                                                    <tr>
                                                                        <td><?php echo date('M j, Y', strtotime($login['login_time'])); ?>
                                                                        </td>
                                                                        <td><?php echo date('H:i', strtotime($login['login_time'])); ?>
                                                                        </td>
                                                                        <td><?php echo htmlspecialchars($login['ip_address']); ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-shield-alt mr-2"></i>
                                        Security Settings
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="two_factor"
                                                        checked disabled>
                                                    <label class="custom-control-label" for="two_factor">Two-Factor
                                                        Authentication</label>
                                                </div>
                                                <small class="form-text text-muted">Currently not available</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="session_timeout" checked disabled>
                                                    <label class="custom-control-label" for="session_timeout">Session
                                                        Timeout</label>
                                                </div>
                                                <small class="form-text text-muted">Automatically log out after
                                                    inactivity</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <script>
            // Password confirmation validation
            document.getElementById('confirm_password').addEventListener('input', function () {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = this.value;

                if (newPassword !== confirmPassword) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });

            document.getElementById('new_password').addEventListener('input', function () {
                const confirmPassword = document.getElementById('confirm_password');
                if (confirmPassword.value) {
                    confirmPassword.dispatchEvent(new Event('input'));
                }
            });
        </script>
        <?php
        $content = ob_get_clean();
        require_once 'layout.php';
        ?>