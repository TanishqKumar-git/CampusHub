<?php
// Login / role helpers.

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

// Use at the top of pages that need a logged in user
function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in first.');
        redirect(BASE_URL . '/auth/login.php');
    }
}

// Use at the top of admin pages
function require_admin() {
    if (!is_admin()) {
        http_response_code(403);
        die('Access denied. Admins only.');
    }
}

// Get the full row of the logged in user (or null)
function current_user($pdo) {
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}
