<?php
// Admin only: add opportunities, approve pending suggestions, delete.
require_once __DIR__ . '/../includes/init.php';
require_admin();

$categories = ['Internship', 'Hackathon', 'Scholarship', 'Event', 'Placement'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title    = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? '';
        $org      = trim($_POST['organization'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $deadline = $_POST['deadline'] ?? '';
        $link     = trim($_POST['link'] ?? '');

        if ($title === '')                              $errors[] = 'Title is required.';
        if (!in_array($category, $categories, true))    $errors[] = 'Choose a category.';
        if ($org === '')                                $errors[] = 'Organization is required.';
        if ($desc === '' || mb_strlen($desc) > 500)     $errors[] = 'Description is required (max 500 characters).';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) $errors[] = 'Choose a valid deadline.';
        if (!preg_match('#^https?://#i', $link))        $errors[] = 'Link must start with http:// or https://';

        if (!$errors) {
            $stmt = $pdo->prepare("INSERT INTO opportunities (title, category, organization, description, deadline, link, status, created_by)
                                   VALUES (?, ?, ?, ?, ?, ?, 'approved', ?)");
            $stmt->execute([$title, $category, $org, $desc, $deadline, $link, $_SESSION['user_id']]);
            set_flash('success', 'Opportunity added.');
            redirect('opportunities.php');
        }
    }

    if ($action === 'approve') {
        $pdo->prepare("UPDATE opportunities SET status = 'approved' WHERE id = ?")->execute([(int)$_POST['id']]);
        set_flash('success', 'Approved.');
        redirect('opportunities.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM opportunities WHERE id = ?')->execute([(int)$_POST['id']]);
        set_flash('success', 'Deleted.');
        redirect('opportunities.php');
    }
}

$pending  = $pdo->query("SELECT * FROM opportunities WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
$approved = $pdo->query("SELECT * FROM opportunities WHERE status = 'approved' ORDER BY deadline DESC")->fetchAll();

$stats = [
    'Users'         => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'Posts'         => $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'Resources'     => $pdo->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
    'Opportunities' => $pdo->query("SELECT COUNT(*) FROM opportunities WHERE status = 'approved'")->fetchColumn(),
];

$page_title = 'Admin';
$no_sidebar = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head"><h2>Admin panel</h2></div>

<div class="stats">
    <?php foreach ($stats as $label => $count): ?>
        <div class="card stat"><strong><?= (int)$count ?></strong><span><?= e($label) ?></span></div>
    <?php endforeach; ?>
</div>

<div class="card">
    <h3>Pending suggestions (<?= count($pending) ?>)</h3>
    <?php if (!$pending): ?><p class="muted">Nothing waiting for approval.</p><?php endif; ?>
    <?php foreach ($pending as $o): ?>
        <div class="admin-row">
            <div>
                <strong><?= e($o['title']) ?></strong> <span class="tag tag-opp"><?= e($o['category']) ?></span><br>
                <span class="muted"><?= e($o['organization']) ?> &middot; <?= e($o['deadline']) ?> &middot; <a href="<?= e($o['link']) ?>" target="_blank" rel="noopener noreferrer">link</a></span>
                <p><?= e($o['description']) ?></p>
            </div>
            <div class="actions">
                <form method="post" class="inline-form">
                    <?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                    <button class="btn btn-small">Approve</button>
                </form>
                <form method="post" class="inline-form" onsubmit="return confirm('Delete this suggestion?');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                    <button class="btn btn-small btn-danger">Reject</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card form-card wide">
    <h3>Add an opportunity</h3>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">

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

        <label>Description</label>
        <textarea name="description" rows="3" maxlength="500" required></textarea>

        <label>Link</label>
        <input type="url" name="link" required>

        <button class="btn" type="submit">Add</button>
    </form>
</div>

<div class="card">
    <h3>All live opportunities (<?= count($approved) ?>)</h3>
    <?php foreach ($approved as $o): ?>
        <div class="admin-row">
            <div><strong><?= e($o['title']) ?></strong> <span class="muted">&middot; <?= e($o['category']) ?> &middot; <?= e($o['deadline']) ?></span></div>
            <form method="post" class="inline-form" onsubmit="return confirm('Delete this opportunity?');">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <button class="btn btn-small btn-danger">Delete</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
