<?php
require_once __DIR__ . '/../classes/PageBootstrap.php';

$app = PageBootstrap::requireAdmin(__DIR__ . '/..');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/Schedule.php';
require_once __DIR__ . '/../classes/Food.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Admin/SchedulesController.php';

use Admin\SchedulesController;
$db = $app->db();
$controller = new SchedulesController($db);
$foods = $controller->getFoods();
$users = $controller->getUsers();
$message = $controller->getMessage();
$messageType = $controller->getMessageType();
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Catatan Makan Pengguna</h1>
                <p class="text-muted mb-0">Pantau dan kelola catatan makan seluruh pengguna</p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-3" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

<?php
$today = date('Y-m-d');
$seven_days_ago = date('Y-m-d', strtotime('-7 days'));
 $catatan = $db->query("
    SELECT s.*, u.name as user_name, f.name as food_name, f.calories, f.image_url
    FROM schedules s
    JOIN users u ON s.user_id = u.id
    JOIN foods f ON s.food_id = f.id
    WHERE s.schedule_date BETWEEN '" . $seven_days_ago . "' AND '" . $today . "'
    ORDER BY s.schedule_date DESC, s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

        <div class="card shadow-sm rounded-4 rms-card-adaptive">
            <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
                <span class="fw-semibold flex-shrink-0">Daftar Catatan Pengguna</span>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <span class="small text-muted" id="selectedCount" style="display:none;">0 dipilih</span>
                    <div class="form-check ms-1" id="selectAllCatatanWrapper" style="display:none;">
                        <input type="checkbox" class="form-check-input" id="selectAllCatatan">
                        <label class="form-check-label" for="selectAllCatatan">Pilih Semua</label>
                    </div>
                    <button type="submit" form="multiDeleteForm" class="btn btn-danger btn-sm" id="deleteSelectedBtn" style="margin-bottom:1px;display:none;" disabled>Hapus</button>
                    <button id="multiSelectBtn" class="btn btn-outline-primary btn-sm">Pilih Banyak</button>
                </div>
            </div>

            <form id="multiDeleteForm" method="post" action="../process/schedule.process.php">
                <input type="hidden" name="action" value="multi_delete">

                <div id="catatan-list" class="list-group list-group-flush" style="overflow-y:auto;overflow-x:hidden;padding:0.5rem 0.5rem 0 0.5rem;max-height:calc(100vh - 180px);">
                <?php if ($catatan && count($catatan)): ?>
                    <?php foreach ($catatan as $i => $j): ?>
                        <?php $imgUrl = (string)($j['image_url'] ?? ''); ?>
                        <div class="list-group-item d-flex align-items-center gap-3 catatan-item catatan-selectable" style="cursor:pointer;transition:background 0.15s;">
                            <input type="checkbox" class="form-check-input catatan-checkbox" name="delete_ids[]" value="<?= $j['id'] ?>" style="display:none;margin-right:12px;">
                            <div class="catatan-thumb flex-shrink-0">
                                <img
                                    src="<?= htmlspecialchars($imgUrl !== '' ? $imgUrl : 'about:blank') ?>"
                                    alt="img"
                                    class="rounded catatan-thumb-img"
                                    style="width:48px;height:48px;object-fit:cover;<?= $imgUrl ? '' : 'display:none;' ?>"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                    onerror="this.style.display='none'; var fb=this.closest('.catatan-thumb')?.querySelector('.catatan-thumb-fallback'); if (fb) fb.style.display='flex';"
                                >
                                <div class="rounded d-flex align-items-center justify-content-center bg-success-subtle text-success-emphasis catatan-thumb-fallback" style="width:48px;height:48px;<?= $imgUrl ? 'display:none;' : '' ?>">
                                    <i class="bi bi-egg-fried"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-1"><?= htmlspecialchars($j['food_name']) ?> <span class="badge bg-secondary ms-2">Pengguna: <?= htmlspecialchars($j['user_name']) ?></span></div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="small text-muted">Kalori: <?= round($j['calories']) ?> kcal</div>
                                    <div class="small text-muted">Tanggal: <?= htmlspecialchars($j['schedule_date']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center mt-4">Belum ada catatan makan.</div>
                <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</section>
<style>
#catatan-list .catatan-item { font-size:1em; padding:0.75rem 1rem; margin-bottom:0.25rem; border-radius:0.5rem; border:1px solid #e0e0e0; transition:box-shadow 0.15s; }
#catatan-list .catatan-item:hover { box-shadow:0 2px 8px rgba(40,167,69,0.08); border-color:#b2dfdb; }
#catatan-list-section { padding-right:0.5rem; }
.catatan-checkbox { accent-color: #28a745; width: 1.2em; height: 1.2em; cursor:pointer; }
.catatan-item.selected { background: #e9f7ef !important; border-left: 4px solid #28a745; }
.catatan-item:hover { background: #f1f8f6; }
</style>
<script>
const multiSelectBtn = document.getElementById('multiSelectBtn');
const catatanCheckboxes = document.querySelectorAll('.catatan-checkbox');
const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
const selectAllCatatan = document.getElementById('selectAllCatatan');
const selectAllCatatanWrapper = document.getElementById('selectAllCatatanWrapper');
const selectedCount = document.getElementById('selectedCount');
let multiSelectMode = false;

const multiDeleteForm = document.getElementById('multiDeleteForm');

function updateMultiUI() {
    const checked = document.querySelectorAll('.catatan-checkbox:checked').length;
    if (selectedCount) {
        selectedCount.textContent = checked + ' dipilih';
    }
    if (deleteSelectedBtn) {
        deleteSelectedBtn.disabled = checked === 0;
    }
    if (selectAllCatatan) {
        const total = catatanCheckboxes.length;
        selectAllCatatan.checked = total > 0 && checked === total;
        selectAllCatatan.indeterminate = checked > 0 && checked < total;
    }
}

function resetSelection() {
    catatanCheckboxes.forEach(cb => {
        cb.checked = false;
        const item = cb.closest('.catatan-item');
        if (item) item.classList.remove('selected');
    });
    if (selectAllCatatan) {
        selectAllCatatan.checked = false;
        selectAllCatatan.indeterminate = false;
    }
    updateMultiUI();
}

multiSelectBtn.addEventListener('click', function(e) {
    e.preventDefault();
    multiSelectMode = !multiSelectMode;
    document.querySelectorAll('.catatan-checkbox').forEach(cb => {
        cb.style.display = multiSelectMode ? '' : 'none';
    });
    document.querySelectorAll('.catatan-item').forEach(item => {
        item.classList.remove('selected');
    });
    if (deleteSelectedBtn) deleteSelectedBtn.style.display = multiSelectMode ? '' : 'none';
    if (selectAllCatatanWrapper) selectAllCatatanWrapper.style.display = multiSelectMode ? '' : 'none';
    if (selectedCount) selectedCount.style.display = multiSelectMode ? '' : 'none';
    multiSelectBtn.textContent = multiSelectMode ? 'Batal' : 'Pilih Banyak';
    resetSelection();
});

if (multiDeleteForm) {
    multiDeleteForm.addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.catatan-checkbox:checked').length;
        if (checked === 0) {
            e.preventDefault();
            return;
        }
        const ok = confirm('Yakin ingin menghapus ' + checked + ' catatan makan terpilih?');
        if (!ok) e.preventDefault();
    });
}

if (selectAllCatatan) {
    selectAllCatatan.addEventListener('change', function() {
        catatanCheckboxes.forEach(cb => {
            cb.checked = this.checked;
            const item = cb.closest('.catatan-item');
            if (item) item.classList.toggle('selected', cb.checked);
        });
        updateMultiUI();
    });
}

document.querySelectorAll('.catatan-item').forEach((item, idx) => {
    item.addEventListener('click', function(e) {
        if (!multiSelectMode) return;
        if (e.target.closest('button, a, input, label')) return;
        const cb = item.querySelector('.catatan-checkbox');
        cb.checked = !cb.checked;
        item.classList.toggle('selected', cb.checked);
        updateMultiUI();
    });
});

catatanCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        const item = cb.closest('.catatan-item');
        if (item) item.classList.toggle('selected', cb.checked);
        updateMultiUI();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>