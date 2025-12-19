<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Admin/UserEditController.php';

use Admin\UserEditController;

require_admin();
$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$controller = new UserEditController($db, (int)$_GET['id']);
$userData = $controller->getUserData();
if (!$userData) {
    echo '<div class="container mt-5"><div class="alert alert-danger">User tidak ditemukan</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Edit User</h1>
                <p class="text-muted">Ubah informasi pengguna</p>
            </div>
            <a href="user_detail.php?id=<?= $userData['id'] ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Form Edit User</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="../process/user.process.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $userData['id'] ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control"
                                           value="<?= htmlspecialchars($userData['name']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
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
                                           value="<?= $userData['date_of_birth'] ?? '' ?>">
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

                                <div class="col-md-6">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-select" required>
                                        <option value="user" <?= $userData['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= $userData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Tinggi Badan (cm)</label>
                                    <input type="number" name="height_cm" class="form-control"
                                           value="<?= $userData['height_cm'] ?? '' ?>" step="0.1">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Berat Badan (kg)</label>
                                    <input type="number" name="weight_kg" class="form-control"
                                           value="<?= $userData['weight_kg'] ?? '' ?>" step="0.1">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Target Kalori Harian</label>
                                    <input type="number" name="daily_calorie_goal" class="form-control"
                                           value="<?= $userData['daily_calorie_goal'] ?? 2000 ?>" min="1000" max="5000">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tingkat Aktivitas</label>
                                    <select name="activity_level" class="form-select">
                                        <option value="sedentary" <?= ($userData['activity_level'] ?? 'moderate') === 'sedentary' ? 'selected' : '' ?>>Sedentary (Jarang olahraga)</option>
                                        <option value="light" <?= ($userData['activity_level'] ?? 'moderate') === 'light' ? 'selected' : '' ?>>Light (Olahraga ringan 1-3x/minggu)</option>
                                        <option value="moderate" <?= ($userData['activity_level'] ?? 'moderate') === 'moderate' ? 'selected' : '' ?>>Moderate (Olahraga sedang 3-5x/minggu)</option>
                                        <option value="active" <?= ($userData['activity_level'] ?? 'moderate') === 'active' ? 'selected' : '' ?>>Active (Olahraga berat 6-7x/minggu)</option>
                                        <option value="very_active" <?= ($userData['activity_level'] ?? 'moderate') === 'very_active' ? 'selected' : '' ?>>Very Active (Olahraga sangat berat & pekerjaan fisik)</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Status Akun</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active"
                                               value="1" <?= $userData['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label">
                                            Akun Aktif
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                                    </button>
                                    <a href="user_detail.php?id=<?= $userData['id'] ?>" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-2"></i>Batal
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>