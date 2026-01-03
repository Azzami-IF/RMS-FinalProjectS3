<?php
class Auth {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  public function login($identifier, $password) {
    // Accept email or name
    $stmt = $this->db->prepare("SELECT * FROM users WHERE email=? OR name=?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user'] = $user;
      return true;
    }
    return false;
  }

  public function register($name,$email,$password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $this->db->prepare(
      "INSERT INTO users(name,email,password) VALUES (?,?,?)"
    );
    return $stmt->execute([$name,$email,$hash]);
  }
}
