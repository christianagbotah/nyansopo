-- Finance Integration Database Migrations
-- Run this SQL to add required columns for double-entry accounting

-- Add sync columns to payment table
ALTER TABLE `payment` 
ADD COLUMN `synced_to_accounts` TINYINT(1) DEFAULT 0 AFTER `method`,
ADD COLUMN `journal_entry_id` INT NULL AFTER `synced_to_accounts`,
ADD COLUMN `synced_at` INT NULL AFTER `journal_entry_id`;

-- Add sync columns to daily_fee_transactions table (if exists)
ALTER TABLE `daily_fee_transactions` 
ADD COLUMN `synced_to_accounts` TINYINT(1) DEFAULT 0,
ADD COLUMN `journal_entry_id` INT NULL,
ADD COLUMN `synced_at` INT NULL;

-- Add ledger sync to invoice table
ALTER TABLE `invoice` 
ADD COLUMN `synced_to_ledger` TINYINT(1) DEFAULT 0,
ADD COLUMN `ledger_entry_id` INT NULL;

-- Create financial integration log table
CREATE TABLE IF NOT EXISTS `financial_integration_log` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `integration_type` VARCHAR(50) NOT NULL,
    `source_table` VARCHAR(50) NOT NULL,
    `source_id` INT NOT NULL,
    `target_table` VARCHAR(50) NOT NULL,
    `target_id` INT NULL,
    `status` ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    `error_message` TEXT NULL,
    `created_at` INT NOT NULL,
    `processed_at` INT NULL,
    INDEX `idx_source` (`source_table`, `source_id`),
    INDEX `idx_target` (`target_table`, `target_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create student ledger table
CREATE TABLE IF NOT EXISTS `student_ledger` (
    `ledger_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `transaction_date` DATE NOT NULL,
    `transaction_type` ENUM('invoice', 'payment', 'discount', 'credit_note', 'adjustment', 'refund') NOT NULL,
    `reference_type` VARCHAR(50) NOT NULL,
    `reference_id` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `debit_amount` DECIMAL(10,2) DEFAULT 0.00,
    `credit_amount` DECIMAL(10,2) DEFAULT 0.00,
    `balance` DECIMAL(10,2) NOT NULL,
    `year` VARCHAR(10) NOT NULL,
    `term` INT NOT NULL,
    `created_by` INT NOT NULL,
    `created_at` INT NOT NULL,
    INDEX `idx_student` (`student_id`),
    INDEX `idx_date` (`transaction_date`),
    INDEX `idx_type` (`transaction_type`),
    INDEX `idx_year_term` (`year`, `term`),
    FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
