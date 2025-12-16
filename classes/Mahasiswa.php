<?php
require_once 'User.php';

class Mahasiswa extends User {
    private $mahasiswa_id;
    private $nama_lengkap;

    public function __construct($db, $user_id) {
        parent::__construct($db);
        $this->db->query("SELECT * FROM mahasiswa WHERE user_id = :user_id");
        $this->db->bind('user_id', $user_id);
        $result = $this->db->single();
        if($result) {
            $this->mahasiswa_id = $result['id'];
            $this->nama_lengkap = $result['nama_lengkap'];
        }
    }

    public function getMahasiswaId() { return $this->mahasiswa_id; }
    public function getNamaLengkap() { return $this->nama_lengkap; }
    
    // ... tambahkan metode khusus mahasiswa lainnya di sini
    // misal: createSchedule(), getReport(), dll.
}
?>