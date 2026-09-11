<?php
require_once __DIR__ . '/config/session.php';
requireLogin();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Order — Khan Fast Food POS</title>
  <link rel="stylesheet" href="/Resturent_POS/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { overflow: hidden; }
    .pos-layout { display: flex; height: 100vh; margin-left: var(--sidebar-w); }

    /* Left: Menu Panel */
    .menu-panel {
      flex: 1; display: flex; flex-direction: column;
      background: var(--bg-primary); overflow: hidden;
    }
    .pos-header {
      padding: 16px 20px; border-bottom: 1px solid var(--border);
      background: var(--bg-secondary);
      display: flex; align-items: center; gap: 12px;
    }
    .pos-header h1 { font-size: 18px; font-weight: 700; flex:1; }
    .search-box { position: relative; }
    .search-box input {
      background: rgba(255,255,255,0.05); border: 1px solid var(--border);
      border-radius: 10px; padding: 9px 14px 9px 36px;
      color: var(--text-primary); font-size: 13px; width: 220px; outline: none;
      transition: border-color var(--transition);
    }
    .search-box input:focus { border-color: var(--brand); }
    .search-box i { position: absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:13px; }

    /* Category Tabs */
    .cat-tabs {
      display: flex; gap: 8px; padding: 14px 20px;
      border-bottom: 1px solid var(--border);
      overflow-x: auto; scrollbar-width: none; flex-shrink: 0;
    }
    .cat-tabs::-webkit-scrollbar { display: none; }
    .cat-tab {
      display: flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 99px;
      border: 1px solid var(--border); background: transparent;
      color: var(--text-secondary); font-size: 13px; font-weight: 500;
      cursor: pointer; white-space: nowrap; transition: all var(--transition);
      flex-shrink: 0;
    }
    .cat-tab:hover { border-color: var(--brand); color: var(--brand); }
    .cat-tab.active {
      background: linear-gradient(135deg, var(--brand), var(--brand-dark));
      border-color: transparent; color: #000; font-weight: 700;
    }

    /* Items Grid */
    .items-grid {
      flex: 1; overflow-y: auto; padding: 16px 20px;
      display: grid; grid-template-columns: repeat(auto-fill, minmax(160px,1fr));
      gap: 14px; align-content: start;
    }
    .item-card {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius-lg); padding: 16px 14px;
      cursor: pointer; transition: all var(--transition);
      position: relative; overflow: hidden;
    }
    .item-card:hover {
      border-color: var(--border-accent);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .item-card:active { transform: scale(0.97); }
    .item-card.unavailable { opacity: 0.45; cursor: not-allowed; }
    .item-emoji { font-size: 38px; margin-bottom: 10px; display: block; text-align: center; }
    .item-name  { font-size: 13px; font-weight: 600; margin-bottom: 4px; text-align: center; }
    .item-desc  { font-size: 11px; color: var(--text-muted); margin-bottom: 10px; text-align: center; line-height: 1.4; }
    .item-price { font-size: 15px; font-weight: 800; color: var(--brand); text-align: center; }
    .item-add-btn {
      position: absolute; bottom: 10px; right: 10px;
      width: 28px; height: 28px; border-radius: 50%;
      background: var(--brand); color: #000;
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; font-weight: 700; opacity: 0;
      transition: all var(--transition);
    }
    .item-card:hover .item-add-btn { opacity: 1; }

    /* Right: Cart Panel */
    .cart-panel {
      width: 340px; flex-shrink: 0;
      background: var(--bg-secondary);
      border-left: 1px solid var(--border);
      display: flex; flex-direction: column;
    }
    .cart-header {
      padding: 18px 20px; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }
    .cart-header h2 { font-size: 16px; font-weight: 700; flex:1; }
    .cart-count {
      background: var(--brand); color: #000;
      border-radius: 99px; padding: 3px 10px;
      font-size: 12px; font-weight: 700;
    }

    /* Order Type */
    .order-type-bar {
      padding: 12px 16px; border-bottom: 1px solid var(--border);
      display: flex; gap: 6px;
    }
    .order-type-btn {
      flex:1; padding: 7px; border-radius: 8px;
      border: 1px solid var(--border); background: transparent;
      color: var(--text-muted); font-size: 12px; font-weight: 600;
      cursor: pointer; transition: all var(--transition);
    }
    .order-type-btn.active {
      background: rgba(245,158,11,0.15); border-color: var(--brand); color: var(--brand);
    }

    /* Cart Items */
    .cart-items { flex:1; overflow-y: auto; padding: 12px 16px; }
    .cart-empty {
      display: flex; flex-direction: column;
      align-items: center; justify-content: center; height: 100%;
      color: var(--text-muted); gap: 12px;
    }
    .cart-empty i { font-size: 40px; opacity: 0.3; }
    .cart-empty p { font-size: 13px; }

    .cart-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .cart-item:last-child { border-bottom: none; }
    .cart-item-emoji { font-size: 24px; }
    .cart-item-info { flex:1; }
    .cart-item-name { font-size: 13px; font-weight: 600; }
    .cart-item-price { font-size: 12px; color: var(--text-muted); }
    .cart-item-controls { display: flex; align-items: center; gap: 6px; }
    .qty-btn {
      width: 26px; height: 26px; border-radius: 6px;
      border: 1px solid var(--border); background: var(--bg-card);
      color: var(--text-primary); font-size: 14px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all var(--transition);
    }
    .qty-btn:hover { border-color: var(--brand); color: var(--brand); }
    .qty-btn.minus:hover { border-color: var(--accent-red); color: var(--accent-red); }
    .qty-num { font-size: 14px; font-weight: 700; min-width: 22px; text-align: center; }
    .cart-item-total { font-size: 13px; font-weight: 700; color: var(--brand); min-width: 70px; text-align: right; }

    /* Cart Totals */
    .cart-summary {
      padding: 14px 16px; border-top: 1px solid var(--border);
      background: rgba(0,0,0,0.2);
    }
    .summary-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; color: var(--text-secondary); }
    .summary-row.total { font-size: 17px; font-weight: 800; color: var(--text-primary); border-top: 1px solid var(--border); margin-top: 8px; padding-top: 10px; }
    .summary-row.total .val { color: var(--brand); }

    /* Discount Row */
    .discount-row { display: flex; align-items: center; gap: 8px; margin: 8px 0; }
    .discount-row input {
      flex:1; background: rgba(255,255,255,0.05); border: 1px solid var(--border);
      border-radius: 8px; padding: 7px 10px; color: var(--text-primary); font-size:13px; outline:none;
    }
    .discount-row input:focus { border-color: var(--brand); }

    /* Cart Actions */
    .cart-actions { padding: 12px 16px; display: flex; gap: 8px; border-top: 1px solid var(--border); }

    /* Table Selector */
    .table-select { padding: 10px 16px; border-bottom: 1px solid var(--border); }
    .table-select select {
      width:100%; background: rgba(255,255,255,0.05);
      border: 1px solid var(--border); border-radius: 8px;
      padding: 8px 10px; color: var(--text-primary); font-size:13px; outline:none;
    }
    .table-select select:focus { border-color: var(--brand); }

    /* Payment Modal */
    .payment-modal { max-width: 440px; }
    .pay-methods { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 16px 0; }
    .pay-method-btn {
      padding: 14px; border-radius: var(--radius-md);
      border: 1.5px solid var(--border); background: var(--bg-card);
      color: var(--text-secondary); cursor: pointer; transition: all var(--transition);
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 600;
    }
    .pay-method-btn .icon { font-size: 26px; }
    .pay-method-btn:hover { border-color: var(--brand); color: var(--brand); }
    .pay-method-btn.selected { border-color: var(--brand); background: rgba(245,158,11,0.12); color: var(--brand); }
    .pay-total { font-size: 28px; font-weight: 900; color: var(--brand); text-align:center; margin: 12px 0; }
    .pay-total small { font-size:14px; color: var(--text-muted); }
    .change-display {
      background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3);
      border-radius: 10px; padding: 12px; text-align: center;
      font-size: 15px; font-weight: 700; color: var(--accent-green); margin-top: 10px;
      display: none;
    }

    /* Receipt Modal */
    .receipt-modal { max-width: 380px; }
    .receipt-body { background: #fff; color: #000; border-radius: 10px; padding: 20px; font-family: monospace; font-size: 13px; }
    .receipt-header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 12px; margin-bottom: 12px; }
    .receipt-row { display:flex; justify-content:space-between; padding: 3px 0; }
    .receipt-footer { border-top: 2px dashed #ccc; margin-top: 12px; padding-top: 10px; text-align:center; font-size: 11px; color: #666; }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="pos-layout">
    <!-- ── LEFT: Menu Panel ─────────────────────────── -->
    <div class="menu-panel">
      <div class="pos-header">
        <h1>🍔 New Order</h1>
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Search items..." oninput="filterItems(this.value)">
        </div>
      </div>

      <!-- Category Tabs -->
      <div class="cat-tabs" id="catTabs">
        <button class="cat-tab active" data-cat="all" onclick="filterCat('all',this)">✨ All</button>
      </div>

      <!-- Items Grid -->
      <div class="items-grid" id="itemsGrid">
        <div style="grid-column:1/-1; display:flex; align-items:center; justify-content:center; height:200px;">
          <div class="spinner"></div>
        </div>
      </div>
    </div>

    <!-- ── RIGHT: Cart Panel ────────────────────────── -->
    <div class="cart-panel">
      <div class="cart-header">
        <h2><i class="fas fa-shopping-cart"></i> Cart</h2>
        <span class="cart-count" id="cartCount">0</span>
        <button class="btn btn-ghost btn-sm" onclick="clearCart()" id="clearBtn" style="display:none">
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>

      <!-- Order Type -->
      <div class="order-type-bar">
        <button class="order-type-btn active" id="ot-dine" onclick="setOrderType('dine-in',this)">🪑 Dine-In</button>
        <button class="order-type-btn" id="ot-take" onclick="setOrderType('takeaway',this)">🥡 Takeaway</button>
        <button class="order-type-btn" id="ot-del"  onclick="setOrderType('delivery',this)">🛵 Delivery</button>
      </div>

      <!-- Table Selector (shown for dine-in) -->
      <div class="table-select" id="tableSelectWrap">
        <select id="tableSelect"><option value="">— Select Table —</option></select>
      </div>

      <!-- Cart Items -->
      <div class="cart-items" id="cartItems">
        <div class="cart-empty">
          <i class="fas fa-shopping-cart"></i>
          <p>Cart is empty.<br>Tap an item to add.</p>
        </div>
      </div>

      <!-- Cart Summary -->
      <div class="cart-summary" id="cartSummary" style="display:none">
        <!-- Discount -->
        <div class="discount-row">
          <input type="number" id="discountInput" placeholder="Discount (Rs.)" min="0" step="10" oninput="updateTotals()">
          <span style="font-size:12px;color:var(--text-muted)">off</span>
        </div>

        <div class="summary-row"><span>Subtotal</span><span id="subtotalVal">Rs. 0</span></div>
        <div class="summary-row"><span>GST (5%)</span><span id="taxVal">Rs. 0</span></div>
        <div class="summary-row" id="discountRow" style="display:none;color:var(--accent-green)"><span>Discount</span><span id="discountVal">- Rs. 0</span></div>
        <div class="summary-row total"><span>TOTAL</span><span class="val" id="totalVal">Rs. 0</span></div>
      </div>

      <!-- Cart Actions -->
      <div class="cart-actions">
        <button class="btn btn-outline w-full" onclick="clearCart()" id="clearBtn2" style="display:none">
          <i class="fas fa-times"></i> Clear
        </button>
        <button class="btn btn-brand w-full" id="checkoutBtn" onclick="openPaymentModal()" disabled>
          <i class="fas fa-credit-card"></i> Checkout
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Payment Modal ──────────────────────────────────── -->
<div class="modal-overlay" id="paymentModalOverlay">
  <div class="modal payment-modal">
    <div class="modal-header">
      <div class="modal-title">💳 Select Payment Method</div>
      <button class="modal-close" onclick="closeModal('paymentModalOverlay')"><i class="fas fa-times"></i></button>
    </div>

    <div class="pay-total">
      <small>Total Due</small><br>
      <span id="payTotalDisplay">Rs. 0</span>
    </div>

    <div class="pay-methods">
      <button class="pay-method-btn selected" id="pm-cash" onclick="selectPayMethod('cash')"><span class="icon">💵</span> Cash</button>
      <button class="pay-method-btn" id="pm-card" onclick="selectPayMethod('card')"><span class="icon">💳</span> Card</button>
      <button class="pay-method-btn" id="pm-easypaisa" onclick="selectPayMethod('easypaisa')"><span class="icon">📱</span> Easypaisa</button>
      <button class="pay-method-btn" id="pm-jazzcash" onclick="selectPayMethod('jazzcash')"><span class="icon">📲</span> JazzCash</button>
    </div>

    <!-- Cash received field -->
    <div id="cashReceivedWrap">
      <label class="form-label">Cash Received (Rs.)</label>
      <input type="number" class="form-control" id="cashReceived" placeholder="Enter amount received" oninput="calcChange()" min="0">
      <div class="change-display" id="changeDisplay"></div>
    </div>

    <div class="form-group mt-16">
      <label class="form-label">Order Notes (Optional)</label>
      <input type="text" class="form-control" id="orderNotes" placeholder="Special instructions...">
    </div>

    <button class="btn btn-brand btn-block btn-lg mt-16" id="confirmPayBtn" onclick="confirmOrder()">
      <i class="fas fa-check-circle"></i> Confirm Order
    </button>
  </div>
</div>

<!-- ── Receipt Modal ──────────────────────────────────── -->
<div class="modal-overlay" id="receiptModalOverlay">
  <div class="modal receipt-modal">
    <div class="modal-header">
      <div class="modal-title">🧾 Receipt</div>
      <button class="modal-close" onclick="closeModal('receiptModalOverlay');newOrder()"><i class="fas fa-times"></i></button>
    </div>
    <div class="receipt-body" id="receiptBody"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn btn-outline w-full" onclick="printReceipt()"><i class="fas fa-print"></i> Print</button>
      <button class="btn btn-brand w-full" onclick="closeModal('receiptModalOverlay');newOrder()"><i class="fas fa-plus"></i> New Order</button>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="/Resturent_POS/js/app.js"></script>
<script>
// ── State ─────────────────────────────────────────────
let menuData    = [];  // [{id, name, icon, items:[...]}]
let cart        = [];  // [{id, name, emoji, price, qty}]
let orderType   = 'dine-in';
let payMethod   = 'cash';
let lastOrderData = null;

const EMOJIS = { 1:'🍔', 2:'🎁', 3:'🍟', 4:'🥤', 5:'🍦', 6:'🍗' };

// ── Load Menu ─────────────────────────────────────────
async function loadMenu() {
  try {
    const res = await api('menu.php?action=pos');
    menuData = res.data;
    buildCategoryTabs();
    renderAllItems();
    loadTables();
  } catch(e) {
    document.getElementById('itemsGrid').innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><p>Failed to load menu</p></div>`;
  }
}

function buildCategoryTabs() {
  const tabs = document.getElementById('catTabs');
  tabs.innerHTML = `<button class="cat-tab active" data-cat="all" onclick="filterCat('all',this)">✨ All</button>`;
  menuData.forEach(cat => {
    tabs.innerHTML += `<button class="cat-tab" data-cat="${cat.id}" onclick="filterCat(${cat.id},this)">${cat.icon} ${cat.name}</button>`;
  });
}

function renderAllItems(search = '') {
  const grid = document.getElementById('itemsGrid');
  let html = '';
  let count = 0;
  menuData.forEach(cat => {
    cat.items.filter(it => !search || it.name.toLowerCase().includes(search.toLowerCase())).forEach(it => {
      const emoji = EMOJIS[it.category_id] || '🍽️';
      html += `
        <div class="item-card ${!it.is_available ? 'unavailable' : ''}" onclick="${it.is_available ? `addToCart(${it.id},'${it.name.replace(/'/g,"\\'")}','${emoji}',${it.price})` : ''}">
          <span class="item-emoji">${emoji}</span>
          <div class="item-name">${it.name}</div>
          <div class="item-desc">${it.description || ''}</div>
          <div class="item-price">${formatPKR(it.price)}</div>
          ${it.is_available ? '<div class="item-add-btn"><i class="fas fa-plus"></i></div>' : '<div style="text-align:center;font-size:10px;color:var(--accent-red);margin-top:4px">Not Available</div>'}
        </div>`;
      count++;
    });
  });
  grid.innerHTML = html || `<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">🔍</div><p>No items found</p></div>`;
}

function filterCat(catId, btn) {
  document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  const grid = document.getElementById('itemsGrid');
  let html = '';
  const cats = catId === 'all' ? menuData : menuData.filter(c => c.id == catId);
  cats.forEach(cat => {
    cat.items.forEach(it => {
      const emoji = EMOJIS[it.category_id] || '🍽️';
      html += `
        <div class="item-card ${!it.is_available ? 'unavailable' : ''}" onclick="${it.is_available ? `addToCart(${it.id},'${it.name.replace(/'/g,"\\'")}','${emoji}',${it.price})` : ''}">
          <span class="item-emoji">${emoji}</span>
          <div class="item-name">${it.name}</div>
          <div class="item-desc">${it.description || ''}</div>
          <div class="item-price">${formatPKR(it.price)}</div>
          ${it.is_available ? '<div class="item-add-btn"><i class="fas fa-plus"></i></div>' : ''}
        </div>`;
    });
  });
  grid.innerHTML = html || `<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">🍽️</div><p>No items in this category</p></div>`;
}

const filterItems = debounce((val) => renderAllItems(val), 250);

// ── Cart Logic ────────────────────────────────────────
function addToCart(id, name, emoji, price) {
  const existing = cart.find(i => i.id === id);
  if (existing) { existing.qty++; }
  else { cart.push({ id, name, emoji, price: parseFloat(price), qty: 1 }); }
  renderCart();
  showToast(`${name} added`, 'success', 1500);
}

function changeQty(id, delta) {
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
  renderCart();
}

function clearCart() {
  cart = [];
  document.getElementById('discountInput').value = '';
  renderCart();
}

function renderCart() {
  const count = cart.reduce((s, i) => s + i.qty, 0);
  document.getElementById('cartCount').textContent = count;

  const cartDiv = document.getElementById('cartItems');
  const summaryDiv = document.getElementById('cartSummary');
  const checkoutBtn = document.getElementById('checkoutBtn');
  const clearBtns = [document.getElementById('clearBtn'), document.getElementById('clearBtn2')];

  if (cart.length === 0) {
    cartDiv.innerHTML = `<div class="cart-empty"><i class="fas fa-shopping-cart"></i><p>Cart is empty.<br>Tap an item to add.</p></div>`;
    summaryDiv.style.display = 'none';
    checkoutBtn.disabled = true;
    clearBtns.forEach(b => b && (b.style.display = 'none'));
    return;
  }

  cartDiv.innerHTML = cart.map(item => `
    <div class="cart-item">
      <span class="cart-item-emoji">${item.emoji}</span>
      <div class="cart-item-info">
        <div class="cart-item-name">${item.name}</div>
        <div class="cart-item-price">${formatPKR(item.price)} each</div>
      </div>
      <div class="cart-item-controls">
        <button class="qty-btn minus" onclick="changeQty(${item.id},-1)">−</button>
        <span class="qty-num">${item.qty}</span>
        <button class="qty-btn" onclick="changeQty(${item.id},1)">+</button>
      </div>
      <div class="cart-item-total">${formatPKR(item.price * item.qty)}</div>
    </div>`).join('');

  summaryDiv.style.display = 'block';
  checkoutBtn.disabled = false;
  clearBtns.forEach(b => b && (b.style.display = ''));
  updateTotals();
}

function updateTotals() {
  const subtotal = cart.reduce((s,i) => s + i.price * i.qty, 0);
  const tax      = subtotal * 0.05;
  const discount = parseFloat(document.getElementById('discountInput').value) || 0;
  const total    = Math.max(0, subtotal + tax - discount);

  document.getElementById('subtotalVal').textContent = formatPKR(subtotal);
  document.getElementById('taxVal').textContent      = formatPKR(tax);
  document.getElementById('totalVal').textContent    = formatPKR(total);

  const discRow = document.getElementById('discountRow');
  if (discount > 0) {
    discRow.style.display = 'flex';
    document.getElementById('discountVal').textContent = `- ${formatPKR(discount)}`;
  } else {
    discRow.style.display = 'none';
  }
}

function getTotal() {
  const subtotal = cart.reduce((s,i) => s + i.price * i.qty, 0);
  const tax      = subtotal * 0.05;
  const discount = parseFloat(document.getElementById('discountInput').value) || 0;
  return Math.max(0, subtotal + tax - discount);
}

// ── Order Type ────────────────────────────────────────
function setOrderType(type, btn) {
  orderType = type;
  document.querySelectorAll('.order-type-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tableSelectWrap').style.display = type === 'dine-in' ? 'block' : 'none';
}

// ── Tables ────────────────────────────────────────────
async function loadTables() {
  try {
    const res = await api('tables.php?action=list');
    const sel = document.getElementById('tableSelect');
    sel.innerHTML = '<option value="">— Select Table —</option>';
    res.data.filter(t => t.status === 'available').forEach(t => {
      sel.innerHTML += `<option value="${t.id}">Table ${t.table_number} (${t.capacity} seats)</option>`;
    });
  } catch(_) {}
}

// ── Payment Modal ─────────────────────────────────────
function openPaymentModal() {
  if (cart.length === 0) return;
  const total = getTotal();
  document.getElementById('payTotalDisplay').textContent = formatPKR(total);
  document.getElementById('cashReceived').value = '';
  document.getElementById('changeDisplay').style.display = 'none';
  openModal('paymentModalOverlay');
}

function selectPayMethod(method) {
  payMethod = method;
  document.querySelectorAll('.pay-method-btn').forEach(b => b.classList.remove('selected'));
  document.getElementById(`pm-${method}`).classList.add('selected');
  const cashWrap = document.getElementById('cashReceivedWrap');
  cashWrap.style.display = method === 'cash' ? 'block' : 'none';
  document.getElementById('changeDisplay').style.display = 'none';
}

function calcChange() {
  const total = getTotal();
  const received = parseFloat(document.getElementById('cashReceived').value) || 0;
  const change = received - total;
  const display = document.getElementById('changeDisplay');
  if (received > 0 && change >= 0) {
    display.style.display = 'block';
    display.innerHTML = `💰 Change: <strong>${formatPKR(change)}</strong>`;
  } else if (received > 0 && change < 0) {
    display.style.display = 'block';
    display.style.background = 'rgba(239,68,68,0.1)';
    display.style.borderColor = 'rgba(239,68,68,0.3)';
    display.style.color = 'var(--accent-red)';
    display.innerHTML = `⚠️ Short by: <strong>${formatPKR(Math.abs(change))}</strong>`;
  } else {
    display.style.display = 'none';
  }
}

// ── Confirm Order ─────────────────────────────────────
async function confirmOrder() {
  if (cart.length === 0) return;
  const total      = getTotal();
  const subtotal   = cart.reduce((s,i) => s + i.price * i.qty, 0);
  const tax        = subtotal * 0.05;
  const discount   = parseFloat(document.getElementById('discountInput').value) || 0;
  const tableId    = document.getElementById('tableSelect').value || null;
  const cashAmt    = parseFloat(document.getElementById('cashReceived').value) || total;
  const notes      = document.getElementById('orderNotes').value;

  if (payMethod === 'cash' && cashAmt < total) {
    showToast('Cash received is less than total!', 'error'); return;
  }

  const btn = document.getElementById('confirmPayBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

  const payload = {
    order_type: orderType,
    table_id: tableId ? parseInt(tableId) : null,
    discount,
    payment_method: payMethod,
    amount_paid: payMethod === 'cash' ? cashAmt : total,
    notes,
    items: cart.map(i => ({
      menu_item_id: i.id,
      item_name: i.name,
      quantity: i.qty,
      unit_price: i.price,
    }))
  };

  try {
    const res = await api('orders.php?action=create', 'POST', payload);
    lastOrderData = { ...res, cart: [...cart], subtotal, tax, discount, total, payMethod, orderType, tableId, cashAmt };
    closeModal('paymentModalOverlay');
    showReceipt(lastOrderData);
  } catch(e) {
    showToast('Failed to place order: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Order';
  }
}

// ── Receipt ───────────────────────────────────────────
function showReceipt(d) {
  const now = new Date().toLocaleString('en-PK');
  const itemRows = d.cart.map(i =>
    `<div class="receipt-row"><span>${i.qty}x ${i.name}</span><span>${formatPKR(i.price*i.qty)}</span></div>`
  ).join('');

  document.getElementById('receiptBody').innerHTML = `
    <div class="receipt-header">
      <div style="font-size:20px">🍔</div>
      <strong style="font-size:16px;display:block">Khan Fast Food</strong>
      <div>Point of Sale System</div>
      <div style="font-size:11px;color:#666">${now}</div>
      <div style="margin-top:6px"><strong>Order#: ${d.order_number}</strong></div>
      <div>${d.orderType.toUpperCase()}</div>
    </div>
    ${itemRows}
    <div class="receipt-row" style="margin-top:8px;padding-top:8px;border-top:1px dashed #ccc"><span>Subtotal</span><span>${formatPKR(d.subtotal)}</span></div>
    <div class="receipt-row"><span>GST (5%)</span><span>${formatPKR(d.tax)}</span></div>
    ${d.discount > 0 ? `<div class="receipt-row"><span>Discount</span><span>- ${formatPKR(d.discount)}</span></div>` : ''}
    <div class="receipt-row" style="font-weight:700;font-size:15px;margin-top:6px;padding-top:6px;border-top:2px dashed #ccc"><span>TOTAL</span><span>${formatPKR(d.total)}</span></div>
    <div class="receipt-row"><span>Payment</span><span style="text-transform:uppercase">${d.payMethod}</span></div>
    ${d.payMethod==='cash' ? `<div class="receipt-row"><span>Received</span><span>${formatPKR(d.cashAmt)}</span></div><div class="receipt-row"><span>Change</span><span>${formatPKR(d.cashAmt-d.total)}</span></div>` : ''}
    <div class="receipt-footer">Thank you for visiting Khan Fast Food!<br>Come again 😊</div>
  `;
  openModal('receiptModalOverlay');
}

function printReceipt() {
  const content = document.getElementById('receiptBody').innerHTML;
  const w = window.open('','_blank','width=350,height=600');
  w.document.write(`<html><head><title>Receipt</title><style>body{font-family:monospace;padding:16px}.receipt-row{display:flex;justify-content:space-between;padding:3px 0}.receipt-header{text-align:center;border-bottom:2px dashed #ccc;padding-bottom:12px;margin-bottom:12px}.receipt-footer{border-top:2px dashed #ccc;margin-top:12px;padding-top:10px;text-align:center;font-size:11px;color:#666}</style></head><body>${content}<script>window.print();window.close();<\/script></body></html>`);
  w.document.close();
}

function newOrder() {
  clearCart();
  loadTables();
  document.getElementById('tableSelect').value = '';
  document.getElementById('orderNotes').value = '';
}

// ── Init ──────────────────────────────────────────────
loadMenu();
</script>
</body>
</html>
