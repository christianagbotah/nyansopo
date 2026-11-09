-- =====================================================
-- Add discount_category to discount_profiles table
-- This separates invoice discounts from daily fees discounts
-- =====================================================

-- Add discount_category column to discount_profiles
ALTER TABLE discount_profiles 
ADD COLUMN discount_category ENUM('invoice', 'daily_fees') NOT NULL DEFAULT 'invoice' 
AFTER discount_type;

-- Add index for faster lookups
CREATE INDEX idx_discount_category ON discount_profiles(discount_category, is_active);

-- Update existing profiles to have proper category
-- (You may need to manually update specific profiles based on their purpose)
UPDATE discount_profiles 
SET discount_category = 'invoice' 
WHERE discount_category IS NULL OR discount_category = '';

-- Verification
SELECT 'Discount Profiles with Categories:' as Status;
SELECT profile_id, profile_name, discount_type, discount_category, is_active 
FROM discount_profiles 
ORDER BY profile_id;
