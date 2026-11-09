-- Fix NULL movement_type values in stock movements
-- All movements should be either 'in' or 'out'
-- Purchases and returns use 'in', sales use 'out'

-- Update NULL movement_types to 'in' (for purchases, returns, stock additions)
UPDATE inventory_stock_movements 
SET movement_type = 'in'
WHERE movement_type IS NULL;

-- Verify no more NULLs
SELECT 
    COUNT(*) as null_count
FROM inventory_stock_movements 
WHERE movement_type IS NULL;

-- Show distribution of movement types
SELECT 
    movement_type,
    COUNT(*) as count,
    SUM(quantity) as total_quantity
FROM inventory_stock_movements 
GROUP BY movement_type
ORDER BY count DESC;
