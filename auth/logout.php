<?php
require_once __DIR__ . '/../includes/init.php';

// Logout only works from the form in the navbar (POST + CSRF token)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $_SESSION = [];
    session_destroy();
}

redirect(BASE_URL . '/pages/home.php');
