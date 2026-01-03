<?php
require_once __DIR__ . '/../classes/PageBootstrap.php';

$app = PageBootstrap::requireAdmin(__DIR__ . '/..');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/Admin/ReportAdminController.php';

use Admin\ReportAdminController;

$db = $app->db();
$controller = new ReportAdminController($db);
// Penggunaan controller (OOP)
$stats = $controller->getStats();
$userCount = $stats['userCount'] ?? 0;
$adminCount = $stats['adminCount'] ?? 0;
$foodCount = $stats['foodCount'] ?? 0;
$scheduleCount = $stats['scheduleCount'] ?? 0;
$notificationCount = $stats['notificationCount'] ?? 0;
$topFoods = $controller->getTopFoods();
$recentSchedules = $controller->getRecentSchedules();
$scheduleTrend = $controller->getScheduleTrend();

function rms_formatPortionCount($value): string {
    $v = (float)$value;
    if (abs($v - round($v)) < 0.000001) {
        return (string)(int)round($v);
    }
    return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
}
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Laporan & Analitik</h1>
                <p class="text-muted">Analisis data penggunaan sistem RMS</p>
            </div>
        </div>

        <!-- System Overview -->
        <div class="row g-4 mb-5">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-bar-chart-line me-2"></i>Ringkasan Sistem
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <div class="p-3 rms-card-adaptive rounded-3">
                                    <i class="bi bi-people-fill fs-2 text-success mb-2"></i>
                                    <h4 class="text-success"><?= $userCount ?></h4>
                                    <small class="text-muted">Pengguna</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 rms-card-adaptive rounded-3">
                                    <i class="bi bi-shield-check fs-2 text-danger mb-2"></i>
                                    <h4 class="text-danger"><?= $adminCount ?></h4>
                                    <small class="text-muted">Admin</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 rms-card-adaptive rounded-3">
                                    <i class="bi bi-egg-fried fs-2 text-primary mb-2"></i>
                                    <h4 class="text-primary"><?= $foodCount ?></h4>
                                    <small class="text-muted">Makanan</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 rms-card-adaptive rounded-3">
                                    <i class="bi bi-calendar-check fs-2 text-warning mb-2"></i>
                                    <h4 class="text-warning"><?= $scheduleCount ?></h4>
                                    <small class="text-muted">Catatan</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 rms-card-adaptive rounded-3">
                                    <i class="bi bi-bell fs-2 text-info mb-2"></i>
                                    <h4 class="text-info"><?= $notificationCount ?></h4>
                                    <small class="text-muted">Notifikasi</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 rms-card-adaptive rounded-3">
                                    <i class="bi bi-activity fs-2 text-secondary mb-2"></i>
                                    <h4 class="text-secondary">
                                        <?= $scheduleCount > 0 ? round($scheduleCount / max($userCount, 1), 1) : 0 ?>
                                    </h4>
                                    <small class="text-muted">Rata-rata per Pengguna</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left column: Trend + Top Foods (stacked) -->
            <div class="col-lg-6">
                <div class="d-flex flex-column gap-4">
                    <div class="card shadow-sm rounded-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-calendar-week me-2"></i>Trend Catatan (7 Hari)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="report-chart-box">
                                <canvas id="scheduleTrendChart" aria-label="Chart trend catatan" role="img"></canvas>
                            </div>
                            <div class="small text-muted mt-2">Jumlah catatan makan per hari (berdasarkan tanggal catatan).</div>
                        </div>
                    </div>

                    <div class="card shadow-sm rounded-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-trophy me-2"></i>Top 5 Makanan Terpopuler
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($topFoods)): ?>
                                <p class="text-muted text-center mb-0">Belum ada data catatan makan</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush report-scroll report-scroll--one">
                                    <?php $rank = 1; ?>
                                    <?php foreach ($topFoods as $food): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary me-2">#<?= $rank ?></span>
                                            <strong><?= htmlspecialchars($food['name']) ?></strong>
                                        </div>
                                        <span class="badge bg-success rounded-pill">
                                            <?= htmlspecialchars(rms_formatPortionCount($food['usage_count'] ?? 0)) ?> kali
                                        </span>
                                    </div>
                                    <?php $rank++; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: Activities (tall, scrollable) -->
            <div class="col-lg-6">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentSchedules)): ?>
                            <p class="text-muted text-center mb-0">Belum ada aktivitas</p>
                        <?php else: ?>
                            <div class="timeline report-scroll report-scroll--two">
                                <?php foreach ($recentSchedules as $schedule): ?>
                                <div class="timeline-item mb-3">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <small class="text-muted">
                                            <?= date('d M Y H:i', strtotime($schedule['created_at'])) ?>
                                        </small>
                                        <p class="mb-1">
                                            <strong><?= htmlspecialchars($schedule['user_name']) ?></strong>
                                            mencatat <strong><?= htmlspecialchars($schedule['food_name']) ?></strong>
                                        </p>
                                        <small class="text-muted">
                                            Tanggal: <?= date('d M Y', strtotime($schedule['schedule_date'])) ?>
                                            <?php if ($schedule['quantity'] > 1): ?>
                                                | Porsi: <?= $schedule['quantity'] ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        </div>
    </div>
</section>

<style>
.report-chart-box {
    height: 280px;
}

.report-scroll {
    overflow-y: auto;
}

.report-scroll--one {
    max-height: 280px;
}

/* Make the Activities list visually match the stacked left column (280 + 280 + gap) */
.report-scroll--two {
    max-height: calc(280px + 280px + 1.5rem);
    padding-right: 6px;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-left: 15px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-marker::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.timeline:not(:last-child)::after {
    content: '';
    position: absolute;
    left: -16px;
    top: 17px;
    bottom: -10px;
    width: 2px;
    background: #dee2e6;
}
</style>

<script>
// Charts for admin reports (Chart.js is loaded by includes/footer.php)
window.addEventListener('load', function() {
    if (!window.Chart) return;

    const css = getComputedStyle(document.documentElement);
    const bsPrimary = (css.getPropertyValue('--bs-primary') || '').trim() || '#0d6efd';
    const bsInfo = (css.getPropertyValue('--bs-info') || '').trim() || '#0dcaf0';
    const bsBorder = (css.getPropertyValue('--bs-border-color') || '').trim() || '#dee2e6';
    const bsBody = (css.getPropertyValue('--bs-body-color') || '').trim() || '#212529';

    const trendLabels = <?= json_encode($scheduleTrend['labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const trendCounts = <?= json_encode($scheduleTrend['counts'] ?? [], JSON_UNESCAPED_UNICODE) ?>;

    const trendEl = document.getElementById('scheduleTrendChart');
    if (trendEl && trendLabels.length) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Catatan',
                    data: trendCounts,
                    borderColor: bsPrimary,
                    backgroundColor: bsInfo,
                    tension: 0.25,
                    fill: false,
                    pointRadius: 3,
                    pointBackgroundColor: bsPrimary,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true },
                },
                scales: {
                    x: {
                        ticks: { color: bsBody },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: bsBody, precision: 0 },
                        grid: { color: bsBorder },
                    }
                }
            }
        });
    }
});
</script>



<?php require_once __DIR__ . '/../includes/footer.php'; ?>