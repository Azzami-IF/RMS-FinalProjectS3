
# RMS (Rekomendasi Makanan Sehat)

## Ringkasan
RMS adalah aplikasi web untuk pencatatan nutrisi dan rekomendasi makanan sehat. Aplikasi dibangun menggunakan PHP native, MySQL/MariaDB, dan Bootstrap. Sistem terintegrasi dengan Edamam API untuk pencarian resep dan analisis nutrisi, menyediakan analitik, notifikasi, dan CRUD untuk entitas utama.

## Diagram
- Activity Diagram: `Activity.jpg`
- Use Case Diagram: `usecase.jpg`

## Teknologi
- Backend: PHP (native)
- Database: MySQL/MariaDB
- Frontend: Bootstrap, Chart.js
- Library: PHPMailer, vlucas/phpdotenv

## Prasyarat
- PHP 8.x (disarankan menggunakan Laragon/XAMPP)
- MySQL/MariaDB
- Composer

## Instalasi Lokal (Laragon)
1. Pastikan proyek berada di direktori web server, contoh: `C:\laragon\www\RMS`.
2. Jalankan instalasi dependency:
    ```bash
    composer install
    ```
3. Buat file environment:
    - Salin `.env.example` menjadi `.env`, lalu isi nilainya.

## Konfigurasi Environment
Konfigurasi dibaca melalui `config/env.php` menggunakan `vlucas/phpdotenv`. Variabel yang digunakan:

```dotenv
DB_HOST=localhost
DB_NAME=db_rms
DB_USER=root
DB_PASS=

EDAMAM_APP_ID=
EDAMAM_APP_KEY=
EDAMAM_USER_ID=

MAIL_USER=
MAIL_PASS=
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM=
```

Catatan keamanan: file `.env` tidak boleh di-commit ke repository.

## Setup Database
1. Buat database dan import skema + seed data:
    ```bash
    mysql -u root -p < sql.txt
    ```
    Secara default, skrip SQL menggunakan database `db_rms`.

2. Akun demo (jika menggunakan seed data):
    - `admin@rms.local` / `admin123`
    - `user@rms.local` / `user123`

## Menjalankan Aplikasi
- Melalui Laragon: jalankan Apache/Nginx + MySQL, lalu akses `http://localhost/RMS/`.

## Fitur
- Autentikasi: login, registrasi, hashing password
- CRUD: makanan (`foods`) dan jadwal makan (`schedules`)
- Integrasi API: Edamam (pencarian resep dan analisis)
- Notifikasi: in-app dan email (PHPMailer)
- Analitik dan visualisasi: Chart.js, view `daily_nutrition_summary` dan `user_progress`
- Ekspor: CSV untuk laporan tertentu

## Notifikasi (Script)
Script notifikasi berada di folder `notifications/` dan dapat dijalankan melalui browser atau CLI.

Contoh (CLI):
```bash
php notifications/send_daily_menu.php --force
```

## Struktur Direktori (Ringkas)
- `classes/`: class OOP (Database, Cache, Service)
- `controllers/`: controller untuk halaman yang memerlukan pemrosesan
- `process/`: handler aksi (create/update/delete)
- `notifications/`: pengiriman dan pencatatan notifikasi
- `assets/`: CSS/JS/Font/Gambar
- `sql.txt`: skema database + seed data

## Troubleshooting
- Jika koneksi database gagal, pastikan variabel `DB_HOST/DB_NAME/DB_USER/DB_PASS` pada `.env` sesuai.
- Jika notifikasi email gagal, pastikan `MAIL_USER` dan `MAIL_PASS` valid (untuk Gmail, gunakan App Password).

- `/analytics.php` - Analytics & charts
- `/notifications.php` - Notification history
- `/schedules.php` - Meal scheduling
- `/admin/foods.php` - Food management (CRUD)
- `/admin/schedules.php` - Schedule management (CRUD)

---

### Core Classes
- `Database` - Koneksi database
- `Auth` - Autentikasi user
- `User` - Manajemen user
- `Food` - Manajemen makanan
- `FoodCategory` - Kategori makanan
- `Schedule` - Jadwal makan
- `MealType` - Jenis makanan
- `AnalyticsService` - Analisis data
- `NotificationService` - Notifikasi
- `UserGoal` - Target user
- `WeightLog` - Log berat badan


## Analytics & Export CSV

The analytics module provides:
- Time-series and categorical charts (Chart.js)
- Summary cards for calories, nutrients, and progress
- **Export CSV:** Analytics data can be exported as CSV from the analytics page (see "Export" button on analytics view)

---

### Kalori Mingguan
```sql
SELECT
    DAYNAME(schedule_date) as hari,
    SUM(calories_consumed) as kalori
FROM schedules
WHERE user_id = ? AND schedule_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
GROUP BY schedule_date ORDER BY schedule_date;
```

### Progress Target
```sql
SELECT
    ug.daily_calorie_target,
    AVG(dns.total_calories) as rata_rata_kalori
FROM user_goals ug
LEFT JOIN daily_nutrition_summary dns ON dns.user_id = ug.user_id
WHERE ug.user_id = ? AND ug.is_active = TRUE;
```


## Testing & Validation

Manual test cases to validate core features:
- User registration, login, and logout
- Add, edit, delete food items (CRUD)
- Add, edit, delete meal schedules (CRUD)
- Search and view food recommendations (Edamam API)
- Receive and view notifications (email & in-app)
- View analytics and export CSV

---

- Foreign key constraints
- Input validation di application layer
- Password hashing dengan bcrypt
- Prepared statements untuk mencegah SQL injection
- Audit trail dengan created_at/updated_at


## Notification System

Notifications are sent daily via email (PHPMailer) and stored in the `notifications` table. In-app notifications are displayed in the dashboard and notification page. Web Push is not implemented; all notifications use email and in-app channels.

---

Database sudah include sample data:
- 6 jenis makanan (breakfast, lunch, dll.)
- 8 kategori makanan
- 18+ sample makanan dengan nutrisi lengkap
- 1 admin user (admin@rms.com / password)


## Security

The application uses:
- Foreign key constraints
- Input validation and prepared statements
- Password hashing with bcrypt
- Audit trail with created_at/updated_at

---

Jika upgrade dari database lama:
1. Backup data existing
2. Run schema baru
3. Migrate users: `INSERT INTO new_users SELECT * FROM old_users`
4. Migrate foods dengan field tambahan default
5. Update schedules dengan meal_type_id default


## Additional Notes

- For development, use the provided sample data.
- For production, ensure regular backups and monitor performance.
- All dependencies are managed via Composer (PHPMailer, vlucas/phpdotenv, etc.).
- For support, open an issue in the repository or contact the development team.

1. **Untuk Development**: Gunakan sample data yang tersedia
2. **Production**: Setup proper backup dan monitoring
3. **Performance**: Monitor slow queries dan optimize indexes
4. **Security**: Regular security audit dan update dependencies

## 📞 Support

Untuk pertanyaan atau issues, silakan buat issue di repository atau hubungi tim development.