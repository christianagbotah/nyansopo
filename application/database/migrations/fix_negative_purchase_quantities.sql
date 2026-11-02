-- Fix inventory_stock_movements ENUM to support purchase orders and returns
-- The movement_type column needs to include 'purchase', 'return', and 'sale' types

-- Step 1: Alter the ENUM to add new movement types
ALTER TABLE inventory_stock_movements 
MODIFY COLUMN movement_type ENUM('in', 'out', 'adjustment', 'transfer', 'purchase', 'return', 'sale') DEFAULT NULL;

-- Step 2: Update existing NULL movement_type records based on notes
-- Records with "Purchase Order" in notes should be 'purchase'
UPDATE inventory_stock_movements 
SET movement_type = 'purchase'
WHERE movement_type IS NULL 
AND notes LIKE '%Purchase Order%';

-- Records with "Return" in notes should be 'return'
UPDATE inventory_stock_movements 
SET movement_type = 'return'
WHERE movement_type IS NULL 
AND notes LIKE '%Return%';

-- Records with "Sale" in notes should be 'sale'
UPDATE inventory_stock_movements 
SET movement_type = 'sale'
WHERE movement_type IS NULL 
AND notes LIKE '%Sale%';

-- Step 3: Verify the fix
SELECT 
    movement_type,
    COUNT(*) as count,
    SUM(quantity) as total_quantity
FROM inventory_stock_movements 
GROUP BY movement_type
ORDER BY movement_type;

-- Step 4: Show recent movements
SELECT 
    id,
    product_id,
    movement_type,
    quantity,
    LEFT(notes, 50) as notes,
    movement_date
FROM inventory_stock_movements 
ORDER BY movement_date DESC
LIMIT 10;
