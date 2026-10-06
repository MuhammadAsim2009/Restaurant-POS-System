<?php
// ============================================================
// Restaurant POS — Session Guard
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require the user to be logged in.
 * Redirects to login if not authenticated.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /Resturent_POS/index.php');
        exit;
    }
}

/**
 * Require the logged-in user to have the manager role.
 * Redirects to dashboard if insufficient role.
 */
function requireManager(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'manager') {
        header('Location: /Resturent_POS/dashboard.php?error=access_denied');
        exit;
    }
}

/**
 * Return currently logged-in user info.
 */
function currentUser(): array {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
    ];
}

/**
 * Check if current user is manager.
 */
function isManager(): bool {
    return ($_SESSION['user_role'] ?? '') === 'manager';
}

/**
 * Send a JSON response and exit.
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
