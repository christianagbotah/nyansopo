-- Result Approval and Locking Workflow
-- GES Compliant Academic Governance System

-- 1. Result Approval Status Table
CREATE TABLE IF NOT EXISTS `result_approval_status` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `class_id` INT NOT NULL,
  `academic_year` VARCHAR(10) NOT NULL,
  `term` VARCHAR(10) NOT NULL,
  `status` ENUM('draft', 'submitted', 'approved', 'locked') DEFAULT 'draft',
  `submitted_by` INT,
  `submitted_at` DATETIME,
  `approved_by` INT,
  `approved_at` DATETIME,
  `locked_by` INT,
  `locked_at` DATETIME,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_class_year_term` (`class_id`, `academic_year`, `term`),
  INDEX `idx_status` (`status`),
  INDEX `idx_class_year_term` (`class_id`, `academic_year`, `term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Result Approval Audit Log
CREATE TABLE IF NOT EXISTS `result_approval_audit` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `approval_status_id` INT NOT NULL,
  `action` ENUM('submit', 'approve', 'lock', 'unlock', 'reject') NOT NULL,
  `previous_status` VARCHAR(20),
  `new_status` VARCHAR(20),
  `performed_by` INT NOT NULL,
  `reason` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `performed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_approval_status` (`approval_status_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_performed_by` (`performed_by`),
  FOREIGN KEY (`approval_status_id`) REFERENCES `result_approval_status`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Initialize draft status for existing classes (safe to re-run)
INSERT IGNORE INTO `result_approval_status` 
  (`class_id`, `academic_year`, `term`, `status`)
SELECT DISTINCT 
  class_id,
  year,
  term,
  'draft'
FROM `sba_components`
WHERE year IS NOT NULL AND term IS NOT NULL;

-- 4. Add indexes for performance
ALTER TABLE `portfolio_headers` 
ADD INDEX `idx_portfolio_class_year_term` (`class_id`, `year`, `term`);

ALTER TABLE `portfolio_aggregates` 
ADD INDEX `idx_aggregate_class_year_term` (`class_id`, `year`, `term`);

ALTER TABLE `sba_components` 
ADD INDEX `idx_sba_class_year_term` (`class_id`, `year`, `term`);
