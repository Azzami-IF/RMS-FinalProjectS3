<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Food.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$food = new Food($db);

$data = $food->all();
?>

<h4 class="mb-3">Data Makanan</h4>

<form method="post" action="../process/food.process.php" class="card p-3 mb-4">
    <input type="hidden" name="action" value="create">

    <div class="row g-2">
        <div class="col">
            <input name="name" class="form-control" placeholder="Nama" required>
        </div>
        <div class="col">
            <input name="calories" class="form-control" placeholder="Kalori" required>
        </div>
        <div class="col">
            <input name="protein" class="form-control" placeholder="Protein" required>
        </div>
        <div class="col">
            <input name="fat" class="form-control" placeholder="Lemak" required>
        </div>
        <div class="col">
            <input name="carbs" class="form-control" placeholder="Karbo" required>
        </div>
        <div class="col">
            <button class="btn btn-success">Tambah</button>
        </div>
    </div>
</form>

<table class="table table-bordered table-striped">
    <tr>
        <th>Nama</th>
        <th>Kalori</th>
        <th>Aksi</th>
    </tr>

    <?php foreach ($data as $d): ?>
        <tr>
            <td><?= htmlspecialchars($d['name']) ?></td>
            <td><?= $d['calories'] ?></td>
            <td>
                <a href="food_edit.php?id=<?= $d['id'] ?>"
                   class="btn btn-warning btn-sm">Edit</a>

                <form method="post"
                      action="../process/food.process.php"
                      style="display:inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <button class="btn btn-danger btn-sm"
                            onclick="return confirm('Hapus data?')">
                        Hapus
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
