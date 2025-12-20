<?php
/**
 * Notification Scheduler
 * 
 * Automatic scheduling system untuk mengirim notifikasi pada jam tertentu.
 * Dapat dijalankan via cron job atau manual execution.
 * 
 * Cron examples:
 * 0 7 * * * php /path/to/schedule_notifications.php morning
 * 0 12 * * * php /path/to/schedule_notifications.php menu
 * 0 20 * * * php /path/to/schedule_notifications.php evening
 * 0 18 * * 0 php /path/to/schedule_notifications.php weekly
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/NotificationService.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();

// Get command argument (morning|menu|evening|weekly)
$command = $argv[1] ?? 'all';
$timestamp = date('Y-m-d H:i:s');

// Notification scheduling configuration
$schedules = [
    'morning' => [
        'time' => '07:00',
        'script' => 'send_daily.php',
        'description' => 'Morning breakfast reminder'
    ],
    'menu' => [
        'time' => '11:00',
        'script' => 'send_daily_menu.php',
        'description' => 'Daily menu recommendation'
    ],
    'evening' => [
        'time' => '20:00',
        'script' => 'send_reminder_log.php',
        'description' => 'Evening food logging reminder'
    ],
    'weekly' => [
        'time' => 'Sunday 18:00',
        'script' => 'send_goal_evaluation.php',
        'description' => 'Weekly goal evaluation'
    ]
];

function isTimeToRun($schedule) {
    $currentTime = date('H:i');
    $currentDay = date('w'); // 0 = Sunday
    
    if ($schedule['script'] === 'send_goal_evaluation.php') {
        // Weekly: run on Sunday at 18:00
        return $currentDay === '0' && $currentTime === '18:00';
    }
    
    // Daily schedules: check if current time matches
    $scheduleTime = substr($schedule['time'], 0, 5);
    return $currentTime === $scheduleTime;
}

function executeScript($scriptPath) {
    if (!file_exists($scriptPath)) {
        return [
            'success' => false,
            'error' => "Script not found: $scriptPath"
        ];
    }
    
    ob_start();
    try {
        require_once $scriptPath;
        $output = ob_get_clean();
        return [
            'success' => true,
            'output' => $output,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        ob_end_clean();
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

function logScheduleExecution($db, $schedule, $result) {
    $stmt = $db->prepare(
        "INSERT INTO notification_schedules (schedule_type, last_run, status, message)
         VALUES (?, ?, ?, ?) 
         ON DUPLICATE KEY UPDATE last_run = ?, status = ?, message = ?"
    );
    
    $status = $result['success'] ? 'success' : 'failed';
    $message = $result['success'] ? 'Executed successfully' : ($result['error'] ?? 'Unknown error');
    $now = date('Y-m-d H:i:s');
    
    $stmt->execute([
        $schedule,
        $now,
        $status,
        $message,
        $now,
        $status,
        $message
    ]);
}

// Main execution
if ($command === 'all' || $command === 'check') {
    // Check all schedules and run if time matches
    echo "=== Notification Scheduler Check ===\n";
    echo "Current Time: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($schedules as $key => $schedule) {
        $shouldRun = isTimeToRun($schedule);
        $status = $shouldRun ? 'READY TO RUN' : 'NOT YET';
        
        echo "[$key] {$schedule['description']}\n";
        echo "  Scheduled: {$schedule['time']}\n";
        echo "  Script: {$schedule['script']}\n";
        echo "  Status: $status\n\n";
        
        if ($shouldRun) {
            $scriptPath = __DIR__ . '/' . $schedule['script'];
            $result = executeScript($scriptPath);
            logScheduleExecution($db, $key, $result);
            
            echo "  Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            if (!$result['success']) {
                echo "  Error: {$result['error']}\n";
            }
        }
    }
} elseif (isset($schedules[$command])) {
    // Run specific schedule immediately
    $schedule = $schedules[$command];
    echo "Running: {$schedule['description']}\n";
    echo "Script: {$schedule['script']}\n";
    echo "---\n";
    
    $scriptPath = __DIR__ . '/' . $schedule['script'];
    $result = executeScript($scriptPath);
    logScheduleExecution($db, $command, $result);
    
    echo $result['success'] ? 'SUCCESS' : 'FAILED';
    if (!$result['success']) {
        echo "\nError: {$result['error']}\n";
    } else {
        echo "\n\nScript executed at: {$result['timestamp']}\n";
    }
} else {
    // Show help
    echo "=== Notification Scheduler ===\n";
    echo "Usage: php schedule_notifications.php [command]\n\n";
    echo "Commands:\n";
    echo "  check      - Check all schedules and run if time matches\n";
    echo "  all        - Same as 'check'\n";
    foreach ($schedules as $key => $schedule) {
        echo "  $key        - Run {$schedule['description']}\n";
    }
    echo "\nExamples:\n";
    echo "  php schedule_notifications.php check\n";
    echo "  php schedule_notifications.php morning\n";
    echo "  php schedule_notifications.php menu\n";
}
