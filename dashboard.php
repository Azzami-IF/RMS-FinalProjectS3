<?php
require_once __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? null;

if (!$user || !in_array($role, ['user', 'admin'])) {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/AnalyticsService.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

$analytics = new AnalyticsService($db);

$totalCalories = $analytics->todayCalories($user['id']);
$totalMeals = $analytics->totalMeals($user['id']);
?>

<section class="py-5">
    <div class="container">

        <h1 class="fw-bold mb-3">
            Selamat Datang, 
            <span class="text-success"><?= htmlspecialchars($user['name']) ?></span>
        </h1>

        <p class="lead text-muted">Role Anda: <b><?php echo ucfirst($role); ?></b></p>

        <hr>

        <div class="row mt-4 g-4">

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Total Kalori Hari Ini</h5>
                        <p class="text-muted">Tracking kalori harian Anda.</p>
                        <h4 class="text-success"><?= $totalCalories ?> kcal</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Total Menu Dijadwalkan</h5>
                        <p class="text-muted">Jumlah menu yang dijadwalkan.</p>
                        <h4 class="text-success"><?= $totalMeals ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Jadwal Makan</h5>
                        <p class="text-muted">Atur menu sehat harian Anda.</p>
                        <a href="schedules.php" class="btn btn-success">Atur Jadwal</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Rekomendasi Makanan</h5>
                        <p class="text-muted">Cari menu sehat dari sistem.</p>
                        <a href="recommendation.php" class="btn btn-primary">Cari Makanan</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Evaluasi Pola Makan</h5>
                        <p class="text-muted">Lihat grafik & analisis nutrisi.</p>
                        <a href="evaluation.php" class="btn btn-warning">Lihat Evaluasi</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Kelola Goals</h5>
                        <p class="text-muted">Tetapkan target nutrisi & berat badan.</p>
                        <a href="goals.php" class="btn btn-success">Kelola Goals</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Log Berat Badan</h5>
                        <p class="text-muted">Pantau progress berat badan Anda.</p>
                        <a href="weight_log.php" class="btn btn-info">Log Berat</a>
                    </div>
                </div>
            </div>

        </div>

        <!-- CHART SECTION -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Grafik Kalori Harian</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="calorieChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script src="assets/js/Chart.js"></script>
<script>
fetch('charts/calorie_chart.php')
  .then(res => res.json())
  .then(data => {
    new Chart(document.getElementById('calorieChart'), {
      type: 'line',
      data: data,
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Kalori'
            }
          }
        }
      }
    });
  })
  .catch(error => {
    console.error('Error loading chart:', error);
    document.getElementById('calorieChart').parentElement.innerHTML = 
      '<div class="alert alert-info">Belum ada data kalori untuk ditampilkan.</div>';
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
