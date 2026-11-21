<?php
// Small helper functions used across the site.

// Escape text before printing it in HTML (stops XSS)
function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// ---------- CSRF protection ----------

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Put this inside every <form method="post">
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Call this at the top of every POST handler
function verify_csrf() {
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(403);
        die('Invalid request (CSRF check failed). Go back and try again.');
    }
}

// ---------- Flash messages (shown once after a redirect) ----------

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ---------- Time ----------

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . ' min ago';
    if ($diff < 86400)   return floor($diff / 3600) . ' hr ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' day(s) ago';
    return date('d M Y', strtotime($datetime));
}

// ---------- Posts ----------

// Fetch posts with author, community, score, comment count and the current user's vote.
// $where is an extra SQL condition (use named params), $sort is 'new' or 'top'.
function get_posts($pdo, $where = '1=1', $params = [], $sort = 'new', $limit = 50) {
    $order = ($sort === 'top') ? 'score DESC, p.created_at DESC' : 'p.created_at DESC';
    $limit = (int)$limit;

    $sql = "SELECT p.*, u.name AS author, c.name AS community_name,
                   (SELECT COALESCE(SUM(v.value), 0) FROM votes v WHERE v.post_id = p.id) AS score,
                   (SELECT COUNT(*) FROM comments cm WHERE cm.post_id = p.id) AS comment_count,
                   (SELECT v2.value FROM votes v2 WHERE v2.post_id = p.id AND v2.user_id = :uid) AS my_vote
            FROM posts p
            JOIN users u ON u.id = p.user_id
            JOIN communities c ON c.id = p.community_id
            WHERE $where
            ORDER BY $order
            LIMIT $limit";

    $params[':uid'] = $_SESSION['user_id'] ?? 0;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Print one post as a card (used on home, community, profile, search pages)
function render_post_card($p) {
    $my   = (int)($p['my_vote'] ?? 0);
    $up   = $my === 1  ? ' active' : '';
    $down = $my === -1 ? ' active' : '';
    $excerpt = mb_strlen($p['body']) > 180 ? mb_substr($p['body'], 0, 180) . '...' : $p['body'];
    ?>
    <article class="card post-card" data-post-id="<?= (int)$p['id'] ?>">
        <div class="vote-box">
            <button class="vote-btn up<?= $up ?>" data-value="1" title="Upvote">&#9650;</button>
            <span class="score"><?= (int)$p['score'] ?></span>
            <button class="vote-btn down<?= $down ?>" data-value="-1" title="Downvote">&#9660;</button>
        </div>
        <div class="post-content">
            <div class="post-meta">
                <a href="<?= BASE_URL ?>/pages/community.php?id=<?= (int)$p['community_id'] ?>"><?= e($p['community_name']) ?></a>
                &middot; by <a href="<?= BASE_URL ?>/pages/profile.php?id=<?= (int)$p['user_id'] ?>"><?= e($p['author']) ?></a>
                &middot; <?= e(time_ago($p['created_at'])) ?>
                <span class="tag tag-<?= strtolower(e($p['tag'])) ?>"><?= e($p['tag']) ?></span>
            </div>
            <h3><a href="<?= BASE_URL ?>/pages/post.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></h3>
            <p><?= nl2br(e($excerpt)) ?></p>
            <a class="muted" href="<?= BASE_URL ?>/pages/post.php?id=<?= (int)$p['id'] ?>">&#128172; <?= (int)$p['comment_count'] ?> comments</a>
        </div>
    </article>
    <?php
}

// ---------- Misc lookups ----------

function get_colleges($pdo) {
    return $pdo->query('SELECT id, name FROM colleges ORDER BY name')->fetchAll();
}

function get_communities($pdo) {
    return $pdo->query('SELECT id, name FROM communities ORDER BY name')->fetchAll();
}
