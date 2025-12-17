<?php
session_start();

require_once '../config/database.php';
require_once '../classes/Schedule.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$db = (new Database(require '../config/env.php'))->getConnection();
$schedule = new Schedule($db);

$action = $_POST['action'] ?? 'create';

try {
    if ($action === 'create') {
        $schedule->create(
            $_SESSION['user']['id'],
            $_POST['food_id'],
            $_POST['schedule_date'],
            $_POST['meal_type_id'] ?? null,
            $_POST['quantity'] ?? 1,
            $_POST['notes'] ?? null
        );
        header('Location: ../dashboard.php?success=schedule_created');
    } elseif ($action === 'create_admin') {
        if ($_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            exit('Akses ditolak');
        }
        $schedule->create(
            $_POST['user_id'],
            $_POST['food_id'],
            $_POST['schedule_date'],
            $_POST['meal_type_id'] ?? null,
            $_POST['quantity'] ?? 1,
            $_POST['notes'] ?? null
        );
        header('Location: ../admin/schedules.php?success=schedule_created');
    } elseif ($action === 'delete') {
        if ($_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            exit('Akses ditolak');
        }
        $stmt = $db->prepare("DELETE FROM schedules WHERE id=?");
        $stmt->execute([$_POST['id']]);
        header('Location: ../admin/schedules.php?success=schedule_deleted');
    } else {
        header('Location: ../dashboard.php?error=invalid_action');
    }
} catch (Exception $e) {
    $redirect_url = ($_SESSION['user']['role'] === 'admin') ? '../admin/schedules.php' : '../dashboard.php';
    header('Location: ' . $redirect_url . '?error=' . urlencode($e->getMessage()));
}
exit;
