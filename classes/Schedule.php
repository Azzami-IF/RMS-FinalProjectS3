<?php
class Schedule {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  public function create($user,$food,$date) {
    $stmt = $this->db->prepare(
      "INSERT INTO schedules(user_id,food_id,schedule_date)
       VALUES (?,?,?)"
    );
    return $stmt->execute([$user,$food,$date]);
  }

  public function history($user) {
    $stmt = $this->db->prepare(
      "SELECT f.name,s.schedule_date
       FROM schedules s
       JOIN foods f ON s.food_id=f.id
       WHERE user_id=?"
    );
    $stmt->execute([$user]);
    return $stmt->fetchAll();
  }
}
