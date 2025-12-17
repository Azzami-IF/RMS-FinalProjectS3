<?php
require_once 'includes/header.php';
require_once 'includes/auth_guard.php';
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/UserGoal.php';

$config = require 'config/env.php';
$db = (new Database($config))->getConnection();
$userClass = new User($db);
$userGoalClass = new UserGoal($db);

$user = $_SESSION['user'];
$userData = $userClass->find($user['id']);
$userGoal = $userGoalClass->findActive($user['id']);

// Get user's statistics
$schedules = $db->prepare("SELECT COUNT(*) as total_schedules FROM schedules WHERE user_id = ?");
$schedules->execute([$user['id']]);
$scheduleStats = $schedules->fetch(PDO::FETCH_ASSOC);

$todaySchedules = $db->prepare("SELECT COUNT(*) as today_count FROM schedules WHERE user_id = ? AND schedule_date = CURDATE()");
$todaySchedules->execute([$user['id']]);
$todayStats = $todaySchedules->fetch(PDO::FETCH_ASSOC);

// Handle success/error messages
$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $message = 'Operasi berhasil!';
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'password_incorrect':
            $message = 'Password yang Anda masukkan salah!';
            $messageType = 'danger';
            break;
        default:
            $message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
            $messageType = 'danger';
    }
}
?>

<section class="py-5">
    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-4" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif ?>

        <div class="row">
            <div class="col-md-4">
                <!-- Profile Card -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                                <?= strtoupper(substr($userData['name'], 0, 1)) ?>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($userData['name']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($userData['email']) ?></p>
                        <span class="badge bg-<?= $userData['role'] === 'admin' ? 'danger' : 'success' ?>">
                            <?= $userData['role'] === 'admin' ? 'Admin' : 'User' ?>
                        </span>

                        <div class="mt-3">
                            <a href="profile_edit.php" class="btn btn-primary btn-sm me-2">
                                <i class="bi bi-pencil me-1"></i>Edit Profile
                            </a>
                            <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                                <i class="bi bi-trash me-1"></i>Hapus Akun
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">Statistik</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="mb-2">
                                    <i class="bi bi-calendar-check fs-2 text-primary"></i>
                                </div>
                                <h4 class="mb-1"><?= $scheduleStats['total_schedules'] ?? 0 ?></h4>
                                <small class="text-muted">Total Jadwal</small>
                            </div>
                            <div class="col-6">
                                <div class="mb-2">
                                    <i class="bi bi-calendar-day fs-2 text-success"></i>
                                </div>
                                <h4 class="mb-1"><?= $todayStats['today_count'] ?? 0 ?></h4>
                                <small class="text-muted">Jadwal Hari Ini</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Personal Information -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Informasi Pribadi</h6>
                        <a href="profile_edit.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Nama Lengkap</strong><br>
                                <span class="text-muted"><?= htmlspecialchars($userData['name']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Email</strong><br>
                                <span class="text-muted"><?= htmlspecialchars($userData['email']) ?></span>
                            </div>
                            <?php if ($userData['phone']): ?>
                            <div class="col-md-6">
                                <strong>Telepon</strong><br>
                                <span class="text-muted"><?= htmlspecialchars($userData['phone']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($userData['date_of_birth']): ?>
                            <div class="col-md-6">
                                <strong>Tanggal Lahir</strong><br>
                                <span class="text-muted"><?= date('d M Y', strtotime($userData['date_of_birth'])) ?> (<?= date('Y') - date('Y', strtotime($userData['date_of_birth'])) ?> tahun)</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($userData['gender']): ?>
                            <div class="col-md-6">
                                <strong>Jenis Kelamin</strong><br>
                                <span class="text-muted">
                                    <?php
                                    switch($userData['gender']) {
                                        case 'male': echo 'Laki-laki'; break;
                                        case 'female': echo 'Perempuan'; break;
                                        default: echo 'Lainnya';
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <strong>Tanggal Bergabung</strong><br>
                                <span class="text-muted"><?= date('d M Y', strtotime($userData['created_at'])) ?></span>
                            </div>
                            <?php if ($userData['last_login']): ?>
                            <div class="col-md-6">
                                <strong>Login Terakhir</strong><br>
                                <span class="text-muted"><?= date('d M Y H:i', strtotime($userData['last_login'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Physical Information -->
                <?php if ($userData['height_cm'] || $userData['weight_kg']): ?>
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Data Fisik</h6>
                        <a href="profile_edit.php#physical" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php if ($userData['height_cm']): ?>
                            <div class="col-md-4">
                                <strong>Tinggi Badan</strong><br>
                                <span class="text-muted"><?= $userData['height_cm'] ?> cm</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($userData['weight_kg']): ?>
                            <div class="col-md-4">
                                <strong>Berat Badan</strong><br>
                                <span class="text-muted"><?= $userData['weight_kg'] ?> kg</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($userData['height_cm'] && $userData['weight_kg']): ?>
                            <div class="col-md-4">
                                <strong>BMI</strong><br>
                                <span class="text-muted">
                                    <?php
                                    $bmi = round($userData['weight_kg'] / (($userData['height_cm']/100) ** 2), 1);
                                    $bmiCategory = '';
                                    if ($bmi < 18.5) $bmiCategory = 'Underweight';
                                    elseif ($bmi < 25) $bmiCategory = 'Normal';
                                    elseif ($bmi < 30) $bmiCategory = 'Overweight';
                                    else $bmiCategory = 'Obese';
                                    echo $bmi . ' (' . $bmiCategory . ')';
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <strong>Target Kalori Harian</strong><br>
                                <span class="text-muted"><?= $userData['daily_calorie_goal'] ?> kcal</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Tingkat Aktivitas</strong><br>
                                <span class="text-muted">
                                    <?php
                                    switch($userData['activity_level']) {
                                        case 'sedentary': echo 'Sedentary (Jarang olahraga)'; break;
                                        case 'light': echo 'Light (Olahraga ringan)'; break;
                                        case 'moderate': echo 'Moderate (Olahraga sedang)'; break;
                                        case 'active': echo 'Active (Olahraga berat)'; break;
                                        case 'very_active': echo 'Very Active (Olahraga sangat berat)'; break;
                                        default: echo 'Moderate';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Goals Section -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Target & Goals</h6>
                        <a href="goals.php" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-target me-1"></i>Kelola Goals
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($userGoal): ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Tipe Goal</strong><br>
                                <span class="text-muted">
                                    <?php
                                    switch($userGoal['goal_type']) {
                                        case 'weight_loss': echo 'Penurunan Berat Badan'; break;
                                        case 'weight_gain': echo 'Peningkatan Berat Badan'; break;
                                        case 'maintain': echo 'Pemeliharaan Berat Badan'; break;
                                        case 'muscle_gain': echo 'Peningkatan Massa Otot'; break;
                                        default: echo ucfirst($userGoal['goal_type']);
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Target Kalori Harian</strong><br>
                                <span class="text-muted"><?= $userGoal['daily_calorie_target'] ?> kcal</span>
                            </div>
                            <?php if ($userGoal['target_weight_kg']): ?>
                            <div class="col-md-6">
                                <strong>Target Berat Badan</strong><br>
                                <span class="text-muted"><?= $userGoal['target_weight_kg'] ?> kg</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($userGoal['target_date']): ?>
                            <div class="col-md-6">
                                <strong>Target Tanggal</strong><br>
                                <span class="text-muted"><?= date('d M Y', strtotime($userGoal['target_date'])) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($userGoal['weekly_weight_change']): ?>
                            <div class="col-md-6">
                                <strong>Perubahan Mingguan</strong><br>
                                <span class="text-muted">
                                    <?php
                                    $change = $userGoal['weekly_weight_change'];
                                    echo ($change > 0 ? '+' : '') . $change . ' kg/minggu';
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <strong>Target Makronutrien</strong><br>
                                <span class="text-muted">
                                    <?php if ($userGoal['daily_protein_target']): ?>
                                        Protein: <?= $userGoal['daily_protein_target'] ?>g
                                    <?php endif; ?>
                                    <?php if ($userGoal['daily_fat_target']): ?>
                                        | Lemak: <?= $userGoal['daily_fat_target'] ?>g
                                    <?php endif; ?>
                                    <?php if ($userGoal['daily_carbs_target']): ?>
                                        | Karbo: <?= $userGoal['daily_carbs_target'] ?>g
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-target fs-1 text-muted mb-3"></i>
                            <h6 class="text-muted">Belum ada goal yang ditetapkan</h6>
                            <p class="text-muted mb-3">Tetapkan goal untuk tracking progress yang lebih baik</p>
                            <a href="goals.php" class="btn btn-success">
                                <i class="bi bi-plus-circle me-2"></i>Buat Goal Pertama
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold">Aktivitas Terbaru</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $recentSchedules = $db->prepare("
                            SELECT s.*, f.name as food_name
                            FROM schedules s
                            JOIN foods f ON s.food_id = f.id
                            WHERE s.user_id = ?
                            ORDER BY s.created_at DESC
                            LIMIT 5
                        ");
                        $recentSchedules->execute([$user['id']]);
                        $activities = $recentSchedules->fetchAll(PDO::FETCH_ASSOC);

                        if (count($activities) > 0):
                        ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($activities as $activity): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($activity['food_name']) ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Dijadwalkan untuk: <?= date('d M Y', strtotime($activity['schedule_date'])) ?>
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('d M', strtotime($activity['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted mb-0">Belum ada aktivitas jadwal makan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Perhatian!</strong> Tindakan ini tidak dapat dibatalkan.
                </div>
                <p>Apakah Anda yakin ingin menghapus akun Anda? Semua data Anda akan hilang permanen, termasuk:</p>
                <ul>
                    <li>Jadwal makan</li>
                    <li>Riwayat nutrisi</li>
                    <li>Data profil</li>
                </ul>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Masukkan password untuk konfirmasi:</label>
                    <input type="password" class="form-control" id="confirmPassword" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="deleteAccount()">Hapus Akun</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function deleteAccount() {
    var password = document.getElementById('confirmPassword').value;
    if (!password) {
        alert('Masukkan password untuk konfirmasi!');
        return;
    }

    if (confirm('Apakah Anda benar-benar yakin ingin menghapus akun ini? Tindakan ini tidak dapat dibatalkan!')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process/profile.process.php';

        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_account';
        form.appendChild(actionInput);

        var passwordInput = document.createElement('input');
        passwordInput.type = 'hidden';
        passwordInput.name = 'password';
        passwordInput.value = password;
        form.appendChild(passwordInput);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>