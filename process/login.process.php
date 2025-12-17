<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!isset($_POST['email'], $_POST['password'])) {
    header('Location: ../login.php');
    exit;
}

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$auth = new Auth($db);

$success = $auth->login(
    $_POST['email'],
    $_POST['password']
);

if ($success) {
    header('Location: ../dashboard.php');
} else {
    header('Location: ../login.php?error=1');
}
