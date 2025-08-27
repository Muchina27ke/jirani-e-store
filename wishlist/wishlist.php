<?php
  $base_url = './';  // we’re in the project root
?>
<?php
session_start();
include '../config.php';
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../Signin/index.php");
  exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Wishlist - Jirani</title>
  <link rel="stylesheet" href="../css/wishlist-style.css">
  <link
    rel="stylesheet"
    href="<?= $base_url ?>css/footer.css"
  >
</head>
<body>
     <!-- Navbar -->
         
     <?php include '../navbar.php'; ?>


  <div class="wishlist-container">
    <h2>My Wishlist</h2>

    <?php
    // Handle status messages
    if (isset($_GET['status'])) {
      $messages = [
        'added' => 'Item added to wishlist!',
        'removed' => 'Item removed from wishlist.',
        'exists' => 'Item already in wishlist.',
        'error' => 'An error occurred.'
      ];
      echo '<div class="alert">'.$messages[$_GET['status']].'</div>';
    }
    ?>

    <div class="wishlist-items">
      <?php
      $sql = "SELECT p.* FROM products p
              JOIN wishlist w ON p.id = w.product_id
              WHERE w.user_id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          echo '<div class="wishlist-item">';
          echo '<img src="'.$row['image_url'].'" alt="'.$row['name'].'">';
          echo '<h3>'.$row['name'].'</h3>';
          echo '<p>Ksh '.number_format($row['price'], 2).'</p>';
          echo '<a href="remove-wishlist.php?product_id='.$row['id'].'">Remove</a>';
          echo '</div>';
        }
      } else {
        echo '<p>Your wishlist is empty.</p>';
      }
      $stmt->close();
      ?>
    </div>
  </div>

   <!-- Footer -->
   <?php 
        $base_url = './'; // Adjust based on your directory structure
        include '../footer.php'; 
    ?>
</body>
</body>
</html>