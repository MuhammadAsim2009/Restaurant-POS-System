<?php
// ============================================================
// API: Dashboard — KPI summary
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireLogin();

$db   = getDB();
$today = date('Y-m-d');

// Today's revenue & order count
$todayStmt = $db->prepare("SELECT COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
$todayStmt->execute([$today]);
$today_data = $todayStmt->fetch();

// Yesterday for comparison
$ystStmt = $db->prepare("SELECT COALESCE(SUM(total),0) AS revenue FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
$ystStmt->execute([date('Y-m-d', strtotime('-1 day'))]);
$yest_revenue = $ystStmt->fetchColumn();

// Active tables
$activeTablesStmt = $db->query("SELECT COUNT(*) FROM restaurant_tables WHERE status = 'occupied'");
$active_tables = $activeTablesStmt->fetchColumn();

$totalTablesStmt = $db->query("SELECT COUNT(*) FROM restaurant_tables");
$total_tables = $totalTablesStmt->fetchColumn();

// Pending orders (kitchen queue)
$pendingStmt = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','preparing')");
$pending = $pendingStmt->fetchColumn();

// Average order value today
$avgStmt = $db->prepare("SELECT COALESCE(AVG(total),0) AS avg FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
$avgStmt->execute([$today]);
$avg_order = $avgStmt->fetchColumn();

// Last 7 days revenue
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $s = $db->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
    $s->execute([$d]);
    $chartData[] = ['date' => date('D', strtotime($d)), 'revenue' => (float)$s->fetchColumn()];
}

// Top 5 items today
$topStmt = $db->prepare("
    SELECT mi.name, SUM(oi.quantity) AS qty, SUM(oi.subtotal) AS revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE DATE(o.created_at) = ? AND o.status != 'cancelled'
    GROUP BY oi.menu_item_id
    ORDER BY qty DESC LIMIT 5
");
$topStmt->execute([$today]);
$top_items = $topStmt->fetchAll();

// Payment method breakdown today
$payStmt = $db->prepare("SELECT payment_method, COUNT(*) AS count, SUM(total) AS revenue FROM orders WHERE DATE(created_at) = ? AND payment_method IS NOT NULL GROUP BY payment_method");
$payStmt->execute([$today]);
$payment_breakdown = $payStmt->fetchAll();

// Revenue change %
$rev_change = $yest_revenue > 0 ? round((($today_data['revenue'] - $yest_revenue) / $yest_revenue) * 100, 1) : 0;

jsonResponse([
    'success' => true,
    'data' => [
        'today_revenue'      => (float)$today_data['revenue'],
        'today_orders'       => (int)$today_data['orders'],
        'active_tables'      => (int)$active_tables,
        'total_tables'       => (int)$total_tables,
        'pending_orders'     => (int)$pending,
        'avg_order_value'    => round((float)$avg_order, 0),
        'revenue_change'     => $rev_change,
        'chart_data'         => $chartData,
        'top_items'          => $top_items,
        'payment_breakdown'  => $payment_breakdown,
    ]
]);
