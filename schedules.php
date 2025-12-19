<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Schedule.php';
require_once __DIR__ . '/classes/Food.php';

$db = (new Database(require 'config/env.php'))->getConnection();
$schedule = new Schedule($db);
$food = new Food($db);
$foods = $food->all();

// Feedback
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger rms-card-adaptive mb-3">' . htmlspecialchars($_GET['error']) . '</div>';
}
if (isset($_GET['success'])) {
    $msg = ($_GET['success'] === 'schedule_created') ? 'Jadwal makan berhasil disimpan.' :
        (($_GET['success'] === 'schedule_updated') ? 'Jadwal makan berhasil diubah.' : htmlspecialchars($_GET['success']));
    echo '<div class="alert alert-success rms-card-adaptive mb-3">' . $msg . '</div>';
}
?>

<div class="rms-card-adaptive p-4 mb-4" style="max-width:480px;margin:auto;">
    <h4 class="mb-3 rms-muted-adaptive text-center">Atur Jadwal Makan</h4>
    <form action="process/schedule.process.php" method="post">
        <div class="mb-3">
            <label class="form-label rms-muted-adaptive">Pilih Makanan</label>
            <select name="food_id" class="form-select rms-input-adaptive" required>
                <?php foreach ($foods as $f): ?>
                    <option value="<?= $f['id'] ?>">
                        <?= $f['name'] ?> (<?= $f['calories'] ?> kcal)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label rms-muted-adaptive">Tanggal</label>
            <input type="date" name="schedule_date" class="form-control rms-input-adaptive" required>
        </div>
        <button class="btn btn-success rms-btn-adaptive w-100">Simpan Jadwal</button>
    </form>
</div>

<?php
$today = date('Y-m-d');
$seven_days_ago = date('Y-m-d', strtotime('-7 days'));
$user_id = $_SESSION['user']['id'] ?? null;
$jadwal = $user_id ? $schedule->getMealsByDateRange($user_id, $seven_days_ago, $today) : [];
?>

<div class="rms-card-adaptive p-4 mb-4" style="max-width:700px;margin:auto;">
    <h5 class="mb-3 rms-muted-adaptive text-center">Jadwal Makan 7 Hari Terakhir</h5>
    <?php if (empty($jadwal)): ?>
        <div class="text-center text-muted">Belum ada jadwal makan.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle rms-card-adaptive">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Makanan</th>
                        <th>Kalori</th>
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($jadwal as $j): ?>
                    <tr>
                        <td><?= htmlspecialchars($j['schedule_date']) ?></td>
                        <td><?= htmlspecialchars($j['food_name']) ?></td>
                        <td><?= htmlspecialchars($j['calories']) ?> kcal</td>
                        <td><?= htmlspecialchars($j['notes'] ?? '-') ?></td>
                        <td>
                            <form action="process/schedule.process.php" method="post" style="display:inline;" onsubmit="return confirm('Hapus jadwal ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-primary ms-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $j['id'] ?>">Edit</button>
                            <!-- Modal Edit Jadwal -->
                            <div class="modal fade" id="editModal<?= $j['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $j['id'] ?>" aria-hidden="true">
                              <div class="modal-dialog">
                                <div class="modal-content rms-card-adaptive">
                                  <form action="process/schedule.process.php" method="post">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                    <div class="modal-header">
                                      <h5 class="modal-title" id="editModalLabel<?= $j['id'] ?>">Edit Jadwal</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                      <div class="mb-3">
                                        <label class="form-label rms-muted-adaptive">Tanggal</label>
                                        <input type="date" name="schedule_date" class="form-control rms-input-adaptive" value="<?= htmlspecialchars($j['schedule_date']) ?>" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label rms-muted-adaptive">Makanan</label>
                                        <select name="food_id" class="form-select rms-input-adaptive" required>
                                          <?php foreach ($foods as $f): ?>
                                            <option value="<?= $f['id'] ?>" <?= $f['id'] == $j['food_id'] ? 'selected' : '' ?>>
                                              <?= $f['name'] ?> (<?= $f['calories'] ?> kcal)
                                            </option>
                                          <?php endforeach; ?>
                                        </select>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label rms-muted-adaptive">Catatan</label>
                                        <input type="text" name="notes" class="form-control rms-input-adaptive" value="<?= htmlspecialchars($j['notes'] ?? '') ?>">
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                      <button type="submit" class="btn btn-success">Simpan</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
