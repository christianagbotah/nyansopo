<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Approval Requests Controller
 * Handles approval workflows for locked invoices and payments
 */
class Approval_requests extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        
        // Check if user is logged in
        if($this->session->userdata('login_type') == '') {
            redirect(site_url('login'), 'refresh');
        }
    }
    
    /**
     * Main approval requests page
     */
    public function index() {
        $user_level = $this->session->userdata('user_type');
        
        $page_data['page_name'] = 'approval_requests';
        $page_data['page_title'] = get_phrase('approval_requests');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $page_data['user_level'] = $user_level;
        
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Request approval to edit/delete locked invoice or payment
     */
    public function request_approval() {
        $record_type = $this->input->post('record_type'); // 'invoice' or 'payment'
        $record_id = $this->input->post('record_id');
        $action = $this->input->post('action'); // 'edit' or 'delete'
        $reason = $this->input->post('reason');
        
        // Validate inputs
        if(empty($record_type) || empty($record_id) || empty($action) || empty($reason)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'All fields are required'
            ]);
            return;
        }
        
        // Check if record exists and is locked
        $table = $record_type === 'invoice' ? 'invoice' : 'payment';
        $id_field = $record_type === 'invoice' ? 'invoice_id' : 'payment_id';
        
        $record = $this->db->get_where($table, [$id_field => $record_id])->row();
        
        if(!$record) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Record not found'
            ]);
            return;
        }
        
        $can_field = $action === 'edit' ? 'can_edit' : 'can_delete';
        
        if($record->$can_field == 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'This record is not locked and does not require approval'
            ]);
            return;
        }
        
        // Check if there's already a pending request
        $existing = $this->db->get_where('approval_requests', [
            'record_type' => $record_type,
            'record_id' => $record_id,
            'request_type' => $record_type . '_' . $action,
            'status' => 'pending'
        ])->row();
        
        if($existing) {
            echo json_encode([
                'status' => 'error',
                'message' => 'There is already a pending approval request for this action'
            ]);
            return;
        }
        
        // Create approval request
        $data = [
            'request_type' => $record_type . '_' . $action,
            'record_type' => $record_type,
            'record_id' => $record_id,
            'requested_by' => $this->session->userdata('login_user_id'),
            'requested_at' => date('Y-m-d H:i:s'),
            'reason' => $reason,
            'status' => 'pending',
            'record_data' => json_encode($record)
        ];
        
        $this->db->insert('approval_requests', $data);
        
        // Log the request
        $this->log_audit($record_type, $record_id, 'request_approval', null, $data, 'Approval requested: ' . $reason);
        
        // Notify super admins
        $this->notify_super_admins($record_type, $record_id, $action);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Approval request submitted successfully. Super admin will review your request.'
        ]);
    }
    
    /**
     * Get pending approval requests (for super admin)
     */
    public function get_pending_requests() {
        $user_level = $this->session->userdata('user_type');
        
        // Only super admin can view approval requests
        if($user_level != 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Access denied. Only super administrators can view approval requests.'
            ]);
            return;
        }
        
        $requests = $this->db->query("SELECT * FROM pending_approvals_view ORDER BY requested_at DESC")->result_array();
        
        echo json_encode([
            'status' => 'success',
            'data' => $requests
        ]);
    }
    
    /**
     * Approve or reject approval request (super admin only)
     */
    public function handle_request() {
        $user_level = $this->session->userdata('user_type');
        
        // Only super admin can approve/reject
        if($user_level != 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Access denied. Only super administrators can handle approval requests.'
            ]);
            return;
        }
        
        $request_id = $this->input->post('request_id');
        $action = $this->input->post('action'); // 'approve' or 'reject'
        $notes = $this->input->post('notes');
        
        if(empty($request_id) || empty($action)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Request ID and action are required'
            ]);
            return;
        }
        
        // Get the request
        $request = $this->db->get_where('approval_requests', ['id' => $request_id, 'status' => 'pending'])->row();
        
        if(!$request) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Request not found or already handled'
            ]);
            return;
        }
        
        // Update request status
        $this->db->where('id', $request_id);
        $this->db->update('approval_requests', [
            'status' => $action === 'approve' ? 'approved' : 'rejected',
            'handled_by' => $this->session->userdata('login_user_id'),
            'handled_at' => date('Y-m-d H:i:s'),
            'handler_notes' => $notes
        ]);
        
        // If approved, unlock the record temporarily
        if($action === 'approve') {
            $table = $request->record_type === 'invoice' ? 'invoice' : 'payment';
            $id_field = $request->record_type === 'invoice' ? 'invoice_id' : 'payment_id';
            
            // Determine which field to unlock
            $unlock_field = strpos($request->request_type, 'edit') !== false ? 'can_edit' : 'can_delete';
            
            $this->db->where($id_field, $request->record_id);
            $this->db->update($table, [
                $unlock_field => 1,
                'approval_status' => 'approved',
                'approval_handled_by' => $this->session->userdata('login_user_id'),
                'approval_handled_at' => date('Y-m-d H:i:s'),
                'approval_notes' => $notes
            ]);
            
            // Log the approval
            $this->log_audit($request->record_type, $request->record_id, 'approve', null, null, 'Approval granted: ' . $notes);
        } else {
            // Log the rejection
            $this->log_audit($request->record_type, $request->record_id, 'reject', null, null, 'Approval rejected: ' . $notes);
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => $action === 'approve' ? 'Request approved successfully' : 'Request rejected successfully'
        ]);
    }
    
    /**
     * Check if a record can be edited/deleted
     */
    public function check_permission() {
        $record_type = $this->input->post('record_type');
        $record_id = $this->input->post('record_id');
        $action = $this->input->post('action'); // 'edit' or 'delete'
        
        $table = $record_type === 'invoice' ? 'invoice' : 'payment';
        $id_field = $record_type === 'invoice' ? 'invoice_id' : 'payment_id';
        
        $record = $this->db->get_where($table, [$id_field => $record_id])->row();
        
        if(!$record) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Record not found'
            ]);
            return;
        }
        
        $can_field = $action === 'edit' ? 'can_edit' : 'can_delete';
        $can_perform = $record->$can_field == 1;
        
        echo json_encode([
            'status' => 'success',
            'can_perform' => $can_perform,
            'locked' => !$can_perform,
            'locked_reason' => $record->locked_reason ?? 'Record is locked',
            'requires_approval' => !$can_perform
        ]);
    }
    
    /**
     * Auto-lock records based on rules
     */
    public function auto_lock_records() {
        // Only run if auto-lock is enabled
        $auto_lock = $this->db->get_where('settings', ['type' => 'auto_lock_enabled'])->row();
        if($auto_lock->description !== 'yes') {
            return;
        }
        
        // Lock paid invoices
        $lock_paid = $this->db->get_where('settings', ['type' => 'invoice_lock_after_payment'])->row();
        if($lock_paid->description === 'yes') {
            $this->db->where('status', 'paid');
            $this->db->where('can_edit', 1);
            $this->db->update('invoice', [
                'can_edit' => 0,
                'can_delete' => 0,
                'locked_at' => date('Y-m-d H:i:s'),
                'locked_reason' => 'Invoice is fully paid'
            ]);
        }
        
        // Lock old invoices
        $lock_days = $this->db->get_where('settings', ['type' => 'invoice_lock_after_days'])->row();
        $days = intval($lock_days->description);
        
        if($days > 0) {
            $this->db->query("
                UPDATE invoice 
                SET can_edit = 0, can_delete = 0, locked_at = NOW(), locked_reason = 'Invoice is older than {$days} day(s)'
                WHERE DATEDIFF(NOW(), FROM_UNIXTIME(creation_timestamp)) > {$days} 
                AND (can_edit = 1 OR can_delete = 1)
            ");
        }
        
        // Lock all payments immediately
        $lock_payments = $this->db->get_where('settings', ['type' => 'payment_lock_immediately'])->row();
        if($lock_payments->description === 'yes') {
            $this->db->where('can_edit', 1);
            $this->db->update('payment', [
                'can_edit' => 0,
                'can_delete' => 0,
                'locked_at' => date('Y-m-d H:i:s'),
                'locked_reason' => 'Payment records are locked for security'
            ]);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Auto-lock completed']);
    }
    
    /**
     * Log audit trail
     */
    private function log_audit($record_type, $record_id, $action, $old_data = null, $new_data = null, $notes = null) {
        $this->db->insert('invoice_payment_audit_log', [
            'record_type' => $record_type,
            'record_id' => $record_id,
            'action' => $action,
            'performed_by' => $this->session->userdata('login_user_id'),
            'performed_at' => date('Y-m-d H:i:s'),
            'old_data' => $old_data ? json_encode($old_data) : null,
            'new_data' => $new_data ? json_encode($new_data) : null,
            'notes' => $notes,
            'ip_address' => $this->input->ip_address()
        ]);
    }
    
    /**
     * Notify super admins of new approval request
     */
    private function notify_super_admins($record_type, $record_id, $action) {
        // Get all super admins
        $super_admins = $this->db->get_where('admin', ['level' => 1])->result();
        
        foreach($super_admins as $admin) {
            // Create notification
            $notification_data = [
                'user_id' => $admin->admin_id,
                'user_type' => 'admin',
                'title' => 'New Approval Request',
                'message' => "A request to {$action} a {$record_type} (ID: {$record_id}) requires your approval",
                'type' => 'approval_request',
                'link' => site_url('approval_requests'),
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('notifications', $notification_data);
        }
    }
}
