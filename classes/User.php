<?php
class User {
    protected $db;
    protected $user_id;
    protected $username;
    protected $role;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($username, $password) {
        $this->db->query("SELECT * FROM users WHERE username=:username");
        $this->db->bind('username', $username);
        $user = $this->db->single();

        if ($user && password_verify($password, $user['password'])) {
            $this->user_id = $user['id'];
            $this->username = $user['username'];
            $this->role = $user['role'];
            return $user;
        }
        return false;
    }

    public function getUserId() { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getRole() { return $this->role; }

    public function logActivity($aktivitas) {
        $this->db->query("INSERT INTO riwayat_aktivitas (user_id, aktivitas) VALUES (:user_id, :aktivitas)");
        $this->db->bind('user_id', $this->user_id);
        $this->db->bind('aktivitas', $aktivitas);
        $this->db->execute();
    }
}
?>