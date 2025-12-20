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
            SELECT IFNULL(SUM(
                COALESCE(s.calories_consumed, (f.calories * COALESCE(s.quantity, 1)))
            ), 0) as total
            FROM schedules s
            JOIN foods f ON s.food_id = f.id
            WHERE s.user_id = ? AND s.schedule_date = CURDATE()
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    // TOTAL KALORI MINGGU INI
    public function weeklyCalories(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DAYNAME(s.schedule_date) as day_name,
                DATE(s.schedule_date) as date,
                IFNULL(SUM(
                    COALESCE(s.calories_consumed, (f.calories * COALESCE(s.quantity, 1)))
                ), 0) as calories
            FROM schedules s
            JOIN foods f ON s.food_id = f.id
            WHERE s.user_id = ? AND s.schedule_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY s.schedule_date
            ORDER BY s.schedule_date
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // RINGKASAN NUTRISI HARIAN
    public function dailyNutritionSummary(int $userId, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT
                IFNULL(SUM(
                    COALESCE(s.calories_consumed, (f.calories * COALESCE(s.quantity, 1)))
                ), 0) as calories,
                IFNULL(SUM(f.protein * s.quantity), 0) as protein,
                IFNULL(SUM(f.fat * s.quantity), 0) as fat,
                IFNULL(SUM(f.carbs * s.quantity), 0) as carbs,
                IFNULL(SUM(f.fiber * s.quantity), 0) as fiber,
                COUNT(DISTINCT s.id) as meals_count
            FROM schedules s
            JOIN foods f ON s.food_id = f.id
            WHERE s.user_id = ? AND s.schedule_date = ?
        ");
        $stmt->execute([$userId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'calories' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0, 'fiber' => 0, 'meals_count' => 0
        ];
    }

    // PROGRESS TERHADAP TARGET
    public function goalProgress(int $userId): array
    {
        // NOTE: This project does not ship a daily_nutrition_summary table in sql.txt.
        // Compute 7-day averages directly from schedules + foods.
        $stmt = $this->db->prepare("
            SELECT
                ug.daily_calorie_target,
                ug.daily_protein_target,
                ug.daily_fat_target,
                ug.daily_carbs_target,
                COALESCE(AVG(d.total_calories), 0) as avg_calories,
                COALESCE(AVG(d.total_protein), 0) as avg_protein,
                COALESCE(AVG(d.total_fat), 0) as avg_fat,
                COALESCE(AVG(d.total_carbs), 0) as avg_carbs
            FROM user_goals ug
            LEFT JOIN (
                SELECT
                    s.user_id,
                    s.schedule_date,
                    SUM(COALESCE(s.calories_consumed, (f.calories * COALESCE(s.quantity, 1)))) as total_calories,
                    SUM(f.protein * COALESCE(s.quantity, 1)) as total_protein,
                    SUM(f.fat * COALESCE(s.quantity, 1)) as total_fat,
                    SUM(f.carbs * COALESCE(s.quantity, 1)) as total_carbs
                FROM schedules s
                JOIN foods f ON f.id = s.food_id
                WHERE s.user_id = ? AND s.schedule_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY s.user_id, s.schedule_date
            ) d ON d.user_id = ug.user_id
            WHERE ug.user_id = ? AND ug.is_active = TRUE
            GROUP BY ug.user_id, ug.daily_calorie_target, ug.daily_protein_target, ug.daily_fat_target, ug.daily_carbs_target
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // TREND BERAT BADAN
    public function weightTrend(int $userId, int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT
                logged_at,
                weight_kg,
                body_fat_percentage
            FROM weight_logs
            WHERE user_id = ? AND logged_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ORDER BY logged_at ASC
        ");
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // KALORI PER HARI (UNTUK CHART)
    public function caloriePerDay(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.schedule_date, SUM(COALESCE(s.calories_consumed, (f.calories * COALESCE(s.quantity, 1)))) total
            FROM schedules s
            JOIN foods f ON s.food_id = f.id
            WHERE s.user_id = ?
            GROUP BY s.schedule_date
            ORDER BY s.schedule_date
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // TOTAL KALORI SELURUHNYA
    public function totalCalories(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT IFNULL(SUM(COALESCE(s.calories_consumed, (f.calories * COALESCE(s.quantity, 1)))),0) total
            FROM schedules s
            JOIN foods f ON s.food_id = f.id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    // TOTAL HARI TERCATAT
    public function totalDays(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT schedule_date) FROM schedules
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    // TOTAL MENU DICATAT
    public function totalMeals(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM schedules
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    // RINGKASAN NUTRISI TOTAL (UNTUK CHART PIE)
    public function nutritionSummary(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                IFNULL(SUM(f.protein * s.quantity), 0) as protein,
                IFNULL(SUM(f.fat * s.quantity), 0) as fat,
                IFNULL(SUM(f.carbs * s.quantity), 0) as carbs
            FROM schedules s
            JOIN foods f ON s.food_id = f.id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: ['protein' => 0, 'fat' => 0, 'carbs' => 0];
    }
}
