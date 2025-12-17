<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Food.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$food = new Food($db);

$data = $food->find((int)$_GET['id']);
if (!$data) {
    exit('Data tidak ditemukan');
}
?>

<h4>Edit Makanan</h4>

<form method="post" action="../process/food.process.php" class="card p-3">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama Makanan</label>
            <input name="name" class="form-control"
                   value="<?= htmlspecialchars($data['name']) ?>" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Kalori (per 100g)</label>
            <input name="calories" type="number" step="0.1" class="form-control"
                   value="<?= $data['calories'] ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Protein (g)</label>
            <input name="protein" type="number" step="0.1" class="form-control"
                   value="<?= $data['protein'] ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Lemak (g)</label>
            <input name="fat" type="number" step="0.1" class="form-control"
                   value="<?= $data['fat'] ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Karbohidrat (g)</label>
            <input name="carbs" type="number" step="0.1" class="form-control"
                   value="<?= $data['carbs'] ?>" required>
        </div>

        <div class="col-12">
            <button class="btn btn-primary me-2">Simpan Perubahan</button>
            <a href="foods.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
