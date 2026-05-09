-- create_payments_table.sql
-- Payments table for billing records
-- Run this on your MySQL server (replace types if your members.member_id is integer)

CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `billing_id` INT NOT NULL,
  `member_id` VARCHAR(100) NOT NULL COLLATE utf8mb4_unicode_ci,
  `payment_amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATETIME NOT NULL,
  `transaction_id` VARCHAR(255) DEFAULT NULL,
  `payment_method` VARCHAR(100) DEFAULT NULL,
  `payment_type` ENUM('advance','installment','full') NOT NULL DEFAULT 'full',
  `installment_no` INT DEFAULT 0,
  `created_by` INT DEFAULT NULL,
  `note` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_billing` (`billing_id`),
  INDEX `idx_member` (`member_id`),
  CONSTRAINT `fk_payments_billing` FOREIGN KEY (`billing_id`) REFERENCES `billing`(`billing_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notes:
-- - `payment_type` indicates whether the payment is an 'advance', part of an 'installment', or a 'full' payment.
-- - Use `payment_date`, `transaction_id`, and `payment_amount` when recording payments.
-- - The application should decide how payments affect plan start dates: e.g. when a qualifying payment is recorded, set the member's plan start and expiration accordingly.
-- - If your `members.member_id` column is an INT, change `member_id` type above to INT to allow the foreign key.
