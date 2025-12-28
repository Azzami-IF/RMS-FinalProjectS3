
# RMS (Rekomendasi Makanan Sehat)

RMS adalah aplikasi web untuk pencatatan asupan nutrisi, rekomendasi menu (Edamam), notifikasi (in-app + email), analitik (Chart.js), dan export CSV.

## Dokumen & Diagram
- Activity Diagram: `docs/Activity.jpg`
- Use Case Diagram: `docs/usecase.jpg`
- ERD: `docs/ERD.md`
- Arsitektur: `docs/ARCHITECTURE.md`
- Routing: `docs/ROUTES.md`

## Teknologi
- Backend: PHP native
- Database: MySQL/MariaDB
- Frontend: Bootstrap, Chart.js
- Library: `phpmailer/phpmailer`, `vlucas/phpdotenv`

## Prasyarat
- PHP 8.x
- MySQL/MariaDB
- Composer

## Instalasi Lokal (Laragon / XAMPP)
1. Letakkan project:
	- Laragon: `C:\laragon\www\RMS`
	- XAMPP: `C:\xampp\htdocs\RMS`
2. Install dependency (Composer).
	- Jika `composer` tersedia di PATH:
		- `composer install`
	- Jika pakai Laragon dan `composer` tidak dikenali di terminal, jalankan Composer via PHP Laragon:
		- `C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe C:\laragon\bin\composer\composer.phar install --no-interaction --prefer-source`
		- (opsional) regenerate autoload: `C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe C:\laragon\bin\composer\composer.phar dump-autoload`
3. Salin `.env.example` menjadi `.env` lalu isi nilainya.
4. Import database (pilih salah satu):
	- Via CLI: `mysql -u root -p < docs/query.sql`
	- Via GUI: buka HeidiSQL/phpMyAdmin → jalankan isi `docs/query.sql`
5. Akses via browser: `http://localhost/RMS/`.

Reset total (opsional): di `docs/query.sql` ada blok **OPTIONAL: FULL RESET (DESTRUCTIVE)** yang bisa di-uncomment untuk `DROP DATABASE` + recreate.

Alternatif (tanpa Apache/Nginx): jalankan PHP built-in server dengan router `public/index.php`:
- `php -S 127.0.0.1:8001 -t public public/index.php`

Catatan: router script diperlukan agar URL seperti `/login.php` dan `/assets/*` bekerja saat docroot di `public/`.

## Troubleshooting

### Fatal error: symfony/polyfill-ctype/bootstrap.php tidak ditemukan
Gejala umum:
- Error seperti: `Failed opening required .../symfony/polyfill-ctype/bootstrap.php`

Penyebab paling sering:
- Folder `vendor/` korup / tidak lengkap (mis. hasil copy-paste project tanpa dependensi lengkap).
- Composer berjalan tanpa dukungan zip/unzip (Composer jadi fallback ke source, tapi instalasi sebelumnya sudah terlanjur tidak lengkap).

Solusi cepat:
1. Hapus folder `vendor/`.
2. Jalankan ulang install dependencies:
	- PATH Composer: `composer install`
	- Laragon (tanpa PATH):
		- `C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe C:\laragon\bin\composer\composer.phar install --no-interaction --prefer-source`
3. Jika masih error autoload, jalankan: `... composer.phar dump-autoload`

## Konfigurasi Environment
Konfigurasi dibaca via `config/env.php` (Dotenv `safeLoad()`), contoh variabel di `.env.example`:

- Database: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Edamam: `EDAMAM_APP_ID`, `EDAMAM_APP_KEY`, `EDAMAM_USER_ID`
- SMTP: `MAIL_USER`, `MAIL_PASS`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_FROM`
- SMTP: `MAIL_FROM_NAME` (nama pengirim)

Catatan: `.env` tidak boleh di-commit.

## Akun Uji (Seed Data)
Saat import `docs/query.sql`, aplikasi akan menambahkan akun demo (jika ID belum terpakai):
- Admin: `admin@rms.local` / `admin123`
- User: `user@rms.local` / `user123`

## Routing Endpoints

**Halaman (root)**
- `index.php`, `home.php`, `dashboard.php`
- `login.php`, `register.php`, `logout.php` (login: Nama atau Email)
- `profile.php`, `profile_edit.php`, `profile_register.php`, `settings.php`
- `schedules.php`, `goals.php`, `weight_log.php`, `evaluation.php`
- `recommendation.php`, `recipe_detail.php`, `nutrition_analysis.php`
- `notifications.php`

**Admin**
- `admin/dashboard.php`
- `admin/users.php`, `admin/user_detail.php`, `admin/user_edit.php`
- `admin/foods.php`, `admin/food_edit.php`
- `admin/schedules.php`, `admin/reports.php`, `admin/broadcast.php`

**API/JSON**
- Charts: `charts/calorie_chart.php`, `charts/nutrition_chart.php`, `charts/weight_chart.php`
- Notifikasi in-app: `notifications/api.php`

**Actions (POST)**
- Autentikasi & profil: `process/login.process.php`, `process/register.process.php`, `process/profile*.process.php`
- Schedule/goal/weight/food/user/broadcast: `process/*.process.php`
- Notifikasi: `process/mark_notifications_read.php`

**Export**
- CSV evaluasi: `export/export_evaluation.php`

**Cron/CLI notifikasi**
- Scheduler: `notifications/schedule_notifications.php`
- Sender: `notifications/send_daily.php`, `notifications/send_daily_menu.php`, `notifications/send_goal_evaluation.php`, `notifications/send_reminder_log.php`

## Deploy (Folder /public siap host)

Untuk shared hosting / server yang membutuhkan docroot terpisah, gunakan folder `public/` sebagai DocumentRoot.

**Apache**
- Point DocumentRoot ke `.../RMS/public`.
- Pastikan `mod_rewrite` aktif (file `public/.htaccess`).

**Nginx (contoh konsep)**
- Set `root` ke folder `public/`.
- Route request ke `public/index.php` (front controller).

Catatan:
- `public/index.php` akan me-`require` file PHP yang relevan dari root project (whitelist directory: `admin/`, `process/`, `charts/`, `notifications/`, `export/`).
- Static `assets/` diproxy lewat `public/asset.php` agar file `assets/*` tetap bisa diakses walau berada di luar docroot.
- `public/index.php` mendukung hosting di subfolder (mis. `/RMS/...`) dengan mendeteksi base path dari `SCRIPT_NAME`.

## Catatan Penting (Notifikasi)
- Tabel `notifications` dipakai multi-channel.
- In-app UI/API harus selalu memfilter `channel='in_app'` agar log email tidak ikut tampil.
- Scheduler CLI `notifications/schedule_notifications.php` menyimpan status eksekusi ke tabel `notification_schedules` (sudah termasuk di `docs/query.sql`).
