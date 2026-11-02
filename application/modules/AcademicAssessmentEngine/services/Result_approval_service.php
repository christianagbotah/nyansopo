<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Result Approval Service
 * GES-compliant result locking and approval workflow
 */
class Result_approval_service {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Get approval status for class/year/term
     */
    public function get_status($class_id, $year, $term) {
        $result = $this->CI->db->get_where('result_approval_status', [
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->row();
        
        if (!$result) {
            // Auto-create draft status
            $this->CI->db->insert('result_approval_status', [
                'class_id' => $class_id,
                'academic_year' => $year,
                'term' => $term,
                'status' => 'draft'
            ]);
            return 'draft';
        }
        
        return $result->status;
    }
    
    /**
     * Check if results are editable (considers teacher subject assignment)
     */
    public function is_editable($class_id, $year, $term, $subject_id = null, $teacher_id = null) {
        $status = $this->get_status($class_id, $year, $term);
        
        // If locked, only admin level 1 or 2 can unlock
        if ($status !== 'draft') {
            return false;
        }
        
        // If teacher context provided, check subject assignment
        if ($teacher_id && $subject_id) {
            $assigned = $this->CI->db->where([
                'teacher_id' => $teacher_id,
                'class_id' => $class_id,
                'subject_id' => $subject_id
            ])->count_all_results('class_subject');
            
            return $assigned > 0;
        }
        
        return true;
    }
    
    /**
     * Submit results for approval
     */
    public function submit($class_id, $year, $term, $user_id, $notes = null) {
        $status = $this->get_status($class_id, $year, $term);
        
        if ($status !== 'draft') {
            return ['status' => 'error', 'message' => 'Results already submitted'];
        }
        
        $this->CI->db->trans_start();
        
        $this->CI->db->where([
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->update('result_approval_status', [
            'status' => 'submitted',
            'submitted_by' => $user_id,
            'submitted_at' => date('Y-m-d H:i:s'),
            'notes' => $notes
        ]);
        
        $approval_id = $this->CI->db->get_where('result_approval_status', [
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->row()->id;
        
        $this->log_action($approval_id, 'submit', 'draft', 'submitted', $user_id, $notes);
        
        $this->CI->db->trans_complete();
        
        return ['status' => 'success', 'message' => 'Results submitted for approval'];
    }
    
    /**
     * Approve results
     */
    public function approve($class_id, $year, $term, $user_id, $notes = null) {
        $status = $this->get_status($class_id, $year, $term);
        
        if ($status !== 'submitted') {
            return ['status' => 'error', 'message' => 'Results must be submitted first'];
        }
        
        $this->CI->db->trans_start();
        
        $this->CI->db->where([
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->update('result_approval_status', [
            'status' => 'approved',
            'approved_by' => $user_id,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
        
        $approval_id = $this->CI->db->get_where('result_approval_status', [
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->row()->id;
        
        $this->log_action($approval_id, 'approve', 'submitted', 'approved', $user_id, $notes);
        
        $this->CI->db->trans_complete();
        
        return ['status' => 'success', 'message' => 'Results approved'];
    }
    
    /**
     * Lock results (final)
     */
    public function lock($class_id, $year, $term, $user_id, $notes = null) {
        $status = $this->get_status($class_id, $year, $term);
        
        if ($status !== 'approved') {
            return ['status' => 'error', 'message' => 'Results must be approved first'];
        }
        
        $this->CI->db->trans_start();
        
        $this->CI->db->where([
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->update('result_approval_status', [
            'status' => 'locked',
            'locked_by' => $user_id,
            'locked_at' => date('Y-m-d H:i:s')
        ]);
        
        $approval_id = $this->CI->db->get_where('result_approval_status', [
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->row()->id;
        
        $this->log_action($approval_id, 'lock', 'approved', 'locked', $user_id, $notes);
        
        $this->CI->db->trans_complete();
        
        return ['status' => 'success', 'message' => 'Results locked'];
    }
    
    /**
     * Unlock results (admin only, requires audit)
     */
    public function unlock($class_id, $year, $term, $user_id, $reason) {
        if (empty($reason)) {
            return ['status' => 'error', 'message' => 'Reason required for unlock'];
        }
        
        $status = $this->get_status($class_id, $year, $term);
        
        if ($status !== 'locked') {
            return ['status' => 'error', 'message' => 'Results not locked'];
        }
        
        $this->CI->db->trans_start();
        
        $this->CI->db->where([
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->update('result_approval_status', [
            'status' => 'draft',
            'locked_by' => NULL,
            'locked_at' => NULL
        ]);
        
        $approval_id = $this->CI->db->get_where('result_approval_status', [
            'class_id' => $class_id,
            'academic_year' => $year,
            'term' => $term
        ])->row()->id;
        
        $this->log_action($approval_id, 'unlock', 'locked', 'draft', $user_id, $reason);
        
        $this->CI->db->trans_complete();
        
        return ['status' => 'success', 'message' => 'Results unlocked'];
    }
    
    /**
     * Log approval action
     */
    private function log_action($approval_id, $action, $prev_status, $new_status, $user_id, $reason = null) {
        $this->CI->db->insert('result_approval_audit', [
            'approval_status_id' => $approval_id,
            'action' => $action,
            'previous_status' => $prev_status,
            'new_status' => $new_status,
            'performed_by' => $user_id,
            'reason' => $reason,
            'ip_address' => $this->CI->input->ip_address(),
            'user_agent' => $this->CI->input->user_agent()
        ]);
    }
    
    /**
     * Get audit trail
     */
    public function get_audit_trail($class_id, $year, $term) {
        return $this->CI->db->select('raa.*, a.name as performed_by_name')
            ->from('result_approval_audit raa')
            ->join('result_approval_status ras', 'ras.id = raa.approval_status_id')
            ->join('admin a', 'a.admin_id = raa.performed_by', 'left')
            ->where([
                'ras.class_id' => $class_id,
                'ras.academic_year' => $year,
                'ras.term' => $term
            ])
            ->order_by('raa.performed_at', 'DESC')
            ->get()
            ->result_array();
    }
    
    /**
     * Guard: Throw exception if not editable
     */
    public function enforce_editable($class_id, $year, $term) {
        if (!$this->is_editable($class_id, $year, $term)) {
            $status = $this->get_status($class_id, $year, $term);
            throw new Exception("Results are {$status} and cannot be edited. Contact admin to unlock.");
        }
    }
}
