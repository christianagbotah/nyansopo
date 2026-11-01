-- Add discount functionality to purchase orders
-- This allows suppliers to offer discounts (percentage or fixed amount)

ALTER TABLE inventory_purchases 
ADD COLUMN discount_type ENUM('percentage', 'fixed') DEFAULT NULL COMMENT 'Type of discount applied',
ADD COLUMN discount_value DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Discount value (percentage or fixed amount)',
ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Calculated discount amount in currency',
ADD COLUMN subtotal DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total before discount',
ADD COLUMN final_total DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total after discount (subtotal - discount_amount)';

-- Update existing records to set subtotal = total_amount and final_total = total_amount
UPDATE inventory_purchases 
SET subtotal = total_amount, 
    final_total = total_amount 
WHERE subtotal = 0.00;

-- Add comment to clarify total_amount usage
ALTER TABLE inventory_purchases 
MODIFY COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 
COMMENT 'Final total after discount (same as final_total, kept for compatibility)';
