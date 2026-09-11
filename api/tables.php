<?php
// ============================================================
// API: Tables — List & Update Status
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireLogin();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ("$method:$action") {

    case 'GET:list':
        $stmt = $db->query("SELECT t.*, o.order_number, o.total, o.status AS order_status
                            FROM restaurant_tables t
                            LEFT JOIN orders o ON t.current_order_id = o.id
                            ORDER BY t.table_number");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'PUT:status':
        $body   = json_decode(file_get_contents('php://input'), true);
        $id     = $body['id'] ?? null;
        $status = $body['status'] ?? null;
        $allowed = ['available', 'occupied', 'reserved'];
        if (!$id || !in_array($status, $allowed)) {
            jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
        }
        $db->prepare("UPDATE restaurant_tables SET status = ? WHERE id = ?")->execute([$status, $id]);
        jsonResponse(['success' => true, 'message' => 'Table status updated.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
