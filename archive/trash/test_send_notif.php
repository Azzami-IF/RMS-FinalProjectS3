<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/NotificationService.php';
require_once __DIR__ . '/classes/User.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();
$userModel = new User($db);
$user = $userModel->findByEmail('1@gmail.com');

if (!$user) {
    echo 'User tidak ditemukan';
    exit;
}

$notif = new NotificationService($db, $config);
$title = 'Notifikasi RMS';
$message = 'Ini adalah notifikasi pengujian dari aplikasi RMS.';

for ($i = 1; $i <= 3; $i++) {
    $result = $notif->sendEmail($user['id'], $user['email'], $title . " #$i", $message . " (Percobaan ke-$i)");
    echo "Percobaan $i: " . ($result ? '✅ Email berhasil dikirim!<br>' : '❌ Email gagal dikirim.<br>');
    flush();
}
