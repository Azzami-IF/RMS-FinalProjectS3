<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/UserGoal.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$userGoal = new UserGoal($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'save_goal':
            // Validate required fields
            $goalType = $_POST['goal_type'] ?? '';
            $dailyCalorieTarget = (int)($_POST['daily_calorie_target'] ?? 2000);

            if (empty($goalType)) {
                header('Location: ../goals.php?error=Tipe goal harus dipilih');
                exit;
            }

            if ($dailyCalorieTarget < 1000 || $dailyCalorieTarget > 5000) {
                header('Location: ../goals.php?error=Target kalori harus antara 1000-5000 kcal');
                exit;
            }

            // Prepare goal data
            $goalData = [
                'user_id' => $_SESSION['user']['id'],
                'goal_type' => $goalType,
                'daily_calorie_target' => $dailyCalorieTarget,
                'target_weight_kg' => !empty($_POST['target_weight_kg']) ? (float)$_POST['target_weight_kg'] : null,
                'target_date' => !empty($_POST['target_date']) ? $_POST['target_date'] : null,
                'weekly_weight_change' => !empty($_POST['weekly_weight_change']) ? (float)$_POST['weekly_weight_change'] : null,
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
            $stmt->execute([$_SESSION['user']['id']]);

            header('Location: ../goals.php?success=Goal berhasil dihapus');
            break;

        default:
            header('Location: ../goals.php?error=Aksi tidak valid');
            break;
    }
} catch (Exception $e) {
    header('Location: ../goals.php?error=' . urlencode($e->getMessage()));
}
exit;