<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/NotificationService.php';

// Admin only
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak');
}

$action = (string)($_POST['action'] ?? '');
if ($action !== 'broadcast') {
    header('Location: ../admin/broadcast.php?error=invalid_action');
    exit;
}

$recipient = (string)($_POST['recipient'] ?? '');
$title = trim((string)($_POST['title'] ?? ''));
$messageRaw = trim((string)($_POST['message'] ?? ''));

if ($title === '') {
    header('Location: ../admin/broadcast.php?error=' . urlencode('Judul wajib diisi.'));
    exit;
}
if ($messageRaw === '') {
    header('Location: ../admin/broadcast.php?error=' . urlencode('Pesan wajib diisi.'));
    exit;
}

// Store message as safe HTML with line breaks
$message = nl2br(htmlspecialchars($messageRaw, ENT_QUOTES, 'UTF-8'));
$titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$notif = new NotificationService($db, $config);

try {
    $userIds = [];

    if ($recipient === 'all') {
        $stmt = $db->query("SELECT id FROM users WHERE role='user' AND is_active=1");
        $userIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } else {
        $userId = (int)$recipient;
        if ($userId <= 0) {
            throw new Exception('Penerima tidak valid.');
        }
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role='user' LIMIT 1");
        $stmt->execute([$userId]);
        $found = (int)$stmt->fetchColumn();
        if ($found <= 0) {
            throw new Exception('Pengguna tidak ditemukan.');
        }
        $userIds = [$found];
    }

    if (count($userIds) === 0) {
        throw new Exception('Tidak ada pengguna aktif untuk dikirim.');
    }

    $db->beginTransaction();
    foreach ($userIds as $uid) {
        $notif->createNotification((int)$uid, $titleSafe, $message, 'info');
    }
    $db->commit();

    header('Location: ../admin/broadcast.php?success=broadcast_sent&count=' . count($userIds));
    exit;
} catch (Exception $e) {
    try {
        if ($db->inTransaction()) $db->rollBack();
    } catch (Throwable $t) {
        // ignore rollback errors
    }
    header('Location: ../admin/broadcast.php?error=' . urlencode($e->getMessage()));
    exit;
}
