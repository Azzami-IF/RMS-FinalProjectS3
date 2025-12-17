<?php

class FoodCategory
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query("SELECT * FROM food_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM food_categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO food_categories (name, description, icon)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['icon'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE food_categories
             SET name=?, description=?, icon=?
             WHERE id=?"
        );
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['icon'] ?? null,
            $id
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM food_categories WHERE id=?");
        $stmt->execute([$id]);
    }
}