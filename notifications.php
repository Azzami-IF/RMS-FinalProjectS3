<?php
// Handle delete notification POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    require_once __DIR__ . '/config/database.php';
    $config = require __DIR__ . '/config/env.php';
    $db = (new Database($config))->getConnection();
    session_start();
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['delete_id'], $_SESSION['user']['id']]);
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        exit;
    } else {
        header('Location: notifications.php');
        exit;
    }
}
// Handle multi-delete notification POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'multi_delete' && isset($_POST['delete_ids'])) {
    require_once __DIR__ . '/config/database.php';
    $config = require __DIR__ . '/config/env.php';
    $db = (new Database($config))->getConnection();
    session_start();
    $ids = $_POST['delete_ids'];
    if (is_array($ids) && count($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$_SESSION['user']['id']]);
        $stmt = $db->prepare("DELETE FROM notifications WHERE id IN ($in) AND user_id = ?");
        $stmt->execute($params);
    }
    header('Location: notifications.php');
    exit;
}
?>
<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

// AJAX handler: output only notif-list div
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $stmt = $db->prepare(
        "SELECT * FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->execute([$_SESSION['user']['id']]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div id="notif-list">
    <?php if (empty($data)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted">
                <i class="bi bi-bell-slash fs-1 mb-3"></i>
                <p>Belum ada notifikasi</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($data as $n): ?>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm rms-card-adaptive">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="card-title mb-1">
                                        <?php if (stripos($n['title'], 'Menu Sehat') !== false): ?>
                                            <i class="bi bi-egg-fried text-success me-1"></i>
                                        <?php elseif (stripos($n['title'], 'Pengingat') !== false): ?>
                                            <i class="bi bi-bell text-warning me-1"></i>
                                        <?php else: ?>
                                            <i class="bi bi-info-circle text-info me-1"></i>
                                        <?php endif; ?>
                                        <?= $n['title'] ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?= date('d M Y H:i', strtotime($n['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <?php
                                // Extract food image and calories if present in message
                                $img = '';
                                $cal = '';
                                $tip = '';
                                if (preg_match('/https?:\\/\\/[^\s]+(jpg|png)/i', $n['message'], $m)) {
                                    $img = $m[0];
                                }
                                if (preg_match('/(\d+) kcal/', $n['message'], $m)) {
                                    $cal = $m[0];
                                }
                                if (preg_match('/Tips: (.*)/', $n['message'], $m)) {
                                    $tip = $m[1];
                                }
                                ?>
                                <?php if ($img): ?>
                                    <img src="<?= $img ?>" alt="menu" class="rounded mb-2" style="width:60px;height:60px;object-fit:cover;">
                                <?php endif; ?>
                                <div class="text-muted small mb-1">
                                    <?= nl2br(htmlspecialchars($n['message'])) ?>
                                </div>
                                <?php if ($cal): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis me-1">Kalori: <?= $cal ?></span>
                                <?php endif; ?>
                                <?php if ($tip): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis">Tips: <?= htmlspecialchars($tip) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </div>
    <?php
    exit;
}

// Normal page output
$stmt = $db->prepare(
    "SELECT * FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
$stmt->execute([$_SESSION['user']['id']]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h4 class="mb-4">Riwayat Notifikasi</h4>
<div class="card shadow-sm rounded-4 rms-card-adaptive">
    <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
        <div class="d-flex align-items-center gap-2" id="multiDeleteNotifControls" style="display:none;">
            <button id="multiDeleteNotifBtn" class="btn btn-danger btn-sm" style="margin-bottom:1px;display:none;">Hapus Terpilih</button>
            <div class="form-check ms-2" style="display:none;" id="selectAllNotifWrapper">
                <input type="checkbox" class="form-check-input" id="selectAllNotif">
                <label class="form-check-label" for="selectAllNotif">Select All</label>
            </div>
        </div>
        <button id="multiSelectNotifBtn" class="btn btn-outline-primary btn-sm ms-auto">Pilih Banyak</button>
    </div>
    <div id="notif-list" class="list-group list-group-flush" style="overflow-y:auto;overflow-x:hidden;padding:0.5rem 0.5rem 0 0.5rem;max-height:calc(100vh - 180px);">
    <?php if ($data && count($data)): ?>
        <?php foreach ($data as $n): ?>
            <div class="list-group-item d-flex align-items-center gap-3 notif-item notif-selectable" style="cursor:pointer;transition:background 0.15s;">
                <input type="checkbox" class="form-check-input notif-checkbox" name="delete_ids[]" value="<?= $n['id'] ?>" style="display:none;margin-right:12px;">
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">
                        <?php if (stripos($n['title'], 'Menu Sehat') !== false): ?>
                            <i class="bi bi-egg-fried text-success me-1"></i>
                        <?php elseif (stripos($n['title'], 'Pengingat') !== false): ?>
                            <i class="bi bi-bell text-warning me-1"></i>
                        <?php else: ?>
                            <i class="bi bi-info-circle text-info me-1"></i>
                        <?php endif; ?>
                        <?= $n['title'] ?>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="small text-muted">Waktu: <?= date('d M Y H:i', strtotime($n['created_at'])) ?></div>
                    </div>
                    <div class="mt-2">
                        <?php
                        $img = '';
                        $cal = '';
                        $tip = '';
                        if (preg_match('/https?:\\/\\/[^\s]+(jpg|png)/i', $n['message'], $m)) {
                            $img = $m[0];
                        }
                        if (preg_match('/(\d+) kcal/', $n['message'], $m)) {
                            $cal = $m[0];
                        }
                        if (preg_match('/Tips: (.*)/', $n['message'], $m)) {
                            $tip = $m[1];
                        }
                        ?>
                        <?php if ($img): ?>
                            <img src="<?= $img ?>" alt="menu" class="rounded mb-2" style="width:60px;height:60px;object-fit:cover;">
                        <?php endif; ?>
                        <div class="text-muted small mb-1">
                            <?= nl2br(htmlspecialchars($n['message'])) ?>
                        </div>
                        <?php if ($cal): ?>
                            <span class="badge bg-success-subtle text-success-emphasis me-1">Kalori: <?= $cal ?></span>
                        <?php endif; ?>
                        <?php if ($tip): ?>
                            <span class="badge bg-info-subtle text-info-emphasis">Tips: <?= htmlspecialchars($tip) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">Belum ada notifikasi.</div>
    <?php endif; ?>
    </div>
</div>
<form id="multiDeleteNotifForm" method="post" action="notifications.php" class="d-none">
    <input type="hidden" name="action" value="multi_delete">
</form>
<script>
// Multi-select logic
const multiBtn = document.getElementById('multiSelectNotifBtn');
const multiDeleteBtn = document.getElementById('multiDeleteNotifBtn');
const multiDeleteForm = document.getElementById('multiDeleteNotifForm');
const notifCheckboxes = document.querySelectorAll('.notif-checkbox');
const notifItems = document.querySelectorAll('.notif-item');
const selectAllNotif = document.getElementById('selectAllNotif');
const selectAllNotifWrapper = document.getElementById('selectAllNotifWrapper');
const multiDeleteNotifControls = document.getElementById('multiDeleteNotifControls');
let multiMode = false;
function setMultiMode(active) {
    multiMode = active;
    notifCheckboxes.forEach(cb => cb.style.display = multiMode ? '' : 'none');
    multiDeleteNotifControls.style.display = multiMode ? '' : 'none';
    selectAllNotifWrapper.style.display = multiMode ? '' : 'none';
    multiDeleteBtn.style.display = multiMode ? '' : 'none';
    notifItems.forEach(item => item.classList.toggle('bg-light', multiMode));
}
multiBtn.addEventListener('click', function() {
    setMultiMode(!multiMode);
});
if (multiDeleteBtn) {
    multiDeleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // Collect checked IDs and submit form
        const checked = Array.from(notifCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
        if (checked.length === 0) return;
        // Remove previous hidden inputs
        while (multiDeleteForm.firstChild) multiDeleteForm.removeChild(multiDeleteForm.firstChild);
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'multi_delete';
        multiDeleteForm.appendChild(actionInput);
        checked.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_ids[]';
            input.value = id;
            multiDeleteForm.appendChild(input);
        });
        multiDeleteForm.submit();
    });
}
selectAllNotif.addEventListener('change', function() {
    notifCheckboxes.forEach(cb => cb.checked = this.checked);
});
notifItems.forEach((item, idx) => {
    item.addEventListener('click', function(e) {
        if (multiMode && e.target.type !== 'checkbox') {
            notifCheckboxes[idx].checked = !notifCheckboxes[idx].checked;
        }
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
