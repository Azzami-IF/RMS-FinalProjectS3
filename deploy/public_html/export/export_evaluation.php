<?php
require_once __DIR__ . '/../classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$GLOBALS['rms_app'] = $app;
$app->requireUser('login.php');

$db = $app->db();
$user = $app->user();
$userId = (int)($user['id'] ?? 0);
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=evaluasi.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Tanggal','Total Kalori','Protein (g)','Lemak (g)','Karbohidrat (g)']);

$stmt = $db->prepare(
    "SELECT s.schedule_date,
        SUM(f.calories) AS total_calories,
        SUM(f.protein * s.quantity) AS total_protein,
        SUM(f.fat * s.quantity) AS total_fat,
        SUM(f.carbs * s.quantity) AS total_carbs
    FROM schedules s
    JOIN foods f ON s.food_id = f.id
    WHERE s.user_id = ?
    GROUP BY s.schedule_date
    ORDER BY s.schedule_date DESC"
);
$stmt->execute([$userId]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['schedule_date'],
        round($row['total_calories'],2),
        round($row['total_protein'],2),
        round($row['total_fat'],2),
        round($row['total_carbs'],2)
    ]);
}
fclose($out);
exit;
