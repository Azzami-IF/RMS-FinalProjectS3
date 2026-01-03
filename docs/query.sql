CREATE DATABASE IF NOT EXISTS db_rms;
USE db_rms;

-- =========================
-- OPTIONAL: FULL RESET (DESTRUCTIVE)
-- =========================
-- Jika ingin reset total (hapus semua data + schema), jalankan baris di bawah ini
-- dengan menghapus prefix komentar "--".
--
-- DROP DATABASE IF EXISTS db_rms;
-- CREATE DATABASE db_rms;
-- USE db_rms;

-- =========================
-- ARCHIVED MIGRATIONS (queries embedded)
-- =========================
-- Folder migrations sudah tidak dipakai; isi query-nya disimpan di bawah ini
-- sebagai referensi.
-- Catatan istilah: kadang ditulis sebagai rms/migration (tanpa 's').
/*

== migrations/2025_12_20_align_goals_notifications.sql ==
-- Align DB schema with current code (goals evaluation/progress + notification types)
-- Notes:
-- - Run once per database.
-- - MySQL does NOT support `ADD COLUMN IF NOT EXISTS`; this file uses INFORMATION_SCHEMA checks instead.

-- 1) Notifications: allow flexible semantic types (goal/tip/reminder/menu) and severity types.
ALTER TABLE notifications
  MODIFY type VARCHAR(20) DEFAULT 'info';

-- 2) User goals: add evaluation/progress tracking fields used by UserGoal.php.
-- Add `evaluation`
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'user_goals'
        AND COLUMN_NAME = 'evaluation'
    ),
    'SELECT 1',
    'ALTER TABLE user_goals ADD COLUMN evaluation TEXT NULL AFTER daily_carbs_target'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add `status`
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'user_goals'
        AND COLUMN_NAME = 'status'
    ),
    'SELECT 1',
    "ALTER TABLE user_goals ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER evaluation"
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add `last_notif`
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'user_goals'
        AND COLUMN_NAME = 'last_notif'
    ),
    'SELECT 1',
    'ALTER TABLE user_goals ADD COLUMN last_notif TIMESTAMP NULL AFTER status'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add `progress`
SET @sql := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'user_goals'
        AND COLUMN_NAME = 'progress'
    ),
    'SELECT 1',
    'ALTER TABLE user_goals ADD COLUMN progress DECIMAL(5,2) DEFAULT 0 AFTER last_notif'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

== migrations/2025_12_21_add_action_url_to_notifications.sql ==
-- Add action_url column to notifications (needed by NotificationService + UI)
ALTER TABLE notifications
  ADD COLUMN action_url VARCHAR(512) NULL AFTER message;

== migrations/2025_12_21_extend_food_image_url.sql ==
-- Extend foods.image_url to support longer CDN URLs (Edamam/etc)
ALTER TABLE foods MODIFY COLUMN image_url VARCHAR(1024) NULL;

== migrations/2025_12_21_add_notification_schedules.sql ==
-- Add notification_schedules table for notifications/schedule_notifications.php
-- This table logs scheduler runs and supports UPSERT via UNIQUE(schedule_type).
CREATE TABLE IF NOT EXISTS notification_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  schedule_type VARCHAR(20) NOT NULL,
  last_run DATETIME NULL,
  status ENUM('success','failed') NOT NULL DEFAULT 'success',
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_schedule_type (schedule_type)
);

*/

START TRANSACTION;

-- =========================
-- TABLE: users
-- =========================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') DEFAULT 'user',
  avatar VARCHAR(255),
  phone VARCHAR(20),
  date_of_birth DATE,
  gender ENUM('male','female','other'),
  height_cm DECIMAL(5,2),
  weight_kg DECIMAL(5,2),
  activity_level ENUM('sedentary','light','moderate','active','very_active') DEFAULT 'moderate',
  daily_calorie_goal INT DEFAULT 2000,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL,
  is_active TINYINT(1) DEFAULT 1
);

-- =========================
-- TABLE: food_categories
-- =========================
CREATE TABLE IF NOT EXISTS food_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  icon VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- TABLE: foods
-- =========================
CREATE TABLE IF NOT EXISTS foods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  calories DECIMAL(8,2) NOT NULL,
  protein DECIMAL(6,2) DEFAULT 0,
  fat DECIMAL(6,2) DEFAULT 0,
  carbs DECIMAL(6,2) DEFAULT 0,
  fiber DECIMAL(6,2) DEFAULT 0,
  sugar DECIMAL(6,2) DEFAULT 0,
  sodium DECIMAL(6,2) DEFAULT 0,
  image_url VARCHAR(1024),
  is_verified TINYINT(1) DEFAULT 0,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY idx_food_search (name, description),
  KEY idx_foods_category_id (category_id),
  KEY idx_foods_created_by (created_by),
  CONSTRAINT fk_foods_category_id FOREIGN KEY (category_id) REFERENCES food_categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_foods_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =========================
-- TABLE: meal_types
-- =========================
CREATE TABLE IF NOT EXISTS meal_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  display_name VARCHAR(50) NOT NULL,
  icon VARCHAR(50),
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1
);

-- =========================
-- TABLE: schedules
-- =========================
CREATE TABLE IF NOT EXISTS schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  food_id INT NOT NULL,
  meal_type_id INT,
  schedule_date DATE NOT NULL,
  quantity DECIMAL(6,2) DEFAULT 1.00,
  notes TEXT,
  calories_consumed DECIMAL(8,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_schedules_user_date (user_id, schedule_date),
  KEY idx_schedules_food_id (food_id),
  KEY idx_schedules_meal_type_id (meal_type_id),
  CONSTRAINT fk_schedules_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_schedules_food_id FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
  CONSTRAINT fk_schedules_meal_type_id FOREIGN KEY (meal_type_id) REFERENCES meal_types(id) ON DELETE SET NULL
);

-- =========================
-- TABLE: notifications
-- =========================
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  action_url VARCHAR(512) DEFAULT NULL,
  type VARCHAR(20) DEFAULT 'info',
  channel ENUM('email','push','in_app') DEFAULT 'in_app',
  status ENUM('unread','read','sent','failed') DEFAULT 'unread',
  scheduled_at TIMESTAMP NULL,
  sent_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_user (user_id, status),
  CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- TABLE: notification_schedules
-- (dipakai oleh notifications/schedule_notifications.php)
-- =========================
CREATE TABLE IF NOT EXISTS notification_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  schedule_type VARCHAR(20) NOT NULL,
  last_run DATETIME NULL,
  status ENUM('success','failed') NOT NULL DEFAULT 'success',
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_schedule_type (schedule_type)
);

-- =========================
-- TABLE: user_goals
-- =========================
CREATE TABLE IF NOT EXISTS user_goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  goal_type ENUM('weight_loss','weight_gain','maintain','muscle_gain') NOT NULL,
  target_weight_kg DECIMAL(5,2),
  target_date DATE,
  weekly_weight_change DECIMAL(3,2),
  daily_calorie_target INT NOT NULL,
  daily_protein_target DECIMAL(6,2),
  daily_fat_target DECIMAL(6,2),
  daily_carbs_target DECIMAL(6,2),
  evaluation TEXT,
  status VARCHAR(20) DEFAULT 'active',
  last_notif TIMESTAMP NULL,
  progress DECIMAL(5,2) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_user_goals_user_active (user_id, is_active),
  CONSTRAINT fk_user_goals_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- TABLE: user_preferences
-- =========================
CREATE TABLE IF NOT EXISTS user_preferences (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  preference_key VARCHAR(50) NOT NULL,
  preference_value TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_preference (user_id, preference_key),
  CONSTRAINT fk_user_preferences_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- TABLE: weight_logs
-- =========================
CREATE TABLE IF NOT EXISTS weight_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  weight_kg DECIMAL(5,2) NOT NULL,
  body_fat_percentage DECIMAL(4,2),
  muscle_mass_kg DECIMAL(5,2),
  notes TEXT,
  logged_at DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_date (user_id, logged_at),
  CONSTRAINT fk_weight_logs_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- PROCEDURES
-- =========================
DELIMITER $$

DROP PROCEDURE IF EXISTS calculate_meal_calories$$
DROP PROCEDURE IF EXISTS get_food_recommendations$$
DROP PROCEDURE IF EXISTS update_user_stats$$

CREATE PROCEDURE calculate_meal_calories(IN schedule_id INT)
BEGIN
  UPDATE schedules s
  JOIN foods f ON s.food_id = f.id
  SET s.calories_consumed = f.calories * s.quantity
  WHERE s.id = schedule_id;
END$$

CREATE PROCEDURE get_food_recommendations(
  IN user_id INT,
  IN meal_type VARCHAR(50),
  IN calorie_limit INT
)
BEGIN
  SELECT f.*, fc.name AS category_name
  FROM foods f
  LEFT JOIN food_categories fc ON f.category_id = fc.id
  WHERE f.calories <= calorie_limit
    AND f.is_verified = 1
  ORDER BY f.calories DESC
  LIMIT 10;
END$$

CREATE PROCEDURE update_user_stats(IN user_id INT)
BEGIN
  UPDATE users
  SET last_login = CURRENT_TIMESTAMP
  WHERE id = user_id;

  UPDATE users u
  SET weight_kg = (
    SELECT weight_kg FROM weight_logs
    WHERE user_id = u.id
    ORDER BY logged_at DESC
    LIMIT 1
  )
  WHERE u.id = user_id;
END$$

DELIMITER ;

-- =========================
-- TRIGGERS
-- =========================
DELIMITER $$

DROP TRIGGER IF EXISTS calculate_calories_on_insert$$
DROP TRIGGER IF EXISTS calculate_calories_on_update$$

CREATE TRIGGER calculate_calories_on_insert
BEFORE INSERT ON schedules
FOR EACH ROW
BEGIN
  SET NEW.calories_consumed =
    (SELECT calories FROM foods WHERE id = NEW.food_id) * NEW.quantity;
END$$

CREATE TRIGGER calculate_calories_on_update
BEFORE UPDATE ON schedules
FOR EACH ROW
BEGIN
  IF NEW.food_id != OLD.food_id OR NEW.quantity != OLD.quantity THEN
    SET NEW.calories_consumed =
      (SELECT calories FROM foods WHERE id = NEW.food_id) * NEW.quantity;
  END IF;
END$$

DELIMITER ;

-- =========================
-- VIEWS
-- =========================
DROP VIEW IF EXISTS daily_nutrition_summary;
DROP VIEW IF EXISTS user_progress;

CREATE VIEW daily_nutrition_summary AS
SELECT
  s.user_id,
  s.schedule_date,
  SUM(s.calories_consumed) AS total_calories,
  SUM(f.protein * s.quantity) AS total_protein,
  SUM(f.fat * s.quantity) AS total_fat,
  SUM(f.carbs * s.quantity) AS total_carbs,
  SUM(f.fiber * s.quantity) AS total_fiber,
  COUNT(s.id) AS total_meals
FROM schedules s
JOIN foods f ON s.food_id = f.id
GROUP BY s.user_id, s.schedule_date;

CREATE VIEW user_progress AS
SELECT
  u.id,
  u.name,
  u.daily_calorie_goal,
  COALESCE(AVG(dns.total_calories),0) AS avg_daily_calories,
  COUNT(DISTINCT dns.schedule_date) AS days_logged,
  COALESCE(SUM(dns.total_meals),0) AS total_meals_logged,
  MAX(wl.weight_kg) AS current_weight,
  MIN(wl.weight_kg) AS starting_weight
FROM users u
LEFT JOIN daily_nutrition_summary dns ON u.id = dns.user_id
LEFT JOIN weight_logs wl ON u.id = wl.user_id
WHERE u.is_active = 1
GROUP BY u.id;

-- =========================
-- SEED DATA (Demo)
-- =========================
-- Login demo:
-- admin@rms.local / admin123
-- user@rms.local  / user123

-- Insert demo users only if IDs are free (avoid overwriting existing data)
INSERT INTO users (id, name, email, password, role, gender, height_cm, weight_kg, activity_level, daily_calorie_goal, is_active)
SELECT 1, 'Admin RMS', 'admin@rms.local', '$2y$10$72PQ.DXNSZKDBlrfNtTsQO/q4KUnCIdmnILmgtNA9HOcI2UyBf4K6', 'admin', 'other', 170.00, 70.00, 'moderate', 2000, 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE id = 1);

INSERT INTO users (id, name, email, password, role, gender, height_cm, weight_kg, activity_level, daily_calorie_goal, is_active)
SELECT 2, 'Demo User', 'user@rms.local', '$2y$10$up4i2hStplzmf3Q4/DASAuiN0sbJOAsEeyrE3NU/5p31SLD9vdELK', 'user', 'male', 172.00, 75.00, 'moderate', 2000, 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE id = 2);

INSERT INTO user_preferences (user_id, preference_key, preference_value)
VALUES
  (1, 'theme', 'light'),
  (1, 'notifications_email', '0'),
  (1, 'notifications_inapp', '1'),
  (2, 'theme', 'light'),
  (2, 'notifications_email', '0'),
  (2, 'notifications_inapp', '1')
ON DUPLICATE KEY UPDATE
  preference_value = VALUES(preference_value),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO meal_types (id, name, display_name, icon, sort_order, is_active)
VALUES
  (1, 'breakfast', 'Sarapan', 'bi-sunrise', 1, 1),
  (2, 'lunch', 'Makan Siang', 'bi-brightness-high', 2, 1),
  (3, 'dinner', 'Makan Malam', 'bi-moon-stars', 3, 1),
  (4, 'snack', 'Snack', 'bi-apple', 4, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  display_name = VALUES(display_name),
  icon = VALUES(icon),
  sort_order = VALUES(sort_order),
  is_active = VALUES(is_active);

INSERT INTO food_categories (id, name, description, icon)
VALUES
  (1, 'Protein', 'Sumber protein', 'bi-egg-fried'),
  (2, 'Karbohidrat', 'Sumber energi', 'bi-bread-slice'),
  (3, 'Sayur', 'Serat dan vitamin', 'bi-flower1'),
  (4, 'Buah', 'Buah segar', 'bi-apple'),
  (5, 'Minuman', 'Minuman sehat', 'bi-cup-straw')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  icon = VALUES(icon);

INSERT INTO foods (id, category_id, name, description, calories, protein, fat, carbs, fiber, sugar, sodium, image_url, is_verified, created_by)
VALUES
  (1, 1, 'Dada Ayam Panggang (100g)', 'Protein tinggi, rendah lemak', 165.00, 31.00, 3.60, 0.00, 0.00, 0.00, 74.00, NULL, 1, 1),
  (2, 2, 'Nasi Putih (1 porsi)', 'Sumber karbohidrat', 206.00, 4.20, 0.40, 45.00, 0.60, 0.10, 2.00, NULL, 1, 1),
  (3, 3, 'Brokoli (1 porsi)', 'Sayur tinggi serat', 55.00, 3.70, 0.60, 11.20, 3.80, 2.20, 33.00, NULL, 1, 1),
  (4, 1, 'Telur Rebus (1 butir)', 'Protein + lemak sehat', 78.00, 6.30, 5.30, 0.60, 0.00, 0.60, 62.00, NULL, 1, 1),
  (5, 4, 'Pisang (1 buah)', 'Buah sumber karbohidrat cepat', 105.00, 1.30, 0.40, 27.00, 3.10, 14.00, 1.00, NULL, 1, 1),
  (6, 5, 'Susu Rendah Lemak (1 gelas)', 'Minuman dengan protein', 120.00, 8.00, 3.00, 12.00, 0.00, 12.00, 100.00, NULL, 1, 1),
  (7, 2, 'Oatmeal (1 porsi)', 'Karbohidrat kompleks', 150.00, 5.00, 3.00, 27.00, 4.00, 1.00, 2.00, NULL, 1, 1),
  (8, 3, 'Salad Sayur (1 porsi)', 'Sayur segar rendah kalori', 80.00, 2.00, 4.00, 10.00, 4.00, 3.00, 120.00, NULL, 1, 1)
ON DUPLICATE KEY UPDATE
  category_id = VALUES(category_id),
  name = VALUES(name),
  description = VALUES(description),
  calories = VALUES(calories),
  protein = VALUES(protein),
  fat = VALUES(fat),
  carbs = VALUES(carbs),
  fiber = VALUES(fiber),
  sugar = VALUES(sugar),
  sodium = VALUES(sodium),
  image_url = VALUES(image_url),
  is_verified = VALUES(is_verified),
  created_by = VALUES(created_by),
  updated_at = CURRENT_TIMESTAMP;

-- Weight logs (demo user): keep 1 row per day (unique_user_date)
INSERT INTO weight_logs (user_id, weight_kg, body_fat_percentage, muscle_mass_kg, notes, logged_at)
VALUES
  (2, 75.00, 22.50, 30.00, 'Mulai tracking', DATE_SUB(CURDATE(), INTERVAL 21 DAY)),
  (2, 74.60, 22.20, 30.10, 'Minggu 1', DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
  (2, 74.20, 22.00, 30.20, 'Minggu 2', DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
  (2, 74.00, 21.80, 30.30, 'Terbaru', CURDATE())
ON DUPLICATE KEY UPDATE
  weight_kg = VALUES(weight_kg),
  body_fat_percentage = VALUES(body_fat_percentage),
  muscle_mass_kg = VALUES(muscle_mass_kg),
  notes = VALUES(notes);

-- Active goal (demo user): only insert if demo user exists and has no active goal
INSERT INTO user_goals (
  user_id, goal_type, target_weight_kg, target_date, weekly_weight_change,
  daily_calorie_target, daily_protein_target, daily_fat_target, daily_carbs_target,
  evaluation, status, last_notif, progress, is_active
)
SELECT
  2, 'weight_loss', 68.00, DATE_ADD(CURDATE(), INTERVAL 8 WEEK), 0.50,
  2000, 120.00, 60.00, 250.00,
  NULL, 'active', NULL, 0.00, 1
WHERE EXISTS (SELECT 1 FROM users WHERE id = 2)
  AND NOT EXISTS (SELECT 1 FROM user_goals WHERE user_id = 2 AND is_active = 1 LIMIT 1);

-- Sample schedules (7 days, 3 meals/day) for demo user: insert missing rows only
INSERT INTO schedules (user_id, food_id, meal_type_id, schedule_date, quantity, notes)
SELECT t.user_id, t.food_id, t.meal_type_id, t.schedule_date, t.quantity, t.notes
FROM (
  SELECT 2 AS user_id, 7 AS food_id, 1 AS meal_type_id, DATE_SUB(CURDATE(), INTERVAL 6 DAY) AS schedule_date, 1.00 AS quantity, 'Sarapan' AS notes
  UNION ALL SELECT 2, 1, 2, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 1.00, 'Makan siang'
  UNION ALL SELECT 2, 3, 3, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 1.00, 'Makan malam'

  UNION ALL SELECT 2, 7, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 1.00, 'Sarapan'
  UNION ALL SELECT 2, 2, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 1.00, 'Makan siang'
  UNION ALL SELECT 2, 8, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 1.00, 'Makan malam'

  UNION ALL SELECT 2, 4, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 2.00, 'Sarapan'
  UNION ALL SELECT 2, 1, 2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 1.00, 'Makan siang'
  UNION ALL SELECT 2, 3, 3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 1.00, 'Makan malam'

  UNION ALL SELECT 2, 7, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1.00, 'Sarapan'
  UNION ALL SELECT 2, 2, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1.00, 'Makan siang'
  UNION ALL SELECT 2, 5, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 1.00, 'Makan malam ringan'

  UNION ALL SELECT 2, 4, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1.00, 'Sarapan'
  UNION ALL SELECT 2, 1, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1.00, 'Makan siang'
  UNION ALL SELECT 2, 8, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 1.00, 'Makan malam'

  UNION ALL SELECT 2, 7, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1.00, 'Sarapan'
  UNION ALL SELECT 2, 2, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1.00, 'Makan siang'
  UNION ALL SELECT 2, 3, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 1.00, 'Makan malam'

  UNION ALL SELECT 2, 4, 1, CURDATE(), 1.00, 'Sarapan'
  UNION ALL SELECT 2, 1, 2, CURDATE(), 1.00, 'Makan siang'
  UNION ALL SELECT 2, 6, 3, CURDATE(), 1.00, 'Makan malam'
) t
LEFT JOIN schedules s
  ON s.user_id = t.user_id
  AND s.food_id = t.food_id
  AND (s.meal_type_id <=> t.meal_type_id)
  AND s.schedule_date = t.schedule_date
WHERE s.id IS NULL
  AND EXISTS (SELECT 1 FROM users WHERE id = 2);

-- Seed notifications: insert missing rows only
INSERT INTO notifications (user_id, title, message, type, channel, status, created_at)
SELECT t.user_id, t.title, t.message, t.type, t.channel, t.status, t.created_at
FROM (
  SELECT 2 AS user_id, 'Selamat datang!' AS title, 'Akun demo siap digunakan. Coba buat jadwal makan dan pantau progress.' AS message, 'info' AS type, 'in_app' AS channel, 'unread' AS status, CURRENT_TIMESTAMP AS created_at
  UNION ALL SELECT 2, 'Tips', 'Minum air yang cukup dan konsisten tracking.', 'tip', 'in_app', 'unread', CURRENT_TIMESTAMP
  UNION ALL SELECT 2, 'Goal', 'Goal Anda aktif. Cek progress di menu Goals.', 'goal', 'in_app', 'unread', CURRENT_TIMESTAMP
) t
LEFT JOIN notifications n
  ON n.user_id = t.user_id
  AND n.title = t.title
WHERE n.id IS NULL
  AND EXISTS (SELECT 1 FROM users WHERE id = 2);

COMMIT;
