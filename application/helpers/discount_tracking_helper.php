<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Enterprise Discount Tracking Helper
 * 
 * @package     SchoolManager
 * @subpackage  Helpers
 * @category    Financial
 * @author      Enterprise Development Team
 * @version     1.0.0
 * @since       2025-12-22
 * 
 * Purpose: Track actual monetary value of discounts applied across the system
 * Compliance: Financial audit trail and reporting requirements
 */

if (!function_exists('record_discount_application')) {
    /**
     * Record a discount application in the ledger
     * 
     * @param array $data Discount application data
     * @return int|false Application ID or false on failure
     */
    function record_discount_application($data) {
        $CI =& get_instance();
        
        // Validate required fields
        $required = ['student_id', 'profile_id', 'discount_category', 'reference_type', 
                     'reference_id', 'original_amount', 'discount_percentage', 'year'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                log_message('error', "Discount application missing required field: {$field}");
                return false;
            }
        }
        
        // Calculate discount amount if not provided
        if (!isset($data['discount_amount'])) {
            $data['discount_amount'] = ($data['original_amount'] * $data['discount_percentage']) / 100;
        }
        
        // Calculate final amount if not provided
        if (!isset($data['final_amount'])) {
            $data['final_amount'] = $data['original_amount'] - $data['discount_amount'];
        }
        
        // Add metadata
        $data['applied_at'] = date('Y-m-d H:i:s');
        $data['applied_by'] = $CI->session->userdata('admin_id') ?: $CI->session->userdata('login_user_id');
        $data['ip_address'] = $CI->input->ip_address();
        
        // Insert into ledger
        $CI->db->insert('discount_applications', $data);
        $application_id = $CI->db->insert_id();
        
        if ($application_id) {
            // Log audit trail
            log_discount_audit($application_id, 'created', 'discount_application', $application_id, null, $data);
            
            // Update summary cache
            update_discount_summary_cache($data['discount_category'], $data['year'], $data['term'], $data['profile_id']);
            
            return $application_id;
        }
        
        return false;
    }
}

if (!function_exists('log_discount_audit')) {
    /**
     * Log discount operation to audit trail
     * 
     * @param int $application_id Application ID
     * @param string $action Action performed
     * @param string $entity_type Entity type
     * @param mixed $entity_id Entity ID
     * @param array|null $old_values Old values
     * @param array|null $new_values New values
     * @param string|null $reason Reason for action
     * @return bool Success status
     */
    function log_discount_audit($application_id, $action, $entity_type, $entity_id, $old_values = null, $new_values = null, $reason = null) {
        $CI =& get_instance();
        
        $audit_data = [
            'application_id' => $application_id,
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'old_values' => $old_values ? json_encode($old_values) : null,
            'new_values' => $new_values ? json_encode($new_values) : null,
            'performed_by' => $CI->session->userdata('admin_id') ?: $CI->session->userdata('login_user_id'),
            'performed_at' => date('Y-m-d H:i:s'),
            'ip_address' => $CI->input->ip_address(),
            'user_agent' => substr($CI->input->user_agent(), 0, 255),
            'reason' => $reason
        ];
        
        return $CI->db->insert('discount_audit_log', $audit_data);
    }
}

if (!function_exists('update_discount_summary_cache')) {
    /**
     * Update discount summary cache for reporting
     * 
     * @param string $category Discount category
     * @param int $year Academic year
     * @param int|null $term Academic term
     * @param int|null $profile_id Profile ID (null for all)
     * @return bool Success status
     */
    function update_discount_summary_cache($category, $year, $term = null, $profile_id = null) {
        $CI =& get_instance();
        
        // Build query
        $CI->db->select('
            COUNT(*) as total_applications,
            SUM(original_amount) as total_original_amount,
            SUM(discount_amount) as total_discount_amount,
            SUM(final_amount) as total_final_amount,
            COUNT(DISTINCT student_id) as unique_students
        ');
        $CI->db->from('discount_applications');
        $CI->db->where('discount_category', $category);
        $CI->db->where('year', $year);
        
        if ($term !== null) {
            $CI->db->where('term', $term);
        }
        
        if ($profile_id !== null) {
            $CI->db->where('profile_id', $profile_id);
        }
        
        $summary = $CI->db->get()->row_array();
        
        if (!$summary) {
            return false;
        }
        
        // Check if cache exists
        $CI->db->where('discount_category', $category);
        $CI->db->where('year', $year);
        $CI->db->where('term', $term);
        $CI->db->where('profile_id', $profile_id);
        $exists = $CI->db->get('discount_summary_cache')->row();
        
        $cache_data = [
            'discount_category' => $category,
            'year' => $year,
            'term' => $term,
            'profile_id' => $profile_id,
            'total_applications' => $summary['total_applications'] ?: 0,
            'total_original_amount' => $summary['total_original_amount'] ?: 0,
            'total_discount_amount' => $summary['total_discount_amount'] ?: 0,
            'total_final_amount' => $summary['total_final_amount'] ?: 0,
            'unique_students' => $summary['unique_students'] ?: 0,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        if ($exists) {
            $CI->db->where('cache_id', $exists->cache_id);
            return $CI->db->update('discount_summary_cache', $cache_data);
        } else {
            return $CI->db->insert('discount_summary_cache', $cache_data);
        }
    }
}

if (!function_exists('get_discount_summary')) {
    /**
     * Get discount summary from cache
     * 
     * @param string $category Discount category
     * @param int $year Academic year
     * @param int|null $term Academic term
     * @param int|null $profile_id Profile ID
     * @return array|null Summary data
     */
    function get_discount_summary($category, $year, $term = null, $profile_id = null) {
        $CI =& get_instance();
        
        $CI->db->where('discount_category', $category);
        $CI->db->where('year', $year);
        
        if ($term !== null) {
            $CI->db->where('term', $term);
        }
        
        if ($profile_id !== null) {
            $CI->db->where('profile_id', $profile_id);
        }
        
        return $CI->db->get('discount_summary_cache')->row_array();
    }
}

if (!function_exists('get_discount_applications_report')) {
    function get_discount_applications_report($filters = []) {
        $CI =& get_instance();
        
        $CI->db->select('
            id.discount_id,
            id.student_id,
            id.profile_id,
            id.discount_category,
            id.discount_method,
            id.discount_value,
            id.discount_amount,
            id.year,
            id.term,
            id.applied_at,
            s.name as student_name,
            s.student_code,
            dp.profile_name,
            c.name as class_name
        ');
        $CI->db->from('invoice_discounts id');
        $CI->db->join('student s', 's.student_id = id.student_id');
        $CI->db->join('discount_profiles dp', 'dp.profile_id = id.profile_id', 'left');
        $CI->db->join('enroll e', 'e.student_id = id.student_id AND e.year = id.year', 'left');
        $CI->db->join('class c', 'c.class_id = e.class_id', 'left');
        $CI->db->where('id.status', 'approved');
        
        if (isset($filters['category']) && $filters['category'] !== '') {
            $CI->db->where('id.discount_category', $filters['category']);
        }
        
        if (isset($filters['year']) && $filters['year'] !== '') {
            $CI->db->where('id.year', $filters['year']);
        }
        
        if (isset($filters['term']) && $filters['term'] !== '') {
            $CI->db->where('id.term', $filters['term']);
        }
        
        $CI->db->order_by('id.applied_at', 'DESC');
        
        $results = $CI->db->get()->result_array();
        
        if (!$results) {
            return [];
        }
        
        foreach ($results as &$row) {
            // Get original amount from invoice_discount_items
            $CI->db->select('SUM(original_amount) as total_original');
            $CI->db->from('invoice_discount_items');
            $CI->db->where('discount_id', $row['discount_id']);
            $items_data = $CI->db->get()->row_array();
            
            $original = $items_data['total_original'] ?: 0;
            $discount_amt = $row['discount_amount'];
            $final = $original - $discount_amt;
            
            $row['original_amount'] = $original;
            $row['discount_percentage'] = ($row['discount_method'] == 'percentage') ? $row['discount_value'] : 0;
            $row['discount_value_display'] = $row['discount_value'];
            $row['discount_amount'] = $discount_amt;
            $row['final_amount'] = $final;
        }
        
        return $results;
    }
}
