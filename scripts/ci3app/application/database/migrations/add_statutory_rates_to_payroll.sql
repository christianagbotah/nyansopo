-- Add statutory rate columns to pay_salary table
-- These store the prevailing rates at the time of payroll creation for historical accuracy

ALTER TABLE `pay_salary` 
ADD COLUMN `rate_ssnit_tier1_employer` DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'SSNIT Tier 1 employer rate at time of creation (%)' AFTER `net_salary`,
ADD COLUMN `rate_ssnit_tier1_employee` DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'SSNIT Tier 1 employee rate at time of creation (%)' AFTER `rate_ssnit_tier1_employer`,
ADD COLUMN `rate_ssnit_tier2` DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'SSNIT Tier 2 rate at time of creation (%)' AFTER `rate_ssnit_tier1_employee`,
ADD COLUMN `rate_getfund` DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'GETFund rate at time of creation (%)' AFTER `rate_ssnit_tier2`,
ADD COLUMN `rate_nhil` DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'NHIL rate at time of creation (%)' AFTER `rate_getfund`;

-- Add index for efficient queries
ALTER TABLE `pay_salary` 
ADD INDEX `idx_payroll_rates` (`rate_ssnit_tier1_employer`, `rate_ssnit_tier2`, `rate_getfund`, `rate_nhil`);
