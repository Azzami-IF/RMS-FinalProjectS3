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
    $msg = ($_GET['success'] === 'schedule_created') ? 'Catatan makan berhasil disimpan.' :
    (($_GET['success'] === 'schedule_updated') ? 'Catatan makan berhasil diubah.' :
    (($_GET['success'] === 'schedule_deleted') ? 'Catatan makan terpilih berhasil dihapus.' : htmlspecialchars($_GET['success'])));
  echo '<div class="alert alert-success rms-card-adaptive mb-3">' . $msg . '</div>';
}

$today = date('Y-m-d');
$seven_days_ago = date('Y-m-d', strtotime('-7 days'));
$user_id = $_SESSION['user']['id'] ?? null;
$catatan = $user_id ? $schedule->getMealsByDateRange($user_id, $seven_days_ago, $today) : [];
?>


<h4 class="mb-4">Catatan Harian Saya</h4>
<div class="mb-3">
    <a href="recommendation.php" class="btn btn-success fw-bold w-100 mb-3" style="font-size:1.1em;">
        <i class="bi bi-search"></i> Cari Makanan
    </a>
</div>
<div class="card shadow-sm rounded-4 rms-card-adaptive">
    <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
        <span class="fw-semibold flex-shrink-0">Riwayat</span>
        <button id="multiSelectBtn" class="btn btn-outline-primary btn-sm ms-auto">Pilih Banyak</button>
    </div>
    <form id="multiDeleteForm" method="post" action="process/schedule.process.php">
    <div id="catatan-list" class="list-group list-group-flush" style="overflow-y:auto;overflow-x:hidden;padding:0.5rem 0.5rem 0 0.5rem;max-height:calc(100vh - 180px);">
    <?php if ($catatan && count($catatan)): ?>
        <?php foreach ($catatan as $i => $j): ?>
            <div class="list-group-item d-flex align-items-center gap-3 catatan-item catatan-selectable" style="cursor:pointer;transition:background 0.15s;">
                <input type="checkbox" class="form-check-input catatan-checkbox" name="delete_ids[]" value="<?= $j['id'] ?>" style="display:none;margin-right:12px;">
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1"><?= $j['food_name'] ?></div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="small text-muted">Kalori: <?= round($j['calories']) ?> kcal</div>
                        <div class="small text-muted">Tanggal: <?= htmlspecialchars($j['schedule_date']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div id="multiDeleteActions" class="mt-2 mb-3" style="display:none;">
            <input type="hidden" name="action" value="multi_delete">
            <button type="submit" class="btn btn-danger" style="margin-bottom:1px;">Hapus Terpilih</button>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">Belum ada catatan.</div>
    <?php endif; ?>
    </div>
    </form>
</div>
<footer class="footer mt-auto py-3 border-top custom-footer rms-card-adaptive" style="bottom:0;width:100%;z-index:10;">
    <div class="container text-center small text-muted">
        &copy; <?= date('Y') ?> RMS - Catatan Makan
    </div>
</footer>
<style>
#catatan-list .catatan-item { font-size:1em; padding:0.75rem 1rem; margin-bottom:0.25rem; border-radius:0.5rem; border:1px solid #e0e0e0; transition:box-shadow 0.15s; }
#catatan-list .catatan-item:hover { box-shadow:0 2px 8px rgba(40,167,69,0.08); border-color:#b2dfdb; }
#catatan-list-section { padding-right:0.5rem; }
.custom-footer { margin-top: 28px !important; }
.catatan-checkbox { accent-color: #28a745; width: 1.2em; height: 1.2em; cursor:pointer; }
.catatan-item.selected { background: #e9f7ef !important; border-left: 4px solid #28a745; }
.catatan-item:hover { background: #f1f8f6; }
</style>
<script>
const multiSelectBtn = document.getElementById('multiSelectBtn');
const catatanCheckboxes = document.querySelectorAll('.catatan-checkbox');
const multiDeleteActions = document.getElementById('multiDeleteActions');
const catatanHapusForms = document.querySelectorAll('.catatan-hapus-form');
let multiSelectActive = false;
multiSelectBtn.addEventListener('click', function() {
    multiSelectActive = !multiSelectActive;
    catatanCheckboxes.forEach(cb => cb.style.display = multiSelectActive ? '' : 'none');
    multiDeleteActions.style.display = multiSelectActive ? '' : 'none';
    multiSelectBtn.textContent = multiSelectActive ? 'Batal Pilih Banyak' : 'Pilih Banyak';
    catatanHapusForms.forEach(f => f.style.display = multiSelectActive ? 'none' : 'inline');
});
catatanCheckboxes.forEach((cb, idx) => {
    const item = cb.closest('.catatan-selectable');
    item.addEventListener('click', function(e) {
        if (!multiSelectActive) return;
        // Hindari klik pada tombol hapus
        if (e.target.tagName === 'BUTTON') return;
        cb.checked = !cb.checked;
        item.classList.toggle('selected', cb.checked);
    });
    cb.addEventListener('change', function() {
        item.classList.toggle('selected', cb.checked);
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
