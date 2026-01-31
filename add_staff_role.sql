-- Add 'staff' to users.role enum (MySQL)
-- Run this on a backup/test DB first. Adjust table and column names if different.

ALTER TABLE `users`
MODIFY COLUMN `role` ENUM('admin','staff','member','trainer') NOT NULL;

-- If you want to convert existing admin users to staff, run (example):
-- UPDATE `users` SET `role` = 'staff' WHERE `role` = 'admin' AND <condition>;

-- Note: MODIFY will fail if other DB objects reference the enum in an incompatible way.
-- Always backup and test before running in production.
