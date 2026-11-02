<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount temporal model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Discount_temporal_model extends MY_Model {

    /**
     * Get approved discounts ready for activation
     * (start_date <= today AND status = 'approved')
     * 
     * @return array Array of discount objects
     */
    public function get_pending_for_activation() {
        return $this->db
            ->where('status', 'approved')
            ->where('start_date <=', date('Y-m-d'))
            ->get('invoice_discounts')
            ->result();
    }

    /**
     * Get active discounts ready for expiration
     * (end_date < today AND status = 'active')
     * 
     * @return array Array of discount objects
     */
    public function get_active_for_expiration() {
        return $this->db
            ->where('status', 'active')
            ->where('end_date <', date('Y-m-d'))
            ->get('invoice_discounts')
            ->result();
    }

    /**
     * Activate a discount (approved → active)
     * 
     * @param int $discount_id Discount ID
     * @return bool Success status
     */
    public function activate_discount($discount_id) {
        return $this->db
            ->where('id', $discount_id)
            ->where('status', 'approved')
            ->update('invoice_discounts', [
                'status' => 'active',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * Expire a discount (active → expired)
     * 
     * @param int $discount_id Discount ID
     * @return bool Success status
     */
    public function expire_discount($discount_id) {
        return $this->db
            ->where('id', $discount_id)
            ->where('status', 'active')
            ->update('invoice_discounts', [
                'status' => 'expired',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * Get all currently active discounts
     * 
     * @param int|null $student_id Optional student filter
     * @return array Array of discount objects
     */
    public function get_active_discounts($student_id = null) {
        $this->db
            ->select('id.*, dt.name as type_name, dc.name as category_name')
            ->from('invoice_discounts id')
            ->join('discount_types dt', 'id.discount_type_id = dt.discount_type_id')
            ->join('discount_categories dc', 'id.category_id = dc.category_id')
            ->where('id.status', 'active')
            ->where('id.start_date <=', date('Y-m-d'))
            ->where('id.end_date >=', date('Y-m-d'));
        
        if ($student_id) {
            $this->db->where('id.student_id', $student_id);
        }
        
        return $this->db->get()->result();
    }

    /**
     * Get upcoming discounts (approved, starting within X days)
     * 
     * @param int $days Number of days to look ahead
     * @return array Array of discount objects
     */
    public function get_upcoming_discounts($days = 7) {
        $future_date = date('Y-m-d', strtotime("+{$days} days"));
        
        return $this->db
            ->select('id.*, dt.name as type_name, dc.name as category_name, s.name as student_name')
            ->from('invoice_discounts id')
            ->join('discount_types dt', 'id.discount_type_id = dt.discount_type_id')
            ->join('discount_categories dc', 'id.category_id = dc.category_id')
            ->join('students s', 'id.student_id = s.student_id')
            ->where('id.status', 'approved')
            ->where('id.start_date >', date('Y-m-d'))
            ->where('id.start_date <=', $future_date)
            ->order_by('id.start_date', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Get expiring discounts (active, expiring within X days)
     * 
     * @param int $days Number of days to look ahead
     * @return array Array of discount objects
     */
    public function get_expiring_discounts($days = 7) {
        $future_date = date('Y-m-d', strtotime("+{$days} days"));
        
        return $this->db
            ->select('id.*, dt.name as type_name, dc.name as category_name, s.name as student_name')
            ->from('invoice_discounts id')
            ->join('discount_types dt', 'id.discount_type_id = dt.discount_type_id')
            ->join('discount_categories dc', 'id.category_id = dc.category_id')
            ->join('students s', 'id.student_id = s.student_id')
            ->where('id.status', 'active')
            ->where('id.end_date >=', date('Y-m-d'))
            ->where('id.end_date <=', $future_date)
            ->order_by('id.end_date', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Check if discount is currently valid (active and within date range)
     * 
     * @param int $discount_id Discount ID
     * @return bool True if valid
     */
    public function is_valid($discount_id) {
        return $this->db
            ->where('id', $discount_id)
            ->where('status', 'active')
            ->where('start_date <=', date('Y-m-d'))
            ->where('end_date >=', date('Y-m-d'))
            ->count_all_results('invoice_discounts') > 0;
    }
}
