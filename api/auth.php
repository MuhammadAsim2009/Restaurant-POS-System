<?php
// ============================================================
// API: Authentication — Login / Logout / Status
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

switch ($action) {
    case 'login':
        $body = json_decode(file_get_contents('php://input'), true);
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        if (!$username || !$password) {
            jsonResponse(['success' => false, 'message' => 'Username and password are required.'], 400);
        }

        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid username or password.'], 401);
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        jsonResponse([
            'success'  => true,
            'message'  => 'Login successful.',
            'redirect' => $user['role'] === 'manager' ? '/Resturent_POS/dashboard.php' : '/Resturent_POS/pos.php',
            'user'     => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']],
        ]);
        break;

    case 'logout':
        session_destroy();
        jsonResponse(['success' => true, 'message' => 'Logged out.']);
        break;

    case 'status':
        if (!empty($_SESSION['user_id'])) {
            jsonResponse(['success' => true, 'authenticated' => true, 'user' => currentUser()]);
        } else {
            jsonResponse(['success' => true, 'authenticated' => false]);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
