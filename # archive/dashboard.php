<?php
session_start();
include "header_dashboard.php";
?>

<section class="py-5">
    <div class="container">

        <h1 class="fw-bold mb-3">
            Selamat Datang, 
            <span class="text-success"><?php echo $_SESSION['login']; ?></span>
        </h1>

        <p class="lead text-muted">Role Anda: <b><?php echo $_SESSION['role']; ?></b></p>

        <hr>

        <div class="row mt-4">

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Profil Akun</h5>
                        <p class="text-muted">Lihat informasi akun Anda.</p>
                        <a href="#" class="btn btn-success">Buka</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Aktivitas Login</h5>
                        <p class="text-muted">Riwayat login Anda.</p>
                        <a href="#" class="btn btn-success">Cek</a>
                    </div>
                </div>
            </div>

            <?php if ($_SESSION['role'] === "admin") { ?>
            <div class="col-md-4">
                <div class="card shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Kelola User</h5>
                        <p class="text-muted">Manajemen akun dan roles.</p>
                        <a href="admin.php" class="btn btn-success">Kelola</a>
                    </div>
                </div>
            </div>
            <?php } ?>

        </div>
    </div>
</section>

<footer class="text-center text-muted py-3 border-top">
    <small>Sample Dashboard</small>
</footer>

</body>
</html>
