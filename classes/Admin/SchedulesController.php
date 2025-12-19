<?php

namespace Admin;

use Schedule;
use Food;
use User;
use PDO;

class SchedulesController
{
    private Schedule $scheduleModel;
    private Food $foodModel;
    private User $userModel;
    private array $foods = [];
    private array $users = [];
    private string $message = '';
    private string $messageType = '';

    public function __construct(PDO $db)
    {
        $this->scheduleModel = new Schedule($db);
        $this->foodModel = new Food($db);
        $this->userModel = new User($db);
        $this->foods = $this->foodModel->all();
        $this->users = $this->userModel->all();
        $this->handleMessages();
    }

    private function handleMessages(): void
    {
        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'schedule_created':
                    $this->message = 'Jadwal berhasil ditambahkan!';
                    $this->messageType = 'success';
                    break;
                case 'schedule_deleted':
                    $this->message = 'Jadwal berhasil dihapus!';
                    $this->messageType = 'success';
                    break;
            }
        } elseif (isset($_GET['error'])) {
            $this->message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
            $this->messageType = 'danger';
        }
    }

    public function getFoods(): array
    {
        return $this->foods;
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
