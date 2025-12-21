
<?php
require_once __DIR__ . '/../classes/PageBootstrap.php';

$app = PageBootstrap::requireAdmin(__DIR__ . '/..');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Admin/UserAdminController.php';

use Admin\UserAdminController;
$db = $app->db();
$controller = new UserAdminController($db);
$users = $controller->getUsers();
$message = $controller->getMessage();
$messageType = $controller->getMessageType();
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Kelola Pengguna</h1>
                <p class="text-muted">Pantau dan kelola pengguna sistem RMS</p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm rounded-3">
            <div class="card-header rms-card-adaptive d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Daftar Pengguna</h6>
                <span class="badge bg-primary fs-6">Total: <?= count($users) ?> pengguna</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($u['name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'success' ?>">
                                        <?= $u['role'] === 'admin' ? 'Admin' : 'Pengguna' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $u['is_active'] ? 'Aktif' : 'Non-aktif' ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info btn-sm" onclick="viewUser(<?= $u['id'] ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm" onclick="editUser(<?= $u['id'] ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="post" action="../process/user.process.php" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button class="btn btn-outline-<?= $u['is_active'] ? 'secondary' : 'success' ?> btn-sm"
                                                    onclick="return confirm('<?= $u['is_active'] ? 'Non-aktifkan' : 'Aktifkan' ?> pengguna ini?')">
                                                <i class="bi bi-<?= $u['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                                            </button>
                                        </form>
                                        <?php if ($u['role'] !== 'admin'): ?>
                                        <form method="post" action="../process/user.process.php" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Hapus pengguna ini secara permanen?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function viewUser(id) {
    // Redirect ke halaman detail pengguna
    window.location.href = 'user_detail.php?id=' + id;
}

function editUser(id) {
    // Redirect ke halaman ubah pengguna
    window.location.href = 'user_edit.php?id=' + id;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>