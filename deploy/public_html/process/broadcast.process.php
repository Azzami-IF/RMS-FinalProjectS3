<?php
require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/NotificationService.php';

// Admin only
$app = AppContext::fromRootDir(__DIR__ . '/..');
$app->requireUser();
if (($app->role() ?? '') !== 'admin') {
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
$type = (string)($_POST['type'] ?? 'info');
$actionUrlRaw = trim((string)($_POST['action_url'] ?? ''));

if ($title === '') {
    header('Location: ../admin/broadcast.php?error=' . urlencode('Judul wajib diisi.'));
    exit;
}
if ($messageRaw === '') {
    header('Location: ../admin/broadcast.php?error=' . urlencode('Pesan wajib diisi.'));
    exit;
}

// Validate type (keep aligned with notifications UI labels/icons)
$allowedTypes = ['info', 'warning', 'success', 'tip', 'reminder', 'goal', 'menu', 'error'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'info';
}

// Validate action_url (relative only; no scheme/host)
$actionUrl = '';
if ($actionUrlRaw !== '') {
    $candidate = ltrim($actionUrlRaw, '/');
    if (str_starts_with($candidate, '//')) {
        header('Location: ../admin/broadcast.php?error=' . urlencode('Tautan tidak valid.'));
        exit;
    }
    $parts = parse_url($candidate);
    if ($parts === false || !empty($parts['scheme']) || !empty($parts['host'])) {
        header('Location: ../admin/broadcast.php?error=' . urlencode('Tautan harus berupa link relatif (tanpa domain).'));
        exit;
    }
    $actionUrl = $candidate;
}

// Store message as safe HTML with line breaks
$message = nl2br(htmlspecialchars($messageRaw, ENT_QUOTES, 'UTF-8'));
$titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

$config = $app->config();
$db = $app->db();
$notif = new NotificationService($db, $config);

try {
    $userIds = [];

    // Backward compatibility: old UI used 'all' or numeric user id
    if ($recipient === 'all') {
        $recipient = 'all_users';
    }

    if ($recipient === 'all_users') {
        $stmt = $db->query("SELECT id FROM users WHERE role='user' AND is_active=1");
        $userIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } elseif ($recipient === 'all_admins') {
        $stmt = $db->query("SELECT id FROM users WHERE role='admin' AND is_active=1");
        $userIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } elseif ($recipient === 'all_everyone') {
        $stmt = $db->query("SELECT id FROM users WHERE role IN ('user','admin') AND is_active=1");
        $userIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } else {
        $targetRole = 'user';
        $targetId = 0;

        if (preg_match('/^(user|admin)\:(\d+)$/', $recipient, $m)) {
            $targetRole = $m[1];
            $targetId = (int)$m[2];
        } else {
            // numeric only => treat as user id
            $targetId = (int)$recipient;
            $targetRole = 'user';
        }

        if ($targetId <= 0) {
            throw new Exception('Penerima tidak valid.');
        }

        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = ? LIMIT 1");
        $stmt->execute([$targetId, $targetRole]);
        $found = (int)$stmt->fetchColumn();
        if ($found <= 0) {
            throw new Exception('Akun tidak ditemukan.');
        }
        $userIds = [$found];
    }

    if (count($userIds) === 0) {
        throw new Exception('Tidak ada akun aktif untuk dikirim.');
    }

    $db->beginTransaction();
    foreach ($userIds as $uid) {
        $notif->createNotification((int)$uid, $titleSafe, $message, $type, $actionUrl);
    }
    $db->commit();

    header('Location: ../admin/broadcast.php?success=broadcast_sent&count=' . count($userIds));
    exit;
} catch (Throwable $e) {
    try {
        if ($db->inTransaction()) $db->rollBack();
    } catch (Throwable $t) {
        // ignore rollback errors
    }
    // Avoid leaking sensitive details, but keep message helpful
    $msg = $e instanceof Exception ? $e->getMessage() : 'Terjadi kesalahan saat mengirim notifikasi.';
    header('Location: ../admin/broadcast.php?error=' . urlencode($msg));
    exit;
}
