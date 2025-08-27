<?php
require_once dirname(__DIR__) . "/config/config.php";

$error = "";
$success = "";

// Add a variable to hold a JS toast message
$toast_message = '';
$toast_type = '';

// Check if user is already logged in
if ($user->is_logged_in()) {
  // Redirect based on role
  if ($user->is_admin()) {
    header("Location: ../admin/index.php");
  } elseif ($user->is_vendor()) {
    header("Location: ../seller/dashboard.php");
  } else {
    header("Location: ../index.php");
  }
  exit();
}

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
  $success = "Registration successful! Please login with your credentials.";
}

// Handle forgot password POST
$forgot_error = '';
$forgot_success = '';
if (isset($_POST['forgot_password_submit'])) {
  $identifier = trim($_POST['forgot_email_or_phone'] ?? '');
  if (empty($identifier)) {
    $forgot_error = 'Please enter your email or phone number.';
    $toast_message = $forgot_error;
    $toast_type = 'error';
  } else {
    $auth = new Auth($conn);
    $result = $auth->generatePasswordResetToken($identifier);
    if ($result['success']) {
      $forgot_success = 'Password reset instructions have been sent to your email (if it exists in our system).';
      $toast_message = $forgot_success;
      $toast_type = 'success';
    } else {
      $forgot_error = $result['message'];
      $toast_message = $forgot_error;
      $toast_type = 'error';
    }
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email_or_phone'], $_POST['password'])) {
  $email_or_phone = trim($_POST["email_or_phone"]);
  $password = trim($_POST["password"]);

  if (empty($email_or_phone) || empty($password)) {
    $error = "Email/Phone and password are required.";
    $toast_message = $error;
    $toast_type = 'error';
  } else {
    // Use the User class to login
    if ($user->login($email_or_phone, $password)) {
      // Redirect based on role
      if ($user->is_admin()) {
        header("Location: ../admin/index.php");
      } elseif ($user->is_vendor()) {
        header("Location: ../seller/dashboard.php");
      } else {
        header("Location: ../index.php");
      }
      exit();
    } else {
      $error = "Invalid email/phone or password.";
      $toast_message = $error;
      $toast_type = 'error';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jirani - Login</title>
  <link rel="stylesheet" href="../css/Signin_style.css">
</head>

<body>

  <!-- login form -->
  <div class="form-container">
    <p class="title">Login To Jirani</p>

    <?php if (!empty($error)): ?>
      <div class="error-message"
        style="color: red; margin-bottom: 10px; padding: 10px; background-color: #ffe6e6; border: 1px solid #ff0000; border-radius: 5px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="success-message"
        style="color: green; margin-bottom: 10px; padding: 10px; background-color: #e6ffe6; border: 1px solid #00ff00; border-radius: 5px;">
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>

    <form class="form" method="POST" action="">
      <div class="input-group">
        <label for="email_or_phone"></label>
        <input type="text" name="email_or_phone" id="email_or_phone" placeholder="Email or Phone Number"
          value="<?php echo htmlspecialchars($_POST['email_or_phone'] ?? ''); ?>" required>
      </div>
      <div class="input-group">
        <label for="password"></label>
        <input type="password" name="password" id="password" placeholder="Password" required>
        <div class="forgot">
          <a href="#" onclick="document.getElementById('forgot-modal').style.display='block';return false;">Forgot
            Password ?</a>
        </div>
      </div>
      <div class="remember-me">
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember">Remember me</label>
      </div>
      <button type="submit" class="sign">Sign in</button>
    </form>

    <div class="social-message">
      <div class="line"></div>
      <p class="message">Login with social accounts</p>
      <div class="line"></div>
    </div>

    <div>
      <!-- Social login icons -->
      <ul class="example-2">
        <li class="icon-content">
          <a href="https://linkedin.com/" aria-label="LinkedIn" data-social="linkedin">
            <div class="filled"></div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-linkedin"
              viewBox="0 0 16 16" xml:space="preserve">
              <path
                d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"
                fill="currentColor"></path>
            </svg>
          </a>
          <div class="tooltip">LinkedIn</div>
        </li>
        <li class="icon-content">
          <a href="https://www.github.com/" aria-label="GitHub" data-social="github">
            <div class="filled"></div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-github"
              viewBox="0 0 16 16" xml:space="preserve">
              <path
                d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8"
                fill="currentColor"></path>
            </svg>
          </a>
          <div class="tooltip">GitHub</div>
        </li>
        <li class="icon-content">
          <a href="https://www.instagram.com/" aria-label="Instagram" data-social="instagram">
            <div class="filled"></div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-instagram"
              viewBox="0 0 16 16" xml:space="preserve">
              <path
                d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334"
                fill="currentColor"></path>
            </svg>
          </a>
          <div class="tooltip">Instagram</div>
        </li>
      </ul>
    </div>

    <p class="signup">Don't have an account?
      <a rel="noopener noreferrer" href="../Register/index.php" class="">Sign up</a>
    </p>

    <div class="signin">
      <p><a href="../index.php">JIRANI</a></p>
    </div>
  </div>

  <!-- Forgot Password Modal -->
  <div id="forgot-modal"
    style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
    <div
      style="background:#fff;padding:30px 20px;border-radius:10px;max-width:350px;margin:100px auto;position:relative;">
      <button onclick="document.getElementById('forgot-modal').style.display='none';"
        style="position:absolute;top:10px;right:10px;background:none;border:none;font-size:20px;">&times;</button>
      <h3 style="margin-bottom:15px;">Forgot Password</h3>
      <?php if (!empty($forgot_error)): ?>
        <div style="color:red;margin-bottom:10px;"> <?php echo htmlspecialchars($forgot_error); ?> </div>
      <?php endif; ?>
      <?php if (!empty($forgot_success)): ?>
        <div style="color:green;margin-bottom:10px;"> <?php echo htmlspecialchars($forgot_success); ?> </div>
      <?php endif; ?>
      <form method="POST" action="">
        <input type="text" name="forgot_email_or_phone" placeholder="Email or Phone Number"
          style="width:100%;padding:10px;margin-bottom:10px;border-radius:5px;border:1px solid #ccc;" required>
        <button type="submit" name="forgot_password_submit"
          style="width:100%;background:#2A5C3D;color:#fff;padding:10px;border:none;border-radius:5px;">Send Reset
          Link</button>
      </form>
    </div>
  </div>

  <div id="toast"
    style="display:none;position:fixed;top:30px;right:30px;z-index:9999;min-width:250px;padding:16px 24px;border-radius:8px;font-size:1rem;box-shadow:0 2px 8px rgba(0,0,0,0.15);background:#2A5C3D;color:#fff;transition:all 0.3s;opacity:0;">
  </div>
  <script>
    // Close modal on outside click
    window.onclick = function (event) {
      var modal = document.getElementById('forgot-modal');
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }

    function showToast(message, type) {
      var toast = document.getElementById('toast');
      toast.textContent = message;
      toast.style.background = type === 'success' ? '#2A5C3D' : (type === 'error' ? '#c00' : '#FFA726');
      toast.style.color = '#fff';
      toast.style.display = 'block';
      toast.style.opacity = 1;
      setTimeout(function () {
        toast.style.opacity = 0;
        setTimeout(function () { toast.style.display = 'none'; }, 400);
      }, 4000);
    }

    <?php if (!empty($toast_message)): ?>
      document.addEventListener('DOMContentLoaded', function () {
        showToast(<?php echo json_encode($toast_message); ?>, <?php echo json_encode($toast_type); ?>);
      });
    <?php endif; ?>
  </script>
</body>

</html>