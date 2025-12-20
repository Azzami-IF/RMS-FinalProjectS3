<?php
// Script: notifications/send_goal_evaluation.php
// Kirim notifikasi evaluasi goal mingguan dengan detail progress
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/UserGoal.php';
require_once __DIR__ . '/../classes/AnalyticsService.php';
require_once __DIR__ . '/../classes/NotificationService.php';
require_once __DIR__ . '/../classes/UserPreferences.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$notif = new NotificationService($db, $config);
$userGoal = new UserGoal($db);
$analytics = new AnalyticsService($db);
$prefs = new UserPreferences($db);

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

// Ambil semua user yang punya goal aktif (atau satu user saat quick test)
if ($userFilterId !== null && $userFilterId > 0) {
    $stmt = $db->prepare(
        "SELECT u.id, u.email, u.name FROM users u JOIN user_goals g ON u.id=g.user_id WHERE g.is_active=1 AND u.id = ? GROUP BY u.id"
    );
    $stmt->execute([$userFilterId]);
    $users = $stmt->fetchAll();
} else {
    $users = $db->query("SELECT u.id, u.email, u.name FROM users u JOIN user_goals g ON u.id=g.user_id WHERE g.is_active=1 GROUP BY u.id")->fetchAll();
}

// Prevent duplicate: check if already sent this week (weekly check)
$thisWeekStart = date('Y-m-d', strtotime('sunday this week'));

if (!$forceSend) {
    $duplicateStmt = $db->prepare(
        "SELECT COUNT(*) FROM notifications 
         WHERE title LIKE '%Evaluasi Target Mingguan%' 
         AND created_at >= ?"
    );
    $duplicateStmt->execute([$thisWeekStart]);
    $alreadySent = $duplicateStmt->fetchColumn();

    if ($alreadySent > 0) {
        exit("Notifikasi evaluasi sudah dikirim minggu ini.\n");
    }
}

foreach ($users as $u) {
    $goal = $userGoal->findActive($u['id']);
    if (!$goal) continue;

    $inAppEnabled = (string)$prefs->get((int)$u['id'], 'notifications_inapp', '1') === '1';
    $emailEnabled = (string)$prefs->get((int)$u['id'], 'notifications_email', '1') === '1';

    if (!$inAppEnabled && !$emailEnabled) {
        continue;
    }

    $stats = $analytics->goalProgress($u['id']);
    $stmt = $db->prepare("SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY logged_at DESC LIMIT 1");
    $stmt->execute([$u['id']]);
    $currentWeight = $stmt->fetchColumn();
    $userGoal->evaluateAndUpdateProgress($u['id'], $stats, $currentWeight);
    $goal = $userGoal->findActive($u['id']); // refresh
    if (empty($goal['evaluation'])) continue;
    
    $userId = (int)$u['id'];
    $userName = htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8');

    $goalTypeCode = strtolower((string)($goal['goal_type'] ?? ''));
    // Keep labels consistent with the Goal form in goals.php.
    $goalTypeMap = [
        'weight_loss' => 'Penurunan Berat Badan',
        'weight_gain' => 'Peningkatan Berat Badan',
        'maintain' => 'Pemeliharaan Berat Badan',
        'muscle_gain' => 'Peningkatan Massa Otot',

        // Backward/alias values (if any exist in DB).
        'maintain_weight' => 'Pemeliharaan Berat Badan',
        'maintenance' => 'Pemeliharaan Berat Badan',
        'maintain_weight_loss' => 'Pemeliharaan Berat Badan',
    ];
    $goalTypeLabel = $goalTypeMap[$goalTypeCode] ?? '';
    if ($goalTypeLabel === '') {
        $normalized = trim(str_replace('_', ' ', $goalTypeCode));
        $goalTypeLabel = $normalized !== '' ? ucwords($normalized) : 'Target Kesehatan';
    }
    $goalType = htmlspecialchars($goalTypeLabel, ENT_QUOTES, 'UTF-8');

    $progress = (int)($goal['progress'] ?? 0);
    $evaluation = htmlspecialchars($goal['evaluation'], ENT_QUOTES, 'UTF-8');
    $actionUrl = 'goals.php';
    $weekInfo = date('d M') . ' - ' . date('d M Y', strtotime('+6 days'));
    $safeActionUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
    
    $title = "Laporan Evaluasi Target Kesehatan Mingguan";
    
    // Progress bar visual
    $barPercent = min($progress, 100);
    $filledBars = intval($barPercent / 10);
    $progressBar = str_repeat('█', $filledBars) . str_repeat('░', 10 - $filledBars);

    // In-app: singkat, padat, jelas.
    // NOTE: notification_center hanya mempertahankan tag <b>/<strong>/<br>, jadi hindari <p>/<span>/<a>.
    $statusLabel = 'Perlu Peningkatan';
    if ($progress >= 75) {
        $statusLabel = 'Sangat Baik';
    } elseif ($progress >= 50) {
        $statusLabel = 'Baik';
    }

    $inAppMessage = "<strong>Evaluasi Target Mingguan</strong><br>"
        . "Periode: $weekInfo<br>"
        . "Progress: <strong>$progress%</strong><br>"
        . "Target: $goalType<br>"
        . "Status: <strong>$statusLabel</strong><br>"
        . "<br>"
        . nl2br(htmlspecialchars($evaluation, ENT_QUOTES, 'UTF-8'))
        . "<br><br>"
        . "Gunakan tombol Buka untuk melihat detail.";

    if ($inAppEnabled) {
        $notif->createNotification($userId, $title, $inAppMessage, 'goal', $actionUrl);
    }
    
    if ($emailEnabled && !empty($u['email'])) {
        // Email: detail & berstruktur (tanpa emoji)
        $emailBody = "<html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;line-height:1.6;'>"
            . "<h2 style='color:#333;'>Laporan Evaluasi Target Kesehatan Mingguan</h2>"
            . "<p>Kepada Yth. <b>$userName</b>,</p>"
            . "<p>Berikut ringkasan evaluasi target kesehatan Anda untuk periode <b>$weekInfo</b>.</p>"
            . "<div style='background:#f5f5f5;padding:15px;border-radius:8px;margin:15px 0;'>"
            . "<h3 style='margin-top:0;'>Ringkasan Evaluasi</h3>"
            . "<table style='width:100%;border-collapse:collapse;'>"
            . "<tr style='background:#4CAF50;color:white;'>"
            . "<td style='padding:10px;border:1px solid #ddd;'><strong>Kriteria</strong></td>"
            . "<td style='padding:10px;border:1px solid #ddd;'><strong>Hasil</strong></td>"
            . "</tr>"
            . "<tr>"
            . "<td style='padding:8px;border:1px solid #ddd;'>Tipe Target yang Sedang Dijalankan</td>"
            . "<td style='padding:8px;border:1px solid #ddd;'><strong>$goalType</strong></td>"
            . "</tr>"
            . "<tr style='background:#f9f9f9;'>"
            . "<td style='padding:8px;border:1px solid #ddd;'>Tingkat Pencapaian Minggu Ini</td>"
            . "<td style='padding:8px;border:1px solid #ddd;'><strong style='color:#FF6B6B;'>$progress%</strong></td>"
            . "</tr>"
            . "<tr>"
            . "<td colspan='2' style='padding:10px;border:1px solid #ddd;'><span style='font-family:monospace;font-weight:bold;letter-spacing:2px;font-size:14px;'>$progressBar</span></td>"
            . "</tr>"
            . "</table>"
            . "</div>"
            . "<div style='background:#fffde7;padding:12px;border-left:4px solid #FBC02D;border-radius:4px;margin:15px 0;'>"
            . "<p><strong>Hasil Evaluasi Detail:</strong></p>"
            . "<p>" . nl2br(htmlspecialchars($evaluation, ENT_QUOTES, 'UTF-8')) . "</p>"
            . "</div>";

        // Provide detailed recommendations based on progress
        if ($progress >= 75) {
            $emailBody .= "<div style='background:#e8f5e9;padding:12px;border-radius:4px;margin:15px 0;border-left:4px solid #4CAF50;'>"
                . "<p><strong>Status: Sangat Baik</strong></p>"
                . "<p>Anda telah mencapai <strong>75% atau lebih</strong> dari target mingguan.</p>"
                . "<p><strong>Rekomendasi untuk Minggu Depan:</strong></p>"
                . "<ul><li>Pertahankan momentum dengan tetap konsisten pada pola hidup sehat yang telah terbukti efektif</li>"
                . "<li>Tingkatkan intensitas atau durasi aktivitas fisik untuk meraih pencapaian 100%</li>"
                . "<li>Dokumentasikan strategi sukses Anda agar dapat diterapkan pada minggu-minggu berikutnya</li></ul>"
                . "</div>";
        } elseif ($progress >= 50) {
            $emailBody .= "<div style='background:#fff3e0;padding:12px;border-radius:4px;margin:15px 0;border-left:4px solid #FF9800;'>"
                . "<p><strong>Status: Baik</strong></p>"
                . "<p>Anda telah mencapai <strong>50-75%</strong> dari target mingguan.</p>"
                . "<p><strong>Rekomendasi untuk Minggu Depan:</strong></p>"
                . "<ul><li>Identifikasi faktor-faktor yang menghambat pencapaian target maksimal</li>"
                . "<li>Susun strategi perbaikan yang lebih konkret dan measurable</li>"
                . "<li>Tingkatkan fokus pada aspek nutrisi dan aktivitas fisik yang masih kurang</li></ul>"
                . "</div>";
        } else {
            $emailBody .= "<div style='background:#ffebee;padding:12px;border-radius:4px;margin:15px 0;border-left:4px solid #F44336;'>"
                . "<p><strong>Status: Perlu Peningkatan</strong></p>"
                . "<p>Anda baru mencapai <strong>di bawah 50%</strong> dari target mingguan.</p>"
                . "<p><strong>Rekomendasi untuk Minggu Depan:</strong></p>"
                . "<ul><li>Lakukan analisis mendalam tentang hambatan utama yang Anda hadapi</li>"
                . "<li>Kembali ke basic: pencatatan akurat, target realistis, dan konsistensi harian</li>"
                . "<li>Konsultasi dengan fitur rekomendasi sistem untuk mendapatkan saran yang lebih personal</li></ul>"
                . "</div>";
        }

        $emailBody .= "<p><strong>Langkah Tindak Lanjut yang Disarankan:</strong></p>"
            . "<ol>"
            . "<li>Review data nutrisi dan aktivitas fisik Anda minggu ini</li>"
            . "<li>Identifikasi pola dan kebiasaan yang mempengaruhi pencapaian target</li>"
            . "<li>Susun action plan spesifik untuk minggu depan dengan milestone harian</li>"
            . "<li>Manfaatkan fitur monitoring dan rekomendasi sistem untuk dukungan berkelanjutan</li>"
            . "</ol>"
            . "<p style='border-top:1px solid #ddd;padding-top:15px;color:#666;font-size:12px;'>"
            . "Email ini dikirim secara otomatis sebagai bagian dari program monitoring target kesehatan personal Anda. "
            . "Data evaluasi ini didasarkan pada catatan nutrisi dan aktivitas yang telah Anda input dalam sistem."
            . "</p>"
            . "<p><a href='$safeActionUrl' style='background:#4CAF50;color:white;padding:10px 16px;text-decoration:none;border-radius:4px;display:inline-block;font-weight:bold;'>"
            . "Buka Dashboard Target Kesehatan</a></p>"
            . "<p>---<br><strong>RMS - Nutrition & Health Management System</strong></p>"
            . "</body></html>";
        
        $notif->sendEmail($userId, $u['email'], $title, $emailBody, $actionUrl);
    }
}
?>