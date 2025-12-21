<?php
// Tandai semua notifikasi user sebagai sudah dibaca saat dropdown dibuka
require_once __DIR__ . '/../classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$userId = (int)($app->user()['id'] ?? 0);
if ($userId <= 0) exit;

$db = $app->db();
$stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ? AND channel = 'in_app' AND status = 'unread'");
$stmt->execute([$userId]);
http_response_code(204);
