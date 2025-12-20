
<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Food.php';
require_once __DIR__ . '/../includes/auth_guard.php';

// Admin only
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak');
}

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$food = new Food($db);

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $food->create([
                'category_id'   => $_POST['category_id'] ?? null,
                'name'          => $_POST['name'],
                'description'   => $_POST['description'] ?? null,
                'calories'      => (float)$_POST['calories'],
                'protein'       => (float)($_POST['protein'] ?? 0),
                'fat'           => (float)($_POST['fat'] ?? 0),
                'carbs'         => (float)($_POST['carbs'] ?? 0),
                'fiber'         => (float)($_POST['fiber'] ?? 0),
                'sugar'         => (float)($_POST['sugar'] ?? 0),
                'sodium'        => (float)($_POST['sodium'] ?? 0),

                'created_by'    => $_SESSION['user']['id']
            ]);
            header('Location: ../admin/foods.php?success=create');
            break;
        case 'update':
            if (empty($_POST['id'])) {
                throw new Exception('ID makanan tidak ditemukan.');
            }
            $food->update(
                (int)$_POST['id'],
                [
                    'category_id'   => $_POST['category_id'] ?? null,
                    'name'          => $_POST['name'],
                    'description'   => $_POST['description'] ?? null,
                    'calories'      => (float)$_POST['calories'],
                    'protein'       => (float)($_POST['protein'] ?? 0),
                    'fat'           => (float)($_POST['fat'] ?? 0),
                    'carbs'         => (float)($_POST['carbs'] ?? 0),
                    'fiber'         => (float)($_POST['fiber'] ?? 0),
                    'sugar'         => (float)($_POST['sugar'] ?? 0),
                    'sodium'        => (float)($_POST['sodium'] ?? 0),

                ]
            );
            header('Location: ../admin/foods.php?success=update');
            break;
        case 'delete':
            if (empty($_POST['id'])) {
                throw new Exception('ID makanan tidak ditemukan.');
            }
            $food->delete((int)$_POST['id']);
            header('Location: ../admin/foods.php?success=delete');
            break;
        default:
            header('Location: ../admin/foods.php?error=invalid_action');
    }
} catch (Exception $e) {
    header('Location: ../admin/foods.php?error=' . urlencode($e->getMessage()));
}
exit;
