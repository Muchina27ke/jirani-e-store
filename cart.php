<?php
require_once __DIR__ . '/config/config.php';
$db = getDbConnection();
require_once __DIR__ . '/includes/Cart.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=cart.php');
    exit;
}
$cart = new Cart($db, $_SESSION['user_id']);
$cartResult = $cart->getCartItems();
$cartItems = [];
$total = 0;
if ($cartResult['success']) {
    $cartItems = $cartResult['items'];
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Navigation.php';
$auth = new Auth($db);
$currentUser = $auth->isLoggedIn() ? $auth->getUser() : null;
$navigation = new Navigation($db, $currentUser, 'cart');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Jirani</title>
    <link rel="icon" type="image/jpeg" href="title_logo.jpg">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/About.css">
    <!-- Footer CSS -->
    <link rel="stylesheet" href="css/footer.css">
</head>
<body>
<?php echo $navigation->renderCustomerNav(); ?>
<!-- (rest of original cart HTML and logic follows) -->
<style>
    /* Only keep cart-specific styles here, remove theme variables */
    .cart-container {
        min-height: 100vh;
        padding-bottom: 120px;
        /* Prevent footer overlap */
        box-sizing: border-box;
        overflow-x: hidden;
    }

    .cart-header {
        background: linear-gradient(135deg, var(--jirani-primary), #1e4a2f);
        color: var(--jirani-white);
        padding: 2rem 0;
        margin-bottom: 2rem;
    }

    .cart-header h1 {
        font-weight: 700;
        margin: 0;
    }

    .cart-item {
        background: var(--jirani-white);
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 1px solid rgba(42, 92, 61, 0.1);
    }

    .cart-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .product-image {
        border-radius: 8px;
        object-fit: cover;
        width: 100%;
        height: 120px;
    }

    .product-title {
        color: var(--jirani-primary);
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    .vendor-name {
        color: var(--jirani-gray);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .price-tag {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 4px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 1rem;
    }

    .quantity-controls {
        border: 2px solid rgba(42, 92, 61, 0.2);
        border-radius: 8px;
        overflow: hidden;
    }

    .quantity-btn {
        background: var(--jirani-primary);
        border: none;
        color: white;
        padding: 8px 12px;
        transition: all 0.3s ease;
    }

    .quantity-btn:hover {
        background: var(--jirani-secondary);
        color: var(--jirani-gray);
    }

    .quantity-input {
        border: none;
        text-align: center;
        font-weight: 600;
        color: var(--jirani-primary);
    }

    .remove-btn {
        background: linear-gradient(135deg, #dc3545, #c82333);
        border: none;
        color: white;
        border-radius: 6px;
        padding: 8px 12px;
        transition: all 0.3s ease;
    }

    .remove-btn:hover {
        background: linear-gradient(135deg, #c82333, #bd2130);
        transform: scale(1.05);
    }

    .order-summary-card {
        background: var(--jirani-white);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(42, 92, 61, 0.1);
        position: sticky;
        top: 20px;
    }

    .summary-title {
        color: var(--jirani-primary);
        font-weight: 700;
        border-bottom: 2px solid rgba(42, 92, 61, 0.1);
        padding-bottom: 1rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(42, 92, 61, 0.05);
    }

    .summary-total {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--jirani-primary);
        border-top: 2px solid rgba(42, 92, 61, 0.1);
        padding-top: 1rem;
    }

    .checkout-btn {
        background: linear-gradient(135deg, var(--jirani-primary), #1e4a2f);
        border: none;
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 1rem;
    }

    .checkout-btn:hover {
        background: linear-gradient(135deg, var(--jirani-secondary), #ff9800);
        color: var(--jirani-gray);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 167, 38, 0.3);
    }

    .empty-cart {
        background: var(--jirani-white);
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .empty-cart-icon {
        font-size: 4rem;
        color: var(--jirani-secondary);
        margin-bottom: 1rem;
    }

    .continue-shopping-btn {
        background: var(--jirani-primary);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
        margin-top: 1rem;
    }

    .continue-shopping-btn:hover {
        background: var(--jirani-secondary);
        color: var(--jirani-gray);
        text-decoration: none;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .cart-container {
            padding: 1rem 0;
        }

        .cart-header {
            padding: 1.5rem 0;
        }

        .cart-header h1 {
            font-size: 1.8rem;
        }

        .product-image {
            height: 100px;
        }

        .order-summary-card {
            position: static;
            margin-top: 2rem;
        }
    }
</style>

<div class="cart-container">
    <div class="cart-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-shopping-cart me-3"></i>Shopping Cart</h1>
                    <p class="mb-0">Review and manage your items</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-box me-2"></i><?php echo count($cartItems); ?> Items
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h3 class="text-muted mb-3">Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="index.php" class="continue-shopping-btn">
                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item mb-4 p-4" data-cart-item-id="<?php echo $item['product_id']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-2 col-4">
                                        <?php 
                                        $hasImage = !empty($item['image']) && trim($item['image']) !== '';
                                        if ($hasImage): 
                                        ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="product-image d-flex align-items-center justify-content-center bg-light text-muted" style="display: none;">
                                                <div class="text-center">
                                                    <i class="fas fa-image fa-2x mb-1 opacity-50"></i>
                                                    <div style="font-size: 0.8rem;">No image available</div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="product-image d-flex align-items-center justify-content-center bg-light text-muted">
                                                <div class="text-center">
                                                    <i class="fas fa-image fa-2x mb-1 opacity-50"></i>
                                                    <div style="font-size: 0.8rem;">No image available</div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 col-8">
                                        <h5 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="vendor-name mb-2">
                                            <i class="fas fa-store me-1"></i>
                                            <?php echo htmlspecialchars($item['vendor_name']); ?>
                                        </p>
                                        <div class="price-tag">
                                            KSh <?php echo number_format($item['price'], 2); ?> each
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mt-3 mt-md-0">
                                        <div class="quantity-controls">
                                            <div class="input-group">
                                                <button class="btn quantity-btn" data-action="decrease">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="form-control quantity-input"
                                                    value="<?php echo $item['quantity']; ?>" min="1"
                                                    max="<?php echo $item['stock']; ?>">
                                                <button class="btn quantity-btn" data-action="increase">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-6 text-end mt-3 mt-md-0">
                                        <div class="price-tag">
                                            KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12 text-end mt-3 mt-md-0">
                                        <button class="btn remove-btn" title="Remove item">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="order-summary-card p-4">
                        <h4 class="summary-title mb-4">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h4>

                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span>KSh <?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Delivery Fee</span>
                            <span class="text-muted">Calculated at checkout</span>
                        </div>

                        <div class="summary-item summary-total">
                            <span>Total</span>
                            <span>KSh <?php echo number_format($total, 2); ?></span>
                        </div>

                        <a href="checkout.php" class="btn checkout-btn">
                            <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                        </a>

                        <div class="text-center mt-3">
                            <a href="index.php" class="text-muted text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle quantity changes
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function () {
                const input = this.parentElement.querySelector('.quantity-input');
                const currentValue = parseInt(input.value);
                const action = this.dataset.action;
                if (action === 'increase' && currentValue < parseInt(input.max)) {
                    input.value = currentValue + 1;
                    updateQuantity(input);
                } else if (action === 'decrease' && currentValue > 1) {
                    input.value = currentValue - 1;
                    updateQuantity(input);
                }
            });
        });

        // Handle direct quantity input
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function () {
                updateQuantity(this);
            });
        });

        // Handle remove item
        document.querySelectorAll('.remove-btn').forEach(button => {
            button.addEventListener('click', function () {
                const cartItem = this.closest('.cart-item');
                const productId = cartItem.dataset.cartItemId;
                if (confirm('Are you sure you want to remove this item?')) {
                    removeItem(productId, cartItem);
                }
            });
        });

        function updateQuantity(input) {
            const cartItem = input.closest('.cart-item');
            const productId = cartItem.dataset.cartItemId;
            const quantity = parseInt(input.value);

            fetch('api/cart/update-quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCount(data.cart_count);
                        location.reload(); // Refresh to update prices
                    } else {
                        alert(data.message);
                        location.reload(); // Refresh to reset quantity
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating quantity');
                    location.reload();
                });
        }

        function removeItem(productId, cartItemElement) {
            fetch('api/cart/remove.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCount(data.cart_count);
                        cartItemElement.remove();
                        // If no items left, show empty cart message
                        if (document.querySelectorAll('.cart-item').length === 0) {
                            location.reload();
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing item');
                });
        }

        function updateCartCount(count) {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
            }
        }
    });
</script>

<?php include 'footer.php'; ?>