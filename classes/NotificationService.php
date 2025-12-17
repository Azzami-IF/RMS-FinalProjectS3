<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['mail_user'];
            $mail->Password   = $this->config['mail_pass'];
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom($this->config['mail_user'], 'Healthy App');
            $mail->addAddress($email);

            $mail->Subject = $title;
            $mail->Body    = $message;

            $mail->send();
            $this->log($userId, $title, $message, 'sent');
            return true;

        } catch (Exception $e) {
            $this->log($userId, $title, $message, 'failed');
            return false;
        }
    }

    private function log(int $userId, string $title, string $message, string $status): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications
             (user_id, title, message, channel, status)
             VALUES (?, ?, ?, 'email', ?)"
        );
        $stmt->execute([$userId, $title, $message, $status]);
    }
}
