<?php
require_once __DIR__ . '/../includes/init.php';

$q = trim($_GET['q'] ?? '');
$posts = $communities = $resources = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    // Posts (title or body matches)
    $posts = get_posts($pdo, '(p.title LIKE :q1 OR p.body LIKE :q2)', [':q1' => $like, ':q2' => $like]);

    // Communities
    $stmt = $pdo->prepare('SELECT id, name, description FROM communities WHERE name LIKE ? OR description LIKE ? LIMIT 20');
    $stmt->execute([$like, $like]);
    $communities = $stmt->fetchAll();

    // Resources
    $stmt = $pdo->prepare('SELECT id, title, subject, type, semester FROM resources WHERE title LIKE ? OR subject LIKE ? ORDER BY created_at DESC LIMIT 20');
    $stmt->execute([$like, $like]);
    $resources = $stmt->fetchAll();
}

$page_title = 'Search';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head"><h2>Search results<?= $q !== '' ? ' for "' . e($q) . '"' : '' ?></h2></div>

<?php if ($q === ''): ?>
    <div class="card"><p>Type something in the search box above.</p></div>
<?php else: ?>

    <h3 class="section-title">Communities (<?= count($communities) ?>)</h3>
    <?php foreach ($communities as $c): ?>
        <div class="card">
            <a href="community.php?id=<?= (int)$c['id'] ?>"><strong><?= e($c['name']) ?></strong></a>
            <p class="muted"><?= e($c['description']) ?></p>
        </div>
    <?php endforeach; ?>

    <h3 class="section-title">Resources (<?= count($resources) ?>)</h3>
    <?php foreach ($resources as $r): ?>
        <div class="card">
            <strong><?= e($r['title']) ?></strong>
            <span class="tag tag-<?= strtolower(e($r['type'])) ?>"><?= e($r['type']) ?></span>
            <p class="muted"><?= e($r['subject']) ?> &middot; Sem <?= (int)$r['semester'] ?>
               &middot; <a href="download.php?id=<?= (int)$r['id'] ?>">Download</a></p>
        </div>
    <?php endforeach; ?>

    <h3 class="section-title">Posts (<?= count($posts) ?>)</h3>
    <?php foreach ($posts as $p) render_post_card($p); ?>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
