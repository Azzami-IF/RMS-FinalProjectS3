<?php
// notifications/send_reminder_log.php
// Kirim notifikasi pengingat pencatatan menu harian (malam) ke semua user
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/NotificationService.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$notif = new NotificationService($db, $config);

$users = $db->query("SELECT * FROM users WHERE role='user'")->fetchAll();

foreach ($users as $u) {
    $title = 'Pengingat Catatan Makan Harian';
    $message = 'Jangan lupa mencatat menu makan harian Anda di aplikasi RMS hari ini!';
    $notif->createNotification($u['id'], $title, $message, 'info');
    if ($u['notifications_email'] ?? false) {
        $notif->sendEmail($u['id'], $u['email'], $title, $message);
    }
}
