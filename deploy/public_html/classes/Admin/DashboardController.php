<?php

namespace Admin;

use AnalyticsService;
use PDO;

class DashboardController
{
    private AnalyticsService $analytics;
    private PDO $db;
    private int $userCount = 0;
    private int $foodCount = 0;
    private int $scheduleCount = 0;
    private int $adminCount = 0;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->analytics = new AnalyticsService($db);
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $this->userCount = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
        $this->foodCount = (int)$this->db->query("SELECT COUNT(*) FROM foods")->fetchColumn();
        $this->scheduleCount = (int)$this->db->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
        $this->adminCount = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    public function getUserCount(): int
    {
        return $this->userCount;
    }

    public function getFoodCount(): int
    {
        return $this->foodCount;
    }

    public function getScheduleCount(): int
    {
        return $this->scheduleCount;
    }

    public function getAdminCount(): int
    {
        return $this->adminCount;
    }
}
