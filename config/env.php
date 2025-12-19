<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();
return [
    'DB_HOST' => getenv('DB_HOST') ?: '',
    'DB_NAME' => getenv('DB_NAME') ?: '',
    'DB_USER' => getenv('DB_USER') ?: '',
    'DB_PASS' => getenv('DB_PASS') ?: '',
    'EDAMAM_APP_ID' => getenv('EDAMAM_APP_ID') ?: '',
    'EDAMAM_APP_KEY' => getenv('EDAMAM_APP_KEY') ?: '',
    'MAIL_USER' => getenv('MAIL_USER') ?: '',
    'MAIL_PASS' => getenv('MAIL_PASS') ?: '',
    'EDAMAM_USER_ID' => getenv('EDAMAM_USER_ID') ?: '',
];