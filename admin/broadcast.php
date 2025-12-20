<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

require_admin();

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();

$users = $db->query("SELECT id, name, email FROM users WHERE role='user' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$messageType = '';
if (isset($_GET['success']) && $_GET['success'] === 'broadcast_sent') {
    $count = (int)($_GET['count'] ?? 0);
    $message = $count > 0 ? ('Notifikasi berhasil dikirim ke ' . $count . ' pengguna.') : 'Notifikasi berhasil dikirim.';
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = 'Gagal mengirim notifikasi: ' . htmlspecialchars((string)$_GET['error']);
    $messageType = 'danger';
}
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Broadcast Notifikasi</h1>
                <p class="text-muted mb-0">Kirim notifikasi in-app ke satu pengguna atau semua pengguna.</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType ?: 'info') ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header rms-card-adaptive d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2"></i>Form Broadcast</h6>
                        <small class="text-muted">Notifikasi in-app</small>
                    </div>
                    <div class="card-body">
                        <form method="post" action="../process/broadcast.process.php">
                            <input type="hidden" name="action" value="broadcast">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Penerima</label>
                                    <select name="recipient" class="form-select" required>
                                        <option value="all">Semua Pengguna</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= (int)$u['id'] ?>">
                                                <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Pilih semua atau satu pengguna.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Judul</label>
                                    <input type="text" name="title" class="form-control" maxlength="150" required>
                                    <div class="form-text">Contoh: Informasi Sistem</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Pesan</label>
                                    <textarea name="message" class="form-control" rows="5" required></textarea>
                                    <div class="form-text">Pesan akan muncul di menu Notifikasi pengguna.</div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i>Kirim Notifikasi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm rounded-3 h-100">
                    <div class="card-header rms-card-adaptive">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2"></i>Catatan</h6>
                    </div>
                    <div class="card-body">
                        <ul class="small text-muted mb-0 ps-3">
                            <li>Broadcast ini hanya mengirim notifikasi <b>in-app</b>.</li>
                            <li>Judul dan pesan akan tampil di halaman Notifikasi user.</li>
                            <li>Gunakan bahasa yang singkat dan jelas.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
