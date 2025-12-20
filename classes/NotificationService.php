<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class NotificationService
{
    private PDO $db;
    private array $config;

    public function __construct(PDO $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function sendEmail(int $userId, string $email, string $title, string $message, string $actionUrl = ''): bool
    {
        // Use SMTP via PHPMailer to avoid relying on missing sendmail executables on Windows.
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['MAIL_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['MAIL_USER'] ?? '';
            $mail->Password = $this->config['MAIL_PASS'] ?? '';
            $mail->SMTPSecure = $this->config['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)($this->config['MAIL_PORT'] ?? 587);

            // Prevent garbled characters in email clients.
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $from = $this->config['MAIL_FROM'] ?? ($this->config['MAIL_USER'] ?? '');
            if ($from !== '') {
                $mail->setFrom($from, 'RMS');
            }

            $mail->addAddress($email);
            $mail->Subject = $title;
            $mail->isHTML(true);
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
            $this->log($userId, $title, $message, 'sent', $actionUrl);
            return true;
        } catch (Exception $e) {
            $this->log($userId, $title, $message, 'failed', $actionUrl);
            return false;
        }
    }

    // Log notifikasi email ke tabel notifications
    public function log(int $userId, string $title, string $message, string $status, string $actionUrl = ''): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, title, message, action_url, type, channel, status, created_at) 
             VALUES (?, ?, ?, ?, 'info', 'email', ?, NOW())"
        );
        $stmt->execute([$userId, $title, $message, $actionUrl, $status]);
    }

    public function createNotification(int $userId, string $title, string $message, string $type = 'info', string $actionUrl = ''): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications
             (user_id, title, message, action_url, type, channel, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'in_app', 'unread', NOW())"
        );
        $stmt->execute([$userId, $title, $message, $actionUrl, $type]);
    }
}
