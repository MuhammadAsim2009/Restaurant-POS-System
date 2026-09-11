<?php
// ============================================================
// API: Menu — Categories & Items CRUD
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireLogin();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ("$method:$action") {

    // GET all categories with items
    case 'GET:categories':
        $cats = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll();
        jsonResponse(['success' => true, 'data' => $cats]);
        break;

    // GET all items (optionally filter by category)
    case 'GET:list':
        $catId = $_GET['category_id'] ?? null;
        if ($catId) {
            $stmt = $db->prepare("SELECT mi.*, c.name AS category_name FROM menu_items mi JOIN categories c ON mi.category_id = c.id WHERE mi.category_id = ? ORDER BY mi.name");
            $stmt->execute([$catId]);
        } else {
            $stmt = $db->query("SELECT mi.*, c.name AS category_name FROM menu_items mi JOIN categories c ON mi.category_id = c.id ORDER BY c.sort_order, mi.name");
        }
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // GET all items with categories (for POS)
    case 'GET:pos':
        $cats = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll();
        $items = $db->query("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category_id, name")->fetchAll();

        // Group items by category
        $grouped = [];
        $itemMap = [];
        foreach ($items as $item) $itemMap[$item['category_id']][] = $item;
        foreach ($cats as $cat) {
            $cat['items'] = $itemMap[$cat['id']] ?? [];
            $grouped[] = $cat;
        }
        jsonResponse(['success' => true, 'data' => $grouped]);
        break;

    // POST — Create new item (manager only)
    case 'POST:create':
        requireManager();
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare("INSERT INTO menu_items (category_id, name, description, price, is_available) VALUES (?,?,?,?,?)");
        $stmt->execute([
            $body['category_id'], $body['name'], $body['description'] ?? null,
            $body['price'], $body['is_available'] ?? 1
        ]);
        jsonResponse(['success' => true, 'message' => 'Item created.', 'id' => $db->lastInsertId()]);
        break;

    // PUT — Update item (manager only)
    case 'PUT:update':
        requireManager();
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare("UPDATE menu_items SET category_id=?, name=?, description=?, price=?, is_available=? WHERE id=?");
        $stmt->execute([
            $body['category_id'], $body['name'], $body['description'] ?? null,
            $body['price'], $body['is_available'] ?? 1, $body['id']
        ]);
        jsonResponse(['success' => true, 'message' => 'Item updated.']);
        break;

    // PUT — Toggle availability
    case 'PUT:toggle':
        requireManager();
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare("UPDATE menu_items SET is_available = NOT is_available WHERE id = ?");
        $stmt->execute([$body['id']]);
        jsonResponse(['success' => true, 'message' => 'Availability toggled.']);
        break;

    // DELETE — Remove item (manager only)
    case 'DELETE:delete':
        requireManager();
        $id = $_GET['id'] ?? null;
        if (!$id) jsonResponse(['success' => false, 'message' => 'ID required.'], 400);
        $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Item deleted.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
