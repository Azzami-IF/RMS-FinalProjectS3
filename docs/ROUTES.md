# Routing Endpoints

Dokumen ini merangkum endpoint berbasis file PHP pada project RMS.

Catatan:
- Endpoint `process/*.php` umumnya menerima `POST` (aksi create/update/delete).
- Endpoint `charts/*.php` dan `notifications/api.php` mengembalikan JSON.
- Endpoint di bawah ini menjaga URL lama (tanpa framework/router MVC).

## Pages (GET)
- `/` → `index.php`
- `/home.php` → beranda
- `/dashboard.php` → dashboard user
- `/login.php` → login
- `/register.php` → registrasi
- `/logout.php` → logout
- `/profile.php` → profil
- `/profile_edit.php` → edit profil
- `/profile_register.php` → lengkapi profil (wajib)
- `/settings.php` → pengaturan
- `/schedules.php` → pencatatan menu
- `/goals.php` → target kesehatan
- `/weight_log.php` → log berat badan
- `/evaluation.php` → evaluasi ringkas
- `/recommendation.php` → rekomendasi resep
- `/recipe_detail.php` → detail resep
- `/nutrition_analysis.php` → analisis nutrisi
- `/notifications.php` → riwayat notifikasi in-app
- `/notification_center.php` → tampilan pusat notifikasi

## Admin (GET)
- `/admin/dashboard.php` → dashboard admin
- `/admin/users.php` → kelola pengguna
- `/admin/user_detail.php?id=...` → detail pengguna
- `/admin/user_edit.php?id=...` → edit pengguna
- `/admin/foods.php` → kelola makanan
- `/admin/food_edit.php` / `/admin/food_edit.php?id=...` → tambah/ubah makanan
- `/admin/schedules.php` → lihat catatan makan pengguna
- `/admin/reports.php` → laporan & analitik admin
- `/admin/broadcast.php` → kirim notifikasi in-app (broadcast)

## JSON (GET)
- `/charts/calorie_chart.php`
- `/charts/nutrition_chart.php`
- `/charts/weight_chart.php`
- `/notifications/api.php`

## Actions (POST)
- `/process/login.process.php`
- `/process/register.process.php`
- `/process/profile.process.php`
- `/process/profile_register.process.php`
- `/process/schedule.process.php`
- `/process/goal.process.php`
- `/process/weight.process.php`
- `/process/food.process.php`
- `/process/user.process.php`
- `/process/broadcast.process.php`
- `/process/mark_notifications_read.php`

## Export
- `/export/export_evaluation.php` → CSV evaluasi user (butuh login)

## Cron / CLI (opsional)
Jalankan via CLI:
- `notifications/schedule_notifications.php`
- `notifications/send_daily.php`
- `notifications/send_daily_menu.php`
- `notifications/send_goal_evaluation.php`
- `notifications/send_reminder_log.php`
