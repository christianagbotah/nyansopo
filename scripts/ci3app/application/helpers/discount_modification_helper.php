<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount Modification Helper
 * Handles permission checks and approval workflows for discount modifications
 */

if (!function_exists('can_modify_discount')) {
    /**
     * Check if current user can modify an approved discount
     * 
     * @param int $discount_id
     * @param string $table 'student_discount_assignments' or 'invoice_discounts'
     * @return array ['can_modify' => bool, 'reason' => string, 'requires_approval' => bool]
     */
    function can_modify_discount($discount_id, $table = 'student_discount_assignments') {
        $CI =& get_instance();
        $user_id = $CI->session->userdata('login_user_id');
        $user_level = $CI->session->userdata('admin_level');
        
        // Super admin can always modify
        if ($user_level == 1) {
            return [
                'can_modify' => true,
                'reason' => 'Super admin has full access',
                'requires_approval' => false
            ];
        }
        
        // Get discount details
        $discount = $CI->db->get_where($table, ['id' => $discount_id])->row();
        
        if (!$discount) {
            return [
                'can_modify' => false,
                'reason' => 'Discount not found',
                'requires_approval' => false
            ];
        }
        
        // Check if discount is approved and locked
        if ($discount->status == 'approved' && $discount->is_locked == 1) {
            // Check if there's an active approved request
            $active_request = $CI->db->where('discount_id', $discount_id)
                ->where('discount_table', $table)
                ->where('requested_by', $user_id)
                ->where('status', 'approved')
                ->where('approval_expires_at >', time())
                ->where('executed_at IS NULL')
                ->get('discount_modification_requests')
                ->row();
            
            if ($active_request) {
                return [
                    'can_modify' => true,
                    'reason' => 'Approved request is active',
                    'requires_approval' => false,
                    'request_id' => $active_request->request_id,
                    'expires_at' => $active_request->approval_expires_at
                ];
            }
            
            return [
                'can_modify' => false,
                'reason' => 'Discount is locked. Request approval from super admin',
                'requires_approval' => true
            ];
        }
        
        // Pending or rejected discounts can be modified by creator
        if ($discount->created_by == $user_id) {
            return [
                'can_modify' => true,
                'reason' => 'You created this discount',
                'requires_approval' => false
            ];
        }
        
        return [
            'can_modify' => false,
            'reason' => 'You do not have permission to modify this discount',
            'requires_approval' => true
        ];
    }
}

if (!function_exists('create_modification_request')) {
    /**
     * Create a modification request for an approved discount
     * 
     * @param int $discount_id
     * @param string $table
     * @param string $action_type 'edit', 'delete', 'activate', 'deactivate'
     * @param string $reason
     * @param array $data Optional data for edit operations
     * @return int|false Request ID or false on failure
     */
    function create_modification_request($discount_id, $table, $action_type, $reason, $data = null) {
        $CI =& get_instance();
        $user_id = $CI->session->userdata('login_user_id');
        
        $request_data = [
            'discount_id' => $discount_id,
            'discount_table' => $table,
            'action_type' => $action_type,
            'requested_by' => $user_id,
            'request_reason' => $reason,
            'request_data' => $data ? json_encode($data) : null,
            'status' => 'pending',
            'created_at' => time()
        ];
        
        $CI->db->insert('discount_modification_requests', $request_data);
        $request_id = $CI->db->insert_id();
        
        // Notify super admins
        notify_super_admins_modification_request($request_id);
        
        return $request_id;
    }
}

if (!function_exists('approve_modification_request')) {
    /**
     * Approve a modification request with time limit
     * 
     * @param int $request_id
     * @param int $hours_valid How many hours the approval is valid
     * @return bool
     */
    function approve_modification_request($request_id, $hours_valid = 24) {
        $CI =& get_instance();
        $admin_id = $CI->session->userdata('login_user_id');
        $admin_level = $CI->session->userdata('admin_level');
        
        // Only super admin can approve
        if ($admin_level != 1) {
            return false;
        }
        
        $expires_at = time() + ($hours_valid * 3600);
        
        $update_data = [
            'status' => 'approved',
            'approved_by' => $admin_id,
            'approved_at' => time(),
            'approval_expires_at' => $expires_at,
            'updated_at' => time()
        ];
        
        $CI->db->where('request_id', $request_id);
        $CI->db->update('discount_modification_requests', $update_data);
        
        // Notify requester
        $request = $CI->db->get_where('discount_modification_requests', ['request_id' => $request_id])->row();
        if ($request) {
            notify_user_request_approved($request->requested_by, $request_id, $expires_at);
        }
        
        return true;
    }
}

if (!function_exists('reject_modification_request')) {
    /**
     * Reject a modification request
     * 
     * @param int $request_id
     * @param string $reason
     * @return bool
     */
    function reject_modification_request($request_id, $reason) {
        $CI =& get_instance();
        $admin_id = $CI->session->userdata('login_user_id');
        $admin_level = $CI->session->userdata('admin_level');
        
        // Only super admin can reject
        if ($admin_level != 1) {
            return false;
        }
        
        $update_data = [
            'status' => 'rejected',
            'approved_by' => $admin_id,
            'approved_at' => time(),
            'rejection_reason' => $reason,
            'updated_at' => time()
        ];
        
        $CI->db->where('request_id', $request_id);
        $CI->db->update('discount_modification_requests', $update_data);
        
        // Notify requester
        $request = $CI->db->get_where('discount_modification_requests', ['request_id' => $request_id])->row();
        if ($request) {
            notify_user_request_rejected($request->requested_by, $request_id, $reason);
        }
        
        return true;
    }
}

if (!function_exists('execute_approved_modification')) {
    /**
     * Execute an approved modification and mark as executed
     * 
     * @param int $request_id
     * @return bool
     */
    function execute_approved_modification($request_id) {
        $CI =& get_instance();
        $user_id = $CI->session->userdata('login_user_id');
        
        $request = $CI->db->get_where('discount_modification_requests', ['request_id' => $request_id])->row();
        
        if (!$request) {
            return false;
        }
        
        // Verify request is approved and not expired
        if ($request->status != 'approved' || $request->approval_expires_at < time()) {
            if ($request->approval_expires_at < time()) {
                $CI->db->where('request_id', $request_id);
                $CI->db->update('discount_modification_requests', ['status' => 'expired']);
            }
            return false;
        }
        
        // Verify requester
        if ($request->requested_by != $user_id) {
            return false;
        }
        
        // Mark as executed
        $CI->db->where('request_id', $request_id);
        $CI->db->update('discount_modification_requests', [
            'executed_at' => time(),
            'updated_at' => time()
        ]);
        
        // Log the execution
        log_discount_modification($request->discount_id, $request->discount_table, $request->action_type, $user_id, $request_id);
        
        return true;
    }
}

if (!function_exists('log_discount_modification')) {
    /**
     * Log discount modification to audit trail
     * 
     * @param int $discount_id
     * @param string $table
     * @param string $action_type
     * @param int $user_id
     * @param int $request_id
     * @param array $old_data
     * @param array $new_data
     * @param string $notes
     * @return int Audit ID
     */
    function log_discount_modification($discount_id, $table, $action_type, $user_id, $request_id = null, $old_data = null, $new_data = null, $notes = null) {
        $CI =& get_instance();
        
        $audit_data = [
            'discount_id' => $discount_id,
            'discount_table' => $table,
            'action_type' => $action_type,
            'performed_by' => $user_id,
            'old_data' => $old_data ? json_encode($old_data) : null,
            'new_data' => $new_data ? json_encode($new_data) : null,
            'request_id' => $request_id,
            'notes' => $notes,
            'created_at' => time()
        ];
        
        $CI->db->insert('discount_modification_audit', $audit_data);
        return $CI->db->insert_id();
    }
}

if (!function_exists('expire_old_requests')) {
    /**
     * Mark expired requests as expired (run via cron)
     * 
     * @return int Number of expired requests
     */
    function expire_old_requests() {
        $CI =& get_instance();
        
        $CI->db->where('status', 'approved');
        $CI->db->where('approval_expires_at <', time());
        $CI->db->where('executed_at IS NULL');
        $CI->db->update('discount_modification_requests', [
            'status' => 'expired',
            'updated_at' => time()
        ]);
        
        return $CI->db->affected_rows();
    }
}

if (!function_exists('notify_super_admins_modification_request')) {
    /**
     * Notify all super admins about a new modification request
     * 
     * @param int $request_id
     * @return void
     */
    function notify_super_admins_modification_request($request_id) {
        $CI =& get_instance();
        
        $request = $CI->db->get_where('discount_modification_requests', ['request_id' => $request_id])->row();
        if (!$request) return;
        
        $requester = $CI->db->get_where('admin', ['admin_id' => $request->requested_by])->row();
        $discount = $CI->db->get_where($request->discount_table, ['id' => $request->discount_id])->row();
        
        // Get all super admins
        $super_admins = $CI->db->get_where('admin', ['level' => 1])->result();
        
        $message = "New discount modification request from {$requester->name}. Action: {$request->action_type}. Reason: {$request->request_reason}";
        
        foreach ($super_admins as $admin) {
            // Send notification (implement your notification system)
            // send_notification($admin->admin_id, $message, 'discount_modification_request', $request_id);
        }
    }
}

if (!function_exists('notify_user_request_approved')) {
    /**
     * Notify user that their request was approved
     * 
     * @param int $user_id
     * @param int $request_id
     * @param int $expires_at
     * @return void
     */
    function notify_user_request_approved($user_id, $request_id, $expires_at) {
        $CI =& get_instance();
        
        $expires_in_hours = round(($expires_at - time()) / 3600, 1);
        $message = "Your discount modification request has been approved. You have {$expires_in_hours} hours to complete the action.";
        
        // Send notification (implement your notification system)
        // send_notification($user_id, $message, 'request_approved', $request_id);
    }
}

if (!function_exists('notify_user_request_rejected')) {
    /**
     * Notify user that their request was rejected
     * 
     * @param int $user_id
     * @param int $request_id
     * @param string $reason
     * @return void
     */
    function notify_user_request_rejected($user_id, $request_id, $reason) {
        $CI =& get_instance();
        
        $message = "Your discount modification request has been rejected. Reason: {$reason}";
        
        // Send notification (implement your notification system)
        // send_notification($user_id, $message, 'request_rejected', $request_id);
    }
}
