<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Check if a daily fee module is enabled
 */
if (!function_exists('is_fee_module_enabled')) {
    function is_fee_module_enabled($module) {
        $CI =& get_instance();
        $setting = $CI->db->get_where('settings', ['type' => 'fee_module_' . $module])->row();
        return $setting ? (int)$setting->description === 1 : false;
    }
}

/**
 * Get all enabled fee modules
 */
if (!function_exists('get_enabled_fee_modules')) {
    function get_enabled_fee_modules() {
        $modules = ['feeding', 'classes', 'transport', 'breakfast', 'water'];
        $enabled = [];
        foreach ($modules as $module) {
            if (is_fee_module_enabled($module)) {
                $enabled[] = $module;
            }
        }
        return $enabled;
    }
}

/**
 * Check if any fee module is enabled
 */
if (!function_exists('any_fee_module_enabled')) {
    function any_fee_module_enabled() {
        return count(get_enabled_fee_modules()) > 0;
    }
}

/**
 * Get fee rate for a module from daily_fee_rates table
 * @param int $class_id Class ID
 * @param string $module Module name (feeding, classes, transport, breakfast, water)
 * @param int|null $year Academic year (defaults to running year)
 * @param int|null $term Academic term (defaults to running term)
 * @return float Fee rate
 */
if (!function_exists('get_module_fee_rate')) {
    function get_module_fee_rate($class_id, $module, $year = null, $term = null) {
        $CI =& get_instance();
        $year = $year ?? get_settings('running_year');
        $term = $term ?? get_settings('running_term');
        
        $rate = $CI->db->get_where('daily_fee_rates', [
            'class_id' => $class_id,
            'year' => $year,
            'term' => $term
        ])->row();
        
        if ($rate && isset($rate->{$module . '_rate'})) {
            return (float)$rate->{$module . '_rate'};
        }
        
        return 0.00;
    }
}

/**
 * Get all module fee rates for a class
 * @param int $class_id Class ID
 * @param int|null $year Academic year
 * @param int|null $term Academic term
 * @return array Associative array of module => rate
 */
if (!function_exists('get_all_module_fee_rates')) {
    function get_all_module_fee_rates($class_id, $year = null, $term = null) {
        $modules = ['feeding', 'classes', 'transport', 'breakfast', 'water'];
        $rates = [];
        foreach ($modules as $module) {
            $rates[$module] = get_module_fee_rate($class_id, $module, $year, $term);
        }
        return $rates;
    }
}
