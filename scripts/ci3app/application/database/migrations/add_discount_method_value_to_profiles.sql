-- Add discount_method and discount_value to discount_profiles table
-- This allows profiles to have default discount values
-- Rules can override these values for specific classes

ALTER TABLE discount_profiles 
ADD COLUMN discount_method ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage' AFTER discount_category,
ADD COLUMN discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER discount_method;

-- Add index for better performance
CREATE INDEX idx_profile_active_category ON discount_profiles(is_active, discount_category);

-- Update existing profiles to have default 0% discount
UPDATE discount_profiles SET discount_value = 0.00 WHERE discount_value IS NULL;
