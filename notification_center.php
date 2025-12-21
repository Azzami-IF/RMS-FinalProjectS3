<?php
/**
 * Notification Center - Display user notifications
 * 
 * UI untuk menampilkan semua notifikasi user dengan filtering dan aksi
 */

require_once __DIR__ . '/classes/AppContext.php';

$app = AppContext::fromRootDir(__DIR__);
$app->requireUser();

$user = $app->user();
$db = $app->db();

$userId = $user['id'];
$filter = $_GET['filter'] ?? 'unread'; // unread, read, all
$type = $_GET['type'] ?? 'all'; // all, reminder, menu, goal, info

// Build query - hanya tampilkan in_app notifications, bukan email
$query = "SELECT id, title, message, action_url, type, channel, status, created_at 
          FROM notifications WHERE user_id = ? AND channel = 'in_app'";
$params = [$userId];

if ($filter === 'unread') {
    $query .= " AND status = 'unread'";
} elseif ($filter === 'read') {
    $query .= " AND status = 'read'";
}

if ($type !== 'all') {
    $query .= " AND type = ?";
    $params[] = $type;
}

$query .= " ORDER BY created_at DESC LIMIT 50";

$stmt = $db->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count (in_app only)
$countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND channel = 'in_app' AND status = 'unread'");
$countStmt->execute([$userId]);
$unreadCount = $countStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Notifikasi - RMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .filters {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filters a, .filters button {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            color: #666;
            transition: all 0.3s;
        }
        
        .filters a:hover, .filters button:hover {
            background: #f0f0f0;
        }
        
        .filters a.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        .notification-item {
            background: white;
            padding: 20px;
            margin-bottom: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #4CAF50;
            transition: all 0.3s;
        }
        
        .notification-item.unread {
            background: #f9f9f9;
            border-left-color: #2196F3;
        }
        
        .notification-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .notification-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            flex: 1;
        }
        
        .notification-type {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .notification-type.reminder {
            background: #FFC107;
            color: white;
        }
        
        .notification-type.menu {
            background: #FF9800;
            color: white;
        }
        
        .notification-type.goal {
            background: #9C27B0;
            color: white;
        }
        
        .notification-type.info {
            background: #2196F3;
            color: white;
        }
        
        .notification-message {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin: 10px 0;
        }
        
        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            font-size: 12px;
            color: #999;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="home.php" class="back-link">← Kembali ke Beranda</a>
        
        <div class="header">
            <h1>📬 Pusat Notifikasi</h1>
            <p>Anda memiliki <strong><?php echo $unreadCount; ?></strong> notifikasi yang belum dibaca</p>
        </div>
        
        <div class="filters">
            <a href="?filter=unread" class="<?php echo $filter === 'unread' ? 'active' : ''; ?>">Belum Dibaca</a>
            <a href="?filter=read" class="<?php echo $filter === 'read' ? 'active' : ''; ?>">Sudah Dibaca</a>
            <a href="?filter=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">Semua</a>
            <span style="flex: 1;"></span>
            <a href="?filter=<?php echo $filter; ?>&type=all" class="<?php echo $type === 'all' ? 'active' : ''; ?>">Semua Tipe</a>
            <a href="?filter=<?php echo $filter; ?>&type=reminder" class="<?php echo $type === 'reminder' ? 'active' : ''; ?>">Pengingat</a>
            <a href="?filter=<?php echo $filter; ?>&type=menu" class="<?php echo $type === 'menu' ? 'active' : ''; ?>">Menu</a>
            <a href="?filter=<?php echo $filter; ?>&type=goal" class="<?php echo $type === 'goal' ? 'active' : ''; ?>">Target</a>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"></div>
                <p>Tidak ada notifikasi untuk ditampilkan</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo $notif['status'] === 'unread' ? 'unread' : ''; ?>">
                    <div class="notification-header">
                        <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                        <span class="notification-type <?php echo htmlspecialchars($notif['type']); ?>">
                            <?php 
                            $typeLabels = ['reminder' => 'Pengingat', 'menu' => 'Menu', 'goal' => 'Target', 'info' => 'Info'];
                            echo $typeLabels[$notif['type']] ?? htmlspecialchars($notif['type']);
                            ?>
                        </span>
                    </div>
                    
                    <div class="notification-message">
                        <?php
                        $msg = (string)($notif['message'] ?? '');
                        // Normalize common HTML line breaks into real newlines.
                        $msg = str_ireplace(["<br />", "<br/>", "<br>"], "\n", $msg);
                        // Keep in-app display safe by removing all tags.
                        $msg = strip_tags($msg);
                        echo nl2br(htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'));
                        ?>
                    </div>
                    
                    <div class="notification-footer">
                        <span><?php echo date('d M Y H:i', strtotime($notif['created_at'])); ?></span>
                        <div class="notification-actions">
                            <?php if ($notif['action_url']): ?>
                                <a href="<?php echo htmlspecialchars($notif['action_url']); ?>" class="btn btn-primary">Buka</a>
                            <?php endif; ?>
                            <?php if ($notif['status'] === 'unread'): ?>
                                <button class="btn btn-secondary" onclick="markAsRead(<?php echo $notif['id']; ?>)">Tandai Dibaca</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        function markAsRead(notificationId) {
            fetch('notifications/api.php?action=mark_read&notification_id=' + notificationId)
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        location.reload();
                    }
                });
        }
    </script>
</body>
</html>
