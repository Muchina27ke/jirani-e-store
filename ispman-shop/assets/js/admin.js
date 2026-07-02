/* ============================================================
   AfriGear.tech — Admin Panel JS
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {

  /* ── Sidebar toggle ── */
  const sidebar    = document.getElementById('adminSidebar');
  const main       = document.getElementById('adminMain');
  const toggleBtn  = document.getElementById('sidebarToggleBtn');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      const isMobile = window.innerWidth <= 900;
      if (isMobile) {
        sidebar.classList.toggle('open');
      } else {
        sidebar.classList.toggle('collapsed');
        main?.classList.toggle('expanded');
      }
    });

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', e => {
      if (window.innerWidth <= 900 &&
          sidebar.classList.contains('open') &&
          !sidebar.contains(e.target) &&
          !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  /* ── Toast ── */
  window.adminToast = function(msg, type = 'success') {
    const el  = document.getElementById('toast');
    const txt = document.getElementById('toastMsg');
    if (!el || !txt) return;
    txt.textContent = msg;
    el.className = 'toast ' + type + ' show';
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 3500);
  };

  /* ── Modal helpers ── */
  window.openModal = function(id) {
    document.getElementById(id)?.classList.add('show');
    document.body.style.overflow = 'hidden';
  };
  window.closeModal = function(id) {
    document.getElementById(id)?.classList.remove('show');
    document.body.style.overflow = '';
  };

  // Close modal on overlay click
  document.querySelectorAll('.admin-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  // Close buttons
  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
  });

  // ESC key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.admin-modal-overlay.show').forEach(m => closeModal(m.id));
      document.querySelectorAll('.confirm-overlay.show').forEach(m => m.classList.remove('show'));
    }
  });

  /* ── Confirm dialog ── */
  window.adminConfirm = function(message, onConfirm) {
    let overlay = document.getElementById('confirmOverlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'confirmOverlay';
      overlay.className = 'confirm-overlay';
      overlay.innerHTML = `
        <div class="confirm-box">
          <h3>Confirm Action</h3>
          <p id="confirmMsg"></p>
          <div class="confirm-actions">
            <button class="btn btn-outline" id="confirmCancel" style="padding:9px 20px;font-size:0.85rem;">Cancel</button>
            <button class="btn btn-primary" id="confirmOk" style="padding:9px 20px;font-size:0.85rem;background:#ef4444;border-color:#ef4444;">Delete</button>
          </div>
        </div>`;
      document.body.appendChild(overlay);
      document.getElementById('confirmCancel').addEventListener('click', () => overlay.classList.remove('show'));
      overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('show'); });
    }
    document.getElementById('confirmMsg').textContent = message;
    overlay.classList.add('show');
    const okBtn = document.getElementById('confirmOk');
    const newOk = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOk, okBtn);
    newOk.addEventListener('click', () => { overlay.classList.remove('show'); onConfirm(); });
  };

  /* ── Image upload preview ── */
  document.querySelectorAll('.image-upload-zone').forEach(zone => {
    const input   = zone.querySelector('input[type="file"]');
    const preview = zone.nextElementSibling;

    if (!input) return;

    ['dragover','dragleave','drop'].forEach(evt => {
      zone.addEventListener(evt, e => {
        e.preventDefault();
        zone.classList.toggle('dragover', evt === 'dragover');
        if (evt === 'drop') handleFiles(e.dataTransfer.files);
      });
    });

    input.addEventListener('change', () => handleFiles(input.files));

    function handleFiles(files) {
      if (!preview) return;
      Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
          const item = document.createElement('div');
          item.className = 'image-preview-item';
          item.innerHTML = `<img src="${e.target.result}" alt="preview">
            <button type="button" class="image-preview-remove" aria-label="Remove">×</button>`;
          item.querySelector('.image-preview-remove').addEventListener('click', () => item.remove());
          preview.appendChild(item);
        };
        reader.readAsDataURL(file);
      });
    }
  });

  /* ── Live table search ── */
  document.querySelectorAll('[data-search-table]').forEach(input => {
    const tableId = input.dataset.searchTable;
    const table   = document.getElementById(tableId);
    if (!table) return;
    input.addEventListener('input', () => {
      const q = input.value.toLowerCase();
      table.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  });

  /* ── Status select auto-submit ── */
  document.querySelectorAll('.status-update-select').forEach(sel => {
    sel.addEventListener('change', function() {
      const orderId = this.dataset.orderId;
      const status  = this.value;
      fetch('api/order_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_status&order_id=${orderId}&status=${encodeURIComponent(status)}`
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          adminToast('Order status updated to ' + status);
          // Update badge in same row
          const badge = this.closest('tr')?.querySelector('.status-badge');
          if (badge) {
            badge.className = 'status-badge status-' + status;
            badge.innerHTML = `<span></span>${status.charAt(0).toUpperCase() + status.slice(1)}`;
          }
        } else {
          adminToast(d.message || 'Update failed', 'error');
        }
      })
      .catch(() => adminToast('Network error', 'error'));
    });
  });

});
