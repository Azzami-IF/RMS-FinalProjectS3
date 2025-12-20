<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Admin/ReportAdminController.php';

use Admin\ReportAdminController;

require_admin();
$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$controller = new ReportAdminController($db);
// OOP controller usage
$stats = $controller->getStats();
$userCount = $stats['userCount'] ?? 0;
$adminCount = $stats['adminCount'] ?? 0;
$foodCount = $stats['foodCount'] ?? 0;
$scheduleCount = $stats['scheduleCount'] ?? 0;
$notificationCount = $stats['notificationCount'] ?? 0;
$topFoods = $controller->getTopFoods();
$recentSchedules = $controller->getRecentSchedules();
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Laporan & Analytics</h1>
                <p class="text-muted">Analisis data penggunaan sistem RMS</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
        </div>

        <!-- System Overview -->
        <div class="row g-4 mb-5">
            <div class="col-md-12">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-bar-chart-line me-2"></i>System Overview
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="bi bi-people-fill fs-2 text-success mb-2"></i>
                                    <h4 class="text-success"><?= $userCount ?></h4>
                                    <small class="text-muted">Users</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="bi bi-shield-check fs-2 text-danger mb-2"></i>
                                    <h4 class="text-danger"><?= $adminCount ?></h4>
                                    <small class="text-muted">Admins</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="bi bi-egg-fried fs-2 text-primary mb-2"></i>
                                    <h4 class="text-primary"><?= $foodCount ?></h4>
                                    <small class="text-muted">Foods</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="bi bi-calendar-check fs-2 text-warning mb-2"></i>
                                    <h4 class="text-warning"><?= $scheduleCount ?></h4>
                                    <small class="text-muted">Schedules</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="bi bi-bell fs-2 text-info mb-2"></i>
                                    <h4 class="text-info"><?= $notificationCount ?></h4>
                                    <small class="text-muted">Notifications</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="bi bi-activity fs-2 text-secondary mb-2"></i>
                                    <h4 class="text-secondary">
                                        <?= $scheduleCount > 0 ? round($scheduleCount / max($userCount, 1), 1) : 0 ?>
                                    </h4>
                                    <small class="text-muted">Avg per User</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Top Foods -->
            <div class="col-md-6">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-trophy me-2"></i>Top 5 Makanan Terpopuler
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topFoods)): ?>
                            <p class="text-muted text-center">Belum ada data catatan makan</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topFoods as $index => $food): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary me-2">#<?= $index + 1 ?></span>
                                        <strong><?= htmlspecialchars($food['name']) ?></strong>
                                    </div>
                                    <span class="badge bg-success rounded-pill">
                                        <?= $food['usage_count'] ?> kali
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-md-6">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentSchedules)): ?>
                            <p class="text-muted text-center">Belum ada aktivitas</p>
                        <?php else: ?>
                            <div class="timeline">
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
                                                | Quantity: <?= $schedule['quantity'] ?>
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



<?php require_once __DIR__ . '/../includes/footer.php'; ?>