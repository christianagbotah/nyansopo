-- Create invoice_discounts table for tracking discount audit trail
CREATE TABLE IF NOT EXISTS `invoice_discounts` (
    `discount_id` INT PRIMARY KEY AUTO_INCREMENT,
    `student_id` INT NOT NULL,
    `invoice_code` VARCHAR(50) NOT NULL,
    `discount_type` ENUM('early_payment', 'sibling', 'hardship', 'staff_child', 'other') NOT NULL,
    `discount_method` ENUM('percentage', 'fixed') NOT NULL,
    `discount_value` DECIMAL(10,2) NOT NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL,
    `reason` TEXT,
    `applied_by` INT NOT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `approved_by` INT NULL,
    `approved_at` TIMESTAMP NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    INDEX `idx_invoice` (`invoice_code`),
    INDEX `idx_student` (`student_id`),
    INDEX `idx_applied_by` (`applied_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
