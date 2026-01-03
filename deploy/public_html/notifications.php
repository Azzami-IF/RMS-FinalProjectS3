<?php
require_once __DIR__ . '/classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__);
$app->requireUser();

$db = $app->db();
$userId = (int)$app->user()['id'];

function rms_notif_plain_text(string $html): string
{
    $withNewlines = preg_replace('/<br\s*\/?\s*>/i', "\n", $html);
    $text = strip_tags((string)$withNewlines);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/\r\n|\r/", "\n", $text);
    return trim((string)$text);
}

function rms_notif_time_ago(?string $datetime): string
{
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    if ($ts === false) return '';

    $diff = time() - $ts;
    
    // Handle future timestamps (system time mismatch)
    if ($diff < 0) return 'baru saja';

    if ($diff < 60) return 'baru saja';
    if ($diff < 120) return '1 menit lalu';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 7200) return '1 jam lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 172800) return '1 hari lalu';
    if ($diff < 86400 * 7) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y H:i', $ts);
}

function rms_notif_safe_href(?string $href): ?string
{
    $href = trim((string)$href);
    if ($href === '') return null;
    if (str_starts_with($href, '//')) return null;
    $parts = parse_url($href);
    if ($parts === false) return null;

    // Relative URLs are allowed.
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = strtolower((string)($parts['host'] ?? ''));
    if ($scheme === '' && $host === '') {
        return $href;
    }

    // Allow only the production deploy host over HTTPS.
    if ($scheme !== 'https') return null;
    if (!in_array($host, ['webrms.site', 'www.webrms.site'], true)) return null;
    if (!empty($parts['user']) || !empty($parts['pass'])) return null;

    return $href;
}

function rms_notif_safe_image_src(?string $src): ?string
{
    $src = trim((string)$src);
    if ($src === '') return null;
    $parts = parse_url($src);
    if ($parts === false) return null;
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true)) return null;
    return $src;
}

function rms_notif_type_label(?string $type): string
{
    $type = (string)$type;
    switch ($type) {
        case 'menu':
            return 'Menu';
        case 'goal':
            return 'Target';
        case 'reminder':
            return 'Pengingat';
        case 'tip':
            return 'Tips';
        case 'warning':
            return 'Peringatan';
        case 'success':
            return 'Sukses';
        case 'error':
            return 'Error';
        default:
            return 'Info';
    }
}

function rms_render_notification_body(array $n): string
{
    $type = (string)($n['type'] ?? 'info');
    $messageHtml = (string)($n['message'] ?? '');
    $plain = rms_notif_plain_text($messageHtml);
    $actionUrl = rms_notif_safe_href($n['action_url'] ?? null);

    ob_start();

    if ($type === 'menu') {
        $href = null;
        if (preg_match('/<a[^>]+href=[\"\']([^\"\']+)[\"\']/i', $messageHtml, $m)) {
            $href = rms_notif_safe_href($m[1]);
        }

        $notifId = (int)($n['id'] ?? 0);
        if ($href && $notifId > 0 && str_contains($href, 'process/schedule.process.php') && !str_contains($href, 'notif_id=')) {
            $href .= (str_contains($href, '?') ? '&' : '?') . 'notif_id=' . $notifId;
        }

        $img = null;
        if (preg_match('/<img[^>]+src=[\"\']([^\"\']+)[\"\']/i', $messageHtml, $m)) {
            $img = rms_notif_safe_image_src($m[1]);
        }

        $label = '';
        $calories = null;
        if (preg_match('/<b>(.*?)<\/b>\s*\((\d+)\s*kcal\)/i', $messageHtml, $m)) {
            $label = trim(rms_notif_plain_text($m[1]));
            $calories = (int)$m[2];
        }

        $target = null;
        if (preg_match('/Target\s*kalori\s*:\s*(\d+)\s*kcal/i', $plain, $m)) {
            $target = (int)$m[1];
        }


        if ($img || $label || $href) {
            echo '<div class="d-flex align-items-start" style="gap:0.75rem;">';
            if ($img) {
                echo '<img src="' . htmlspecialchars($img) . '" alt="menu" class="rounded" style="width:64px;height:64px;object-fit:cover;">';
            }
            echo '<div style="min-width:0;">';
            if ($label !== '') {
                if ($href) {
                    echo '<div class="fw-semibold"><a class="text-decoration-none" href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . '</a></div>';
                } else {
                    echo '<div class="fw-semibold">' . htmlspecialchars($label) . '</div>';
                }
            }
            if ($calories !== null || $target !== null) {
                echo '<div class="small text-muted mt-1">';
                $parts = [];
                if ($calories !== null) $parts[] = 'Kalori: ' . htmlspecialchars((string)$calories) . ' kcal';
                if ($target !== null) $parts[] = 'Target: ' . htmlspecialchars((string)$target) . ' kcal';
                echo implode(' • ', $parts);
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';

            $primary = $href ?: $actionUrl;
            if ($primary) {
                echo '<div class="mt-2"><a class="btn btn-sm btn-primary" href="' . htmlspecialchars($primary) . '"><i class="bi bi-box-arrow-up-right me-1"></i>Buka Menu</a></div>';
            }
        } else {
            echo '<div class="text-muted small">' . nl2br(htmlspecialchars($plain)) . '</div>';
        }

    } elseif ($type === 'goal') {
        $progress = null;
        if (preg_match('/Progres\s*:\s*(\d{1,3})%/i', $plain, $m)) {
            $progress = max(0, min(100, (int)$m[1]));
        }

        $evaluation = null;
        if (preg_match('/Evaluasi\s*Target\s*:\s*(.*?)(?:\n|\r|\s)*Progres\s*:/is', $plain, $m)) {
            $evaluation = trim((string)$m[1]);
        }

        if ($evaluation) {
            echo '<div class="text-muted">' . htmlspecialchars($evaluation) . '</div>';
        } elseif ($plain !== '') {
            echo '<div class="text-muted">' . nl2br(htmlspecialchars($plain)) . '</div>';
        }

        if ($progress !== null) {
            echo '<div class="mt-2">';
            echo '<div class="d-flex justify-content-between small text-muted"><span>Progres</span><span>' . htmlspecialchars((string)$progress) . '%</span></div>';
            echo '<div class="progress" style="height:8px;">';
            echo '<div class="progress-bar bg-success" role="progressbar" style="width:' . htmlspecialchars((string)$progress) . '%" aria-valuenow="' . htmlspecialchars((string)$progress) . '" aria-valuemin="0" aria-valuemax="100"></div>';
            echo '</div>';
            echo '<div class="mt-2 d-flex flex-wrap gap-2">';
            echo '<a class="btn btn-sm btn-outline-success" href="goals.php"><i class="bi bi-flag me-1"></i>Lihat Target</a>';
            echo '<a class="btn btn-sm btn-outline-secondary" href="evaluation.php"><i class="bi bi-clipboard-check me-1"></i>Lihat Evaluasi</a>';
            echo '</div>';
            echo '</div>';
        }

        if ($actionUrl) {
            echo '<div class="mt-2"><a class="btn btn-sm btn-primary" href="' . htmlspecialchars($actionUrl) . '"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a></div>';
        }

    } elseif ($type === 'reminder') {
        echo '<div class="text-muted">' . nl2br(htmlspecialchars($plain)) . '</div>';
        echo '<div class="mt-2 d-flex flex-wrap gap-2">';
        echo '<a class="btn btn-sm btn-outline-success" href="schedules.php"><i class="bi bi-journal-text me-1"></i>Catat Menu</a>';
        echo '<a class="btn btn-sm btn-outline-secondary" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Ke Dashboard</a>';
        echo '</div>';

        if ($actionUrl) {
            echo '<div class="mt-2"><a class="btn btn-sm btn-primary" href="' . htmlspecialchars($actionUrl) . '"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a></div>';
        }

    } else {
        echo '<div class="text-muted">' . nl2br(htmlspecialchars($plain)) . '</div>';

        if ($actionUrl) {
            echo '<div class="mt-2"><a class="btn btn-sm btn-primary" href="' . htmlspecialchars($actionUrl) . '"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a></div>';
        }
    }

    return (string)ob_get_clean();
}

// Handle POST actions early (before output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $notifId = (int)($_POST['notif_id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ? AND channel = 'in_app'");
            $stmt->execute([$notifId, $userId]);
        }
        header('Location: notifications.php');
        exit;
    }

    if ($action === 'mark_read') {
        $notifId = (int)($_POST['notif_id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ? AND channel = 'in_app'");
            $stmt->execute([$notifId, $userId]);
        }
        header('Location: notifications.php');
        exit;
    }

    if ($action === 'multi_delete' && isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
        $ids = array_values(array_filter(array_map('intval', $_POST['delete_ids']), function ($v) {
            return $v > 0;
        }));
        if (count($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge($ids, [$userId]);
            $stmt = $db->prepare("DELETE FROM notifications WHERE id IN ($in) AND user_id = ? AND channel = 'in_app'");
            $stmt->execute($params);
        }
        header('Location: notifications.php');
        exit;
    }

    if ($action === 'mark_all_read') {
        $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ? AND channel = 'in_app' AND status = 'unread'");
        $stmt->execute([$userId]);
        header('Location: notifications.php');
        exit;
    }
}

// Optional: mark a single notification as read via GET (used by dropdown click)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $userId && isset($_GET['mark_read'])) {
    $notifId = (int)$_GET['mark_read'];
    if ($notifId > 0) {
        $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ? AND channel = 'in_app'");
        $stmt->execute([$notifId, $userId]);
    }
    header('Location: notifications.php' . ($notifId > 0 ? ('#notif-' . $notifId) : ''));
    exit;
}
?>
<?php
require_once __DIR__ . '/includes/header.php';

// AJAX handler: output only notif-list div
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $stmt = $db->prepare(
        "SELECT * FROM notifications
         WHERE user_id = ? AND channel = 'in_app'
         ORDER BY created_at DESC"
    );
    $stmt->execute([$userId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div id="notif-list" class="list-group list-group-flush">
        <?php if (empty($data)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted">
                    <i class="bi bi-bell-slash fs-1 mb-3"></i>
                    <p>Belum ada notifikasi</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($data as $n): ?>
                <?php
                $notifId = (int)($n['id'] ?? 0);
                $isUnread = ($n['status'] ?? 'unread') === 'unread';
                $typeLabel = rms_notif_type_label($n['type'] ?? '');

                $channel = (string)($n['channel'] ?? 'in_app');
                $channelLabel = $channel === 'email' ? 'Email' : 'In-app';

                $status = (string)($n['status'] ?? '');
                $statusLabel = null;
                if ($channel === 'email' && in_array($status, ['sent', 'failed'], true)) {
                    $statusLabel = $status === 'sent' ? 'Terkirim' : 'Gagal';
                }
                ?>
                <details class="list-group-item border-0 border-bottom position-relative" <?php if ($notifId > 0): ?>id="notif-<?php echo $notifId; ?>"<?php endif; ?> data-notif-dropdown="1">
                    <form method="post" action="notifications.php" class="position-absolute top-0 end-0 mt-2 me-2 notif-delete-wrap d-inline-flex" style="z-index:2;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="notif_id" value="<?= (int)($n['id'] ?? 0) ?>">
                        <button type="submit" class="btn btn-sm btn-link text-muted p-0 notif-delete-btn" title="Hapus notifikasi" aria-label="Hapus notifikasi">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </form>
                    <summary class="d-flex justify-content-between align-items-center notif-summary px-3 py-3" style="cursor:pointer;gap:0.75rem;">
                        <div class="fw-semibold text-truncate" style="max-width:560px;">
                            <?= htmlspecialchars((string)($n['title'] ?? '')) ?>
                            <?php if ($isUnread): ?> <span class="text-success" title="Belum dibaca">•</span><?php endif; ?>
                        </div>
                        <small class="text-muted" title="<?= htmlspecialchars(date('d M Y H:i', strtotime((string)$n['created_at']))) ?>">
                            <i class="bi bi-clock me-1"></i><?= htmlspecialchars(rms_notif_time_ago($n['created_at'] ?? null)) ?>
                        </small>
                    </summary>
                    <div class="mt-2">
                        <?= rms_render_notification_body($n) ?>
                    </div>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    exit;
}

// Normal page output
$stmt = $db->prepare(
    "SELECT * FROM notifications
    WHERE user_id = ? AND channel = 'in_app'
     ORDER BY created_at DESC"
);
$stmt->execute([$userId]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCountPage = 0;
if (is_array($data) && count($data)) {
    foreach ($data as $row) {
        if (($row['status'] ?? 'unread') === 'unread') {
            $unreadCountPage++;
        }
    }
}
?>

<h4 class="mb-4">Riwayat Notifikasi</h4>
<div class="card shadow-sm rounded-4 rms-card-adaptive">
    <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
        <div class="d-flex align-items-center gap-2 ms-auto">
            <form method="post" action="notifications.php" class="d-inline">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-outline-secondary btn-sm" <?php if ($unreadCountPage === 0): ?>disabled<?php endif; ?>>
                    Tandai Semua Dibaca
                </button>
            </form>
            <span class="small text-muted" id="selectedNotifCount" style="display:none;">0 dipilih</span>
            <div class="form-check ms-2" style="display:none;" id="selectAllNotifWrapper">
                <input type="checkbox" class="form-check-input" id="selectAllNotif">
                <label class="form-check-label" for="selectAllNotif">Pilih Semua</label>
            </div>
            <button id="multiDeleteNotifBtn" class="btn btn-danger btn-sm" style="margin-bottom:1px;display:none;" disabled>Hapus</button>
            <button id="multiSelectNotifBtn" class="btn btn-outline-primary btn-sm">Pilih Banyak</button>
        </div>
    </div>
    <div id="notif-list" class="list-group list-group-flush" style="overflow-y:auto;overflow-x:hidden;padding:0.5rem 0.5rem 0 0.5rem;max-height:calc(100vh - 180px);">
    <?php if ($data && count($data)): ?>
        <?php foreach ($data as $n): ?>
            <?php
            $notifId = (int)($n['id'] ?? 0);
                $isUnread = ($n['status'] ?? 'unread') === 'unread';
                $type = $n['type'] ?? '';

                $typeLabel = rms_notif_type_label($type);

                $channel = (string)($n['channel'] ?? 'in_app');
                $channelLabel = $channel === 'email' ? 'Email' : 'In-app';

                $status = (string)($n['status'] ?? '');
                $statusLabel = null;
                if ($channel === 'email' && in_array($status, ['sent', 'failed'], true)) {
                    $statusLabel = $status === 'sent' ? 'Terkirim' : 'Gagal';
                }

                $icon = '<i class="bi bi-info-circle-fill text-secondary fs-4"></i>';
                $cardClass = 'notif-item position-relative';

                if ($type === 'goal') {
                    $icon = '<i class="bi bi-flag-fill text-primary fs-4"></i>';
                } elseif ($type === 'reminder') {
                    $icon = '<i class="bi bi-bell-fill text-warning fs-4"></i>';
                } elseif ($type === 'tip') {
                    $icon = '<i class="bi bi-lightbulb-fill text-info fs-4"></i>';
                } elseif ($type === 'menu') {
                    $icon = '<i class="bi bi-egg-fried text-success fs-4"></i>';
                } elseif ($type === 'warning') {
                    $icon = '<i class="bi bi-exclamation-triangle-fill text-warning fs-4"></i>';
                } elseif ($type === 'success') {
                    $icon = '<i class="bi bi-check-circle-fill text-success fs-4"></i>';
                } elseif ($type === 'error') {
                    $icon = '<i class="bi bi-x-octagon-fill text-danger fs-4"></i>';
                }
            ?>
            <div class="list-group-item <?= $cardClass ?><?= $isUnread ? ' notif-unread' : '' ?> d-flex align-items-start position-relative" <?php if ($notifId > 0): ?>id="notif-<?php echo $notifId; ?>"<?php endif; ?>>
                <form method="post" action="notifications.php" class="position-absolute top-0 end-0 mt-2 me-2 notif-delete-wrap d-inline-flex" style="z-index:2;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="notif_id" value="<?= (int)($n['id'] ?? 0) ?>">
                    <button type="submit" class="btn btn-sm btn-link text-muted p-0 notif-delete-btn" title="Hapus notifikasi" aria-label="Hapus notifikasi">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </form>
                <input type="checkbox" class="form-check-input notif-checkbox me-3 mt-2" value="<?= $n['id'] ?>" style="display:none;">
                <div class="flex-shrink-0 me-3 mt-1">
                    <?= $icon ?>
                </div>
                <div class="flex-grow-1">
                    <details class="notif-details" data-notif-dropdown="1">
                        <summary class="d-flex justify-content-between align-items-center notif-summary px-2 py-2" style="cursor:pointer;gap:0.75rem;">
                            <span class="fw-semibold<?= $isUnread ? ' text-primary' : '' ?>">
                                <?= htmlspecialchars($n['title']) ?><?php if ($isUnread): ?> <span class="text-success" title="Belum dibaca">•</span><?php endif; ?>
                            </span>
                            <small class="text-muted ms-2" title="<?= htmlspecialchars(date('d M Y H:i', strtotime((string)$n['created_at']))) ?>"><i class="bi bi-clock me-1"></i><?= htmlspecialchars(rms_notif_time_ago($n['created_at'] ?? null)) ?></small>
                        </summary>
                        <div class="mt-2">
                            <?= rms_render_notification_body($n) ?>
                        </div>

                    </details>
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
const selectedNotifCount = document.getElementById('selectedNotifCount');
let multiMode = false;

// Prevent <details> toggling while multi-select is active
document.addEventListener('click', function(e) {
    const summary = e.target.closest('summary');
    if (!summary) return;
    const details = summary.closest('details[data-notif-dropdown="1"]');
    if (!details) return;
    if (!multiMode) return;
    e.preventDefault();
}, true);

function updateNotifMultiUI() {
    const checked = Array.from(notifCheckboxes).filter(cb => cb.checked).length;
    if (selectedNotifCount) {
        selectedNotifCount.textContent = checked + ' dipilih';
    }
    if (multiDeleteBtn) {
        multiDeleteBtn.disabled = checked === 0;
    }
    if (selectAllNotif) {
        const total = notifCheckboxes.length;
        selectAllNotif.checked = total > 0 && checked === total;
        selectAllNotif.indeterminate = checked > 0 && checked < total;
    }
}

function resetNotifSelection() {
    notifCheckboxes.forEach(cb => cb.checked = false);
    if (selectAllNotif) {
        selectAllNotif.checked = false;
        selectAllNotif.indeterminate = false;
    }
    updateNotifMultiUI();
}

function setMultiMode(active) {
    multiMode = active;
    notifCheckboxes.forEach(cb => cb.style.display = multiMode ? '' : 'none');
    selectAllNotifWrapper.style.display = multiMode ? '' : 'none';
    multiDeleteBtn.style.display = multiMode ? '' : 'none';
    if (selectedNotifCount) selectedNotifCount.style.display = multiMode ? '' : 'none';
    notifItems.forEach(item => item.classList.toggle('notif-multi-mode', multiMode));
    if (multiMode) {
        resetNotifSelection();
    } else {
        resetNotifSelection();
    }
}
multiBtn.addEventListener('click', function() {
    setMultiMode(!multiMode);
    multiBtn.textContent = multiMode ? 'Batal' : 'Pilih Banyak';
});
if (multiDeleteBtn) {
    multiDeleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // Collect checked IDs and submit form
        const checked = Array.from(notifCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
        if (checked.length === 0) return;

        const ok = confirm('Yakin ingin menghapus ' + checked.length + ' notifikasi terpilih?');
        if (!ok) return;

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
    updateNotifMultiUI();
});

notifCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        updateNotifMultiUI();
    });
});

notifItems.forEach((item, idx) => {
    item.addEventListener('click', function(e) {
        // Multi-select mode: clicking item toggles checkbox only
        if (multiMode) {
            if (e.target.closest('button, a, input, label, select, textarea, form, summary')) return;
            if (!notifCheckboxes[idx]) return;
            notifCheckboxes[idx].checked = !notifCheckboxes[idx].checked;
            updateNotifMultiUI();
            return;
        }

        // Normal mode: make the entire item clickable to toggle its dropdown (<details>)
        if (e.target.closest('button, a, input, label, select, textarea, form, summary')) return;
        const details = item.querySelector('details.notif-details');
        if (!details) return;
        details.open = !details.open;
    });
});
</script>
<style>
.notif-item {
    background: white;
    border: 1px solid #e0e0e0 !important;
    border-radius: 0.75rem !important;
    margin-bottom: 1rem !important;
    padding: 1.25rem 1rem !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    transition: all 0.2s ease;
}

.notif-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.notif-item.notif-unread {
    background: linear-gradient(to right, #f0f9f4, #ffffff);
    border-left: 4px solid #28a745 !important;
}

.notif-item.notif-multi-mode {
    outline: 2px dashed rgba(40, 167, 69, 0.25);
    outline-offset: 2px;
}

.notif-summary {
    border-radius: 0.5rem;
    transition: background 0.15s;
}

.notif-summary:hover {
    background: rgba(0,0,0,0.02);
}

.notif-delete-btn {
    opacity: 0.5;
    transition: opacity 0.2s;
}

.notif-item:hover .notif-delete-btn {
    opacity: 1;
}

.notif-item .flex-shrink-0 {
    width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
