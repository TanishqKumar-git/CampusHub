<?php
require_once __DIR__ . '/../includes/init.php';

$id = (int)($_GET['id'] ?? 0);

// Handle new comment or comment delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'comment') {
        $body = trim($_POST['body'] ?? '');
        if ($body === '' || mb_strlen($body) > 1000) {
            set_flash('error', 'Comment must be between 1 and 1000 characters.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, body) VALUES (?, ?, ?)');
            $stmt->execute([$id, $_SESSION['user_id'], $body]);
            set_flash('success', 'Comment added.');
        }
    }

    if ($action === 'delete_comment') {
        $cid = (int)($_POST['comment_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT user_id FROM comments WHERE id = ? AND post_id = ?');
        $stmt->execute([$cid, $id]);
        $c = $stmt->fetch();
        if ($c && ((int)$c['user_id'] === (int)$_SESSION['user_id'] || is_admin())) {
            $pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$cid]);
            set_flash('success', 'Comment deleted.');
        }
    }

    redirect('post.php?id=' . $id);
}

$rows = get_posts($pdo, 'p.id = :pid', [':pid' => $id]);
if (!$rows) {
    http_response_code(404);
    die('Post not found.');
}
$p = $rows[0];

$stmt = $pdo->prepare('SELECT c.*, u.name AS author FROM comments c JOIN users u ON u.id = c.user_id
                       WHERE c.post_id = ? ORDER BY c.created_at ASC');
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

$can_edit = is_logged_in() && ((int)$p['user_id'] === (int)$_SESSION['user_id'] || is_admin());
$my = (int)($p['my_vote'] ?? 0);

$page_title = $p['title'];
require __DIR__ . '/../includes/header.php';
?>

<article class="card post-card post-full" data-post-id="<?= (int)$p['id'] ?>">
    <div class="vote-box">
        <button class="vote-btn up<?= $my === 1 ? ' active' : '' ?>" data-value="1" title="Upvote">&#9650;</button>
        <span class="score"><?= (int)$p['score'] ?></span>
        <button class="vote-btn down<?= $my === -1 ? ' active' : '' ?>" data-value="-1" title="Downvote">&#9660;</button>
    </div>
    <div class="post-content">
        <div class="post-meta">
            <a href="community.php?id=<?= (int)$p['community_id'] ?>"><?= e($p['community_name']) ?></a>
            &middot; by <a href="profile.php?id=<?= (int)$p['user_id'] ?>"><?= e($p['author']) ?></a>
            &middot; <?= e(time_ago($p['created_at'])) ?>
            <?php if ($p['updated_at']): ?>(edited)<?php endif; ?>
            <span class="tag tag-<?= strtolower(e($p['tag'])) ?>"><?= e($p['tag']) ?></span>
        </div>
        <h2><?= e($p['title']) ?></h2>
        <p><?= nl2br(e($p['body'])) ?></p>

        <?php if ($can_edit): ?>
            <div class="actions">
                <a class="btn btn-small btn-outline" href="post_form.php?id=<?= (int)$p['id'] ?>">Edit</a>
                <form class="inline-form" method="post" action="delete_post.php" onsubmit="return confirm('Delete this post?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</article>

<div class="card">
    <h3><?= count($comments) ?> Comments</h3>

    <?php if (is_logged_in()): ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="comment">
            <textarea name="body" rows="3" maxlength="1000" placeholder="Write a comment..." required></textarea>
            <button type="submit" class="btn">Comment</button>
        </form>
    <?php else: ?>
        <p class="muted"><a href="<?= BASE_URL ?>/auth/login.php">Log in</a> to comment.</p>
    <?php endif; ?>

    <?php foreach ($comments as $c): ?>
        <div class="comment">
            <div class="post-meta">
                <a href="profile.php?id=<?= (int)$c['user_id'] ?>"><?= e($c['author']) ?></a>
                &middot; <?= e(time_ago($c['created_at'])) ?>
            </div>
            <p><?= nl2br(e($c['body'])) ?></p>
            <?php if (is_logged_in() && ((int)$c['user_id'] === (int)$_SESSION['user_id'] || is_admin())): ?>
                <form class="inline-form" method="post" onsubmit="return confirm('Delete this comment?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_comment">
                    <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                    <button type="submit" class="link-btn small">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
