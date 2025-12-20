<?php
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'classes/User.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$config = require 'config/env.php';
$db = (new Database($config))->getConnection();
$userClass = new User($db);
$user = $_SESSION['user'];

// Jika sudah pernah isi profil, hapus sesi dan redirect ke dashboard
if ($user['weight_kg'] && $user['height_cm'] && $user['date_of_birth']) {
    unset($_SESSION['wajib_profil']);
    header('Location: dashboard.php');
    exit;
}
// Set sesi wajib_profil agar dicek di halaman lain
$_SESSION['wajib_profil'] = true;

// Notifikasi jika redirect dari halaman lain
$message = '';
if (isset($_GET['error'])) {
    $message = 'Semua field wajib diisi!';
}
if (isset($_SESSION['notif_wajib_profil'])) {
    $message = 'Lengkapi data profil terlebih dahulu.';
    unset($_SESSION['notif_wajib_profil']);
}
?>
<section class="py-5">
    <div class="container">
        <h1 class="fw-bold mb-3">Lengkapi Profil Anda</h1>
        <p class="text-muted">Data ini wajib diisi untuk pengalaman terbaik dan analisis nutrisi yang akurat.</p>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?= $message ?></div>
        <?php endif; ?>
        <form method="post" action="process/profile_register.process.php" class="card p-4 shadow-sm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="date_of_birth" class="form-control" min="1900-01-01" max="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Pilih...</option>
                        <option value="male">Laki-laki</option>
                        <option value="female">Perempuan</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tinggi Badan (cm)</label>
                    <input type="number" name="height_cm" class="form-control" min="100" max="250" step="0.1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Berat Badan (kg)</label>
                    <input type="number" name="weight_kg" class="form-control" min="20" max="300" step="0.1" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Tingkat Aktivitas</label>
                <select name="activity_level" class="form-select" required>
                    <option value="sedentary">Sedentari (Jarang berolahraga)</option>
                    <option value="light">Ringan (Olahraga ringan 1-3x/minggu)</option>
                    <option value="moderate" selected>Sedang (Olahraga sedang 3-5x/minggu)</option>
                    <option value="active">Aktif (Olahraga berat 6-7x/minggu)</option>
                    <option value="very_active">Sangat Aktif (Olahraga sangat berat & pekerjaan fisik)</option>
                </select>
            </div>
            <button class="btn btn-success px-4">Simpan & Lanjutkan</button>
        </form>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
