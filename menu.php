<?php
require_once __DIR__ . '/config/session.php';
requireManager();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu Management — Khan Fast Food POS</title>
  <link rel="stylesheet" href="/Resturent_POS/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .menu-tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
    .menu-cat-tab { padding:8px 18px; border-radius:99px; border:1px solid var(--border); background:transparent; color:var(--text-muted); font-size:13px; font-weight:600; cursor:pointer; transition:all var(--transition); }
    .menu-cat-tab.active { background:var(--brand); border-color:var(--brand); color:#000; }
    .menu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
    .menu-item-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); padding:18px; position:relative; transition:all var(--transition); }
    .menu-item-card:hover { border-color:var(--border-accent); }
    .menu-item-card.unavailable { opacity:0.6; }
    .item-emoji-lg { font-size:40px; margin-bottom:10px; display:block; }
    .item-card-actions { display:flex; gap:6px; margin-top:12px; }
    .toggle-switch { position:relative; width:42px; height:24px; }
    .toggle-switch input { opacity:0; width:0; height:0; }
    .slider { position:absolute; inset:0; background:rgba(255,255,255,0.1); border-radius:99px; cursor:pointer; transition:.3s; }
    .slider::before { content:''; position:absolute; width:18px; height:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
    input:checked + .slider { background:var(--accent-green); }
    input:checked + .slider::before { transform:translateX(18px); }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">🍽️ Menu Management</div>
      <div class="topbar-actions">
        <button class="btn btn-brand" onclick="openItemModal()"><i class="fas fa-plus"></i> Add Item</button>
      </div>
    </div>
    <div class="page-body">
      <div class="menu-tabs" id="menuCatTabs">
        <button class="menu-cat-tab active" onclick="filterMenuCat('all',this)">All Items</button>
      </div>
      <div class="menu-grid" id="menuGrid">
        <div class="spinner"></div>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal-overlay" id="itemModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="itemModalTitle">Add Menu Item</div>
      <button class="modal-close" onclick="closeModal('itemModal')"><i class="fas fa-times"></i></button>
    </div>
    <form id="itemForm" onsubmit="saveItem(event)">
      <input type="hidden" id="editItemId">
      <div class="form-group">
        <label class="form-label">Category *</label>
        <select class="form-control" id="itemCategory" required>
          <option value="">Select category</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Item Name *</label>
        <input type="text" class="form-control" id="itemName" placeholder="e.g. Double Cheese Burger" required>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <input type="text" class="form-control" id="itemDesc" placeholder="Short description...">
      </div>
      <div class="form-group">
        <label class="form-label">Price (Rs.) *</label>
        <input type="number" class="form-control" id="itemPrice" placeholder="0" min="1" step="5" required>
      </div>
      <div class="form-group">
        <label class="form-label">Available</label>
        <select class="form-control" id="itemAvailable">
          <option value="1">Yes — Available</option>
          <option value="0">No — Unavailable</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="button" class="btn btn-outline w-full" onclick="closeModal('itemModal')">Cancel</button>
        <button type="submit" class="btn btn-brand w-full" id="saveItemBtn"><i class="fas fa-save"></i> Save Item</button>
      </div>
    </form>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="/Resturent_POS/js/app.js"></script>
<script>
const EMOJIS = { 1:'🍔', 2:'🎁', 3:'🍟', 4:'🥤', 5:'🍦', 6:'🍗' };
let allItems = [], allCategories = [], filterCatId = 'all';

async function loadMenu() {
  try {
    const [catsRes, itemsRes] = await Promise.all([
      api('menu.php?action=categories'),
      api('menu.php?action=list')
    ]);
    allCategories = catsRes.data;
    allItems = itemsRes.data;

    // Build category tabs
    const tabs = document.getElementById('menuCatTabs');
    tabs.innerHTML = `<button class="menu-cat-tab active" onclick="filterMenuCat('all',this)">All Items</button>`;
    allCategories.forEach(c => {
      tabs.innerHTML += `<button class="menu-cat-tab" onclick="filterMenuCat(${c.id},this)">${c.icon} ${c.name}</button>`;
    });

    // Populate category select
    const sel = document.getElementById('itemCategory');
    sel.innerHTML = '<option value="">Select category</option>';
    allCategories.forEach(c => { sel.innerHTML += `<option value="${c.id}">${c.icon} ${c.name}</option>`; });

    renderMenuItems();
  } catch(e) { showToast('Failed to load menu','error'); }
}

function filterMenuCat(catId, btn) {
  filterCatId = catId;
  document.querySelectorAll('.menu-cat-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderMenuItems();
}

function renderMenuItems() {
  const filtered = filterCatId === 'all' ? allItems : allItems.filter(i => i.category_id == filterCatId);
  const grid = document.getElementById('menuGrid');
  if (!filtered.length) {
    grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">🍽️</div><p>No items found</p></div>`;
    return;
  }
  grid.innerHTML = filtered.map(it => `
    <div class="menu-item-card ${!it.is_available ? 'unavailable' : ''}">
      <span class="item-emoji-lg">${EMOJIS[it.category_id]||'🍽️'}</span>
      <div style="font-size:14px;font-weight:700">${it.name}</div>
      <div style="font-size:12px;color:var(--text-muted);margin:4px 0">${it.description||''}</div>
      <div style="font-size:16px;font-weight:800;color:var(--brand)">${formatPKR(it.price)}</div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:2px">${it.category_name}</div>
      <div style="display:flex;align-items:center;gap:8px;margin-top:10px;font-size:12px">
        <label class="toggle-switch">
          <input type="checkbox" ${it.is_available?'checked':''} onchange="toggleItem(${it.id})">
          <span class="slider"></span>
        </label>
        <span style="color:var(--text-muted)">${it.is_available?'Available':'Unavailable'}</span>
      </div>
      <div class="item-card-actions">
        <button class="btn btn-ghost btn-sm w-full" onclick="editItem(${JSON.stringify(it).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i> Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteItem(${it.id},'${it.name.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
      </div>
    </div>`).join('');
}

function openItemModal(reset=true) {
  if (reset) {
    document.getElementById('itemModalTitle').textContent = 'Add Menu Item';
    document.getElementById('editItemId').value = '';
    document.getElementById('itemForm').reset();
  }
  openModal('itemModal');
}

function editItem(it) {
  document.getElementById('itemModalTitle').textContent = 'Edit Item';
  document.getElementById('editItemId').value = it.id;
  document.getElementById('itemCategory').value = it.category_id;
  document.getElementById('itemName').value = it.name;
  document.getElementById('itemDesc').value = it.description || '';
  document.getElementById('itemPrice').value = it.price;
  document.getElementById('itemAvailable').value = it.is_available;
  openItemModal(false);
}

async function saveItem(e) {
  e.preventDefault();
  const id = document.getElementById('editItemId').value;
  const data = {
    id: id || undefined,
    category_id: document.getElementById('itemCategory').value,
    name: document.getElementById('itemName').value,
    description: document.getElementById('itemDesc').value,
    price: document.getElementById('itemPrice').value,
    is_available: document.getElementById('itemAvailable').value,
  };
  const btn = document.getElementById('saveItemBtn');
  btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  try {
    if (id) { await api('menu.php?action=update', 'PUT', data); showToast('Item updated!','success'); }
    else     { await api('menu.php?action=create', 'POST', data); showToast('Item added!','success'); }
    closeModal('itemModal');
    loadMenu();
  } catch(_) {}
  btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Save Item';
}

async function toggleItem(id) {
  try { await api('menu.php?action=toggle', 'PUT', { id }); } catch(_) {}
}

async function deleteItem(id, name) {
  if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
  try {
    await api(`menu.php?action=delete&id=${id}`, 'DELETE');
    showToast('Item deleted!','success');
    loadMenu();
  } catch(_) {}
}

loadMenu();
</script>
</body>
</html>
