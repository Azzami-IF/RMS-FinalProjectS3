<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

$stmt = $db->prepare(
    "SELECT * FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
$stmt->execute([$_SESSION['user']['id']]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h4 class="mb-4">Riwayat Notifikasi</h4>

<?php if (empty($data)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted">
            <i class="bi bi-bell-slash fs-1 mb-3"></i>
            <p>Belum ada notifikasi</p>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($data as $n): ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-title mb-1"><?= $n['title'] ?></h6>
                                <small class="text-muted">
                                    <?= date('d M Y H:i', strtotime($n['created_at'])) ?>
                                </small>
                            </div>
                            <span class="badge bg-<?= $n['status'] === 'unread' ? 'primary' : ($n['status'] === 'read' ? 'secondary' : ($n['status'] === 'sent' ? 'success' : 'danger')) ?>">
                                <?= $n['status'] === 'unread' ? 'Baru' : ($n['status'] === 'read' ? 'Dibaca' : ($n['status'] === 'sent' ? 'Terkirim' : 'Gagal')) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
