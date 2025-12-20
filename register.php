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
                            <h4 class="fw-bold text-success mb-0">Registrasi</h4>
                        </div>

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
                                <label class="form-label fw-semibold">Nama</label>
                                <input type="text" name="name"
                                       class="form-control rounded-3" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email"
                                       class="form-control rounded-3" required>
                            </div>


                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="regPassword" class="form-control rounded-start-3" required>
                                    <button class="btn btn-outline-secondary border-1 rounded-end-3" type="button" id="toggleRegPassword" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Konfirmasi Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control rounded-start-3" required>
                                    <button class="btn btn-outline-secondary border-1 rounded-end-3" type="button" id="toggleRegConfirmPassword" tabindex="-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var btn1 = document.getElementById('toggleRegPassword');
                                var input1 = document.getElementById('regPassword');
                                btn1.addEventListener('click', function() {
                                    if (input1.type === 'password') {
                                        input1.type = 'text';
                                        btn1.innerHTML = '<i class="bi bi-eye-slash"></i>';
                                    } else {
                                        input1.type = 'password';
                                        btn1.innerHTML = '<i class="bi bi-eye"></i>';
                                    }
                                });
                                var btn2 = document.getElementById('toggleRegConfirmPassword');
                                var input2 = document.getElementById('regConfirmPassword');
                                btn2.addEventListener('click', function() {
                                    if (input2.type === 'password') {
                                        input2.type = 'text';
                                        btn2.innerHTML = '<i class="bi bi-eye-slash"></i>';
                                    } else {
                                        input2.type = 'password';
                                        btn2.innerHTML = '<i class="bi bi-eye"></i>';
                                    }
                                });
                            });
                            </script>

                            <button class="btn btn-success w-100 py-2 fw-semibold rounded-3">
                                Daftar Sekarang
                            </button>
                        </form>

                        <hr class="my-4">

                        <div class="text-center small">
                            <p class="text-muted mb-0">Sudah punya akun? <a href="login.php" class="fw-semibold text-success text-decoration-none">Login di sini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
