<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Schedule.php';
require_once __DIR__ . '/../classes/Food.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Admin/SchedulesController.php';

use Admin\SchedulesController;

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$controller = new SchedulesController($db);
$foods = $controller->getFoods();
$users = $controller->getUsers();
$message = $controller->getMessage();
$messageType = $controller->getMessageType();
?>

<h4 class="mb-3">Kelola Jadwal Makan</h4>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-3" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="post" action="../process/schedule.process.php" class="card p-3 mb-4">
    <input type="hidden" name="action" value="create_admin">

    <div class="row g-2">
        <div class="col">
            <label>Pilih User</label>
            <select name="user_id" class="form-select" required>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= $u['name'] ?> (<?= $u['email'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col">
            <label>Pilih Makanan</label>
            <select name="food_id" class="form-select" required>
                <?php foreach ($foods as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= $f['name'] ?> (<?= $f['calories'] ?> kcal)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col">
            <label>Tanggal</label>
            <input type="date" name="schedule_date" class="form-control" required>
        </div>
        <div class="col">
            <label>&nbsp;</label>
            <button class="btn btn-success">Tambah Jadwal</button>
        </div>
    </div>
</form>

<table class="table table-bordered table-striped">
    <tr>
        <th>User</th>
        <th>Makanan</th>
        <th>Tanggal</th>
        <th>Aksi</th>
    </tr>

    <?php
    $stmt = $db->query("
        SELECT s.id, u.name as user_name, f.name as food_name, s.schedule_date
        FROM schedules s
        JOIN users u ON s.user_id = u.id
        JOIN foods f ON s.food_id = f.id
        ORDER BY s.schedule_date DESC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $d): ?>
    <tr>
        <td><?= $d['user_name'] ?></td>
        <td><?= $d['food_name'] ?></td>
        <td><?= $d['schedule_date'] ?></td>
        <td>
            <form method="post" action="../process/schedule.process.php" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <button class="btn btn-danger btn-sm">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>