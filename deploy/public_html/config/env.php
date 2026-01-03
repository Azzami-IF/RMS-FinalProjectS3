<?php

// Load compatibility polyfills early.
require_once __DIR__ . '/../includes/compat.php';

// Dotenv is optional on some shared hosting setups; fall back to getenv().
$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
}
return [
    'DB_HOST' => getenv('DB_HOST') ?: '',
    'DB_NAME' => getenv('DB_NAME') ?: '',
    'DB_USER' => getenv('DB_USER') ?: '',
    'DB_PASS' => getenv('DB_PASS') ?: '',
    'APP_TIMEZONE' => getenv('APP_TIMEZONE') ?: 'Asia/Jakarta',
    'DB_TIMEZONE' => getenv('DB_TIMEZONE') ?: '+07:00',
    'EDAMAM_APP_ID' => getenv('EDAMAM_APP_ID') ?: '',
    'EDAMAM_APP_KEY' => getenv('EDAMAM_APP_KEY') ?: '',
    'MAIL_USER' => getenv('MAIL_USER') ?: '',
    'MAIL_PASS' => getenv('MAIL_PASS') ?: '',
    'MAIL_HOST' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'MAIL_PORT' => getenv('MAIL_PORT') ?: '587',
    'MAIL_ENCRYPTION' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'MAIL_FROM' => getenv('MAIL_FROM') ?: (getenv('MAIL_USER') ?: ''),
    'MAIL_FROM_NAME' => getenv('MAIL_FROM_NAME') ?: 'RMS',
    'EDAMAM_USER_ID' => getenv('EDAMAM_USER_ID') ?: '',
];