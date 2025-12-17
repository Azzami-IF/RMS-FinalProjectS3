<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/AnalyticsService.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

$analytics = new AnalyticsService($db);

$totalCalories = $analytics->todayCalories($user['id']);
$totalMeals = $analytics->totalMeals($user['id']);

if (!$user || $role !== 'mahasiswa') {
    header('Location: login.php');
    exit;
}
?>

<div class="row">
    <div class="col-md-12">
        <h4 class="fw-bold mb-4">
            Selamat datang, <?= htmlspecialchars($user['name']) ?>
        </h4>
    </div>
</div>

<div class="row g-4">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Kalori Hari Ini</h6>
                    <h4><?= $totalCalories ?> kcal</h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Menu Dijadwalkan</h6>
                    <h4><?= $totalMeals ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Jadwal Makan</h6>
                <p class="text-muted">Atur menu sehat harian Anda</p>
                <a href="schedule.php" class="btn btn-success btn-sm">
                    Atur Jadwal
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Rekomendasi Makanan</h6>
                <p class="text-muted">Cari menu sehat dari sistem</p>
                <a href="recommendation.php" class="btn btn-primary btn-sm">
                    Cari Makanan
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Evaluasi</h6>
                <p class="text-muted">Lihat grafik & analisis</p>
                <a href="evaluation.php" class="btn btn-warning btn-sm">
                    Lihat Evaluasi
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
