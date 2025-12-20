<?php
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

$stmt = $db->query('DESCRIBE notifications');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== Notifications Table Schema ===\n";
foreach ($cols as $col) {
    $default = $col['Default'] ?? 'NULL';
    echo "{$col['Field']} | {$col['Type']} | Default: {$default}\n";
}
?>
