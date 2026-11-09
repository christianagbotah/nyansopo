<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Remark Helper
 * Helper functions for handling teacher and head teacher remarks with ID/Text fallback
 */

/**
 * Get class teacher remark with ID/Text fallback
 * 
 * If value is numeric (ID), fetch from teacher_remarks_templates table
 * If value is text, display it directly (backward compatibility)
 * 
 * @param string|int|null $value The remark value (can be ID or text)
 * @return string The remark text
 */
if (!function_exists('get_class_teacher_remark')) {
    function get_class_teacher_remark($value) {
        if (empty($value)) {
            return '';
        }
        
        // Check if value is numeric (ID)
        if (is_numeric($value)) {
            $CI =& get_instance();
            $CI->db->select('remark_text');
            $CI->db->where('id', $value);
            // Don't filter by is_active to ensure we get the remark even if inactive
            $template = $CI->db->get('teacher_remarks_templates')->row();
            
            if ($template && !empty($template->remark_text)) {
                return $template->remark_text;
            }
            // If ID not found, return the ID itself to indicate an issue
            return '[Remark ID: ' . $value . ' not found]';
        }
        
        // Value is text, return as is (backward compatibility)
        return $value;
    }
}

/**
 * Get head teacher remark with ID/Text fallback
 * 
 * If value is numeric (ID), fetch from head_teacher_remarks_ranges table
 * If value is text, display it directly (backward compatibility)
 * 
 * @param string|int|null $value The remark value (can be ID or text)
 * @return string The remark text
 */
if (!function_exists('get_head_teacher_remark')) {
    function get_head_teacher_remark($value) {
        if (empty($value)) {
            return '';
        }
        
        // Check if value is numeric (ID)
        if (is_numeric($value)) {
            $CI =& get_instance();
            $CI->db->select('remark_text');
            $CI->db->where('id', $value);
            // Don't filter by is_active to ensure we get the remark
            $template = $CI->db->get('head_teacher_remarks_ranges')->row();
            
            if ($template && !empty($template->remark_text)) {
                return $template->remark_text;
            }
            // If ID not found, return the ID itself to indicate an issue
            return '[Remark ID: ' . $value . ' not found]';
        }
        
        // Value is text, return as is (backward compatibility)
        return $value;
    }
}

/**
 * Get conduct name with ID/Text fallback
 * 
 * If value is numeric (ID), fetch from conduct_items table
 * If value is text, display it directly (backward compatibility)
 * 
 * @param string|int|null $value The conduct value (can be ID or text)
 * @return string The conduct text
 */
if (!function_exists('get_conduct_text')) {
    function get_conduct_text($value) {
        if (empty($value)) {
            return '';
        }
        
        // Check if value is numeric (ID)
        if (is_numeric($value)) {
            $CI =& get_instance();
            $CI->db->select('name');
            $CI->db->where('id', $value);
            // Don't filter by is_active to ensure we get the conduct
            $conduct = $CI->db->get('conduct_items')->row();
            
            if ($conduct && !empty($conduct->name)) {
                return $conduct->name;
            }
            // If ID not found, return the ID itself to indicate an issue
            return '[Conduct ID: ' . $value . ' not found]';
        }
        
        // Value is text, return as is (backward compatibility)
        return $value;
    }
}

/**
 * Get interest name with ID/Text fallback
 * 
 * If value is numeric (ID), fetch from interest_items table
 * If value is text, display it directly (backward compatibility)
 * 
 * @param string|int|null $value The interest value (can be ID or text)
 * @return string The interest text
 */
if (!function_exists('get_interest_text')) {
    function get_interest_text($value) {
        if (empty($value)) {
            return '';
        }
        
        // Check if value is numeric (ID)
        if (is_numeric($value)) {
            $CI =& get_instance();
            $CI->db->select('name');
            $CI->db->where('id', $value);
            // Don't filter by is_active to ensure we get the interest
            $interest = $CI->db->get('interest_items')->row();
            
            if ($interest && !empty($interest->name)) {
                return $interest->name;
            }
            // If ID not found, return the ID itself to indicate an issue
            return '[Interest ID: ' . $value . ' not found]';
        }
        
        // Value is text, return as is (backward compatibility)
        return $value;
    }
}

