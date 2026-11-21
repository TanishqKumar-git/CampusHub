<?php
require_once __DIR__ . '/../includes/init.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM communities WHERE id = ?');
$stmt->execute([$id]);
$community = $stmt->fetch();
if (!$community) {
    http_response_code(404);
    die('Community not found.');
}

// Member count and whether the current user has joined
$stmt = $pdo->prepare('SELECT COUNT(*) FROM community_members WHERE community_id = ?');
$stmt->execute([$id]);
$members = (int)$stmt->fetchColumn();

$joined = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare('SELECT 1 FROM community_members WHERE community_id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    $joined = (bool)$stmt->fetch();
}

$sort  = (($_GET['sort'] ?? 'new') === 'top') ? 'top' : 'new';
$posts = get_posts($pdo, 'p.community_id = :cid', [':cid' => $id], $sort);

$page_title = $community['name'];
require __DIR__ . '/../includes/header.php';
?>

<div class="card community-head">
    <div>
        <h2><?= e($community['name']) ?></h2>
        <p><?= e($community['description']) ?></p>
        <p class="muted"><span id="member-count"><?= $members ?></span> members</p>
    </div>
    <div>
        <?php if (is_logged_in()): ?>
            <button class="btn join-btn <?= $joined ? 'btn-outline' : '' ?>" data-community-id="<?= $id ?>">
                <?= $joined ? 'Leave' : 'Join' ?>
            </button>
            <a class="btn" href="post_form.php?community_id=<?= $id ?>">+ Post here</a>
        <?php else: ?>
            <a class="btn" href="<?= BASE_URL ?>/auth/login.php">Log in to join</a>
        <?php endif; ?>
    </div>
</div>

<div class="page-head">
    <h3>Posts</h3>
    <div class="tabs">
        <a class="<?= $sort === 'new' ? 'active' : '' ?>" href="?id=<?= $id ?>&sort=new">New</a>
        <a class="<?= $sort === 'top' ? 'active' : '' ?>" href="?id=<?= $id ?>&sort=top">Top</a>
    </div>
</div>

<?php if (!$posts): ?>
    <div class="card"><p>No posts in this community yet.</p></div>
<?php endif; ?>

<?php foreach ($posts as $p) render_post_card($p); ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
