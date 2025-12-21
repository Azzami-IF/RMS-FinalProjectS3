<?php
require_once __DIR__ . '/classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__);
$GLOBALS['rms_app'] = $app;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Schedule.php';
require_once __DIR__ . '/classes/Cache.php';
require_once __DIR__ . '/classes/EdamamService.php';

$config = $app->config();
$db = $app->db();
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
$user_id = (int)($app->user()['id'] ?? 0);
$catatan = $user_id ? $schedule->getMealsByDateRange($user_id, $seven_days_ago, $today) : [];

// Backfill missing image_url for foods shown in the list (uses cache to avoid repeated API calls).
// Limit work per request to keep page snappy.
try {
    if ($catatan && $user_id) {
        $cache = new Cache();
        $edamamSvc = new EdamamService($config, $cache);
        $touched = [];
        $filled = 0;
        foreach ($catatan as &$j) {
            if ($filled >= 3) break;
            $foodId = (int)($j['food_id'] ?? 0);
            if ($foodId <= 0) continue;
            if (isset($touched[$foodId])) continue;
            $touched[$foodId] = true;

            $current = trim((string)($j['image_url'] ?? ''));
            if ($current !== '') continue;

            $q = trim((string)($j['food_name'] ?? ''));
            if ($q === '') continue;

            $data = $edamamSvc->searchRecipes($q, 5000);
            if (!empty($data['error'])) continue;
            $img = (string)($data['hits'][0]['recipe']['image'] ?? '');
            $img = trim($img);
            if ($img === '') continue;

            if (strlen($img) > 1024) $img = substr($img, 0, 1024);
            $stmt = $db->prepare("UPDATE foods SET image_url=? WHERE id=? AND (image_url IS NULL OR image_url='')");
            $stmt->execute([$img, $foodId]);
            $j['image_url'] = $img;
            $filled++;
        }
        unset($j);
    }
} catch (Throwable $e) {
    // Ignore image backfill errors and keep UI functional.
}
?>


<div class="schedules-page-wrap">
<h4 class="mb-4">Catatan Harian Saya</h4>
<div class="mb-3">
    <a href="recommendation.php" class="btn btn-success fw-bold w-100 mb-3" style="font-size:1.1em;">
        <i class="bi bi-search"></i> Cari Makanan
    </a>
</div>
<div class="card shadow-sm rounded-4 rms-card-adaptive schedules-card">
    <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
        <span class="fw-semibold flex-shrink-0">Riwayat</span>
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
    <form id="multiDeleteForm" method="post" action="process/schedule.process.php">
    <input type="hidden" name="action" value="multi_delete">
    <div id="catatan-list" class="list-group list-group-flush" style="overflow-y:auto;overflow-x:hidden;scrollbar-gutter:stable;padding:0.5rem 0.5rem 0 0.5rem;">
    <?php if ($catatan && count($catatan)): ?>
        <?php foreach ($catatan as $i => $j): ?>
            <?php
                $foodName = (string)($j['food_name'] ?? '');
                $detailHref = 'recommendation.php?q=' . urlencode($foodName) . '&focus_label=' . urlencode($foodName);
                $timeLabel = '';
                if (!empty($j['created_at'])) {
                    $ts = strtotime((string)$j['created_at']);
                    if ($ts !== false) $timeLabel = date('H:i', $ts);
                }
                $imgUrl = (string)($j['image_url'] ?? '');
            ?>
            <div class="list-group-item d-flex align-items-center gap-3 catatan-item catatan-selectable" data-href="<?= htmlspecialchars($detailHref) ?>" style="cursor:pointer;transition:background 0.15s;">
                <input type="checkbox" class="form-check-input catatan-checkbox" name="delete_ids[]" value="<?= $j['id'] ?>" style="display:none;margin-right:12px;">
                <div class="catatan-thumb flex-shrink-0">
                    <img
                        src="<?= htmlspecialchars($imgUrl !== '' ? $imgUrl : 'about:blank') ?>"
                        alt="img"
                        class="rounded catatan-thumb-img"
                        style="width:56px;height:56px;object-fit:cover;<?= $imgUrl ? '' : 'display:none;' ?>"
                        loading="lazy"
                        referrerpolicy="no-referrer"
                        onerror="this.style.display='none'; var fb=this.closest('.catatan-thumb')?.querySelector('.catatan-thumb-fallback'); if (fb) fb.style.display='flex';"
                    >
                    <div class="rounded d-flex align-items-center justify-content-center bg-success-subtle text-success-emphasis catatan-thumb-fallback" style="width:56px;height:56px;<?= $imgUrl ? 'display:none;' : '' ?>">
                        <i class="bi bi-egg-fried"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1"><?= $j['food_name'] ?></div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="small text-muted">Kalori: <?= round($j['calories']) ?> kcal</div>
                        <div class="small text-muted">Tanggal: <?= htmlspecialchars($j['schedule_date']) ?></div>
                        <?php if ($timeLabel !== ''): ?>
                            <div class="small text-muted">Jam: <?= htmlspecialchars($timeLabel) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">Belum ada catatan.</div>
    <?php endif; ?>
    </div>
    </form>
</div>

</div>
<style>
#catatan-list { flex: 1 1 auto; min-height: 0; }
.schedules-page-wrap { min-height: calc(100vh - 140px); display: flex; flex-direction: column; }
.schedules-card { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
#catatan-list .catatan-item { font-size:1em; padding:0.75rem 1rem; margin-bottom:0.25rem; border-radius:0.5rem; border:1px solid #e0e0e0; transition:box-shadow 0.15s; }
#catatan-list .catatan-item img { width:56px;height:56px; }
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
const catatanHapusForms = document.querySelectorAll('.catatan-hapus-form');
let multiSelectActive = false;

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

multiSelectBtn.addEventListener('click', function() {
    multiSelectActive = !multiSelectActive;
    catatanCheckboxes.forEach(cb => cb.style.display = multiSelectActive ? '' : 'none');
    if (deleteSelectedBtn) deleteSelectedBtn.style.display = multiSelectActive ? '' : 'none';
    if (selectAllCatatanWrapper) selectAllCatatanWrapper.style.display = multiSelectActive ? '' : 'none';
    if (selectedCount) selectedCount.style.display = multiSelectActive ? '' : 'none';
    multiSelectBtn.textContent = multiSelectActive ? 'Batal' : 'Pilih Banyak';
    catatanHapusForms.forEach(f => f.style.display = multiSelectActive ? 'none' : 'inline');
    if (multiSelectActive) {
        resetSelection();
    } else {
        resetSelection();
    }
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

catatanCheckboxes.forEach((cb, idx) => {
    const item = cb.closest('.catatan-selectable');
    item.addEventListener('click', function(e) {
        if (!multiSelectActive) return;
        // Hindari klik pada elemen interaktif (checkbox/label/tombol)
        if (e.target.closest('button, a, input, label')) return;
        cb.checked = !cb.checked;
        item.classList.toggle('selected', cb.checked);
        updateMultiUI();
    });
    cb.addEventListener('change', function() {
        item.classList.toggle('selected', cb.checked);
        updateMultiUI();
    });
});

// Normal mode: click item opens recommendation detail
document.querySelectorAll('.catatan-item[data-href]').forEach((item) => {
    item.addEventListener('click', function(e) {
        if (multiSelectActive) return;
        if (e.target.closest('button, a, input, label')) return;
        const href = this.getAttribute('data-href');
        if (href) window.location.href = href;
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
