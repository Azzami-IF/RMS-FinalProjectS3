-- Add action_url column to notifications (needed by NotificationService + UI)

ALTER TABLE notifications
  ADD COLUMN action_url VARCHAR(512) NULL AFTER message;
