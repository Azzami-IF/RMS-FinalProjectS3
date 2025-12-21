# 🚀 Web Aplikasi Rekomendasi Makanan Sehat

Bangun aplikasi cerdas yang membantu mahasiswa memilih makanan sehat, lengkap dengan fitur modern dan tampilan interaktif!

---

## 🎯 Fitur Utama

- **Integrasi API Gizi:** Edamam API / Nutritionix API
- **Notifikasi Pintar:** Pengingat menu sehat harian (Web Push/Email)
- **Grafik Interaktif:** Visualisasi asupan kalori & nutrisi (Chart.js)
- **Analitik Canggih:** Evaluasi pola makan & rekomendasi personal

---

## 🛠️ Ketentuan Pengerjaan

### 1. Teknologi Inti
- **Backend:** PHP native (tanpa framework MVC)
- **Database:** MySQL/MariaDB
- **Frontend:** CSS framework (Bootstrap/Tailwind/Flowbite), Chart.js
- **Composer:** Hanya library utilitas (JWT, PHPMailer, Web Push)

### 2. Arsitektur & OOP
- Wajib pakai **Class** & **Function** (Object-Oriented Programming)

### 3. Autentikasi & Otorisasi
- Fitur **Login/Logout/Registrasi**
- Hash password: `password_hash()` + `password_verify()` atau Google OAuth 2.0

### 4. Database (Wajib)
- Tabel domain sesuai tema
- **Seed data** (data dummy)

### 5. CRUD (Wajib)
- Minimal **2 entitas** dengan CRUD penuh (Create, Read, Update, Delete)

### 6. Integrasi API (Wajib)
- **API key** di `.env` (jangan di repo)
- Class `ApiClient{Tema}` terpisah + cache TTL

### 7. Notifikasi (Wajib)
- Web Push atau Email (PHPMailer)
- Log ke tabel `notifications` dengan status

### 8. Grafik (Wajib)
- **1 grafik time-series** + **1 grafik kategori** (bar/pie)

### 9. Analitik (Wajib)
- `AnalyticsService` untuk metrik & rekomendasi
- Ringkasan cards + **export CSV**

### 10. Deploy & Dokumentasi
- **README**, ERD, diagram arsitektur, routing endpoints
- SQL dump + folder `/public` siap host
- Source code di **GitHub**

---

> 💡 **Tantang dirimu! Bangun aplikasi yang bukan cuma fungsional, tapi juga impactful untuk gaya hidup sehat.**