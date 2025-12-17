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

    <div class="mb-2">
        <label>Nama</label>
        <input name="name" class="form-control"
               value="<?= htmlspecialchars($data['name']) ?>" required>
    </div>

    <div class="mb-2">
        <label>Kalori</label>
        <input name="calories" class="form-control"
               value="<?= $data['calories'] ?>" required>
    </div>

    <div class="mb-2">
        <label>Protein</label>
        <input name="protein" class="form-control"
               value="<?= $data['protein'] ?>" required>
    </div>

    <div class="mb-2">
        <label>Lemak</label>
        <input name="fat" class="form-control"
               value="<?= $data['fat'] ?>" required>
    </div>

    <div class="mb-3">
        <label>Karbo</label>
        <input name="carbs" class="form-control"
               value="<?= $data['carbs'] ?>" required>
    </div>

    <button class="btn btn-primary">Simpan</button>
    <a href="foods.php" class="btn btn-secondary">Kembali</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
