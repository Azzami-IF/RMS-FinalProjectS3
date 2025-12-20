<?php
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

echo "=== Latest 15 notifications for user 15 ===\n";
$stmt = $db->prepare('SELECT id, title, channel, status, created_at FROM notifications WHERE user_id = 15 ORDER BY created_at DESC LIMIT 15');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    $title = substr($row['title'], 0, 35);
    echo "ID: {$row['id']} | Title: {$title} | Channel: {$row['channel']} | Status: {$row['status']} | Created: {$row['created_at']}\n";
}

echo "\n=== Channel breakdown for user 15 ===\n";
$stmt = $db->prepare('SELECT channel, COUNT(*) as count FROM notifications WHERE user_id = 15 GROUP BY channel');
$stmt->execute();
$breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($breakdown as $row) {
    echo "Channel: {$row['channel']} | Count: {$row['count']}\n";
}

echo "\n=== Checking if email notifications are being shown in in_app query ===\n";
$stmt = $db->prepare('SELECT id, title, channel FROM notifications WHERE user_id = 15 AND channel = "in_app" ORDER BY created_at DESC LIMIT 5');
$stmt->execute();
$inapp = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "In-app notifications count: " . count($inapp) . "\n";
foreach ($inapp as $row) {
    echo "  ID: {$row['id']} | {$row['title']} | Channel: {$row['channel']}\n";
}
?>
