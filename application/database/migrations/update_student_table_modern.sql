-- Update student table for modern admission system
-- Add new fields for enhanced student information

ALTER TABLE `student` 
ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `name`,
ADD COLUMN `middle_name` VARCHAR(100) NULL AFTER `first_name`,
ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `middle_name`,
ADD COLUMN `blood_group` VARCHAR(10) NULL AFTER `sex`,
ADD COLUMN `nationality` VARCHAR(50) DEFAULT 'Ghanaian' AFTER `blood_group`,
ADD COLUMN `ghana_card_id` VARCHAR(20) NULL AFTER `nationality`,
ADD COLUMN `student_phone` VARCHAR(20) NULL AFTER `phone`,
ADD COLUMN `admission_date` DATE NULL AFTER `address`,
ADD COLUMN `allergies` TEXT NULL AFTER `admission_date`,
ADD COLUMN `medical_conditions` TEXT NULL AFTER `allergies`,
ADD COLUMN `emergency_contact` VARCHAR(20) NULL AFTER `medical_conditions`,
ADD COLUMN `parent_email` VARCHAR(100) NULL AFTER `emergency_contact`,
ADD COLUMN `place_of_birth` VARCHAR(100) NULL AFTER `nationality`,
ADD COLUMN `hometown` VARCHAR(100) NULL AFTER `place_of_birth`,
ADD COLUMN `disability_status` TINYINT(1) DEFAULT 0 COMMENT '0=No Disability, 1=Has Disability' AFTER `medical_conditions`,
ADD COLUMN `special_needs` TEXT NULL AFTER `disability_status`,
ADD COLUMN `learning_support` TEXT NULL AFTER `special_needs`,
ADD COLUMN `digital_literacy` ENUM('beginner','intermediate','advanced') DEFAULT 'beginner' AFTER `learning_support`,
ADD COLUMN `home_technology_access` TINYINT(1) DEFAULT 0 COMMENT '0=No Access, 1=Has Access' AFTER `digital_literacy`,
ADD COLUMN `nhis_number` VARCHAR(20) NULL AFTER `home_technology_access` COMMENT 'National Health Insurance Scheme Number',
ADD COLUMN `nhis_status` ENUM('active','inactive','pending') DEFAULT 'pending' AFTER `nhis_number`;

-- Add indexes for better performance
ALTER TABLE `student` 
ADD INDEX `idx_first_name` (`first_name`),
ADD INDEX `idx_last_name` (`last_name`),
ADD INDEX `idx_ghana_card` (`ghana_card_id`),
ADD INDEX `idx_admission_date` (`admission_date`);

-- Update existing records to populate first_name and last_name from name field
UPDATE `student` 
SET 
    `first_name` = SUBSTRING_INDEX(`name`, ' ', 1),
    `last_name` = CASE 
        WHEN LENGTH(`name`) - LENGTH(REPLACE(`name`, ' ', '')) >= 1 
        THEN SUBSTRING_INDEX(`name`, ' ', -1)
        ELSE SUBSTRING_INDEX(`name`, ' ', 1)
    END,
    `middle_name` = CASE 
        WHEN LENGTH(`name`) - LENGTH(REPLACE(`name`, ' ', '')) >= 2 
        THEN SUBSTRING_INDEX(SUBSTRING_INDEX(`name`, ' ', 2), ' ', -1)
        ELSE NULL
    END
WHERE `first_name` IS NULL;
