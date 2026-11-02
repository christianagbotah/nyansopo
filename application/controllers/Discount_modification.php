<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discount Modification Controller
 * Handles approval workflow for modifying approved discounts
 */
class Discount_modification extends CI_Controller {
    
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
     * Request permission to modify an approved discount
     */
    public function request_modification() {
        $discount_id = $this->input->post('discount_id');
        $table = $this->input->post('table'); // 'student_discount_assignments' or 'invoice_discounts'
        $action_type = $this->input->post('action_type'); // 'edit', 'delete', 'activate', 'deactivate'
        $reason = $this->input->post('reason');
        $edit_data = $this->input->post('edit_data'); // For edit operations
        
        // Validate inputs
        if (empty($discount_id) || empty($table) || empty($action_type) || empty($reason)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'All fields are required'
            ]);
            return;
        }
        
        // Check if user can modify
        $permission = can_modify_discount($discount_id, $table);
        
        if ($permission['can_modify'] && !$permission['requires_approval']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You already have permission to modify this discount'
            ]);
            return;
        }
        
        // Create modification request
        $request_id = create_modification_request($discount_id, $table, $action_type, $reason, $edit_data);
        
        if ($request_id) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Modification request sent to super admin for approval',
                'request_id' => $request_id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create modification request'
            ]);
        }
    }
    
    /**
     * Get pending modification requests (Super Admin only)
     */
    public function get_pending_requests() {
        $admin_level = $this->session->userdata('admin_level');
        
        if ($admin_level != 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Access denied. Super admin only'
            ]);
            return;
        }
        
        $this->db->select('dmr.*, a.name as requester_name, a.email as requester_email');
        $this->db->from('discount_modification_requests dmr');
        $this->db->join('admin a', 'dmr.requested_by = a.admin_id');
        $this->db->where('dmr.status', 'pending');
        $this->db->order_by('dmr.created_at', 'DESC');
        $requests = $this->db->get()->result();
        
        // Enrich with discount details
        foreach ($requests as &$request) {
            $discount = $this->db->get_where($request->discount_table, ['id' => $request->discount_id])->row();
            $request->discount_details = $discount;
            
            // Get student info
            if ($discount) {
                $student = $this->db->get_where('student', ['student_id' => $discount->student_id])->row();
                $request->student_name = $student ? $student->name : 'Unknown';
                $request->student_code = $student ? $student->student_code : 'N/A';
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $requests
        ]);
    }
    
    /**
     * Approve a modification request (Super Admin only)
     */
    public function approve_request() {
        $admin_level = $this->session->userdata('admin_level');
        
        if ($admin_level != 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Access denied. Super admin only'
            ]);
            return;
        }
        
        $request_id = $this->input->post('request_id');
        $hours_valid = $this->input->post('hours_valid') ?: 24; // Default 24 hours
        
        if (empty($request_id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Request ID is required'
            ]);
            return;
        }
        
        $success = approve_modification_request($request_id, $hours_valid);
        
        if ($success) {
            echo json_encode([
                'status' => 'success',
                'message' => "Request approved. Valid for {$hours_valid} hours"
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to approve request'
            ]);
        }
    }
    
    /**
     * Reject a modification request (Super Admin only)
     */
    public function reject_request() {
        $admin_level = $this->session->userdata('admin_level');
        
        if ($admin_level != 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Access denied. Super admin only'
            ]);
            return;
        }
        
        $request_id = $this->input->post('request_id');
        $reason = $this->input->post('reason');
        
        if (empty($request_id) || empty($reason)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Request ID and reason are required'
            ]);
            return;
        }
        
        $success = reject_modification_request($request_id, $reason);
        
        if ($success) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Request rejected successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to reject request'
            ]);
        }
    }
    
    /**
     * Check if user has active approval for a discount
     */
    public function check_permission() {
        $discount_id = $this->input->post('discount_id');
        $table = $this->input->post('table');
        
        if (empty($discount_id) || empty($table)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Discount ID and table are required'
            ]);
            return;
        }
        
        $permission = can_modify_discount($discount_id, $table);
        
        echo json_encode([
            'status' => 'success',
            'data' => $permission
        ]);
    }
    
    /**
     * Execute an approved modification
     */
    public function execute_modification() {
        $request_id = $this->input->post('request_id');
        $discount_id = $this->input->post('discount_id');
        $table = $this->input->post('table');
        $action_type = $this->input->post('action_type');
        
        if (empty($request_id) || empty($discount_id) || empty($table) || empty($action_type)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required parameters'
            ]);
            return;
        }
        
        // Verify permission
        $permission = can_modify_discount($discount_id, $table);
        
        if (!$permission['can_modify']) {
            echo json_encode([
                'status' => 'error',
                'message' => $permission['reason']
            ]);
            return;
        }
        
        // Get old data for audit
        $old_data = $this->db->get_where($table, ['id' => $discount_id])->row_array();
        
        // Execute the action
        $success = false;
        $message = '';
        
        switch ($action_type) {
            case 'delete':
                $this->db->where('id', $discount_id);
                $success = $this->db->delete($table);
                $message = 'Discount deleted successfully';
                break;
                
            case 'edit':
                $request = $this->db->get_where('discount_modification_requests', ['request_id' => $request_id])->row();
                if ($request && $request->request_data) {
                    $edit_data = json_decode($request->request_data, true);
                    $edit_data['last_modified_by'] = $this->session->userdata('login_user_id');
                    $edit_data['last_modified_at'] = time();
                    
                    $this->db->where('id', $discount_id);
                    $success = $this->db->update($table, $edit_data);
                    $message = 'Discount updated successfully';
                }
                break;
                
            case 'activate':
                $this->db->where('id', $discount_id);
                $success = $this->db->update($table, [
                    'is_active' => 1,
                    'last_modified_by' => $this->session->userdata('login_user_id'),
                    'last_modified_at' => time()
                ]);
                $message = 'Discount activated successfully';
                break;
                
            case 'deactivate':
                $this->db->where('id', $discount_id);
                $success = $this->db->update($table, [
                    'is_active' => 0,
                    'last_modified_by' => $this->session->userdata('login_user_id'),
                    'last_modified_at' => time()
                ]);
                $message = 'Discount deactivated successfully';
                break;
        }
        
        if ($success) {
            // Get new data for audit
            $new_data = $action_type != 'delete' ? $this->db->get_where($table, ['id' => $discount_id])->row_array() : null;
            
            // Mark request as executed
            execute_approved_modification($request_id);
            
            // Log the modification
            log_discount_modification(
                $discount_id,
                $table,
                $action_type,
                $this->session->userdata('login_user_id'),
                $request_id,
                $old_data,
                $new_data
            );
            
            echo json_encode([
                'status' => 'success',
                'message' => $message
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to execute modification'
            ]);
        }
    }
    
    /**
     * Get user's modification requests
     */
    public function my_requests() {
        $user_id = $this->session->userdata('login_user_id');
        
        $this->db->select('dmr.*, aa.name as approver_name');
        $this->db->from('discount_modification_requests dmr');
        $this->db->join('admin aa', 'dmr.approved_by = aa.admin_id', 'left');
        $this->db->where('dmr.requested_by', $user_id);
        $this->db->order_by('dmr.created_at', 'DESC');
        $requests = $this->db->get()->result();
        
        // Enrich with discount details
        foreach ($requests as &$request) {
            $discount = $this->db->get_where($request->discount_table, ['id' => $request->discount_id])->row();
            $request->discount_details = $discount;
            
            // Calculate time remaining if approved
            if ($request->status == 'approved' && $request->approval_expires_at) {
                $request->time_remaining = $request->approval_expires_at - time();
                $request->hours_remaining = round($request->time_remaining / 3600, 1);
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $requests
        ]);
    }
    
    /**
     * View for pending requests (Super Admin)
     */
    public function pending_requests_view() {
        $admin_level = $this->session->userdata('admin_level');
        
        if ($admin_level != 1) {
            $this->session->set_flashdata('error_message', 'Access denied. Super admin only');
            redirect(site_url('admin/dashboard'));
            return;
        }
        
        $page_data['page_name'] = 'discount_modification_requests';
        $page_data['page_title'] = 'Pending Discount Modification Requests';
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * View for user's requests
     */
    public function my_requests_view() {
        $page_data['page_name'] = 'my_discount_modification_requests';
        $page_data['page_title'] = 'My Discount Modification Requests';
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
}
