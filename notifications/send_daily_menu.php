<?php
// notifications/send_daily_menu.php
// Kirim notifikasi menu sehat harian dengan rekomendasi food dari Edamam API
require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/Cache.php';
require_once __DIR__ . '/../classes/NotificationService.php';
require_once __DIR__ . '/../classes/ApiClientEdamam.php';
require_once __DIR__ . '/../classes/UserPreferences.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$config = $app->config();
$db = $app->db();
$notif = new NotificationService($db, $config);
$edamam = new ApiClientEdamam(
    $config['EDAMAM_APP_ID'],
    $config['EDAMAM_APP_KEY'],
    $config['EDAMAM_USER_ID'] ?? ''
);
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

// Nutrisi tips berdasar kalori target
$tips = [
    'Perbanyak konsumsi sayur dan buah setiap hari untuk serat optimal.',
    'Pilih makanan dengan lemak sehat, hindari gorengan berlebihan.',
    'Minum air putih minimal 8 gelas sehari untuk hidrasi terbaik.',
    'Batasi konsumsi gula dan garam untuk kesehatan jangka panjang.',
    'Jangan lewatkan sarapan untuk energi dan fokus optimal.',
    'Perhatikan porsi makan, gunakan piring kecil untuk kontrol.',
    'Konsumsi protein tanpa lemak seperti ikan atau ayam tanpa kulit.',
    'Kombinasikan karbohidrat kompleks dengan protein dan serat.',
    'Masak dengan minyak sehat seperti olive oil atau coconut oil.',
    'Nikmati makanan dengan sempurna, kunyah perlahan dan sadar.'
];

// Query keywords untuk berbagai meal type dan target kalori
function getSearchQuery($dailyCalorie) {
    // Pisahkan target kalori per meal (rough estimate)
    // Breakfast: 25%, Lunch: 35%, Dinner: 30%, Snacks: 10%
    $breakfastCal = intval($dailyCalorie * 0.25);
    
    $keywords = [
        ['healthy breakfast', $breakfastCal],
        ['nutritious meal', intval($dailyCalorie * 0.35)],
        ['balanced diet', $dailyCalorie],
        ['light lunch', intval($dailyCalorie * 0.3)],
        ['protein rich', intval($dailyCalorie * 0.25)],
        ['vegetable salad', intval($dailyCalorie * 0.2)],
    ];
    
    return $keywords[array_rand($keywords)];
}

// Ambil semua user (atau satu user saat quick test)
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
         WHERE title LIKE '%Rekomendasi Menu Sehat%' 
         AND DATE(created_at) = ?"
    );
    $duplicateStmt->execute([$today]);
    $alreadySent = $duplicateStmt->fetchColumn();

    if ($alreadySent > 0) {
        exit("Notifikasi menu sudah dikirim hari ini.\n");
    }
}

foreach ($users as $u) {
    $sentInApp = $sentInApp ?? 0;
    $sentEmail = $sentEmail ?? 0;
    $skippedDisabled = $skippedDisabled ?? 0;
    $skippedNoHits = $skippedNoHits ?? 0;

    $inAppEnabled = (string)$prefs->get((int)$u['id'], 'notifications_inapp', '1') === '1';
    $emailEnabled = (string)$prefs->get((int)$u['id'], 'notifications_email', '1') === '1';

    if (!$inAppEnabled && !$emailEnabled) {
        $skippedDisabled++;
        continue; // Skip jika notifikasi disabled
    }

    $userId = (int)$u['id'];
    $userName = htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8');
    $targetKalori = (int)($u['daily_calorie_goal'] ?? 2000);
    
    // Coba cari recipe dengan keyword dinamis
    [$keyword, $cal] = getSearchQuery($targetKalori);
    $result = $edamam->searchFood($keyword, $cal);
    
    if (!isset($result['hits']) || count($result['hits']) === 0) {
        // Fallback ke search umum jika tidak ada hasil
        $result = $edamam->searchFood('healthy', $targetKalori);
    }
    
    if (isset($result['hits']) && count($result['hits']) > 0) {
        $pick = $result['hits'][array_rand($result['hits'])]['recipe'];
        $menuLabel = (string)($pick['label'] ?? 'Menu Sehat');
        $menuCal = round((float)($pick['calories'] ?? 0));
        $menuImg = (string)($pick['image'] ?? '');
        $cuisineType = (string)($pick['cuisineType'][0] ?? 'International');
        $healthLabels = $pick['healthLabels'] ?? [];
        
        // Ambil 2-3 label kesehatan saja untuk display
        $displayLabels = array_slice($healthLabels, 0, 2);
        $labelHtml = implode(', ', $displayLabels);

        // Prefer a stable Edamam recipe id for direct schedule creation
        $recipeId = '';
        if (!empty($pick['uri']) && preg_match('/recipe_([A-Za-z0-9]+)/', (string)$pick['uri'], $m)) {
            $recipeId = $m[1];
        }

        $scheduleUrl = $recipeId !== ''
            ? "process/schedule.process.php?action=create_from_recipe&recipe_id={$recipeId}"
            : '';

        // Link ke recommendation.php dengan query
        $menuQ = urlencode($menuLabel);
        // Deep-link to open the selected menu detail on recommendation.php
        $recommendationFocusUrl = "recommendation.php?q=$menuQ&calories=$targetKalori&focus_label=$menuQ";
        // General recommendation page (no forced detail)
        $recommendationListUrl = "recommendation.php?calories=$targetKalori";

        $recommendationUrl = $recommendationFocusUrl;
        $primaryUrl = $scheduleUrl !== '' ? $scheduleUrl : $recommendationUrl;

        $tip = $tips[array_rand($tips)];
        $title = 'Rekomendasi Menu Sehat Harian';

        $safeLabel = htmlspecialchars($menuLabel, ENT_QUOTES, 'UTF-8');
        $safeImg = htmlspecialchars($menuImg, ENT_QUOTES, 'UTF-8');
        $safePrimaryUrl = htmlspecialchars($primaryUrl, ENT_QUOTES, 'UTF-8');
        $safeRecUrl = htmlspecialchars($recommendationFocusUrl, ENT_QUOTES, 'UTF-8');
        $safeRecListUrl = htmlspecialchars($recommendationListUrl, ENT_QUOTES, 'UTF-8');
        $safeCuisine = htmlspecialchars($cuisineType, ENT_QUOTES, 'UTF-8');
        $safeLabels = htmlspecialchars($labelHtml, ENT_QUOTES, 'UTF-8');

        // In-app: singkat dan informatif, dengan link langsung ke recommendation.php.
        // notifications.php akan mengambil href ini untuk tombol "Buka Menu".
        $inAppMessage = "<a href='$safeRecUrl'>Buka Menu</a><br>"
            . "Target kalori: $targetKalori kcal<br>"
            . "<b>$safeLabel</b> ($menuCal kcal)";

        if ($inAppEnabled) {
            // Action URL ke recommendation.php (detail menu terbuka via focus_label)
            $notif->createNotification($userId, $title, $inAppMessage, 'menu', $recommendationFocusUrl);
            $sentInApp++;
        }

        if ($emailEnabled && !empty($u['email'])) {
            // Email: detail & berstruktur (tanpa emoji)
            $safeTip = nl2br(htmlspecialchars($tip, ENT_QUOTES, 'UTF-8'));
            $emailBody = "<html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;line-height:1.6;'>"
                . "<h2 style='color:#333;margin:0 0 8px 0;'>Rekomendasi Menu Sehat Harian</h2>"
                . "<p style='margin:0 0 12px 0;'>Halo <b>$userName</b>,</p>"
                . "<p style='margin:0 0 12px 0;'><strong>Target kalori harian:</strong> $targetKalori kcal</p>"
                . ($menuImg ? "<div style='margin:12px 0;'><img src='$safeImg' alt='$safeLabel' style='max-width:320px;border-radius:8px;'></div>" : "")
                . "<div style='background:#f5f5f5;padding:15px;border-radius:8px;margin:12px 0;'>"
                . "<h3 style='margin:0 0 8px 0;'>$safeLabel</h3>"
                . "<table style='width:100%;border-collapse:collapse;'>"
                . "<tr><td style='padding:8px;border:1px solid #ddd;'>Kalori</td><td style='padding:8px;border:1px solid #ddd;'><strong>$menuCal kcal</strong></td></tr>"
                . "<tr style='background:#f9f9f9;'><td style='padding:8px;border:1px solid #ddd;'>Tipe</td><td style='padding:8px;border:1px solid #ddd;'>$safeCuisine</td></tr>"
                . "<tr><td style='padding:8px;border:1px solid #ddd;'>Label</td><td style='padding:8px;border:1px solid #ddd;'>$safeLabels</td></tr>"
                . "</table>"
                . "</div>"
                . "<div style='background:#fffde7;padding:12px;border-left:4px solid #FBC02D;border-radius:4px;margin:12px 0;'>"
                . "<p style='margin:0 0 6px 0;'><strong>Catatan</strong></p>"
                . "<p style='margin:0;'>$safeTip</p>"
                . "</div>"
                . "<p><a href='$safeRecUrl' style='background:#4CAF50;color:white;padding:10px 16px;text-decoration:none;border-radius:4px;display:inline-block;font-weight:bold;'>Catat Menu Ini</a></p>"
                . "<p><a href='$safeRecListUrl' style='background:#2196F3;color:white;padding:10px 16px;text-decoration:none;border-radius:4px;display:inline-block;font-weight:bold;'>Lihat Menu Lainnya</a></p>"
                . "<p style='color:#666;font-size:12px;margin:16px 0 0 0;'>&copy; " . date('Y') . " RMS - Rekomendasi Makanan Sehat</p>"
                . "</body></html>";

            if ($notif->sendEmail($userId, $u['email'], $title, $emailBody, $primaryUrl)) {
                $sentEmail++;
            }
        }
    } else {
        // Fallback: tetap kirim notifikasi walau rekomendasi menu belum tersedia
        $fallbackUrl = "recommendation.php?q=healthy&calories=$targetKalori";
        $safeFallbackUrl = htmlspecialchars($fallbackUrl, ENT_QUOTES, 'UTF-8');
        $title = 'Rekomendasi Menu Sehat Harian';

        // In-app: singkat dan jelas + link langsung.
        $inAppMessage = "<a href='$safeFallbackUrl'>Buka Menu</a><br>"
            . "Target kalori: $targetKalori kcal<br>"
            . "Rekomendasi menu belum tersedia saat ini.";

        if ($inAppEnabled) {
            $notif->createNotification($userId, $title, $inAppMessage, 'menu', $fallbackUrl);
            $sentInApp++;
        }

        if ($emailEnabled && !empty($u['email'])) {
            $emailBody = "<html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;line-height:1.6;'>"
                . "<h2 style='color:#333;margin:0 0 8px 0;'>Rekomendasi Menu Sehat Harian</h2>"
                . "<p style='margin:0 0 12px 0;'>Halo <b>$userName</b>,</p>"
                . "<p style='margin:0 0 12px 0;'><strong>Target kalori harian:</strong> $targetKalori kcal</p>"
                . "<div style='background:#f5f5f5;padding:12px;border-radius:8px;margin:12px 0;'>"
                . "<p style='margin:0;'><strong>Info</strong><br>Rekomendasi menu belum tersedia saat ini. Silakan pilih dari daftar alternatif.</p>"
                . "</div>"
                . "<p><a href='$safeFallbackUrl' style='background:#2196F3;color:white;padding:10px 16px;text-decoration:none;border-radius:4px;display:inline-block;font-weight:bold;'>Lihat Menu Alternatif</a></p>"
                . "<p style='color:#666;font-size:12px;margin:16px 0 0 0;'>&copy; " . date('Y') . " RMS - Rekomendasi Makanan Sehat</p>"
                . "</body></html>";

            if ($notif->sendEmail($userId, $u['email'], $title, $emailBody, $fallbackUrl)) {
                $sentEmail++;
            }
        }

        $skippedNoHits++;
    }
}

// Monitoring output
$usersTotal = is_array($users) ? count($users) : 0;
echo "OK send_daily_menu | users={$usersTotal} | in_app={$sentInApp} | email={$sentEmail} | skipped_disabled={$skippedDisabled} | fallback={$skippedNoHits}\n";
?>