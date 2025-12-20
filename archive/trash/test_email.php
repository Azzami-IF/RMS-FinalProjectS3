<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/NotificationService.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();
$notif = new NotificationService($db, $config);

// Ganti dengan user id yang valid di database Anda
$userId = 1; // asumsikan admin
$email = 'habiburrazami@gmail.com';
$title = 'Test Notifikasi Email RMS';
$message = 'Ini adalah email uji coba notifikasi dari aplikasi RMS.';

$result = $notif->sendEmail($userId, $email, $title, $message);

echo $result ? "✅ Email berhasil dikirim!" : "❌ Email gagal dikirim.";
