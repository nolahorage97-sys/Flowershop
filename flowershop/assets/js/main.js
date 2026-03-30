// ============================================================
//  BloomTrack — Main JavaScript
// ============================================================

// ── Active nav link highlight ────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const current = window.location.pathname;
  document.querySelectorAll('.nav-item').forEach(link => {
    if (link.getAttribute('href') && current.endsWith(link.getAttribute('href').split('/').pop())) {
      link.classList.add('active');
    }
  });

  // Auto-dismiss flash messages
  const flash = document.querySelector('.flash');
  if (flash) setTimeout(() => flash.remove(), 5000);

  // Quantity controls in cart
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const input = this.parentElement.querySelector('input[type="number"]');
      if (!input) return;
      let val = parseInt(input.value) || 1;
      if (this.dataset.action === 'inc') val++;
      if (this.dataset.action === 'dec') val = Math.max(1, val - 1);
      input.value = val;
      input.dispatchEvent(new Event('change'));
    });
  });

  // Confirm delete prompts
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  // Close sidebar on overlay click (mobile)
  document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && sidebar.classList.contains('open') &&
        !sidebar.contains(e.target) &&
        !e.target.closest('.menu-toggle')) {
      sidebar.classList.remove('open');
    }
  });
});

// ── Cart (localStorage) ──────────────────────────────
const Cart = {
  key: 'bloomtrack_cart',

  get() {
    try { return JSON.parse(localStorage.getItem(this.key)) || []; }
    catch { return []; }
  },

  save(cart) { localStorage.setItem(this.key, JSON.stringify(cart)); },

  add(id, name, price, max) {
    const cart = this.get();
    const idx  = cart.findIndex(i => i.id === id);
    if (idx > -1) {
      cart[idx].qty = Math.min(cart[idx].qty + 1, max);
    } else {
      cart.push({ id, name, price: parseFloat(price), qty: 1, max: parseInt(max) });
    }
    this.save(cart);
    this.updateBadge();
    return cart;
  },

  remove(id) {
    const cart = this.get().filter(i => i.id !== id);
    this.save(cart);
    this.updateBadge();
    return cart;
  },

  // NEW: Update quantity by delta (for + and - buttons)
  updateQtyByDelta(id, delta) {
    const cart = this.get();
    const item = cart.find(i => i.id === id);
    if (item) {
      const newQty = Math.max(1, Math.min(item.qty + delta, item.max));
      item.qty = newQty;
      this.save(cart);
      this.updateBadge();
    }
    return cart;
  },

  // Keep original for compatibility
  updateQty(id, qty) {
    const cart = this.get();
    const item = cart.find(i => i.id === id);
    if (item) { 
      item.qty = Math.max(1, Math.min(qty, item.max)); 
      this.save(cart); 
      this.updateBadge();
    }
    return cart;
  },

  clear() { 
    localStorage.removeItem(this.key); 
    this.updateBadge(); 
  },

  total() { 
    return this.get().reduce((s, i) => s + i.price * i.qty, 0); 
  },

  count() { 
    return this.get().reduce((s, i) => s + i.qty, 0); 
  },

  updateBadge() {
    const badge = document.querySelector('.cart-badge');
    if (badge) badge.textContent = this.count();
  }
};

// ── Chart helper (uses Chart.js if available) ────────
function renderLineChart(canvasId, labels, datasets, options = {}) {
  const ctx = document.getElementById(canvasId);
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      plugins: { legend: { display: datasets.length > 1 } },
      scales: { y: { beginAtZero: true } },
      ...options
    }
  });
}

function renderBarChart(canvasId, labels, data, color = '#059669') {
  const ctx = document.getElementById(canvasId);
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ data, backgroundColor: color + '99', borderColor: color, borderWidth: 2, borderRadius: 6 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
}

function renderDoughnut(canvasId, labels, data, colors) {
  const ctx = document.getElementById(canvasId);
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2 }] },
    options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
  });
}

// ── Form helpers ─────────────────────────────────────
function showLoading(btn) {
  btn.dataset.origText = btn.innerHTML;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
  btn.disabled = true;
}
function hideLoading(btn) {
  btn.innerHTML = btn.dataset.origText;
  btn.disabled = false;
}
