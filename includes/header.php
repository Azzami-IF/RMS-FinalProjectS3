<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek sesi wajib_profil, redirect jika perlu
if (isset($_SESSION['wajib_profil']) && $_SESSION['wajib_profil'] && basename($_SERVER['PHP_SELF']) !== 'profile_register.php') {
    $_SESSION['notif_wajib_profil'] = true;
    header('Location: profile_register.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? null;

// Determine path prefix based on current directory
$path_prefix = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : '';
// Language feature removed
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <script>
    // Prevent flashbang: set data-theme ASAP
    (function() {
        try {
            var theme = localStorage.getItem('theme');
            if (!theme) {
                // Try to get from cookies if not in localStorage
                var m = document.cookie.match(/(?:^|; )theme=([^;]*)/);
                if (m) theme = decodeURIComponent(m[1]);
            }
            if (!theme || theme === 'auto') {
                // Use system preference if auto or not set
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', theme);
        } catch(e) {}
    })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="color-scheme" content="light dark">
    <?php
    // Inject user theme preference for JS
    $themePref = 'light';
    if (isset($user) && $user) {
        require_once __DIR__ . '/../classes/UserPreferences.php';
        $userPrefs = new UserPreferences($db);
        $themePref = $userPrefs->get($user['id'], 'theme', 'light');
    }
    ?>
    <script>
    window.userPreferences = window.userPreferences || {};
    window.userPreferences.theme = "<?= htmlspecialchars($themePref) ?>";
    </script>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.css">

    <title>Rekomendasi Makanan Sehat</title>

    <style>
        .primarybg {
            background: linear-gradient(to right, #349250, #4cb292);
        }
        
        .navbar-nav .nav-link {
            display: flex;
            align-items: center;
            min-height: 40px;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
        }
        
        .navbar-nav .dropdown-toggle {
            border: none;
            background: none;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
        }
        
        .navbar-nav .nav-link:focus,
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0.375rem;
        }
        
        .navbar-nav .dropdown-toggle:focus,
        .navbar-nav .dropdown-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0.375rem;
        }
        
        .navbar-brand {
            margin-right: 2rem;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark primarybg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $path_prefix; ?><?php echo $user ? 'home.php' : 'index.php'; ?>">
            RMS
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <?php if (!$user): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $path_prefix; ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $path_prefix; ?>login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $path_prefix; ?>register.php">Registrasi</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $path_prefix; ?>home.php">Home</a>
                    </li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $path_prefix; ?>dashboard.php">
                                Dashboard
                            </a>
                        </li>

                        <!-- NOTIFIKASI -->
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
                        $stmt->execute([$_SESSION['user']['id']]);
                        $unreadCount = $stmt->fetch()['count'];

                        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute([$_SESSION['user']['id']]);
                        $recentNotifications = $stmt->fetchAll();
                        ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative notif-bell" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-bell fs-5"></i>
                                <?php if ($unreadCount > 0): ?>
                                <span id="notif-dot" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="width:10px;height:10px;z-index:2;"></span>
                                <?php endif; ?>
                            <script>
                            // Hilangkan dot notifikasi saat dropdown dibuka dan tandai notifikasi sudah dilihat (AJAX)
                            document.addEventListener('DOMContentLoaded', function() {
                                var bell = document.querySelector('.notif-bell');
                                if (bell) {
                                    bell.addEventListener('show.bs.dropdown', function() {
                                        var dot = document.getElementById('notif-dot');
                                        if (dot) dot.style.display = 'none';
                                        // AJAX: tandai semua notifikasi sebagai sudah dilihat
                                        fetch('<?php echo $path_prefix; ?>process/mark_notifications_read.php', {method: 'POST', credentials: 'same-origin'});
                                    });
                                }
                            });
                            </script>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow" style="width: 320px; max-height: 380px; overflow-y: auto;">
                                <li class="dropdown-header fw-semibold">Notifikasi</li>
                                <li><hr class="dropdown-divider"></li>

                                <?php if (empty($recentNotifications)): ?>
                                <li class="px-3 small text-muted">Belum ada notifikasi</li>
                                <?php else: ?>
                                <?php foreach ($recentNotifications as $notif): ?>
                                <li class="px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small text-truncate" style="max-width:180px;">
                                                <?php echo htmlspecialchars($notif['title']); ?>
                                            </div>
                                            <div class="small text-muted text-truncate" style="max-width:180px;">
                                                <?php echo htmlspecialchars(strip_tags(substr($notif['message'], 0, 40))); ?><?php if(strlen($notif['message'])>40): ?>...<?php endif; ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?php echo date('d M H:i', strtotime($notif['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php endforeach; ?>
                                <?php endif; ?>

                                <li class="text-center">
                                    <a class="small text-success text-decoration-none" href="<?php echo $path_prefix; ?>notifications.php">Lihat Semua</a>
                                </li>
                            </ul>
                        </li>

                        <!-- ADMIN PROFILE DROPDOWN -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($_SESSION['user']['name'], 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li class="dropdown-header">
                                    <strong><?php echo htmlspecialchars($_SESSION['user']['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></small>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>profile.php">
                                        <i class="bi bi-person me-2"></i>Lihat Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>profile_edit.php">
                                        <i class="bi bi-pencil me-2"></i>Edit Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>dashboard.php">
                                        <i class="bi bi-house me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>settings.php">
                                        <i class="bi bi-gear me-2"></i>Pengaturan
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>admin/dashboard.php">
                                        <i class="bi bi-house me-2"></i>Dashboard Admin
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo $path_prefix; ?>logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $path_prefix; ?>dashboard.php">
                                Dashboard
                            </a>
                        </li>

                        <!-- NOTIFIKASI -->
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
                        $stmt->execute([$_SESSION['user']['id']]);
                        $unreadCount = $stmt->fetch()['count'];

                        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute([$_SESSION['user']['id']]);
                        $recentNotifications = $stmt->fetchAll();
                        ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-bell fs-5"></i>
                                <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unreadCount; ?>
                                </span>
                                <?php endif; ?>
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow" style="width: 320px; max-height: 380px; overflow-y: auto;">
                                <li class="dropdown-header fw-semibold">Notifikasi</li>
                                <li><hr class="dropdown-divider"></li>

                                <?php if (empty($recentNotifications)): ?>
                                <li class="px-3 small text-muted">Belum ada notifikasi</li>
                                <?php else: ?>
                                <?php foreach ($recentNotifications as $notif): ?>
                                <li class="px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small text-truncate" style="max-width:180px;">
                                                <?php echo htmlspecialchars($notif['title']); ?>
                                            </div>
                                            <div class="small text-muted text-truncate" style="max-width:180px;">
                                                <?php echo htmlspecialchars(strip_tags(substr($notif['message'], 0, 40))); ?><?php if(strlen($notif['message'])>40): ?>...<?php endif; ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?php echo date('d M H:i', strtotime($notif['created_at'])); ?>
                                            </div>
                                        </div>
                                        <?php if ($notif['status'] === 'unread'): ?>
                                        <span class="badge bg-primary ms-2">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php endforeach; ?>
                                <?php endif; ?>

                                <li class="text-center">
                                    <a class="small text-success text-decoration-none" href="<?php echo $path_prefix; ?>notifications.php">Lihat Semua</a>
                                </li>
                            </ul>
                        </li>

                        <!-- PROFILE DROPDOWN -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($_SESSION['user']['name'], 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li class="dropdown-header">
                                    <strong><?php echo htmlspecialchars($_SESSION['user']['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></small>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>profile.php">
                                        <i class="bi bi-person me-2"></i>Lihat Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>profile_edit.php">
                                        <i class="bi bi-pencil me-2"></i>Edit Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>dashboard.php">
                                        <i class="bi bi-house me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>settings.php">
                                        <i class="bi bi-gear me-2"></i>Pengaturan
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo $path_prefix; ?>logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
<script>
// Hilangkan dot notifikasi saat dropdown dibuka dan tandai notifikasi sudah dilihat (AJAX)
document.addEventListener('DOMContentLoaded', function() {
    var bells = document.querySelectorAll('.notif-bell');
    bells.forEach(function(bell) {
        bell.addEventListener('show.bs.dropdown', function() {
            var dot = document.getElementById('notif-dot');
            if (dot) dot.style.display = 'none';
            fetch('<?php echo $path_prefix; ?>process/mark_notifications_read.php', {method: 'POST', credentials: 'same-origin'});
        });
    });
});
</script>

<div class="container mt-4">
