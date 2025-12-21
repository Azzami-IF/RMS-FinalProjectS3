<?php
/**
 * Sync local DB schema to match sql.txt expectations (minimal changes).
 *
 * Applies:
 * - remove users.username column (no longer used)
 * - notifications.action_url size to VARCHAR(512)
 *
 * Usage:
 *   php tools/schema_sync.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$db = $app->db();

$dbName = (string)$db->query('SELECT DATABASE()')->fetchColumn();
if ($dbName === '') {
    fwrite(STDERR, "No database selected (check DB_NAME in .env)\n");
    exit(2);
}

echo "DB: {$dbName}\n\n";

function columnExists(PDO $db, string $dbName, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1'
    );
    $stmt->execute([$dbName, $table, $column]);
    return (bool)$stmt->fetchColumn();
}

function getColumnType(PDO $db, string $dbName, string $table, string $column): ?string
{
    $stmt = $db->prepare(
        'SELECT column_type FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1'
    );
    $stmt->execute([$dbName, $table, $column]);
    $val = $stmt->fetchColumn();
    return $val === false ? null : (string)$val;
}

$changes = 0;

// 1) users.username (removed)
if (columnExists($db, $dbName, 'users', 'username')) {
    echo "Applying: ALTER TABLE users DROP COLUMN username;\n";
    $db->exec("ALTER TABLE users DROP COLUMN username");
    $changes++;
} else {
    echo "OK: users.username does not exist\n";
}

// 2) notifications.action_url
$actionUrlType = getColumnType($db, $dbName, 'notifications', 'action_url');
if ($actionUrlType === null) {
    echo "WARN: notifications.action_url column not found (sql.txt expects it).\n";
} else {
    // Normalize: expect varchar(512)
    if (preg_match('~^varchar\((\d+)\)$~i', $actionUrlType, $m)) {
        $len = (int)$m[1];
        if ($len !== 512) {
            echo "Applying: ALTER TABLE notifications MODIFY COLUMN action_url VARCHAR(512) NULL; (was {$actionUrlType})\n";
            $db->exec("ALTER TABLE notifications MODIFY COLUMN action_url VARCHAR(512) NULL");
            $changes++;
        } else {
            echo "OK: notifications.action_url is {$actionUrlType}\n";
        }
    } else {
        // Any non-varchar type: force to expected
        echo "Applying: ALTER TABLE notifications MODIFY COLUMN action_url VARCHAR(512) NULL; (was {$actionUrlType})\n";
        $db->exec("ALTER TABLE notifications MODIFY COLUMN action_url VARCHAR(512) NULL");
        $changes++;
    }
}

echo "\nDone. Changes applied: {$changes}\n";
