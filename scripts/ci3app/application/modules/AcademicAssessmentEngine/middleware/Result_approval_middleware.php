<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Result Approval Middleware
 * Enforces read-only access when results are approved/locked
 */
class Result_approval_middleware {
    
    protected $CI;
    protected $approval_service;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->library('modules/AcademicAssessmentEngine/services/Result_approval_service');
        $this->approval_service = $this->CI->result_approval_service;
    }
    
    /**
     * Check before portfolio save (with teacher subject check)
     */
    public function before_portfolio_save($class_id, $year, $term, $subject_id = null, $teacher_id = null) {
        if ($teacher_id && $subject_id) {
            // Teacher context - check subject assignment
            if (!$this->approval_service->is_editable($class_id, $year, $term, $subject_id, $teacher_id)) {
                throw new Exception("You are not assigned to teach this subject or results are locked.");
            }
        } else {
            // Admin context - check status only
            $this->approval_service->enforce_editable($class_id, $year, $term);
        }
    }
    
    /**
     * Check before SBA save (with teacher subject check)
     */
    public function before_sba_save($class_id, $year, $term, $subject_id = null, $teacher_id = null) {
        if ($teacher_id && $subject_id) {
            // Teacher context - check subject assignment
            if (!$this->approval_service->is_editable($class_id, $year, $term, $subject_id, $teacher_id)) {
                throw new Exception("You are not assigned to teach this subject or results are locked.");
            }
        } else {
            // Admin context - check status only
            $this->approval_service->enforce_editable($class_id, $year, $term);
        }
    }
    
    /**
     * Check before exam marks edit (read-only enforcement)
     */
    public function before_exam_edit($exam_id) {
        $exam = $this->CI->db->select('class_id, year, term')
            ->from('exams_enterprise')
            ->where('exam_id', $exam_id)
            ->get()
            ->row();
        
        if ($exam) {
            $this->approval_service->enforce_editable($exam->class_id, $exam->year, $exam->term);
        }
    }
    
    /**
     * Check before computation
     */
    public function before_computation($class_id, $year, $term) {
        $status = $this->approval_service->get_status($class_id, $year, $term);
        
        if ($status === 'locked') {
            throw new Exception("Results are locked. Cannot recompute. Unlock first.");
        }
    }
    
    /**
     * Get status badge HTML
     */
    public function get_status_badge($class_id, $year, $term) {
        $status = $this->approval_service->get_status($class_id, $year, $term);
        
        $badges = [
            'draft' => '<span class="badge badge-secondary">Draft</span>',
            'submitted' => '<span class="badge badge-info">Submitted</span>',
            'approved' => '<span class="badge badge-success">Approved</span>',
            'locked' => '<span class="badge badge-danger">Locked</span>'
        ];
        
        return $badges[$status] ?? '';
    }
}
