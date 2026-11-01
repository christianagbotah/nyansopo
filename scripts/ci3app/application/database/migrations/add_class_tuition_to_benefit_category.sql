-- Add class_fee and tuition_fee columns to benefit_category table
ALTER TABLE `benefit_category` 
ADD COLUMN `class_fee` decimal(10,2) DEFAULT 0.00 AFTER `classes_charge`,
ADD COLUMN `tuition_fee` decimal(10,2) DEFAULT 0.00 AFTER `class_fee`;
