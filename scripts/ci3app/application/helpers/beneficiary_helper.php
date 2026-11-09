<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Get beneficiary fee amount for a student
 * 
 * @param int $student_id Student ID
 * @param string $fee_type Fee type: 'feeding' or 'classes'
 * @param string $year Academic year (optional, defaults to running year)
 * @param string $term Term (optional, defaults to running term)
 * @return float|null Total amount or null if not a beneficiary
 */
if (!function_exists('get_beneficiary_fee')) {
    function get_beneficiary_fee($student_id, $fee_type = 'feeding', $year = null, $term = null) {
        $CI =& get_instance();
        
        if ($year === null) {
            $year = $CI->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        }
        
        if ($term === null) {
            $term = $CI->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        }
        
        // Check beneficiary_list
        $beneficiary = $CI->db->get_where('beneficiary_list', array(
            'student_id' => $student_id,
            'year' => $year,
            'term' => $term
        ))->row();
        
        if ($beneficiary) {
            $categories = json_decode($beneficiary->categories, true);
            $total_amount = 0;
            
            foreach($categories as $cat) {
                $total_amount += $fee_type == 'feeding' ? $cat['feeding_amount'] : $cat['classes_amount'];
            }
            
            return $total_amount > 0 ? $total_amount : null;
        }
        
        return null;
    }
}

/**
 * Check if student is a beneficiary for current term
 * 
 * @param int $student_id Student ID
 * @param string $year Academic year (optional)
 * @param string $term Term (optional)
 * @return bool
 */
if (!function_exists('is_beneficiary')) {
    function is_beneficiary($student_id, $year = null, $term = null) {
        $CI =& get_instance();
        
        if ($year === null) {
            $year = $CI->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        }
        
        if ($term === null) {
            $term = $CI->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        }
        
        $count = $CI->db->get_where('beneficiary_list', array(
            'student_id' => $student_id,
            'year' => $year,
            'term' => $term
        ))->num_rows();
        
        return $count > 0;
    }
}

/**
 * Get all beneficiary categories for a student
 * 
 * @param int $student_id Student ID
 * @param string $year Academic year (optional)
 * @param string $term Term (optional)
 * @return array Array of categories with amounts
 */
if (!function_exists('get_beneficiary_categories')) {
    function get_beneficiary_categories($student_id, $year = null, $term = null) {
        $CI =& get_instance();
        
        if ($year === null) {
            $year = $CI->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        }
        
        if ($term === null) {
            $term = $CI->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        }
        
        $beneficiary = $CI->db->get_where('beneficiary_list', array(
            'student_id' => $student_id,
            'year' => $year,
            'term' => $term
        ))->row();
        
        if ($beneficiary) {
            return json_decode($beneficiary->categories, true);
        }
        
        return array();
    }
}

/**
 * Get beneficiary history for a student across all terms
 * 
 * @param int $student_id Student ID
 * @return array Array of beneficiary records
 */
if (!function_exists('get_beneficiary_history')) {
    function get_beneficiary_history($student_id) {
        $CI =& get_instance();
        
        $CI->db->order_by('year', 'DESC');
        $CI->db->order_by('term', 'DESC');
        $history = $CI->db->get_where('beneficiary_list', array(
            'student_id' => $student_id
        ))->result_array();
        
        return $history;
    }
}

/**
 * Copy beneficiaries from one term to another
 * 
 * @param string $from_year Source year
 * @param string $from_term Source term
 * @param string $to_year Destination year
 * @param string $to_term Destination term
 * @return int Number of beneficiaries copied
 */
if (!function_exists('copy_beneficiaries_to_term')) {
    function copy_beneficiaries_to_term($from_year, $from_term, $to_year, $to_term) {
        $CI =& get_instance();
        
        $beneficiaries = $CI->db->get_where('beneficiary_list', array(
            'year' => $from_year,
            'term' => $from_term
        ))->result_array();
        
        $copied = 0;
        
        foreach($beneficiaries as $ben) {
            // Check if already exists
            $exists = $CI->db->get_where('beneficiary_list', array(
                'student_id' => $ben['student_id'],
                'year' => $to_year,
                'term' => $to_term
            ))->num_rows();
            
            if ($exists > 0) continue;
            
            // Get updated class_id for new term
            $enroll = $CI->db->get_where('enroll', array(
                'student_id' => $ben['student_id'],
                'year' => $to_year,
                'term' => $to_term,
                'mute' => '0'
            ))->row();
            
            if (!$enroll) continue;
            
            $CI->db->insert('beneficiary_list', array(
                'student_id' => $ben['student_id'],
                'class_id' => $enroll->class_id,
                'year' => $to_year,
                'term' => $to_term,
                'categories' => $ben['categories'],
                'created_at' => time()
            ));
            
            $copied++;
        }
        
        return $copied;
    }
}
