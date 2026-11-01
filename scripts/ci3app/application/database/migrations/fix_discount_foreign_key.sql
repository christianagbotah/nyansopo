-- =====================================================
-- Fix Foreign Key to Use discount_profiles (A++ Architecture)
-- =====================================================

-- Step 1: Drop incorrect foreign key (ignore error if doesn't exist)
SET @query = IF(
    EXISTS(
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_NAME = 'student_discount_assignments_ibfk_2' 
        AND TABLE_NAME = 'student_discount_assignments'
    ),
    'ALTER TABLE student_discount_assignments DROP FOREIGN KEY student_discount_assignments_ibfk_2',
    'SELECT 1'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Ensure profile_id column exists and is correct type
ALTER TABLE student_discount_assignments 
MODIFY COLUMN profile_id INT(11) NOT NULL;

-- Step 3: Add correct foreign key to discount_profiles
ALTER TABLE student_discount_assignments 
ADD CONSTRAINT fk_student_discount_profile 
FOREIGN KEY (profile_id) REFERENCES discount_profiles(profile_id) ON DELETE CASCADE;

-- Step 4: Add index for performance (ignore if exists)
SET @query = IF(
    NOT EXISTS(
        SELECT 1 FROM information_schema.STATISTICS 
        WHERE INDEX_NAME = 'idx_profile_year_term' 
        AND TABLE_NAME = 'student_discount_assignments'
    ),
    'CREATE INDEX idx_profile_year_term ON student_discount_assignments(profile_id, year, term, is_active)',
    'SELECT 1'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verification
SELECT 
    'Foreign Key Fixed' as Status,
    COUNT(*) as ProfileCount 
FROM discount_profiles;
