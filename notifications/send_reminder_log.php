<?php
// notifications/send_reminder_log.php
// Kirim notifikasi pengingat pencatatan menu harian + rekomendasi makanan ringan
require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/Cache.php';
require_once __DIR__ . '/../classes/NotificationService.php';
require_once __DIR__ . '/../classes/UserPreferences.php';
require_once __DIR__ . '/../classes/ApiClientEdamam.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$config = $app->config();
$db = $app->db();
$notif = new NotificationService($db, $config);
$prefs = new UserPreferences($db);
$edamam = new ApiClientEdamam(
    $config['EDAMAM_APP_ID'],
    $config['EDAMAM_APP_KEY'],
    $config['EDAMAM_USER_ID'] ?? ''
);

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

if ($userFilterId !== null && $userFilterId > 0) {
    $stmt = $db->prepare("SELECT u.id, u.name, u.email, u.daily_calorie_goal FROM users u WHERE u.role='user' AND u.id = ?");
    $stmt->execute([$userFilterId]);
    $users = $stmt->fetchAll();
} else {
    $users = $db->query("SELECT u.id, u.name, u.email, u.daily_calorie_goal FROM users u WHERE u.role='user'")->fetchAll();
}

// Prevent duplicate: check if already sent today
$today = date('Y-m-d');

if (!$forceSend) {
    $duplicateStmt = $db->prepare(
        "SELECT COUNT(*) FROM notifications 
         WHERE title LIKE '%Pengingat Penutupan Catatan%' 
         AND DATE(created_at) = ?"
    );
    $duplicateStmt->execute([$today]);
    $alreadySent = $duplicateStmt->fetchColumn();

    if ($alreadySent > 0) {
        exit("Notifikasi sudah dikirim hari ini.\n");
    }
}

foreach ($users as $u) {
    $inAppEnabled = (string)$prefs->get((int)$u['id'], 'notifications_inapp', '1') === '1';
    $emailEnabled = (string)$prefs->get((int)$u['id'], 'notifications_email', '1') === '1';

    if (!$inAppEnabled && !$emailEnabled) {
        continue;
    }

    $userId = (int)$u['id'];
    $userName = htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8');
    $actionUrl = 'schedules.php';
    $safeActionUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
    $todayDate = date('l, d F Y');
    $targetCalorie = (int)($u['daily_calorie_goal'] ?? 2000);

    $title = 'Pengingat Penutupan Catatan Asupan Nutrisi Harian';
    
    // Coba ambil rekomendasi makanan ringan dari Edamam (snack/light meal untuk malam)
    $snackKeywords = ['light snack', 'evening snack', 'healthy dessert', 'fruit salad'];
    $snackCal = intval($targetCalorie * 0.1); // 10% dari target untuk snack malam
    $foodData = null;
    
    foreach ($snackKeywords as $keyword) {
        $result = $edamam->searchFood($keyword, $snackCal);
        if (isset($result['hits']) && count($result['hits']) > 0) {
            $foodData = $result['hits'][array_rand($result['hits'])]['recipe'];
            break;
        }
    }
    
    // In-app: singkat, kompatibel dengan sanitizer notification_center (<b>/<strong>/<br>).
    $inAppMessage = "<strong>Pengingat Pencatatan Harian</strong><br>"
        . "Tanggal: $todayDate<br><br>"
        . "Lengkapi catatan asupan nutrisi hari ini.<br><br>"
        . "Gunakan tombol Buka untuk menuju pencatatan.";
    
    if ($inAppEnabled) {
        $notif->createNotification($userId, $title, $inAppMessage, 'reminder', $actionUrl);
    }
    
    if ($emailEnabled && !empty($u['email'])) {
        // Email: detail & berstruktur (tanpa emoji)
        $emailBody = "<html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;line-height:1.6;'>"
            . "<h2 style='color:#333;margin:0 0 8px 0;'>Pengingat Penutupan Catatan Nutrisi Harian</h2>"
            . "<p style='margin:0 0 12px 0;'>Halo <b>$userName</b>,</p>"
            . "<p style='margin:0 0 12px 0;'><strong>Tanggal:</strong> $todayDate</p>"
            . "<p style='margin:0 0 12px 0;'>Mohon pastikan seluruh asupan nutrisi Anda hari ini sudah tercatat.</p>";
        
        if ($foodData) {
            $foodLabel = htmlspecialchars((string)($foodData['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $foodCal = round((float)($foodData['calories'] ?? 0));
            $foodImg = htmlspecialchars((string)($foodData['image'] ?? ''), ENT_QUOTES, 'UTF-8');
            $cuisineType = htmlspecialchars((string)($foodData['cuisineType'][0] ?? 'International'), ENT_QUOTES, 'UTF-8');
            $healthLabels = $foodData['healthLabels'] ?? [];
            $labelText = htmlspecialchars(implode(', ', array_slice((array)$healthLabels, 0, 2)), ENT_QUOTES, 'UTF-8');

            $emailBody .= "<div style='background:#f5f5f5;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #FF9800;'>"
                . "<h3 style='margin-top:0;'>Rekomendasi Makanan Ringan Malam</h3>";
            
            if ($foodImg) {
                $emailBody .= "<img src='$foodImg' alt='$foodLabel' style='max-width:280px;border-radius:8px;margin:10px 0;'>";
            }
            
            $emailBody .= "<p><strong>$foodLabel</strong></p>"
                . "<table style='width:100%;border-collapse:collapse;'>"
                . "<tr style='background:#f0f0f0;'>"
                . "<td style='padding:8px;border:1px solid #ddd;'><strong>Parameter</strong></td>"
                . "<td style='padding:8px;border:1px solid #ddd;'><strong>Nilai</strong></td>"
                . "</tr>"
                . "<tr>"
                . "<td style='padding:8px;border:1px solid #ddd;'>Total Kalori</td>"
                . "<td style='padding:8px;border:1px solid #ddd;'><strong>$foodCal kcal</strong></td>"
                . "</tr>"
                . "<tr style='background:#f9f9f9;'>"
                . "<td style='padding:8px;border:1px solid #ddd;'>Tipe Masakan</td>"
                . "<td style='padding:8px;border:1px solid #ddd;'>$cuisineType</td>"
                . "</tr>"
                . "<tr>"
                . "<td style='padding:8px;border:1px solid #ddd;'>Label Kesehatan</td>"
                . "<td style='padding:8px;border:1px solid #ddd;'>$labelText</td>"
                . "</tr>"
                . "</table>"
                . "</div>";
        }
        
        $emailBody .= "<p><strong>Checklist Pencatatan Hari Ini:</strong></p>"
            . "<ul style='margin-left:20px;'>"
            . "<li>Sarapan</li>"
            . "<li>Camilan Pagi</li>"
            . "<li>Makan Siang</li>"
            . "<li>Camilan Sore</li>"
            . "<li>Makan Malam</li>"
            . "</ul>"
            . "<p><strong>Mengapa Pencatatan Penting?</strong></p>"
            . "<ul style='margin-left:20px;'>"
            . "<li>Memantau asupan kalori terhadap target personal Anda</li>"
            . "<li>Menganalisis distribusi nutrisi (protein, karbohidrat, lemak)</li>"
            . "<li>Mendapatkan rekomendasi perbaikan diet yang spesifik</li>"
            . "<li>Melacak tren kesehatan jangka panjang</li>"
            . "</ul>"
            . "<p style='border-top:1px solid #ddd;padding-top:15px;color:#666;font-size:12px;'>"
            . "Email ini dikirim sebagai pengingat otomatis. Semakin akurat pencatatan Anda, semakin baik analisis dan rekomendasi yang kami berikan."
            . "</p>"
            . "<p><a href='$safeActionUrl' style='background:#1976D2;color:white;padding:10px 16px;text-decoration:none;border-radius:4px;display:inline-block;font-weight:bold;'>"
            . "Buka Pencatatan Nutrisi</a></p>"
            . "<p>---<br><strong>RMS - Nutrition & Health Management System</strong></p>"
            . "</body></html>";
        
        $notif->sendEmail($userId, $u['email'], $title, $emailBody, $actionUrl);
    }
}
?>
