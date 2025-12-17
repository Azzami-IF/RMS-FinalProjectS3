<?php
require_once '../config/database.php';
require_once '../classes/NotificationService.php';

$config = require '../config/env.php';
$db = (new Database($config))->getConnection();
$notif = new NotificationService($db, $config);

$users = $db->query("SELECT * FROM users WHERE role='user'")->fetchAll();

foreach ($users as $u) {
    // Send email notification
    $notif->sendEmail($u['id'], $u['email'], 'Pengingat Harian', 'Jangan lupa konsumsi menu sehat hari ini!');
    
    // Create in-app notification
    $notif->createNotification($u['id'], 'Pengingat Harian', 'Jangan lupa konsumsi menu sehat hari ini!', 'info');
}
