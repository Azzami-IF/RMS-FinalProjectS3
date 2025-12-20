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
        background: linear-gradient(135deg, #349250ff 0%, #2d7a43ff 25%, #4cb292ff 75%, #5ab5a8ff 100%);
        background-attachment: fixed;
        position: relative;
        overflow: hidden;
    }
    
    .primarybg::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(0, 0, 0, 0.05) 0%, transparent 50%);
        pointer-events: none;
    }
    
    .primarybg {
        color: white;
    }
</style>

<div class="primarybg d-flex justify-content-center align-items-center vh-100 position-relative">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-5 col-lg-4">
                <div class="card shadow-sm rounded-4 border-0 rms-card-adaptive">
                    <div class="card-body p-4">
                        <div class="text-center mb-3">
                            <h4 class="fw-bold text-success mb-0">Login</h4>
                        </div>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                Nama/Email atau password salah
                            </div>
                        <?php elseif (isset($_GET['message'])): ?>
                            <?php if ($_GET['message'] === 'password_changed'): ?>
                                <div class="alert alert-success">
                                    Password berhasil diubah. Silakan login dengan password baru.
                                </div>
                            <?php elseif ($_GET['message'] === 'account_deleted'): ?>
                                <div class="alert alert-info">
                                    Akun Anda telah berhasil dihapus.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <form method="post" action="process/login.process.php">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama atau Email</label>
                                <input type="text" name="login_identifier"
                                       class="form-control rounded-3" required>
                            </div>


                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="loginPassword" class="form-control rounded-start-3" required>
                                    <button class="btn btn-outline-secondary border-1 rounded-end-3" type="button" id="toggleLoginPassword" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var btn = document.getElementById('toggleLoginPassword');
                                var input = document.getElementById('loginPassword');
                                btn.addEventListener('click', function() {
                                    if (input.type === 'password') {
                                        input.type = 'text';
                                        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
                                    } else {
                                        input.type = 'password';
                                        btn.innerHTML = '<i class="bi bi-eye"></i>';
                                    }
                                });
                            });
                            </script>

                            <button class="btn btn-success w-100 py-2 fw-semibold rounded-3">
                                Masuk Sekarang
                            </button>
                        </form>

                        <hr class="my-4">

                        <div class="text-center small">
                            <p class="text-muted mb-0">Belum punya akun? <a href="register.php" class="fw-semibold text-success text-decoration-none">Daftar di sini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
