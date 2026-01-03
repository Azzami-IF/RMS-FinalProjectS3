<?php
require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/User.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$app->requireUser();
if (($app->role() ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak');
}

$db = $app->db();
$user = new User($db);

$action = $_POST['action'] ?? '';

try {
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('ID pengguna tidak valid.');
        }

        $existing = $user->find($id);
        if (!$existing) {
            throw new Exception('Pengguna tidak ditemukan.');
        }

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

        // Ensure role is always present (User::update requires it)
        if (isset($_POST['role']) && ($app->role() ?? '') === 'admin') {
            $userData['role'] = $_POST['role'];
        } else {
            $userData['role'] = $existing['role'] ?? 'user';
        }

        $user->update($id, $userData);
        header('Location: ../admin/users.php?success=user_updated');

    } elseif ($action === 'delete') {
        // Prevent deleting the current admin user
        if ((int)$_POST['id'] === (int)$app->user()['id']) {
            header('Location: ../admin/users.php?error=cannot_delete_self');
            exit;
        }

        $user->delete((int)$_POST['id']);
        header('Location: ../admin/users.php?success=user_deleted');

    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ../admin/users.php?error=user_not_found');
            exit;
        }

        $existing = $user->find($id);
        if ($existing) {
            $user->update($id, [
                'name' => $existing['name'],
                'email' => $existing['email'],
                'phone' => $existing['phone'] ?? null,
                'date_of_birth' => $existing['date_of_birth'] ?? null,
                'gender' => $existing['gender'] ?? null,
                'height_cm' => $existing['height_cm'] ?? null,
                'weight_kg' => $existing['weight_kg'] ?? null,
                'activity_level' => $existing['activity_level'] ?? 'moderate',
                'daily_calorie_goal' => $existing['daily_calorie_goal'] ?? 2000,
                'role' => $existing['role'] ?? 'user',
                'is_active' => ($existing['is_active'] ?? 0) ? 0 : 1
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