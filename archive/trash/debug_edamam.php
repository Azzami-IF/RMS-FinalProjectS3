<?php
require_once __DIR__ . '/classes/Cache.php';
require_once __DIR__ . '/classes/ApiClientEdamam.php';

$config = require __DIR__ . '/config/env.php';

$client = new ApiClientEdamam(
    $config['EDAMAM_APP_ID'] ?? '',
    $config['EDAMAM_APP_KEY'] ?? '',
    $config['EDAMAM_USER_ID'] ?? ''
);

$tests = [
    ['healthy', 2000],
    ['healthy breakfast', 500],
    ['balanced diet', 2000],
];

foreach ($tests as [$q, $cal]) {
    echo "=== Query: {$q} | calories: 0-{$cal} ===\n";
    $res = $client->searchFood($q, (int)$cal);

    if (isset($res['error'])) {
        echo "ERROR: {$res['error']}\n\n";
        continue;
    }

    $hits = $res['hits'] ?? null;
    if (!is_array($hits)) {
        echo "No hits array. Keys: " . implode(', ', array_keys($res)) . "\n\n";
        continue;
    }

    echo "hits: " . count($hits) . "\n";
    if (count($hits) > 0) {
        $recipe = $hits[0]['recipe'] ?? [];
        $label = $recipe['label'] ?? '';
        $calories = isset($recipe['calories']) ? round((float)$recipe['calories']) : '';
        echo "sample: {$label} ({$calories} kcal)\n";
    }
    echo "\n";
}
