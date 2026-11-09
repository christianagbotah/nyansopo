-- Add subject_type column to subject table for core/elective classification

ALTER TABLE `subject` 
ADD COLUMN `subject_type` ENUM('core', 'elective') DEFAULT 'elective' AFTER `name`;

-- Update common core subjects
UPDATE `subject` SET `subject_type` = 'core' 
WHERE LOWER(`name`) IN ('english', 'mathematics', 'science', 'social studies', 'integrated science');

-- Update common elective subjects  
UPDATE `subject` SET `subject_type` = 'elective'
WHERE LOWER(`name`) IN ('french', 'ict', 'rme', 'bdt', 'visual arts', 'ghanaian language');
