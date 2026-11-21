<?php
require_once __DIR__ . '/../includes/init.php';

$categories = ['Internship', 'Hackathon', 'Scholarship', 'Event', 'Placement'];
$errors = [];

// Logged in users can suggest an opportunity; it stays "pending" until an admin approves it
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();

    $title    = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $org      = trim($_POST['organization'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    $link     = trim($_POST['link'] ?? '');

    if ($title === '' || mb_strlen($title) > 200)   $errors[] = 'Title is required.';
    if (!in_array($category, $categories, true))    $errors[] = 'Choose a category.';
    if ($org === '' || mb_strlen($org) > 120)       $errors[] = 'Organization is required.';
    if ($desc === '' || mb_strlen($desc) > 500)     $errors[] = 'Description is required (max 500 characters).';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) $errors[] = 'Choose a valid deadline.';
    if (!preg_match('#^https?://#i', $link) || !filter_var($link, FILTER_VALIDATE_URL)) $errors[] = 'Link must start with http:// or https://';

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO opportunities (title, category, organization, description, deadline, link, status, created_by)
                               VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$title, $category, $org, $desc, $deadline, $link, $_SESSION['user_id']]);
        set_flash('success', 'Thanks! Your suggestion will appear after admin approval.');
        redirect('opportunities.php');
    }
}

$f_cat = $_GET['category'] ?? '';
$where = "status = 'approved'";
$params = [];
if (in_array($f_cat, $categories, true)) {
    $where .= ' AND category = :cat';
    $params[':cat'] = $f_cat;
}

// Upcoming first, expired ones at the bottom
$stmt = $pdo->prepare("SELECT * FROM opportunities WHERE $where
                       ORDER BY (deadline < CURDATE()) ASC, deadline ASC");
$stmt->execute($params);
$items = $stmt->fetchAll();

$page_title = 'Opportunities';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <h2>Opportunities</h2>
    <div class="tabs">
        <a class="<?= $f_cat === '' ? 'active' : '' ?>" href="opportunities.php">All</a>
        <?php foreach ($categories as $c): ?>
            <a class="<?= $f_cat === $c ? 'active' : '' ?>" href="?category=<?= e($c) ?>"><?= e($c) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$items): ?>
    <div class="card"><p>No opportunities in this category yet.</p></div>
<?php endif; ?>

<?php foreach ($items as $o):
    $expired = strtotime($o['deadline']) < strtotime(date('Y-m-d'));
?>
    <div class="card opp-card <?= $expired ? 'expired' : '' ?>">
        <div>
            <h3><?= e($o['title']) ?>
                <span class="tag tag-opp"><?= e($o['category']) ?></span>
                <?php if ($expired): ?><span class="tag tag-expired">Expired</span><?php endif; ?>
            </h3>
            <p class="muted"><?= e($o['organization']) ?> &middot; Deadline: <?= e(date('d M Y', strtotime($o['deadline']))) ?></p>
            <p><?= e($o['description']) ?></p>
        </div>
        <a class="btn" href="<?= e($o['link']) ?>" target="_blank" rel="noopener noreferrer">Apply / View</a>
    </div>
<?php endforeach; ?>

<?php if (is_logged_in()): ?>
<div class="card form-card wide">
    <h3>Suggest an opportunity</h3>
    <p class="muted">Admins review every suggestion before it goes live.</p>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>
        <label>Title</label>
        <input type="text" name="title" maxlength="200" required>

        <div class="row">
            <div>
                <label>Category</label>
                <select name="category">
                    <?php foreach ($categories as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Deadline</label>
                <input type="date" name="deadline" required>
            </div>
        </div>

        <label>Organization</label>
        <input type="text" name="organization" maxlength="120" required>

        <label>Short description</label>
        <textarea name="description" rows="3" maxlength="500" required></textarea>

        <label>Link (https://...)</label>
        <input type="url" name="link" required>

        <button type="submit" class="btn">Submit for approval</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
