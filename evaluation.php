<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/AnalyticsService.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();
$analytics = new AnalyticsService($db);

$userId = $_SESSION['user']['id'];

$totalCalories = $analytics->totalCalories($userId);
$totalDays     = $analytics->totalDays($userId);
?>

<h4 class="mb-4">Evaluasi Pola Makan</h4>

<!-- ANALYTICS CARDS -->
<div class="row mb-4">

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Total Kalori</h6>
                <h4><?= $totalCalories ?> kcal</h4>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Total Hari Tercatat</h6>
                <h4><?= $totalDays ?> hari</h4>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Rata-rata Kalori / Hari</h6>
                <h4>
                    <?= $totalDays > 0 ? round($totalCalories / $totalDays) : 0 ?>
                    kcal
                </h4>
            </div>
        </div>
    </div>

</div>

<!-- GRAFIK -->
<div class="row">
    <div class="col-md-6">
        <h6 class="mb-2">Distribusi Nutrisi</h6>
        <canvas id="nutritionChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch('charts/nutrition_chart.php')
  .then(res => res.json())
  .then(data => {
    new Chart(document.getElementById('nutritionChart'), {
      type: 'pie',
      data: data
    });
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
