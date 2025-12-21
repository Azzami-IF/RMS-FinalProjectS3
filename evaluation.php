<?php
require_once __DIR__ . '/classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__);
$app->requireUser();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/AnalyticsService.php';

$analytics = new AnalyticsService($db);

$userId = (int)$user['id'];

$totalCalories = $analytics->totalCalories($userId);
$totalDays     = $analytics->totalDays($userId);
?>


<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Evaluasi Pola Makan</h4>
    <a href="export/export_evaluation.php" class="btn btn-outline-success">Ekspor CSV</a>
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
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm rounded-3 h-100">
            <div class="card-header rms-card-adaptive">
                <h6 class="mb-0">Distribusi Nutrisi Total</h6>
            </div>
            <div class="card-body">
                <div class="w-100" style="position:relative;height:320px;">
                    <canvas id="nutritionChart"></canvas>
                </div>
                <div class="small text-muted mt-2">Persentase total protein, lemak, dan karbohidrat dari seluruh catatan makan Anda.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card shadow-sm rounded-3 h-100">
            <div class="card-header rms-card-adaptive">
                <h6 class="mb-0">Tren Kalori Harian</h6>
            </div>
            <div class="card-body">
                <div class="w-100" style="position:relative;height:320px;">
                    <canvas id="calorieChart"></canvas>
                </div>
                <div class="small text-muted mt-2">Visualisasi tren asupan kalori harian Anda.</div>
            </div>
        </div>
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
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const value = Number(ctx.parsed) || 0;
                                const pct = total > 0 ? (value / total) * 100 : 0;
                                return `${ctx.label}: ${value}g (${pct.toFixed(1)}%)`;
                            }
                        }
                    }
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
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Kalori' } },
                    x: { title: { display: true, text: 'Tanggal' } }
                },
                elements: {
                    line: { tension: 0.35, borderWidth: 2 },
                    point: { radius: 2, hoverRadius: 5 }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const y = ctx.parsed?.y;
                                return `Kalori: ${typeof y === 'number' ? Math.round(y) : y}`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
