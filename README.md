# RMS (Rekomendasi Makanan Sehat) - Database Documentation

## 📋 Overview
RMS adalah aplikasi web untuk tracking nutrisi dan rekomendasi makanan sehat yang dibangun dengan PHP, MySQL, dan Bootstrap.

## 🗄️ Struktur Database

### Tabel Utama

#### 1. `users` - Data Pengguna
```sql
- id (PRIMARY KEY)
- name, email, password
- role (admin/user)
- phone, date_of_birth, gender
- height_cm, weight_kg, activity_level
- daily_calorie_goal
- avatar, created_at, updated_at, last_login, is_active
```

#### 2. `food_categories` - Kategori Makanan
```sql
- id (PRIMARY KEY)
- name, description, icon
- created_at
```

#### 3. `foods` - Database Makanan
```sql
- id (PRIMARY KEY)
- category_id (FOREIGN KEY)
- name, description, calories
- protein, fat, carbs, fiber, sugar, sodium
- serving_size, image_url
- is_verified, created_by, created_at, updated_at
```

#### 4. `meal_types` - Jenis Makanan
```sql
- id (PRIMARY KEY)
- name, display_name, icon, sort_order
- is_active
```

#### 5. `schedules` - Jadwal Makan
```sql
- id (PRIMARY KEY)
- user_id, food_id, meal_type_id (FOREIGN KEYS)
- schedule_date, quantity, notes
- calories_consumed (auto-calculated)
- created_at, updated_at
```

#### 6. `user_goals` - Target Pengguna
```sql
- id (PRIMARY KEY)
- user_id (FOREIGN KEY)
- goal_type, target_weight_kg, target_date
- daily_calorie_target, daily_protein_target, etc.
- is_active, created_at, updated_at
```

#### 7. `weight_logs` - Log Berat Badan
```sql
- id (PRIMARY KEY)
- user_id (FOREIGN KEY)
- weight_kg, body_fat_percentage, muscle_mass_kg
- notes, logged_at, created_at
```

#### 8. `notifications` - Notifikasi
```sql
- id (PRIMARY KEY)
- user_id (FOREIGN KEY)
- title, message, type, channel, status
- scheduled_at, sent_at, created_at
```

## 🚀 Setup Database

### 1. Import Schema
```bash
mysql -u username -p rms_db < sql.txt
```

### 2. Konfigurasi Environment
Edit file `config/env.php`:
```php
<?php
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'rms_db',
    'DB_USER' => 'your_username',
    'DB_PASS' => 'your_password',
    'MAIL_USER' => 'your_email@example.com',
    'MAIL_PASS' => 'your_email_password',
    'SPOON_API_KEY' => 'your_spoonacular_api_key'
];
```

## 📊 Fitur Database

### Views untuk Analisis
- `daily_nutrition_summary` - Ringkasan nutrisi harian
- `user_progress` - Progress pengguna

### Stored Procedures
- `calculate_meal_calories()` - Hitung kalori otomatis
- `get_food_recommendations()` - Rekomendasi makanan
- `update_user_stats()` - Update statistik user

### Triggers
- Auto-calculate calories saat insert/update schedule

### Indexes
- Optimized untuk performa query yang sering digunakan

## 🔧 Classes PHP

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

## 📈 Contoh Query Analytics

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

## 🔒 Keamanan Database

- Foreign key constraints
- Input validation di application layer
- Password hashing dengan bcrypt
- Prepared statements untuk mencegah SQL injection
- Audit trail dengan created_at/updated_at

## 📊 Sample Data

Database sudah include sample data:
- 6 jenis makanan (breakfast, lunch, dll.)
- 8 kategori makanan
- 18+ sample makanan dengan nutrisi lengkap
- 1 admin user (admin@rms.com / password)

## 🔄 Migrasi dari Versi Lama

Jika upgrade dari database lama:
1. Backup data existing
2. Run schema baru
3. Migrate users: `INSERT INTO new_users SELECT * FROM old_users`
4. Migrate foods dengan field tambahan default
5. Update schedules dengan meal_type_id default

## 🎯 Rekomendasi Penggunaan

1. **Untuk Development**: Gunakan sample data yang tersedia
2. **Production**: Setup proper backup dan monitoring
3. **Performance**: Monitor slow queries dan optimize indexes
4. **Security**: Regular security audit dan update dependencies

## 📞 Support

Untuk pertanyaan atau issues, silakan buat issue di repository atau hubungi tim development.