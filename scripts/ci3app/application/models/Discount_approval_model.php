<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount approval model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Discount_approval_model extends MY_Model {

    /**
     * Approve a pending discount
     * 
     * @param int $discount_id Discount ID
     * @param int $admin_id Admin user ID
     * @param string|null $reason Approval reason
     * @return bool Success status
     */
    public function approve($discount_id, $admin_id, $reason = null) {
        $data = [
            'status' => 'approved',
            'approved_by' => $admin_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($reason) {
            $data['reason'] = $reason;
        }
        
        return $this->db
            ->where('id', $discount_id)
            ->where('status', 'pending')
            ->update('invoice_discounts', $data);
    }

    /**
     * Reject a pending discount
     * 
     * @param int $discount_id Discount ID
     * @param int $admin_id Admin user ID
     * @param string $reason Rejection reason
     * @return bool Success status
     */
    public function reject($discount_id, $admin_id, $reason) {
        return $this->db
            ->where('id', $discount_id)
            ->where('status', 'pending')
            ->update('invoice_discounts', [
                'status' => 'rejected',
                'approved_by' => $admin_id,
                'approved_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * Get all pending discounts awaiting approval
     * 
     * @return array Array of discount objects
     */
    public function get_pending_approvals() {
        return $this->db
            ->select('id.*, dt.name as type_name, dc.name as category_name, s.name as student_name, s.code as student_code, u.name as created_by_name')
            ->from('invoice_discounts id')
            ->join('discount_types dt', 'id.discount_type_id = dt.discount_type_id')
            ->join('discount_categories dc', 'id.category_id = dc.category_id')
            ->join('students s', 'id.student_id = s.student_id')
            ->join('users u', 'id.created_by = u.user_id', 'left')
            ->where('id.status', 'pending')
            ->order_by('id.created_at', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Get approval history for a discount
     * 
     * @param int $discount_id Discount ID
     * @return array Array of history records
     */
    public function get_approval_history($discount_id) {
        return $this->db
            ->select('id.*, u.name as approved_by_name')
            ->from('invoice_discounts id')
            ->join('users u', 'id.approved_by = u.user_id', 'left')
            ->where('id.id', $discount_id)
            ->where_in('id.status', ['approved', 'rejected'])
            ->get()
            ->result();
    }

    /**
     * Get approval statistics
     * 
     * @return object Statistics object
     */
    public function get_approval_stats() {
        $stats = new stdClass();
        
        $stats->pending = $this->db
            ->where('status', 'pending')
            ->count_all_results('invoice_discounts');
        
        $stats->approved = $this->db
            ->where('status', 'approved')
            ->count_all_results('invoice_discounts');
        
        $stats->rejected = $this->db
            ->where('status', 'rejected')
            ->count_all_results('invoice_discounts');
        
        $stats->active = $this->db
            ->where('status', 'active')
            ->count_all_results('invoice_discounts');
        
        return $stats;
    }

    /**
     * Check if user can approve discount
     * 
     * @param int $user_id User ID
     * @param int $discount_id Discount ID
     * @return bool True if can approve
     */
    public function can_approve($user_id, $discount_id) {
        // Get discount
        $discount = $this->db
            ->where('id', $discount_id)
            ->get('invoice_discounts')
            ->row();
        
        if (!$discount) {
            return false;
        }
        
        // Cannot approve own discount
        if ($discount->created_by == $user_id) {
            return false;
        }
        
        // Must be pending
        if ($discount->status != 'pending') {
            return false;
        }
        
        return true;
    }
}
