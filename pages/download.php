<?php
require_once __DIR__ . '/../includes/init.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM resources WHERE id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    http_response_code(404);
    die('Resource not found.');
}

// Seed/demo rows have no real file
$path = $r['file_name'] ? realpath(__DIR__ . '/../uploads/' . basename($r['file_name'])) : false;
if (!$path || !is_file($path)) {
    set_flash('error', 'This is demo data, so there is no real file to download. Upload your own to test downloads.');
    redirect('resources.php');
}

// Count the download
$pdo->prepare('UPDATE resources SET downloads = downloads + 1 WHERE id = ?')->execute([$id]);

// Send the file with its original name
$download_name = $r['original_name'] ?: basename($path);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $download_name) . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
