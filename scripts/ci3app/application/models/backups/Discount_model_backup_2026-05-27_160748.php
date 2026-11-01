<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount Model
 * 
 * Core model for discount operations
 */
class Discount_model extends CI_Model {

    /**
     * Get discount by ID
     */
    public function get($id) {
        return $this->db
            ->where('discount_id', $id)
            ->get('invoice_discounts')
            ->row();
    }

    /**
     * Get all discounts with optional filters
     */
    public function get_all($filters = []) {
        $this->db->select('invoice_discounts.*, students.name as student_name, students.code as student_code');
        $this->db->from('invoice_discounts');
        $this->db->join('students', 'students.student_id = invoice_discounts.student_id', 'left');
        
        if (!empty($filters['discount_category'])) {
            $this->db->where('invoice_discounts.discount_category', $filters['discount_category']);
        }
        
        if (!empty($filters['status'])) {
            $this->db->where('invoice_discounts.status', $filters['status']);
        }
        
        if (!empty($filters['student_id'])) {
            $this->db->where('invoice_discounts.student_id', $filters['student_id']);
        }
        
        if (!empty($filters['year'])) {
            $this->db->where('invoice_discounts.year', $filters['year']);
        }
        
        if (!empty($filters['term'])) {
            $this->db->where('invoice_discounts.term', $filters['term']);
        }
        
        $this->db->order_by('invoice_discounts.applied_at', 'DESC');
        
        return $this->db->get()->result();
    }

    /**
     * Create new discount
     */
    public function create($data) {
        $this->db->insert('invoice_discounts', $data);
        return $this->db->insert_id();
    }

    /**
     * Update discount
     */
    public function update($id, $data) {
        $this->db->where('discount_id', $id);
        return $this->db->update('invoice_discounts', $data);
    }

    /**
     * Delete discount
     */
    public function delete($id) {
        $this->db->where('discount_id', $id);
        return $this->db->delete('invoice_discounts');
    }

    /**
     * Check for duplicate discount
     */
    public function check_duplicate($student_id, $discount_type, $year, $term, $exclude_id = null) {
        $this->db->where('student_id', $student_id);
        $this->db->where('discount_type', $discount_type);
        $this->db->where('year', $year);
        $this->db->where('term', $term);
        $this->db->where('status !=', 'rejected');
        
        if ($exclude_id) {
            $this->db->where('discount_id !=', $exclude_id);
        }
        
        return $this->db->get('invoice_discounts')->num_rows() > 0;
    }

    /**
     * Get discounts by student
     */
    public function get_by_student($student_id, $year = null, $term = null) {
        $this->db->where('student_id', $student_id);
        $this->db->where('status', 'approved');
        
        if ($year) {
            $this->db->where('year', $year);
        }
        
        if ($term) {
            $this->db->where('term', $term);
        }
        
        return $this->db->get('invoice_discounts')->result();
    }

    /**
     * Get discounts by invoice
     */
    public function get_by_invoice($invoice_code) {
        return $this->db
            ->where('invoice_code', $invoice_code)
            ->get('invoice_discounts')
            ->result();
    }

    /**
     * Get student discount for specific date and fee type
     * Uses student_discount_assignments table directly
     * Handles '*' wildcard for discounts that apply to all daily fees
     */
    public function get_student_discount_for_date($student_id, $year, $term, $class_id, $fee_type, $charge_date) {
        $result = [
            'has_discount' => false,
            'discount_type' => null,
            'discount_method' => null,
            'discount_value' => 0,
            'profile_id' => null,
            'profile_name' => null
        ];
        
        $charge_date_str = date('Y-m-d', $charge_date);
        
        // Get assignment that was active on the charge date
        // Check for both specific fee type AND 'all_daily_fees' (covers all daily fees)
        $this->db->select('sda.*, dp.profile_name');
        $this->db->from('student_discount_assignments sda');
        $this->db->join('discount_profiles dp', 'sda.profile_id = dp.profile_id');
        $this->db->where('sda.student_id', $student_id);
        $this->db->where('sda.year', $year);
        $this->db->where('sda.term', $term);
        $this->db->group_start();
            $this->db->where('sda.discount_type', $fee_type);           // Specific fee type
            $this->db->or_where('sda.discount_type', 'all_daily_fees'); // All daily fees wildcard
        $this->db->group_end();
        $this->db->where('dp.discount_category', 'daily_fees');
        $this->db->where('sda.is_active', 1);
        $this->db->where('DATE(sda.assigned_at) <=', $charge_date_str);
        $this->db->where('sda.status', 'approved');
        $this->db->order_by('sda.discount_type', 'ASC'); // Prioritize specific fee type over wildcard
        $this->db->limit(1);
        
        $assignment = $this->db->get()->row_array();
        
        if ($assignment) {
            $result['has_discount'] = true;
            $result['discount_type'] = $assignment['discount_type'];
            $result['discount_method'] = $assignment['discount_method'];
            $result['discount_value'] = $assignment['discount_value'];
            $result['profile_id'] = $assignment['profile_id'];
            $result['profile_name'] = $assignment['profile_name'];
        }
        
        return $result;
    }
    
    /**
     * Check if student has 100% discount on all daily fees
     * This is used to hide fee collection fields when student shouldn't be charged
     */
    public function has_full_discount_on_all_daily_fees($student_id, $year, $term, $class_id, $as_of_date) {
        $as_of_date_str = date('Y-m-d', $as_of_date);
        
        // Check for 'all_daily_fees' discount type with 100% or full amount discount
        $this->db->select('sda.*, dp.profile_name');
        $this->db->from('student_discount_assignments sda');
        $this->db->join('discount_profiles dp', 'sda.profile_id = dp.profile_id');
        $this->db->where('sda.student_id', $student_id);
        $this->db->where('sda.year', $year);
        $this->db->where('sda.term', $term);
        $this->db->where('sda.discount_type', 'all_daily_fees');
        $this->db->where('dp.discount_category', 'daily_fees');
        $this->db->where('sda.is_active', 1);
        $this->db->where('DATE(sda.assigned_at) <=', $as_of_date_str);
        $this->db->where('sda.status', 'approved');
        $this->db->limit(1);
        
        $assignment = $this->db->get()->row_array();
        
        if ($assignment) {
            // Check if it's 100% discount
            if ($assignment['discount_method'] == 'percentage' && $assignment['discount_value'] >= 100) {
                return true;
            }
            // For fixed amount, we need to check if it covers all fees
            // This would require knowing the total daily fees, so we'll be conservative
            // and only return true for percentage-based 100% discounts
        }
        
        return false;
    }
}
