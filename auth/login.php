<?php
require_once __DIR__ . '/../includes/init.php';

if (is_logged_in()) redirect(BASE_URL . '/pages/home.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);   // prevents session fixation
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];

        set_flash('success', 'Logged in successfully.');
        redirect(BASE_URL . '/pages/home.php');
    } else {
        $error = 'Wrong email or password.';
    }
}

$page_title = 'Login';
$no_sidebar = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="card form-card">
    <h2>Log in</h2>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <label>Email</label>
        <input type="email" name="email" value="<?= e($email) ?>" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" class="btn">Log in</button>
    </form>
    <p class="muted">New here? <a href="register.php">Create an account</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
