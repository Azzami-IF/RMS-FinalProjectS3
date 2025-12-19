<?php
class WeightLogPageController
{
    private WeightLog $weightLogModel;
    private array $recentLogs = [];
    private string $message = '';
    private string $messageType = '';
    private array|false $stats = false;

    public function __construct(PDO $db, array $user)
    {
        $this->weightLogModel = new WeightLog($db);
        $this->recentLogs = $this->weightLogModel->getRecent($user['id'], 10);
        $this->stats = $this->weightLogModel->getStats($user['id']);
        $this->handleMessages();
    }
    public function getStats(): array|false
    {
        return $this->stats;
    }

    private function handleMessages(): void
    {
        if (isset($_GET['success'])) {
            $this->message = 'Log berat badan berhasil disimpan!';
            $this->messageType = 'success';
        } elseif (isset($_GET['error'])) {
            $this->message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
            $this->messageType = 'danger';
        }
    }

    public function getRecentLogs(): array
    {
        return $this->recentLogs;
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
