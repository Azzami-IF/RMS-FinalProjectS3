<?php
require_once __DIR__ . '/classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__);
$app->requireUser();
$GLOBALS['rms_app'] = $app;

require_once __DIR__ . '/includes/header.php';
require_once 'classes/UserPreferences.php';

$db = $app->db();
$userId = (int)$app->user()['id'];
$userPrefs = new UserPreferences($db);
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $preferences = [
            'theme' => $_POST['theme'] ?? 'light',
            'notifications_email' => isset($_POST['notifications_email']) ? '1' : '0',
            'notifications_inapp' => isset($_POST['notifications_inapp']) ? '1' : '0',
        ];

        $userPrefs->setMultiple($userId, $preferences);


        $message = 'Pengaturan berhasil disimpan!';
        $messageType = 'success';
    }
}

$currentPrefs = $userPrefs->getAll($userId);

$defaults = [
    'theme' => 'light',
    'notifications_email' => '1',
    'notifications_inapp' => '1',
];

$currentPrefs = array_merge($defaults, $currentPrefs);
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Pengaturan</h1>
                <p class="text-muted">Sesuaikan preferensi aplikasi Anda</p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-4" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <form method="post" action="settings.php">
                    <input type="hidden" name="action" value="update_settings">

                    <!-- Appearance Settings -->
                    <div class="card shadow-sm rounded-3 mb-4">
                        <div class="card-header rms-card-adaptive">
                            <h5 class="mb-0">
                                <i class="bi bi-palette me-2"></i>Tampilan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tema</label>
                                    <select name="theme" class="form-select" id="themeSelect">
                                        <option value="light" <?= $currentPrefs['theme'] === 'light' ? 'selected' : '' ?>>Terang</option>
                                        <option value="dark" <?= $currentPrefs['theme'] === 'dark' ? 'selected' : '' ?>>Gelap</option>
                                        <option value="auto" <?= $currentPrefs['theme'] === 'auto' ? 'selected' : '' ?>>Otomatis (sesuai sistem)</option>
                                    </select>
                                </div>
                                <!-- Bahasa setting removed -->
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="card shadow-sm rounded-3 mb-4">
                        <div class="card-header rms-card-adaptive">
                            <h5 class="mb-0">
                                <i class="bi bi-bell me-2"></i>Notifikasi
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notifications_email" id="notifEmail"
                                               <?= $currentPrefs['notifications_email'] === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="notifEmail">
                                            <strong>Email</strong><br>
                                            <small class="text-muted">Terima notifikasi melalui email</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notifications_inapp" id="notifInApp"
                                               <?= $currentPrefs['notifications_inapp'] === '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="notifInApp">
                                            <strong>Di Aplikasi</strong><br>
                                            <small class="text-muted">Terima notifikasi di dalam aplikasi</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Save Button -->
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview/Settings Info -->
            <div class="col-lg-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0">Pratinjau Tema</h6>
                    </div>
                    <div class="card-body">
                        <div class="theme-preview p-3 rounded mb-3" id="themePreview">
                            <div class="bg-primary text-white p-2 rounded mb-2">Bagian Atas</div>
                            <div class="p-2 rounded mb-2 rms-card-adaptive">Konten</div>
                            <div class="bg-secondary text-white p-2 rounded">Bagian Bawah</div>
                        </div>
                        <small class="text-muted">Tema akan diterapkan setelah menyimpan pengaturan</small>
                    </div>
                </div>

                <div class="card shadow-sm rounded-3 mt-3">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0">Tips</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Tema gelap menghemat baterai</li>
                            <li class="mb-2"><i class="bi bi-bell text-info me-2"></i>Notifikasi membantu Anda tetap konsisten</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    window.userPreferences = <?php echo json_encode($currentPrefs); ?>;
</script>

<script>
document.getElementById('themeSelect').addEventListener('change', function() {
    const theme = this.value;
    const preview = document.getElementById('themePreview');

    if (theme === 'dark') {
        preview.className = 'theme-preview p-3 rounded mb-3 bg-dark text-light';
        preview.innerHTML = `
            <div class="bg-secondary text-white p-2 rounded mb-2">Bagian Atas</div>
            <div class="bg-dark p-2 rounded mb-2 border">Konten</div>
            <div class="bg-secondary text-white p-2 rounded">Bagian Bawah</div>
        `;
    } else {
        preview.className = 'theme-preview p-3 rounded mb-3';
        preview.innerHTML = `
            <div class="bg-primary text-white p-2 rounded mb-2">Bagian Atas</div>
            <div class="p-2 rounded mb-2 rms-card-adaptive">Konten</div>
            <div class="bg-secondary text-white p-2 rounded">Bagian Bawah</div>
        `;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('themeSelect').dispatchEvent(new Event('change'));
});

document.querySelector('form').addEventListener('submit', function(e) {
    const themeSelect = document.getElementById('themeSelect');
    if (themeSelect && window.ThemeManager) {
        window.ThemeManager.applyTheme(themeSelect.value);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>