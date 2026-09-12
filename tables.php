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

    /* SaaS Modal Styling */
    .saas-modal {
      background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px; padding: 0; overflow: hidden;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
      width: 100%; max-width: 420px;
    }
    .saas-modal-header {
      padding: 24px 32px 16px; display: flex; justify-content: space-between; align-items: center;
    }
    .saas-modal-title { font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .saas-modal-title i { color: var(--brand); background: rgba(var(--brand-rgb), 0.15); padding: 8px; border-radius: 8px; font-size: 16px; }
    .saas-modal-body { padding: 16px 32px 32px; }
    .saas-input {
      width: 100%; background: #0f172a; border: 1px solid rgba(255,255,255,0.1);
      padding: 14px 16px; border-radius: 10px; color: #fff; font-size: 15px;
      transition: all 0.3s;
    }
    .saas-input:focus { border-color: var(--brand); outline: none; box-shadow: 0 0 0 3px rgba(var(--brand-rgb), 0.15); }
    .saas-btn {
      width: 100%; padding: 14px; font-size: 16px; font-weight: 700;
      background: linear-gradient(135deg, var(--brand), #eab308); color: #000;
      border: none; border-radius: 10px; cursor: pointer;
      box-shadow: 0 4px 12px rgba(234,179,8,0.3); transition: transform 0.2s, box-shadow 0.2s;
    }
    .saas-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(234,179,8,0.4); }
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
        <a href="/Resturent_POS/pos.php" class="btn btn-brand"><i class="fas fa-cash-register"></i> New Order</a>
      </div>
    </div>
    <div class="page-body">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div class="stats-bar" id="statsBar" style="margin-bottom: 0;">
          <div class="stat-pill"><div class="dot dot-green"></div>Loading...</div>
        </div>
        <button class="btn btn-brand" onclick="openCreateTableModal()" style="padding: 10px 20px; box-shadow: 0 4px 12px rgba(var(--brand-rgb), 0.3); font-weight: 600;"><i class="fas fa-plus"></i> Create Table</button>
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

<!-- Create Table Modal -->
<div class="modal-overlay" id="createTableModal">
  <div class="saas-modal">
    <div class="saas-modal-header">
      <div class="saas-modal-title"><i class="fas fa-plus"></i> Create New Table</div>
      <button class="modal-close" onclick="closeModal('createTableModal')" style="position: static;"><i class="fas fa-times"></i></button>
    </div>
    <div class="saas-modal-body">
      <form id="createTableForm" onsubmit="createTable(event)">
        <div style="margin-bottom: 20px;">
          <label style="display:block; font-size:13px; font-weight:600; color:var(--text-muted); margin-bottom:8px;">Table Name</label>
          <input type="text" id="newTableNumber" class="saas-input" required placeholder="e.g. Main Hall 1">
        </div>
        <div style="margin-bottom: 24px;">
          <label style="display:block; font-size:13px; font-weight:600; color:var(--text-muted); margin-bottom:8px;">Capacity (Seats)</label>
          <input type="number" id="newTableCapacity" class="saas-input" required min="1" value="4">
        </div>
        <button type="submit" class="saas-btn">Create Table</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit Table Modal -->
<div class="modal-overlay" id="editTableModal">
  <div class="saas-modal">
    <div class="saas-modal-header">
      <div class="saas-modal-title"><i class="fas fa-edit"></i> Edit Table</div>
      <button class="modal-close" onclick="closeModal('editTableModal')" style="position: static;"><i class="fas fa-times"></i></button>
    </div>
    <div class="saas-modal-body">
      <form id="editTableForm" onsubmit="editTable(event)">
        <input type="hidden" id="editTableId">
        <div style="margin-bottom: 20px;">
          <label style="display:block; font-size:13px; font-weight:600; color:var(--text-muted); margin-bottom:8px;">Table Name</label>
          <input type="text" id="editTableNumber" class="saas-input" required placeholder="e.g. Main Hall 1">
        </div>
        <div style="margin-bottom: 24px;">
          <label style="display:block; font-size:13px; font-weight:600; color:var(--text-muted); margin-bottom:8px;">Capacity (Seats)</label>
          <input type="number" id="editTableCapacity" class="saas-input" required min="1">
        </div>
        <button type="submit" class="saas-btn">Update Table</button>
      </form>
    </div>
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
          <div class="table-num">${t.table_number}</div>
          <div class="table-cap"><i class="fas fa-users"></i> ${t.capacity} seats</div>
          ${statusBadge(t.status)}
          ${t.status==='occupied' && t.order_number ? `<div class="table-order">Order: ${t.order_number}<br>${formatPKR(t.total)}</div>` : ''}
        </div>`;
    }).join('');
  } catch(e) { showToast('Failed to load tables', 'error'); }
}

function showTableDetail(t) {
  document.getElementById('tableModalTitle').textContent = `${t.table_number}`;
  let body = `
    <div style="text-align:left;margin-bottom:20px">
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
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
      <button class="btn btn-success btn-sm" onclick="updateTable(${t.id},'available')">🟢 Available</button>
      <button class="btn btn-danger btn-sm" onclick="updateTable(${t.id},'occupied')">🔴 Occupied</button>
      <button class="btn btn-ghost btn-sm" onclick="updateTable(${t.id},'reserved')">🟡 Reserved</button>
    </div>
    <div class="section-title">Manage</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn btn-outline btn-sm" data-name="${(t.table_number+'').replace(/"/g, '&quot;')}" onclick="openEditTableModal('${t.id}', this.getAttribute('data-name'), '${t.capacity}')"><i class="fas fa-edit"></i> Edit</button>
      <button class="btn btn-danger btn-sm" onclick="deleteTable(${t.id})" ${t.status !== 'available' ? 'disabled title="Cannot delete an active table"' : ''}><i class="fas fa-trash"></i> Delete</button>
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

function openCreateTableModal() {
  document.getElementById('createTableForm').reset();
  openModal('createTableModal');
}

async function createTable(e) {
  e.preventDefault();
  const table_number = document.getElementById('newTableNumber').value;
  const capacity = document.getElementById('newTableCapacity').value;
  
  try {
    const res = await api('tables.php?action=create', 'POST', { table_number, capacity });
    if(res.success) {
      showToast('Table created successfully', 'success');
      closeModal('createTableModal');
      loadTables();
    } else {
      showToast(res.message || 'Error creating table', 'error');
    }
  } catch(err) {
    showToast('Failed to create table', 'error');
  }
}

function openEditTableModal(id, number, capacity) {
  closeModal('tableModal');
  document.getElementById('editTableId').value = id;
  document.getElementById('editTableNumber').value = number;
  document.getElementById('editTableCapacity').value = capacity;
  openModal('editTableModal');
}

async function editTable(e) {
  e.preventDefault();
  const id = document.getElementById('editTableId').value;
  const table_number = document.getElementById('editTableNumber').value;
  const capacity = document.getElementById('editTableCapacity').value;
  
  try {
    const res = await api('tables.php?action=update', 'PUT', { id, table_number, capacity });
    if(res.success) {
      showToast('Table updated successfully', 'success');
      closeModal('editTableModal');
      loadTables();
    } else {
      showToast(res.message || 'Error updating table', 'error');
    }
  } catch(err) {
    showToast('Failed to update table', 'error');
  }
}

async function deleteTable(id) {
  if (!confirm('Are you sure you want to delete this table?')) return;
  try {
    const res = await api(`tables.php?action=delete&id=${id}`, 'DELETE');
    if(res.success) {
      showToast('Table deleted successfully', 'success');
      closeModal('tableModal');
      loadTables();
    } else {
      showToast(res.message || 'Error deleting table', 'error');
    }
  } catch(err) {
    showToast('Failed to delete table', 'error');
  }
}

loadTables();
setInterval(loadTables, 30000);
</script>
</body>
</html>
