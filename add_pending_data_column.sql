-- Add pending_data column to users table to store registration information
-- until admin approves the member/trainer

ALTER TABLE users ADD COLUMN pending_data TEXT NULL AFTER is_active;
