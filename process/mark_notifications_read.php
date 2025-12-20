<?php
// Tandai semua notifikasi user sebagai sudah dibaca saat dropdown dibuka
session_start();
if (!isset($_SESSION['user']['id'])) exit;
require_once __DIR__ . '/../config/database.php';
$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ? AND status = 'unread'");
$stmt->execute([$_SESSION['user']['id']]);
http_response_code(204);
