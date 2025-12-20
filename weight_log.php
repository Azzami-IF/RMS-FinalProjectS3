<?php
require_once 'includes/header.php';
require_once 'includes/auth_guard.php';
require_once 'config/database.php';
require_once 'classes/WeightLog.php';
require_once 'classes/WeightLogPageController.php';

$config = require 'config/env.php';
$db = (new Database($config))->getConnection();
$user = $_SESSION['user'];
$controller = new WeightLogPageController($db, $user);
$recentLogs = $controller->getRecentLogs();
$message = $controller->getMessage();
$messageType = $controller->getMessageType();
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Log Berat Badan</h1>
                <p class="text-muted">Pantau progress berat badan Anda</p>
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
            <div class="col-lg-4">
                <!-- Add Weight Log -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Log Baru
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="process/weight.process.php">
                            <input type="hidden" name="action" value="add_log">

                            <div class="mb-3">
                                <label class="form-label">Berat Badan (kg) *</label>
                                <input type="number" name="weight_kg" class="form-control"
                                       step="0.1" min="30" max="200" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tanggal *</label>
                                <input type="date" name="logged_at" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Persentase Lemak Tubuh (%)</label>
                                <input type="number" name="body_fat_percentage" class="form-control"
                                       step="0.1" min="3" max="50">
                                <div class="form-text">Opsional</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Massa Otot (kg)</label>
                                <input type="number" name="muscle_mass_kg" class="form-control"
                                       step="0.1" min="10" max="100">
                                <div class="form-text">Opsional</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Opsional: bagaimana perasaan Anda, aktivitas hari ini, dll."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-save me-2"></i>Simpan Log
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Weight Stats -->
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up me-2"></i>Statistik
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $stats = $controller->getStats();
                        if ($stats):
                        ?>
                        <div class="row text-center">
                            <div class="col-6">
                                <h6 class="mb-1">Berat Terkini</h6>
                                <span class="text-primary fw-bold"><?= $stats['current_weight'] ?> kg</span>
                            </div>
                            <div class="col-6">
                                <h6 class="mb-1">Perubahan</h6>
                                <span class="text-<?= $stats['change_30d'] >= 0 ? 'danger' : 'success' ?> fw-bold">
                                    <?= $stats['change_30d'] > 0 ? '+' : '' ?><?= $stats['change_30d'] ?> kg
                                </span>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted">Berat Tertinggi</small>
                                <br><span class="fw-bold"><?= $stats['max_weight'] ?> kg</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Berat Terendah</small>
                                <br><span class="fw-bold"><?= $stats['min_weight'] ?> kg</span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-info-circle fs-2 text-muted mb-2"></i>
                            <p class="text-muted small mb-0">Belum ada data berat badan</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <!-- Weight Chart -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up me-2"></i>Grafik Berat Badan
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="weightChart" height="80"></canvas>
                    </div>
                </div>

                <!-- Weight Log History -->
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-clock-history me-2"></i>Riwayat Log
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentLogs) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Berat (kg)</th>
                                        <th>Lemak Tubuh (%)</th>
                                        <th>Massa Otot (kg)</th>
                                        <th>Catatan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($log['logged_at'])) ?></td>
                                        <td><strong><?= $log['weight_kg'] ?> kg</strong></td>
                                        <td><?= $log['body_fat_percentage'] ? $log['body_fat_percentage'] . '%' : '-' ?></td>
                                        <td><?= $log['muscle_mass_kg'] ? $log['muscle_mass_kg'] . ' kg' : '-' ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $log['notes'] ? htmlspecialchars(substr($log['notes'], 0, 30)) . (strlen($log['notes']) > 30 ? '...' : '') : '-' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="deleteLog(<?= $log['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-data fs-1 text-muted mb-3"></i>
                            <h6 class="text-muted">Belum ada log berat badan</h6>
                            <p class="text-muted">Mulai catat berat badan Anda untuk tracking progress</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rms-card-adaptive">
            <div class="modal-header rms-card-adaptive">
                <h5 class="modal-title">Hapus Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body rms-card-adaptive">
                <p>Apakah Anda yakin ingin menghapus log berat badan ini?</p>
                <p class="text-muted small">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer rms-card-adaptive">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/Chart.js"></script>
<script>
let deleteLogId = null;

function deleteLog(logId) {
    deleteLogId = logId;
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (deleteLogId) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process/weight.process.php';

        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_log';
        form.appendChild(actionInput);

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'log_id';
        idInput.value = deleteLogId;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
    }
});

// Load weight chart
fetch('charts/weight_chart.php')
  .then(res => res.json())
  .then(data => {
    new Chart(document.getElementById('weightChart'), {
      type: 'line',
      data: data,
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: false,
            title: {
              display: true,
              text: 'Berat Badan (kg)'
            }
          }
        }
      }
    });
  })
  .catch(error => {
    console.error('Error loading weight chart:', error);
    document.getElementById('weightChart').parentElement.innerHTML =
      '<div class="alert alert-info">Belum ada data berat badan untuk ditampilkan.</div>';
  });
</script>

<?php require_once 'includes/footer.php'; ?>