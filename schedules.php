<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Schedule.php';
require_once __DIR__ . '/classes/Food.php';

$db = (new Database(require 'config/env.php'))->getConnection();
$schedule = new Schedule($db);
$food = new Food($db);

$foods = $food->all();
?>

<h4 class="mb-3">Atur Jadwal Makan</h4>

<form action="process/schedule.process.php" method="post" class="card p-3 shadow-sm">
    <div class="mb-3">
        <label class="form-label">Pilih Makanan</label>
        <select name="food_id" class="form-select" required>
            <?php foreach ($foods as $f): ?>
                <option value="<?= $f['id'] ?>">
                    <?= $f['name'] ?> (<?= $f['calories'] ?> kcal)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Tanggal</label>
        <input type="date" name="schedule_date" class="form-control" required>
    </div>

    <button class="btn btn-success">Simpan Jadwal</button>
</form>
