<?php

class AnalyticsService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // TOTAL KALORI HARI INI
    public function todayCalories(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT IFNULL(SUM(f.calories),0) total
            FROM schedules s
            JOIN foods f ON s.food_id = f.id
            WHERE s.user_id = ?
            AND s.schedule_date = CURDATE()
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    // TOTAL MENU DIJADWALKAN
    public function totalMeals(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM schedules
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    // TOTAL PROTEIN / LEMAK / KARBO
    public function nutritionSummary(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                SUM(f.protein) protein,
                SUM(f.fat) fat,
                SUM(f.carbs) carbs
            FROM schedules s
            JOIN foods f ON s.food_id = f.id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
