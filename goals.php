<?php
require_once 'includes/header.php';
require_once 'includes/auth_guard.php';
require_once 'config/database.php';
require_once 'classes/UserGoal.php';

$config = require 'config/env.php';
$db = (new Database($config))->getConnection();
$userGoalClass = new UserGoal($db);

$user = $_SESSION['user'];
$currentGoal = $userGoalClass->findActive($user['id']);

// Handle success/error messages
$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'Goal berhasil diperbarui!';
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
    $messageType = 'danger';
}
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Kelola Goals</h1>
                <p class="text-muted">Tetapkan dan kelola target nutrisi Anda</p>
            </div>
            <a href="profile.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Profile
            </a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-4" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <?php if ($currentGoal): ?>
                                <i class="bi bi-pencil me-2"></i>Edit Goal Saat Ini
                            <?php else: ?>
                                <i class="bi bi-plus-circle me-2"></i>Buat Goal Baru
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="process/goal.process.php">
                            <input type="hidden" name="action" value="save_goal">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tipe Goal *</label>
                                    <select name="goal_type" class="form-select" required>
                                        <option value="">Pilih tipe goal...</option>
                                        <option value="weight_loss" <?= ($currentGoal['goal_type'] ?? '') === 'weight_loss' ? 'selected' : '' ?>>Penurunan Berat Badan</option>
                                        <option value="weight_gain" <?= ($currentGoal['goal_type'] ?? '') === 'weight_gain' ? 'selected' : '' ?>>Peningkatan Berat Badan</option>
                                        <option value="maintain" <?= ($currentGoal['goal_type'] ?? '') === 'maintain' ? 'selected' : '' ?>>Pemeliharaan Berat Badan</option>
                                        <option value="muscle_gain" <?= ($currentGoal['goal_type'] ?? '') === 'muscle_gain' ? 'selected' : '' ?>>Peningkatan Massa Otot</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Target Kalori Harian *</label>
                                    <input type="number" name="daily_calorie_target" class="form-control"
                                           value="<?= $currentGoal['daily_calorie_target'] ?? 2000 ?>" min="1000" max="5000" required>
                                    <div class="form-text">Rekomendasi: 1500-2500 kcal</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Target Berat Badan (kg)</label>
                                    <input type="number" name="target_weight_kg" class="form-control"
                                           value="<?= $currentGoal['target_weight_kg'] ?? '' ?>" min="30" max="200" step="0.1">
                                    <div class="form-text">Opsional, kosongkan jika tidak ada target berat</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Target Tanggal</label>
                                    <input type="date" name="target_date" class="form-control"
                                           value="<?= $currentGoal['target_date'] ?? '' ?>">
                                    <div class="form-text">Opsional, kosongkan jika tidak ada deadline</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Perubahan Berat Mingguan (kg)</label>
                                    <input type="number" name="weekly_weight_change" class="form-control"
                                           value="<?= $currentGoal['weekly_weight_change'] ?? '' ?>" min="-2" max="2" step="0.1">
                                    <div class="form-text">Contoh: -0.5 (turun 0.5kg/minggu)</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Target Protein Harian (g)</label>
                                    <input type="number" name="daily_protein_target" class="form-control"
                                           value="<?= $currentGoal['daily_protein_target'] ?? '' ?>" min="0" max="500">
                                    <div class="form-text">Opsional, kosongkan jika tidak ada target</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Target Lemak Harian (g)</label>
                                    <input type="number" name="daily_fat_target" class="form-control"
                                           value="<?= $currentGoal['daily_fat_target'] ?? '' ?>" min="0" max="200">
                                    <div class="form-text">Opsional, kosongkan jika tidak ada target</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Target Karbohidrat Harian (g)</label>
                                    <input type="number" name="daily_carbs_target" class="form-control"
                                           value="<?= $currentGoal['daily_carbs_target'] ?? '' ?>" min="0" max="500">
                                    <div class="form-text">Opsional, kosongkan jika tidak ada target</div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <?php if ($currentGoal): ?>Update Goal<?php else: ?>Buat Goal<?php endif; ?>
                                </button>
                                <?php if ($currentGoal): ?>
                                <a href="process/goal.process.php?action=delete_goal" class="btn btn-outline-danger ms-2"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus goal ini?')">
                                    <i class="bi bi-trash me-2"></i>Hapus Goal
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Goal Tips -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-lightbulb text-warning me-2"></i>Tips Goal Setting
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="fw-bold">Penurunan Berat Badan:</h6>
                            <ul class="small mb-0">
                                <li>Defisit 500 kcal/hari = 0.5 kg/minggu</li>
                                <li>Target realistis: 0.5-1 kg/minggu</li>
                                <li>Fokus pada protein tinggi</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-bold">Peningkatan Berat Badan:</h6>
                            <ul class="small mb-0">
                                <li>Surplus 300-500 kcal/hari</li>
                                <li>Fokus pada karbohidrat kompleks</li>
                                <li>Sertakan olahraga resistance</li>
                            </ul>
                        </div>

                        <div>
                            <h6 class="fw-bold">Pemeliharaan:</h6>
                            <ul class="small small mb-0">
                                <li>Konsumsi sesuai TDEE</li>
                                <li>Monitor berat badan weekly</li>
                                <li>Jaga pola makan seimbang</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Current Progress -->
                <?php if ($currentGoal): ?>
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up text-success me-2"></i>Progress Saat Ini
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="progress" style="height: 20px;">
                                <?php
                                // Calculate progress (simplified example)
                                $progress = 65; // This would be calculated based on actual data
                                ?>
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: <?= $progress ?>%" aria-valuenow="<?= $progress ?>"
                                     aria-valuemin="0" aria-valuemax="100">
                                    <?= $progress ?>%
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">Progress menuju goal</small>
                        </div>

                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h6 class="mb-1">Kalori Hari Ini</h6>
                                    <span class="text-success fw-bold">1,850 kcal</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <h6 class="mb-1">Target</h6>
                                <span class="text-muted fw-bold"><?= $currentGoal['daily_calorie_target'] ?> kcal</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>