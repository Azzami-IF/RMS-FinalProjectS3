<?php
class Database {
  protected $conn;

  public function __construct($config) {
    $this->conn = new PDO(
      "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']}",
      $config['DB_USER'],
      $config['DB_PASS']
    );
    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Align DB session timezone with app timezone (use offset to avoid relying on MySQL timezone tables).
    $dbTz = isset($config['DB_TIMEZONE']) ? $config['DB_TIMEZONE'] : '+07:00';
    if (!is_string($dbTz) || $dbTz === '') {
      $dbTz = '+07:00';
    }
    $this->conn->exec('SET time_zone = ' . $this->conn->quote($dbTz));
  }

  public function getConnection() {
    return $this->conn;
  }
}
