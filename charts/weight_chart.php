<?php
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Tidak diizinkan']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/WeightLog.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();

$weightLog = new WeightLog($db);
$endDate = date('Y-m-d', strtotime('+2 days'));
$data = $weightLog->getByDateRange($_SESSION['user']['id'], date('Y-m-d', strtotime('-90 days')), $endDate);

echo json_encode([
    'labels' => array_map(function($item) {
        return date('d M', strtotime($item['logged_at']));
    }, $data),
    'datasets' => [[
        'label' => 'Berat Badan (kg)',
        'data' => array_column($data, 'weight_kg'),
        'borderColor' => 'rgb(75, 192, 192)',
        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
        'tension' => 0.1
    ]]
]);
?>