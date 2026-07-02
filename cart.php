<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'Signin/index.php?redirect=cart.php');
    exit;
}

// Get cart items
$cartObj    = new Cart($conn, $_SESSION['user_id']);
$cartResult = $cartObj->getCartItems();
$cartItems  = $cartResult['success'] ? $cartResult['items'] : [];
$total      = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

// Group by vendor
$vendorGroups = [];
foreach ($cartItems as $item) {
    $vid = $item['vendor_id'] ?? 0;
    if (!isset($vendorGroups[$vid])) {
        $vendorGroups[$vid] = ['name' => $item['vendor_name'] ?? $item['business_name'] ?? 'Vendor', 'items' => []];
    }
    $vendorGroups[$vid]['items'][] = $item;
}

$pageTitle   = 'Shopping Cart';
$currentPage = 'cart';
?>
<?php require_once __DIR__ . '/navbar.php'; ?>

<style>
.cart-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
    padding: 40px 0 80px;
}

.cart-items-card {
    background: white;
    border-radius: var(--j-radius-lg);
    border: 1px solid var(--j-border-light);
    box-shadow: var(--j-shadow-card);
    overflow: hidden;
}

.cart-vendor-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 24px;
    background: var(--j-primary-glass);
    border-bottom: 1px solid var(--j-border-light);
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--j-primary);
}

.cart-row {
    display: grid;
    grid-template-columns: 88px 1fr auto;
    gap: 18px;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--j-border-light);
    transition: var(--j-transition);
}

.cart-row:last-child { border-bottom: none; }
.cart-row:hover { background: var(--j-bg); }

.cart-row-removing {
    opacity: 0;
    transform: translateX(-20px);
    transition: all 0.35s ease;
}

.cart-img {
    width: 88px;
    height: 80px;
    border-radius: var(--j-radius);
    object-fit: cover;
    background: var(--j-bg);
    display: block;
    flex-shrink: 0;
}

.cart-img-placeholder {
    width: 88px;
    height: 80px;
    border-radius: var(--j-radius);
    background: linear-gradient(135deg, #e8f0ea, #d4e8d9);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--j-primary);
    font-size: 1.8rem;
    opacity: 0.5;
}

.cart-row-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
}

.cart-row-price {
    font-family: var(--j-font-heading);
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--j-primary);
}

.cart-remove-btn {
    background: none;
    border: none;
    color: var(--j-text-light);
    font-size: 0.85rem;
    cursor: pointer;
    padding: 4px;
    transition: var(--j-transition);
    display: flex;
    align-items: center;
    gap: 4px;
}
.cart-remove-btn:hover { color: var(--j-danger); }

@media(max-width:768px) {
    .cart-layout { grid-template-columns: 1fr; }
    .j-order-summary { position: static; }
    .cart-row { grid-template-columns: 72px 1fr; grid-template-rows: auto auto; }
    .cart-row-right { flex-direction: row; align-items: center; justify-content: space-between; }
}
</style>

<!-- Page header -->
<div class="j-page-header">
    <div class="j-container">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
            <a href="<?php echo SITE_URL; ?>index.php" style="color:rgba(255,255,255,0.7);font-size:0.85rem;">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
        <h1><i class="fas fa-shopping-cart" style="margin-right:12px;opacity:.8;"></i>Shopping Cart
            <?php if (!empty($cartItems)): ?>
            <span style="font-size:1rem;font-weight:400;opacity:0.7;margin-left:8px;">(<?php echo count($cartItems); ?> items)</span>
            <?php endif; ?>
        </h1>
    </div>
</div>

<div class="j-container">
    <?php if (empty($cartItems)): ?>
    <!-- Empty cart state -->
    <div style="padding:80px 0;">
        <div class="j-empty-state">
            <div class="j-empty-icon"><i class="fas fa-shopping-cart"></i></div>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any products yet.<br>Explore local products from your community!</p>
            <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
                <a href="<?php echo SITE_URL; ?>index.php" class="j-btn j-btn-primary j-btn-lg">
                    <i class="fas fa-seedling"></i> Start Shopping
                </a>
                <a href="<?php echo SITE_URL; ?>fruits.php" class="j-btn j-btn-ghost j-btn-lg">
                    <i class="fas fa-apple-alt"></i> Browse Fruits
                </a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="cart-layout">
        <!-- Left: Cart items -->
        <div>
            <?php foreach ($vendorGroups as $vid => $vg): ?>
            <div class="cart-items-card" style="margin-bottom:16px;" id="vendor-group-<?php echo $vid; ?>">
                <div class="cart-vendor-header">
                    <i class="fas fa-store"></i>
                    <?php echo htmlspecialchars($vg['name']); ?>
                </div>
                <?php foreach ($vg['items'] as $item):
                    $imgSrc = !empty($item['image']) ? SITE_URL . 'uploads/' . htmlspecialchars($item['image']) : '';
                ?>
                <div class="cart-row" id="cartItem<?php echo $item['product_id']; ?>">
                    <!-- Image -->
                    <?php if ($imgSrc): ?>
                    <img class="cart-img" src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php else: ?>
                    <div class="cart-img-placeholder"><i class="fas fa-seedling"></i></div>
                    <?php endif; ?>

                    <!-- Info -->
                    <div>
                        <div style="font-weight:700;font-size:0.95rem;color:var(--j-text);margin-bottom:4px;">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </div>
                        <div style="font-size:0.8rem;color:var(--j-text-muted);margin-bottom:12px;">
                            KSh <?php echo number_format($item['price'], 2); ?> each
                        </div>
                        <!-- Qty control -->
                        <div class="j-qty-control">
                            <button class="j-qty-btn" onclick="changeQty(<?php echo $item['product_id']; ?>, -1)" title="Decrease">
                                <i class="fas fa-minus" style="font-size:0.7rem;"></i>
                            </button>
                            <input type="number" class="j-qty-input" id="qty<?php echo $item['product_id']; ?>"
                                   value="<?php echo $item['quantity']; ?>" min="1"
                                   onchange="setQty(<?php echo $item['product_id']; ?>, this.value)">
                            <button class="j-qty-btn" onclick="changeQty(<?php echo $item['product_id']; ?>, 1)" title="Increase">
                                <i class="fas fa-plus" style="font-size:0.7rem;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Price + remove -->
                    <div class="cart-row-right">
                        <div class="cart-row-price" id="itemTotal<?php echo $item['product_id']; ?>">
                            KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                        <button class="cart-remove-btn" onclick="removeItem(<?php echo $item['product_id']; ?>)">
                            <i class="fas fa-trash-alt"></i> Remove
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <!-- Clear cart -->
            <div style="display:flex;justify-content:flex-end;padding:8px 0;">
                <button class="cart-remove-btn" onclick="clearCart()" style="font-size:0.85rem;padding:8px 14px;border-radius:8px;border:1px solid var(--j-border);">
                    <i class="fas fa-trash"></i> Clear Entire Cart
                </button>
            </div>
        </div>

        <!-- Right: Summary -->
        <div class="j-order-summary">
            <div class="j-summary-header">Order Summary</div>
            <div class="j-summary-body" id="summaryBody">
                <div class="j-summary-row">
                    <span>Subtotal (<span id="itemCount"><?php echo count($cartItems); ?></span> items)</span>
                    <span class="amount" id="subtotalDisplay">KSh <?php echo number_format($total, 2); ?></span>
                </div>
                <div class="j-summary-row">
                    <span>Delivery</span>
                    <span class="amount" style="color:var(--j-success);">Negotiated</span>
                </div>
                <div class="j-summary-row total">
                    <span>Total</span>
                    <span class="amount" id="totalDisplay">KSh <?php echo number_format($total, 2); ?></span>
                </div>
            </div>
            <div style="padding:0 20px 20px;display:flex;flex-direction:column;gap:10px;">
                <a href="<?php echo SITE_URL; ?>checkout.php" class="j-btn j-btn-primary j-btn-full j-btn-lg">
                    <i class="fas fa-lock"></i> Proceed to Checkout
                </a>
                <a href="<?php echo SITE_URL; ?>index.php" class="j-btn j-btn-ghost j-btn-full">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
            <div style="padding:0 20px 20px;">
                <div style="font-size:0.78rem;color:var(--j-text-muted);display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-shield-alt" style="color:var(--j-success);"></i>
                    Escrow protected checkout
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const SITE_URL = <?php echo json_encode(SITE_URL); ?>;

// Item prices for client-side total recalculation
const itemPrices = <?php echo json_encode(
    array_combine(
        array_column($cartItems, 'product_id'),
        array_column($cartItems, 'price')
    )
); ?>;

let itemQtys = <?php echo json_encode(
    array_combine(
        array_column($cartItems, 'product_id'),
        array_column($cartItems, 'quantity')
    )
); ?>;

function changeQty(productId, delta) {
    const input = document.getElementById('qty' + productId);
    const newQty = Math.max(1, parseInt(input.value) + delta);
    input.value = newQty;
    setQty(productId, newQty);
}

function setQty(productId, qty) {
    qty = Math.max(1, parseInt(qty));
    document.getElementById('qty' + productId).value = qty;

    fetch(SITE_URL + 'api/cart/update-quantity.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: qty })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            itemQtys[productId] = qty;
            // Update item total
            const price = itemPrices[productId] || 0;
            const el = document.getElementById('itemTotal' + productId);
            if (el) el.textContent = 'KSh ' + (price * qty).toLocaleString('en-KE', { minimumFractionDigits: 2 });
            updateSummary();
            updateCartCount(d.cart_count);
        } else {
            jToast(d.message || 'Could not update quantity', 'error');
        }
    })
    .catch(() => jToast('Network error', 'error'));
}

function removeItem(productId) {
    const row = document.getElementById('cartItem' + productId);
    if (row) row.classList.add('cart-row-removing');

    fetch(SITE_URL + 'api/cart/remove.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            setTimeout(() => {
                if (row) row.remove();
                delete itemQtys[productId];
                delete itemPrices[productId];
                updateSummary();
                updateCartCount(d.cart_count);
                jToast('Item removed from cart', 'success');
                // If cart is empty, reload to show empty state
                if (Object.keys(itemQtys).length === 0) {
                    setTimeout(() => location.reload(), 600);
                }
            }, 350);
        } else {
            if (row) row.classList.remove('cart-row-removing');
            jToast(d.message || 'Could not remove item', 'error');
        }
    })
    .catch(() => {
        if (row) row.classList.remove('cart-row-removing');
        jToast('Network error', 'error');
    });
}

function clearCart() {
    if (!confirm('Clear your entire cart? This cannot be undone.')) return;
    fetch(SITE_URL + 'api/cart/clear.php', { method: 'POST' })
    .then(r => r.json())
    .then(d => {
        if (d.success) { location.reload(); }
        else jToast(d.message || 'Could not clear cart', 'error');
    })
    .catch(() => jToast('Network error', 'error'));
}

function updateSummary() {
    let total = 0;
    let count = 0;
    for (const [pid, qty] of Object.entries(itemQtys)) {
        total += (itemPrices[pid] || 0) * qty;
        count++;
    }
    const fmt = n => 'KSh ' + n.toLocaleString('en-KE', { minimumFractionDigits: 2 });
    const subtotal = document.getElementById('subtotalDisplay');
    const totalEl  = document.getElementById('totalDisplay');
    const countEl  = document.getElementById('itemCount');
    if (subtotal) subtotal.textContent = fmt(total);
    if (totalEl)  totalEl.textContent  = fmt(total);
    if (countEl)  countEl.textContent  = count;
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>