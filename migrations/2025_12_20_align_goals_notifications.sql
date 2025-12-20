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
