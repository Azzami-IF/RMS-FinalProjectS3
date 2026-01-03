<?php
require_once __DIR__ . '/../classes/PageBootstrap.php';

function require_login() {
    PageBootstrap::requireUser(__DIR__ . '/..', 'login.php');
}

function require_admin() {
    $app = PageBootstrap::requireUser(__DIR__ . '/..', 'login.php');
    if ($app->role() !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak');
    }
}
