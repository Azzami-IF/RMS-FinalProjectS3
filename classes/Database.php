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
  }

  public function getConnection() {
    return $this->conn;
  }
}
