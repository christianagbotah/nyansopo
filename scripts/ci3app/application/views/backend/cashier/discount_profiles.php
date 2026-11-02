<?php
/**
 * Cashier portal: Discount Profiles (legacy page_name route)
 * Wraps the shared modern view in restricted mode.
 */
$restricted_mode = true;
$allowed_category = 'daily_fees';
include __DIR__ . '/../admin/discount_profiles_modern.php';
