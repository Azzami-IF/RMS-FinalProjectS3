<?php

// Deprecated: the application uses config/env.php directly (see classes/NotificationService.php).
// This file is kept only for backward compatibility with older scripts.

$env = require __DIR__ . '/env.php';

return [
    'host' => $env['MAIL_HOST'] ?? 'smtp.gmail.com',
    'username' => $env['MAIL_USER'] ?? '',
    'password' => $env['MAIL_PASS'] ?? '',
    'port' => (int)($env['MAIL_PORT'] ?? 587),
    'encryption' => $env['MAIL_ENCRYPTION'] ?? 'tls',
    'from_email' => $env['MAIL_FROM'] ?? ($env['MAIL_USER'] ?? ''),
    'from_name' => $env['MAIL_FROM_NAME'] ?? 'RMS',
];
