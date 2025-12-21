
<?php
require_once __DIR__ . '/../classes/AppContext.php';
require_once __DIR__ . '/../classes/Food.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$app->requireUser();

if (($app->role() ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak');
}

$db = $app->db();
$userId = (int)$app->user()['id'];
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

                'created_by'    => $userId
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
