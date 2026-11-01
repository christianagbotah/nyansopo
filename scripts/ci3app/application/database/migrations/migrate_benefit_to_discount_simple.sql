-- =====================================================
-- Migrate Benefit Category Data to Discount Profiles
-- SIMPLE VERSION (Compatible with older MySQL)
-- Run this AFTER creating the discount tables
-- =====================================================

-- Step 1: Migrate benefit_category to discount_profiles
INSERT INTO discount_profiles (profile_id, profile_name, discount_type, description, is_active)
SELECT 
    category_id,
    name,
    'percentage',
    CONCAT('Migrated from benefit category: ', name),
    1
FROM benefit_category
WHERE NOT EXISTS (SELECT 1 FROM discount_profiles WHERE profile_id = benefit_category.category_id);

-- Step 2: Migrate benefit category rules (simplified - all classes, all categories)
-- You'll need to manually add specific class/category rules later
INSERT INTO discount_profile_rules (profile_id, class_id, bill_category_id, discount_value)
SELECT 
    category_id,
    NULL, -- All classes
    NULL, -- All bill categories
    10.00 -- Default 10% - ADJUST THIS MANUALLY AFTER MIGRATION
FROM benefit_category
WHERE NOT EXISTS (
    SELECT 1 FROM discount_profile_rules 
    WHERE profile_id = benefit_category.category_id
);

-- Step 3: Migrate student assignments from beneficiary_list
-- This creates one assignment per student per category per term
INSERT INTO student_discount_assignments (student_id, profile_id, year, term, assigned_by, is_active)
SELECT DISTINCT
    bl.student_id,
    bc.category_id as profile_id,
    bl.year,
    bl.term,
    1, -- Default admin
    1
FROM beneficiary_list bl
CROSS JOIN benefit_category bc
WHERE bl.categories LIKE CONCAT('%"category_id":', bc.category_id, '%')
  OR bl.categories LIKE CONCAT('%"category_id":"', bc.category_id, '"%')
  AND NOT EXISTS (
      SELECT 1 FROM student_discount_assignments sda 
      WHERE sda.student_id = bl.student_id 
        AND sda.profile_id = bc.category_id
        AND sda.year = bl.year
        AND sda.term = bl.term
  );

-- Verification queries
SELECT 'Discount Profiles Created:' as Status, COUNT(*) as Count FROM discount_profiles;
SELECT 'Discount Rules Created:' as Status, COUNT(*) as Count FROM discount_profile_rules;
SELECT 'Student Assignments Created:' as Status, COUNT(*) as Count FROM student_discount_assignments;

-- Show sample data
SELECT 'Sample Profiles:' as Info;
SELECT profile_id, profile_name, discount_type FROM discount_profiles LIMIT 5;

SELECT 'Sample Assignments:' as Info;
SELECT sda.student_id, s.name, dp.profile_name, sda.year, sda.term 
FROM student_discount_assignments sda
JOIN student s ON sda.student_id = s.student_id
JOIN discount_profiles dp ON sda.profile_id = dp.profile_id
LIMIT 10;
