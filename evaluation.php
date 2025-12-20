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


<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Evaluasi Pola Makan</h4>
    <a href="export/export_evaluation.php" class="btn btn-outline-success">Export CSV</a>
</div>

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

<div class="row g-4">
    <div class="col-12 col-lg-6 d-flex flex-column align-items-center">
        <h6 class="mb-2">Distribusi Nutrisi Total</h6>
        <div style="width:100%;max-width:370px;min-width:220px;">
            <canvas id="nutritionChart" style="aspect-ratio:1.1/1;"></canvas>
        </div>
        <div class="small text-muted mt-2">Persentase total protein, lemak, dan karbohidrat dari seluruh catatan makan Anda.</div>
    </div>
    <div class="col-12 col-lg-6 d-flex flex-column align-items-center">
        <h6 class="mb-2">Tren Kalori Harian</h6>
        <div style="width:100%;max-width:420px;min-width:220px;">
            <canvas id="calorieChart" style="aspect-ratio:1.8/1;"></canvas>
        </div>
        <div class="small text-muted mt-2">Visualisasi tren asupan kalori harian Anda.</div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch('charts/nutrition_chart.php')
    .then(res => res.json())
    .then(data => {
        const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
        if (total === 0) {
            document.getElementById('nutritionChart').replaceWith((() => {
                const d = document.createElement('div');
                d.className = 'alert alert-info mt-3';
                d.innerText = 'Belum ada data nutrisi untuk ditampilkan.';
                return d;
            })());
            return;
        }
        new Chart(document.getElementById('nutritionChart'), {
            type: 'pie',
            data: data,
            options: {
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed}g` } }
                }
            }
        });
    });
fetch('charts/calorie_chart.php')
    .then(res => res.json())
    .then(data => {
        new Chart(document.getElementById('calorieChart'), {
            type: 'line',
            data: data,
            options: {
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Kalori' } },
                    x: { title: { display: true, text: 'Tanggal' } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => `Kalori: ${ctx.parsed.y}` } }
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
