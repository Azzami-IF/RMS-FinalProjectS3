<?php
/**
 * Notification API Handler
 * 
 * REST endpoint untuk menampilkan dan mengelola notifikasi user
 * Usage: /notifications/api.php?action=list&user_id=15
 *        /notifications/api.php?action=mark_read&notification_id=207
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();

$action = $_GET['action'] ?? 'list';
$userId = (int)($_GET['user_id'] ?? $_SESSION['user_id'] ?? 0);
$notificationId = (int)($_GET['notification_id'] ?? 0);

// Simple auth check
if ($userId === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$response = [];

switch ($action) {
    case 'list':
        // Get all in-app notifications for user (exclude email logs)
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $stmt = $db->prepare(
            "SELECT id, title, message, action_url, type, channel, status, created_at 
             FROM notifications 
             WHERE user_id = ? AND channel = 'in_app'
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$userId, $limit, $offset]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get unread count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND channel = 'in_app' AND status = 'unread'");
        $countStmt->execute([$userId]);
        $unreadCount = $countStmt->fetchColumn();
        
        $response = [
            'success' => true,
            'data' => $notifications,
            'unread_count' => (int)$unreadCount,
            'total' => count($notifications)
        ];
        break;

    case 'get':
        // Get single notification
        $stmt = $db->prepare(
            "SELECT id, title, message, action_url, type, channel, status, created_at 
             FROM notifications 
             WHERE id = ? AND user_id = ? AND channel = 'in_app'"
        );
        $stmt->execute([$notificationId, $userId]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$notification) {
            http_response_code(404);
            $response = ['error' => 'Notification not found'];
        } else {
            // Mark as read
            $updateStmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND channel = 'in_app'");
            $updateStmt->execute([$notificationId]);
            
            $response = [
                'success' => true,
                'data' => $notification
            ];
        }
        break;

    case 'mark_read':
        // Mark notification as read
        $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ? AND channel = 'in_app'");
        $result = $stmt->execute([$notificationId, $userId]);
        
        $response = [
            'success' => $result,
            'message' => $result ? 'Marked as read' : 'Failed to update'
        ];
        break;

    case 'mark_all_read':
        // Mark all notifications as read
        $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ? AND channel = 'in_app' AND status = 'unread'");
        $result = $stmt->execute([$userId]);
        
        $response = [
            'success' => $result,
            'message' => 'All notifications marked as read'
        ];
        break;

    case 'delete':
        // Delete notification
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ? AND channel = 'in_app'");
        $result = $stmt->execute([$notificationId, $userId]);
        
        $response = [
            'success' => $result,
            'message' => $result ? 'Deleted' : 'Failed to delete'
        ];
        break;

    case 'stats':
        // Get notification statistics (in-app only)
        $typeStmt = $db->prepare(
            "SELECT type, COUNT(*) as count FROM notifications WHERE user_id = ? AND channel = 'in_app' GROUP BY type"
        );
        $typeStmt->execute([$userId]);
        $byType = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $statusStmt = $db->prepare(
            "SELECT status, COUNT(*) as count FROM notifications WHERE user_id = ? AND channel = 'in_app' GROUP BY status"
        );
        $statusStmt->execute([$userId]);
        $byStatus = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $response = [
            'success' => true,
            'by_type' => $byType,
            'by_status' => $byStatus
        ];
        break;

    default:
        http_response_code(400);
        $response = ['error' => 'Unknown action'];
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
