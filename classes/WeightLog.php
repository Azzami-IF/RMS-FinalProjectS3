<?php

class WeightLog
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO weight_logs (user_id, weight_kg, body_fat_percentage, muscle_mass_kg, notes, logged_at)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             weight_kg = VALUES(weight_kg),
             body_fat_percentage = VALUES(body_fat_percentage),
             muscle_mass_kg = VALUES(muscle_mass_kg),
             notes = VALUES(notes)"
        );
        $stmt->bindParam(1, $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(2, $data['weight_kg'], PDO::PARAM_STR); // decimal
        $bodyFat = $data['body_fat_percentage'] ?? null;
        $stmt->bindParam(3, $bodyFat, is_null($bodyFat) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $muscleMass = $data['muscle_mass_kg'] ?? null;
        $stmt->bindParam(4, $muscleMass, is_null($muscleMass) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $notes = $data['notes'] ?? null;
        $stmt->bindParam(5, $notes, is_null($notes) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $loggedAt = $data['logged_at'] ?? date('Y-m-d');
        $stmt->bindParam(6, $loggedAt, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function getLatest(int $userId): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM weight_logs
             WHERE user_id = ?
             ORDER BY logged_at DESC LIMIT 1"
        );
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getHistory(int $userId, int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM weight_logs
             WHERE user_id = ?
             ORDER BY logged_at DESC LIMIT ?"
        );
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDateRange(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM weight_logs
             WHERE user_id = ? AND logged_at BETWEEN ? AND ?
             ORDER BY logged_at ASC"
        );
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $startDate, PDO::PARAM_STR);
        $stmt->bindParam(3, $endDate, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWeightChange(int $userId, int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT
                MIN(weight_kg) as start_weight,
                MAX(weight_kg) as current_weight,
                MAX(weight_kg) - MIN(weight_kg) as change_kg,
                COUNT(*) as entries
            FROM weight_logs
            WHERE user_id = ? AND logged_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ");
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'start_weight' => 0, 'current_weight' => 0, 'change_kg' => 0, 'entries' => 0
        ];
    }

    public function getRecent(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM weight_logs
             WHERE user_id = ?
             ORDER BY logged_at DESC LIMIT ?"
        );
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(int $userId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY logged_at DESC LIMIT 1) as current_weight,
                (SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY logged_at DESC LIMIT 1) -
                (SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY logged_at DESC LIMIT 1 OFFSET 1) as change_30d,
                MAX(weight_kg) as max_weight,
                MIN(weight_kg) as min_weight,
                COUNT(*) as total_entries
            FROM weight_logs
            WHERE user_id = ?
        ");
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $userId, PDO::PARAM_INT);
        $stmt->bindParam(3, $userId, PDO::PARAM_INT);
        $stmt->bindParam(4, $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete(int $logId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM weight_logs WHERE id = ? AND user_id = ?"
        );
        $stmt->bindParam(1, $logId, PDO::PARAM_INT);
        $stmt->bindParam(2, $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}