<?php
class Auth {
  private $db;

  public function __construct($db) {
    $this->db = $db;
  }

  public function login($email, $password) {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
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
