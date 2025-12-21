<?php
/**
 * Compare current DB schema vs sql.txt (expected schema).
 *
 * Usage:
 *   php tools/schema_compare.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$db = $app->db();

$rootDir = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$sqlPath = $rootDir . DIRECTORY_SEPARATOR . 'sql.txt';
if (!is_file($sqlPath)) {
    fwrite(STDERR, "sql.txt not found at: $sqlPath\n");
    exit(2);
}

$sql = file_get_contents($sqlPath);
if ($sql === false) {
    fwrite(STDERR, "Failed reading sql.txt\n");
    exit(2);
}

function normalizeSql(string $sql): string {
    // Remove archived migrations block to avoid parsing statements inside comments.
    $sql = preg_replace('~/\*.*?\*/~s', '', $sql) ?? $sql;
    // Remove single-line comments.
    $sql = preg_replace('~^\s*--.*$~m', '', $sql) ?? $sql;
    return $sql;
}

$sqlMain = normalizeSql($sql);

function parseCreateTables(string $sql): array {
    // Returns: [tableName => ['columns' => [colName => colTypeRaw]]]
    $tables = [];
    $pattern = '~CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*;~si';
    if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
        return $tables;
    }

    foreach ($matches as $m) {
        $table = $m[1];
        $body = $m[2];
        $columns = [];

        // Split by commas but keep it simple: we only need first token per line.
        $lines = preg_split('~\r?\n~', $body) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $line = rtrim($line, ',');

            // Ignore keys/constraints.
            if (preg_match('~^(PRIMARY\s+KEY|UNIQUE\s+KEY|UNIQUE\s+INDEX|KEY|INDEX|FULLTEXT\s+KEY|CONSTRAINT)\b~i', $line)) {
                continue;
            }

            // Match: `col` TYPE ...
            if (preg_match('~^`?([a-zA-Z0-9_]+)`?\s+(.+)$~', $line, $cm)) {
                $col = $cm[1];
                $typeRaw = trim($cm[2]);
                $columns[$col] = $typeRaw;
            }
        }

        $tables[$table] = ['columns' => $columns];
    }

    return $tables;
}

function parseCreates(string $sql, string $kind): array {
    // kind: VIEW|PROCEDURE|TRIGGER
    $names = [];
    $pattern = '~CREATE\s+' . $kind . '\s+`?([a-zA-Z0-9_]+)`?~i';
    if (!preg_match_all($pattern, $sql, $matches)) {
        return $names;
    }
    foreach ($matches[1] as $n) {
        $names[] = $n;
    }
    return array_values(array_unique($names));
}

$expectedTables = parseCreateTables($sqlMain);
$expectedViews = parseCreates($sqlMain, 'VIEW');
$expectedProcedures = parseCreates($sqlMain, 'PROCEDURE');
$expectedTriggers = parseCreates($sqlMain, 'TRIGGER');

$dbName = (string)$db->query('SELECT DATABASE()')->fetchColumn();
if ($dbName === '') {
    fwrite(STDERR, "No database selected (check DB_NAME in .env)\n");
    exit(2);
}

function fetchExistingTables(PDO $db, string $dbName): array {
    $stmt = $db->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = ?');
    $stmt->execute([$dbName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
}

function fetchExistingColumns(PDO $db, string $dbName): array {
    // Returns: [table => [col => column_type]]
    $stmt = $db->prepare('SELECT table_name, column_name, column_type FROM information_schema.columns WHERE table_schema = ?');
    $stmt->execute([$dbName]);
    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row = array_change_key_case($row, CASE_LOWER);
        $t = (string)$row['table_name'];
        $c = (string)$row['column_name'];
        $ct = (string)$row['column_type'];
        $out[$t][$c] = $ct;
    }
    return $out;
}

function fetchExistingViews(PDO $db, string $dbName): array {
    $stmt = $db->prepare('SELECT table_name FROM information_schema.views WHERE table_schema = ?');
    $stmt->execute([$dbName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
}

function fetchExistingProcedures(PDO $db, string $dbName): array {
    $stmt = $db->prepare("SELECT routine_name FROM information_schema.routines WHERE routine_schema = ? AND routine_type = 'PROCEDURE'");
    $stmt->execute([$dbName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
}

function fetchExistingTriggers(PDO $db, string $dbName): array {
    $stmt = $db->prepare('SELECT trigger_name FROM information_schema.triggers WHERE trigger_schema = ?');
    $stmt->execute([$dbName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
}

$existingTables = fetchExistingTables($db, $dbName);
$existingColumns = fetchExistingColumns($db, $dbName);
$existingViews = fetchExistingViews($db, $dbName);
$existingProcedures = fetchExistingProcedures($db, $dbName);
$existingTriggers = fetchExistingTriggers($db, $dbName);

$missingTables = [];
$missingColumns = []; // [table => [col...]]
$typeMismatches = []; // [table.col => ['expected' => ..., 'actual' => ...]]

foreach ($expectedTables as $table => $meta) {
    if (!in_array($table, $existingTables, true)) {
        $missingTables[] = $table;
        continue;
    }

    $expectedCols = $meta['columns'];
    $actualCols = $existingColumns[$table] ?? [];

    foreach ($expectedCols as $col => $typeRaw) {
        if (!array_key_exists($col, $actualCols)) {
            $missingColumns[$table][] = $col;
            continue;
        }

        // Lightweight type check for common mismatches (varchar length/enum).
        // Map sql.txt raw type to a best-effort comparable signature.
        $expectedSig = strtolower($typeRaw);
        $expectedSig = preg_replace('~\s+.*$~', '', $expectedSig) ?? $expectedSig; // base keyword (varchar/int/enum/decimal/etc)
        $actualType = strtolower((string)$actualCols[$col]);

        // Special-case varchar(N)
        if (preg_match('~^(varchar)\s*\((\d+)\)~i', $typeRaw, $mm)) {
            $expectedSig = 'varchar(' . $mm[2] . ')';
        } elseif (preg_match('~^(decimal)\s*\((\d+),(\d+)\)~i', $typeRaw, $mm)) {
            $expectedSig = 'decimal(' . $mm[2] . ',' . $mm[3] . ')';
        } elseif (preg_match('~^(enum)\s*\((.+)\)~i', $typeRaw, $mm)) {
            $expectedSig = 'enum(' . strtolower(preg_replace('~\s+~', '', (string)$mm[2])) . ')';
        }

        $actualSig = $actualType;
        if (preg_match('~^varchar\((\d+)\)~', $actualType, $mm)) {
            $actualSig = 'varchar(' . $mm[1] . ')';
        } elseif (preg_match('~^decimal\((\d+),(\d+)\)~', $actualType, $mm)) {
            $actualSig = 'decimal(' . $mm[1] . ',' . $mm[2] . ')';
        } elseif (preg_match('~^enum\((.+)\)~', $actualType, $mm)) {
            $actualSig = 'enum(' . strtolower(preg_replace('~\s+~', '', (string)$mm[1])) . ')';
        } else {
            $actualSig = preg_replace('~\s+.*$~', '', $actualSig) ?? $actualSig;
        }

        if ($expectedSig !== '' && $actualSig !== '' && $expectedSig !== $actualSig) {
            // Only report for a small set of high-impact columns to avoid noisy output.
            $watch = [
                'foods.image_url',
                'notifications.action_url',
                'notifications.type',
            ];
            $key = $table . '.' . $col;
            if (in_array($key, $watch, true)) {
                $typeMismatches[$key] = ['expected' => $typeRaw, 'actual' => $actualCols[$col]];
            }
        }
    }
}

function diffNames(array $expected, array $actual): array {
    $missing = [];
    foreach ($expected as $name) {
        if (!in_array($name, $actual, true)) {
            $missing[] = $name;
        }
    }
    sort($missing);
    return $missing;
}

$missingViews = diffNames($expectedViews, $existingViews);
$missingProcedures = diffNames($expectedProcedures, $existingProcedures);
$missingTriggers = diffNames($expectedTriggers, $existingTriggers);

$hasDiff = false;

echo "DB: {$dbName}\n";

echo "\n== Tables ==\n";
if ($missingTables) {
    $hasDiff = true;
    echo "Missing tables (in DB, but expected by sql.txt):\n";
    foreach ($missingTables as $t) echo "  - $t\n";
} else {
    echo "OK: all expected tables exist\n";
}

if ($missingColumns) {
    $hasDiff = true;
    echo "\nMissing columns:\n";
    ksort($missingColumns);
    foreach ($missingColumns as $t => $cols) {
        sort($cols);
        echo "  - $t: " . implode(', ', $cols) . "\n";
    }
}

if ($typeMismatches) {
    $hasDiff = true;
    echo "\nType mismatches (watched columns):\n";
    ksort($typeMismatches);
    foreach ($typeMismatches as $k => $v) {
        echo "  - $k: expected {$v['expected']} ; actual {$v['actual']}\n";
    }
}

echo "\n== Views ==\n";
if ($missingViews) {
    $hasDiff = true;
    echo "Missing views: " . implode(', ', $missingViews) . "\n";
} else {
    echo "OK: all expected views exist\n";
}

echo "\n== Procedures ==\n";
if ($missingProcedures) {
    $hasDiff = true;
    echo "Missing procedures: " . implode(', ', $missingProcedures) . "\n";
} else {
    echo "OK: all expected procedures exist\n";
}

echo "\n== Triggers ==\n";
if ($missingTriggers) {
    $hasDiff = true;
    echo "Missing triggers: " . implode(', ', $missingTriggers) . "\n";
} else {
    echo "OK: all expected triggers exist\n";
}

echo "\nDone.\n";
exit($hasDiff ? 1 : 0);
