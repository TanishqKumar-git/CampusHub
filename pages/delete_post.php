<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('home.php');
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT user_id FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();

// Only the author or an admin can delete
if ($post && ((int)$post['user_id'] === (int)$_SESSION['user_id'] || is_admin())) {
    $pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
    set_flash('success', 'Post deleted.');
} else {
    set_flash('error', 'You cannot delete this post.');
}

redirect('home.php');
