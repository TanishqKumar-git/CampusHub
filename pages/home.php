<?php
require_once __DIR__ . '/../includes/init.php';

// Sort tabs: New or Top
$sort = (($_GET['sort'] ?? 'new') === 'top') ? 'top' : 'new';
$posts = get_posts($pdo, '1=1', [], $sort);

$page_title = 'Home';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <h2>All Colleges Feed</h2>
    <div class="tabs">
        <a class="<?= $sort === 'new' ? 'active' : '' ?>" href="?sort=new">New</a>
        <a class="<?= $sort === 'top' ? 'active' : '' ?>" href="?sort=top">Top</a>
    </div>
</div>

<?php if (!$posts): ?>
    <div class="card"><p>No posts yet. Be the first to post!</p></div>
<?php endif; ?>

<?php foreach ($posts as $p) render_post_card($p); ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
