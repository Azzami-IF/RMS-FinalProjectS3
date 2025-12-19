<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Food.php';
require_once __DIR__ . '/../classes/Admin/FoodsController.php';

use Admin\FoodsController;

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$controller = new FoodsController($db);
$data = $controller->getData();
$message = $controller->getMessage();
$messageType = $controller->getMessageType();
?>

<h4 class="mb-3">Data Makanan</h4>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-3" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="post" action="../process/food.process.php" class="card p-3 mb-4">
    <input type="hidden" name="action" value="create">

    <div class="row g-2">
        <div class="col-md-3">
            <input name="name" class="form-control" placeholder="Nama" required>
        </div>
        <div class="col-md-2">
            <input name="calories" type="number" step="0.1" class="form-control" placeholder="Kalori" required>
        </div>
        <div class="col-md-2">
            <input name="protein" type="number" step="0.1" class="form-control" placeholder="Protein (g)" required>
        </div>
        <div class="col-md-2">
            <input name="fat" type="number" step="0.1" class="form-control" placeholder="Lemak (g)" required>
        </div>
        <div class="col-md-2">
            <input name="carbs" type="number" step="0.1" class="form-control" placeholder="Karbo (g)" required>
        </div>
        <div class="col-md-1">
            <button class="btn btn-success w-100">Tambah</button>
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
