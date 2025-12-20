-- Add username column to users table for flexible login
ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER email;
