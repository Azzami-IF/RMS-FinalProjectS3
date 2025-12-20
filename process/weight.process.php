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
            $loggedAt = (string)($_POST['logged_at'] ?? date('Y-m-d'));

            if ($weight < 30 || $weight > 200) {
                header('Location: ../weight_log.php?error=' . urlencode('Berat badan harus antara 30–200 kg'));
                exit;
            }

            // Validate date
            $loggedAtTrim = trim($loggedAt);
            $dt = DateTime::createFromFormat('Y-m-d', $loggedAtTrim);
            $dtErrors = DateTime::getLastErrors();
            if ($loggedAtTrim === '' || $dt === false || ($dtErrors['warning_count'] ?? 0) > 0 || ($dtErrors['error_count'] ?? 0) > 0) {
                header('Location: ../weight_log.php?error=' . urlencode('Tanggal tidak valid'));
                exit;
            }
            $today = new DateTime('today');
            $maxFuture = (clone $today)->modify('+2 days');
            if ($dt > $maxFuture) {
                header('Location: ../weight_log.php?error=' . urlencode('Tanggal maksimal 2 hari ke depan'));
                exit;
            }

            // Prepare log data
            $logData = [
                'user_id' => $_SESSION['user']['id'],
                'weight_kg' => $weight,
                'body_fat_percentage' => !empty($_POST['body_fat_percentage']) ? (float)$_POST['body_fat_percentage'] : null,
                'muscle_mass_kg' => !empty($_POST['muscle_mass_kg']) ? (float)$_POST['muscle_mass_kg'] : null,
                'notes' => !empty(trim($_POST['notes'] ?? '')) ? trim($_POST['notes']) : null,
                'logged_at' => $dt->format('Y-m-d')
            ];

            $status = $weightLog->create($logData);
            header('Location: ../weight_log.php?success=' . urlencode($status));
            break;

        case 'delete_log':
            $logId = (int)($_POST['log_id'] ?? 0);

            if ($logId > 0) {
                $weightLog->delete($logId, $_SESSION['user']['id']);
                header('Location: ../weight_log.php?success=deleted');
            } else {
                header('Location: ../weight_log.php?error=' . urlencode('ID catatan tidak valid'));
            }
            break;

        default:
            header('Location: ../weight_log.php?error=' . urlencode('Aksi tidak valid'));
            break;
    }
} catch (Exception $e) {
    header('Location: ../weight_log.php?error=' . urlencode($e->getMessage()));
}
exit;