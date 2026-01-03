<?php
require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!isset($_POST['login_identifier'], $_POST['password'])) {
    header('Location: ../login.php');
    exit;
}

$app = AppContext::fromRootDir(__DIR__ . '/..');
$db = $app->db();
$auth = new Auth($db);

$success = $auth->login(
    $_POST['login_identifier'],
    $_POST['password']
);

if ($success) {
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../dashboard.php');
    }
} else {
    header('Location: ../login.php?error=1');
}
