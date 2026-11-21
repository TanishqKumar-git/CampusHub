<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$user = current_user($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name       = trim($_POST['name'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $year       = (int)($_POST['year'] ?? 0);
    $college_id = (int)($_POST['college_id'] ?? 0);

    if ($name === '' || mb_strlen($name) > 80)   $errors[] = 'Name is required.';
    if (mb_strlen($bio) > 300)                   $errors[] = 'Bio can be at most 300 characters.';
    if ($course === '' || mb_strlen($course) > 80) $errors[] = 'Course is required.';
    if ($year < 1 || $year > 6)                  $errors[] = 'Year must be between 1 and 6.';

    $stmt = $pdo->prepare('SELECT id FROM colleges WHERE id = ?');
    $stmt->execute([$college_id]);
    if (!$stmt->fetch()) $errors[] = 'Choose a college.';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE users SET name = ?, bio = ?, course = ?, year = ?, college_id = ? WHERE id = ?');
        $stmt->execute([$name, $bio, $course, $year, $college_id, $_SESSION['user_id']]);
        $_SESSION['user_name'] = $name;
        set_flash('success', 'Profile updated.');
        redirect('profile.php');
    }
    $user = array_merge($user, compact('name', 'bio', 'course', 'year', 'college_id'));
}

$colleges = get_colleges($pdo);
$page_title = 'Edit profile';
require __DIR__ . '/../includes/header.php';
?>

<div class="card form-card">
    <h2>Edit profile</h2>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>
        <label>Name</label>
        <input type="text" name="name" value="<?= e($user['name']) ?>" required>

        <label>Bio (max 300 characters)</label>
        <textarea name="bio" rows="3" maxlength="300"><?= e($user['bio']) ?></textarea>

        <label>College</label>
        <select name="college_id">
            <?php foreach ($colleges as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$user['college_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Course</label>
        <input type="text" name="course" value="<?= e($user['course']) ?>" required>

        <label>Year</label>
        <input type="number" name="year" min="1" max="6" value="<?= (int)$user['year'] ?>" required>

        <button type="submit" class="btn">Save</button>
        <a class="btn btn-outline" href="profile.php">Cancel</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
