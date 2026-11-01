-- Add details column to benefit_category table
ALTER TABLE `benefit_category` ADD COLUMN `details` TEXT NULL AFTER `name`;

-- Migrate existing data to new format
UPDATE `benefit_category` 
SET `details` = JSON_ARRAY(
    JSON_OBJECT(
        'class_id', COALESCE(class_id, 0),
        'feeding_charge', COALESCE(feeding_charge, 0),
        'classes_charge', COALESCE(classes_charge, 0),
        'tuition_charge', COALESCE(tuition_charge, 0)
    )
)
WHERE `details` IS NULL;

-- Optional: Drop old columns after confirming migration worked
-- ALTER TABLE `benefit_category` DROP COLUMN `class_id`;
-- ALTER TABLE `benefit_category` DROP COLUMN `feeding_charge`;
-- ALTER TABLE `benefit_category` DROP COLUMN `classes_charge`;
-- ALTER TABLE `benefit_category` DROP COLUMN `tuition_charge`;
