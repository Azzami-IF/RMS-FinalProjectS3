<?php
require_once __DIR__ . '/includes/header.php';

if ($user) {
    header('Location: dashboard.php');
    exit;
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-3 text-center">Registrasi</h5>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        Email sudah terdaftar
                    </div>
                <?php endif; ?>

                <form method="post" action="process/register.process.php">
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" name="name"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password"
                               class="form-control" required>
                    </div>

                    <button class="btn btn-success w-100">
                        Daftar
                    </button>
                </form>

                <div class="mt-3 text-center">
                    Sudah punya akun?
                    <a href="login.php">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
