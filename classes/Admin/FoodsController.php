<?php

namespace Admin;

use Food;
use PDO;

class FoodsController
{
    private Food $foodModel;
    private array $data = [];
    private string $message = '';
    private string $messageType = '';

    public function __construct(PDO $db)
    {
        $this->foodModel = new Food($db);
        $this->data = $this->foodModel->all();
        $this->handleMessages();
    }

    private function handleMessages(): void
    {
        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'create':
                    $this->message = 'Makanan berhasil ditambahkan!';
                    $this->messageType = 'success';
                    break;
                case 'update':
                    $this->message = 'Makanan berhasil diperbarui!';
                    $this->messageType = 'success';
                    break;
                case 'delete':
                    $this->message = 'Makanan berhasil dihapus!';
                    $this->messageType = 'success';
                    break;
            }
        } elseif (isset($_GET['error'])) {
            $this->message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
            $this->messageType = 'danger';
        }
    }

    public function getData(): array
    {
        return $this->data;
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
