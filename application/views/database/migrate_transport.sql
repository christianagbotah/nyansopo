-- Migration script to add new columns to existing transport table
-- Run this first before creating new tables

ALTER TABLE `transport` 
ADD COLUMN `route_code` varchar(50) DEFAULT NULL AFTER `route_name`,
ADD COLUMN `total_stops` int(11) DEFAULT 0 AFTER `route_fare`,
ADD COLUMN `total_distance` decimal(10,2) DEFAULT 0.00 AFTER `total_stops`,
ADD COLUMN `status` enum('active','inactive') DEFAULT 'active' AFTER `total_distance`;

-- Generate route codes for existing routes
UPDATE `transport` SET `route_code` = CONCAT('RT-', LPAD(transport_id, 3, '0')) WHERE `route_code` IS NULL;

-- Now run the full schema from transport_schema.sql
