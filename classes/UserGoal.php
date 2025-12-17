<?php

class UserGoal
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findActive(int $userId): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_goals
             WHERE user_id = ? AND is_active = TRUE
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        // Set previous goals as inactive
        $stmt = $this->db->prepare(
            "UPDATE user_goals SET is_active = FALSE WHERE user_id = ?"
        );
        $stmt->execute([$data['user_id']]);

        // Create new goal
        $stmt = $this->db->prepare(
            "INSERT INTO user_goals
             (user_id, goal_type, target_weight_kg, target_date, weekly_weight_change,
              daily_calorie_target, daily_protein_target, daily_fat_target, daily_carbs_target)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['user_id'],
            $data['goal_type'],
            $data['target_weight_kg'] ?? null,
            $data['target_date'] ?? null,
            $data['weekly_weight_change'] ?? null,
            $data['daily_calorie_target'],
            $data['daily_protein_target'] ?? null,
            $data['daily_fat_target'] ?? null,
            $data['daily_carbs_target'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_goals SET
             goal_type=?, target_weight_kg=?, target_date=?, weekly_weight_change=?,
             daily_calorie_target=?, daily_protein_target=?, daily_fat_target=?,
             daily_carbs_target=?, updated_at=CURRENT_TIMESTAMP
             WHERE id=?"
        );
        $stmt->execute([
            $data['goal_type'],
            $data['target_weight_kg'] ?? null,
            $data['target_date'] ?? null,
            $data['weekly_weight_change'] ?? null,
            $data['daily_calorie_target'],
            $data['daily_protein_target'] ?? null,
            $data['daily_fat_target'] ?? null,
            $data['daily_carbs_target'] ?? null,
            $id
        ]);
    }

    public function history(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_goals
             WHERE user_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}