<?php
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

echo "Fixing notifications with future timestamps...\n";

// Update any notifications with created_at in the future
$stmt = $db->prepare("UPDATE notifications SET created_at = NOW() WHERE created_at > NOW()");
$result = $stmt->execute();

if ($result) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE created_at > NOW()");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "✓ All notifications updated successfully!\n";
        echo "Future timestamp notifications have been set to current time.\n";
    } else {
        echo "Warning: Still have {$count} notifications with future timestamps\n";
    }
} else {
    echo "✗ Error updating notifications\n";
}
?>
