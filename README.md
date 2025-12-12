Final Project (Set Up instruction)
===============
"Program masih dalam masa pembangunan dan belum sepenuhnya selesai"

1. Konfigurasi Database (`db.php`):
Masing-masing anggota tim harus melakukan penyesuaian koneksi database lokal.
Silakan masuk ke `db.php` di vscode kemudian input informasi MyAdmin kalian ke string:

    `$user = "root";`

    `$password = "";`


2.  Kemudian masuk ke localhost PhpMyAdmin kalian dan buat database dengan struktur berikut ke SQL Query:

    >CREATE DATABASE myapp;
    >
    >USE myapp;
    >
    >CREATE TABLE users ( id INT AUTO_INCREMENT PRIMARY KEY, username
    VARCHAR(100) NOT NULL UNIQUE, email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, role ENUM(‘admin’,‘user’) DEFAULT
    ‘user’, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP );
    >
    >CREATE TABLE log_login ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT,
    ip_address VARCHAR(50), user_agent TEXT, status VARCHAR(20), login_time
    TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES
    users(id) );

3. Untuk pembuatan akun, bisa menggunakan Registrasi dalam kode, kemudian akun dapat di buat menjadi admin dengan cara mengubahnya di database dalam tabel `users`.
<br> <br>

FINAL PROJECT:
===============
## Tema: Web Aplikasi Rekomendasi Makanan Sehat

**Fitur Utama:**
- API: Edamam API / Nutritionix API
- Notifikasi: Pengingat menu sehat harian
- Grafik: Asupan kalori & nutrisi
- Analitik: Evaluasi pola makan mahasiswa

---

## Ketentuan Pengerjaan

### 1. Teknologi Inti
- **Backend:** PHP native (tanpa framework MVC seperti Laravel/CodeIgniter)
- **Database:** MySQL/MariaDB
- **Frontend:** CSS framework (Bootstrap/Tailwind/Flowbite), Chart.js untuk grafik
- **Composer:** Library utilitas saja (JWT, PHPMailer, Web Push), bukan framework

### 2. Arsitektur & OOP
- Harus menggunakan Class dan Function (Object-Oriented)

### 3. Autentikasi & Otorisasi
- Fitur Login/Logout/Registrasi
- Hash password: `password_hash()` + `password_verify()` atau Google OAuth 2.0

### 4. Database (Wajib)
- Tabel domain sesuai tema
- Seed data (data dummy)

### 5. CRUD (Wajib)
- Minimal 2 entitas dengan CRUD penuh (Create, Read, Update, Delete)

### 6. Integrasi API (Wajib)
- API key di `.env` (jangan di repo)
- Class `ApiClient{Tema}` terpisah dengan cache TTL

### 7. Notifikasi (Wajib)
- Web Push atau Email (PHPMailer)
- Log ke tabel `notifications` dengan status

### 8. Grafik (Wajib)
- 1 grafik time-series + 1 grafik kategori (bar/pie)

### 9. Analitik (Wajib)
- `AnalyticsService` untuk metrik & rekomendasi
- Ringkasan cards + export CSV

### 10. Deploy & Dokumentasi
- README, ERD, diagram arsitektur, routing endpoints
- SQL dump + folder `/public` siap host
- Source code di GitHub

---

## Pembagian Jobdesk

**System Analyst (1 orang):** Use Case, Activity & Class Diagram, ERD

**Programmer (2 orang):** Database, Coding, Git, Hosting (VPS/Dedicated, bukan shared hosting)

