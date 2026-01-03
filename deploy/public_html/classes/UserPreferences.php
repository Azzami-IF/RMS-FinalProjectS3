<?php

class UserPreferences
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $results ?: [];
    }

    public function get(int $userId, string $key, $default = null)
    {
        $stmt = $this->db->prepare(
            "SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = ?"
        );
        $stmt->execute([$userId, $key]);
        $result = $stmt->fetch(PDO::FETCH_COLUMN);

        return $result !== false ? $result : $default;
    }

    public function set(int $userId, string $key, $value): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO user_preferences (user_id, preference_key, preference_value)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)"
        );
        $stmt->execute([$userId, $key, $value]);
    }

    public function setMultiple(int $userId, array $preferences): void
    {
        foreach ($preferences as $key => $value) {
            $this->set($userId, $key, $value);
        }
    }

    public function delete(int $userId, string $key): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_preferences WHERE user_id = ? AND preference_key = ?"
        );
        $stmt->execute([$userId, $key]);
    }
}