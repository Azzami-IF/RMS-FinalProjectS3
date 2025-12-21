<?php
require_once __DIR__ . '/../classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');

header('Content-Type: application/json');

if (!$app->user()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/AnalyticsService.php';

$analytics = new AnalyticsService($app->db());
$data = $analytics->caloriePerDay((int)$app->user()['id']);

echo json_encode([
    'labels' => array_column($data, 'schedule_date'),
    'datasets' => [[
        'label' => 'Kalori Harian',
        'data' => array_column($data, 'total'),
    ]],
]);
