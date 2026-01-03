<?php
require_once __DIR__ . '/classes/PageBootstrap.php';
$app = PageBootstrap::fromRootDir(__DIR__);
$user = $app->user();

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
            <p class="lead text-center mt-3 rms-muted-adaptive">Pantau kalori harian, catat menu, dapatkan rekomendasi makanan, lihat evaluasi nutrisi, kelola target, dan pantau berat badan Anda dalam satu aplikasi.</p>
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
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                        <div class="mb-3"><i class="bi bi-journal-plus fs-1 text-success"></i></div>
                        <h5 class="fw-bold">Catatan Menu Harian</h5>
                        <p class="rms-muted-adaptive">Catat menu makan harian Anda dengan mudah untuk memantau asupan kalori dan membangun kebiasaan makan yang lebih teratur.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                        <div class="mb-3"><i class="bi bi-egg-fried fs-1 text-success"></i></div>
                        <h5 class="fw-bold">Rekomendasi Makanan</h5>
                        <p class="rms-muted-adaptive">Cari dan temukan menu sehat berdasarkan kebutuhan nutrisi Anda. Rekomendasi terintegrasi dengan data makanan dan informasi nutrisi.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                        <div class="mb-3"><i class="bi bi-bar-chart-line fs-1 text-primary"></i></div>
                        <h5 class="fw-bold">Evaluasi Pola Makan</h5>
                        <p class="rms-muted-adaptive">Lihat grafik dan evaluasi nutrisi untuk memahami pola makan Anda, sehingga Anda bisa melakukan penyesuaian yang lebih tepat.</p>
                    </div>
                </div>
            </div>

            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                        <div class="mb-3"><i class="bi bi-flag-fill fs-1 text-success"></i></div>
                        <h5 class="fw-bold">Kelola Target</h5>
                        <p class="rms-muted-adaptive">Tetapkan target nutrisi dan target berat badan, lalu pantau progresnya agar tetap konsisten menuju tujuan Anda.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                        <div class="mb-3"><i class="bi bi-activity fs-1 text-info"></i></div>
                        <h5 class="fw-bold">Catatan Berat Badan</h5>
                        <p class="rms-muted-adaptive">Catat dan pantau perubahan berat badan Anda secara berkala untuk melihat tren dan progres kesehatan.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                        <div class="mb-3"><i class="bi bi-bell fs-1 text-warning"></i></div>
                        <h5 class="fw-bold">Notifikasi & Pengingat</h5>
                        <p class="rms-muted-adaptive">Dapatkan notifikasi pengingat dan informasi penting agar Anda tidak melewatkan jadwal, target, dan pembaruan di aplikasi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>