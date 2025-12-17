<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/WeightLog.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$weightLog = new WeightLog($db);

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_log':
            // Validate required fields
            $weight = (float)($_POST['weight_kg'] ?? 0);
            $loggedAt = $_POST['logged_at'] ?? date('Y-m-d');

            if ($weight < 30 || $weight > 200) {
                header('Location: ../weight_log.php?error=Berat badan harus antara 30-200 kg');
                exit;
            }

            // Prepare log data
            $logData = [
                'user_id' => $_SESSION['user']['id'],
                'weight_kg' => $weight,
                'body_fat_percentage' => !empty($_POST['body_fat_percentage']) ? (float)$_POST['body_fat_percentage'] : null,
                'muscle_mass_kg' => !empty($_POST['muscle_mass_kg']) ? (float)$_POST['muscle_mass_kg'] : null,
                'notes' => !empty(trim($_POST['notes'] ?? '')) ? trim($_POST['notes']) : null,
                'logged_at' => $loggedAt
            ];

            $weightLog->create($logData);
            header('Location: ../weight_log.php?success=1');
            break;

        case 'delete_log':
            $logId = (int)($_POST['log_id'] ?? 0);

            if ($logId > 0) {
                $weightLog->delete($logId, $_SESSION['user']['id']);
                header('Location: ../weight_log.php?success=Log berhasil dihapus');
            } else {
                header('Location: ../weight_log.php?error=ID log tidak valid');
            }
            break;

        default:
            header('Location: ../weight_log.php?error=Aksi tidak valid');
            break;
    }
} catch (Exception $e) {
    header('Location: ../weight_log.php?error=' . urlencode($e->getMessage()));
}
exit;