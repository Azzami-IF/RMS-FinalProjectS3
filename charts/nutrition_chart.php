<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/AnalyticsService.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();

$analytics = new AnalyticsService($db);
$data = $analytics->nutritionSummary($_SESSION['user']['id']);

echo json_encode([
    'labels' => ['Protein', 'Lemak', 'Karbohidrat'],
    'datasets' => [[
        'data' => [
            $data['protein'],
            $data['fat'],
            $data['carbs']
        ]
    ]]
]);
