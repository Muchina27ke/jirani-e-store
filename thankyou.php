<?php
$pageTitle = 'Order Confirmed';
include 'includes/header.php';
?>
<div class="container py-5 text-center">
    <div class="mb-4">
        <i class="fas fa-check-circle fa-4x text-success"></i>
    </div>
    <h1 class="mb-3">Thank You for Your Order!</h1>
    <p class="lead mb-4">Your order has been placed successfully. You will receive a confirmation email and SMS
        shortly.<br>We appreciate your business!</p>
    <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-lg">Return to Shop</a>
</div>
<?php include 'footer.php'; ?>