<?php
require_once __DIR__ . '/config/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (empty($token) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $auth = new Auth($conn);
        $result = $auth->resetPassword($token, $password);
        if ($result['success']) {
            $success = 'Your password has been reset successfully. <a href="' . SITE_URL . 'Signin/index.php">Login here</a>.';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Jirani</title>
    <link rel="stylesheet" href="css/Signin_style.css">
    <style>
        .reset-container {
            max-width: 400px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .reset-container h2 {
            color: #2A5C3D;
            margin-bottom: 20px;
        }

        .reset-container input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .reset-container button {
            width: 100%;
            background: #2A5C3D;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
        }

        .reset-container .error {
            color: #c00;
            margin-bottom: 10px;
        }

        .reset-container .success {
            color: #080;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <h2>Reset Your Password</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>