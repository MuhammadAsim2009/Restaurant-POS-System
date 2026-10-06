// ============================================================
// Restaurant POS — Shared JS Utilities & API Layer
// ============================================================

const APP = {
  baseUrl: '/Resturent_POS',
  cart: [],
  currentUser: null,
};

/* ── API Fetch Wrapper ──────────────────────────────────────── */
async function api(endpoint, method = 'GET', data = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  };
  if (data) opts.body = JSON.stringify(data);

  try {
    const res = await fetch(`${APP.baseUrl}/api/${endpoint}`, opts);
    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Request failed');
    return json;
  } catch (err) {
    showToast(err.message || 'Network error', 'error');
    throw err;
  }
}

/* ── Toast Notifications ────────────────────────────────────── */
function showToast(message, type = 'info', duration = 3500) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <i class="fas ${icons[type] || icons.info} toast-icon ${type}"></i>
    <span>${message}</span>
  `;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'toastIn 0.3s ease reverse forwards';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/* ── Modal Helpers ──────────────────────────────────────────── */
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('active');
}
// Close on overlay click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
  }
});

/* ── Currency Formatting ────────────────────────────────────── */
function formatPKR(amount) {
  return 'Rs. ' + parseFloat(amount).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

/* ── Date/Time Helpers ──────────────────────────────────────── */
function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('en-PK', { day: '2-digit', month: 'short', year: 'numeric' });
}
function formatTime(dateStr) {
  return new Date(dateStr).toLocaleTimeString('en-PK', { hour: '2-digit', minute: '2-digit' });
}
function formatDateTime(dateStr) {
  return formatDate(dateStr) + ' ' + formatTime(dateStr);
}
function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
  if (diff < 60)   return diff + 's ago';
  if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
  return Math.floor(diff / 3600) + 'h ago';
}

/* ── Status Badge HTML ──────────────────────────────────────── */
function statusBadge(status) {
  const map = {
    pending:   { cls: 'badge-pending',   label: 'Pending',   icon: 'fa-clock' },
    preparing: { cls: 'badge-preparing', label: 'Preparing', icon: 'fa-fire' },
    ready:     { cls: 'badge-ready',     label: 'Ready',     icon: 'fa-check' },
    delivered: { cls: 'badge-delivered', label: 'Delivered', icon: 'fa-check-double' },
    cancelled: { cls: 'badge-cancelled', label: 'Cancelled', icon: 'fa-times' },
    available: { cls: 'badge-available', label: 'Available', icon: 'fa-circle' },
    occupied:  { cls: 'badge-occupied',  label: 'Occupied',  icon: 'fa-circle' },
    reserved:  { cls: 'badge-reserved',  label: 'Reserved',  icon: 'fa-circle' },
  };
  const s = map[status] || { cls: '', label: status, icon: 'fa-circle' };
  return `<span class="badge ${s.cls}"><i class="fas ${s.icon}"></i>${s.label}</span>`;
}

/* ── Payment Method Icon ────────────────────────────────────── */
function paymentIcon(method) {
  const map = { cash: '💵', card: '💳', easypaisa: '📱', jazzcash: '📲' };
  return map[method] || '💰';
}

/* ── Debounce ───────────────────────────────────────────────── */
function debounce(fn, wait) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
}

/* ── Logout ─────────────────────────────────────────────────── */
async function logout() {
  try { await api('auth.php?action=logout', 'POST'); } catch(_) {}
  window.location.href = `${APP.baseUrl}/index.php`;
}
