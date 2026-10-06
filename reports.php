<?php
require_once __DIR__ . '/config/session.php';
requireManager();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports — Restaurant POS</title>
  <link rel="stylesheet" href="/Resturent_POS/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .report-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:24px; }
    .chart-row { display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:20px; }
    .chart-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); padding:22px; }
    .chart-title { font-size:15px; font-weight:700; margin-bottom:16px; }
    .top-item-row { display:flex; align-items:center; gap:12px; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.04); font-size:13px; }
    .top-item-row:last-child { border-bottom:none; }
    .top-item-bar-wrap { flex:1; background:rgba(255,255,255,0.06); border-radius:99px; height:6px; overflow:hidden; }
    .top-item-bar { height:100%; background:linear-gradient(90deg,var(--brand),var(--brand-dark)); border-radius:99px; }
    .date-filter { display:flex; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
    .preset-btn { padding:7px 14px; border-radius:99px; border:1px solid var(--border); background:transparent; color:var(--text-muted); font-size:12px; font-weight:600; cursor:pointer; transition:all var(--transition); }
    .preset-btn.active { background:var(--brand); border-color:var(--brand); color:#000; }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📈 Reports & Analytics</div>
      <div class="topbar-actions">
      </div>
    </div>
    <div class="page-body">
      <!-- Date Filters -->
      <div class="date-filter">
        <button class="preset-btn active" onclick="setPreset('today')">Today</button>
        <button class="preset-btn" onclick="setPreset('month')">This Month</button>
        <button class="preset-btn" onclick="setPreset('week')">This Week</button>
        <div style="display:flex;align-items:center;gap:8px;margin-left:10px">
          <input type="date" class="form-control" id="fromDate" style="width:auto">
          <span style="color:var(--text-muted)">to</span>
          <input type="date" class="form-control" id="toDate" style="width:auto">
          <button class="btn btn-brand btn-sm" onclick="loadReports()"><i class="fas fa-search"></i></button>
        </div>
      </div>

      <!-- Summary KPIs -->
      <div class="report-summary" id="reportSummary">
        <div class="kpi-card"><div class="kpi-icon amber"><i class="fas fa-spinner fa-spin"></i></div><div class="kpi-data"><div class="kpi-value">—</div><div class="kpi-label">Loading...</div></div></div>
      </div>

      <!-- Revenue Chart + Top Items -->
      <div class="chart-row">
        <div class="chart-card">
          <div class="chart-title">📊 Daily Revenue</div>
          <canvas id="dailyChart" height="100"></canvas>
        </div>
        <div class="chart-card">
          <div class="chart-title">🏆 Top Selling Items</div>
          <div id="topItemsReport"></div>
        </div>
      </div>

      <!-- Payment + Category Charts -->
      <div class="chart-row">
        <div class="chart-card">
          <div class="chart-title">🍽️ Revenue by Category</div>
          <canvas id="categoryChart" height="100"></canvas>
        </div>
        <div class="chart-card">
          <div class="chart-title">💳 Payment Methods</div>
          <canvas id="paymentPieChart" height="100"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="/Resturent_POS/js/app.js"></script>
<script>
let dailyChart, categoryChart, paymentChart;
let reportData = null;

function setPreset(type) {
  document.querySelectorAll('.preset-btn').forEach(b => {
    b.classList.remove('active');
    if (b.getAttribute('onclick') === `setPreset('${type}')`) b.classList.add('active');
  });
  const today = new Date();
  let from, to = today.toISOString().slice(0,10);
  if (type === 'today') { from = to; }
  else if (type === 'week') { const d = new Date(today); d.setDate(d.getDate()-6); from = d.toISOString().slice(0,10); }
  else if (type === 'month') { from = today.toISOString().slice(0,8) + '01'; }
  document.getElementById('fromDate').value = from;
  document.getElementById('toDate').value   = to;
  loadReports();
}

async function loadReports() {
  const from = document.getElementById('fromDate').value;
  const to   = document.getElementById('toDate').value;
  if (!from || !to) { showToast('Select date range','error'); return; }

  try {
    const res = await api(`reports.php?from=${from}&to=${to}`);
    reportData = res.data;
    renderSummary(reportData.summary);
    renderDailyChart(reportData.daily);
    renderTopItems(reportData.top_items);
    renderCategoryChart(reportData.by_category);
    renderPaymentChart(reportData.payment_methods);
  } catch(e) { showToast('Failed to load reports','error'); }
}

function renderSummary(s) {
  document.getElementById('reportSummary').innerHTML = `
    <div class="kpi-card fade-in">
      <div class="kpi-icon amber"><i class="fas fa-coins"></i></div>
      <div class="kpi-data"><div class="kpi-value">${formatPKR(s.total_revenue)}</div><div class="kpi-label">Total Revenue</div></div>
    </div>
    <div class="kpi-card fade-in">
      <div class="kpi-icon green"><i class="fas fa-receipt"></i></div>
      <div class="kpi-data"><div class="kpi-value">${s.total_orders}</div><div class="kpi-label">Total Orders</div></div>
    </div>
    <div class="kpi-card fade-in">
      <div class="kpi-icon blue"><i class="fas fa-chart-line"></i></div>
      <div class="kpi-data"><div class="kpi-value">${formatPKR(Math.round(s.avg_order))}</div><div class="kpi-label">Avg Order Value</div></div>
    </div>
    <div class="kpi-card fade-in">
      <div class="kpi-icon purple"><i class="fas fa-tag"></i></div>
      <div class="kpi-data"><div class="kpi-value">${formatPKR(s.total_discount)}</div><div class="kpi-label">Total Discounts</div></div>
    </div>
  `;
}

function renderDailyChart(data) {
  if (dailyChart) dailyChart.destroy();
  if (!data.length) return;
  dailyChart = new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
      labels: data.map(d => d.date),
      datasets: [
        { label: 'Revenue', data: data.map(d => d.revenue), backgroundColor: 'rgba(245,158,11,0.3)', borderColor: '#F59E0B', borderWidth:2, borderRadius:6, yAxisID:'y' },
        { label: 'Orders',  data: data.map(d => d.orders),  backgroundColor: 'rgba(59,130,246,0.3)',  borderColor: '#3B82F6', borderWidth:2, borderRadius:6, type:'line', yAxisID:'y1' },
      ]
    },
    options: {
      responsive:true,
      plugins:{legend:{labels:{color:'#94A3B8'}}},
      scales:{
        x:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#94A3B8'}},
        y:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#94A3B8',callback:v=>'Rs.'+v.toLocaleString()},position:'left'},
        y1:{grid:{display:false},ticks:{color:'#3B82F6'},position:'right'},
      }
    }
  });
}

function renderTopItems(items) {
  const max = items.length ? Math.max(...items.map(i=>i.qty)) : 1;
  document.getElementById('topItemsReport').innerHTML = items.slice(0,8).map((it,i) => `
    <div class="top-item-row">
      <span style="color:var(--text-muted);font-size:12px;min-width:18px">${i+1}</span>
      <div style="flex:1">
        <div style="font-weight:600">${it.name}</div>
        <div class="top-item-bar-wrap" style="margin-top:4px"><div class="top-item-bar" style="width:${(it.qty/max)*100}%"></div></div>
      </div>
      <div style="text-align:right">
        <div style="color:var(--brand);font-weight:700">${it.qty} sold</div>
        <div style="font-size:11px;color:var(--text-muted)">${formatPKR(it.revenue)}</div>
      </div>
    </div>`).join('') || '<div class="empty-state"><p>No data for this period</p></div>';
}

function renderCategoryChart(data) {
  if (categoryChart) categoryChart.destroy();
  if (!data.length) return;
  categoryChart = new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
      labels: data.map(d => d.category),
      datasets: [{ label: 'Revenue', data: data.map(d=>d.revenue), backgroundColor: ['#F59E0B','#3B82F6','#10B981','#8B5CF6','#EF4444','#06B6D4'].slice(0,data.length), borderRadius:6 }]
    },
    options: {
      responsive:true, indexAxis:'y',
      plugins:{legend:{display:false}},
      scales:{
        x:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#94A3B8',callback:v=>'Rs.'+v.toLocaleString()}},
        y:{grid:{display:false},ticks:{color:'#94A3B8'}},
      }
    }
  });
}

function renderPaymentChart(data) {
  if (paymentChart) paymentChart.destroy();
  if (!data.length) return;
  const labels = data.map(d => {
    const map={cash:'💵 Cash',card:'💳 Card',easypaisa:'📱 Easypaisa',jazzcash:'📲 JazzCash'};
    return map[d.payment_method]||d.payment_method;
  });
  paymentChart = new Chart(document.getElementById('paymentPieChart'), {
    type:'doughnut',
    data:{ labels, datasets:[{ data:data.map(d=>d.revenue), backgroundColor:['#F59E0B','#3B82F6','#10B981','#8B5CF6'], borderColor:'#0D1228', borderWidth:3 }] },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom', labels:{ color:'#94A3B8', padding:14, font:{size:12} } } } }
  });
}

function exportCSV() {
  if (!reportData) { showToast('Load a report first','error'); return; }
  let csv = 'Date,Orders,Revenue\n';
  reportData.daily.forEach(d => { csv += `${d.date},${d.orders},${d.revenue}\n`; });
  const blob = new Blob([csv], {type:'text/csv'});
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
  a.download = `restaurant_pos_report_${document.getElementById('fromDate').value}_${document.getElementById('toDate').value}.csv`;
  a.click();
}

// Init
setPreset('today');
</script>
</body>
</html>
