<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$user = $_SESSION['user'] ?? null;

if ($user) {
    if (isset($_SESSION['wajib_profil']) && $_SESSION['wajib_profil']) {
        header('Location: profile_register.php');
        exit;
    } else {
        header('Location: dashboard.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

// Handle messages
$message = '';
$messageType = '';

if (isset($_GET['message'])) {
    if ($_GET['message'] === 'account_deleted') {
        $message = 'Akun Anda telah berhasil dihapus. Terima kasih telah menggunakan RMS.';
        $messageType = 'info';
    }
}
?>

<style>
    .primarybg {
        background: linear-gradient(to right, #349250ff, #4cb292ff);
        color: white;
    }
</style>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show m-3" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

    <!-- HERO SECTION -->
    <section class="py-5">
        <div class="container">
            <h1 class="fw-bold mb-3 text-center">Rekomendasi Makanan Sehat</h1>
            <div class="mt-4 text-center">
                <div class="rms-card-adaptive rounded shadow-sm d-inline-block p-4">
                    <i class="bi bi-apple fs-1 text-success"></i>
                    <div class="mt-2">
                        <i class="bi bi-graph-up text-primary fs-2 me-3"></i>
                        <i class="bi bi-calendar-check text-warning fs-2"></i>
                    </div>
                </div>
            </div>
            <p class="lead text-muted text-center mt-3">Aplikasi untuk membantu Anda menjaga pola makan sehat dengan rekomendasi makanan dan tracking kalori harian.</p>
            <div class="mt-4 text-center">
                <a href="login.php" class="btn btn-success btn-lg me-2">Login</a>
                <a href="register.php" class="btn btn-outline-success btn-lg">Daftar</a>
            </div>
        </div>
    </section>

    <!-- SECTION FITUR -->
    <section class="py-5 bg-white border-top">
        <div class="container">
            <h3 class="fw-bold text-center mb-4">Fitur Program RMS Saat Ini</h3>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0">
                        <div class="mb-3"><i class="bi bi-egg-fried fs-1 text-success"></i></div>
                        <h5 class="fw-bold">Rekomendasi Makanan Sehat</h5>
                        <p class="text-muted">Dapatkan rekomendasi makanan berbasis kebutuhan nutrisi, preferensi, dan tujuan kesehatan Anda. Sistem kami terintegrasi dengan database makanan dan API nutrisi terkini.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0">
                        <div class="mb-3"><i class="bi bi-graph-up-arrow fs-1 text-primary"></i></div>
                        <h5 class="fw-bold">Tracking Kalori & Nutrisi</h5>
                        <p class="text-muted">Pantau asupan kalori, protein, karbohidrat, lemak, dan nutrisi penting lain setiap hari. Tersedia grafik analitik dan log berat badan untuk memantau progres Anda.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0">
                        <div class="mb-3"><i class="bi bi-calendar-check fs-1 text-warning"></i></div>
                        <h5 class="fw-bold">Catatan & Notifikasi Makan</h5>
                        <p class="text-muted">Catat makan harian, dapatkan notifikasi pengingat, dan kelola pola makan lebih disiplin. Fitur notifikasi tersedia via aplikasi dan email.</p>
                    </div>
                </div>
            </div>
            <div class="row text-center mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                        <div class="mb-3"><i class="bi bi-bar-chart-steps fs-1 text-success"></i></div>
                        <h5 class="fw-bold">Evaluasi & Laporan Nutrisi</h5>
                        <p class="text-muted">Dapatkan evaluasi otomatis dari pola makan Anda, laporan mingguan, dan insight untuk perbaikan pola hidup sehat. Semua data dapat diekspor untuk kebutuhan pribadi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="text-center text-muted py-3 border-top rms-card-adaptive">
        <small>Rekomendasi Makanan Sehat - Aplikasi Pola Makan Seimbang</small>
    </footer>

<?php require_once __DIR__ . '/includes/footer.php'; ?>