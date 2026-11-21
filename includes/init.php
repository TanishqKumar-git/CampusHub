<?php
// Loaded at the top of every page: session, database, helpers.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// BASE_URL = web path of this project, e.g. "/campushub".
// Worked out automatically so links work wherever the folder is placed in htdocs.
$doc_root     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$project_path = str_replace('\\', '/', realpath(__DIR__ . '/..'));
define('BASE_URL', rtrim(str_replace($doc_root, '', $project_path), '/'));
