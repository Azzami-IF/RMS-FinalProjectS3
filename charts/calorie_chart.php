<?php
session_start();
require_once '../config/database.php';
require_once '../classes/AnalyticsService.php';

$db = (new Database(require '../config/env.php'))->getConnection();
$analytics = new AnalyticsService($db);

$data = $analytics->caloriePerDay($_SESSION['user']['id']);

echo json_encode([
  'labels' => array_column($data,'schedule_date'),
  'datasets' => [[
    'label' => 'Kalori Harian',
    'data' => array_column($data,'total')
  ]]
]);
