<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Discount_model extends MY_Model {

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
     * 
     * Overrides parent method to provide custom filtering logic.
     * Maintains compatibility with parent signature.
     * 
     * @param array $where WHERE conditions (can be filters array for backward compatibility)
     * @param int|null $limit Optional limit
     * @param int|null $offset Optional offset
     * @return array Discount records
     */
    public function get_all($where = [], $limit = null, $offset = null) {
        // Support both parent signature and custom filters
        $filters = $where;
        
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
        
        // Apply limit and offset if provided
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        
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
     * 
     * IMPORTANT: Checks if discount is active (is_active=1 and status='approved')
     * NOT tied to specific year/term - discounts continue across periods until deactivated
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
        // DOES NOT filter by year/term - checks only if discount is active
        $this->db->select('sda.*, dp.profile_name');
        $this->db->from('student_discount_assignments sda');
        $this->db->join('discount_profiles dp', 'sda.profile_id = dp.profile_id');
        $this->db->where('sda.student_id', $student_id);
        // Removed year/term filter - discount is active regardless of academic period
        $this->db->group_start();
            $this->db->where('sda.discount_type', $fee_type);           // Specific fee type
            $this->db->or_where('sda.discount_type', 'all_daily_fees'); // All daily fees wildcard
        $this->db->group_end();
        $this->db->where('dp.discount_category', 'daily_fees');
        $this->db->where('sda.is_active', 1);  // Discount must be active
        $this->db->where('DATE(sda.assigned_at) <=', $charge_date_str);
        $this->db->where('sda.status', 'approved');  // Must be approved
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
     * 
     * IMPORTANT: Checks if discount is active (is_active=1 and status='approved')
     * NOT tied to specific year/term - discounts continue across periods until deactivated
     */
    public function has_full_discount_on_all_daily_fees($student_id, $year, $term, $class_id, $as_of_date) {
        $as_of_date_str = date('Y-m-d', $as_of_date);
        
        // Check for 'all_daily_fees' discount type with 100% or full amount discount
        // DOES NOT filter by year/term - checks only if discount is active
        $this->db->select('sda.*, dp.profile_name');
        $this->db->from('student_discount_assignments sda');
        $this->db->join('discount_profiles dp', 'sda.profile_id = dp.profile_id');
        $this->db->where('sda.student_id', $student_id);
        // Removed year/term filter - discount is active regardless of academic period
        $this->db->where('sda.discount_type', 'all_daily_fees');
        $this->db->where('dp.discount_category', 'daily_fees');
        $this->db->where('sda.is_active', 1);  // Discount must be active
        $this->db->where('DATE(sda.assigned_at) <=', $as_of_date_str);
        $this->db->where('sda.status', 'approved');  // Must be approved
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
