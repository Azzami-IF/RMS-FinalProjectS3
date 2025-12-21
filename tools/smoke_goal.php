<?php
// Usage:
//   php tools/smoke_goal.php --user_id=1
//   php tools/smoke_goal.php --email=user@example.com
//
// This script is a small backend smoke test for Goals progress/evaluation.

require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/UserGoal.php';
require_once __DIR__ . '/../classes/AnalyticsService.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$db = $app->db();

function parseArgs(array $argv): array {
    $out = [];
    foreach ($argv as $arg) {
        if (strpos($arg, '--') !== 0) continue;
        $parts = explode('=', substr($arg, 2), 2);
        $key = $parts[0] ?? '';
        $value = $parts[1] ?? true;
        if ($key !== '') $out[$key] = $value;
    }
    return $out;
}

$args = parseArgs($argv);
$userId = null;

if (!empty($args['user_id'])) {
    $userId = (int)$args['user_id'];
} elseif (!empty($args['email'])) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([(string)$args['email']]);
    $userId = (int)$stmt->fetchColumn();
}

if (!$userId) {
    $stmt = $db->query('SELECT user_id FROM user_goals WHERE is_active = 1 ORDER BY updated_at DESC, created_at DESC LIMIT 1');
    $userId = (int)$stmt->fetchColumn();
}

if (!$userId) {
    fwrite(STDERR, "No active goals found.\n");
    exit(2);
}

$userGoal = new UserGoal($db);
$analytics = new AnalyticsService($db);

$goalBefore = $userGoal->findActive($userId);
if (!$goalBefore) {
    fwrite(STDERR, "User #{$userId} has no active goal.\n");
    exit(2);
}

$stmt = $db->prepare('SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY logged_at DESC LIMIT 1');
$stmt->execute([$userId]);
$currentWeight = $stmt->fetchColumn();
$currentWeight = ($currentWeight === false || $currentWeight === null || $currentWeight === '') ? null : (float)$currentWeight;

$stats = $analytics->goalProgress($userId);
$userGoal->evaluateAndUpdateProgress($userId, $stats, $currentWeight);
$goalAfter = $userGoal->findActive($userId);

$summary = [
    'user_id' => $userId,
    'goal_type' => $goalAfter['goal_type'] ?? null,
    'target_date' => $goalAfter['target_date'] ?? null,
    'weekly_weight_change' => $goalAfter['weekly_weight_change'] ?? null,
    'target_weight_kg' => $goalAfter['target_weight_kg'] ?? null,
    'current_weight_kg' => $currentWeight,
    'progress_percent' => $goalAfter['progress'] ?? null,
    'evaluation' => $goalAfter['evaluation'] ?? null,
    'stats' => $stats,
];

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
