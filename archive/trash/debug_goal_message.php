<?php
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

$userId = 15;

// Allow override for quick debugging.
// - Web:   debug_goal_message.php?user_id=15
// - CLI:   php debug_goal_message.php 15
if (PHP_SAPI === 'cli' && isset($argv[1]) && is_numeric($argv[1])) {
    $userId = (int)$argv[1];
} elseif (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
}

$stmt = $db->prepare('SELECT id, title, message, action_url, channel, created_at FROM notifications WHERE user_id = ? AND type = "goal" ORDER BY id DESC LIMIT 3');
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No goal notifications found for user {$userId}.\n";
    exit;
}

foreach ($rows as $r) {
    echo "=== {$r['id']} {$r['channel']} {$r['created_at']} ===\n";
    echo "Title: {$r['title']}\n";
    echo "Action: {$r['action_url']}\n";
    echo "Message:\n{$r['message']}\n\n";
}
