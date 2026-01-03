<?php

namespace Admin;

use User;
use PDO;

class UserDetailController
{
    private User $userModel;
    /** @var array|false */
    private $userData;
    private array $scheduleStats = [];

    public function __construct(PDO $db, int $id)
    {
        $this->userModel = new User($db);
        $this->userData = $this->userModel->find($id);
        $this->loadStats($db, $id);
    }

    private function loadStats(PDO $db, int $userId): void
    {
        if ($this->userData) {
            $schedules = $db->prepare("SELECT COUNT(*) as total_schedules FROM schedules WHERE user_id = ?");
            $schedules->execute([$userId]);
            $this->scheduleStats = $schedules->fetch(PDO::FETCH_ASSOC);
        }
    }

    /** @return array|false */
    public function getUserData()
    {
        return $this->userData;
    }

    public function getScheduleStats(): array
    {
        return $this->scheduleStats;
    }
}
