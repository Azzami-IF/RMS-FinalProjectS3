<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/AnalyticsService.php';

require_admin();

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$analytics = new AnalyticsService($db);

// Get admin statistics
$userCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$foodCount = $db->query("SELECT COUNT(*) FROM foods")->fetchColumn();
$scheduleCount = $db->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
$adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
?>

<section class="py-5">
    <div class="container">
        <h1 class="fw-bold mb-3">
            Dashboard Admin
            <span class="text-success">RMS</span>
        </h1>

        <p class="lead text-muted">Kelola sistem Rekomendasi Makanan Sehat</p>

        <hr>

        <!-- Statistics Cards -->
        <div class="row mt-4 g-4">
            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 border-success">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill fs-1 text-success mb-2"></i>
                        <h5 class="fw-bold">Total Users</h5>
                        <h4 class="text-success"><?= $userCount ?></h4>
                        <small class="text-muted">Pengguna terdaftar</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 border-primary">
                    <div class="card-body text-center">
                        <i class="bi bi-egg-fried fs-1 text-primary mb-2"></i>
                        <h5 class="fw-bold">Total Makanan</h5>
                        <h4 class="text-primary"><?= $foodCount ?></h4>
                        <small class="text-muted">Menu tersedia</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 border-warning">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check-fill fs-1 text-warning mb-2"></i>
                        <h5 class="fw-bold">Total Jadwal</h5>
                        <h4 class="text-warning"><?= $scheduleCount ?></h4>
                        <small class="text-muted">Jadwal makan</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm rounded-3 border-info">
                    <div class="card-body text-center">
                        <i class="bi bi-shield-check fs-1 text-info mb-2"></i>
                        <h5 class="fw-bold">Admin Count</h5>
                        <h4 class="text-info"><?= $adminCount ?></h4>
                        <small class="text-muted">Administrator</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Cards -->
        <div class="row mt-5 g-4">
            <div class="col-md-6">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-egg-fried fs-2 text-success me-3"></i>
                            <div>
                                <h5 class="fw-bold mb-1">Kelola Makanan</h5>
                                <small class="text-muted">Tambah, edit, dan hapus data menu sehat</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">
                            Kelola database makanan dengan informasi nutrisi lengkap termasuk kalori, protein, lemak, dan karbohidrat.
                        </p>
                        <a href="foods.php" class="btn btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Kelola Makanan
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-people-fill fs-2 text-primary me-3"></i>
                            <div>
                                <h5 class="fw-bold mb-1">Kelola Users</h5>
                                <small class="text-muted">Pantau dan kelola pengguna sistem</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">
                            Lihat daftar pengguna, pantau aktivitas, dan kelola role pengguna dalam sistem.
                        </p>
                        <a href="users.php" class="btn btn-primary">
                            <i class="bi bi-people me-2"></i>Kelola Users
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-check fs-2 text-warning me-3"></i>
                            <div>
                                <h5 class="fw-bold mb-1">Pantau Jadwal</h5>
                                <small class="text-muted">Lihat jadwal makan pengguna</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">
                            Monitor jadwal makan yang dibuat oleh pengguna dan lihat statistik penggunaan aplikasi.
                        </p>
                        <a href="schedules.php" class="btn btn-warning">
                            <i class="bi bi-calendar me-2"></i>Lihat Jadwal
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-bar-chart-line fs-2 text-info me-3"></i>
                            <div>
                                <h5 class="fw-bold mb-1">Laporan & Analytics</h5>
                                <small class="text-muted">Analisis data penggunaan sistem</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">
                            Lihat laporan lengkap tentang penggunaan aplikasi, tren nutrisi, dan statistik pengguna.
                        </p>
                        <a href="reports.php" class="btn btn-info">
                            <i class="bi bi-graph-up me-2"></i>Lihat Laporan
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">Aksi Cepat</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="foods.php?action=add" class="btn btn-outline-success w-100">
                                    <i class="bi bi-plus-lg me-2"></i>Tambah Makanan
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="food_edit.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-pencil me-2"></i>Edit Makanan
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="schedules.php" class="btn btn-outline-warning w-100">
                                    <i class="bi bi-calendar me-2"></i>Lihat Semua Jadwal
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
