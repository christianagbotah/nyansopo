<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Payment Method Helper
 * 
 * Provides centralized functions for retrieving payment method data from the database.
 * Implements request-level caching to optimize performance.
 * 
 * @package     School Management System
 * @subpackage  Helpers
 * @category    Payment Methods
 */

/**
 * Get all active payment methods from database
 * Results are cached for the duration of the request
 * 
 * @return array Array of payment method objects with properties: id, name, icon, display_order
 */
if (!function_exists('get_payment_methods')) {
    function get_payment_methods() {
        static $payment_methods_cache = null;
        
        // Return cached data if available
        if ($payment_methods_cache !== null) {
            return $payment_methods_cache;
        }
        
        // Get CodeIgniter instance
        $CI =& get_instance();
        $CI->load->database();
        
        try {
            // Check if payment_methods table exists
            if (!$CI->db->table_exists('payment_methods')) {
                log_message('info', 'Payment methods table does not exist, using fallback');
                $payment_methods_cache = [];
                return $payment_methods_cache;
            }
            
            // Query active payment methods ordered by display_order
            $CI->db->select('id, name, icon, is_active, display_order');
            $CI->db->from('payment_methods');
            $CI->db->where('is_active', 1);
            $CI->db->order_by('display_order', 'ASC');
            
            $query = $CI->db->get();
            
            if ($query) {
                $payment_methods_cache = $query->result();
            } else {
                $payment_methods_cache = [];
            }
            
        } catch (Exception $e) {
            log_message('error', 'Error fetching payment methods: ' . $e->getMessage());
            $payment_methods_cache = [];
        }
        
        return $payment_methods_cache;
    }
}

/**
 * Get payment method name by ID
 * 
 * @param int|null $payment_method_id The payment method ID
 * @return string Payment method name, "Unknown" for invalid IDs, "Not Specified" for null/empty
 */
if (!function_exists('get_payment_method_name')) {
    function get_payment_method_name($payment_method_id) {
        // Handle null or empty values
        if ($payment_method_id === null || $payment_method_id === '') {
            return 'Not Specified';
        }
        
        // Get cached payment methods
        $payment_methods = get_payment_methods();
        
        // If no payment methods available, use fallback
        if (empty($payment_methods)) {
            return get_payment_method_fallback($payment_method_id);
        }
        
        // Search for matching ID
        foreach ($payment_methods as $method) {
            if ($method->id == $payment_method_id) {
                return $method->name;
            }
        }
        
        // Log warning for invalid ID
        log_message('info', 'Invalid payment method ID: ' . $payment_method_id);
        
        return 'Unknown';
    }
}

/**
 * Get payment methods formatted for HTML select dropdown
 * 
 * @param int|null $selected_id The ID of the currently selected payment method
 * @return string HTML option elements for select dropdown
 */
if (!function_exists('get_payment_methods_dropdown')) {
    function get_payment_methods_dropdown($selected_id = null) {
        $payment_methods = get_payment_methods();
        
        if (empty($payment_methods)) {
            return '';
        }
        
        $html = '';
        foreach ($payment_methods as $method) {
            $selected = ($method->id == $selected_id) ? ' selected' : '';
            $html .= '<option value="' . $method->id . '"' . $selected . '>' . htmlspecialchars($method->name) . '</option>';
        }
        
        return $html;
    }
}

/**
 * Fallback payment method names when database table is unavailable
 * 
 * @param int $payment_method_id The payment method ID
 * @return string Payment method name or "Unknown"
 */
if (!function_exists('get_payment_method_fallback')) {
    function get_payment_method_fallback($payment_method_id) {
        $fallback_methods = [
            1 => 'Cash',
            2 => 'Cheque',
            3 => 'Mobile Money',
            4 => 'Bank Transfer'
        ];
        
        return isset($fallback_methods[$payment_method_id]) 
            ? $fallback_methods[$payment_method_id] 
            : 'Unknown';
    }
}

/**
 * Get payment methods as associative array (id => name)
 * Useful for array lookups and mappings
 * 
 * @return array Associative array of payment method IDs to names
 */
if (!function_exists('get_payment_methods_array')) {
    function get_payment_methods_array() {
        $payment_methods = get_payment_methods();
        
        if (empty($payment_methods)) {
            return [
                1 => 'Cash',
                2 => 'Cheque',
                3 => 'Mobile Money',
                4 => 'Bank Transfer'
            ];
        }
        
        $array = [];
        foreach ($payment_methods as $method) {
            $array[$method->id] = $method->name;
        }
        
        return $array;
    }
}
