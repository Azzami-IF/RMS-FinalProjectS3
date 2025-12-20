<?php
session_start();
require_once '../config/database.php';

$db = (new Database(require '../config/env.php'))->getConnection();

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=evaluasi.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Tanggal','Total Kalori','Protein (g)','Lemak (g)','Karbohidrat (g)']);

$q = $db->query("SELECT s.schedule_date,
    SUM(f.calories) AS total_calories,
    SUM(f.protein * s.quantity) AS total_protein,
    SUM(f.fat * s.quantity) AS total_fat,
    SUM(f.carbs * s.quantity) AS total_carbs
FROM schedules s
JOIN foods f ON s.food_id = f.id
WHERE s.user_id = ".intval($_SESSION['user']['id'])."
GROUP BY s.schedule_date
ORDER BY s.schedule_date DESC");

while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['schedule_date'],
        round($row['total_calories'],2),
        round($row['total_protein'],2),
        round($row['total_fat'],2),
        round($row['total_carbs'],2)
    ]);
}
fclose($out);
