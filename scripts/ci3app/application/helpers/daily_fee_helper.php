<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Get user level name from numeric level
 */
function get_level_name($level) {
    $levels = [
        '1' => 'Super Admin',
        '2' => 'Admin',
        '3' => 'Accountant',
        '4' => 'Cashier',
        '5' => 'Conductor'
    ];
    return $levels[$level] ?? 'Unknown';
}

/**
 * Get all admin levels for dropdown
 */
function get_admin_levels() {
    return [
        '1' => 'Super Admin',
        '2' => 'Admin',
        '3' => 'Accountant',
        '4' => 'Cashier',
        '5' => 'Conductor'
    ];
}

/**
 * Check if level can collect daily fees
 */
function level_can_collect_fees($level) {
    return in_array($level, ['1', '2', '3', '4', '5']);
}

/**
 * Get collection point for level
 */
function get_level_collection_point($level) {
    $points = [
        '1' => 'office',
        '2' => 'office',
        '3' => 'office',
        '4' => 'office',
        '5' => 'bus'
    ];
    return $points[$level] ?? 'office';
}

/**
 * Check if a fee module is enabled
 */
if (!function_exists('is_fee_module_enabled')) {
    function is_fee_module_enabled($module) {
        $ci =& get_instance();
        $setting = $ci->db->get_where('settings', ['type' => $module . '_module'])->row();
        return $setting && $setting->description == '1';
    }
}
