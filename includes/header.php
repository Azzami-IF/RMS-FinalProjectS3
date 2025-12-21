<?php

$rootDir = __DIR__ . '/..';
require_once $rootDir . '/classes/PageBootstrap.php';
$app = PageBootstrap::fromRootDir($rootDir);

// Cek sesi wajib_profil, redirect jika perlu
if (isset($_SESSION['wajib_profil']) && $_SESSION['wajib_profil'] && basename((string)($_SERVER['PHP_SELF'] ?? '')) !== 'profile_register.php') {
    $_SESSION['notif_wajib_profil'] = true;
    header('Location: profile_register.php');
    exit;
}
$config = $app->config();
$db = $app->db();
$user = $app->user();
$role = $app->role();
$path_prefix = $app->pathPrefix();
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
<body>

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
                        <a class="nav-link" href="<?php echo $path_prefix; ?>index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $path_prefix; ?>login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $path_prefix; ?>register.php">Registrasi</a>
                    </li>
                <?php else: ?>
                    <?php
                    $userId = (int)($user['id'] ?? 0);
                    $userName = (string)($user['name'] ?? '');
                    $userEmail = (string)($user['email'] ?? '');
                    $userInitial = $userName !== '' ? strtoupper(substr($userName, 0, 1)) : '?';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $path_prefix; ?>home.php">Beranda</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $path_prefix; ?>dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <!-- NOTIFIKASI (User + Admin) -->
                    <?php
                    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND channel = 'in_app' AND status = 'unread'");
                    $stmt->execute([$userId]);
                    $unreadCount = (int)$stmt->fetchColumn();

                    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND channel = 'in_app' ORDER BY created_at DESC LIMIT 5");
                    $stmt->execute([$userId]);
                    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $notifTypeLabels = [
                        'menu' => 'Menu',
                        'goal' => 'Target',
                        'reminder' => 'Pengingat',
                        'info' => 'Info',
                    ];

                    $notifTypeIcons = [
                        'menu' => 'bi-egg-fried text-success',
                        'goal' => 'bi-flag-fill text-success',
                        'reminder' => 'bi-bell-fill text-success',
                        'info' => 'bi-info-circle-fill text-success',
                    ];
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative notif-bell" href="#" data-bs-toggle="dropdown" aria-label="Notifikasi" id="notifBellDropdown">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" aria-hidden="true" id="notifBadge"></span>
                                <span class="visually-hidden">Ada notifikasi belum dibaca</span>
                            <?php endif; ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow p-0 notif-dropdown-menu" style="width: 340px; max-height: 380px; overflow-y: auto;">
                            <li class="dropdown-header fw-semibold d-flex align-items-center justify-content-between">
                                <span>Notifikasi</span>
                                <a class="small text-success text-decoration-none" href="<?php echo $path_prefix; ?>notifications.php">Lihat Semua</a>
                            </li>
                            <li><hr class="dropdown-divider my-0"></li>

                            <?php if (empty($recentNotifications)): ?>
                                <li class="px-3 py-3 small text-muted">Belum ada notifikasi</li>
                            <?php else: ?>
                                <?php foreach ($recentNotifications as $notif): ?>
                                    <?php
                                    $notifId = (int)($notif['id'] ?? 0);
                                    $type = (string)($notif['type'] ?? 'info');
                                    $typeLabel = $notifTypeLabels[$type] ?? 'Info';

                                    $msg = (string)($notif['message'] ?? '');
                                    $snippet = strip_tags(substr($msg, 0, 60));
                                    $createdAt = !empty($notif['created_at']) ? date('d M H:i', strtotime($notif['created_at'])) : '';
                                    $isUnread = (($notif['status'] ?? '') === 'unread');

                                    $href = $path_prefix . 'notifications.php'
                                        . (($isUnread && $notifId > 0) ? ('?mark_read=' . $notifId) : '')
                                        . ($notifId > 0 ? ('#notif-' . $notifId) : '');

                                    // For menu recommendations: prefer direct schedule link embedded in message
                                    if ($type === 'menu' && $notifId > 0) {
                                        if (preg_match('/<a[^>]+href=["\']([^"\']+)["\']/i', $msg, $m)) {
                                            $candidate = $m[1];
                                            $parts = parse_url($candidate);
                                            $isRelative = $parts !== false && empty($parts['scheme']) && empty($parts['host']) && !str_starts_with($candidate, '//');
                                            if ($isRelative && str_contains($candidate, 'process/schedule.process.php') && str_contains($candidate, 'action=create_from_recipe')) {
                                                $direct = $candidate;
                                                if (!str_contains($direct, 'notif_id=')) {
                                                    $direct .= (str_contains($direct, '?') ? '&' : '?') . 'notif_id=' . $notifId;
                                                }
                                                $href = $path_prefix . ltrim($direct, '/');
                                            }
                                        }
                                    }
                                    ?>
                                    <?php $iconClass = $notifTypeIcons[$type] ?? $notifTypeIcons['info']; ?>
                                    <li class="border-bottom">
                                        <a class="dropdown-item px-3 py-2 <?php echo $isUnread ? 'border-start border-3 border-success-subtle' : ''; ?>" href="<?php echo htmlspecialchars($href); ?>">
                                            <div class="d-flex align-items-start" style="gap:0.75rem;">
                                                <div class="flex-shrink-0 mt-1" style="width:20px;">
                                                    <i class="bi <?php echo $iconClass; ?>"></i>
                                                </div>
                                                <div class="flex-grow-1" style="min-width:0;">
                                                    <div class="d-flex justify-content-between align-items-baseline" style="gap:0.75rem;">
                                                        <div class="fw-semibold small text-truncate" style="max-width:240px;">
                                                            <?php echo htmlspecialchars($notif['title'] ?? ''); ?>
                                                            <?php if ($isUnread): ?> <span class="text-success" title="Belum dibaca">•</span><?php endif; ?>
                                                        </div>
                                                        <div class="small text-muted" style="white-space:nowrap;">
                                                            <?php echo htmlspecialchars($createdAt); ?>
                                                        </div>
                                                    </div>
                                                    <div class="small text-muted text-truncate" style="max-width:280px;">
                                                        <?php echo htmlspecialchars($typeLabel); ?> — <?php echo htmlspecialchars($snippet); ?><?php if (strlen($msg) > 60): ?>...<?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <?php if ($role === 'admin'): ?>
                        <!-- ADMIN PROFILE DROPDOWN -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($userInitial); ?>
                                </div>
                                <span><?php echo htmlspecialchars($userName); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li class="dropdown-header">
                                    <strong><?php echo htmlspecialchars($userName); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($userEmail); ?></small>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>profile.php">
                                        <i class="bi bi-person me-2"></i>Lihat Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>profile_edit.php">
                                        <i class="bi bi-pencil me-2"></i>Ubah Profil
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
                        <!-- PROFILE DROPDOWN -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($userInitial); ?>
                                </div>
                                <span><?php echo htmlspecialchars($userName); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li class="dropdown-header">
                                    <strong><?php echo htmlspecialchars($userName); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($userEmail); ?></small>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>profile.php">
                                        <i class="bi bi-person me-2"></i>Lihat Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $path_prefix; ?>profile_edit.php">
                                        <i class="bi bi-pencil me-2"></i>Ubah Profil
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
<div class="container mt-4">

<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$hideBackOn = [
    'index.php',
    'login.php',
    'register.php',
    'home.php',
    'dashboard.php',
    'profile_register.php',
    // pages that already have their own back button
    'goals.php'
];
$showBackButton = !in_array($currentPage, $hideBackOn, true);
$isAdminArea = (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false);
$fallbackUrl = $user
    ? ($path_prefix . (($isAdminArea && $role === 'admin') ? 'admin/dashboard.php' : 'dashboard.php'))
    : ($path_prefix . 'index.php');
?>

<?php if ($showBackButton): ?>
    <div class="d-flex justify-content-end mb-3">
        <a
            href="<?php echo htmlspecialchars($fallbackUrl); ?>"
            class="btn btn-outline-secondary btn-sm"
            onclick="try{var r=document.referrer||''; if(r){var u=new URL(r, window.location.href); if(u.origin===window.location.origin && !/\/process\//i.test(u.pathname)){ window.location.href=r; return false; }} }catch(e){} return true;"
        >
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
<?php endif; ?>


