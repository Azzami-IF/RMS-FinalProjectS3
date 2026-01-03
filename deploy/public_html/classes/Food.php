<?php

class Food
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db
            ->query("SELECT * FROM foods ORDER BY id DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array|false */
    public function find(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM foods WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO foods (category_id, name, description, calories, protein, fat, carbs, fiber, sugar, sodium, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['category_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['calories'],
            $data['protein'] ?? 0,
            $data['fat'] ?? 0,
            $data['carbs'] ?? 0,
            $data['fiber'] ?? 0,
            $data['sugar'] ?? 0,
            $data['sodium'] ?? 0,
            $data['created_by'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE foods
             SET category_id=?, name=?, description=?, calories=?, protein=?, fat=?, carbs=?,
                 fiber=?, sugar=?, sodium=?, updated_at=CURRENT_TIMESTAMP
             WHERE id=?"
        );
        $stmt->execute([
            $data['category_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['calories'],
            $data['protein'] ?? 0,
            $data['fat'] ?? 0,
            $data['carbs'] ?? 0,
            $data['fiber'] ?? 0,
            $data['sugar'] ?? 0,
            $data['sodium'] ?? 0,
            $id
        ]);
    }

    public function getByCategory(int $categoryId): array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, fc.name as category_name
             FROM foods f
             LEFT JOIN food_categories fc ON f.category_id = fc.id
             WHERE f.category_id = ?
             ORDER BY f.name"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(string $query): array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, fc.name as category_name
             FROM foods f
             LEFT JOIN food_categories fc ON f.category_id = fc.id
             WHERE MATCH(f.name, f.description) AGAINST(? IN NATURAL LANGUAGE MODE)
             ORDER BY f.name"
        );
        $stmt->execute([$query]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM foods WHERE id=?");
        $stmt->execute([$id]);
    }
}
