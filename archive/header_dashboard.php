<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika belum login → redirect ke index
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

include "header.php";
?>

<!-- NAVBAR DASHBOARD -->
<nav class="navbar navbar-expand-lg navbar-dark primarybg">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">MyApp</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">

                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>

                <!-- NOTIFIKASI -->
                <li class="nav-item dropdown mx-2">
                    <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow" style="width: 300px;">
                        <li class="dropdown-header fw-semibold">Notifikasi</li>
                        <li><hr class="dropdown-divider"></li>

                        <li class="px-3 small text-muted">• Login berhasil</li>
                        <li class="px-3 small text-muted">• Edit profil</li>
                        <li class="px-3 small text-muted">• Notifikasi sistem</li>

                        <li><hr class="dropdown-divider"></li>
                        <li class="text-center">
                            <a class="small text-success text-decoration-none" href="#">Lihat Semua</a>
                        </li>
                    </ul>
                </li>

                <!-- PROFIL -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="ms-1">
                            <?php echo $_SESSION['login']; ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="#">Lihat Profil</a></li>
                        <li><a class="dropdown-item" href="#">Pengaturan Akun</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</nav>
