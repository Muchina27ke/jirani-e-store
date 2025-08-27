<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../signin/index.php");
  exit();
}

if (isset($_GET['product_id'])) {
  $user_id = $_SESSION['user_id'];
  $product_id = $_GET['product_id'];

  $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $user_id, $product_id);
  
  if ($stmt->execute()) {
    header("Location: wishlist.php?status=removed");
  } else {
    header("Location: wishlist.php?status=error");
  }
  $stmt->close();
}
$conn->close();
?>