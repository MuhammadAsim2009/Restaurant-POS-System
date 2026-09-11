<?php
// ============================================================
// API: Orders — Create, List, Update Status
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireLogin();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ("$method:$action") {

    // GET — List orders with optional filters
    case 'GET:list':
        $status = $_GET['status'] ?? null;
        $date   = $_GET['date']   ?? date('Y-m-d');
        $limit  = (int)($_GET['limit'] ?? 50);

        $sql    = "SELECT o.*, u.name AS cashier_name, t.table_number
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id
                   LEFT JOIN restaurant_tables t ON o.table_id = t.id
                   WHERE DATE(o.created_at) = ?";
        $params = [$date];

        if ($status) { $sql .= " AND o.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY o.created_at DESC LIMIT $limit";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        // Fetch items for each order
        foreach ($orders as &$order) {
            $iStmt = $db->prepare("SELECT oi.*, mi.name AS item_name FROM order_items oi LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?");
            $iStmt->execute([$order['id']]);
            $order['items'] = $iStmt->fetchAll();
        }
        jsonResponse(['success' => true, 'data' => $orders]);
        break;

    // GET — Single order detail
    case 'GET:detail':
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['success' => false, 'message' => 'Order ID required.'], 400);

        $stmt = $db->prepare("SELECT o.*, u.name AS cashier_name, t.table_number
                              FROM orders o
                              LEFT JOIN users u ON o.user_id = u.id
                              LEFT JOIN restaurant_tables t ON o.table_id = t.id
                              WHERE o.id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) jsonResponse(['success' => false, 'message' => 'Order not found.'], 404);

        $iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $iStmt->execute([$id]);
        $order['items'] = $iStmt->fetchAll();

        $pStmt = $db->prepare("SELECT * FROM payments WHERE order_id = ?");
        $pStmt->execute([$id]);
        $order['payment'] = $pStmt->fetch();

        jsonResponse(['success' => true, 'data' => $order]);
        break;

    // GET — Kitchen queue (pending + preparing)
    case 'GET:kitchen':
        $stmt = $db->query("SELECT o.*, t.table_number, u.name AS cashier_name
                            FROM orders o
                            LEFT JOIN restaurant_tables t ON o.table_id = t.id
                            LEFT JOIN users u ON o.user_id = u.id
                            WHERE o.status IN ('pending','preparing')
                            ORDER BY o.created_at ASC");
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $iStmt = $db->prepare("SELECT oi.quantity, oi.item_name FROM order_items oi WHERE oi.order_id = ?");
            $iStmt->execute([$order['id']]);
            $order['items'] = $iStmt->fetchAll();
        }
        jsonResponse(['success' => true, 'data' => $orders]);
        break;

    // POST — Create new order
    case 'POST:create':
        $body  = json_decode(file_get_contents('php://input'), true);
        $items = $body['items'] ?? [];
        if (empty($items)) jsonResponse(['success' => false, 'message' => 'No items in order.'], 400);

        $user  = currentUser();
        $orderNum = 'KFF-' . strtoupper(substr(uniqid(), -6));

        $subtotal = 0;
        foreach ($items as $item) $subtotal += $item['unit_price'] * $item['quantity'];

        $tax      = round($subtotal * 0.05, 2); // 5% GST
        $discount = (float)($body['discount'] ?? 0);
        $total    = $subtotal + $tax - $discount;

        $db->beginTransaction();
        try {
            // Insert order
            $stmt = $db->prepare("INSERT INTO orders (order_number, table_id, user_id, order_type, status, subtotal, tax, discount, total, notes) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $orderNum,
                $body['table_id'] ?? null,
                $user['id'],
                $body['order_type'] ?? 'dine-in',
                'pending',
                $subtotal, $tax, $discount, $total,
                $body['notes'] ?? null
            ]);
            $orderId = $db->lastInsertId();

            // Insert order items
            foreach ($items as $item) {
                $itemSubtotal = $item['unit_price'] * $item['quantity'];
                $iStmt = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, subtotal) VALUES (?,?,?,?,?,?)");
                $iStmt->execute([$orderId, $item['menu_item_id'], $item['item_name'], $item['quantity'], $item['unit_price'], $itemSubtotal]);
            }

            // Update table status if dine-in
            if (!empty($body['table_id'])) {
                $db->prepare("UPDATE restaurant_tables SET status='occupied', current_order_id=? WHERE id=?")->execute([$orderId, $body['table_id']]);
            }

            // Record payment if provided
            if (!empty($body['payment_method'])) {
                $amountPaid = (float)($body['amount_paid'] ?? $total);
                $change     = max(0, $amountPaid - $total);
                $pStmt = $db->prepare("INSERT INTO payments (order_id, method, amount_paid, change_given) VALUES (?,?,?,?)");
                $pStmt->execute([$orderId, $body['payment_method'], $amountPaid, $change]);
                $db->prepare("UPDATE orders SET payment_method=? WHERE id=?")->execute([$body['payment_method'], $orderId]);
            }

            $db->commit();
            jsonResponse([
                'success' => true,
                'message' => 'Order placed successfully.',
                'order_id' => $orderId,
                'order_number' => $orderNum,
                'total' => $total,
                'change' => isset($change) ? $change : 0,
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
        break;

    // PUT — Update order status
    case 'PUT:status':
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = $body['id'] ?? null;
        $status = $body['status'] ?? null;
        $allowed = ['pending','preparing','ready','delivered','cancelled'];

        if (!$id || !in_array($status, $allowed)) {
            jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
        }

        $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $id]);

        // If delivered/cancelled, free the table
        if (in_array($status, ['delivered', 'cancelled'])) {
            $order = $db->prepare("SELECT table_id FROM orders WHERE id = ?");
            $order->execute([$id]);
            $ord = $order->fetch();
            if ($ord && $ord['table_id']) {
                $db->prepare("UPDATE restaurant_tables SET status='available', current_order_id=NULL WHERE id=?")->execute([$ord['table_id']]);
            }
        }

        jsonResponse(['success' => true, 'message' => 'Order status updated.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
