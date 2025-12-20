<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Food.php';
require_once __DIR__ . '/../classes/Admin/FoodsController.php';

use Admin\FoodsController;

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$controller = new FoodsController($db);
$data = $controller->getData();
$message = $controller->getMessage();
$messageType = $controller->getMessageType();
?>

<section class="py-5">
	<div class="container">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<div>
				<h1 class="fw-bold mb-1">Makanan</h1>
				<p class="text-muted mb-0">Kelola data makanan yang tersimpan di database.</p>
			</div>
			<a href="food_edit.php" class="btn btn-primary btn-sm">
				<i class="bi bi-plus-circle me-1"></i>Tambah Makanan
			</a>
		</div>

		<?php if (!empty($message)): ?>
			<div class="alert alert-<?= htmlspecialchars($messageType ?: 'info') ?> alert-dismissible fade show" role="alert">
				<?= htmlspecialchars($message) ?>
				<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
			</div>
		<?php endif; ?>

		<div class="card shadow-sm rounded-3">
			<div class="card-header rms-card-adaptive d-flex justify-content-between align-items-center">
				<h6 class="mb-0 fw-bold">Daftar Makanan</h6>
				<span class="badge bg-primary">Total: <?= count($data) ?> makanan</span>
			</div>
			<div class="card-body">
				<?php if (empty($data)): ?>
					<div class="alert alert-info mb-0">Belum ada data makanan.</div>
				<?php else: ?>
					<div class="table-responsive">
						<table class="table table-hover align-middle">
							<thead>
								<tr>
									<th style="width:70px;">ID</th>
									<th>Nama</th>
									<th style="width:120px;">Kalori</th>
									<th style="width:120px;">Protein</th>
									<th style="width:120px;">Lemak</th>
									<th style="width:120px;">Karbo</th>
									<th style="width:140px;">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($data as $food): ?>
									<tr>
										<td><?= (int)$food['id'] ?></td>
										<td>
											<strong><?= htmlspecialchars($food['name'] ?? '') ?></strong>
											<?php if (!empty($food['description'])): ?>
												<div class="small text-muted text-truncate" style="max-width:520px;">
													<?= htmlspecialchars($food['description']) ?>
												</div>
											<?php endif; ?>
										</td>
										<td><?= isset($food['calories']) ? round((float)$food['calories'], 1) : '-' ?> kcal</td>
										<td><?= isset($food['protein']) ? round((float)$food['protein'], 1) : '-' ?> g</td>
										<td><?= isset($food['fat']) ? round((float)$food['fat'], 1) : '-' ?> g</td>
										<td><?= isset($food['carbs']) ? round((float)$food['carbs'], 1) : '-' ?> g</td>
										<td>
											<div class="btn-group btn-group-sm">
												<a href="food_edit.php?id=<?= (int)$food['id'] ?>" class="btn btn-outline-warning" title="Ubah">
													<i class="bi bi-pencil"></i>
												</a>
												<form method="post" action="../process/food.process.php" onsubmit="return confirm('Hapus makanan ini?')">
													<input type="hidden" name="action" value="delete">
													<input type="hidden" name="id" value="<?= (int)$food['id'] ?>">
													<button type="submit" class="btn btn-outline-danger" title="Hapus">
														<i class="bi bi-trash"></i>
													</button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
