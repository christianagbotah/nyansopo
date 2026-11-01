-- =====================================================
-- ENTERPRISE DISCOUNT APPLICATION TRACKING SYSTEM
-- Version: 1.0.0
-- Purpose: Track actual monetary value of discounts applied
-- Compliance: Financial audit trail requirements
-- =====================================================

-- Main discount applications ledger
CREATE TABLE IF NOT EXISTS `discount_applications` (
  `application_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `profile_id` INT(11) NOT NULL,
  `discount_category` ENUM('invoice','daily_fees') NOT NULL,
  `reference_type` VARCHAR(50) NOT NULL COMMENT 'invoice, daily_fee_transaction',
  `reference_id` VARCHAR(100) NOT NULL COMMENT 'invoice_code or transaction_id',
  `bill_item_type` VARCHAR(50) DEFAULT NULL COMMENT 'school_fees, feeding, classes, etc',
  `original_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `final_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `year` INT(4) NOT NULL,
  `term` INT(1) DEFAULT NULL,
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applied_by` INT(11) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_profile` (`profile_id`),
  KEY `idx_category` (`discount_category`),
  KEY `idx_year_term` (`year`,`term`),
  KEY `idx_applied_at` (`applied_at`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_reporting` (`discount_category`, `year`, `term`, `applied_at`),
  CONSTRAINT `fk_discount_app_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_discount_app_profile` FOREIGN KEY (`profile_id`) REFERENCES `discount_profiles` (`profile_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Enterprise discount application ledger';

-- Discount summary cache for performance
CREATE TABLE IF NOT EXISTS `discount_summary_cache` (
  `cache_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `discount_category` ENUM('invoice','daily_fees') NOT NULL,
  `year` INT(4) NOT NULL,
  `term` INT(1) DEFAULT NULL,
  `profile_id` INT(11) DEFAULT NULL,
  `total_applications` INT(11) NOT NULL DEFAULT 0,
  `total_original_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_final_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `unique_students` INT(11) NOT NULL DEFAULT 0,
  `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cache_id`),
  UNIQUE KEY `idx_unique_summary` (`discount_category`, `year`, `term`, `profile_id`),
  KEY `idx_category_period` (`discount_category`, `year`, `term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Aggregated discount statistics cache';

-- Audit trail for discount modifications
CREATE TABLE IF NOT EXISTS `discount_audit_log` (
  `audit_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `action` ENUM('created','modified','reversed','approved','rejected') NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` VARCHAR(100) NOT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `performed_by` INT(11) NOT NULL,
  `performed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `idx_application` (`application_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_performed_at` (`performed_at`),
  KEY `idx_performed_by` (`performed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprehensive audit trail for discount operations';

-- Verification queries
SELECT 'Discount Applications Table Created' AS Status;
SELECT 'Summary Cache Table Created' AS Status;
SELECT 'Audit Log Table Created' AS Status;

-- Grant appropriate permissions (adjust as needed)
-- GRANT SELECT, INSERT ON discount_applications TO 'app_user'@'localhost';
-- GRANT SELECT ON discount_summary_cache TO 'app_user'@'localhost';
