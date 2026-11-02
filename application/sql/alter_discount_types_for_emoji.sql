-- Alter discount_types table to support emojis in icon column
ALTER TABLE discount_types 
MODIFY COLUMN icon VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;

ALTER TABLE discount_types 
MODIFY COLUMN description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;

-- Ensure table uses utf8mb4
ALTER TABLE discount_types 
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
