<?php
class ProfilePageController
{
    private $userModel;
    private $userGoalModel;
    private array $userData = [];
    private array|null|false $userGoal = null;
    private array $scheduleStats = [];
    private array $todayStats = [];
    private string $message = '';
    private string $messageType = '';

    public function __construct($db, array $user)
    {
        $this->userModel = new \User($db);
        $this->userGoalModel = new \UserGoal($db);
        $this->userData = $this->userModel->find($user['id']);
        $this->userGoal = $this->userGoalModel->findActive($user['id']);
        $this->loadStats($db, $user['id']);
        $this->handleMessages();
    }

    private function loadStats($db, int $userId): void
    {
        $schedules = $db->prepare("SELECT COUNT(*) as total_schedules FROM schedules WHERE user_id = ?");
        $schedules->execute([$userId]);
        $this->scheduleStats = $schedules->fetch(\PDO::FETCH_ASSOC);

        $todaySchedules = $db->prepare("SELECT COUNT(*) as today_count FROM schedules WHERE user_id = ? AND schedule_date = CURDATE()");
        $todaySchedules->execute([$userId]);
        $this->todayStats = $todaySchedules->fetch(\PDO::FETCH_ASSOC);
    }

    private function handleMessages(): void
    {
        if (isset($_GET['success'])) {
            $this->message = 'Operasi berhasil!';
            $this->messageType = 'success';
        } elseif (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'password_incorrect':
                    $this->message = 'Password yang Anda masukkan salah!';
                    $this->messageType = 'danger';
                    break;
                default:
                    $this->message = 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
                    $this->messageType = 'danger';
            }
        }
    }

    public function getUserData(): array
    {
        return $this->userData;
    }

    public function getUserGoal(): array|null|false
    {
        return $this->userGoal;
    }

    public function getScheduleStats(): array
    {
        return $this->scheduleStats;
    }

    public function getTodayStats(): array
    {
        return $this->todayStats;
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
?>
