<?php
/**
 * Dining Coupon Helper Functions
 * 
 * Provides access control and utility functions for the dining coupon feature.
 * 
 * @package     Helpers
 * @author      School Manager System
 */
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('getTeacherClassIds')) {
    /**
     * Get class IDs assigned to a teacher
     * 
     * Extracts class IDs from the class table where teacher_id matches.
     * This is a programmatic alternative to getFullClassList() which outputs HTML.
     * 
     * @param int $teacher_id Teacher ID
     * @return array Array of class IDs
     */
    function getTeacherClassIds($teacher_id) {
        $ci =& get_instance();
        
        // Validate teacher_id exists
        if (empty($teacher_id) || !is_numeric($teacher_id)) {
            return [];
        }
        
        // Get classes assigned to teacher
        $ci->db->select('class_id');
        $ci->db->from('class');
        $ci->db->where('teacher_id', $teacher_id);
        $query = $ci->db->get();
        
        $class_ids = [];
        foreach ($query->result() as $row) {
            $class_ids[] = $row->class_id;
        }
        
        return $class_ids;
    }
}

if (!function_exists('canTeacherAccessClass')) {
    /**
     * Check if teacher has access to a specific class
     * 
     * @param int $teacher_id Teacher ID
     * @param int $class_id Class ID to check
     * @return bool True if teacher can access class, false otherwise
     */
    function canTeacherAccessClass($teacher_id, $class_id) {
        $teacher_classes = getTeacherClassIds($teacher_id);
        return in_array($class_id, $teacher_classes);
    }
}

if (!function_exists('canGenerateCoupons')) {
    /**
     * Check if user role can generate dining coupons
     * 
     * @param string $role User role (admin, teacher, cashier, etc.)
     * @return bool True if role can generate coupons
     */
    function canGenerateCoupons($role) {
        // Admin, cashiers, and teachers can generate coupons
        $allowed_roles = ['admin', 'teacher', 'cashier'];
        return in_array($role, $allowed_roles);
    }
}

if (!function_exists('getAllClassIds')) {
    /**
     * Get all class IDs in the system
     * 
     * @return array Array of all class IDs
     */
    function getAllClassIds() {
        $ci =& get_instance();
        
        $ci->db->select('class_id');
        $ci->db->from('class');
        $ci->db->order_by('name', 'ASC');
        $ci->db->order_by('name_numeric', 'ASC');
        $query = $ci->db->get();
        
        $class_ids = [];
        foreach ($query->result() as $row) {
            $class_ids[] = $row->class_id;
        }
        
        return $class_ids;
    }
}
