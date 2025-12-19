
<?php

class GoalsPageController
{
    private UserGoal $userGoalModel;
    private array|false $currentGoal;
    private string $message = '';
    private string $messageType = '';

    public function __construct(PDO $db, array $user)
    {
        $this->userGoalModel = new UserGoal($db);
        $this->currentGoal = $this->userGoalModel->findActive($user['id']);
        $this->handleMessages();
    }

    private function handleMessages(): void
    {
        if (isset($_GET['success'])) {
            $this->message = 'Goal berhasil diperbarui!';
            $this->messageType = 'success';
        } elseif (isset($_GET['error'])) {
            $this->message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
            $this->messageType = 'danger';
        }
    }

    public function getCurrentGoal(): array|false
    {
        return $this->currentGoal;
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
