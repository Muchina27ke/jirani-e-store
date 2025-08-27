<?php
require_once dirname(__DIR__) . "/config/config.php";

// Process form submission when method POST is used
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Retrieve and trim form input values
  $firstname = trim($_POST["firstname"]);
  $lastname = trim($_POST["lastname"]);
  $email = trim($_POST["email"]);
  $password = trim($_POST["password"]);
  $confirm_password = trim($_POST["confirm_password"]);
  $role = trim($_POST["role"]);
  $phone = trim($_POST["phone"]);

  // Initialize an error variable
  $error = "";

  // Validate that all fields are filled
  if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password) || empty($role) || empty($phone)) {
    $error = "Please fill in all fields.";
  }
  // Validate password match
  elseif ($password !== $confirm_password) {
    $error = "Passwords do not match.";
  }
  // Validate role
  elseif (!in_array($role, ['customer', 'vendor'])) {
    $error = "Invalid role selected.";
  } else {
    // Combine first and last names into one name field
    $name = $firstname . " " . $lastname;

    // Use the User class to register the user
    if ($user->register($name, $phone, $email, $password, $role)) {
      // Registration successful; redirect to the login page
      header("Location: ../Signin/index.php?registered=true");
      exit();
    } else {
      $error = "Registration failed. Email or phone may already be in use.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Jirani - Register</title>
  <!-- Link to external style sheet -->
  <link rel="stylesheet" href="../css/Register_style.css">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

  <!-- Registration Form -->
  <form class="form" method="POST" action="">
    <p class="title">Register </p>
    <p class="message">Signup now and get full access to our app.</p>
    <?php if (!empty($error)): ?>
      <div class="error-message" style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="flex">
      <label>
        <input required type="text" name="firstname" class="input" placeholder="" value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
        <span>Firstname</span>
      </label>
      <label>
        <input required type="text" name="lastname" class="input" placeholder="" value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">
        <span>Lastname</span>
      </label>
    </div>

    <label>
      <input required type="email" name="email" class="input" placeholder="" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      <span>Email</span>
    </label>

    <label>
      <input required type="password" name="password" class="input" placeholder="">
      <span>Password</span>
    </label>
    <label>
      <input required type="password" name="confirm_password" class="input" placeholder="">
      <span>Confirm Password</span>
    </label>

    <!-- Role Selection Dropdown -->
    <label for="role" class="input-label">
      Select
      <span class="visually-hidden">Role</span>
    </label>
    <div class="input-group">
      <select id="role" name="role" class="input-field" required aria-label="Select your role">
        <option value="" disabled <?php echo empty($_POST['role']) ? 'selected' : ''; ?>>Select your role</option>
        <option value="customer" <?php echo ($_POST['role'] ?? '') === 'customer' ? 'selected' : ''; ?>>Customer</option>
        <option value="vendor" <?php echo ($_POST['role'] ?? '') === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
      </select>
    </div>

    <label>
      <input required type="tel" name="phone" class="input" placeholder="" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
      <span>Phone Number (M-Pesa)</span>
    </label>

    <button type="submit" class="submit">Register</button>
    <p class="signin">Already have an account? <a href="../Signin/index.php">Login</a></p>
    <div class="signin">
      <p><a href="../index.php">JIRANI</a></p>
    </div>
  </form>
</body>

</html>

