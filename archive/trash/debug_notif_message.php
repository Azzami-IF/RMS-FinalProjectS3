<?php
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

$userId = 15;

$stmt = $db->prepare('SELECT id, title, message, action_url, channel, type, status, created_at FROM notifications WHERE user_id = ? AND type = "menu" ORDER BY id DESC LIMIT 5');
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No menu notifications found for user {$userId}.\n";
    exit;
}

// Same transformation as notification_center.php
function render_like_notification_center(string $message): string {
    $msg = str_ireplace(["<br />", "<br/>", "<br>"], "\n", $message);
    $msg = strip_tags($msg);
    return nl2br(htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'));
}

foreach ($rows as $r) {
    $raw = (string)$r['message'];
    echo "=== Notification ID {$r['id']} ({$r['channel']}) {$r['created_at']} ===\n";
    echo "Title: {$r['title']}\n";
    echo "Action URL: {$r['action_url']}\n";
    echo "Raw length: " . strlen($raw) . "\n";
    echo "Raw preview:\n";
    echo substr($raw, 0, 350) . "\n\n";
    echo "Rendered (like notification_center):\n";
    echo render_like_notification_center($raw) . "\n\n";
}
