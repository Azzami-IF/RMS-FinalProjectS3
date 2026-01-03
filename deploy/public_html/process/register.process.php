<?php
require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
    header('Location: ../register.php');
    exit;
}

// Validasi confirm password
if ($_POST['password'] !== $_POST['confirm_password']) {
    header('Location: ../register.php?error=password_mismatch');
    exit;
}

$app = AppContext::fromRootDir(__DIR__ . '/..');
$db = $app->db();
$auth = new Auth($db);

try {
    $auth->register(
        $_POST['name'],
        $_POST['email'],
        $_POST['password']
    );
    // Login otomatis setelah register
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user'] = $user;
    header('Location: ../profile_register.php');
} catch (PDOException $e) {
    header('Location: ../register.php?error=1');
}
