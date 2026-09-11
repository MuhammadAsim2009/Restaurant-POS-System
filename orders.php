<?php
require_once __DIR__ . '/config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders — Khan Fast Food POS</title>
  <link rel="stylesheet" href="/Resturent_POS/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .filter-bar { display:flex; gap:12px; align-items:center; margin-bottom:20px; flex-wrap:wrap; }
    .order-table-wrap { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; }
    .status-filter-btn {
      padding:7px 14px; border-radius:99px; border:1px solid var(--border);
      background:transparent; color:var(--text-muted); font-size:13px; font-weight:600;
      cursor:pointer; transition:all var(--transition);
    }
    .status-filter-btn.active { background:var(--brand); border-color:var(--brand); color:#000; }
    .order-row-details { font-size:12px; color:var(--text-muted); }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">🧾 Orders</div>
      <div class="topbar-actions">
        <input type="date" class="form-control" id="dateFilter" value="<?= date('Y-m-d') ?>" onchange="loadOrders()" style="width:auto">
        <button class="btn btn-outline" onclick="loadOrders()"><i class="fas fa-sync-alt"></i></button>
        <a href="/Resturent_POS/pos.php" class="btn btn-brand"><i class="fas fa-plus"></i> New</a>
      </div>
    </div>
    <div class="page-body">
      <div class="filter-bar">
        <button class="status-filter-btn active" onclick="setFilter('all',this)">All</button>
        <button class="status-filter-btn" onclick="setFilter('pending',this)">Pending</button>
        <button class="status-filter-btn" onclick="setFilter('preparing',this)">Preparing</button>
        <button class="status-filter-btn" onclick="setFilter('ready',this)">Ready</button>
        <button class="status-filter-btn" onclick="setFilter('delivered',this)">Delivered</button>
        <button class="status-filter-btn" onclick="setFilter('cancelled',this)">Cancelled</button>
      </div>

      <div class="order-table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Type</th>
              <th>Table</th>
              <th>Cashier</th>
              <th>Items</th>
              <th>Total</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Time</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="ordersBody">
            <tr><td colspan="10" style="text-align:center;padding:30px"><div class="spinner"></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Order Detail Modal -->
<div class="modal-overlay" id="orderDetailModal">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <div class="modal-title" id="orderDetailTitle">Order Details</div>
      <button class="modal-close" onclick="closeModal('orderDetailModal')"><i class="fas fa-times"></i></button>
    </div>
    <div id="orderDetailBody"></div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="/Resturent_POS/js/app.js"></script>
<script>
let currentFilter = 'all';
let allOrders = [];

async function loadOrders() {
  const date = document.getElementById('dateFilter').value;
  try {
    const res = await api(`orders.php?action=list&date=${date}`);
    allOrders = res.data;
    renderOrders();
  } catch(e) { showToast('Failed to load orders', 'error'); }
}

function setFilter(status, btn) {
  currentFilter = status;
  document.querySelectorAll('.status-filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderOrders();
}

function renderOrders() {
  const filtered = currentFilter === 'all' ? allOrders : allOrders.filter(o => o.status === currentFilter);
  const tbody = document.getElementById('ordersBody');

  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><div class="empty-icon">🧾</div><p>No orders found</p></div></td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(o => `
    <tr>
      <td><strong>${o.order_number}</strong></td>
      <td><span style="text-transform:capitalize">${o.order_type}</span></td>
      <td>${o.table_number || '—'}</td>
      <td>${o.cashier_name}</td>
      <td>${o.items ? o.items.length : '—'} items</td>
      <td><strong style="color:var(--brand)">${formatPKR(o.total)}</strong></td>
      <td>${o.payment_method ? paymentIcon(o.payment_method) + ' ' + o.payment_method : '—'}</td>
      <td>${statusBadge(o.status)}</td>
      <td style="font-size:12px;color:var(--text-muted)">${formatTime(o.created_at)}</td>
      <td>
        <button class="btn btn-ghost btn-sm" onclick="viewOrder(${o.id})"><i class="fas fa-eye"></i></button>
        ${o.status !== 'delivered' && o.status !== 'cancelled' ? `
          <select onchange="updateStatus(${o.id},this.value)" class="form-control" style="display:inline;width:auto;padding:4px 8px;font-size:12px">
            <option value="">Change</option>
            <option value="pending">Pending</option>
            <option value="preparing">Preparing</option>
            <option value="ready">Ready</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>` : ''}
      </td>
    </tr>`).join('');
}

async function updateStatus(id, status) {
  if (!status) return;
  try {
    await api('orders.php?action=status', 'PUT', { id, status });
    showToast('Order status updated!', 'success');
    loadOrders();
  } catch(_) {}
}

async function viewOrder(id) {
  try {
    const res = await api(`orders.php?action=detail&id=${id}`);
    const o = res.data;
    document.getElementById('orderDetailTitle').textContent = `Order ${o.order_number}`;
    document.getElementById('orderDetailBody').innerHTML = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
        <div><div class="text-muted text-sm">Type</div><div class="font-semibold">${o.order_type}</div></div>
        <div><div class="text-muted text-sm">Table</div><div class="font-semibold">${o.table_number || 'N/A'}</div></div>
        <div><div class="text-muted text-sm">Cashier</div><div class="font-semibold">${o.cashier_name}</div></div>
        <div><div class="text-muted text-sm">Time</div><div class="font-semibold">${formatDateTime(o.created_at)}</div></div>
      </div>
      <div class="section-title">Items</div>
      <table class="data-table" style="margin-bottom:16px">
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>${o.items.map(i=>`<tr><td>${i.item_name}</td><td>${i.quantity}</td><td>${formatPKR(i.unit_price)}</td><td>${formatPKR(i.subtotal)}</td></tr>`).join('')}</tbody>
      </table>
      <div class="divider"></div>
      <div class="summary-row" style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0"><span>Subtotal</span><span>${formatPKR(o.subtotal)}</span></div>
      <div class="summary-row" style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0"><span>Tax (5%)</span><span>${formatPKR(o.tax)}</span></div>
      ${parseFloat(o.discount)>0?`<div style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0;color:var(--accent-green)"><span>Discount</span><span>- ${formatPKR(o.discount)}</span></div>`:''}
      <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;padding:10px 0;border-top:1px solid var(--border);margin-top:8px"><span>TOTAL</span><span style="color:var(--brand)">${formatPKR(o.total)}</span></div>
      ${o.payment ? `<div style="margin-top:10px;font-size:13px;color:var(--text-muted)">Paid via ${paymentIcon(o.payment.method)} ${o.payment.method} | Cash: ${formatPKR(o.payment.amount_paid)} | Change: ${formatPKR(o.payment.change_given)}</div>` : ''}
    `;
    openModal('orderDetailModal');
  } catch(_) {}
}

loadOrders();
</script>
</body>
</html>
