-- Add benefit tracking columns to invoice table
-- This allows us to display benefit/discount information on invoices

ALTER TABLE `invoice` 
ADD COLUMN `benefit_category_id` INT NULL AFTER `discount_applied`,
ADD COLUMN `benefit_category_name` VARCHAR(100) NULL AFTER `benefit_category_id`,
ADD COLUMN `benefit_discount_type` ENUM('percentage','fixed') NULL AFTER `benefit_category_name`,
ADD COLUMN `benefit_feeding_amount` DECIMAL(10,2) NULL DEFAULT 0.00 AFTER `benefit_discount_type`,
ADD COLUMN `benefit_classes_amount` DECIMAL(10,2) NULL DEFAULT 0.00 AFTER `benefit_feeding_amount`,
ADD COLUMN `benefit_tuition_amount` DECIMAL(10,2) NULL DEFAULT 0.00 AFTER `benefit_classes_amount`,
ADD COLUMN `benefit_total_discount` DECIMAL(10,2) NULL DEFAULT 0.00 AFTER `benefit_tuition_amount`,
ADD INDEX `idx_benefit_category` (`benefit_category_id`);

-- Update existing invoices to set default values
UPDATE `invoice` SET 
    `benefit_feeding_amount` = 0.00,
    `benefit_classes_amount` = 0.00,
    `benefit_tuition_amount` = 0.00,
    `benefit_total_discount` = 0.00
WHERE `benefit_feeding_amount` IS NULL;
