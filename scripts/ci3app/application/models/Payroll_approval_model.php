<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payroll Approval Model
 * 
 * Handles the payroll approval workflow including submission, approval, rejection,
 * and payment marking. This model manages the approval lifecycle and integrates
 * with the payroll_approvals table for tracking approval history.
 * 
 * @package    SchoolManager
 * @subpackage Models
 * @category   Payroll
 * @author     School Manager Dev Team
 */
class Payroll_approval_model extends MY_Model
{
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        // Set table configuration
        $this->table_name = 'payroll_approvals';
        $this->primary_key = 'approval_id';
        
        // Load dependencies
        $this->load->model('Audit_log_model');
        $this->load->model('Payroll_model');
    }
    
    /**
     * Submit payroll for approval
     * 
     * Updates the pay_salary record status from 'draft' to 'pending_approval'
     * and creates an approval record with action='submitted'.
     * 
     * @param int $pay_id The pay_salary record ID
     * @return bool True on success, false on failure
     */
    public function submit_for_approval($pay_id)
    {
        // Start transaction
        $this->db->trans_start();
        
        try {
            // Get current payroll data
            $payroll = $this->db->get_where('pay_salary', ['pay_id' => $pay_id])->row();
            
            if (!$payroll) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => 'Payroll record not found'];
            }
            
            // Check if already submitted or approved
            if (in_array($payroll->approval_status, ['pending_approval', 'approved', 'paid'])) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => 'Payroll already submitted for approval'];
            }
            
            // Update pay_salary status
            $this->db->where('pay_id', $pay_id);
            $this->db->update('pay_salary', [
                'approval_status' => 'pending_approval',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Insert approval record
            $approval_data = [
                'pay_id' => $pay_id,
                'approver_user_id' => $this->session->userdata('admin_id') ?: $this->session->userdata('teacher_id'),
                'approver_role' => $this->session->userdata('account_type') ?: 'admin',
                'action' => 'submitted',
                'comments' => 'Submitted for approval',
                'action_date' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('payroll_approvals', $approval_data);
            
            // Log the action
            $this->Audit_log_model->log_action(
                'payroll',
                'submit_for_approval',
                $pay_id,
                ['approval_status' => $payroll->approval_status],
                ['approval_status' => 'pending_approval']
            );
            
            // Task 27.4: Add audit logging for submit action
            $this->Payroll_model->log_audit(
                $pay_id,
                'submit',
                'approval_status',
                $payroll->approval_status,
                'pending_approval'
            );
            
            // Complete transaction
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => 'Transaction failed'];
            }
            
            // Task 5.1: Trigger submission notification
            // Requirement 5.1, 5.5, 5.8: Send notifications after successful submission
            try {
                // Load Notification_dispatcher library
                $this->load->library('Notification_dispatcher');
                
                // Get payroll data with employee details for notification
                $payroll_data = $this->get_payroll_with_staff($pay_id);
                
                // Dispatch notification
                $this->notification_dispatcher->dispatch_notification(
                    'submission',
                    $payroll_data,
                    ['submitted_by' => $this->session->userdata('admin_id') ?: $this->session->userdata('teacher_id')]
                );
                
            } catch (Exception $e) {
                // Requirement 5.8: Catch exceptions without disrupting workflow
                log_message('error', 'Notification dispatch failed for payroll submission ' . $pay_id . ': ' . $e->getMessage());
                // Continue without failing the submission
            }
            
            return ['success' => true, 'message' => 'Payroll submitted for approval successfully'];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Submit for approval failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit payroll: ' . $e->getMessage()];
        }
    }
    
    /**
     * Approve payroll
     * 
     * Updates the pay_salary record status to 'approved' and creates an approval
     * record with action='approved'. Requires approval permissions.
     * 
     * @param int $pay_id The pay_salary record ID
     * @param string $comments Optional approval comments
     * @return array Status array with success boolean and message
     */
    public function approve_payroll($pay_id, $comments = '')
    {
        // Check permissions
        if (!$this->can_user_approve()) {
            return ['success' => false, 'message' => 'You do not have permission to approve payroll'];
        }
        
        // Start transaction
        $this->db->trans_start();
        
        try {
            // Get current payroll data
            $payroll = $this->db->get_where('pay_salary', ['pay_id' => $pay_id])->row();
            
            if (!$payroll) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => 'Payroll record not found'];
            }
            
            // Check if in pending_approval status
            if ($payroll->approval_status !== 'pending_approval') {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => 'Payroll is not pending approval'];
            }
            
            // Update pay_salary status
            $this->db->where('pay_id', $pay_id);
            $this->db->update('pay_salary', [
                'approval_status' => 'approved',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Insert approval record
            $approval_data = [
                'pay_id' => $pay_id,
                'approver_user_id' => $this->session->userdata('admin_id') ?: $this->session->userdata('teacher_id'),
                'approver_role' => $this->session->userdata('account_type') ?: 'admin',
                'action' => 'approved',
                'comments' => $comments ?: 'Approved',
                'action_date' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('payroll_approvals', $approval_data);
            
            // Log the action
            $this->Audit_log_model->log_action(
                'payroll',
                'approve',
                $pay_id,
                ['approval_status' => $payroll->approval_status],
                ['approval_status' => 'approved']
            );
            
            // Task 27.4: Add audit logging for approve action
            $this->Payroll_model->log_audit(
                $pay_id,
                'approve',
                'approval_status',
                $payroll->approval_status,
                'approved'
            );
            
            // Complete transaction
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => 'Transaction failed'];
            }
            
            // Task 17.2: Invalidate dashboard cache after approval
            $this->load->driver('cache', array('adapter' => 'file'));
            $cache_key = "payroll_dashboard_" . $payroll->month . "_" . $payroll->year;
            $this->cache->delete($cache_key);
            
            // Task 5.2: Trigger approval notification
            // Requirement 5.2, 5.6: Send notifications after successful approval
            try {
                // Load Notification_dispatcher library
                $this->load->library('Notification_dispatcher');
                
                // Get approver information
                $approver_id = $this->session->userdata('admin_id') ?: $this->session->userdata('teacher_id');
                $approver = $this->db->get_where('admin', ['admin_id' => $approver_id])->row();
                $approver_name = $approver ? $approver->name : 'Administrator';
                
                // Get payroll data with employee details for notification
                $payroll_data = $this->get_payroll_with_staff($pay_id);
                
                // Dispatch notification
                $this->notification_dispatcher->dispatch_notification(
                    'approval',
                    $payroll_data,
                    [
                        'approved_by' => $approver_id,
                        'approver_name' => $approver_name,
                        'comments' => $comments ?: 'Approved'
                    ]
                );
                
            } catch (Exception $e) {
                // Requirement 5.8: Catch exceptions without disrupting workflow
                log_message('error', 'Notification dispatch failed for payroll approval ' . $pay_id . ': ' . $e->getMessage());
                // Continue without failing the approval
            }
            
            return ['success' => true, 'message' => 'Payroll approved successfully'];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Approve payroll failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve payroll: ' . $e->getMessage()];
        }
    }
    
    /**
     * Reject payroll
     * 
     * Updates the pay_salary record status to 'rejected' and creates an approval
     * record with action='rejected'. Requires rejection reason (minimum 10 characters).
     * 
     * @param int $pay_id The pay_salary record ID
     * @param string $reason Rejection reason (required, min 10 chars)
     * @return array Status array with success boolean and message
     */
    public function reject_payroll($pay_id, $reason)
    {
        // Check permissions
        if (!$this->can_user_approve()) {
            return ['success' => false, 'message' => 'You do not have permission to reject payroll'];
        }
        
        // Validate rejection reason
        if (empty($reason) || strlen(trim($reason)) < 10) {
            return ['success' => false, 'message' => 'Rejection reason must be at least 10 characters'];
        }
        
        // Start transaction
        $this->db->trans_start();
        
        try {
            // Get current payroll data
            $payroll = $this->db->get_where('pay_salary', ['pay_id' => $pay_id])->row();
            
            if (!$payroll) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => 'Payroll record not found'];
            }
            
            // Check if in pending_approval status
            if ($payroll->approval_status !== 'pending_approval') {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => 'Payroll is not pending approval'];
            }
            
            // Update pay_salary status back to draft
            $this->db->where('pay_id', $pay_id);
            $this->db->update('pay_salary', [
                'approval_status' => 'rejected',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Insert approval record
            $approval_data = [
                'pay_id' => $pay_id,
                'approver_user_id' => $this->session->userdata('admin_id') ?: $this->session->userdata('teacher_id'),
                'approver_role' => $this->session->userdata('account_type') ?: 'admin',
                'action' => 'rejected',
                'comments' => $reason,
                'action_date' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('payroll_approvals', $approval_data);
            
            // Log the action
            $this->Audit_log_model->log_action(
                'payroll',
                'reject',
                $pay_id,
                ['approval_status' => $payroll->approval_status],
                ['approval_status' => 'rejected', 'reason' => $reason]
            );
            
            // Task 27.4: Add audit logging for reject action
            $this->Payroll_model->log_audit(
                $pay_id,
                'reject',
                'approval_status',
                $payroll->approval_status,
                'rejected'
            );
            
            // Complete transaction
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => 'Transaction failed'];
            }
            
            // Task 17.2: Invalidate dashboard cache after rejection
            $this->load->driver('cache', array('adapter' => 'file'));
            $cache_key = "payroll_dashboard_" . $payroll->month . "_" . $payroll->year;
            $this->cache->delete($cache_key);
            
            // Task 5.3: Trigger rejection notification
            // Requirement 5.3, 5.7: Send notifications after successful rejection
            try {
                // Load Notification_dispatcher library
                $this->load->library('Notification_dispatcher');
                
                // Get payroll data with employee details for notification
                $payroll_data = $this->get_payroll_with_staff($pay_id);
                
                // Get approver ID and retrieve rejector name from admin table
                $approver_id = $this->session->userdata('admin_id') ?: $this->session->userdata('teacher_id');
                
                // Retrieve rejector name from admin table
                $rejector = $this->db->select('name')
                    ->from('admin')
                    ->where('admin_id', $approver_id)
                    ->get()
                    ->row();
                
                $rejector_name = $rejector ? $rejector->name : 'Admin';
                
                // Dispatch notification
                $this->notification_dispatcher->dispatch_notification(
                    'rejection',
                    $payroll_data,
                    [
                        'rejected_by' => $approver_id,
                        'rejector_name' => $rejector_name,
                        'reason' => $reason
                    ]
                );
                
            } catch (Exception $e) {
                // Requirement 5.8: Catch exceptions without disrupting workflow
                log_message('error', 'Notification dispatch failed for payroll rejection ' . $pay_id . ': ' . $e->getMessage());
                // Continue without failing the rejection
            }
            
            return ['success' => true, 'message' => 'Payroll rejected successfully'];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Reject payroll failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject payroll: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark payroll as paid
     * 
     * Updates the pay_salary record status to 'paid'. Requires 'approved' status
     * and Finance Manager role.
     * 
     * @param int $pay_id The pay_salary record ID
     * @return array Status array with success boolean and message
     */
    public function mark_as_paid($pay_id)
    {
        // Check if user is Finance Manager
        $account_type = $this->session->userdata('account_type');
        if ($account_type !== 'super_admin' && $account_type !== 'admin') {
            return ['success' => false, 'message' => 'Only administrators can mark payroll as paid'];
        }
        
        // Start transaction
        $this->db->trans_start();
        
        try {
            // Get current payroll data
            $payroll = $this->db->get_where('pay_salary', ['pay_id' => $pay_id])->row();
            
            if (!$payroll) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => 'Payroll record not found'];
            }
            
            // Check if in approved status
            if ($payroll->approval_status !== 'approved') {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => 'Payroll must be approved before marking as paid'];
            }
            
            // Update pay_salary status
            $this->db->where('pay_id', $pay_id);
            $this->db->update('pay_salary', [
                'approval_status' => 'paid',
                'updated_at' => date('Y-m-d H:i:s'),
                'paid_at' => date('Y-m-d H:i:s')
            ]);
            
            // Insert approval record
            $approval_data = [
                'pay_id' => $pay_id,
                'approver_user_id' => $this->session->userdata('admin_id'),
                'approver_role' => $this->session->userdata('account_type'),
                'action' => 'paid',
                'comments' => 'Marked as paid',
                'action_date' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('payroll_approvals', $approval_data);
            
            // Log the action
            $this->Audit_log_model->log_action(
                'payroll',
                'mark_paid',
                $pay_id,
                ['approval_status' => $payroll->approval_status],
                ['approval_status' => 'paid']
            );
            
            // Complete transaction
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => 'Transaction failed'];
            }
            
            // Task 17.2: Invalidate dashboard cache after marking as paid
            $this->load->driver('cache', array('adapter' => 'file'));
            $cache_key = "payroll_dashboard_" . $payroll->month . "_" . $payroll->year;
            $this->cache->delete($cache_key);
            
            // Task 5.4: Trigger payment notification
            // Requirement 5.4: Send notifications after successful payment status update
            try {
                // Load Notification_dispatcher library
                $this->load->library('Notification_dispatcher');
                
                // Get payroll data with employee details for notification
                $payroll_data = $this->get_payroll_with_staff($pay_id);
                
                // Dispatch notification
                $this->notification_dispatcher->dispatch_notification(
                    'payment',
                    $payroll_data,
                    ['marked_paid_by' => $this->session->userdata('admin_id') ?: $this->session->userdata('teacher_id')]
                );
                
            } catch (Exception $e) {
                // Requirement 5.8: Catch exceptions without disrupting workflow
                log_message('error', 'Notification dispatch failed for payroll payment ' . $pay_id . ': ' . $e->getMessage());
                // Continue without failing the mark as paid operation
            }
            
            return ['success' => true, 'message' => 'Payroll marked as paid successfully'];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Mark as paid failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark payroll as paid: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get pending approvals
     * 
     * Retrieves all payroll records with approval_status='pending_approval'
     * joined with staff tables to get employee names.
     * 
     * @return array Array of pending payroll records with employee details
     */
    public function get_pending_approvals()
    {
        $this->db->select('ps.*, COALESCE(t.name, a.name, nts.name) as employee_name, ps.employment_category');
        $this->db->from('pay_salary ps');
        $this->db->join('teacher t', 'ps.employee_code = t.teacher_code AND ps.employment_category = "teacher"', 'left');
        $this->db->join('admin a', 'ps.employee_code = a.admin_code AND ps.employment_category = "administrator"', 'left');
        $this->db->join('non_teaching_staff nts', 'ps.employee_code = nts.staff_code AND ps.employment_category = "non_teaching_staff"', 'left');
        // Removed approval_status filter to return ALL payrolls for filtering on frontend
        // Old: $this->db->where('ps.approval_status', 'pending_approval');
        $this->db->order_by('ps.created_at', 'DESC');
        
        return $this->db->get()->result();
    }
    
    /**
     * Get approval history
     * 
     * Retrieves the approval history for a given payroll record,
     * joined with the admin table to get approver names.
     * 
     * @param int $pay_id The pay_salary record ID
     * @return array Array of approval history records
     */
    public function get_approval_history($pay_id)
    {
        $this->db->select('pa.*, COALESCE(a.name, t.name) as approver_name');
        $this->db->from('payroll_approvals pa');
        $this->db->join('admin a', 'pa.approver_user_id = a.admin_id AND pa.approver_role IN ("admin", "super_admin")', 'left');
        $this->db->join('teacher t', 'pa.approver_user_id = t.teacher_id AND pa.approver_role = "teacher"', 'left');
        $this->db->where('pa.pay_id', $pay_id);
        $this->db->order_by('pa.action_date', 'DESC');
        
        return $this->db->get()->result();
    }
    
    /**
     * Check if user can approve payroll
     * 
     * Checks if the current user has 'School Principal' or 'Finance Manager' role.
     * 
     * @return bool True if user can approve, false otherwise
     */
    public function can_user_approve()
    {
        $account_type = $this->session->userdata('account_type');
        
        // Super admin and admin can approve
        if (in_array($account_type, ['super_admin', 'admin'])) {
            return true;
        }
        
        // Check if teacher has principal privileges
        if ($account_type === 'teacher') {
            $teacher_id = $this->session->userdata('teacher_id');
            $teacher = $this->db->get_where('teacher', ['teacher_id' => $teacher_id])->row();
            
            // Check if teacher has a principal role indicator (you may need to add this field)
            // For now, we'll return false for teachers
            return false;
        }
        
        return false;
    }
    
    /**
     * Get payroll record with staff details
     * 
     * Retrieves a single payroll record joined with employee/staff tables
     * to get full employee information for notifications.
     * 
     * Task 5.1: Required for notification data
     * 
     * @param int $pay_id The pay_salary record ID
     * @return array Payroll record with employee details or null if not found
     */
    public function get_payroll_with_staff($pay_id)
    {
        $this->db->select('
            ps.*,
            COALESCE(t.name, a.name, nts.name) as employee_name,
            COALESCE(t.email, a.email, nts.email) as employee_email,
            COALESCE(t.phone, a.phone, nts.phone) as employee_phone,
            ps.pay_id as reference,
            creator.admin_id as created_by,
            creator.name as creator_name
        ');
        $this->db->from('pay_salary ps');
        $this->db->join('teacher t', 'ps.employee_code = t.teacher_code AND ps.employment_category = "teacher"', 'left');
        $this->db->join('admin a', 'ps.employee_code = a.admin_code AND ps.employment_category = "administrator"', 'left');
        $this->db->join('non_teaching_staff nts', 'ps.employee_code = nts.staff_code AND ps.employment_category = "non_teaching_staff"', 'left');
        $this->db->join('admin creator', 'ps.user_id = creator.admin_id', 'left');
        $this->db->where('ps.pay_id', $pay_id);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        
        return null;
    }
}
