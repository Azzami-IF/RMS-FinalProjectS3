<?php
require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/UserGoal.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$app->requireUser();

$db = $app->db();
$userId = (int)$app->user()['id'];
$userGoal = new UserGoal($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'save_goal':
            // Validate required fields
            $goalType = $_POST['goal_type'] ?? '';
            $dailyCalorieTarget = (int)($_POST['daily_calorie_target'] ?? 2000);

            if (empty($goalType)) {
                header('Location: ../goals.php?error=Tipe target harus dipilih');
                exit;
            }

            if ($dailyCalorieTarget < 1000 || $dailyCalorieTarget > 5000) {
                header('Location: ../goals.php?error=Target kalori harus antara 1000-5000 kcal');
                exit;
            }

            $targetDate = null;
            if (!empty($_POST['target_date'])) {
                $candidate = (string)$_POST['target_date'];
                $dt = DateTime::createFromFormat('Y-m-d', $candidate);
                $errs = DateTime::getLastErrors();
                if ($dt === false || ($errs['warning_count'] ?? 0) > 0 || ($errs['error_count'] ?? 0) > 0) {
                    header('Location: ../goals.php?error=' . urlencode('Format target tanggal tidak valid'));
                    exit;
                }
                $today = new DateTime('today');
                if ($dt < $today) {
                    header('Location: ../goals.php?error=' . urlencode('Target tanggal tidak boleh di masa lalu'));
                    exit;
                }
                $targetDate = $dt->format('Y-m-d');
            }

            $weeklyChange = null;
            if (!empty($_POST['weekly_weight_change'])) {
                $weeklyChange = abs((float)$_POST['weekly_weight_change']);
            }

            // Prepare goal data
            $goalData = [
                'user_id' => $userId,
                'goal_type' => $goalType,
                'daily_calorie_target' => $dailyCalorieTarget,
                'target_weight_kg' => !empty($_POST['target_weight_kg']) ? (float)$_POST['target_weight_kg'] : null,
                'target_date' => $targetDate,
                'weekly_weight_change' => $weeklyChange,
                'daily_protein_target' => !empty($_POST['daily_protein_target']) ? (float)$_POST['daily_protein_target'] : null,
                'daily_fat_target' => !empty($_POST['daily_fat_target']) ? (float)$_POST['daily_fat_target'] : null,
                'daily_carbs_target' => !empty($_POST['daily_carbs_target']) ? (float)$_POST['daily_carbs_target'] : null,
            ];

            $userGoal->create($goalData);
            header('Location: ../goals.php?success=1');
            break;

        case 'delete_goal':
            // Deactivate current goal
            $stmt = $db->prepare(
                "UPDATE user_goals SET is_active = FALSE
                 WHERE user_id = ? AND is_active = TRUE"
            );
            $stmt->execute([$userId]);

            header('Location: ../goals.php?success=Target berhasil dihapus');
            break;

        default:
            header('Location: ../goals.php?error=Aksi tidak valid');
            break;
    }
} catch (Throwable $e) {
    // Log error for shared hosting debugging
    $rootDir = realpath(__DIR__ . '/..');
    if ($rootDir !== false) {
        $logDir = $rootDir . '/cache';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $e->getMessage() . "\n";
        @error_log(rtrim($line));
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        if (is_dir($logDir)) {
            @file_put_contents($logDir . '/error.log', $line, FILE_APPEND);
        }
    }

    header('Location: ../goals.php?error=' . urlencode('Terjadi kesalahan saat menyimpan goal.'));
}
exit;