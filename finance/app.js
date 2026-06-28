/**
 * School Finance Management System — Core JS
 */

/* ── Dark Mode ──────────────────────────────────────────── */
const ThemeManager = {
  key: 'sf_theme',
  init() {
    const saved = localStorage.getItem(this.key) || 'light';
    this.apply(saved);
  },
  toggle() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    this.apply(next);
    localStorage.setItem(this.key, next);
  },
  apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const btn = document.getElementById('themeToggle');
    if (btn) btn.innerHTML = `<i class="ri-${theme === 'dark' ? 'sun' : 'moon'}-line"></i>`;
  }
};

/* ── Toast Notifications ────────────────────────────────── */
const Toast = {
  show(msg, type = 'info', duration = 3500) {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    const icons = { success: 'ri-check-circle-fill', error: 'ri-error-warning-fill', info: 'ri-information-fill' };
    const colors = { success: '#22C55E', error: '#EF4444', info: '#3B82F6' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="${icons[type]}" style="color:${colors[type]};font-size:16px;flex-shrink:0"></i><span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = 'slideInRight .25s reverse both';
      setTimeout(() => toast.remove(), 250);
    }, duration);
  }
};

/* ── Sidebar Toggle (mobile) ────────────────────────────── */
function initSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggle  = document.getElementById('menuToggle');
  if (!sidebar) return;

  const open  = () => { sidebar.classList.add('open'); overlay?.classList.add('open'); };
  const close = () => { sidebar.classList.remove('open'); overlay?.classList.remove('open'); };

  toggle?.addEventListener('click', () => sidebar.classList.contains('open') ? close() : open());
  overlay?.addEventListener('click', close);
}

/* ── Modal ──────────────────────────────────────────────── */
function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}
function closeModalOnOverlay(e) {
  if (e.target === e.currentTarget) closeModal(e.currentTarget.id);
}

/* ── Number Formatting ──────────────────────────────────── */
function formatCurrency(amount, symbol = '₱') {
  return symbol + parseFloat(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

/* ── Animated Counter ───────────────────────────────────── */
function animateCounter(el, target, duration = 800) {
  const start = performance.now();
  const from = 0;
  function update(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = from + (target - from) * eased;
    el.textContent = formatCurrency(current);
    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
}

/* ── Confirm Dialog ─────────────────────────────────────── */
function confirm(message, onConfirm) {
  if (window.confirm(message)) onConfirm();
}

/* ── AJAX Helper ────────────────────────────────────────── */
async function apiRequest(url, method = 'GET', data = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  };
  if (data) opts.body = JSON.stringify(data);
  const res = await fetch(url, opts);
  return res.json();
}

/* ── File Upload Preview ────────────────────────────────── */
function initFileUpload(inputId, previewId) {
  const input = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (!input || !preview) return;

  input.addEventListener('change', () => {
    preview.innerHTML = '';
    Array.from(input.files).forEach(file => {
      const div = document.createElement('div');
      div.className = 'receipt-thumb';
      if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        div.appendChild(img);
      } else {
        const ext = file.name.split('.').pop().toUpperCase();
        div.innerHTML = `<div class="file-icon"><i class="ri-file-line"></i><small>${ext}</small></div>`;
      }
      preview.appendChild(div);
    });
  });
}

/* ── Search & Filter ────────────────────────────────────── */
function initSearchFilter(inputId, tableId, colIndex = -1) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;

  input.addEventListener('input', () => {
    const q = input.value.toLowerCase().trim();
    const rows = table.querySelectorAll('tbody tr:not(.group-header)');
    rows.forEach(row => {
      const text = colIndex >= 0
        ? (row.cells[colIndex]?.textContent || '').toLowerCase()
        : row.textContent.toLowerCase();
      row.style.display = text.includes(q) ? '' : 'none';
    });
  });
}

/* ── Calculate Expense Amount ───────────────────────────── */
function calcExpenseAmount(qtyId, priceId, amountId) {
  const qty   = parseFloat(document.getElementById(qtyId)?.value || 0);
  const price = parseFloat(document.getElementById(priceId)?.value || 0);
  const el = document.getElementById(amountId);
  if (el) el.value = formatCurrency(qty * price, '');
}

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  ThemeManager.init();
  initSidebar();

  // Animate stat values on load
  document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseFloat(el.getAttribute('data-counter') || 0);
    animateCounter(el, target, 900);
  });

  // Close modals on overlay click
  document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', closeModalOnOverlay);
  });

  // Theme toggle
  document.getElementById('themeToggle')?.addEventListener('click', () => ThemeManager.toggle());
});
