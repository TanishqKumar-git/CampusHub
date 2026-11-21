<?php
// AJAX endpoint: join or leave a community. Returns JSON.
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST')  json_out(['error' => 'POST only'], 405);
if (!is_logged_in())                        json_out(['error' => 'Please log in first.'], 401);
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    json_out(['error' => 'Invalid token.'], 403);
}

$community_id = (int)($_POST['community_id'] ?? 0);
$user_id      = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id FROM communities WHERE id = ?');
$stmt->execute([$community_id]);
if (!$stmt->fetch()) json_out(['error' => 'Community not found.'], 404);

$stmt = $pdo->prepare('SELECT 1 FROM community_members WHERE user_id = ? AND community_id = ?');
$stmt->execute([$user_id, $community_id]);

if ($stmt->fetch()) {
    $pdo->prepare('DELETE FROM community_members WHERE user_id = ? AND community_id = ?')->execute([$user_id, $community_id]);
    $joined = false;
} else {
    $pdo->prepare('INSERT INTO community_members (user_id, community_id) VALUES (?, ?)')->execute([$user_id, $community_id]);
    $joined = true;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM community_members WHERE community_id = ?');
$stmt->execute([$community_id]);

json_out(['joined' => $joined, 'members' => (int)$stmt->fetchColumn()]);
