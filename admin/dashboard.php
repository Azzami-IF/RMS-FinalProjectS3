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


<section class="py-5 rms-card-adaptive">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-2">Dashboard <span class="text-success">Admin RMS</span></h1>
                <p class="lead text-muted mb-3">Pantau statistik, kelola pengguna, dan analisis performa sistem rekomendasi makanan sehat.</p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow h-100 text-center rms-card-adaptive">
                    <div class="card-body py-4">
                        <i class="bi bi-people-fill fs-1 text-success mb-2"></i>
                        <h6 class="fw-bold text-uppercase small mb-1">Total Users</h6>
                        <div class="display-6 fw-bold text-success mb-1"><?= $userCount ?></div>
                        <div class="small text-muted">Pengguna terdaftar</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow h-100 text-center rms-card-adaptive">
                    <div class="card-body py-4">
                        <i class="bi bi-egg-fried fs-1 text-primary mb-2"></i>
                        <h6 class="fw-bold text-uppercase small mb-1">Total Makanan</h6>
                        <div class="display-6 fw-bold text-primary mb-1"><?= $foodCount ?></div>
                        <div class="small text-muted">Menu tersedia</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow h-100 text-center rms-card-adaptive">
                    <div class="card-body py-4">
                        <i class="bi bi-calendar-check-fill fs-1 text-warning mb-2"></i>
                        <h6 class="fw-bold text-uppercase small mb-1">Total Catatan</h6>
                        <div class="display-6 fw-bold text-warning mb-1"><?= $scheduleCount ?></div>
                        <div class="small text-muted">Catatan makan</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow h-100 text-center rms-card-adaptive">
                    <div class="card-body py-4">
                        <i class="bi bi-shield-check fs-1 text-info mb-2"></i>
                        <h6 class="fw-bold text-uppercase small mb-1">Admin</h6>
                        <div class="display-6 fw-bold text-info mb-1"><?= $adminCount ?></div>
                        <div class="small text-muted">Administrator</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                        <i class="bi bi-people fs-2 text-primary mb-2"></i>
                        <h5 class="fw-bold mb-1">Kelola Users</h5>
                        <p class="text-muted small mb-3">Pantau, edit, dan kelola pengguna sistem.</p>
                        <a href="users.php" class="btn btn-primary w-100"><i class="bi bi-people me-2"></i>Kelola Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                        <i class="bi bi-calendar-check fs-2 text-warning mb-2"></i>
                        <h5 class="fw-bold mb-1">Pantau Catatan</h5>
                        <p class="text-muted small mb-3">Lihat dan kelola catatan makan seluruh pengguna.</p>
                        <a href="schedules.php" class="btn btn-warning w-100"><i class="bi bi-calendar me-2"></i>Lihat Catatan</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                        <i class="bi bi-bar-chart-line fs-2 text-info mb-2"></i>
                        <h5 class="fw-bold mb-1">Laporan & Analytics</h5>
                        <p class="text-muted small mb-3">Analisis data, tren nutrisi, dan statistik aplikasi.</p>
                        <a href="reports.php" class="btn btn-info w-100"><i class="bi bi-graph-up me-2"></i>Lihat Laporan</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom-0">
                        <h6 class="mb-0 fw-bold text-secondary">Aksi Cepat</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 justify-content-center">
                            <div class="col-md-3">
                                <a href="schedules.php" class="btn btn-outline-warning w-100">
                                    <i class="bi bi-calendar me-2"></i>Lihat Semua Catatan
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="users.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-people me-2"></i>Kelola Users
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="reports.php" class="btn btn-outline-info w-100">
                                    <i class="bi bi-graph-up me-2"></i>Laporan & Analytics
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="../logout.php" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
