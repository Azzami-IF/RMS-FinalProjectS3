<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    /* Adaptive hero for dark mode */
    .hero-bg {
        background: var(--bs-body-bg, #f8f9fa);
        color: var(--bs-body-color, #212529);
        border-radius: 1.5rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: background 0.2s, color 0.2s;
    }
    @media (prefers-color-scheme: dark) {
        .hero-bg {
            background: #23272e;
            color: #e0e0e0;
        }
    }
</style>


<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show m-3 rms-alert-adaptive" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- HERO SECTION -->
<section class="py-5">
    <div class="container">
        <div class="hero-bg p-5 mb-4 text-center rms-hero-adaptive">
            <h1 class="fw-bold mb-3">Rekomendasi Makanan Sehat</h1>
            <div class="mt-4">
                <div class="rms-card-adaptive rounded shadow-sm d-inline-block p-4">
                    <i class="bi bi-apple fs-1 text-success"></i>
                    <div class="mt-2">
                        <i class="bi bi-graph-up text-primary fs-2 me-3"></i>
                        <i class="bi bi-calendar-check text-warning fs-2"></i>
                    </div>
                </div>
            </div>
            <p class="lead text-center mt-3 rms-muted-adaptive">Aplikasi untuk membantu Anda menjaga pola makan sehat dengan rekomendasi makanan dan tracking kalori harian.</p>
        </div>
    </div>
</section>

<!-- SECTION FITUR -->
<section class="py-5 rms-feature-section">
    <div class="container">
        <h3 class="fw-bold text-center mb-4 rms-title-adaptive">Fitur Program RMS Saat Ini</h3>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                    <div class="mb-3"><i class="bi bi-egg-fried fs-1 text-success"></i></div>
                    <h5 class="fw-bold">Rekomendasi Makanan Sehat</h5>
                    <p class="rms-muted-adaptive">Dapatkan rekomendasi makanan berbasis kebutuhan nutrisi, preferensi, dan tujuan kesehatan Anda. Sistem kami terintegrasi dengan database makanan dan API nutrisi terkini.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                    <div class="mb-3"><i class="bi bi-graph-up-arrow fs-1 text-primary"></i></div>
                    <h5 class="fw-bold">Tracking Kalori & Nutrisi</h5>
                    <p class="rms-muted-adaptive">Pantau asupan kalori, protein, karbohidrat, lemak, dan nutrisi penting lain setiap hari. Tersedia grafik analitik dan log berat badan untuk memantau progres Anda.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm rounded-4 p-4 h-100 border-0 rms-card-adaptive">
                    <div class="mb-3"><i class="bi bi-bell fs-1 text-warning"></i></div>
                    <h5 class="fw-bold">Notifikasi & Pengingat</h5>
                    <p class="rms-muted-adaptive">Dapatkan notifikasi pengingat menu sehat harian, target kalori, dan tips nutrisi langsung di aplikasi atau email Anda.</p>
                </div>
            </div>
        </div>
    </div>
</section>


<style>
    .rms-feature-section {
        background: var(--bs-body-bg, #fff);
        border-top: 1px solid var(--bs-card-border-color, #e0e0e0);
        transition: background 0.2s, border-color 0.2s;
    }
    .rms-card-adaptive {
        background: var(--bs-card-bg, #fff) !important;
        color: var(--bs-card-color, #212529) !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        transition: background 0.2s, color 0.2s;
    }
    .rms-muted-adaptive {
        color: var(--bs-text-muted, #6c757d) !important;
        transition: color 0.2s;
    }
    .rms-title-adaptive {
        color: var(--bs-body-color, #212529) !important;
        transition: color 0.2s;
    }
    .rms-hero-adaptive {
        background: var(--bs-body-bg, #f8f9fa) !important;
        color: var(--bs-body-color, #212529) !important;
        transition: background 0.2s, color 0.2s;
    }
    .rms-alert-adaptive {
        background: var(--bs-alert-bg, #f8d7da) !important;
        color: var(--bs-alert-color, #842029) !important;
        border-color: var(--bs-alert-border-color, #f5c2c7) !important;
        transition: background 0.2s, color 0.2s, border-color 0.2s;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
