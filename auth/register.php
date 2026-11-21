<?php
require_once __DIR__ . '/../includes/init.php';

if (is_logged_in()) redirect(BASE_URL . '/pages/home.php');

$errors = [];
$old = ['name' => '', 'email' => '', 'college_id' => '', 'course' => '', 'year' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $college_id = (int)($_POST['college_id'] ?? 0);
    $course     = trim($_POST['course'] ?? '');
    $year       = (int)($_POST['year'] ?? 0);
    $old = compact('name', 'email', 'college_id', 'course', 'year');

    // Validation
    if ($name === '' || mb_strlen($name) > 80)           $errors[] = 'Enter your name (max 80 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 6)                           $errors[] = 'Password must be at least 6 characters.';
    if ($course === '' || mb_strlen($course) > 80)       $errors[] = 'Enter your course.';
    if ($year < 1 || $year > 6)                          $errors[] = 'Year must be between 1 and 6.';

    $check = $pdo->prepare('SELECT id FROM colleges WHERE id = ?');
    $check->execute([$college_id]);
    if (!$check->fetch()) $errors[] = 'Please choose your college.';

    // Email must be new
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'This email is already registered.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, college_id, course, year) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $college_id, $course, $year]);
        $user_id = (int)$pdo->lastInsertId();

        // Auto-join the community of the user's college
        $stmt = $pdo->prepare('INSERT IGNORE INTO community_members (user_id, community_id)
                               SELECT ?, id FROM communities WHERE college_id = ?');
        $stmt->execute([$user_id, $college_id]);

        // Log the user in
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['role']      = 'user';

        set_flash('success', 'Welcome to CampusHub, ' . $name . '!');
        redirect(BASE_URL . '/pages/home.php');
    }
}

$colleges = get_colleges($pdo);
$page_title = 'Sign up';
$no_sidebar = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="card form-card">
    <h2>Create your account</h2>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>Full name</label>
        <input type="text" name="name" value="<?= e($old['name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= e($old['email']) ?>" required>

        <label>Password (min 6 characters)</label>
        <input type="password" name="password" required>

        <label>College</label>
        <select name="college_id" required>
            <option value="">-- choose college --</option>
            <?php foreach ($colleges as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$old['college_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Course (e.g. B.Tech CSE)</label>
        <input type="text" name="course" value="<?= e($old['course']) ?>" required>

        <label>Year of study</label>
        <input type="number" name="year" min="1" max="6" value="<?= e($old['year']) ?>" required>

        <button type="submit" class="btn">Sign up</button>
    </form>
    <p class="muted">Already have an account? <a href="login.php">Log in</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
