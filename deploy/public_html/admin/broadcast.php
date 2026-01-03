<?php
require_once __DIR__ . '/../classes/PageBootstrap.php';

$app = PageBootstrap::requireAdmin(__DIR__ . '/..');

require_once __DIR__ . '/../includes/header.php';

$db = $app->db();

$users = $db->query("SELECT id, name, email FROM users WHERE role='user' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$admins = $db->query("SELECT id, name, email FROM users WHERE role='admin' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$messageType = '';
if (isset($_GET['success']) && $_GET['success'] === 'broadcast_sent') {
    $count = (int)($_GET['count'] ?? 0);
    $message = $count > 0 ? ('Notifikasi berhasil dikirim ke ' . $count . ' akun.') : 'Notifikasi berhasil dikirim.';
    $messageType = 'success';
} elseif (isset($_GET['success']) && $_GET['success'] === 'notif_run') {
    $message = 'Script notifikasi berhasil dijalankan.';
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $isRunnerError = isset($_GET['id']) && (string)$_GET['id'] !== '';
    $prefix = $isRunnerError ? 'Gagal menjalankan script: ' : 'Gagal mengirim notifikasi: ';
    $message = $prefix . htmlspecialchars((string)$_GET['error']);
    $messageType = 'danger';
}

// Load monitoring logs (optional)
$runId = (string)($_GET['id'] ?? '');
$runs = [];
$runsPath = __DIR__ . '/../cache/notification_runs.json';
if (is_file($runsPath)) {
    $raw = file_get_contents($runsPath);
    $decoded = json_decode((string)$raw, true);
    if (is_array($decoded)) {
        $runs = $decoded;
    }
}

$selectedRun = null;
if ($runId !== '' && is_array($runs)) {
    foreach ($runs as $r) {
        if (is_array($r) && (string)($r['id'] ?? '') === $runId) {
            $selectedRun = $r;
            break;
        }
    }
}
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Broadcast Notifikasi</h1>
                <p class="text-muted mb-0">Kirim notifikasi in-app ke satu akun, semua user, semua admin, atau semuanya.</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType ?: 'info') ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header rms-card-adaptive d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2"></i>Form Broadcast</h6>
                        <small class="text-muted">Notifikasi in-app</small>
                    </div>
                    <div class="card-body">
                        <form method="post" action="../process/broadcast.process.php">
                            <input type="hidden" name="action" value="broadcast">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Penerima</label>
                                    <input type="hidden" name="recipient" id="recipientValue" value="">

                                    <div class="dropdown" id="recipientDropdown">
                                        <input
                                            type="text"
                                            class="form-control dropdown-toggle"
                                            id="recipientSearch"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            autocomplete="off"
                                            placeholder="Cari penerima..."
                                            value=""
                                        >

                                        <div class="dropdown-menu w-100 p-0" aria-labelledby="recipientSearch" style="max-height: 320px; overflow-y: auto;">
                                            <div class="px-3 py-2 border-bottom small text-muted">Pilih penerima</div>

                                            <button type="button" class="dropdown-item py-2" data-recipient="all_users" data-label="Semua User">
                                                Semua User
                                            </button>
                                            <button type="button" class="dropdown-item py-2" data-recipient="all_admins" data-label="Semua Admin">
                                                Semua Admin
                                            </button>
                                            <button type="button" class="dropdown-item py-2" data-recipient="all_everyone" data-label="Semua (User + Admin)">
                                                Semua (User + Admin)
                                            </button>

                                            <?php if (!empty($users)): ?>
                                                <div class="dropdown-header small text-uppercase text-muted">User</div>
                                                <?php foreach ($users as $u): ?>
                                                    <?php
                                                    $label = (string)$u['name'] . ' (' . (string)$u['email'] . ')';
                                                    $labelSafe = htmlspecialchars($label);
                                                    ?>
                                                    <button type="button" class="dropdown-item py-2" data-recipient="user:<?= (int)$u['id'] ?>" data-label="<?= $labelSafe ?>">
                                                        <div class="fw-semibold text-truncate" style="max-width: 100%;"><?= htmlspecialchars((string)$u['name']) ?></div>
                                                        <div class="small text-muted text-truncate" style="max-width: 100%;"><?= htmlspecialchars((string)$u['email']) ?></div>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <?php if (!empty($admins)): ?>
                                                <div class="dropdown-header small text-uppercase text-muted">Admin</div>
                                                <?php foreach ($admins as $a): ?>
                                                    <?php
                                                    $label = (string)$a['name'] . ' (' . (string)$a['email'] . ')';
                                                    $labelSafe = htmlspecialchars($label);
                                                    ?>
                                                    <button type="button" class="dropdown-item py-2" data-recipient="admin:<?= (int)$a['id'] ?>" data-label="<?= $labelSafe ?>">
                                                        <div class="fw-semibold text-truncate" style="max-width: 100%;"><?= htmlspecialchars((string)$a['name']) ?></div>
                                                        <div class="small text-muted text-truncate" style="max-width: 100%;"><?= htmlspecialchars((string)$a['email']) ?></div>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <div class="px-3 py-2 border-top small text-muted" id="recipientEmpty" style="display:none;">Tidak ada hasil</div>
                                        </div>
                                    </div>
                                    <div class="form-text">Ketik untuk mencari, lalu pilih dari dropdown.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Judul</label>
                                    <input type="text" name="title" class="form-control" maxlength="150" required>
                                    <div class="form-text">Contoh: Informasi Sistem</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tipe</label>
                                    <select name="type" class="form-select">
                                        <option value="info" selected>Info</option>
                                        <option value="warning">Peringatan</option>
                                        <option value="success">Sukses</option>
                                        <option value="tip">Tips</option>
                                        <option value="reminder">Pengingat</option>
                                    </select>
                                    <div class="form-text">Mengatur ikon dan label notifikasi.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Pesan</label>
                                    <textarea name="message" class="form-control" rows="5" required></textarea>
                                    <div class="form-text">Pesan akan muncul di menu Notifikasi pengguna.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Tautan (Opsional)</label>
                                    <input type="text" name="action_url" class="form-control" maxlength="512" placeholder="Contoh: goals.php atau schedules.php">
                                    <div class="form-text">Isi link relatif (tanpa domain) untuk tombol "Buka" di notifikasi.</div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i>Kirim Notifikasi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="d-grid gap-3">
                    <div class="card shadow-sm rounded-3">
                        <div class="card-header rms-card-adaptive">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Catatan</h6>
                        </div>
                        <div class="card-body">
                            <ul class="small text-muted mb-0 ps-3">
                                <li>Broadcast ini hanya mengirim notifikasi <b>in-app</b>.</li>
                                <li>Judul dan pesan akan tampil di halaman Notifikasi user.</li>
                                <li>Jika mengisi tautan, user akan melihat tombol <b>Buka</b>.</li>
                                <li>Gunakan bahasa yang singkat dan jelas.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow-sm rounded-3">
                        <div class="card-header rms-card-adaptive d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-activity me-2"></i>Monitoring Notifikasi</h6>
                            <small class="text-muted">Manual Run</small>
                        </div>
                        <div class="card-body">
                            <form method="post" action="../process/notification_runner.process.php" class="mb-3" id="notifRunnerForm">
                                <input type="hidden" name="action" value="run_notification_script">

                                <div class="mb-2">
                                    <label class="form-label">Pilih Script</label>
                                    <select name="script" class="form-select" required>
                                        <option value="send_daily">Pengingat Sarapan Pagi</option>
                                        <option value="send_daily_menu">Rekomendasi Menu Harian</option>
                                        <option value="send_reminder_log">Pengingat Pencatatan Harian</option>
                                        <option value="send_goal_evaluation">Evaluasi Target Mingguan</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Target User (Opsional)</label>
                                    <input type="hidden" name="user_id" id="notifRunnerUserId" value="">

                                    <div class="dropdown" id="notifRunnerUserDropdown">
                                        <input
                                            type="text"
                                            class="form-control dropdown-toggle"
                                            id="notifRunnerUserSearch"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            autocomplete="off"
                                            placeholder="Cari user (opsional)..."
                                            value=""
                                        >

                                        <div class="dropdown-menu w-100 p-0" aria-labelledby="notifRunnerUserSearch" style="max-height: 240px; overflow-y: auto;">
                                            <div class="px-3 py-2 border-bottom small text-muted">Pilih target (opsional)</div>

                                            <button type="button" class="dropdown-item py-2" data-user-id="" data-label="Semua User">
                                                Semua User
                                            </button>

                                            <?php if (!empty($users)): ?>
                                                <div class="dropdown-header small text-uppercase text-muted">User</div>
                                                <?php foreach ($users as $u): ?>
                                                    <?php
                                                    $label = (string)$u['name'] . ' (' . (string)$u['email'] . ')';
                                                    $labelSafe = htmlspecialchars($label);
                                                    ?>
                                                    <button type="button" class="dropdown-item py-2" data-user-id="<?= (int)$u['id'] ?>" data-label="<?= $labelSafe ?>">
                                                        <div class="fw-semibold text-truncate" style="max-width: 100%;"><?= htmlspecialchars((string)$u['name']) ?></div>
                                                        <div class="small text-muted text-truncate" style="max-width: 100%;"><?= htmlspecialchars((string)$u['email']) ?></div>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <div class="px-3 py-2 border-top small text-muted" id="notifRunnerUserEmpty" style="display:none;">Tidak ada hasil</div>
                                        </div>
                                    </div>

                                    <div class="form-text">Jika pilih user tertentu, hanya user itu yang dikirimi (untuk testing).</div>
                                </div>

                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-play-circle me-2"></i>Jalankan Script
                                </button>
                            </form>

                            <?php if (is_array($selectedRun)): ?>
                                <?php
                                $status = (string)($selectedRun['status'] ?? '');
                                $badge = $status === 'success' ? 'success' : 'danger';
                                ?>
                                <div class="border rounded-3 p-2">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <div class="fw-semibold">Hasil Terakhir</div>
                                        <span class="badge bg-<?= htmlspecialchars($badge) ?>"><?= htmlspecialchars($status) ?></span>
                                    </div>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars((string)($selectedRun['label'] ?? '')) ?><br>
                                        Mulai: <?= htmlspecialchars((string)($selectedRun['started_at'] ?? '')) ?><br>
                                        Durasi: <?= htmlspecialchars((string)($selectedRun['duration_ms'] ?? '0')) ?> ms
                                        <?php if (!empty($selectedRun['user_id'])): ?>
                                            <br>Target user_id: <?= (int)$selectedRun['user_id'] ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($selectedRun['error'])): ?>
                                        <div class="alert alert-danger mt-2 mb-2 py-2 px-2 small">
                                            <?= htmlspecialchars((string)$selectedRun['error']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedRun['output']) && (string)$selectedRun['output'] !== ''): ?>
                                        <div class="mt-2">
                                            <div class="small fw-semibold mb-1">Output</div>
                                            <pre class="small mb-0" style="white-space:pre-wrap;max-height:220px;overflow:auto;"><?= htmlspecialchars((string)$selectedRun['output']) ?></pre>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-muted mt-2">Output kosong (script tidak mencetak apa pun).</div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="small text-muted">Jalankan script untuk melihat hasilnya di sini.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var searchInput = document.getElementById('recipientSearch');
    var valueInput = document.getElementById('recipientValue');
    var dropdown = document.getElementById('recipientDropdown');
    if (!searchInput || !valueInput || !dropdown) return;

    var form = dropdown.closest ? dropdown.closest('form') : null;

    var menu = dropdown.querySelector('.dropdown-menu');
    var emptyState = document.getElementById('recipientEmpty');
    var items = Array.prototype.slice.call(dropdown.querySelectorAll('.dropdown-item[data-recipient]'));
    var headers = Array.prototype.slice.call(dropdown.querySelectorAll('.dropdown-header'));

    function normalize(s) {
        return (s || '').toString().toLowerCase();
    }

    function filterItems() {
        var q = normalize(searchInput.value);
        var visibleCount = 0;

        items.forEach(function(btn) {
            var label = normalize(btn.getAttribute('data-label'));
            var show = q === '' || label.indexOf(q) !== -1;
            btn.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Show/hide group headers based on whether any following items are visible.
        // Minimal logic: hide header if no visible items in its group.
        headers.forEach(function(h) {
            var hasVisible = false;
            var el = h.nextElementSibling;
            while (el && !el.classList.contains('dropdown-header') && !el.classList.contains('px-3')) {
                if (el.matches && el.matches('.dropdown-item[data-recipient]') && el.style.display !== 'none') {
                    hasVisible = true;
                    break;
                }
                el = el.nextElementSibling;
            }
            h.style.display = hasVisible ? '' : 'none';
        });

        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    function selectRecipient(recipient, label) {
        valueInput.value = recipient;
        searchInput.value = label || '';
        try { searchInput.setCustomValidity(''); } catch (e) {}
    }

    dropdown.addEventListener('click', function(e) {
        var btn = e.target && e.target.closest ? e.target.closest('.dropdown-item[data-recipient]') : null;
        if (!btn) return;
        e.preventDefault();
        selectRecipient(btn.getAttribute('data-recipient'), btn.getAttribute('data-label') || btn.textContent.trim());
    });

    searchInput.addEventListener('input', filterItems);
    searchInput.addEventListener('focus', filterItems);

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!valueInput.value) {
                e.preventDefault();
                try { searchInput.setCustomValidity('Silakan pilih penerima dari daftar.'); } catch (err) {}
                try { searchInput.reportValidity(); } catch (err) {}
                try { searchInput.focus(); } catch (err) {}
            } else {
                try { searchInput.setCustomValidity(''); } catch (err) {}
            }
        });
    }

    // Initial filter state
    filterItems();
})();
</script>

<script>
(function() {
    // Confirm before running notification scripts
    var runForm = document.getElementById('notifRunnerForm');
    if (runForm) {
        runForm.addEventListener('submit', function(e) {
            var scriptSelect = runForm.querySelector('select[name="script"]');
            var scriptLabel = scriptSelect ? (scriptSelect.options[scriptSelect.selectedIndex] ? scriptSelect.options[scriptSelect.selectedIndex].text : '') : '';
            var ok = window.confirm('Jalankan script notifikasi sekarang?\n\nScript: ' + scriptLabel + '\n\nCatatan: ini bisa mengirim notifikasi ke user.');
            if (!ok) {
                e.preventDefault();
            }
        });
    }

    // Searchable dropdown for optional target user
    var userSearch = document.getElementById('notifRunnerUserSearch');
    var userIdInput = document.getElementById('notifRunnerUserId');
    var userDropdown = document.getElementById('notifRunnerUserDropdown');
    if (!userSearch || !userIdInput || !userDropdown) return;

    var userEmpty = document.getElementById('notifRunnerUserEmpty');
    var userItems = Array.prototype.slice.call(userDropdown.querySelectorAll('.dropdown-item[data-user-id]'));
    var userHeaders = Array.prototype.slice.call(userDropdown.querySelectorAll('.dropdown-header'));

    function normalize(s) {
        return (s || '').toString().toLowerCase();
    }

    function filterUsers() {
        var q = normalize(userSearch.value);
        var visibleCount = 0;

        userItems.forEach(function(btn) {
            var label = normalize(btn.getAttribute('data-label'));
            var show = q === '' || label.indexOf(q) !== -1;
            btn.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        userHeaders.forEach(function(h) {
            var hasVisible = false;
            var el = h.nextElementSibling;
            while (el && !el.classList.contains('dropdown-header') && !el.classList.contains('px-3')) {
                if (el.matches && el.matches('.dropdown-item[data-user-id]') && el.style.display !== 'none') {
                    hasVisible = true;
                    break;
                }
                el = el.nextElementSibling;
            }
            h.style.display = hasVisible ? '' : 'none';
        });

        if (userEmpty) {
            userEmpty.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    function selectUser(userId, label) {
        userIdInput.value = userId || '';
        userSearch.value = label || '';
    }

    userDropdown.addEventListener('click', function(e) {
        var btn = e.target && e.target.closest ? e.target.closest('.dropdown-item[data-user-id]') : null;
        if (!btn) return;
        e.preventDefault();
        selectUser(btn.getAttribute('data-user-id'), btn.getAttribute('data-label') || btn.textContent.trim());
    });

    userSearch.addEventListener('input', filterUsers);
    userSearch.addEventListener('focus', filterUsers);
    filterUsers();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
