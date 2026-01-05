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

$next = $_POST['next'] ?? '';
$next = is_string($next) ? trim($next) : '';

$isSafeNext = false;
if ($next !== '') {
    // Prevent header injection and open redirects.
    if (!str_contains($next, "\n") && !str_contains($next, "\r")) {
        $scheme = parse_url($next, PHP_URL_SCHEME);
        $host = parse_url($next, PHP_URL_HOST);
        if ($scheme === null && $host === null && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
            $isSafeNext = true;
        }
    }
}

if ($success) {
    $role = $_SESSION['user']['role'] ?? null;

    if ($isSafeNext) {
        $nextPath = (string)parse_url($next, PHP_URL_PATH);
        $isAdminPath = ($nextPath !== '' && str_contains($nextPath, '/admin/'));
        if (!($isAdminPath && $role !== 'admin')) {
            header('Location: ' . $next);
            exit;
        }
    }

    if ($role === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../dashboard.php');
    }
} else {
    $location = '../login.php?error=1';
    if ($isSafeNext) {
        $location .= '&next=' . rawurlencode($next);
    }
    header('Location: ' . $location);
}
