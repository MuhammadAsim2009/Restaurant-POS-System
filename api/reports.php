<?php
// ============================================================
// API: Reports — Sales analytics
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireLogin();

$db = getDB();
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$type = $_GET['type'] ?? 'daily'; // daily | weekly | monthly

// Revenue by day
$dailyStmt = $db->prepare("
    SELECT DATE(created_at) AS date, COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'
    GROUP BY DATE(created_at) ORDER BY date ASC
");
$dailyStmt->execute([$from, $to]);
$daily = $dailyStmt->fetchAll();

// Top selling items
$topStmt = $db->prepare("
    SELECT mi.name, c.name AS category, SUM(oi.quantity) AS qty, SUM(oi.subtotal) AS revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN categories c ON mi.category_id = c.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
    GROUP BY oi.menu_item_id ORDER BY qty DESC LIMIT 10
");
$topStmt->execute([$from, $to]);
$top_items = $topStmt->fetchAll();

// Revenue by payment method
$payStmt = $db->prepare("
    SELECT payment_method, COUNT(*) AS orders, SUM(total) AS revenue
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND payment_method IS NOT NULL AND status != 'cancelled'
    GROUP BY payment_method
");
$payStmt->execute([$from, $to]);
$payment_methods = $payStmt->fetchAll();

// Summary totals
$summaryStmt = $db->prepare("
    SELECT COUNT(*) AS total_orders, COALESCE(SUM(total),0) AS total_revenue,
           COALESCE(AVG(total),0) AS avg_order, COALESCE(SUM(discount),0) AS total_discount
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'
");
$summaryStmt->execute([$from, $to]);
$summary = $summaryStmt->fetch();

// Revenue by category
$catStmt = $db->prepare("
    SELECT c.name AS category, SUM(oi.subtotal) AS revenue, SUM(oi.quantity) AS qty
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN categories c ON mi.category_id = c.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
    GROUP BY c.id ORDER BY revenue DESC
");
$catStmt->execute([$from, $to]);
$by_category = $catStmt->fetchAll();

jsonResponse([
    'success'  => true,
    'data' => [
        'from'            => $from,
        'to'              => $to,
        'summary'         => $summary,
        'daily'           => $daily,
        'top_items'       => $top_items,
        'payment_methods' => $payment_methods,
        'by_category'     => $by_category,
    ]
]);
