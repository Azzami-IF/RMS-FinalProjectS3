<?php

namespace Admin;

use PDO;

class ReportAdminController
{
    private PDO $db;
    private array $stats = [];
    private array $topFoods = [];
    private array $recentSchedules = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->loadStats();
        $this->loadTopFoods();
        $this->loadRecentSchedules();
    }

    private function loadStats(): void
    {
        $this->stats['userCount'] = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
        $this->stats['adminCount'] = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $this->stats['foodCount'] = $this->db->query("SELECT COUNT(*) FROM foods")->fetchColumn();
        $this->stats['scheduleCount'] = $this->db->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
        $this->stats['notificationCount'] = $this->db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    }

    private function loadTopFoods(): void
    {
        $this->topFoods = $this->db->query("
            SELECT f.name, COUNT(s.id) as usage_count
            FROM foods f
            LEFT JOIN schedules s ON f.id = s.food_id
            GROUP BY f.id, f.name
            ORDER BY usage_count DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadRecentSchedules(): void
    {
        $this->recentSchedules = $this->db->query("
            SELECT s.*, u.name as user_name, f.name as food_name
            FROM schedules s
            JOIN users u ON s.user_id = u.id
            JOIN foods f ON s.food_id = f.id
            ORDER BY s.created_at DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getTopFoods(): array
    {
        return $this->topFoods;
    }

    public function getRecentSchedules(): array
    {
        return $this->recentSchedules;
    }
}
