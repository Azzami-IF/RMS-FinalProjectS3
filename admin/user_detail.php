<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Admin/UserDetailController.php';

use Admin\UserDetailController;

require_admin();
$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if (!$id) {
    echo '<section class="py-5"><div class="container">'
        . '<div class="alert alert-danger mb-0">ID pengguna tidak valid.</div>'
        . '</div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$controller = new UserDetailController($db, $id);
$userData = $controller->getUserData();
if (!$userData) {
    echo '<section class="py-5"><div class="container">'
        . '<div class="alert alert-danger mb-0">Pengguna tidak ditemukan.</div>'
        . '</div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
$scheduleStats = $controller->getScheduleStats();
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Pengguna</h1>
                <p class="text-muted">Informasi lengkap pengguna</p>
            </div>
            <div>
                <a href="user_edit.php?id=<?= $userData['id'] ?>" class="btn btn-warning me-2">
                    <i class="bi bi-pencil me-2"></i>Ubah Pengguna
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header rms-card-adaptive">
                        <h5 class="mb-0">Informasi Pribadi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Nama:</strong><br>
                                <?= htmlspecialchars($userData['name']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong><br>
                                <?= htmlspecialchars($userData['email']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Role:</strong><br>
                                <span class="badge bg-<?= $userData['role'] === 'admin' ? 'danger' : 'success' ?>">
                                    <?= $userData['role'] === 'admin' ? 'Admin' : 'Pengguna' ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <span class="badge bg-<?= $userData['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $userData['is_active'] ? 'Aktif' : 'Non-aktif' ?>
                                </span>
                            </div>
                            <?php if ($userData['phone']): ?>
                            <div class="col-md-6">
                                <strong>Telepon:</strong><br>
                                <?= htmlspecialchars($userData['phone']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($userData['date_of_birth']): ?>
                            <div class="col-md-6">
                                <strong>Tanggal Lahir:</strong><br>
                                <?= date('d M Y', strtotime($userData['date_of_birth'])) ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($userData['gender']): ?>
                            <div class="col-md-6">
                                <strong>Jenis Kelamin:</strong><br>
                                <?= ucfirst($userData['gender']) ?>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <strong>Tanggal Daftar:</strong><br>
                                <?= date('d M Y H:i', strtotime($userData['created_at'])) ?>
                            </div>
                            <?php if ($userData['last_login']): ?>
                            <div class="col-md-6">
                                <strong>Login Terakhir:</strong><br>
                                <?= date('d M Y H:i', strtotime($userData['last_login'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3 mb-3">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0">Statistik</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-calendar-check fs-1 text-primary"></i>
                            <h4 class="mt-2"><?= $scheduleStats['total_schedules'] ?? 0 ?></h4>
                            <small class="text-muted">Total Catatan</small>
                        </div>
                    </div>
                </div>

                <?php if ($userData['height_cm'] && $userData['weight_kg']): ?>
                <div class="card shadow-sm rounded-3">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0">Data Fisik</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <strong>Tinggi</strong><br>
                                <?= $userData['height_cm'] ?> cm
                            </div>
                            <div class="col-6">
                                <strong>Berat</strong><br>
                                <?= $userData['weight_kg'] ?> kg
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <strong>Target Kalori Harian</strong><br>
                            <?= $userData['daily_calorie_goal'] ?> kcal
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>