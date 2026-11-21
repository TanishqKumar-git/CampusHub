<?php
// AJAX endpoint: upvote / downvote a post. Returns JSON.
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST')  json_out(['error' => 'POST only'], 405);
if (!is_logged_in())                        json_out(['error' => 'Please log in to vote.'], 401);

// CSRF check (JSON version)
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    json_out(['error' => 'Invalid token.'], 403);
}

$post_id = (int)($_POST['post_id'] ?? 0);
$value   = (int)($_POST['value'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if (!in_array($value, [1, -1], true)) json_out(['error' => 'Bad vote value.'], 400);

$stmt = $pdo->prepare('SELECT id FROM posts WHERE id = ?');
$stmt->execute([$post_id]);
if (!$stmt->fetch()) json_out(['error' => 'Post not found.'], 404);

// What did this user vote before?
$stmt = $pdo->prepare('SELECT value FROM votes WHERE user_id = ? AND post_id = ?');
$stmt->execute([$user_id, $post_id]);
$existing = $stmt->fetchColumn();

if ($existing !== false && (int)$existing === $value) {
    // Clicking the same arrow again removes the vote (toggle)
    $pdo->prepare('DELETE FROM votes WHERE user_id = ? AND post_id = ?')->execute([$user_id, $post_id]);
    $my_vote = 0;
} elseif ($existing !== false) {
    // Changing from up to down (or down to up)
    $pdo->prepare('UPDATE votes SET value = ? WHERE user_id = ? AND post_id = ?')->execute([$value, $user_id, $post_id]);
    $my_vote = $value;
} else {
    $pdo->prepare('INSERT INTO votes (user_id, post_id, value) VALUES (?, ?, ?)')->execute([$user_id, $post_id, $value]);
    $my_vote = $value;
}

$stmt = $pdo->prepare('SELECT COALESCE(SUM(value), 0) FROM votes WHERE post_id = ?');
$stmt->execute([$post_id]);

json_out(['score' => (int)$stmt->fetchColumn(), 'my_vote' => $my_vote]);
