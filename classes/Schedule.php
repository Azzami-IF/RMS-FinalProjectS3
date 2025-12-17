<?php
class Schedule {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  public function create($user, $food, $date, $mealTypeId = null, $quantity = 1, $notes = null) {
    $stmt = $this->db->prepare(
      "INSERT INTO schedules(user_id, food_id, meal_type_id, schedule_date, quantity, notes)
       VALUES (?, ?, ?, ?, ?, ?)"
    );
    return $stmt->execute([$user, $food, $mealTypeId, $date, $quantity, $notes]);
  }

  public function getByDate($user, $date) {
    $stmt = $this->db->prepare(
      "SELECT s.*, f.name as food_name, f.calories, mt.display_name as meal_type_name
       FROM schedules s
       JOIN foods f ON s.food_id = f.id
       LEFT JOIN meal_types mt ON s.meal_type_id = mt.id
       WHERE s.user_id = ? AND s.schedule_date = ?
       ORDER BY mt.sort_order, s.created_at"
    );
    $stmt->execute([$user, $date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getMealsByDateRange($user, $startDate, $endDate) {
    $stmt = $this->db->prepare(
      "SELECT s.*, f.name as food_name, f.calories, mt.display_name as meal_type_name
       FROM schedules s
       JOIN foods f ON s.food_id = f.id
       LEFT JOIN meal_types mt ON s.meal_type_id = mt.id
       WHERE s.user_id = ? AND s.schedule_date BETWEEN ? AND ?
       ORDER BY s.schedule_date DESC, mt.sort_order"
    );
    $stmt->execute([$user, $startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
