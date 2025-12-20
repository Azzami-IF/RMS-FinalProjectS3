<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/AnalyticsService.php';
require_once __DIR__ . '/../classes/Admin/DashboardController.php';

use Admin\DashboardController;

require_admin();
$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$controller = new DashboardController($db);
$userCount = $controller->getUserCount();
$foodCount = $controller->getFoodCount();
$scheduleCount = $controller->getScheduleCount();
$adminCount = $controller->getAdminCount();
?>

<section class="py-5">
    <div class="container">
        <h1 class="fw-bold mb-3">Dashboard Admin</h1>
        <p class="lead text-muted">Pantau ringkasan sistem dan akses fitur admin.</p>

        <hr>

        <div class="row mt-4 g-4">
            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h6 class="fw-bold">Total Pengguna</h6>
                        <p class="text-muted mb-2">Pengguna terdaftar.</p>
                        <h4 class="text-success mb-0"><?= $userCount ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h6 class="fw-bold">Total Makanan</h6>
                        <p class="text-muted mb-2">Menu tersedia.</p>
                        <h4 class="text-success mb-0"><?= $foodCount ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h6 class="fw-bold">Total Catatan</h6>
                        <p class="text-muted mb-2">Catatan makan pengguna.</p>
                        <h4 class="text-success mb-0"><?= $scheduleCount ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h6 class="fw-bold">Total Admin</h6>
                        <p class="text-muted mb-2">Akun administrator.</p>
                        <h4 class="text-success mb-0"><?= $adminCount ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4 g-4">
            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h5 class="fw-bold">Pengguna</h5>
                        <p class="text-muted mb-3">Kelola akun pengguna.</p>
                        <div>
                            <a href="users.php" class="btn btn-primary w-100 text-truncate">Kelola Pengguna</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h5 class="fw-bold">Catatan</h5>
                        <p class="text-muted mb-3">Pantau catatan makan pengguna.</p>
                        <div>
                            <a href="schedules.php" class="btn btn-warning w-100 text-truncate">Lihat Catatan</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h5 class="fw-bold">Laporan</h5>
                        <p class="text-muted mb-3">Analitik penggunaan sistem.</p>
                        <div>
                            <a href="reports.php" class="btn btn-info w-100 text-truncate">Lihat Laporan</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h5 class="fw-bold">Broadcast</h5>
                        <p class="text-muted mb-3">Kirim notifikasi in-app.</p>
                        <div>
                            <a href="broadcast.php" class="btn btn-success w-100 text-truncate">Buka Broadcast</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
