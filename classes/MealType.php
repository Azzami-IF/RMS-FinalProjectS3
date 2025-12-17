<?php

class MealType
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query("SELECT * FROM meal_types WHERE is_active = TRUE ORDER BY sort_order")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM meal_types WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getActive(): array
    {
        return $this->db->query("SELECT * FROM meal_types WHERE is_active = TRUE ORDER BY sort_order")
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}