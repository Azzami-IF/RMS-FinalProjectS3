<?php
require_once __DIR__ . '/classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__);
$app->requireUser();
$GLOBALS['rms_app'] = $app;

require_once __DIR__ . '/includes/header.php';
require_once 'classes/User.php';

$db = $app->db();
$userClass = new User($db);

$user = $app->user();
$userData = $userClass->find($user['id']);

// Handle success/error messages
$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'Profil berhasil diperbarui!';
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'password_incorrect':
            $message = 'Password lama tidak sesuai!';
            $messageType = 'danger';
            break;
        case 'password_mismatch':
            $message = 'Password baru tidak cocok!';
            $messageType = 'danger';
            break;
        default:
            $message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
            $messageType = 'danger';
    }
}
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Ubah Profil</h1>
                <p class="text-muted">Perbarui informasi profil Anda</p>
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
                <div class="card shadow-sm rounded-3">
                    <div class="card-header rms-card-adaptive">
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">Informasi Pribadi</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="physical-tab" data-bs-toggle="tab" data-bs-target="#physical" type="button" role="tab">Data Fisik</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">Ubah Password</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Personal Information Tab -->
                            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                <form method="post" action="process/profile.process.php">
                                    <input type="hidden" name="action" value="update_personal">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Lengkap *</label>
                                            <input type="text" name="name" class="form-control"
                                                   value="<?= htmlspecialchars($userData['name']) ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Email *</label>
                                            <input type="email" name="email" class="form-control"
                                                   value="<?= htmlspecialchars($userData['email']) ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Telepon</label>
                                            <input type="tel" name="phone" class="form-control"
                                                   value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Lahir</label>
                                            <input type="date" name="date_of_birth" class="form-control"
                                                   value="<?= $userData['date_of_birth'] ?? '' ?>"
                                                   min="1900-01-01"
                                                   max="<?= date('Y-m-d') ?>">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Jenis Kelamin</label>
                                            <select name="gender" class="form-select">
                                                <option value="">Pilih...</option>
                                                <option value="male" <?= ($userData['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Laki-laki</option>
                                                <option value="female" <?= ($userData['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Perempuan</option>
                                                <option value="other" <?= ($userData['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Lainnya</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Physical Information Tab -->
                            <div class="tab-pane fade" id="physical" role="tabpanel">
                                <form method="post" action="process/profile.process.php">
                                    <input type="hidden" name="action" value="update_physical">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tinggi Badan (cm)</label>
                                            <input type="number" name="height_cm" class="form-control"
                                                   value="<?= htmlspecialchars((string)($userData['height_cm'] ?? '')) ?>" step="0.1" min="50" max="250">
                                            <div class="form-text">Masukkan tinggi badan dalam centimeter</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Berat Badan (kg)</label>
                                            <input type="number" name="weight_kg" class="form-control"
                                                   value="<?= htmlspecialchars((string)($userData['weight_kg'] ?? '')) ?>" step="0.1" min="20" max="300">
                                            <div class="form-text">Masukkan berat badan dalam kilogram</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Target Kalori Harian</label>
                                            <input type="number" name="daily_calorie_goal" class="form-control"
                                                   value="<?= htmlspecialchars((string)($userData['daily_calorie_goal'] ?? 2000)) ?>" min="1000" max="5000">
                                            <div class="form-text">Target kalori harian Anda</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Tingkat Aktivitas</label>
                                            <select name="activity_level" class="form-select">
                                                <option value="sedentary" <?= ($userData['activity_level'] ?? 'moderate') === 'sedentary' ? 'selected' : '' ?>>Sedentari (Jarang berolahraga)</option>
                                                <option value="light" <?= ($userData['activity_level'] ?? 'moderate') === 'light' ? 'selected' : '' ?>>Ringan (Olahraga ringan 1-3x/minggu)</option>
                                                <option value="moderate" <?= ($userData['activity_level'] ?? 'moderate') === 'moderate' ? 'selected' : '' ?>>Sedang (Olahraga sedang 3-5x/minggu)</option>
                                                <option value="active" <?= ($userData['activity_level'] ?? 'moderate') === 'active' ? 'selected' : '' ?>>Aktif (Olahraga berat 6-7x/minggu)</option>
                                                <option value="very_active" <?= ($userData['activity_level'] ?? 'moderate') === 'very_active' ? 'selected' : '' ?>>Sangat Aktif (Olahraga sangat berat & pekerjaan fisik)</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i>
                                                <strong>Info:</strong> Data fisik digunakan untuk menghitung kebutuhan kalori dan rekomendasi nutrisi yang lebih akurat.
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Password Change Tab -->
                            <div class="tab-pane fade" id="password" role="tabpanel">
                                <form method="post" action="process/profile.process.php">
                                    <input type="hidden" name="action" value="change_password">

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Password Lama *</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Password Baru *</label>
                                            <input type="password" name="new_password" class="form-control" required minlength="6">
                                            <div class="form-text">Minimal 6 karakter</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Konfirmasi Password Baru *</label>
                                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                        </div>

                                        <div class="col-12">
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                <strong>Perhatian:</strong> Setelah mengubah password, Anda akan diminta login kembali.
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bi bi-key me-2"></i>Ubah Password
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Profile Preview -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                                <?= strtoupper(substr($userData['name'], 0, 1)) ?>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($userData['name']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($userData['email']) ?></p>
                        <span class="badge bg-success">User</span>
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="card shadow-sm rounded-3">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0 fw-bold">Aksi Akun</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="bi bi-person me-2"></i>Lihat Profil
                            </a>
                            <button class="btn btn-outline-danger" onclick="confirmDelete()">
                                <i class="bi bi-trash me-2"></i>Hapus Akun
                            </button>
                        </div>

                        <hr>

                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Tips:</strong> Pastikan data Anda selalu diperbarui agar rekomendasi nutrisi lebih akurat.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rms-card-adaptive">
            <div class="modal-header rms-card-adaptive">
                <h5 class="modal-title">Hapus Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body rms-card-adaptive">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Perhatian!</strong> Tindakan ini tidak dapat dibatalkan.
                </div>
                <p>Apakah Anda yakin ingin menghapus akun Anda? Semua data Anda akan hilang permanen.</p>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Masukkan password untuk konfirmasi:</label>
                    <input type="password" class="form-control" id="confirmPassword" required>
                </div>
            </div>
            <div class="modal-footer rms-card-adaptive">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="deleteAccount()">Hapus Akun</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function deleteAccount() {
    var password = document.getElementById('confirmPassword').value;
    if (!password) {
        alert('Masukkan password untuk konfirmasi!');
        return;
    }

    if (confirm('Apakah Anda benar-benar yakin ingin menghapus akun ini? Tindakan ini tidak dapat dibatalkan!')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process/profile.process.php';

        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_account';
        form.appendChild(actionInput);

        var passwordInput = document.createElement('input');
        passwordInput.type = 'hidden';
        passwordInput.name = 'password';
        passwordInput.value = password;
        form.appendChild(passwordInput);

        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-focus on tab if hash is present
document.addEventListener('DOMContentLoaded', function() {
    var hash = window.location.hash;
    if (hash) {
        var tab = document.querySelector('button[data-bs-target="' + hash + '"]');
        if (tab) {
            tab.click();
        }
    }

    // Add validation for password change form
    var passwordForm = document.querySelector('form input[name="action"][value="change_password"]').closest('form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            var newPassword = passwordForm.querySelector('input[name="new_password"]').value;
            var confirmPassword = passwordForm.querySelector('input[name="confirm_password"]').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Password baru dan konfirmasi password tidak cocok!');
                return false;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password baru minimal 6 karakter!');
                return false;
            }
        });
    }

    // Add validation for physical data form
    var physicalForm = document.querySelector('form input[name="action"][value="update_physical"]').closest('form');
    if (physicalForm) {
        physicalForm.addEventListener('submit', function(e) {
            var height = physicalForm.querySelector('input[name="height_cm"]').value;
            var weight = physicalForm.querySelector('input[name="weight_kg"]').value;
            var calories = physicalForm.querySelector('input[name="daily_calorie_goal"]').value;

            if (height && (isNaN(height) || height < 50 || height > 250)) {
                e.preventDefault();
                alert('Tinggi badan harus antara 50-250 cm!');
                return false;
            }

            if (weight && (isNaN(weight) || weight < 20 || weight > 300)) {
                e.preventDefault();
                alert('Berat badan harus antara 20-300 kg!');
                return false;
            }

            if (calories && (isNaN(calories) || calories < 1000 || calories > 5000)) {
                e.preventDefault();
                alert('Target kalori harus antara 1000-5000 kcal!');
                return false;
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>