<?php
// ============================================================================
// ADDITIONAL HELPER METHOD FOR DISCOUNT PROFILES
// Add this method to the Admin controller
// ============================================================================

/**
 * Get discount profile data for editing
 * Returns JSON data for a specific profile
 */
function get_discount_profile_data($profile_id) {
    $profile = $this->db->where('profile_id', $profile_id)
                        ->get('discount_profiles')
                        ->row();
    
    if ($profile) {
        echo json_encode($profile);
    } else {
        echo json_encode(array('error' => 'Profile not found'));
    }
}

/**
 * Get all active discount profiles (for dropdowns)
 * Can be filtered by category
 */
function get_active_discount_profiles($category = null) {
    $this->db->where('is_active', 1);
    
    if ($category) {
        $this->db->where('discount_category', $category);
    }
    
    $profiles = $this->db->order_by('profile_name', 'ASC')
                         ->get('discount_profiles')
                         ->result();
    
    echo json_encode($profiles);
}

/**
 * Get students with a specific discount profile
 * Useful for viewing who has which discount
 */
function get_students_by_profile($profile_id) {
    $students = $this->db->select('s.student_id, s.name, s.student_code, sda.assigned_at, sda.notes')
                         ->from('student_discount_assignments sda')
                         ->join('student s', 'sda.student_id = s.student_id')
                         ->where('sda.profile_id', $profile_id)
                         ->where('sda.is_active', 1)
                         ->order_by('s.name', 'ASC')
                         ->get()
                         ->result();
    
    echo json_encode($students);
}

/**
 * Get discount history for a student
 * Shows all past and current discount assignments
 */
function get_student_discount_history($student_id) {
    $history = $this->db->select('sda.*, dp.profile_name, dp.discount_type, dp.discount_category, 
                                   dp.discount_method, dp.discount_value,
                                   a1.name as assigned_by_name,
                                   a2.name as deactivated_by_name')
                        ->from('student_discount_assignments sda')
                        ->join('discount_profiles dp', 'sda.profile_id = dp.profile_id')
                        ->join('admin a1', 'sda.assigned_by = a1.admin_id', 'left')
                        ->join('admin a2', 'sda.deactivated_by = a2.admin_id', 'left')
                        ->where('sda.student_id', $student_id)
                        ->order_by('sda.assigned_at', 'DESC')
                        ->get()
                        ->result();
    
    echo json_encode($history);
}

/**
 * Calculate discount amount for a given profile and amount
 * Utility function for preview/calculation
 */
function calculate_discount_amount() {
    $discount_method = $this->input->post('discount_method');
    $discount_value = floatval($this->input->post('discount_value'));
    $original_amount = floatval($this->input->post('original_amount'));
    
    $discount_amount = 0;
    
    if ($discount_method === 'percentage') {
        $discount_amount = ($original_amount * $discount_value) / 100;
    } else {
        $discount_amount = min($discount_value, $original_amount);
    }
    
    $final_amount = $original_amount - $discount_amount;
    
    echo json_encode(array(
        'original_amount' => $original_amount,
        'discount_amount' => $discount_amount,
        'final_amount' => $final_amount,
        'discount_percentage' => $original_amount > 0 ? ($discount_amount / $original_amount) * 100 : 0
    ));
}

/**
 * Bulk update profile status
 * Activate or deactivate multiple profiles at once
 */
function bulk_update_profile_status() {
    $profile_ids = $this->input->post('profile_ids'); // Array
    $status = $this->input->post('status'); // 1 or 0
    
    if (empty($profile_ids)) {
        echo json_encode(array(
            'status' => 'error',
            'message' => get_phrase('no_profiles_selected')
        ));
        return;
    }
    
    $this->db->where_in('profile_id', $profile_ids);
    $this->db->update('discount_profiles', array(
        'is_active' => $status,
        'updated_at' => time()
    ));
    
    $count = $this->db->affected_rows();
    
    echo json_encode(array(
        'status' => 'success',
        'message' => $count . ' ' . get_phrase('profiles_updated')
    ));
}

/**
 * Get discount statistics
 * Returns summary of discount usage
 */
function get_discount_statistics() {
    // Total profiles
    $total_profiles = $this->db->count_all('discount_profiles');
    
    // Active profiles
    $active_profiles = $this->db->where('is_active', 1)
                                 ->count_all_results('discount_profiles');
    
    // Total active assignments
    $active_assignments = $this->db->where('is_active', 1)
                                    ->count_all_results('student_discount_assignments');
    
    // Students with discounts
    $students_with_discounts = $this->db->select('DISTINCT student_id')
                                        ->where('is_active', 1)
                                        ->get('student_discount_assignments')
                                        ->num_rows();
    
    // Breakdown by category
    $invoice_profiles = $this->db->where('discount_category', 'invoice')
                                  ->where('is_active', 1)
                                  ->count_all_results('discount_profiles');
    
    $daily_fees_profiles = $this->db->where('discount_category', 'daily_fees')
                                     ->where('is_active', 1)
                                     ->count_all_results('discount_profiles');
    
    // Most used profile
    $most_used = $this->db->select('dp.profile_name, COUNT(sda.assignment_id) as usage_count')
                          ->from('student_discount_assignments sda')
                          ->join('discount_profiles dp', 'sda.profile_id = dp.profile_id')
                          ->where('sda.is_active', 1)
                          ->group_by('sda.profile_id')
                          ->order_by('usage_count', 'DESC')
                          ->limit(1)
                          ->get()
                          ->row();
    
    $stats = array(
        'total_profiles' => $total_profiles,
        'active_profiles' => $active_profiles,
        'inactive_profiles' => $total_profiles - $active_profiles,
        'active_assignments' => $active_assignments,
        'students_with_discounts' => $students_with_discounts,
        'invoice_profiles' => $invoice_profiles,
        'daily_fees_profiles' => $daily_fees_profiles,
        'most_used_profile' => $most_used ? $most_used->profile_name : 'N/A',
        'most_used_count' => $most_used ? $most_used->usage_count : 0
    );
    
    echo json_encode($stats);
}
