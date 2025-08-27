<?php
function renderProductCard($product)
{
    $isLoggedIn = isset($_SESSION['user_id']);
    $wishlistClass = $isLoggedIn ? 'wishlist-btn' : 'login-required';
    $cartClass = $isLoggedIn ? 'add-to-cart-btn' : 'login-required';
    
    // Handle image display with fallback
    $imageUrl = $product['image'] ?? $product['image_url'] ?? '';
    
    // If we have a local image, prepend /jirani/ prefix
    if (!empty($imageUrl) && !str_starts_with($imageUrl, 'http')) {
        $imageUrl = '/jirani/' . $imageUrl;
    }
    
    $showPlaceholder = empty($imageUrl) || (!str_starts_with($imageUrl, 'http') && !str_starts_with($imageUrl, '/jirani/'));
    ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100 product-card">
            <?php if ($showPlaceholder): ?>
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted" style="height: 200px;">
                    <div class="text-center">
                        <i class="fas fa-image fa-3x mb-2 opacity-50"></i>
                        <div>No image available</div>
                    </div>
                </div>
            <?php else: ?>
                <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="card-img-top"
                    alt="<?php echo htmlspecialchars($product['name']); ?>" 
                    style="height: px; object-fit: cover;"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                <p class="card-text text-muted">
                    Vendor: <?php echo htmlspecialchars($product['vendor_name']); ?>
                </p>
                <p class="card-text">
                    <?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)) . '...'; ?>
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">KSh <?php echo number_format($product['price'], 2); ?></h5>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary <?php echo $cartClass; ?>"
                            data-product-id="<?php echo $product['id']; ?>" 
                            <?php echo !$isLoggedIn ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : ''; ?>>
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        <button class="btn btn-outline-danger <?php echo $wishlistClass; ?>"
                            data-product-id="<?php echo $product['id']; ?>" 
                            <?php echo !$isLoggedIn ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : ''; ?>>
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Add login modal once per page
function renderLoginModal() {
    if (!isset($_SESSION['user_id'])) {
        ?>
        <!-- Login Modal -->
        <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="loginModalLabel">Login Required</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Please login to add items to your cart or wishlist.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="login.php" class="btn btn-primary">Login</a>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
}
?>