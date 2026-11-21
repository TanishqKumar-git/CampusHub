<?php
require_once __DIR__ . '/../includes/init.php';

$types = ['Notes', 'PYQ', 'Book', 'Other'];

// Allowed upload types: extension => list of acceptable MIME types
$allowed = [
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword', 'application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    'ppt'  => ['application/vnd.ms-powerpoint', 'application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
    'zip'  => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
];
$max_size = 10 * 1024 * 1024; // 10 MB

$errors = [];

// ---------- Handle upload ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    verify_csrf();

    $title      = trim($_POST['title'] ?? '');
    $subject    = trim($_POST['subject'] ?? '');
    $semester   = (int)($_POST['semester'] ?? 0);
    $college_id = (int)($_POST['college_id'] ?? 0);
    $type       = $_POST['type'] ?? '';
    $file       = $_FILES['file'] ?? null;

    if ($title === '' || mb_strlen($title) > 150)    $errors[] = 'Title is required (max 150 characters).';
    if ($subject === '' || mb_strlen($subject) > 100) $errors[] = 'Subject is required.';
    if ($semester < 1 || $semester > 10)             $errors[] = 'Semester must be between 1 and 10.';
    if (!in_array($type, $types, true))              $errors[] = 'Choose a valid type.';

    $stmt = $pdo->prepare('SELECT id FROM colleges WHERE id = ?');
    $stmt->execute([$college_id]);
    if (!$stmt->fetch()) $errors[] = 'Choose a college.';

    $ext = '';
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please choose a file to upload (max 10 MB).';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) {
            $errors[] = 'Only PDF, DOC, DOCX, PPT, PPTX and ZIP files are allowed.';
        } elseif ($file['size'] > $max_size) {
            $errors[] = 'File is too large. Maximum size is 10 MB.';
        } else {
            // Check the real MIME type, not just the file name
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            if (!in_array($mime, $allowed[$ext], true)) {
                $errors[] = 'File content does not match its extension.';
            }
        }
    }

    if (!$errors) {
        // Random name so nobody can guess or overwrite files
        $stored_name = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = __DIR__ . '/../uploads/' . $stored_name;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $original = mb_substr(basename($file['name']), 0, 255);
            $stmt = $pdo->prepare('INSERT INTO resources (user_id, title, subject, semester, college_id, type, file_name, original_name)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $title, $subject, $semester, $college_id, $type, $stored_name, $original]);
            set_flash('success', 'Resource uploaded. Thanks for sharing!');
            redirect('resources.php');
        } else {
            $errors[] = 'Could not save the file. Check that the uploads folder is writable.';
        }
    }
}

// ---------- Filters ----------
$q          = trim($_GET['q'] ?? '');
$f_subject  = trim($_GET['subject'] ?? '');
$f_sem      = (int)($_GET['semester'] ?? 0);
$f_college  = (int)($_GET['college_id'] ?? 0);
$f_type     = $_GET['type'] ?? '';

$where = ['1=1'];
$params = [];
if ($q !== '')          { $where[] = '(r.title LIKE :q OR r.subject LIKE :q2)'; $params[':q'] = "%$q%"; $params[':q2'] = "%$q%"; }
if ($f_subject !== '')  { $where[] = 'r.subject = :subject';    $params[':subject'] = $f_subject; }
if ($f_sem > 0)         { $where[] = 'r.semester = :sem';       $params[':sem'] = $f_sem; }
if ($f_college > 0)     { $where[] = 'r.college_id = :college'; $params[':college'] = $f_college; }
if (in_array($f_type, $types, true)) { $where[] = 'r.type = :type'; $params[':type'] = $f_type; }

$sql = 'SELECT r.*, u.name AS uploader, c.name AS college_name
        FROM resources r
        JOIN users u ON u.id = r.user_id
        JOIN colleges c ON c.id = r.college_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY r.created_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

$colleges = get_colleges($pdo);
$subjects = $pdo->query('SELECT DISTINCT subject FROM resources ORDER BY subject')->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Resources';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head"><h2>Study Resources</h2></div>

<!-- Filter bar -->
<form class="card filter-bar" method="get">
    <input type="text" name="q" placeholder="Search title or subject" value="<?= e($q) ?>">
    <select name="subject">
        <option value="">All subjects</option>
        <?php foreach ($subjects as $s): ?>
            <option <?= $f_subject === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="semester">
        <option value="">Any semester</option>
        <?php for ($i = 1; $i <= 8; $i++): ?>
            <option value="<?= $i ?>" <?= $f_sem === $i ? 'selected' : '' ?>>Sem <?= $i ?></option>
        <?php endfor; ?>
    </select>
    <select name="college_id">
        <option value="">All colleges</option>
        <?php foreach ($colleges as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $f_college === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="type">
        <option value="">All types</option>
        <?php foreach ($types as $t): ?>
            <option <?= $f_type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Filter</button>
    <a class="btn btn-outline" href="resources.php">Reset</a>
</form>

<!-- Resource list -->
<?php if (!$resources): ?>
    <div class="card"><p>No resources match your filters.</p></div>
<?php endif; ?>

<?php foreach ($resources as $r): ?>
    <div class="card resource-card">
        <div>
            <h3><?= e($r['title']) ?> <span class="tag tag-<?= strtolower(e($r['type'])) ?>"><?= e($r['type']) ?></span></h3>
            <p class="muted">
                <?= e($r['subject']) ?> &middot; Sem <?= (int)$r['semester'] ?> &middot; <?= e($r['college_name']) ?><br>
                Uploaded by <a href="profile.php?id=<?= (int)$r['user_id'] ?>"><?= e($r['uploader']) ?></a>
                &middot; <?= e(time_ago($r['created_at'])) ?> &middot; <?= (int)$r['downloads'] ?> downloads
            </p>
        </div>
        <a class="btn" href="download.php?id=<?= (int)$r['id'] ?>">Download</a>
    </div>
<?php endforeach; ?>

<!-- Upload form -->
<?php if (is_logged_in()): ?>
<div class="card form-card wide" id="upload">
    <h3>Upload a resource</h3>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>Title</label>
        <input type="text" name="title" maxlength="150" required>

        <label>Subject</label>
        <input type="text" name="subject" maxlength="100" placeholder="e.g. Data Structures" required>

        <div class="row">
            <div>
                <label>Semester</label>
                <input type="number" name="semester" min="1" max="10" required>
            </div>
            <div>
                <label>Type</label>
                <select name="type">
                    <?php foreach ($types as $t): ?><option><?= e($t) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <label>College</label>
        <select name="college_id" required>
            <option value="">-- choose college --</option>
            <?php foreach ($colleges as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>File (PDF, DOC, DOCX, PPT, PPTX, ZIP - max 10 MB)</label>
        <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip" required>

        <button type="submit" class="btn">Upload</button>
    </form>
</div>
<?php else: ?>
    <div class="card"><p><a href="<?= BASE_URL ?>/auth/login.php">Log in</a> to upload your own notes and papers.</p></div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
