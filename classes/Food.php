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

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM foods WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO foods (name, calories, protein, fat, carbs)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE foods
             SET name=?, calories=?, protein=?, fat=?, carbs=?
             WHERE id=?"
        );
        $stmt->execute([...$data, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM foods WHERE id=?");
        $stmt->execute([$id]);
    }
}
