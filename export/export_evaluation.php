<?php
session_start();
require_once '../config/database.php';

$db = (new Database(require '../config/env.php'))->getConnection();

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=evaluasi.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Tanggal','Total Kalori']);

$q = $db->query("
    SELECT schedule_date, SUM(calories) total
    FROM schedules s JOIN foods f ON s.food_id=f.id
    WHERE user_id=".$_SESSION['user']['id']."
    GROUP BY schedule_date
");

while ($row = $q->fetch()) {
    fputcsv($out, $row);
}
fclose($out);
