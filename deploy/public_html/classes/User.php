<?php

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** @return array|false */
    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return array|false */
    public function findByEmail(string $email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all(): array
    {
        return $this->db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password, phone, date_of_birth, gender, height_cm, weight_kg, activity_level, daily_calorie_goal)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['password'],
            $data['phone'] ?? null,
            $data['date_of_birth'] ?? null,
            $data['gender'] ?? null,
            $data['height_cm'] ?? null,
            $data['weight_kg'] ?? null,
            $data['activity_level'] ?? 'moderate',
            $data['daily_calorie_goal'] ?? 2000
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET
             name=?, email=?, phone=?, date_of_birth=?, gender=?,
             height_cm=?, weight_kg=?, activity_level=?, daily_calorie_goal=?,
             role=?, is_active=?, updated_at=CURRENT_TIMESTAMP
             WHERE id=?"
        );
        $stmt->bindParam(1, $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(2, $data['email'], PDO::PARAM_STR);
        $stmt->bindParam(3, $data['phone'], is_null($data['phone']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(4, $data['date_of_birth'], is_null($data['date_of_birth']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(5, $data['gender'], is_null($data['gender']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(6, $data['height_cm'], is_null($data['height_cm']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(7, $data['weight_kg'], is_null($data['weight_kg']) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(8, $data['activity_level'], PDO::PARAM_STR);
        $stmt->bindParam(9, $data['daily_calorie_goal'], PDO::PARAM_INT);
        $stmt->bindParam(10, $data['role'], PDO::PARAM_STR);
        $stmt->bindParam(11, $data['is_active'], PDO::PARAM_INT);
        $stmt->bindParam(12, $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
    }
}