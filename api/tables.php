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

    case 'POST:create':
        requireManager();
        $body = json_decode(file_get_contents('php://input'), true);
        $table_number = $body['table_number'] ?? null;
        $capacity = $body['capacity'] ?? 4;
        
        if (!$table_number) {
            jsonResponse(['success' => false, 'message' => 'Table number is required.'], 400);
        }
        
        $stmt = $db->prepare("INSERT INTO restaurant_tables (table_number, capacity) VALUES (?, ?)");
        $stmt->execute([$table_number, $capacity]);
        jsonResponse(['success' => true, 'message' => 'Table created successfully.']);
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

    case 'PUT:update':
        requireManager();
        $body = json_decode(file_get_contents('php://input'), true);
        $id = $body['id'] ?? null;
        $table_number = $body['table_number'] ?? null;
        $capacity = $body['capacity'] ?? 4;
        
        if (!$id || !$table_number) {
            jsonResponse(['success' => false, 'message' => 'Table ID and number are required.'], 400);
        }
        $db->prepare("UPDATE restaurant_tables SET table_number = ?, capacity = ? WHERE id = ?")->execute([$table_number, $capacity, $id]);
        jsonResponse(['success' => true, 'message' => 'Table updated successfully.']);
        break;

    case 'DELETE:delete':
        requireManager();
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID required.'], 400);
        $check = $db->prepare("SELECT status FROM restaurant_tables WHERE id = ?");
        $check->execute([$id]);
        $table = $check->fetch();
        if ($table && $table['status'] !== 'available') {
            jsonResponse(['success' => false, 'message' => 'Cannot delete a table that is in use.'], 400);
        }
        $db->prepare("DELETE FROM restaurant_tables WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Table deleted.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
