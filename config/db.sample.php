<?php
// Copy this file to db.php and put your own MySQL details.
// db.php is ignored by Git so passwords never reach GitHub.

$db_host = 'localhost';
$db_name = 'campushub';
$db_user = 'root';
$db_pass = '';          // XAMPP default is an empty password

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Is MySQL running and schema.sql imported?');
}
