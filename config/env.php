<?php
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'db_rms',
    'DB_USER' => 'root',
    'DB_PASS' => '02012006',

    'SPOON_API_KEY' => $_ENV['SPOON_API_KEY'] ?? 'd080297e98df4e328b0e0421712486ab',

    'MAIL_USER' => $_ENV['MAIL_USER'] ?? '',
    'MAIL_PASS' => $_ENV['MAIL_PASS'] ?? ''
];
