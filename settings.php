<?php
require_once 'includes/header.php';
require_once 'includes/auth_guard.php';
require_once 'config/database.php';
require_once 'classes/UserPreferences.php';

$config = require 'config/env.php';
$db = (new Database($config))->getConnection();
$userPrefs = new UserPreferences($db);

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        // Update user preferences in database
        $preferences = [
            'theme' => $_POST['theme'] ?? 'light',
            // 'language' removed
            'notifications_email' => isset($_POST['notifications_email']) ? '1' : '0',
            'notifications_inapp' => isset($_POST['notifications_inapp']) ? '1' : '0',
            'units_weight' => $_POST['units_weight'] ?? 'kg',
            'units_height' => $_POST['units_height'] ?? 'cm',
            // privacy fields removed
        ];

        $userPrefs->setMultiple($_SESSION['user']['id'], $preferences);


        $message = 'Pengaturan berhasil disimpan!';
        $messageType = 'success';
    }
}

// Get current preferences
$currentPrefs = $userPrefs->getAll($_SESSION['user']['id']);

// Set defaults if not set
$defaults = [
    'theme' => 'light',
    // 'language' removed
    'notifications_email' => '1',
    'notifications_inapp' => '1',
    'units_weight' => 'kg',
    'units_height' => 'cm',
    // privacy fields removed
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
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
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
                        <div class="card-header bg-light">
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
                        <div class="card-header bg-light">
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
                                            <strong>In-App</strong><br>
                                            <small class="text-muted">Terima notifikasi di dalam aplikasi</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Units Settings -->
                    <div class="card shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-rulers me-2"></i>Satuan Pengukuran
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Berat Badan</label>
                                    <select name="units_weight" class="form-select">
                                        <option value="kg" <?= $currentPrefs['units_weight'] === 'kg' ? 'selected' : '' ?>>Kilogram (kg)</option>
                                        <option value="lbs" <?= $currentPrefs['units_weight'] === 'lbs' ? 'selected' : '' ?>>Pound (lbs)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tinggi Badan</label>
                                    <select name="units_height" class="form-select">
                                        <option value="cm" <?= $currentPrefs['units_height'] === 'cm' ? 'selected' : '' ?>>Centimeter (cm)</option>
                                        <option value="inch" <?= $currentPrefs['units_height'] === 'inch' ? 'selected' : '' ?>>Inch</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Settings removed -->

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
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Pratinjau Tema</h6>
                    </div>
                    <div class="card-body">
                        <div class="theme-preview p-3 rounded mb-3" id="themePreview">
                            <div class="bg-primary text-white p-2 rounded mb-2">Header</div>
                            <div class="bg-light p-2 rounded mb-2">Konten</div>
                            <div class="bg-secondary text-white p-2 rounded">Footer</div>
                        </div>
                        <small class="text-muted">Tema akan diterapkan setelah menyimpan pengaturan</small>
                    </div>
                </div>

                <div class="card shadow-sm rounded-3 mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Tips</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Tema gelap menghemat baterai</li>
                            <li class="mb-2"><i class="bi bi-bell text-info me-2"></i>Notifikasi membantu Anda tetap on track</li>
                            <li class="mb-2"><i class="bi bi-shield text-success me-2"></i>Privasi data Anda selalu aman</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Make user preferences available globally
    window.userPreferences = <?php echo json_encode($currentPrefs); ?>;
</script>

<script>
// Theme preview functionality
document.getElementById('themeSelect').addEventListener('change', function() {
    const theme = this.value;
    const preview = document.getElementById('themePreview');

    if (theme === 'dark') {
        preview.className = 'theme-preview p-3 rounded mb-3 bg-dark text-light';
        preview.innerHTML = `
            <div class="bg-secondary text-white p-2 rounded mb-2">Header</div>
            <div class="bg-dark p-2 rounded mb-2 border">Konten</div>
            <div class="bg-secondary text-white p-2 rounded">Footer</div>
        `;
    } else {
        preview.className = 'theme-preview p-3 rounded mb-3';
        preview.innerHTML = `
            <div class="bg-primary text-white p-2 rounded mb-2">Header</div>
            <div class="bg-light p-2 rounded mb-2">Konten</div>
            <div class="bg-secondary text-white p-2 rounded">Footer</div>
        `;
    }
});

// Apply current theme on load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('themeSelect').dispatchEvent(new Event('change'));
});

// Apply theme on form submit
document.querySelector('form').addEventListener('submit', function(e) {
    const themeSelect = document.getElementById('themeSelect');
    if (themeSelect && window.ThemeManager) {
        console.log('Applying theme:', themeSelect.value);
        window.ThemeManager.applyTheme(themeSelect.value);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?></content>
<parameter name="filePath">c:\laragon\www\RMS\settings.php