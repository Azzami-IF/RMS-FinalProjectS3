<?php

class NotificationService
{
    private PDO $db;
    private array $config;

    public function __construct(PDO $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function sendEmail(int $userId, string $email, string $title, string $message): bool
    {
        $headers = 'From: ' . $this->config['MAIL_USER'] . "\r\n" .
                   'Reply-To: ' . $this->config['MAIL_USER'] . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        $success = mail($email, $title, $message, $headers);

        if ($success) {
            $this->log($userId, $title, $message, 'sent');
            return true;
        } else {
            $this->log($userId, $title, $message, 'failed');
            return false;
        }
    }

    // Log notifikasi email ke tabel notifications
    public function log(int $userId, string $title, string $message, string $status): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, title, message, type, channel, status) VALUES (?, ?, ?, 'info', 'email', ?)"
        );
        $stmt->execute([$userId, $title, $message, $status]);
    }

    public function createNotification(int $userId, string $title, string $message, string $type = 'info'): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications
             (user_id, title, message, type, channel, status)
             VALUES (?, ?, ?, ?, 'in_app', 'unread')"
        );
        $stmt->execute([$userId, $title, $message, $type]);
    }
}
