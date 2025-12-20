<?php
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

echo "=== Timezone Information ===\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "PHP time: " . time() . " (" . date('Y-m-d H:i:s') . ")\n\n";

$stmt = $db->query("SELECT @@GLOBAL.time_zone as global_tz, @@SESSION.time_zone as session_tz, NOW() as db_now, UNIX_TIMESTAMP(NOW()) as db_unix");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "MySQL global timezone: " . $result['global_tz'] . "\n";
echo "MySQL session timezone: " . $result['session_tz'] . "\n";
echo "MySQL NOW(): " . $result['db_now'] . "\n";
echo "MySQL UNIX_TIMESTAMP(NOW()): " . $result['db_unix'] . "\n";
echo "PHP UNIX timestamp: " . time() . "\n";
echo "Difference: " . ($result['db_unix'] - time()) . " seconds\n";

// Check current notifications
echo "\n=== Sample Notification Times ===\n";
$stmt = $db->prepare("SELECT id, created_at, UNIX_TIMESTAMP(created_at) as unix_ts FROM notifications WHERE user_id = 15 LIMIT 2");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "ID {$row['id']}: {$row['created_at']} (unix: {$row['unix_ts']})\n";
}
?>
