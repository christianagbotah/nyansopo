-- =====================================================================
-- Migration 002: Discount profile rules + daily fee rates schema fixes
-- Date: 2026-11-02
--
-- 1) discount_profile_rules.bill_category_id
--    The column was INT, but daily-fee rules store the fee type NAME
--    ('feeding', 'breakfast', 'classes', 'water', 'transport') while
--    invoice rules store bill_item_id. MySQL silently coerced the
--    strings to 0, losing the fee-type scope of every daily-fee rule.
--    -> Widen to VARCHAR(50) so both use cases are supported.
--
-- 2) daily_fee_rates.section_id
--    Controllers (Cashier::daily_fee_rates etc.) read/write
--    daily_fee_rates.section_id but the column was never in the dump.
--    -> Add nullable section_id + helpful index.
-- =====================================================================

-- 1) Widen bill_category_id ------------------------------------------------
ALTER TABLE `discount_profile_rules`
  MODIFY `bill_category_id` VARCHAR(50) NULL DEFAULT NULL
  COMMENT 'Bill item id (invoice profiles) or daily fee type: feeding|breakfast|classes|water|transport (daily fee profiles). NULL = all';

-- 2) daily_fee_rates.section_id -------------------------------------------
ALTER TABLE `daily_fee_rates`
  ADD COLUMN `section_id` INT NULL DEFAULT NULL AFTER `class_id`,
  ADD KEY `idx_class_section_year` (`class_id`, `section_id`, `year`, `term`);

-- 3) Helpful index for the per-student assignment lookup -------------------
ALTER TABLE `student_discount_assignments`
  ADD KEY `idx_student_active` (`student_id`, `is_active`, `status`);
