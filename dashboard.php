<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/AnalyticsService.php';

if (!$user || !$role || !in_array($role, ['user', 'admin'], true)) {
    header('Location: index.php');
    exit;
}

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

        <p class="lead text-muted">Peran Anda: <b><?php echo ucfirst($role); ?></b></p>

        <hr>

        <div class="row mt-4 g-4">

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Total Kalori Hari Ini</h5>
                        <p class="text-muted">Pantau kalori harian Anda.</p>
                        <h4 class="text-success"><?= $totalCalories ?> kcal</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Total Menu Dicatat</h5>
                        <p class="text-muted">Jumlah menu yang dicatat.</p>
                        <h4 class="text-success"><?= $totalMeals ?></h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Catatan Harian</h5>
                        <p class="text-muted">Catat menu harian Anda.</p>
                        <a href="schedules.php" class="btn btn-success">Catat Menu</a>
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
                        <h5 class="fw-bold">Kelola Target</h5>
                        <p class="text-muted">Tetapkan target nutrisi & berat badan.</p>
                        <a href="goals.php" class="btn btn-success">Kelola Target</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Catatan Berat Badan</h5>
                        <p class="text-muted">Pantau progres berat badan Anda.</p>
                        <a href="weight_log.php" class="btn btn-info">Lihat Catatan</a>
                    </div>
                </div>
            </div>

        </div>

        <!-- CHART SECTION -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header rms-card-adaptive">
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
