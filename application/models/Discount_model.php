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
            'profile_name' => null,
            'rule_applied' => false
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
            $this->db->where('sda.discount_type', $fee_type);           // Single fee type
            $this->db->or_where('sda.discount_type', 'all_daily_fees'); // All daily fees wildcard
            // Multi-fee scope: profiles can target several fee types at once,
            // stored as a comma-separated list (e.g. "feeding,water")
            $this->db->or_where("FIND_IN_SET(" . $this->db->escape($fee_type) . ", sda.discount_type)", null, FALSE);
        $this->db->group_end();
        $this->db->where('dp.discount_category', 'daily_fees');
        $this->db->where('sda.is_active', 1);  // Discount must be active
        $this->db->where('DATE(sda.assigned_at) <=', $charge_date_str);
        $this->db->where('sda.status', 'approved');  // Must be approved
        // Most specific scope wins: exact fee type (2) > multi-fee list (1) > all_daily_fees wildcard (0)
        $fee_type_esc = $this->db->escape($fee_type);
        $this->db->order_by("(CASE WHEN sda.discount_type = {$fee_type_esc} THEN 2 WHEN sda.discount_type = 'all_daily_fees' THEN 0 ELSE 1 END)", 'DESC', FALSE);
        $this->db->order_by('sda.assigned_at', 'DESC'); // Most recent assignment wins
        $this->db->limit(1);
        
        $assignment = $this->db->get()->row_array();
        
        if ($assignment) {
            $result['has_discount'] = true;
            $result['discount_type'] = $assignment['discount_type'];
            $result['discount_method'] = $assignment['discount_method'];
            $result['discount_value'] = $assignment['discount_value'];
            $result['profile_id'] = $assignment['profile_id'];
            $result['profile_name'] = $assignment['profile_name'];
            
            // Apply per-class / per-fee-type rule override (discount_profile_rules)
            // Rules let the same profile carry a different value for a specific class
            // and/or a specific fee type (bill_category_id stores the fee type for daily fees).
            $rule_value = $this->get_profile_rule_value($assignment['profile_id'], $class_id, $fee_type);
            if ($rule_value !== null) {
                $result['discount_value'] = $rule_value;
                $result['rule_applied'] = true;
            }
        }
        
        return $result;
    }

    /**
     * Get the effective discount value from discount_profile_rules for a profile,
     * honouring specificity: (class + fee type) > (class only) > (fee type only) > (global)
     *
     * @param  int    $profile_id Discount profile ID
     * @param  int    $class_id   Student's class ID
     * @param  string $fee_type   Fee type (feeding|breakfast|classes|water|transport) or null
     * @return float|null Null when no rule applies (caller keeps profile default)
     */
    public function get_profile_rule_value($profile_id, $class_id, $fee_type = null) {
        if (empty($profile_id)) {
            return null;
        }
        
        $this->db->select('rule_id, class_id, bill_category_id, discount_value');
        $this->db->from('discount_profile_rules');
        $this->db->where('profile_id', $profile_id);
        $this->db->group_start();
            $this->db->where('class_id', $class_id);
            $this->db->or_where('class_id IS NULL');
        $this->db->group_end();
        $query = $this->db->get();
        
        if ($query->num_rows() === 0) {
            return null;
        }
        
        $best = null;
        $best_score = -1;
        foreach ($query->result_array() as $rule) {
            $rule_fee = ($rule['bill_category_id'] !== null && $rule['bill_category_id'] !== '')
                ? (string)$rule['bill_category_id'] : null;
            $rule_class = ($rule['class_id'] !== null && $rule['class_id'] !== '')
                ? (string)$rule['class_id'] : null;
            
            // A rule matches when its class scope covers the student's class AND
            // its fee scope covers the fee being priced.
            $class_matches = ($rule_class === null) || ((string)$class_id === $rule_class);
            $fee_matches = ($rule_fee === null)
                || ($fee_type !== null && strtolower($rule_fee) === strtolower($fee_type));
            
            if (!$class_matches || !$fee_matches) {
                continue; // Rule does not apply to this class/fee combination
            }
            
            // Score by specificity - highest wins (0 = global rule, still a match)
            $score = 0;
            if ($rule_class !== null) $score += 2;
            if ($rule_fee !== null && $fee_type !== null) $score += 1;
            
            if ($score > $best_score) {
                $best_score = $score;
                $best = $rule;
            }
        }
        
        return ($best !== null) ? (float)$best['discount_value'] : null;
    }

    /**
     * Compatibility wrapper used by Bus_conductor / legacy code.
     * Delegates to get_student_discount_for_date() as of "now" (or a supplied date).
     */
    public function get_daily_fee_discount($student_id, $year, $term, $class_id, $fee_type, $as_of_date = null) {
        return $this->get_student_discount_for_date(
            $student_id, $year, $term, $class_id, $fee_type, $as_of_date !== null ? $as_of_date : time()
        );
    }

    /**
     * Get ALL active approved daily-fee discount assignments for a student,
     * with effective values (per-class rule overrides applied).
     * Used by attendance / fee-collection views that need the full discount picture.
     */
    public function get_student_discounts($student_id, $year = null, $term = null, $class_id = null) {
        $this->db->select('sda.*, dp.profile_name, dp.description');
        $this->db->from('student_discount_assignments sda');
        $this->db->join('discount_profiles dp', 'sda.profile_id = dp.profile_id');
        $this->db->where('sda.student_id', $student_id);
        $this->db->where('dp.discount_category', 'daily_fees');
        $this->db->where('sda.is_active', 1);
        $this->db->where('sda.status', 'approved');
        $n        = $this->db->order_by('sda.assigned_at', 'DESC')->get()->result_array();
        
        // Apply per-class rule overrides so consumers see effective values
        foreach ($n as &$row) {
            if ($class_id) {
                $rule_value = $this->get_profile_rule_value($row['profile_id'], $class_id);
                if ($rule_value !== null) {
                    $row['base_discount_value'] = $row['discount_value'];
                    $row['discount_value'] = $rule_value;
                }
            }
        }
        return $n;
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
            // Apply per-class rule override first (same precedence as the main lookup)
            $effective_value = $assignment['discount_value'];
            if (!empty($class_id)) {
                $rule_value = $this->get_profile_rule_value($assignment['profile_id'], $class_id, null);
                if ($rule_value !== null) {
                    $effective_value = $rule_value;
                }
            }
            
            // Check if it's 100% discount
            if ($assignment['discount_method'] == 'percentage' && $effective_value >= 100) {
                return true;
            }
            // For fixed amount, we need to check if it covers all fees
            // This would require knowing the total daily fees, so we'll be conservative
            // and only return true for percentage-based 100% discounts
        }
        
        return false;
    }
}
