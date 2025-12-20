<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Food.php';
require_once __DIR__ . '/../classes/FoodCategory.php';
require_once __DIR__ . '/../classes/Admin/FoodEditController.php';

use Admin\FoodEditController;

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

$isEdit = (bool)$id;
$data = [
    'id' => null,
    'category_id' => null,
    'name' => '',
    'description' => '',
    'calories' => '',
    'protein' => '',
    'fat' => '',
    'carbs' => '',
    'fiber' => '',
    'sugar' => '',
    'sodium' => '',
];

if ($isEdit) {
    $controller = new FoodEditController($db, $id);
    $found = $controller->getData();
    if (!$found) {
        echo '<section class="py-5"><div class="container">'
            . '<div class="alert alert-danger mb-0">Data makanan tidak ditemukan.</div>'
            . '</div></section>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
    $data = array_merge($data, $found);
}

$categoryModel = new FoodCategory($db);
$categories = $categoryModel->all();
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1"><?= $isEdit ? 'Ubah Makanan' : 'Tambah Makanan' ?></h1>
                <p class="text-muted mb-0"><?= $isEdit ? 'Perbarui data makanan' : 'Tambahkan data makanan baru' ?></p>
            </div>
        </div>

        <form method="post" action="../process/food.process.php" class="card p-3 shadow-sm rounded-3">
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$data['id'] ?>">
            <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Kategori</label>
            <select name="category_id" class="form-select">
                <option value="">(Tanpa kategori)</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= ((string)($data['category_id'] ?? '') === (string)$cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Nama Makanan</label>
            <input name="name" class="form-control"
                   value="<?= htmlspecialchars((string)($data['name'] ?? '')) ?>" required>
        </div>

        <div class="col-12">
            <label class="form-label">Deskripsi (opsional)</label>
            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars((string)($data['description'] ?? '')) ?></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">Kalori (per 100g)</label>
            <input name="calories" type="number" step="0.1" class="form-control"
                   value="<?= htmlspecialchars((string)($data['calories'] ?? '')) ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Protein (g)</label>
            <input name="protein" type="number" step="0.1" class="form-control"
                   value="<?= htmlspecialchars((string)($data['protein'] ?? '')) ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Lemak (g)</label>
            <input name="fat" type="number" step="0.1" class="form-control"
                   value="<?= htmlspecialchars((string)($data['fat'] ?? '')) ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Karbohidrat (g)</label>
            <input name="carbs" type="number" step="0.1" class="form-control"
                   value="<?= htmlspecialchars((string)($data['carbs'] ?? '')) ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Serat (g)</label>
            <input name="fiber" type="number" step="0.1" class="form-control"
                   value="<?= htmlspecialchars((string)($data['fiber'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Gula (g)</label>
            <input name="sugar" type="number" step="0.1" class="form-control"
                   value="<?= htmlspecialchars((string)($data['sugar'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Sodium (mg)</label>
            <input name="sodium" type="number" step="0.1" class="form-control"
                   value="<?= htmlspecialchars((string)($data['sodium'] ?? '')) ?>">
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary me-2"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Makanan' ?></button>
            <a href="foods.php" class="btn btn-secondary">Batal</a>
        </div>
    </div>

        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
