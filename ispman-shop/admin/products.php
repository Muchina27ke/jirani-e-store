<?php
require_once 'auth_guard.php';
require_once '../config/config.php';

$pageTitle  = 'Products';
$activePage = 'products';

$pdo = getPDO();

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Fetch product for edit if ?edit=ID
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

// Filters
$filterCat   = $_GET['category'] ?? '';
$filterSearch= $_GET['q'] ?? '';
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 20;
$offset      = ($page - 1) * $perPage;

$sql    = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE 1=1";
$params = [];
if ($filterCat) {
    $sql .= " AND p.category_id = :cid";
    $params[':cid'] = $filterCat;
}
if ($filterSearch) {
    $sql .= " AND (p.name LIKE :q OR p.brand LIKE :q OR p.sku LIKE :q)";
    $params[':q'] = '%' . $filterSearch . '%';
}
$countSql = str_replace("SELECT p.*, c.name as category_name", "SELECT COUNT(*)", $sql);
$total    = (int)$pdo->prepare($countSql)->execute($params) ? $pdo->prepare($countSql)->execute($params) : 0;

// Re-run count properly
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = max(1, ceil($totalProducts / $perPage));

$sql .= " ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'layout.php';
?>

<!-- Toolbar -->
<div class="admin-card" style="margin-bottom:20px;">
  <div class="admin-card-header">
    <div class="admin-toolbar" style="flex:1;">
      <form method="GET" action="products.php" style="display:contents;">
        <div class="admin-search">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" name="q" placeholder="Search products…" value="<?= htmlspecialchars($filterSearch) ?>">
        </div>
        <select name="category" class="admin-filter-select" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $filterCat == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline" style="padding:8px 16px;font-size:0.82rem;">Filter</button>
      </form>
    </div>
    <button class="btn btn-primary" style="padding:9px 18px;font-size:0.85rem;" onclick="openModal('addProductModal')">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Add Product
    </button>
  </div>
</div>

<!-- Products table -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      All Products
    </div>
    <span style="font-size:0.8rem;color:var(--muted);"><?= $totalProducts ?> total</span>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table" id="productsTable">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Brand</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Featured</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px;">No products found</td></tr>
        <?php else: ?>
        <?php foreach ($products as $p):
          $imgs = json_decode($p['images'] ?? '[]', true);
          $img  = $imgs[0] ?? null;
        ?>
        <tr id="productRow-<?= $p['id'] ?>">
          <td>
            <div class="product-thumb-cell">
              <div class="product-thumb">
                <?php if ($img): ?>
                  <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                <?php else: ?>
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <?php endif; ?>
              </div>
              <div>
                <div class="product-thumb-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="product-thumb-sku"><?= htmlspecialchars($p['sku'] ?? '—') ?></div>
              </div>
            </div>
          </td>
          <td style="color:var(--muted);"><?= htmlspecialchars($p['category_name']) ?></td>
          <td><?= htmlspecialchars($p['brand'] ?? '—') ?></td>
          <td><strong>KES <?= number_format($p['price'], 0) ?></strong></td>
          <td>
            <span class="<?= $p['stock_qty'] == 0 ? 'stock-zero' : ($p['stock_qty'] < 5 ? 'stock-low' : 'stock-ok') ?>">
              <?= (int)$p['stock_qty'] ?>
            </span>
          </td>
          <td>
            <?php if ($p['featured']): ?>
              <span class="status-badge status-paid">Yes</span>
            <?php else: ?>
              <span style="color:var(--muted);font-size:0.8rem;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="action-btns">
              <button class="action-btn edit" title="Edit"
                      onclick="openEditModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button class="action-btn delete" title="Delete"
                      onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="admin-pagination">
    <span>Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalProducts) ?> of <?= $totalProducts ?></span>
    <div class="pagination-btns">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($filterSearch) ?>&category=<?= urlencode($filterCat) ?>"
           class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ============================================================
     ADD PRODUCT MODAL
     ============================================================ -->
<div class="admin-modal-overlay" id="addProductModal">
  <div class="admin-modal">
    <div class="admin-modal-header">
      <span class="admin-modal-title">Add New Product</span>
      <button class="admin-modal-close" data-close-modal="addProductModal">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="addProductForm" enctype="multipart/form-data">
      <div class="admin-modal-body">
        <div class="form-grid" style="gap:14px;">
          <div class="form-group full">
            <label class="form-label">Product Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-input" placeholder="e.g. MikroTik hAP ax²" required>
          </div>
          <div class="form-group">
            <label class="form-label">Category <span class="required">*</span></label>
            <select name="category_id" class="form-select" required>
              <option value="">— Select —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Brand</label>
            <input type="text" name="brand" class="form-input" placeholder="e.g. MikroTik">
          </div>
          <div class="form-group">
            <label class="form-label">Price (KES) <span class="required">*</span></label>
            <input type="number" name="price" class="form-input" placeholder="0" min="0" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Stock Qty</label>
            <input type="number" name="stock_qty" class="form-input" placeholder="0" min="0" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" class="form-input" placeholder="e.g. MT-HAPAX2-001">
          </div>
          <div class="form-group">
            <label class="form-label">Weight (kg)</label>
            <input type="number" name="weight_kg" class="form-input" placeholder="0.000" step="0.001" min="0">
          </div>
          <div class="form-group full">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input" rows="3" placeholder="Product description…" style="resize:vertical;"></textarea>
          </div>
          <div class="form-group full">
            <label class="form-label">
              <input type="checkbox" name="featured" value="1" style="margin-right:6px;accent-color:var(--orange);">
              Mark as Featured
            </label>
          </div>
          <div class="form-group full">
            <label class="form-label">Product Image</label>
            <div class="image-upload-zone">
              <input type="file" name="image" accept="image/*">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <p><span>Click to upload</span> or drag & drop</p>
              <p style="font-size:0.72rem;margin-top:4px;">PNG, JPG, WebP — max 5MB</p>
            </div>
            <div class="image-preview-grid"></div>
          </div>
        </div>
      </div>
      <div class="admin-modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal="addProductModal" style="padding:9px 20px;font-size:0.85rem;">Cancel</button>
        <button type="submit" class="btn btn-primary" style="padding:9px 20px;font-size:0.85rem;">Add Product</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     EDIT PRODUCT MODAL
     ============================================================ -->
<div class="admin-modal-overlay" id="editProductModal">
  <div class="admin-modal">
    <div class="admin-modal-header">
      <span class="admin-modal-title">Edit Product</span>
      <button class="admin-modal-close" data-close-modal="editProductModal">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="editProductForm" enctype="multipart/form-data">
      <input type="hidden" name="product_id" id="editProductId">
      <div class="admin-modal-body">
        <div class="form-grid" style="gap:14px;">
          <div class="form-group full">
            <label class="form-label">Product Name <span class="required">*</span></label>
            <input type="text" name="name" id="editName" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Category <span class="required">*</span></label>
            <select name="category_id" id="editCategoryId" class="form-select" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Brand</label>
            <input type="text" name="brand" id="editBrand" class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Price (KES) <span class="required">*</span></label>
            <input type="number" name="price" id="editPrice" class="form-input" min="0" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Stock Qty</label>
            <input type="number" name="stock_qty" id="editStock" class="form-input" min="0">
          </div>
          <div class="form-group">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" id="editSku" class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Weight (kg)</label>
            <input type="number" name="weight_kg" id="editWeight" class="form-input" step="0.001" min="0">
          </div>
          <div class="form-group full">
            <label class="form-label">Description</label>
            <textarea name="description" id="editDesc" class="form-input" rows="3" style="resize:vertical;"></textarea>
          </div>
          <div class="form-group full">
            <label class="form-label">
              <input type="checkbox" name="featured" id="editFeatured" value="1" style="margin-right:6px;accent-color:var(--orange);">
              Mark as Featured
            </label>
          </div>
          <div class="form-group full">
            <label class="form-label">Replace Image (optional)</label>
            <div class="image-upload-zone">
              <input type="file" name="image" accept="image/*">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <p><span>Click to upload</span> new image</p>
            </div>
            <div class="image-preview-grid"></div>
          </div>
        </div>
      </div>
      <div class="admin-modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal="editProductModal" style="padding:9px 20px;font-size:0.85rem;">Cancel</button>
        <button type="submit" class="btn btn-primary" style="padding:9px 20px;font-size:0.85rem;">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php include 'layout_end.php'; ?>

<script>
/* ── Add product ── */
document.getElementById('addProductForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fd  = new FormData(this);
  fd.append('action', 'add');
  submitProductForm(fd, 'add', this);
});

/* ── Edit product ── */
document.getElementById('editProductForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  fd.append('action', 'edit');
  submitProductForm(fd, 'edit', this);
});

function submitProductForm(fd, action, form) {
  const btn = form.querySelector('[type="submit"]');
  btn.disabled = true;
  btn.textContent = 'Saving…';

  fetch('api/product_actions.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      btn.disabled = false;
      btn.textContent = action === 'add' ? 'Add Product' : 'Save Changes';
      if (d.success) {
        adminToast(d.message, 'success');
        closeModal(action === 'add' ? 'addProductModal' : 'editProductModal');
        setTimeout(() => location.reload(), 800);
      } else {
        adminToast(d.message || 'Error', 'error');
      }
    })
    .catch(() => { btn.disabled = false; adminToast('Network error', 'error'); });
}

/* ── Open edit modal ── */
function openEditModal(p) {
  document.getElementById('editProductId').value  = p.id;
  document.getElementById('editName').value        = p.name;
  document.getElementById('editCategoryId').value  = p.category_id;
  document.getElementById('editBrand').value       = p.brand || '';
  document.getElementById('editPrice').value       = p.price;
  document.getElementById('editStock').value       = p.stock_qty;
  document.getElementById('editSku').value         = p.sku || '';
  document.getElementById('editWeight').value      = p.weight_kg || '';
  document.getElementById('editDesc').value        = p.description || '';
  document.getElementById('editFeatured').checked  = p.featured == 1;
  openModal('editProductModal');
}

/* ── Delete product ── */
function deleteProduct(id, name) {
  adminConfirm(`Delete "${name}"? This cannot be undone.`, () => {
    fetch('api/product_actions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete&product_id=${id}`
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        document.getElementById('productRow-' + id)?.remove();
        adminToast('Product deleted');
      } else {
        adminToast(d.message || 'Delete failed', 'error');
      }
    });
  });
}

<?php if ($editProduct): ?>
// Auto-open edit modal if ?edit= in URL
openEditModal(<?= json_encode($editProduct) ?>);
<?php endif; ?>
</script>
