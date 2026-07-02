/* ============================================================
   AfriGear.tech — Shared Cart JS
   Provides: AfriCart.add(), AfriCart.updateBadge(), AfriCart.toast()
   Used by: index.php, shop.php, product.php, cart.php
   ============================================================ */

const AfriCart = (() => {

  /* Detect path depth to build correct API URL */
  function apiUrl() {
    const path = window.location.pathname;
    // If we're inside /pages/, go up one level
    if (path.includes('/pages/')) return '../api/cart.php';
    return 'api/cart.php';
  }

  /* Update all cart count badges on the page */
  function updateBadge(count) {
    document.querySelectorAll('.cart-count').forEach(el => {
      el.textContent = count;
      el.style.display = count > 0 ? 'flex' : 'none';
    });
    // Keep localStorage in sync for index.php (no session)
    localStorage.setItem('cartCount', count);
  }

  /* Show a toast notification */
  function toast(message, type = 'success') {
    const el  = document.getElementById('toast');
    const msg = document.getElementById('toastMsg');
    if (!el || !msg) return;

    msg.textContent = message;
    el.className = `toast ${type}`;

    // Update icon
    const icon = el.querySelector('.toast-icon');
    if (icon) {
      icon.innerHTML = type === 'success'
        ? '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>'
        : '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>';
    }

    el.classList.add('show');
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.remove('show'), 3000);
  }

  /* Add a product to cart via AJAX */
  function add(productId, qty = 1, btnEl = null) {
    if (!productId) return;

    // Button loading state
    if (btnEl) {
      btnEl.disabled = true;
      btnEl._original = btnEl.innerHTML;
      btnEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="spin">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
      </svg> Adding...`;
    }

    fetch(apiUrl(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add&product_id=${encodeURIComponent(productId)}&qty=${encodeURIComponent(qty)}`
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        updateBadge(data.count);
        toast('Added to cart — ' + (btnEl?.dataset?.productName || 'Product'), 'success');
        if (btnEl) {
          btnEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg> Added!`;
          btnEl.style.background   = '#16a34a';
          btnEl.style.borderColor  = '#16a34a';
          setTimeout(() => {
            btnEl.innerHTML         = btnEl._original;
            btnEl.style.background  = '';
            btnEl.style.borderColor = '';
            btnEl.disabled          = false;
          }, 2000);
        }
      } else {
        toast(data.message || 'Could not add to cart', 'error');
        if (btnEl) { btnEl.innerHTML = btnEl._original; btnEl.disabled = false; }
      }
    })
    .catch(() => {
      toast('Network error — please try again', 'error');
      if (btnEl) { btnEl.innerHTML = btnEl._original; btnEl.disabled = false; }
    });
  }

  /* Initialise badge from server on page load */
  function init() {
    fetch(apiUrl() + '?action=get')
      .then(r => r.json())
      .then(data => { if (data.success) updateBadge(data.count); })
      .catch(() => {
        // Fallback to localStorage
        const n = parseInt(localStorage.getItem('cartCount') || '0', 10);
        updateBadge(n);
      });
  }

  return { add, updateBadge, toast, init };
})();

/* ---- Auto-wire "Add to Cart" buttons on any page ---- */
document.addEventListener('DOMContentLoaded', () => {
  AfriCart.init();

  document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    // Skip if already wired (product.php wires its own)
    if (btn.dataset.wired) return;
    btn.dataset.wired = '1';
    btn.addEventListener('click', () => {
      AfriCart.add(btn.dataset.productId, 1, btn);
    });
  });
});

/* Spin animation for loading state */
const spinStyle = document.createElement('style');
spinStyle.textContent = `
  @keyframes spin { to { transform: rotate(360deg); } }
  .spin { animation: spin 0.8s linear infinite; }
`;
document.head.appendChild(spinStyle);
