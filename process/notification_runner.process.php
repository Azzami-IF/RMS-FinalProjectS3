<?php
require_once __DIR__ . '/../classes/AppContext.php';

// Admin only
$app = AppContext::fromRootDir(__DIR__ . '/..');
$app->requireUser();
if (($app->role() ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak');
}

$action = (string)($_POST['action'] ?? '');
if ($action !== 'run_notification_script') {
    header('Location: ../admin/broadcast.php?error=invalid_action');
    exit;
}

$scriptKey = (string)($_POST['script'] ?? '');
$userIdRaw = trim((string)($_POST['user_id'] ?? ''));
$userId = null;
if ($userIdRaw !== '') {
    if (!preg_match('/^\d+$/', $userIdRaw)) {
        header('Location: ../admin/broadcast.php?error=' . urlencode('User ID tidak valid.'));
        exit;
    }
    $userId = (int)$userIdRaw;
    if ($userId <= 0) {
        header('Location: ../admin/broadcast.php?error=' . urlencode('User ID tidak valid.'));
        exit;
    }
}

$allowed = [
    'send_daily' => [
        'file' => __DIR__ . '/../notifications/send_daily.php',
        'label' => 'Pengingat Sarapan Pagi (send_daily.php)',
    ],
    'send_daily_menu' => [
        'file' => __DIR__ . '/../notifications/send_daily_menu.php',
        'label' => 'Rekomendasi Menu Harian (send_daily_menu.php)',
    ],
    'send_reminder_log' => [
        'file' => __DIR__ . '/../notifications/send_reminder_log.php',
        'label' => 'Pengingat Pencatatan Harian (send_reminder_log.php)',
    ],
    'send_goal_evaluation' => [
        'file' => __DIR__ . '/../notifications/send_goal_evaluation.php',
        'label' => 'Evaluasi Target Mingguan (send_goal_evaluation.php)',
    ],
];

if (!isset($allowed[$scriptKey])) {
    header('Location: ../admin/broadcast.php?error=' . urlencode('Script tidak valid.'));
    exit;
}

$scriptFile = $allowed[$scriptKey]['file'];
$scriptLabel = $allowed[$scriptKey]['label'];

if (!is_file($scriptFile)) {
    header('Location: ../admin/broadcast.php?error=' . urlencode('File script tidak ditemukan.'));
    exit;
}

$runId = date('YmdHis') . '-' . bin2hex(random_bytes(4));
$startedAt = microtime(true);
$startedAtText = date('Y-m-d H:i:s');

// Force send for monitoring to avoid early exit from duplicate checks.
$_GET['force'] = '1';
if ($userId !== null) {
    $_GET['user_id'] = (string)$userId;
}

// Ensure scripts behave like HTTP mode (not CLI)
$_SERVER['argc'] = 0;
$_SERVER['argv'] = [];

$status = 'success';
$error = '';
$output = '';

ob_start();
try {
    require $scriptFile;
    $output = (string)ob_get_clean();
} catch (Throwable $e) {
    $status = 'failed';
    $error = $e->getMessage();
    $output = (string)ob_get_clean();
}

$endedAt = microtime(true);
$endedAtText = date('Y-m-d H:i:s');
$durationMs = (int)round(($endedAt - $startedAt) * 1000);

// Persist run log for monitoring
$logPath = __DIR__ . '/../cache/notification_runs.json';
$entry = [
    'id' => $runId,
    'script' => $scriptKey,
    'label' => $scriptLabel,
    'user_id' => $userId,
    'status' => $status,
    'error' => $error,
    'started_at' => $startedAtText,
    'ended_at' => $endedAtText,
    'duration_ms' => $durationMs,
    'output' => mb_substr($output, 0, 15000),
];

try {
    $existing = [];
    if (is_file($logPath)) {
        $raw = file_get_contents($logPath);
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) {
            $existing = $decoded;
        }
    }

    if (!is_array($existing)) {
        $existing = [];
    }

    array_unshift($existing, $entry);
    $existing = array_slice($existing, 0, 50);

    $tmpPath = $logPath . '.tmp';
    file_put_contents($tmpPath, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    @rename($tmpPath, $logPath);
} catch (Throwable $e) {
    // If logging fails, still redirect with result.
}

if ($status === 'success') {
    header('Location: ../admin/broadcast.php?success=notif_run&id=' . urlencode($runId));
    exit;
}

header('Location: ../admin/broadcast.php?error=' . urlencode('Gagal menjalankan script. ' . ($error !== '' ? $error : '')) . '&id=' . urlencode($runId));
exit;
