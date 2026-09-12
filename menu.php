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
        <button class="btn btn-outline" onclick="openCatModal()"><i class="fas fa-tags"></i> Categories</button>
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
        <input type="number" class="form-control" id="itemPrice" placeholder="0" step="5" required>
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

<!-- Category Management Modal -->
<div class="modal-overlay" id="catModal">
  <div class="saas-modal" style="max-width:520px;background:#1e293b;border:1px solid rgba(255,255,255,0.1);border-radius:16px;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);width:100%;">
    <div style="padding:24px 32px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(255,255,255,0.08);">
      <div style="font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px;"><i class="fas fa-tags" style="color:var(--brand)"></i> Manage Categories</div>
      <button class="modal-close" onclick="closeModal('catModal')" style="position:static;"><i class="fas fa-times"></i></button>
    </div>
    <!-- Add / Edit Form -->
    <div style="padding:20px 32px;border-bottom:1px solid rgba(255,255,255,0.08);">
      <div style="display:flex;gap:10px;align-items:flex-end;">
        <div style="flex:0 0 90px;">
          <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">Icon</label>
          <select id="catIcon" style="width:100%;background:#0f172a;border:1px solid rgba(255,255,255,0.1);padding:11px 8px;border-radius:10px;color:#fff;font-size:18px;text-align:center;cursor:pointer;">
            <option value="🍔">🍔 Burger</option>
            <option value="🍟">🍟 Fries</option>
            <option value="🍕">🍕 Pizza</option>
            <option value="🍗">🍗 Chicken</option>
            <option value="🥤">🥤 Drinks</option>
            <option value="🍦">🍦 Ice Cream</option>
            <option value="🍩">🍩 Donuts</option>
            <option value="🌮">🌮 Tacos</option>
            <option value="🌯">🌯 Wraps</option>
            <option value="🥪">🥪 Sandwich</option>
            <option value="🍜">🍜 Noodles</option>
            <option value="🍛">🍛 Rice</option>
            <option value="🥗">🥗 Salad</option>
            <option value="🍰">🍰 Cake</option>
            <option value="☕">☕ Coffee</option>
            <option value="🧃">🧃 Juice</option>
            <option value="🍱">🍱 Combo</option>
            <option value="🎁">🎁 Special</option>
            <option value="🍽️">🍽️ Other</option>
          </select>
        </div>
        <div style="flex:1;">
          <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">Category Name</label>
          <input type="text" id="catName" placeholder="e.g. Desserts" style="width:100%;background:#0f172a;border:1px solid rgba(255,255,255,0.1);padding:12px 16px;border-radius:10px;color:#fff;font-size:15px;">
        </div>
        <div>
          <input type="hidden" id="catEditId">
          <button onclick="saveCategory()" style="padding:12px 20px;background:linear-gradient(135deg,var(--brand),#eab308);color:#000;border:none;border-radius:10px;font-weight:700;cursor:pointer;white-space:nowrap;" id="catSaveBtn"><i class="fas fa-plus"></i> Add</button>
        </div>
      </div>
    </div>
    <!-- Category List -->
    <div style="padding:16px 32px 28px;max-height:340px;overflow-y:auto;" id="catList">
      <div class="spinner"></div>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="/Resturent_POS/js/app.js"></script>
<script>
const EMOJIS = {};
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
  grid.innerHTML = filtered.map(it => {
    const cat = allCategories.find(c => c.id == it.category_id);
    const icon = cat ? cat.icon : '🍽️';
    return `
    <div class="menu-item-card ${!it.is_available ? 'unavailable' : ''}">
      <span class="item-emoji-lg">${icon}</span>
      <div style="font-size:14px;font-weight:700">${it.name}</div>
      <div style="font-size:12px;color:var(--text-muted);margin:4px 0">${it.description||''}</div>
      <div style="font-size:16px;font-weight:800;color:var(--brand)">${formatPKR(it.price)}</div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:2px">${it.category_name}</div>
      <div style="display:flex;align-items:center;gap:8px;margin-top:10px;font-size:12px">
        <label class="toggle-switch">
          <input type="checkbox" ${it.is_available?'checked':''} onchange="toggleItem(${it.id}, this)">
          <span class="slider"></span>
        </label>
        <span class="toggle-label" style="color:var(--text-muted)">${it.is_available?'Available':'Unavailable'}</span>
      </div>
      <div class="item-card-actions">
        <button class="btn btn-ghost btn-sm w-full" onclick="editItem(${JSON.stringify(it).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i> Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteItem(${it.id},'${it.name.replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
      </div>
    </div>`}).join('');
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

async function toggleItem(id, checkbox) {
  const card = checkbox.closest('.menu-item-card');
  const label = card.querySelector('.toggle-label');
  const isNowAvailable = checkbox.checked;
  try {
    await api('menu.php?action=toggle', 'PUT', { id });
    // Update card appearance instantly
    if (isNowAvailable) {
      card.classList.remove('unavailable');
      if (label) label.textContent = 'Available';
    } else {
      card.classList.add('unavailable');
      if (label) label.textContent = 'Unavailable';
    }
    // Also update in allItems array so re-renders are consistent
    const item = allItems.find(i => i.id == id);
    if (item) item.is_available = isNowAvailable ? 1 : 0;
  } catch(_) {
    // Revert checkbox on failure
    checkbox.checked = !isNowAvailable;
    showToast('Failed to update availability', 'error');
  }
}

async function deleteItem(id, name) {
  if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
  try {
    await api(`menu.php?action=delete&id=${id}`, 'DELETE');
    showToast('Item deleted!','success');
    loadMenu();
  } catch(_) {}
}

// ── Category Management ─────────────────────────────────
async function openCatModal() {
  resetCatForm();
  openModal('catModal');
  await refreshCatList();
}

function resetCatForm() {
  document.getElementById('catEditId').value = '';
  document.getElementById('catName').value = '';
  document.getElementById('catIcon').value = '🍽️';
  const btn = document.getElementById('catSaveBtn');
  btn.innerHTML = '<i class="fas fa-plus"></i> Add';
  btn.style.background = 'linear-gradient(135deg,var(--brand),#eab308)';
}

async function refreshCatList() {
  const list = document.getElementById('catList');
  list.innerHTML = '<div class="spinner"></div>';
  const res = await api('menu.php?action=cat-list');
  const cats = res.data;
  if (!cats.length) {
    list.innerHTML = '<div style="color:var(--text-muted);text-align:center;padding:24px;">No categories yet.</div>';
    return;
  }
  list.innerHTML = cats.map(c => `
    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
      <span style="font-size:22px;width:32px;text-align:center;">${c.icon}</span>
      <div style="flex:1;">
        <div style="font-weight:600;font-size:14px;">${c.name}</div>
        <div style="font-size:11px;color:var(--text-muted);">${c.item_count} item${c.item_count != 1 ? 's' : ''}</div>
      </div>
      <button onclick="startEditCat(${c.id},'${c.name.replace(/'/g,"\\'")}','${c.icon}')" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);color:#fff;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:12px;"><i class="fas fa-edit"></i></button>
      <button onclick="deleteCategory(${c.id},'${c.name.replace(/'/g,"\\'")}',${c.item_count})" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:var(--accent-red);padding:6px 12px;border-radius:8px;cursor:pointer;font-size:12px;"><i class="fas fa-trash"></i></button>
    </div>`).join('');
}

function startEditCat(id, name, icon) {
  document.getElementById('catEditId').value = id;
  document.getElementById('catName').value = name;
  // Try to match the icon in the dropdown, fallback to last option
  const sel = document.getElementById('catIcon');
  const opt = Array.from(sel.options).find(o => o.value === icon);
  sel.value = opt ? icon : '🍽️';
  const btn = document.getElementById('catSaveBtn');
  btn.innerHTML = '<i class="fas fa-save"></i> Update';
  btn.style.background = 'linear-gradient(135deg,#3b82f6,#6366f1)';
  document.getElementById('catName').focus();
}

async function saveCategory() {
  const id   = document.getElementById('catEditId').value;
  const name = document.getElementById('catName').value.trim();
  const icon = document.getElementById('catIcon').value.trim() || '🍽️';
  if (!name) { showToast('Category name is required', 'error'); return; }
  try {
    if (id) {
      await api('menu.php?action=cat-update', 'PUT', { id, name, icon });
      showToast('Category updated!', 'success');
    } else {
      await api('menu.php?action=cat-create', 'POST', { name, icon });
      showToast('Category created!', 'success');
    }
    resetCatForm();
    await refreshCatList();
    loadMenu();
  } catch (_) {}
}

async function deleteCategory(id, name, itemCount) {
  if (itemCount > 0) {
    showToast(`Cannot delete "${name}" — move or delete its ${itemCount} item(s) first.`, 'error');
    return;
  }
  if (!confirm(`Delete category "${name}"?`)) return;
  try {
    const res = await api(`menu.php?action=cat-delete&id=${id}`, 'DELETE');
    if (res.success) { showToast('Category deleted!', 'success'); await refreshCatList(); loadMenu(); }
    else showToast(res.message, 'error');
  } catch (_) {}
}
// ────────────────────────────────────────────────────────

loadMenu();
</script>
</body>
</html>
