
<?php

class GoalsPageController
{
    private UserGoal $userGoalModel;
    /** @var array|false */
    private $currentGoal;
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
            $this->message = 'Perubahan berhasil disimpan!';
            $this->messageType = 'success';
        } elseif (isset($_GET['error'])) {
            $this->message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
            $this->messageType = 'danger';
        }
    }

    /** @return array|false */
    public function getCurrentGoal()
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
