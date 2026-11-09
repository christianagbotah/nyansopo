-- ============================================
-- CREATE PAYROLL STATUTORY SETTINGS TABLE
-- ============================================
-- Purpose: Allow users to configure statutory deduction percentages
-- Date: September 5, 2026
-- ============================================

CREATE TABLE IF NOT EXISTS `payroll_statutory_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL UNIQUE COMMENT 'Unique identifier for setting',
  `setting_name` varchar(100) NOT NULL COMMENT 'Display name',
  `setting_value` decimal(5,2) NOT NULL COMMENT 'Percentage value (e.g., 13.50 for 13.5%)',
  `setting_type` enum('percentage','fixed') DEFAULT 'percentage',
  `description` text COMMENT 'What this setting controls',
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who last updated',
  PRIMARY KEY (`setting_id`),
  KEY `idx_setting_key` (`setting_key`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Configurable statutory deduction percentages';

-- ============================================
-- INSERT DEFAULT VALUES (Ghana Statutory Rates)
-- ============================================

INSERT INTO `payroll_statutory_settings` 
(`setting_key`, `setting_name`, `setting_value`, `setting_type`, `description`, `is_active`) 
VALUES
-- SSNIT Rates
('ssnit_tier1_employer', 'SSNIT Tier 1 - Employer Contribution', 13.50, 'percentage', 'Employer contribution to SSNIT Tier 1 (Applied to basic salary)', 1),
('ssnit_tier1_employee', 'SSNIT Tier 1 - Employee Deduction', 5.50, 'percentage', 'Employee deduction for SSNIT Tier 1 (Deducted from basic salary)', 1),
('ssnit_tier2', 'SSNIT Tier 2', 5.00, 'percentage', 'Employee contribution to SSNIT Tier 2/Pension fund', 1),

-- Taxes
('getfund', 'GETFund', 2.50, 'percentage', 'Ghana Education Trust Fund levy (Applied to basic salary)', 1),
('nhil', 'NHIL', 2.50, 'percentage', 'National Health Insurance Levy (Applied to basic salary)', 1),

-- Other statutory (for future use)
('covid_levy', 'COVID-19 Levy', 0.00, 'percentage', 'COVID-19 Health Recovery Levy (if applicable)', 0),
('district_assembly', 'District Assembly Rate', 0.00, 'percentage', 'District Assembly Common Fund (if applicable)', 0);

-- ============================================
-- CREATE AUDIT LOG TABLE FOR SETTING CHANGES
-- ============================================

CREATE TABLE IF NOT EXISTS `payroll_statutory_settings_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `old_value` decimal(5,2) NOT NULL,
  `new_value` decimal(5,2) NOT NULL,
  `changed_by` int(11) NOT NULL COMMENT 'Admin user ID',
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` text COMMENT 'Reason for change',
  PRIMARY KEY (`log_id`),
  KEY `idx_setting_id` (`setting_id`),
  KEY `idx_changed_by` (`changed_by`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Audit trail for statutory settings changes';

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Verify table creation
SELECT 'Tables created successfully' as status;

-- View default settings
SELECT 
    setting_key,
    setting_name,
    CONCAT(setting_value, '%') as rate,
    description,
    IF(is_active = 1, 'Active', 'Inactive') as status
FROM payroll_statutory_settings
ORDER BY setting_id;

-- ============================================
-- Language Phrase for Navigation
-- ============================================
INSERT INTO `language` (`phrase`, `english`) VALUES ('statutory_settings', 'Statutory Settings')
ON DUPLICATE KEY UPDATE `english` = 'Statutory Settings';
