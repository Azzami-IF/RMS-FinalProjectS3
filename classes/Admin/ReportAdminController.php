<?php

namespace Admin;

use PDO;

class ReportAdminController
{
    private PDO $db;
    private array $stats = [];
    private array $topFoods = [];
    private array $recentSchedules = [];
    private array $scheduleTrend = [
        'labels' => [],
        'counts' => [],
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->loadStats();
        $this->loadTopFoods();
        $this->loadRecentSchedules();
        $this->loadScheduleTrend();
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
        // Popular foods should be based on actual consumption.
        // In this app, repeat consumption is often represented by increasing `quantity` on a schedule row.
        // Therefore, rank by total portions consumed (SUM(quantity)).
        $this->topFoods = $this->db->query("
            SELECT f.name,
                   SUM(COALESCE(s.quantity, 1)) AS usage_count
            FROM schedules s
            JOIN foods f ON f.id = s.food_id
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

    private function loadScheduleTrend(int $days = 7): void
    {
        $days = max(1, min(31, $days));

        // Build date labels for last N days (including today)
        $labels = [];
        $dateKeys = [];
        $today = new \DateTime('today');
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = (clone $today)->modify('-' . $i . ' days');
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('d M');
            $dateKeys[] = $key;
        }

        $start = (clone $today)->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT schedule_date, COUNT(*) AS cnt\n             FROM schedules\n             WHERE schedule_date >= ?\n             GROUP BY schedule_date\n             ORDER BY schedule_date ASC"
        );
        $stmt->execute([$start]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[(string)$r['schedule_date']] = (int)$r['cnt'];
        }

        $counts = [];
        foreach ($dateKeys as $k) {
            $counts[] = $map[$k] ?? 0;
        }

        $this->scheduleTrend = [
            'labels' => $labels,
            'counts' => $counts,
        ];
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

    public function getScheduleTrend(): array
    {
        return $this->scheduleTrend;
    }
}
