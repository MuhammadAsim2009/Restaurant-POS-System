<?php
require_once __DIR__ . '/config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tables — Khan Fast Food POS</title>
  <link rel="stylesheet" href="/Resturent_POS/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .tables-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 18px;
    }
    .table-card {
      background: var(--bg-card); border: 2px solid var(--border);
      border-radius: var(--radius-lg); padding: 22px 18px;
      text-align: center; cursor: pointer; transition: all var(--transition);
      position: relative;
    }
    .table-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .table-card.available { border-color: rgba(16,185,129,0.3); }
    .table-card.available:hover { border-color: var(--accent-green); }
    .table-card.occupied  { border-color: rgba(239,68,68,0.4); background: rgba(239,68,68,0.04); }
    .table-card.reserved  { border-color: rgba(245,158,11,0.4); background: rgba(245,158,11,0.04); }
    .table-icon { font-size: 36px; margin-bottom: 10px; }
    .table-num  { font-size: 18px; font-weight: 800; margin-bottom: 4px; }
    .table-cap  { font-size: 12px; color: var(--text-muted); margin-bottom: 10px; }
    .table-order { font-size: 11px; color: var(--text-secondary); margin-top: 6px; }
    .stats-bar { display: flex; gap: 14px; margin-bottom: 24px; flex-wrap: wrap; }
    .stat-pill {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 18px; border-radius: 99px;
      border: 1px solid var(--border); font-size: 13px; font-weight: 600;
    }
    .stat-pill .dot { width: 10px; height: 10px; border-radius: 50%; }
    .dot-green  { background: var(--accent-green); }
    .dot-red    { background: var(--accent-red); }
    .dot-yellow { background: var(--brand); }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="topbar-title">🪑 Table Management</div>
      </div>
      <div class="topbar-actions">
        <button class="btn btn-outline" onclick="loadTables()"><i class="fas fa-sync-alt"></i> Refresh</button>
        <a href="/Resturent_POS/pos.php" class="btn btn-brand"><i class="fas fa-plus"></i> New Order</a>
      </div>
    </div>
    <div class="page-body">
      <div class="stats-bar" id="statsBar">
        <div class="stat-pill"><div class="dot dot-green"></div>Loading...</div>
      </div>
      <div class="tables-grid" id="tablesGrid">
        <div class="spinner"></div>
      </div>
    </div>
  </div>
</div>

<!-- Table Detail Modal -->
<div class="modal-overlay" id="tableModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="tableModalTitle">Table</div>
      <button class="modal-close" onclick="closeModal('tableModal')"><i class="fas fa-times"></i></button>
    </div>
    <div id="tableModalBody"></div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="/Resturent_POS/js/app.js"></script>
<script>
async function loadTables() {
  try {
    const res = await api('tables.php?action=list');
    const tables = res.data;

    const available = tables.filter(t => t.status==='available').length;
    const occupied  = tables.filter(t => t.status==='occupied').length;
    const reserved  = tables.filter(t => t.status==='reserved').length;

    document.getElementById('statsBar').innerHTML = `
      <div class="stat-pill"><div class="dot dot-green"></div>${available} Available</div>
      <div class="stat-pill"><div class="dot dot-red"></div>${occupied} Occupied</div>
      <div class="stat-pill"><div class="dot dot-yellow"></div>${reserved} Reserved</div>
    `;

    document.getElementById('tablesGrid').innerHTML = tables.map(t => {
      const icons = { available: '🟢', occupied: '🔴', reserved: '🟡' };
      return `
        <div class="table-card ${t.status}" onclick="showTableDetail(${JSON.stringify(t).replace(/"/g,'&quot;')})">
          <div class="table-icon">${icons[t.status] || '⚪'}</div>
          <div class="table-num">Table ${t.table_number}</div>
          <div class="table-cap"><i class="fas fa-users"></i> ${t.capacity} seats</div>
          ${statusBadge(t.status)}
          ${t.status==='occupied' && t.order_number ? `<div class="table-order">Order: ${t.order_number}<br>${formatPKR(t.total)}</div>` : ''}
        </div>`;
    }).join('');
  } catch(e) { showToast('Failed to load tables', 'error'); }
}

function showTableDetail(t) {
  document.getElementById('tableModalTitle').textContent = `Table ${t.table_number}`;
  let body = `
    <div style="text-align:center;margin-bottom:20px">
      ${statusBadge(t.status)}
      <div style="margin-top:10px;color:var(--text-muted);font-size:13px">${t.capacity} seats capacity</div>
    </div>`;

  if (t.status === 'occupied' && t.order_number) {
    body += `
      <div class="card" style="margin-bottom:16px">
        <div style="font-size:13px;color:var(--text-muted)">Current Order</div>
        <div style="font-size:16px;font-weight:700;margin-top:4px">${t.order_number}</div>
        <div style="font-size:20px;font-weight:800;color:var(--brand);margin-top:4px">${formatPKR(t.total)}</div>
        <div style="margin-top:8px">${statusBadge(t.order_status)}</div>
      </div>`;
  }

  body += `
    <div class="section-title">Change Status</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn btn-success btn-sm" onclick="updateTable(${t.id},'available')">🟢 Available</button>
      <button class="btn btn-danger btn-sm" onclick="updateTable(${t.id},'occupied')">🔴 Occupied</button>
      <button class="btn btn-ghost btn-sm" onclick="updateTable(${t.id},'reserved')">🟡 Reserved</button>
    </div>`;

  document.getElementById('tableModalBody').innerHTML = body;
  openModal('tableModal');
}

async function updateTable(id, status) {
  try {
    await api('tables.php?action=status', 'PUT', { id, status });
    showToast('Table status updated!', 'success');
    closeModal('tableModal');
    loadTables();
  } catch(_) {}
}

loadTables();
setInterval(loadTables, 30000);
</script>
</body>
</html>
