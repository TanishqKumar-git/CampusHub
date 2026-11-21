<?php
// Create a new post, or edit one if ?id= is given.
require_once __DIR__ . '/../includes/init.php';
require_login();

$tags = ['Academics', 'Doubt', 'Project', 'General'];
$edit_id = (int)($_GET['id'] ?? 0);
$post = ['title' => '', 'body' => '', 'tag' => 'General', 'community_id' => (int)($_GET['community_id'] ?? 0)];

// Editing: load the post and make sure it belongs to the user (or admin)
if ($edit_id) {
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$edit_id]);
    $post = $stmt->fetch();
    if (!$post) die('Post not found.');
    if ((int)$post['user_id'] !== (int)$_SESSION['user_id'] && !is_admin()) {
        http_response_code(403);
        die('You can only edit your own posts.');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title        = trim($_POST['title'] ?? '');
    $body         = trim($_POST['body'] ?? '');
    $tag          = $_POST['tag'] ?? 'General';
    $community_id = (int)($_POST['community_id'] ?? 0);

    if ($title === '' || mb_strlen($title) > 200) $errors[] = 'Title is required (max 200 characters).';
    if ($body === '')                              $errors[] = 'Write something in the post body.';
    if (!in_array($tag, $tags, true))              $errors[] = 'Choose a valid tag.';

    $stmt = $pdo->prepare('SELECT id FROM communities WHERE id = ?');
    $stmt->execute([$community_id]);
    if (!$stmt->fetch()) $errors[] = 'Choose a community.';

    if (!$errors) {
        if ($edit_id) {
            $stmt = $pdo->prepare('UPDATE posts SET title = ?, body = ?, tag = ?, community_id = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$title, $body, $tag, $community_id, $edit_id]);
            set_flash('success', 'Post updated.');
            redirect('post.php?id=' . $edit_id);
        } else {
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, community_id, title, body, tag) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $community_id, $title, $body, $tag]);
            set_flash('success', 'Post published.');
            redirect('post.php?id=' . $pdo->lastInsertId());
        }
    }
    // Keep what the user typed if there were errors
    $post = ['title' => $title, 'body' => $body, 'tag' => $tag, 'community_id' => $community_id];
}

$communities = get_communities($pdo);
$page_title = $edit_id ? 'Edit post' : 'New post';
require __DIR__ . '/../includes/header.php';
?>

<div class="card form-card wide">
    <h2><?= $edit_id ? 'Edit post' : 'Create a post' ?></h2>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>Community</label>
        <select name="community_id" required>
            <option value="">-- choose community --</option>
            <?php foreach ($communities as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$post['community_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Tag</label>
        <select name="tag">
            <?php foreach ($tags as $t): ?>
                <option <?= $post['tag'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Title</label>
        <input type="text" name="title" maxlength="200" value="<?= e($post['title']) ?>" required>

        <label>Body</label>
        <textarea name="body" rows="8" required><?= e($post['body']) ?></textarea>

        <button type="submit" class="btn"><?= $edit_id ? 'Save changes' : 'Publish' ?></button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
