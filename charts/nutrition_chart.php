<?php
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

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
