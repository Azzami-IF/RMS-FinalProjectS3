<?php
require_once '../config/database.php';
require_once '../classes/NotificationService.php';

$db = (new Database(require '../config/env.php'))->getConnection();
$notif = new NotificationService($db);

$users = $db->query("SELECT * FROM users WHERE role='mahasiswa'")->fetchAll();

foreach ($users as $u) {
    $notif->sendEmail($u, 'Jangan lupa konsumsi menu sehat hari ini!');
}
