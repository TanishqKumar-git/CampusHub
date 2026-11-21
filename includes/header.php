<?php
// Shared top part of every page. Set $page_title (and optionally $no_sidebar = true) before including.
$page_title = $page_title ?? 'CampusHub';
$flash = get_flash();
$sidebar_communities = $pdo->query(
    'SELECT c.id, c.name, (SELECT COUNT(*) FROM community_members m WHERE m.community_id = c.id) AS members
     FROM communities c ORDER BY c.name'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | CampusHub</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="navbar">
    <a class="logo" href="<?= BASE_URL ?>/pages/home.php">Campus<span>Hub</span></a>

    <form class="search-form" action="<?= BASE_URL ?>/pages/search.php" method="get">
        <input type="text" name="q" placeholder="Search posts, communities, resources..." value="<?= e($_GET['q'] ?? '') ?>">
    </form>

    <button class="menu-toggle" type="button" aria-label="Menu">&#9776;</button>
    <nav class="nav-links">
        <a href="<?= BASE_URL ?>/pages/home.php">Home</a>
        <a href="<?= BASE_URL ?>/pages/resources.php">Resources</a>
        <a href="<?= BASE_URL ?>/pages/opportunities.php">Opportunities</a>
        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/pages/post_form.php">+ New Post</a>
            <?php if (is_admin()): ?>
                <a href="<?= BASE_URL ?>/admin/opportunities.php">Admin</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/pages/profile.php"><?= e($_SESSION['user_name']) ?></a>
            <form class="inline-form" action="<?= BASE_URL ?>/auth/logout.php" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="link-btn">Logout</button>
            </form>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
            <a class="btn btn-small" href="<?= BASE_URL ?>/auth/register.php">Sign up</a>
        <?php endif; ?>
    </nav>
</header>

<div class="layout<?= !empty($no_sidebar) ? ' no-sidebar' : '' ?>">
    <?php if (empty($no_sidebar)): ?>
    <aside class="sidebar">
        <div class="card">
            <h4>Communities</h4>
            <ul class="community-list">
                <?php foreach ($sidebar_communities as $sc): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/pages/community.php?id=<?= (int)$sc['id'] ?>"><?= e($sc['name']) ?></a>
                        <small><?= (int)$sc['members'] ?> members</small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card coming-soon">
            <h4>Find Teammates</h4>
            <p>Collaboration and project requests are coming soon.</p>
        </div>
    </aside>
    <?php endif; ?>

    <main class="content">
        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
