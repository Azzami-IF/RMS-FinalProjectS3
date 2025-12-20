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


<h4 class="mb-4">Catatan Makan Pengguna</h4>
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
    SELECT s.*, u.name as user_name, f.name as food_name, f.calories
    FROM schedules s
    JOIN users u ON s.user_id = u.id
    JOIN foods f ON s.food_id = f.id
    WHERE s.schedule_date BETWEEN '" . $seven_days_ago . "' AND '" . $today . "'
    ORDER BY s.schedule_date DESC, s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow-sm rounded-4" style="background:#f8f9fa;">
    <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
        <span class="fw-semibold flex-shrink-0">List Catatan Pengguna</span>
        <button id="multiSelectBtn" class="btn btn-outline-primary btn-sm ms-auto">Pilih Banyak</button>
    </div>
    <form id="multiDeleteForm" method="post" action="../process/schedule.process.php">
    <div id="catatan-list" class="list-group list-group-flush" style="overflow-y:auto;overflow-x:hidden;padding:0.5rem 0.5rem 0 0.5rem;max-height:calc(100vh - 180px);">
    <?php if ($catatan && count($catatan)): ?>
        <?php foreach ($catatan as $i => $j): ?>
            <div class="list-group-item d-flex align-items-center gap-3 catatan-item catatan-selectable" style="cursor:pointer;transition:background 0.15s;">
                <input type="checkbox" class="form-check-input catatan-checkbox" name="delete_ids[]" value="<?= $j['id'] ?>" style="display:none;margin-right:12px;">
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1"><?= htmlspecialchars($j['food_name']) ?> <span class="badge bg-secondary ms-2">User: <?= htmlspecialchars($j['user_name']) ?></span></div>
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
        <div class="alert alert-info text-center mt-4">Belum ada catatan makan.</div>
    <?php endif; ?>
    </div>
    </form>
</div>
<footer class="footer mt-auto py-3 border-top custom-footer rms-card-adaptive" style="bottom:0;width:100%;z-index:10;">
    <div class="container text-center small text-muted">
        &copy; <?= date('Y') ?> RMS - Catatan Makan Admin
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
let multiSelectMode = false;
multiSelectBtn.addEventListener('click', function(e) {
    e.preventDefault();
    multiSelectMode = !multiSelectMode;
    document.querySelectorAll('.catatan-checkbox').forEach(cb => {
        cb.style.display = multiSelectMode ? '' : 'none';
        cb.checked = false;
    });
    document.querySelectorAll('.catatan-item').forEach(item => {
        item.classList.remove('selected');
    });
    multiDeleteActions.style.display = 'none';
});
document.querySelectorAll('.catatan-item').forEach((item, idx) => {
    item.addEventListener('click', function(e) {
        if (!multiSelectMode) return;
        const cb = item.querySelector('.catatan-checkbox');
        cb.checked = !cb.checked;
        item.classList.toggle('selected', cb.checked);
        const anyChecked = document.querySelectorAll('.catatan-checkbox:checked').length > 0;
        multiDeleteActions.style.display = anyChecked ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>