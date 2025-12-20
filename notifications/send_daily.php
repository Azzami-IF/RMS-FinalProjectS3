<?php
require_once '../config/database.php';
require_once '../classes/NotificationService.php';

$config = require '../config/env.php';
$db = (new Database($config))->getConnection();
$notif = new NotificationService($db, $config);

// Quick test switches: --user=<id> and --force=1 (or ?user_id=&force=1)
$cliArgs = $_SERVER['argc'] ?? 0;
$argvList = $_SERVER['argv'] ?? [];
$userFilterId = null;
$forceSend = false;

if ($cliArgs > 0) {
    foreach ($argvList as $arg) {
        if (preg_match('/^--user=(\d+)$/', $arg, $m)) {
            $userFilterId = (int)$m[1];
        }
        if ($arg === '--force=1' || $arg === '--force=true' || $arg === '--force') {
            $forceSend = true;
        }
    }
}

if (isset($_GET['user_id'])) {
    $userFilterId = (int)$_GET['user_id'];
}
if (isset($_GET['force'])) {
    $forceSend = (string)$_GET['force'] === '1' || (string)$_GET['force'] === 'true';
}

// Prevent duplicate notifications: check if already sent today
$today = date('Y-m-d');

if (!$forceSend) {
    $duplicateStmt = $db->prepare(
        "SELECT COUNT(*) FROM notifications 
         WHERE title = 'Pencatatan Menu Pagi' 
         AND DATE(created_at) = ?"
    );
    $duplicateStmt->execute([$today]);
    $alreadySent = $duplicateStmt->fetchColumn();

    if ($alreadySent > 0) {
        // Already sent today, skip
        exit("Notifikasi 'Pencatatan Menu Pagi' sudah dikirim hari ini.\n");
    }
}

if ($userFilterId !== null && $userFilterId > 0) {
    $stmt = $db->prepare("SELECT * FROM users WHERE role='user' AND id = ?");
    $stmt->execute([$userFilterId]);
    $users = $stmt->fetchAll();
} else {
    $users = $db->query("SELECT * FROM users WHERE role='user'")->fetchAll();
}

foreach ($users as $u) {
    $userName = htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8');
    $actionUrl = 'schedules.php';
    $today = date('l, d F Y', strtotime('today'));
    
    $safeActionUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');

    // In-app: singkat, padat, jelas + tombol direct
    $inAppMessage = "<strong>Pengingat Sarapan</strong><br>"
        . "<span style='font-size:12px;color:#666;'>$today</span><br><br>"
        . "<p>Silakan catat menu sarapan Anda.</p>"
        . "<a href='$safeActionUrl' style='background:#4CAF50;color:white;padding:8px 12px;text-decoration:none;border-radius:4px;display:inline-block;font-size:12px;font-weight:bold;'>Buka Pencatatan</a>";

    // Email: detail & berstruktur (tanpa emoji)
    $emailBody = "<html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;line-height:1.6;'>"
        . "<h2 style='color:#333;margin:0 0 8px 0;'>Pengingat Pencatatan Sarapan Pagi</h2>"
        . "<p style='margin:0 0 12px 0;'>Halo <b>$userName</b>,</p>"
        . "<p style='margin:0 0 12px 0;'><strong>Tanggal:</strong> $today</p>"
        . "<div style='background:#f5f5f5;padding:12px;border-radius:8px;margin:12px 0;'>"
        . "<p style='margin:0 0 8px 0;'><strong>Tujuan</strong></p>"
        . "<p style='margin:0;'>Membantu pencatatan sarapan agar analisis nutrisi harian lebih akurat.</p>"
        . "</div>"
        . "<p style='margin:0 0 8px 0;'><strong>Tips Sarapan Sehat</strong></p>"
        . "<ul style='margin:0 0 12px 18px;'>"
        . "<li>Utamakan protein</li>"
        . "<li>Tambahkan serat dari buah/sayur</li>"
        . "<li>Pilih karbohidrat kompleks</li>"
        . "<li>Minum air putih</li>"
        . "</ul>"
        . "<p><a href='$safeActionUrl' style='background:#4CAF50;color:white;padding:10px 16px;text-decoration:none;border-radius:4px;display:inline-block;font-weight:bold;'>Buka Pencatatan Sarapan</a></p>"
        . "<p style='color:#666;font-size:12px;margin:16px 0 0 0;'>RMS - Nutrition &amp; Health Management System</p>"
        . "</body></html>";

    $notif->createNotification($u['id'], 'Pengingat Pencatatan Sarapan Pagi', $inAppMessage, 'reminder', $actionUrl);
    if (!empty($u['email'])) {
        $notif->sendEmail($u['id'], $u['email'], 'Pengingat Pencatatan Sarapan Pagi', $emailBody, $actionUrl);
    }
}

