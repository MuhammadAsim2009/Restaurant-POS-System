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
  <title>Dashboard — Khan Fast Food POS</title>
  <link rel="stylesheet" href="/Resturent_POS/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap: 18px; }
    .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 24px; }
    .chart-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; }
    .chart-title { font-size: 15px; font-weight: 700; margin-bottom: 18px; }
    .top-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
    .top-item:last-child { border-bottom: none; }
    .top-rank { width: 28px; height: 28px; border-radius: 8px; background: var(--bg-card-hover); display:flex;align-items:center;justify-content:center; font-size:12px; font-weight:700; color:var(--text-muted); flex-shrink:0; }
    .top-rank.gold   { background: rgba(245,158,11,0.2); color: var(--brand); }
    .top-rank.silver { background: rgba(148,163,184,0.15); color: #94A3B8; }
    .top-rank.bronze { background: rgba(180,120,80,0.15); color: #B07840; }
    .top-item-name { flex:1; font-size:13px; font-weight:500; }
    .top-item-qty  { font-size:12px; color:var(--text-muted); }
    .quick-links { display:grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap:12px; margin-top:24px; }
    .quick-link {
      background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
      padding: 20px 16px; text-align:center; cursor:pointer; transition:all var(--transition);
      text-decoration:none; color: var(--text-secondary);
    }
    .quick-link:hover { border-color: var(--border-accent); color: var(--brand); transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .quick-link i { font-size:24px; margin-bottom:8px; display:block; }
    .quick-link span { font-size:13px; font-weight:600; }
    .activity-item { display:flex; gap:12px; padding:12px 0; border-bottom:1px solid rgba(255,255,255,0.04); font-size:13px; }
    .activity-item:last-child { border-bottom:none; }
    .activity-dot { width:8px; height:8px; border-radius:50%; background:var(--brand); flex-shrink:0; margin-top:5px; }
  </style>
</head>
<body>
<div class="app-layout">

  <!-- Sidebar -->
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <div class="topbar">
      <div style="flex:1;">
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-subtitle" id="currentDate"></div>
      </div>
      <div class="topbar-actions">
        <a href="/Resturent_POS/pos.php" class="btn btn-brand">
          <i class="fas fa-plus"></i> New Order
        </a>
      </div>
    </div>

    <div class="page-body">
      <!-- KPI Cards -->
      <div class="kpi-grid" id="kpiGrid">
        <div class="kpi-card"><div class="kpi-icon amber"><i class="fas fa-spinner fa-spin"></i></div><div class="kpi-data"><div class="kpi-value">—</div><div class="kpi-label">Loading...</div></div></div>
        <div class="kpi-card"><div class="kpi-icon green"><i class="fas fa-spinner fa-spin"></i></div><div class="kpi-data"><div class="kpi-value">—</div><div class="kpi-label">Loading...</div></div></div>
        <div class="kpi-card"><div class="kpi-icon blue"><i class="fas fa-spinner fa-spin"></i></div><div class="kpi-data"><div class="kpi-value">—</div><div class="kpi-label">Loading...</div></div></div>
        <div class="kpi-card"><div class="kpi-icon purple"><i class="fas fa-spinner fa-spin"></i></div><div class="kpi-data"><div class="kpi-value">—</div><div class="kpi-label">Loading...</div></div></div>
      </div>

      <!-- Quick Links -->
      <div class="section-title mt-24">Quick <span>Access</span></div>
      <div class="quick-links">
        <a href="/Resturent_POS/pos.php"     class="quick-link"><i class="fas fa-cash-register"></i><span>New Order</span></a>
        <a href="/Resturent_POS/tables.php"  class="quick-link"><i class="fas fa-chair"></i><span>Tables</span></a>
        <a href="/Resturent_POS/kitchen.php" class="quick-link"><i class="fas fa-fire-burner"></i><span>Kitchen</span></a>
        <a href="/Resturent_POS/orders.php"  class="quick-link"><i class="fas fa-receipt"></i><span>Orders</span></a>
        <?php if (isManager()): ?>
        <a href="/Resturent_POS/menu.php"    class="quick-link"><i class="fas fa-utensils"></i><span>Menu</span></a>
        <a href="/Resturent_POS/reports.php" class="quick-link"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
        <?php endif; ?>
      </div>

      <!-- Charts -->
      <div class="charts-grid">
        <div class="chart-card">
          <div class="chart-title">📈 Revenue — Last 7 Days</div>
          <canvas id="revenueChart" height="90"></canvas>
        </div>
        <div class="chart-card">
          <div class="chart-title">🏆 Top Items Today</div>
          <div id="topItemsList"><div class="spinner" style="margin-top:20px"></div></div>
        </div>
      </div>

      <!-- Payment Breakdown -->
      <div class="charts-grid" style="margin-top:20px">
        <div class="chart-card">
          <div class="chart-title">💳 Payment Methods Today</div>
          <div style="max-width: 240px; margin: 0 auto;">
            <canvas id="paymentChart"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-title">📋 Recent Activity</div>
          <div id="activityList"><div class="spinner" style="margin-top:20px"></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="/Resturent_POS/js/app.js"></script>
<script>
document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-PK',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

let revenueChart, paymentChart;

async function loadDashboard() {
  try {
    const res = await api('dashboard.php');
    const d = res.data;

    // KPI Cards
    const revChange = d.revenue_change;
    const changeDir = revChange >= 0 ? 'up' : 'down';
    const changeIcon = revChange >= 0 ? '↑' : '↓';

    document.getElementById('kpiGrid').innerHTML = `
      <div class="kpi-card fade-in">
        <div class="kpi-icon amber"><i class="fas fa-coins"></i></div>
        <div class="kpi-data">
          <div class="kpi-value">${formatPKR(d.today_revenue)}</div>
          <div class="kpi-label">Today's Revenue</div>
          <div class="kpi-change ${changeDir}">${changeIcon} ${Math.abs(revChange)}% vs yesterday</div>
        </div>
      </div>
      <div class="kpi-card fade-in">
        <div class="kpi-icon green"><i class="fas fa-shopping-bag"></i></div>
        <div class="kpi-data">
          <div class="kpi-value">${d.today_orders}</div>
          <div class="kpi-label">Orders Today</div>
          <div class="kpi-change">${formatPKR(d.avg_order_value)} avg</div>
        </div>
      </div>
      <div class="kpi-card fade-in">
        <div class="kpi-icon blue"><i class="fas fa-chair"></i></div>
        <div class="kpi-data">
          <div class="kpi-value">${d.active_tables}<span style="font-size:15px;color:var(--text-muted)"> / ${d.total_tables}</span></div>
          <div class="kpi-label">Tables Occupied</div>
        </div>
      </div>
      <div class="kpi-card fade-in">
        <div class="kpi-icon ${d.pending_orders > 0 ? 'red' : 'purple'}"><i class="fas fa-fire"></i></div>
        <div class="kpi-data">
          <div class="kpi-value">${d.pending_orders}</div>
          <div class="kpi-label">Kitchen Queue</div>
        </div>
      </div>
    `;

    // Revenue Chart
    const labels = d.chart_data.map(x => x.date);
    const revData = d.chart_data.map(x => x.revenue);
    if (revenueChart) revenueChart.destroy();
    revenueChart = new Chart(document.getElementById('revenueChart'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Revenue (Rs.)',
          data: revData,
          backgroundColor: 'rgba(245,158,11,0.3)',
          borderColor: '#F59E0B',
          borderWidth: 2,
          borderRadius: 8,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94A3B8' } },
          y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94A3B8', callback: v => 'Rs.' + v.toLocaleString() } }
        }
      }
    });

    // Top Items
    const ranks = ['gold','silver','bronze'];
    const topHtml = d.top_items.length ? d.top_items.slice(0,5).map((it,i) => `
      <div class="top-item">
        <div class="top-rank ${ranks[i] || ''}">${i+1}</div>
        <div class="top-item-name">${it.name}</div>
        <div class="top-item-qty">${it.qty} sold</div>
      </div>`).join('') : '<div class="empty-state"><div class="empty-icon">🍽️</div><p>No orders yet today</p></div>';
    document.getElementById('topItemsList').innerHTML = topHtml;

    // Payment Chart
    if (d.payment_breakdown.length) {
      const payLabels = d.payment_breakdown.map(p => {
        const map = { cash: '💵 Cash', card: '💳 Card', easypaisa: '📱 Easypaisa', jazzcash: '📲 JazzCash' };
        return map[p.payment_method] || p.payment_method;
      });
      const payData = d.payment_breakdown.map(p => parseFloat(p.revenue));
      if (paymentChart) paymentChart.destroy();
      paymentChart = new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
          labels: payLabels,
          datasets: [{
            data: payData,
            backgroundColor: ['#F59E0B','#3B82F6','#10B981','#8B5CF6'],
            borderColor: '#0D1228',
            borderWidth: 3,
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom', labels: { color: '#94A3B8', padding: 16, font: { size: 12 } } }
          }
        }
      });
    } else {
      document.getElementById('paymentChart').parentElement.innerHTML += '<div style="text-align:center;color:var(--text-muted);font-size:13px;padding:20px 0">No payments recorded today</div>';
    }

    // Activity
    document.getElementById('activityList').innerHTML = d.top_items.length
      ? d.top_items.map(it => `<div class="activity-item"><div class="activity-dot"></div><div><strong>${it.name}</strong><br><span style="color:var(--text-muted)">${it.qty} sold — ${formatPKR(it.revenue)}</span></div></div>`).join('')
      : '<div class="empty-state"><p>No activity yet</p></div>';

  } catch(e) { showToast('Failed to load dashboard data', 'error'); }
}

loadDashboard();
// Auto-refresh every 60 seconds
setInterval(loadDashboard, 60000);
</script>
</body>
</html>
