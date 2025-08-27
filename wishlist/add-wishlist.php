<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../signin/index.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_SESSION['user_id'];
  $product_id = $_POST['product_id'];

  // Check if item already exists in wishlist
  $check_sql = "SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?";
  $stmt = $conn->prepare($check_sql);
  $stmt->bind_param("ii", $user_id, $product_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    // Insert new wishlist item
    $insert_sql = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
      header("Location: wishlist.php?status=added");
    } else {
      header("Location: wishlist.php?status=error");
    }
  } else {
    header("Location: wishlist.php?status=exists");
  }
  $stmt->close();
}
$conn->close();
?>