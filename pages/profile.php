<?php
require_once __DIR__ . '/../includes/init.php';

// /profile.php shows your own profile; /profile.php?id=3 shows someone else's
$id = (int)($_GET['id'] ?? ($_SESSION['user_id'] ?? 0));
if (!$id) {
    require_login();
}

$stmt = $pdo->prepare('SELECT u.*, c.name AS college_name FROM users u JOIN colleges c ON c.id = u.college_id WHERE u.id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    http_response_code(404);
    die('User not found.');
}

$is_me = is_logged_in() && (int)$_SESSION['user_id'] === (int)$user['id'];

// Communities joined
$stmt = $pdo->prepare('SELECT c.id, c.name FROM communities c JOIN community_members m ON m.community_id = c.id WHERE m.user_id = ? ORDER BY c.name');
$stmt->execute([$id]);
$joined = $stmt->fetchAll();

// Their posts and uploads
$posts = get_posts($pdo, 'p.user_id = :author', [':author' => $id]);

$stmt = $pdo->prepare('SELECT * FROM resources WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$id]);
$uploads = $stmt->fetchAll();

$page_title = $user['name'];
require __DIR__ . '/../includes/header.php';
?>

<div class="card profile-head">
    <div class="avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
    <div>
        <h2><?= e($user['name']) ?> <?php if ($user['role'] === 'admin'): ?><span class="tag tag-opp">Admin</span><?php endif; ?></h2>
        <p class="muted"><?= e($user['college_name']) ?> &middot; <?= e($user['course']) ?> &middot; Year <?= (int)$user['year'] ?></p>
        <p><?= e($user['bio'] ?: 'No bio yet.') ?></p>
        <p class="muted">Joined <?= e(date('d M Y', strtotime($user['created_at']))) ?></p>
        <?php if ($is_me): ?>
            <a class="btn btn-small btn-outline" href="edit_profile.php">Edit profile</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Communities joined</h3>
    <?php if (!$joined): ?>
        <p class="muted">Not in any community yet.</p>
    <?php else: ?>
        <p>
        <?php foreach ($joined as $j): ?>
            <a class="chip" href="community.php?id=<?= (int)$j['id'] ?>"><?= e($j['name']) ?></a>
        <?php endforeach; ?>
        </p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Uploads (<?= count($uploads) ?>)</h3>
    <?php if (!$uploads): ?>
        <p class="muted">No uploads yet.</p>
    <?php endif; ?>
    <?php foreach ($uploads as $r): ?>
        <p>&#128196; <?= e($r['title']) ?> <span class="muted">(<?= e($r['type']) ?>, <?= (int)$r['downloads'] ?> downloads)</span>
           &middot; <a href="download.php?id=<?= (int)$r['id'] ?>">Download</a></p>
    <?php endforeach; ?>
</div>

<h3 class="section-title">Posts (<?= count($posts) ?>)</h3>
<?php foreach ($posts as $p) render_post_card($p); ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
