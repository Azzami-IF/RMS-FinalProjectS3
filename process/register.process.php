<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!isset($_POST['name'], $_POST['email'], $_POST['password'])) {
    header('Location: ../register.php');
    exit;
}

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$auth = new Auth($db);

try {
    $auth->register(
        $_POST['name'],
        $_POST['email'],
        $_POST['password']
    );
    header('Location: ../login.php');
} catch (PDOException $e) {
    header('Location: ../register.php?error=1');
}
