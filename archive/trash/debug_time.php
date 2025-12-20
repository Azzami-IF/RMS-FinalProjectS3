<?php
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

function rms_notif_time_ago(?string $datetime): string
{
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    if ($ts === false) return '';

    $diff = time() - $ts;
    if ($diff < 0) $diff = 0;

    echo "DEBUG: datetime=$datetime, ts=$ts, now=" . time() . ", diff=$diff\n";

    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 86400 * 7) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y H:i', $ts);
}

echo "Current time: " . time() . "\n";
echo "Current datetime: " . date('Y-m-d H:i:s') . "\n\n";

$stmt = $db->prepare('SELECT id, created_at FROM notifications WHERE user_id = 15 ORDER BY created_at DESC LIMIT 3');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    $formatted = rms_notif_time_ago($row['created_at']);
    echo "ID {$row['id']}: {$formatted}\n";
}
?>
