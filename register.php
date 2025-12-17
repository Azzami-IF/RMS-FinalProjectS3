<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;

if ($user) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .primarybg {
        background: linear-gradient(to right, #349250ff, #4cb292ff);
        color: white;
    }
</style>

<div class="primarybg d-flex justify-content-center align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3 text-center">Registrasi</h5>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <?php if ($_GET['error'] === 'password_mismatch'): ?>
                                    Password dan konfirmasi password tidak cocok
                                <?php else: ?>
                                    Email sudah terdaftar
                                <?php endif; ?>
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

                            <div class="mb-3">
                                <label>Konfirmasi Password</label>
                                <input type="password" name="confirm_password"
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
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
