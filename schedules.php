<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Schedule.php';

$db = (new Database(require 'config/env.php'))->getConnection();
$schedule = new Schedule($db);

// Feedback
if (isset($_GET['error'])) {
  echo '<div class="alert alert-danger rms-card-adaptive mb-3">' . htmlspecialchars($_GET['error']) . '</div>';
}
if (isset($_GET['success'])) {
  $msg = ($_GET['success'] === 'schedule_created') ? 'Jadwal makan berhasil disimpan.' :
    (($_GET['success'] === 'schedule_updated') ? 'Jadwal makan berhasil diubah.' : htmlspecialchars($_GET['success']));
  echo '<div class="alert alert-success rms-card-adaptive mb-3">' . $msg . '</div>';
}

$today = date('Y-m-d');
$seven_days_ago = date('Y-m-d', strtotime('-7 days'));
$user_id = $_SESSION['user']['id'] ?? null;
$jadwal = $user_id ? $schedule->getMealsByDateRange($user_id, $seven_days_ago, $today) : [];
?>


<h4 class="mb-4">Jadwal Makan Saya</h4>
<div class="mb-3">
    <a href="recommendation.php" class="btn btn-success fw-bold w-100 mb-3" style="font-size:1.1em;">
        <i class="bi bi-search"></i> Cari Makanan
    </a>
</div>
<div class="card shadow-sm rounded-4" style="background:#f8f9fa;">
    <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
        <span class="fw-semibold flex-shrink-0">List Jadwal</span>
    </div>
    <div id="jadwal-list" class="list-group list-group-flush" style="overflow-y:auto;overflow-x:hidden;padding:0.5rem 0.5rem 0 0.5rem;max-height:calc(100vh - 180px);">
    <?php if ($jadwal && count($jadwal)): ?>
        <?php foreach ($jadwal as $i => $j): ?>
            <div class="list-group-item d-flex align-items-center gap-3 jadwal-item">
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1"><?= $j['food_name'] ?></div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="small text-muted">Kalori: <?= round($j['calories']) ?> kcal</div>
                        <div class="small text-muted">Tanggal: <?= htmlspecialchars($j['schedule_date']) ?></div>
                    </div>
                </div>
                <form action="process/schedule.process.php" method="post" style="display:inline;" onsubmit="return confirm('Hapus jadwal ini?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">Belum ada jadwal makan.</div>
    <?php endif; ?>
    </div>
</div>
<footer class="footer mt-auto py-3 bg-light border-top custom-footer" style="bottom:0;width:100%;z-index:10;">
    <div class="container text-center small text-muted">
        &copy; <?= date('Y') ?> RMS - Jadwal Makan
    </div>
</footer>
<style>
#jadwal-list .jadwal-item { font-size:1em; padding:0.75rem 1rem; margin-bottom:0.25rem; border-radius:0.5rem; border:1px solid #e0e0e0; transition:box-shadow 0.15s; }
#jadwal-list .jadwal-item:hover { box-shadow:0 2px 8px rgba(40,167,69,0.08); border-color:#b2dfdb; }
#jadwal-list-section { padding-right:0.5rem; }
.custom-footer { margin-top: 28px !important; }
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
