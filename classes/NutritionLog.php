<?php

class NutritionLog
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Jika ada tabel nutrition_logs, tambahkan method di sini
    // Untuk sekarang, kosong karena tidak digunakan
}