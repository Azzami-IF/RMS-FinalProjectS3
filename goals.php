<?php
require_once __DIR__ . '/classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__);
$app->requireUser();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/UserGoal.php';
require_once __DIR__ . '/classes/GoalsPageController.php';
require_once __DIR__ . '/classes/AnalyticsService.php';

$user = $app->user();
$controller = new GoalsPageController($db, $user);
$currentGoal = $controller->getCurrentGoal();
$message = $controller->getMessage();
$messageType = $controller->getMessageType();

// Beginner-friendly: show whether there is enough data to calculate progress
$stmt = $db->prepare(
    "SELECT COUNT(DISTINCT schedule_date) FROM schedules WHERE user_id = ? AND schedule_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
);
$stmt->execute([(int)$user['id']]);
$recentScheduleDays = (int)$stmt->fetchColumn();

$stmt = $db->prepare(
    "SELECT COUNT(*) FROM weight_logs WHERE user_id = ? AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);
$stmt->execute([(int)$user['id']]);
$recentWeightLogs30d = (int)$stmt->fetchColumn();

// Refresh progress/evaluation using latest stats (requires goal progress fields in DB)
if ($currentGoal) {
    $analytics = new AnalyticsService($db);
    $userGoalModel = new UserGoal($db);
    $stats = $analytics->goalProgress((int)$user['id']);
    $stmt = $db->prepare("SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY logged_at DESC LIMIT 1");
    $stmt->execute([(int)$user['id']]);
    $latestWeight = $stmt->fetchColumn();
    $userGoalModel->evaluateAndUpdateProgress((int)$user['id'], $stats, $latestWeight !== false ? (float)$latestWeight : null);
    $currentGoal = $userGoalModel->findActive((int)$user['id']);
}
?>



<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Target</h4>
                <div class="text-muted small">Atur target dan pantau progres secara berkala.</div>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType ?: 'info') ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 col-lg-5 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-flag me-2"></i><?= $currentGoal ? 'Ubah Target' : 'Buat Target Baru' ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-secondary small">
                            <div class="fw-semibold mb-1"><i class="bi bi-lightning-charge me-1"></i>Tips</div>
                            <ol class="mb-0 ps-3">
                                <li><span class="fw-semibold">Wajib:</span> pilih tipe target dan isi target kalori.</li>
                                <li><span class="fw-semibold">Opsional:</span> isi target makro dan target berat agar evaluasi lebih spesifik.</li>
                                <li>Agar progres bergerak, catat menu harian di <a href="schedules.php" class="link-dark">Catatan Menu</a>.</li>
                                <li>Jika target terkait berat badan, catat berat di <a href="weight_log.php" class="link-dark">Catatan Berat</a>.</li>
                            </ol>
                        </div>

                        <form method="post" action="process/goal.process.php">
                            <input type="hidden" name="action" value="save_goal">
                            <div class="mb-3">
                                <label class="form-label">Tipe Target *</label>
                                <select name="goal_type" class="form-select" required>
                                    <option value="">Pilih tipe target...</option>
                                    <option value="weight_loss" <?= ($currentGoal['goal_type'] ?? '') === 'weight_loss' ? 'selected' : '' ?>>Penurunan Berat Badan</option>
                                    <option value="weight_gain" <?= ($currentGoal['goal_type'] ?? '') === 'weight_gain' ? 'selected' : '' ?>>Peningkatan Berat Badan</option>
                                    <option value="maintain" <?= ($currentGoal['goal_type'] ?? '') === 'maintain' ? 'selected' : '' ?>>Pemeliharaan Berat Badan</option>
                                    <option value="muscle_gain" <?= ($currentGoal['goal_type'] ?? '') === 'muscle_gain' ? 'selected' : '' ?>>Peningkatan Massa Otot</option>
                                </select>
                                <div class="form-text">Pilih target utama agar evaluasi lebih relevan.</div>
                                <div id="goalTypeExample" class="form-text text-muted"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Target Kalori Harian *</label>
                                <input type="number" name="daily_calorie_target" class="form-control" placeholder="Contoh: 2000" value="<?= $currentGoal['daily_calorie_target'] ?? '' ?>" min="1000" max="5000" required>
                                <div class="form-text">Digunakan untuk menghitung progres dan evaluasi mingguan.</div>
                                <div id="calorieTargetExample" class="form-text text-muted"></div>
                            </div>

                            <hr class="my-3">
                            <div class="fw-semibold mb-2">Target Makronutrien (Opsional)</div>
                            <div class="mb-3">
                                <label class="form-label">Target Protein (g)</label>
                                <input type="number" name="daily_protein_target" class="form-control" placeholder="Contoh: 100" value="<?= $currentGoal['daily_protein_target'] ?? '' ?>" min="0" max="500">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Target Lemak (g)</label>
                                <input type="number" name="daily_fat_target" class="form-control" placeholder="Contoh: 60" value="<?= $currentGoal['daily_fat_target'] ?? '' ?>" min="0" max="200">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Target Karbohidrat (g)</label>
                                <input type="number" name="daily_carbs_target" class="form-control" placeholder="Contoh: 250" value="<?= $currentGoal['daily_carbs_target'] ?? '' ?>" min="0" max="500">
                            </div>

                            <hr class="my-3">
                            <div class="fw-semibold mb-2">Target Berat Badan (Opsional)</div>
                            <div class="mb-3">
                                <label class="form-label">Target Berat Badan (kg)</label>
                                <input type="number" name="target_weight_kg" class="form-control" placeholder="Contoh: 70" value="<?= $currentGoal['target_weight_kg'] ?? '' ?>" min="30" max="200" step="0.1">
                                <div class="form-text">Isi jika target Anda terkait berat badan.</div>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Target Tanggal</label>
                                    <input type="date" name="target_date" class="form-control" value="<?= $currentGoal['target_date'] ?? '' ?>" min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Perubahan/Minggu (kg)</label>
                                    <input type="number" name="weekly_weight_change" class="form-control" placeholder="Contoh: 0.5" step="0.1" value="<?= $currentGoal['weekly_weight_change'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle me-2"></i><?= $currentGoal ? 'Simpan Perubahan' : 'Simpan Target' ?>
                                </button>
                                <?php if ($currentGoal): ?>
                                <a href="process/goal.process.php?action=delete_goal" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus target ini?')">
                                    <i class="bi bi-trash me-2"></i>Hapus Target
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-7 mb-4">
                <?php if ($currentGoal): ?>
                <div class="card shadow-sm h-100">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up me-2"></i>Progres & Target Nutrisi
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-muted small">Progres gabungan (kalori + makro + berat badan jika ada)</div>
                            <div class="small text-muted">
                                Target kalori: <span class="fw-semibold"><?= (int)($currentGoal['daily_calorie_target'] ?? 0) ?></span> kcal
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small text-muted">
                                Data 7 hari terakhir: <span class="fw-semibold"><?= $recentScheduleDays ?></span> hari tercatat
                            </div>
                            <?php if (($currentGoal['target_weight_kg'] ?? null) !== null): ?>
                                <div class="small text-muted">Catatan berat 30 hari: <span class="fw-semibold"><?= $recentWeightLogs30d ?></span></div>
                            <?php endif; ?>
                        </div>

                        <div class="progress mb-4" style="height: 28px;">
                            <?php $progress = (int)max(0, min(100, round((float)($currentGoal['progress'] ?? 0)))); ?>
                            <div class="progress-bar bg-success fs-6" role="progressbar"
                                 style="width: <?= $progress ?>%" aria-valuenow="<?= $progress ?>"
                                 aria-valuemin="0" aria-valuemax="100">
                                <?= $progress ?>%
                            </div>
                        </div>

                        <?php if ($progress <= 0 && $recentScheduleDays === 0): ?>
                            <div class="alert alert-warning small">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Progres masih 0 karena belum ada catatan menu dalam 7 hari terakhir. Tambahkan menu harian di
                                <a href="schedules.php" class="alert-link">Catatan Menu</a> agar progres bisa dihitung.
                            </div>
                        <?php endif; ?>

                        <?php if ($progress <= 0 && ($currentGoal['target_weight_kg'] ?? null) !== null && $recentWeightLogs30d === 0): ?>
                            <div class="alert alert-warning small">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Target Anda memakai target berat badan, tetapi belum ada catatan berat (30 hari terakhir).
                                Isi di <a href="weight_log.php" class="alert-link">Catatan Berat</a> agar evaluasi lebih akurat.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($currentGoal['evaluation'])): ?>
                            <div class="alert alert-primary">
                                <i class="bi bi-chat-left-text me-2"></i>
                                <?= htmlspecialchars($currentGoal['evaluation']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="row g-3 text-center">
                            <div class="col-6 col-md-3">
                                <div class="small text-muted">Protein</div>
                                <div class="fw-bold fs-6"><?= $currentGoal['daily_protein_target'] ?? '-' ?> <span class="text-muted">g</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="small text-muted">Lemak</div>
                                <div class="fw-bold fs-6"><?= $currentGoal['daily_fat_target'] ?? '-' ?> <span class="text-muted">g</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="small text-muted">Karbo</div>
                                <div class="fw-bold fs-6"><?= $currentGoal['daily_carbs_target'] ?? '-' ?> <span class="text-muted">g</span></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="small text-muted">Berat Badan</div>
                                <div class="fw-bold fs-6"><?= $currentGoal['target_weight_kg'] ?? '-' ?> <span class="text-muted">kg</span></div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <span>Pastikan target nutrisi dan berat badan realistis dan sesuai kebutuhan Anda.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$currentGoal): ?>
                    <div class="card shadow-sm h-100">
                        <div class="card-header rms-card-adaptive">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-compass me-2"></i>Mulai dari sini</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-muted small mb-3">
                                Anda belum memiliki target aktif. Buat target di sebelah kiri, lalu lakukan dua kebiasaan ini agar progres muncul.
                            </div>
                            <div class="alert alert-info small mb-3">
                                <i class="bi bi-check2-circle me-2"></i>
                                Catat menu harian di <a class="alert-link" href="schedules.php">Catatan Menu</a>.
                            </div>
                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-check2-circle me-2"></i>
                                Jika target berat badan, catat berat di <a class="alert-link" href="weight_log.php">Catatan Berat</a>.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const goalTypeSelect = document.querySelector('select[name="goal_type"]');
    const exampleEl = document.getElementById('goalTypeExample');
    const calorieExampleEl = document.getElementById('calorieTargetExample');

    if (!goalTypeSelect || !exampleEl) return;

    const examples = {
        weight_loss: 'Contoh: defisit ringan dan konsisten. Isi target kalori sedikit di bawah kebutuhan harian. Jika memakai target berat, isi juga perubahan per minggu (mis. 0.3–0.7 kg).',
        weight_gain: 'Contoh: surplus kalori bertahap. Isi target kalori sedikit di atas kebutuhan harian. Jika memakai target berat, isi perubahan per minggu (mis. 0.2–0.5 kg).',
        maintain: 'Contoh: stabil. Isi target kalori mendekati kebutuhan harian. Target berat boleh diisi atau dikosongkan.',
        muscle_gain: 'Contoh: fokus protein + latihan. Isi target kalori sedikit surplus, dan isi target protein (mis. 90–140 g, sesuaikan kebutuhan).'
    };

    function updateExample() {
        const value = goalTypeSelect.value;
        exampleEl.textContent = value && examples[value] ? examples[value] : '';

        if (calorieExampleEl) {
            const caloriesHint = {
                weight_loss: 'Tips: mulai dari 1600–2200 kcal (sesuaikan kondisi). Utamakan konsisten, lalu evaluasi tiap minggu.',
                weight_gain: 'Tips: mulai dari 2200–2800 kcal. Naikkan bertahap kalau berat belum naik.',
                maintain: 'Tips: mulai dari 1800–2600 kcal. Targetnya stabil, bukan berubah cepat.',
                muscle_gain: 'Tips: mulai dari 2200–3000 kcal. Gabungkan dengan target protein dan latihan rutin.'
            };
            calorieExampleEl.textContent = value && caloriesHint[value] ? caloriesHint[value] : '';
        }
    }

    goalTypeSelect.addEventListener('change', updateExample);
    updateExample();
});
</script>

<?php require_once 'includes/footer.php'; ?>