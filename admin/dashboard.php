<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();
?>

<h4 class="mb-4">Dashboard Admin</h4>

<div class="row g-4">

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Kelola Makanan</h6>
                <p class="text-muted">CRUD data menu sehat</p>
                <a href="foods.php" class="btn btn-success btn-sm">
                    Kelola
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
