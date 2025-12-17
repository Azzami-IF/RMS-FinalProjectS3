<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak');
}

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$user = new User($db);

$action = $_POST['action'] ?? '';

try {
    if ($action === 'update') {
        $userData = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => !empty(trim($_POST['phone'] ?? '')) ? trim($_POST['phone']) : null,
            'date_of_birth' => !empty(trim($_POST['date_of_birth'] ?? '')) ? trim($_POST['date_of_birth']) : null,
            'gender' => !empty(trim($_POST['gender'] ?? '')) ? trim($_POST['gender']) : null,
            'height_cm' => !empty(trim($_POST['height_cm'] ?? '')) ? (float)trim($_POST['height_cm']) : null,
            'weight_kg' => !empty(trim($_POST['weight_kg'] ?? '')) ? (float)trim($_POST['weight_kg']) : null,
            'activity_level' => !empty(trim($_POST['activity_level'] ?? '')) ? trim($_POST['activity_level']) : 'moderate',
            'daily_calorie_goal' => !empty(trim($_POST['daily_calorie_goal'] ?? '')) ? (int)trim($_POST['daily_calorie_goal']) : 2000,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Don't allow changing role to admin unless current user is admin
        if (isset($_POST['role']) && $_SESSION['user']['role'] === 'admin') {
            $userData['role'] = $_POST['role'];
        }

        $user->update((int)$_POST['id'], $userData);
        header('Location: ../admin/users.php?success=user_updated');

    } elseif ($action === 'delete') {
        // Prevent deleting the current admin user
        if ((int)$_POST['id'] === $_SESSION['user']['id']) {
            header('Location: ../admin/users.php?error=cannot_delete_self');
            exit;
        }

        $user->delete((int)$_POST['id']);
        header('Location: ../admin/users.php?success=user_deleted');

    } elseif ($action === 'toggle_status') {
        $userData = $user->find((int)$_POST['id']);
        if ($userData) {
            $user->update((int)$_POST['id'], [
                'is_active' => $userData['is_active'] ? 0 : 1
            ]);
            header('Location: ../admin/users.php?success=status_updated');
        } else {
            header('Location: ../admin/users.php?error=user_not_found');
        }

    } else {
        header('Location: ../admin/users.php?error=invalid_action');
    }
} catch (Exception $e) {
    header('Location: ../admin/users.php?error=' . urlencode($e->getMessage()));
}
exit;