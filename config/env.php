<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->safeLoad();
return [
    'DB_HOST' => getenv('DB_HOST') ?: '',
    'DB_NAME' => getenv('DB_NAME') ?: '',
    'DB_USER' => getenv('DB_USER') ?: '',
    'DB_PASS' => getenv('DB_PASS') ?: '',
    'EDAMAM_APP_ID' => getenv('EDAMAM_APP_ID') ?: '',
    'EDAMAM_APP_KEY' => getenv('EDAMAM_APP_KEY') ?: '',
    'MAIL_USER' => getenv('MAIL_USER') ?: '',
    'MAIL_PASS' => getenv('MAIL_PASS') ?: '',
    'MAIL_HOST' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'MAIL_PORT' => getenv('MAIL_PORT') ?: '587',
    'MAIL_ENCRYPTION' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'MAIL_FROM' => getenv('MAIL_FROM') ?: (getenv('MAIL_USER') ?: ''),
    'EDAMAM_USER_ID' => getenv('EDAMAM_USER_ID') ?: '',
];