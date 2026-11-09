<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount Helper Functions
 * Enterprise-grade discount utilities
 */

/**
 * Safely record discount application with table existence check
 * 
 * @param CI_Controller $CI
 * @param array $data
 * @return bool
 */
if (!function_exists('record_discount_safely')) {
    function record_discount_safely($CI, $data) {
        // Ensure table exists
        if(!$CI->db->table_exists('discount_applications')) {
            $CI->db->query("CREATE TABLE IF NOT EXISTS discount_applications (
                application_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                profile_id INT NOT NULL,
                discount_category ENUM('invoice', 'daily_fees') NOT NULL,
                reference_type VARCHAR(50) NOT NULL,
                reference_id VARCHAR(100) NOT NULL,
                bill_item_type VARCHAR(100),
                original_amount DECIMAL(10,2) NOT NULL,
                discount_percentage DECIMAL(5,2) NOT NULL,
                discount_amount DECIMAL(10,2) NOT NULL,
                final_amount DECIMAL(10,2) NOT NULL,
                year INT NOT NULL,
                term INT NOT NULL,
                applied_at INT NOT NULL,
                INDEX idx_student (student_id),
                INDEX idx_profile (profile_id),
                INDEX idx_category (discount_category),
                INDEX idx_reference (reference_type, reference_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $data['applied_at'] = time();
        return $CI->db->insert('discount_applications', $data);
    }
}

/**
 * Get bill category ID by name (case-insensitive)
 * 
 * @param CI_Controller $CI
 * @param string $category_name
 * @return int|null
 */
if (!function_exists('get_bill_category_id')) {
    function get_bill_category_id($CI, $category_name) {
        $category = $CI->db->like('name', $category_name, 'both')->get('bill_category')->row();
        return $category ? $category->category_id : null;
    }
}

/**
 * Format discount display
 * 
 * @param float $percentage
 * @return string
 */
if (!function_exists('format_discount')) {
    function format_discount($percentage) {
        return number_format($percentage, 2) . '%';
    }
}

/**
 * Calculate discount amount
 * 
 * @param float $original_amount
 * @param float $discount_percentage
 * @return float
 */
if (!function_exists('calculate_discount_amount')) {
    function calculate_discount_amount($original_amount, $discount_percentage) {
        return ($original_amount * $discount_percentage) / 100;
    }
}

/**
 * Apply discount to amount
 * 
 * @param float $original_amount
 * @param float $discount_percentage
 * @return float
 */
if (!function_exists('apply_discount')) {
    function apply_discount($original_amount, $discount_percentage) {
        $discount_amount = calculate_discount_amount($original_amount, $discount_percentage);
        return max(0, $original_amount - $discount_amount);
    }
}
