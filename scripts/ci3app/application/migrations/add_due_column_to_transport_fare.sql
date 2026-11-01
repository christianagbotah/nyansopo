-- Migration to add due column to transport_fare table
-- This allows tracking of outstanding balances for transport fees

ALTER TABLE `transport_fare` 
ADD COLUMN `due` DECIMAL(10,2) DEFAULT 0.00 AFTER `amount_paid`;
