-- Indexes to optimize bulk invoice loading performance
-- Run this migration to improve query speed

-- Index for invoice filtering and grouping
-- year is varchar(20) format 'YYYY-YYYY', term is int(11) - no length needed for invoice table
ALTER TABLE invoice 
ADD INDEX idx_bulk_invoice_lookup (mute, can_delete, year, term, invoice_code);

-- Index for invoice aggregation
ALTER TABLE invoice 
ADD INDEX idx_invoice_grouping (invoice_code, student_id, mute, can_delete);

-- Index for class filtering via enroll table
-- year and term are longtext - year format is 'YYYY-YYYY' (9 chars), term is '1', '2', or '3'
ALTER TABLE enroll 
ADD INDEX idx_enroll_class_filter (student_id, year(10), term(5), class_id);

-- Index for student lookup
-- name and student_code are longtext, need prefix
ALTER TABLE student 
ADD INDEX idx_student_lookup (student_id, name(100), student_code(50));

-- Index for class lookup
-- name is varchar(11), name_numeric is varchar(3) - NO PREFIX needed (columns are short)
ALTER TABLE class 
ADD INDEX idx_class_lookup (class_id, name, name_numeric);

-- Composite index for invoice queries
ALTER TABLE invoice 
ADD INDEX idx_invoice_student_term_year (student_id, term, year, mute, can_delete);
