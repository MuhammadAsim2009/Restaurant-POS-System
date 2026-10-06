<?php
// Shared sidebar partial
require_once __DIR__ . '/../config/session.php';
requireLogin();
$user    = currentUser();
$current = basename($_SERVER['PHP_SELF'], '.php');
$isManager = isManager();
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">🍔</div>
    <div>
      <div class="brand-name">Restaurant</div>
      <div class="brand-sub">POS System</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-title">Main</div>
    <a href="/Resturent_POS/dashboard.php" class="nav-item <?= $current==='dashboard'?'active':'' ?>">
      <svg viewBox="0 0 24 24" width="1.15em" height="1.15em" fill="currentColor" style="margin-right:8px;">
        <rect x="3" y="3" width="7" height="11" rx="2.5" />
        <rect x="3" y="16" width="7" height="5" rx="2.5" />
        <rect x="14" y="3" width="7" height="5" rx="2.5" />
        <rect x="14" y="10" width="7" height="11" rx="2.5" />
      </svg> Dashboard
    </a>
    <a href="/Resturent_POS/pos.php" class="nav-item <?= $current==='pos'?'active':'' ?>">
      <i class="fas fa-cash-register"></i> New Order
    </a>
    <a href="/Resturent_POS/tables.php" class="nav-item <?= $current==='tables'?'active':'' ?>">
      <i class="fas fa-chair"></i> Tables
    </a>

    <div class="nav-section-title">Operations</div>
    <a href="/Resturent_POS/orders.php" class="nav-item <?= $current==='orders'?'active':'' ?>">
      <i class="fas fa-receipt"></i> Orders
      <span class="nav-badge" id="pendingBadge" style="display:none">0</span>
    </a>
    <a href="/Resturent_POS/kitchen.php" class="nav-item <?= $current==='kitchen'?'active':'' ?>">
      <i class="fas fa-fire-burner"></i> Kitchen
    </a>

    <?php if ($isManager): ?>
    <div class="nav-section-title">Management</div>
    <a href="/Resturent_POS/menu.php" class="nav-item <?= $current==='menu'?'active':'' ?>">
      <i class="fas fa-utensils"></i> Menu
    </a>
    <a href="/Resturent_POS/reports.php" class="nav-item <?= $current==='reports'?'active':'' ?>">
      <i class="fas fa-chart-bar"></i> Reports
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="user-role"><?= $user['role'] ?></div>
      </div>
      <button class="logout-btn" onclick="logout()" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
      </button>
    </div>
  </div>
</aside>

<script>
// Update pending orders badge
(async () => {
  try {
    const res = await fetch('/Resturent_POS/api/orders.php?action=kitchen', { headers: {'X-Requested-With':'XMLHttpRequest'} });
    const data = await res.json();
    if (data.success && data.data.length > 0) {
      const badge = document.getElementById('pendingBadge');
      if (badge) { badge.textContent = data.data.length; badge.style.display = ''; }
    }
  } catch(_) {}
})();
</script>
