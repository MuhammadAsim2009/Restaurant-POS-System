<?php
require_once __DIR__ . '/config/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kitchen Display — Khan Fast Food POS</title>
  <link rel="stylesheet" href="/Resturent_POS/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background: #060B16; }
    .kds-header { background: #0A0F1E; border-bottom:2px solid var(--brand); padding:16px 28px; display:flex; align-items:center; gap:16px; position:sticky; top:0; z-index:10; }
    .kds-header h1 { font-size:20px; font-weight:800; flex:1; }
    .kds-time { font-size:24px; font-weight:700; color:var(--brand); font-variant-numeric:tabular-nums; }
    .kds-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; padding:24px; }
    .kds-card {
      background: var(--bg-secondary); border:2px solid var(--border);
      border-radius: var(--radius-lg); overflow:hidden; transition: all var(--transition);
    }
    .kds-card.pending   { border-color: rgba(245,158,11,0.5); }
    .kds-card.preparing { border-color: rgba(59,130,246,0.6); box-shadow: 0 0 20px rgba(59,130,246,0.15); }
    .kds-card-header {
      padding:14px 18px; border-bottom:1px solid var(--border);
      display:flex; align-items:center; gap:10px;
    }
    .kds-order-num { font-size:16px; font-weight:800; }
    .kds-type      { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); }
    .kds-table     { font-size:12px; color:var(--brand); font-weight:600; }
    .kds-timer     { margin-left:auto; font-size:13px; font-weight:700; padding:4px 10px; border-radius:99px; background:rgba(255,255,255,0.07); font-variant-numeric:tabular-nums; }
    .kds-timer.urgent { background:rgba(239,68,68,0.2); color:var(--accent-red); }
    .kds-items { padding:16px 18px; }
    .kds-item { display:flex; align-items:center; gap:10px; padding:6px 0; font-size:14px; border-bottom:1px solid rgba(255,255,255,0.05); }
    .kds-item:last-child { border-bottom:none; }
    .kds-item-qty { width:28px; height:28px; border-radius:8px; background:rgba(245,158,11,0.15); color:var(--brand); font-weight:800; font-size:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .kds-actions { padding:14px 18px; border-top:1px solid var(--border); display:flex; gap:8px; }
    .kds-count-badge { background:var(--accent-red); color:#fff; border-radius:99px; padding:3px 10px; font-size:12px; font-weight:700; }
    .kds-empty { grid-column:1/-1; display:flex; flex-direction:column; align-items:center; justify-content:center; height:60vh; color:var(--text-muted); gap:16px; }
    .kds-empty i { font-size:64px; opacity:0.2; }
    .kds-empty h2 { font-size:20px; }
    .refresh-indicator { font-size:12px; color:var(--text-muted); }
    @keyframes pulse-border { 0%,100%{box-shadow:0 0 15px rgba(59,130,246,0.2);} 50%{box-shadow:0 0 30px rgba(59,130,246,0.4);} }
    .kds-card.preparing { animation: pulse-border 2s ease-in-out infinite; }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content" style="display:flex;flex-direction:column;overflow:hidden;">
    <div class="topbar">
      <div class="topbar-title">🍳 Kitchen Display</div>
      <div class="topbar-actions">
        <div class="refresh-indicator" id="refreshIndicator" style="font-size:13px;color:var(--text-muted);">Next refresh in <span id="countdown">10</span>s</div>
        <div class="kds-time" id="kdsTime"></div>
        <a href="/Resturent_POS/pos.php" class="btn btn-brand"><i class="fas fa-plus"></i> New Order</a>
      </div>
    </div>
    <div class="page-body" style="overflow-y:auto;flex:1;">
      <div class="kds-grid" id="kdsGrid">
        <div style="grid-column:1/-1;display:flex;align-items:center;justify-content:center;height:50vh">
          <div class="spinner" style="width:50px;height:50px"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script src="/Resturent_POS/js/app.js"></script>
<script>
let refreshTimer = 10;
let orders = [];

// Clock
function updateClock() {
  document.getElementById('kdsTime').textContent = new Date().toLocaleTimeString('en-PK',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
setInterval(updateClock, 1000);
updateClock();

// Countdown
setInterval(() => {
  refreshTimer--;
  document.getElementById('countdown').textContent = refreshTimer;
  if (refreshTimer <= 0) { refreshTimer = 10; loadKitchen(); }
}, 1000);

function elapsedSeconds(dateStr) { return Math.floor((Date.now() - new Date(dateStr)) / 1000); }
function formatElapsed(sec) {
  const m = Math.floor(sec/60), s = sec%60;
  return m > 0 ? `${m}m ${s}s` : `${s}s`;
}

async function loadKitchen() {
  try {
    const res = await api('orders.php?action=kitchen');
    orders = res.data;
    renderKitchen();
  } catch(e) { showToast('Refresh failed','error'); }
}

function renderKitchen() {
  const grid = document.getElementById('kdsGrid');
  if (!orders.length) {
    grid.innerHTML = `<div class="kds-empty"><i class="fas fa-fire-burner"></i><h2>Kitchen is clear!</h2><p>No pending orders right now.</p></div>`;
    return;
  }

  grid.innerHTML = orders.map(o => {
    const elapsed = elapsedSeconds(o.created_at);
    const urgent  = elapsed > 600; // > 10 minutes
    return `
      <div class="kds-card ${o.status}" id="card-${o.id}">
        <div class="kds-card-header">
          <div>
            <div class="kds-order-num">${o.order_number}</div>
            <div class="kds-type">${o.order_type} ${o.table_number ? '• ' + o.table_number : ''}</div>
          </div>
          <div class="kds-timer ${urgent ? 'urgent' : ''}" id="timer-${o.id}" data-created="${o.created_at}">
            ${formatElapsed(elapsed)}
          </div>
        </div>
        <div class="kds-items">
          ${o.items.map(i=>`
            <div class="kds-item">
              <div class="kds-item-qty">${i.quantity}</div>
              <div>${i.item_name}</div>
            </div>`).join('')}
        </div>
        <div class="kds-actions">
          ${o.status === 'pending' ? `<button class="btn btn-brand w-full" onclick="updateStatus(${o.id},'preparing')"><i class="fas fa-fire"></i> Start Preparing</button>` : ''}
          ${o.status === 'preparing' ? `<button class="btn btn-success w-full" onclick="updateStatus(${o.id},'ready')"><i class="fas fa-check"></i> Mark Ready</button>` : ''}
          ${statusBadge(o.status)}
        </div>
      </div>`;
  }).join('');
}

// Live timers
setInterval(() => {
  document.querySelectorAll('[data-created]').forEach(el => {
    const sec = elapsedSeconds(el.dataset.created);
    el.textContent = formatElapsed(sec);
    if (sec > 600) el.classList.add('urgent');
  });
}, 1000);

async function updateStatus(id, status) {
  try {
    await api('orders.php?action=status', 'PUT', { id, status });
    showToast(status === 'ready' ? '✅ Order marked ready!' : '🔥 Preparing started!', 'success');
    loadKitchen();
  } catch(_) {}
}

loadKitchen();
</script>
</body>
</html>
