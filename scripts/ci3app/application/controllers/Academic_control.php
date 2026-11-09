<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Academic Control Dashboard
 * Monitor result readiness and approval status
 */
class Academic_control extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('modules/AcademicAssessmentEngine/services/Result_approval_service');
        
        if($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'));
        }
    }
    
    public function index() {
        $page_data['page_name'] = 'academic_control_dashboard';
        $page_data['page_title'] = 'Academic Control Dashboard';
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Get completion metrics for all classes
     */
    public function get_class_metrics() {
        $year = $this->input->post('year') ?: $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->input->post('term') ?: $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $classes = $this->db->select('class_id, name')->from('class')->get()->result_array();
        $metrics = [];
        
        foreach($classes as $class) {
            $metrics[] = [
                'class_id' => $class['class_id'],
                'class_name' => $class['name'],
                'portfolio_completion' => $this->get_portfolio_completion($class['class_id'], $year, $term),
                'sba_completion' => $this->get_sba_completion($class['class_id'], $year, $term),
                'exam_completion' => $this->get_exam_completion($class['class_id'], $year, $term),
                'status' => $this->result_approval_service->get_status($class['class_id'], $year, $term)
            ];
        }
        
        echo json_encode(['status' => 'success', 'data' => $metrics]);
    }
    
    /**
     * Portfolio completion percentage
     */
    private function get_portfolio_completion($class_id, $year, $term) {
        $total_students = $this->db->where('class_id', $class_id)->count_all_results('student');
        if($total_students == 0) return 0;
        
        $completed = $this->db->select('COUNT(DISTINCT pa.student_id) as count')
            ->from('portfolio_aggregates pa')
            ->where([
                'pa.class_id' => $class_id,
                'pa.year' => $year,
                'pa.term' => $term,
                'pa.term_average IS NOT NULL' => NULL
            ])
            ->get()->row()->count;
        
        return round(($completed / $total_students) * 100, 1);
    }
    
    /**
     * SBA completion percentage
     */
    private function get_sba_completion($class_id, $year, $term) {
        $total_students = $this->db->where('class_id', $class_id)->count_all_results('student');
        if($total_students == 0) return 0;
        
        $completed = $this->db->select('COUNT(DISTINCT student_id) as count')
            ->from('sba_components')
            ->where([
                'class_id' => $class_id,
                'year' => $year,
                'term' => $term,
                'total_sba >' => 0
            ])
            ->get()->row()->count;
        
        return round(($completed / $total_students) * 100, 1);
    }
    
    /**
     * Exam completion percentage
     */
    private function get_exam_completion($class_id, $year, $term) {
        $total_students = $this->db->where('class_id', $class_id)->count_all_results('student');
        if($total_students == 0) return 0;
        
        // Check if exam_marks table exists
        if(!$this->db->table_exists('exam_marks')) return 0;
        
        $completed = $this->db->select('COUNT(DISTINCT student_id) as count')
            ->from('exam_marks')
            ->where([
                'class_id' => $class_id,
                'year' => $year,
                'term' => $term,
                'total_score >' => 0
            ])
            ->get()->row()->count;
        
        return round(($completed / $total_students) * 100, 1);
    }
    
    /**
     * Submit results for approval
     */
    public function submit_results() {
        $result = $this->result_approval_service->submit(
            $this->input->post('class_id'),
            $this->input->post('year'),
            $this->input->post('term'),
            $this->session->userdata('admin_id'),
            $this->input->post('notes')
        );
        echo json_encode($result);
    }
    
    /**
     * Approve results
     */
    public function approve_results() {
        $result = $this->result_approval_service->approve(
            $this->input->post('class_id'),
            $this->input->post('year'),
            $this->input->post('term'),
            $this->session->userdata('admin_id'),
            $this->input->post('notes')
        );
        echo json_encode($result);
    }
    
    /**
     * Lock results
     */
    public function lock_results() {
        $result = $this->result_approval_service->lock(
            $this->input->post('class_id'),
            $this->input->post('year'),
            $this->input->post('term'),
            $this->session->userdata('admin_id'),
            $this->input->post('notes')
        );
        echo json_encode($result);
    }
    
    /**
     * Unlock results (admin level 1 or 2 only)
     */
    public function unlock_results() {
        $admin_level = $this->session->userdata('admin_level');
        if($admin_level != 1 && $admin_level != 2) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        $result = $this->result_approval_service->unlock(
            $this->input->post('class_id'),
            $this->input->post('year'),
            $this->input->post('term'),
            $this->session->userdata('admin_id'),
            $this->input->post('reason')
        );
        echo json_encode($result);
    }
    
    /**
     * Get audit trail
     */
    public function get_audit_trail() {
        $audit = $this->result_approval_service->get_audit_trail(
            $this->input->post('class_id'),
            $this->input->post('year'),
            $this->input->post('term')
        );
        echo json_encode(['status' => 'success', 'data' => $audit]);
    }
}
