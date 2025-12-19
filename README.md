
# RMS (Rekomendasi Makanan Sehat) - Documentation

## Overview
RMS is a web application for nutrition tracking and healthy food recommendations, built with PHP (native), MySQL/MariaDB, and Bootstrap. The system integrates the Edamam API for food and nutrition data, provides analytics, notifications, and supports full CRUD for core entities.

---

## System Architecture & Diagrams

- **ERD & Architecture:**
    - [Activity Diagram](Activity.jpg)
    - [Use Case Diagram](usecase.jpg)

---


## Features

- **API Integration:** Edamam API (nutrition & food search)
- **Smart Notifications:** Daily healthy menu reminders (Email/PHPMailer)
- **Interactive Charts:** Calorie & nutrition visualization (Chart.js)
- **Advanced Analytics:** Eating pattern evaluation & personalized recommendations
- **Authentication:** Login, registration, password hashing (bcrypt)
- **CRUD:** Full Create, Read, Update, Delete for foods and schedules
- **Seed Data:** Sample data for development/testing
- **Export CSV:** Analytics data exportable as CSV
- **Documentation:** ERD, architecture diagram, endpoint routing, SQL dump

---


## Database Structure

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


## Setup & Deployment

### 1. Import Schema & Seed Data
Import the schema and sample data using:
```bash
mysql -u username -p rms_db < sql.txt
```

### 2. Environment Configuration
Edit `config/env.php` or use a `.env` file for sensitive credentials:
```php
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'rms_db',
    'DB_USER' => 'your_username',
    'DB_PASS' => 'your_password',
    'MAIL_USER' => 'your_email@example.com',
    'MAIL_PASS' => 'your_email_password',
    'EDAMAM_APP_ID' => 'your_edamam_app_id',
    'EDAMAM_APP_KEY' => 'your_edamam_app_key'
];
```

### 3. Deployment
- Place all files in your web server's public directory (e.g., `/public` or `/var/www/html`).
- Ensure `sql.txt` is imported and environment variables are set.
- Access the app via your browser.

---

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


## API Integration: Edamam

The application uses the Edamam API for food search and nutrition analysis. Example usage:

**Endpoint:**
`https://api.edamam.com/api/recipes/v2?type=public&q={query}&app_id={APP_ID}&app_key={APP_KEY}`

**Parameters:**
- `q`: Search query (e.g., "chicken")
- `calories`: Calorie range (e.g., "0-600")
- `diet`: Diet label (optional)
- `health`: Health label (optional)

**Response:** JSON with recipe hits, nutrition info, and ingredients.

All API credentials are stored securely in `.env` or `config/env.php`.

---

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


## Routing & Endpoints

Main endpoints:
- `/index.php` - Dashboard
- `/login.php` - User login
- `/register.php` - User registration
- `/recommendation.php` - Food recommendations (Edamam API)
- `/nutrition_analysis.php` - Nutrition analysis
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