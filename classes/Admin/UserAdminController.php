<?php

namespace Admin;

use User;
use PDO;

class UserAdminController
{
    private User $userModel;
    private array $users;
    private string $message = '';
    private string $messageType = '';

    public function __construct(PDO $db)
    {
        $this->userModel = new User($db);
        $this->users = $this->userModel->all();
        $this->handleMessages();
    }

    private function handleMessages(): void
    {
        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'user_updated':
                    $this->message = 'Pengguna berhasil diperbarui!';
                    $this->messageType = 'success';
                    break;
                case 'user_deleted':
                    $this->message = 'Pengguna berhasil dihapus!';
                    $this->messageType = 'success';
                    break;
                case 'status_updated':
                    $this->message = 'Status pengguna berhasil diperbarui!';
                    $this->messageType = 'success';
                    break;
            }
        } elseif (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'cannot_delete_self':
                    $this->message = 'Anda tidak dapat menghapus akun Anda sendiri!';
                    $this->messageType = 'danger';
                    break;
                case 'user_not_found':
                    $this->message = 'Pengguna tidak ditemukan!';
                    $this->messageType = 'danger';
                    break;
                case 'invalid_action':
                    $this->message = 'Aksi tidak valid!';
                    $this->messageType = 'danger';
                    break;
                default:
                    $this->message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
                    $this->messageType = 'danger';
            }
        }
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getMessageType(): string
    {
        return $this->messageType;
    }
}
