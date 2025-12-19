<?php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
    }
}
return [
    'DB_HOST' => getenv('DB_HOST') ?: 'localhost',
    'DB_NAME' => getenv('DB_NAME') ?: 'db_rms',
    'DB_USER' => getenv('DB_USER') ?: 'root',
    'DB_PASS' => getenv('DB_PASS') ?: '02012006',

    'EDAMAM_APP_ID' => getenv('EDAMAM_APP_ID') ?: '0b0eb516',
    'EDAMAM_APP_KEY' => getenv('EDAMAM_APP_KEY') ?: '4682a4832f0f2f5168d4d763bf2f7575',

    'MAIL_USER' => getenv('MAIL_USER') ?: 'id.rms.for.us@gmail.com',
    'MAIL_PASS' => getenv('MAIL_PASS') ?: 'rtsp utnv rlzd blpf',
];
