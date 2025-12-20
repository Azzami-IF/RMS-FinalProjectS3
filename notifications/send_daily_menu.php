<?php
// notifications/send_daily_menu.php
// Kirim notifikasi menu sehat harian (pagi) ke semua user
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Cache.php'; // FIX: Add Cache class
require_once __DIR__ . '/../classes/NotificationService.php';
require_once __DIR__ . '/../classes/ApiClientEdamam.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$notif = new NotificationService($db, $config);
$edamam = new ApiClientEdamam($config['EDAMAM_APP_ID'], $config['EDAMAM_APP_KEY']);

// List tips nutrisi sederhana (bisa dikembangkan)
$tips = [
    'Perbanyak konsumsi sayur dan buah setiap hari.',
    'Pilih makanan dengan lemak sehat, hindari gorengan berlebihan.',
    'Minum air putih minimal 8 gelas sehari.',
    'Batasi konsumsi gula dan garam.',
    'Jangan lewatkan sarapan untuk energi optimal.',
    'Perhatikan porsi makan, jangan berlebihan.',
    'Konsumsi protein tanpa lemak seperti ikan atau ayam tanpa kulit.'
];

// Ambil semua user
$users = $db->query("SELECT * FROM users WHERE role='user'")->fetchAll();

foreach ($users as $u) {
    $targetKalori = $u['daily_calorie_goal'] ?? 2000;
    $result = $edamam->searchFood('healthy', $targetKalori);
    if (isset($result['hits']) && count($result['hits']) > 0) {
        $pick = $result['hits'][array_rand($result['hits'])]['recipe'];
        $menuLabel = $pick['label'];
        $menuCal = round($pick['calories']);
        $menuImg = $pick['image'] ?? '';
        $menuQ = urlencode($menuLabel);
        $menuUrl = "recommendation.php?q=$menuQ&calories=$targetKalori";
        $tip = $tips[array_rand($tips)];
        $title = 'Menu Sehat Harian & Target Kalori';
        $message = "<a href='$menuUrl' style='text-decoration:none'><img src='$menuImg' alt='$menuLabel' style='width:60px;height:60px;border-radius:8px;margin-bottom:6px;'><br><b>$menuLabel</b> ($menuCal kcal)</a><br>Target kalori: $targetKalori kcal<br>Tips: $tip";
        $notif->createNotification($u['id'], $title, $message, 'info');
        if ($u['notifications_email'] ?? false) {
            $notif->sendEmail($u['id'], $u['email'], $title, strip_tags($message));
        }
    }
    // Jika tidak ada hasil Edamam, tidak kirim notifikasi
}
