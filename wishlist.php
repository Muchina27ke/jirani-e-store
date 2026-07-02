<?php
require_once __DIR__ . '/config/config.php';

$db = getDbConnection();
require_once __DIR__ . '/includes/Wishlist.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'login.php?redirect=wishlist.php');
    exit;
}

$wishlist = new Wishlist($db, $_SESSION['user_id']);
$wishlistResult = $wishlist->getWishlistItems();
$wishlistItems = $wishlistResult['success'] ? $wishlistResult['items'] : [];

$pageTitle = "My Wishlist - Jirani";
$currentPage = 'wishlist';
$additionalCSS = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'];
?>
<?php require_once __DIR__ . '/navbar.php'; ?>

<style>
:root {
    --jirani-primary: #2A5C3D;
    --jirani-secondary: #FFA726;
    --jirani-white: #FFFFFF;
    --jirani-gray: #333333;
}
html, body {
    height: 100%;
    min-height: 100%;
    width: 100%;
    overflow-x: hidden;
}
.wishlist-container {
    min-height: 100vh;
    padding-bottom: 120px;
    box-sizing: border-box;
    overflow-x: hidden;
}
.wishlist-header {
    background: linear-gradient(135deg, var(--jirani-primary), #1e4a2f);
    color: var(--jirani-white);
    padding: 2rem 0;
    margin-bottom: 2rem;
}
.wishlist-header h1 {
    font-weight: 700;
    margin: 0;
}
.wishlist-item {
    background: var(--jirani-white);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid rgba(42, 92, 61, 0.1);
    margin-bottom: 1.5rem;
    padding: 1.5rem;
}
.wishlist-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
.add-to-cart-btn {
    background: linear-gradient(135deg, var(--jirani-primary), #1e4a2f);
    border: none;
    color: white;
    border-radius: 6px;
    padding: 8px 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}
.add-to-cart-btn:hover {
    background: linear-gradient(135deg, var(--jirani-secondary), #ff9800);
    color: var(--jirani-gray);
}
.empty-wishlist {
    background: var(--jirani-white);
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.empty-wishlist-icon {
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
    .wishlist-container {
        padding: 1rem 0;
    }
    .wishlist-header {
        padding: 1.5rem 0;
    }
    .wishlist-header h1 {
        font-size: 1.8rem;
    }
    .product-image {
        height: 100px;
    }
}
</style>

<div class="wishlist-container">
    <div class="wishlist-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-heart me-3"></i>My Wishlist</h1>
                    <p class="mb-0">Save your favorite products for later</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-heart me-2"></i><?php echo count($wishlistItems); ?> Items
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <?php if (empty($wishlistItems)): ?>
            <div class="empty-wishlist">
                <div class="empty-wishlist-icon">
                    <i class="fas fa-heart-broken"></i>
                </div>
                <h3 class="text-muted mb-3">Your wishlist is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any products to your wishlist yet.</p>
                <a href="<?php echo SITE_URL; ?>" class="continue-shopping-btn">
                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="wishlist-item h-100">
                            <div class="row align-items-center">
                                <div class="col-4">
                                    <?php 
                                    $hasImage = !empty($item['image_url']) && trim($item['image_url']) !== '';
                                    if ($hasImage): 
                                    ?>
                                        <img src="<?php echo '/jirani/' . htmlspecialchars($item['image_url']); ?>"
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
                                <div class="col-8">
                                    <h5 class="product-title mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="vendor-name mb-1">
                                        <i class="fas fa-store me-1"></i>
                                        <?php echo htmlspecialchars($item['vendor_name']); ?>
                                    </p>
                                    <div class="price-tag mb-2">
                                        KSh <?php echo number_format($item['price'], 2); ?>
                                    </div>
                                    <p class="mb-2 text-muted" style="font-size:0.95rem;">
                                        <?php echo htmlspecialchars(substr($item['description'], 0, 80)) . '...'; ?>
                                    </p>
                                    <div class="d-flex gap-2">
                                        <button class="btn add-to-cart-btn flex-fill" data-product-id="<?php echo $item['product_id']; ?>">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                        <button class="btn remove-btn flex-fill remove-from-wishlist" data-product-id="<?php echo $item['product_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle Add to Cart
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.dataset.productId;
                fetch('api/cart/add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateCartCount(data.cart_count);
                            location.reload();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while adding item to cart');
                    });
            });
        });

        // Handle Remove from Wishlist
        document.querySelectorAll('.remove-from-wishlist').forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.dataset.productId;
                const card = this.closest('.col-md-6, .col-lg-4');
                if (confirm('Are you sure you want to remove this item from your wishlist?')) {
                    fetch('api/wishlist/remove.php', {
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
                                card.remove();
                                if (document.querySelectorAll('.wishlist-item').length === 0) {
                                    location.reload();
                                }
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while removing item from wishlist');
                        });
                }
            });
        });

        function updateCartCount(count) {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
            }
        }
    });
</script>

<?php include 'footer.php'; ?>