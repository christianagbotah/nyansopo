-- =====================================================
-- Migrate Benefit Category Data to Discount Profiles
-- Run this AFTER creating the discount tables
-- =====================================================

-- Step 1: Migrate benefit_category to discount_profiles
INSERT INTO discount_profiles (profile_id, profile_name, discount_type, description, is_active)
SELECT 
    category_id,
    name,
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(details, '$.discount_type')), 'percentage'),
    CONCAT('Migrated from benefit category: ', name),
    1
FROM benefit_category
WHERE NOT EXISTS (SELECT 1 FROM discount_profiles WHERE profile_id = benefit_category.category_id);

-- Step 2: Migrate benefit category rules to discount_profile_rules
-- This handles the complex JSON structure in benefit_category.details
INSERT INTO discount_profile_rules (profile_id, class_id, bill_category_id, discount_value)
SELECT 
    bc.category_id as profile_id,
    CASE 
        WHEN class_key = 'a' OR class_key = '0' THEN NULL
        ELSE CAST(class_key AS UNSIGNED)
    END as class_id,
    (SELECT category_id FROM bill_category WHERE LOWER(name) LIKE '%feeding%' LIMIT 1) as bill_category_id,
    COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.feeding_charged')) AS DECIMAL(10,2)), 0) as discount_value
FROM benefit_category bc
CROSS JOIN JSON_TABLE(
    bc.details,
    '$.classes.*' COLUMNS(
        class_key VARCHAR(10) PATH '$',
        class_data JSON PATH '$'
    )
) jt
WHERE JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.feeding_charged')) IS NOT NULL
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.feeding_charged')) AS DECIMAL(10,2)) > 0;

-- Classes discount
INSERT INTO discount_profile_rules (profile_id, class_id, bill_category_id, discount_value)
SELECT 
    bc.category_id as profile_id,
    CASE 
        WHEN class_key = 'a' OR class_key = '0' THEN NULL
        ELSE CAST(class_key AS UNSIGNED)
    END as class_id,
    (SELECT category_id FROM bill_category WHERE LOWER(name) LIKE '%class%' LIMIT 1) as bill_category_id,
    COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.classes_charged')) AS DECIMAL(10,2)), 0) as discount_value
FROM benefit_category bc
CROSS JOIN JSON_TABLE(
    bc.details,
    '$.classes.*' COLUMNS(
        class_key VARCHAR(10) PATH '$',
        class_data JSON PATH '$'
    )
) jt
WHERE JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.classes_charged')) IS NOT NULL
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.classes_charged')) AS DECIMAL(10,2)) > 0;

-- Tuition discount
INSERT INTO discount_profile_rules (profile_id, class_id, bill_category_id, discount_value)
SELECT 
    bc.category_id as profile_id,
    CASE 
        WHEN class_key = 'a' OR class_key = '0' THEN NULL
        ELSE CAST(class_key AS UNSIGNED)
    END as class_id,
    (SELECT category_id FROM bill_category WHERE LOWER(name) LIKE '%tuition%' LIMIT 1) as bill_category_id,
    COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.tuition_charged')) AS DECIMAL(10,2)), 0) as discount_value
FROM benefit_category bc
CROSS JOIN JSON_TABLE(
    bc.details,
    '$.classes.*' COLUMNS(
        class_key VARCHAR(10) PATH '$',
        class_data JSON PATH '$'
    )
) jt
WHERE JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.tuition_charged')) IS NOT NULL
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(class_data, '$.tuition_charged')) AS DECIMAL(10,2)) > 0;

-- Step 3: Migrate student benefit assignments from beneficiary_list table
INSERT INTO student_discount_assignments (student_id, profile_id, year, term, sem, assigned_by, is_active)
SELECT DISTINCT
    bl.student_id,
    JSON_UNQUOTE(JSON_EXTRACT(cat_item.value, '$.category_id')) as profile_id,
    bl.year,
    bl.term,
    NULL as sem,
    1, -- Default admin user
    1
FROM beneficiary_list bl
CROSS JOIN JSON_TABLE(
    bl.categories,
    '$[*]' COLUMNS(
        value JSON PATH '$'
    )
) cat_item
WHERE JSON_UNQUOTE(JSON_EXTRACT(cat_item.value, '$.category_id')) IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM student_discount_assignments sda 
      WHERE sda.student_id = bl.student_id 
        AND sda.profile_id = JSON_UNQUOTE(JSON_EXTRACT(cat_item.value, '$.category_id'))
        AND sda.year = bl.year
        AND sda.term = bl.term
  );

-- Verification queries
SELECT 'Discount Profiles Created:' as Status, COUNT(*) as Count FROM discount_profiles;
SELECT 'Discount Rules Created:' as Status, COUNT(*) as Count FROM discount_profile_rules;
SELECT 'Student Assignments Created:' as Status, COUNT(*) as Count FROM student_discount_assignments;
