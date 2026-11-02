<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Teacher Completion Enforcement
 * Ensures teachers complete all assessments before submission
 */
class Teacher_completion extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        
        if($this->session->userdata('teacher_login') != 1) {
            redirect(site_url('login'));
        }
    }
    
    public function index() {
        $page_data['page_name'] = 'teacher_completion_dashboard';
        $page_data['page_title'] = 'My Completion Status';
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Get teacher's assigned classes with completion status
     */
    public function get_my_assignments() {
        $teacher_id = $this->session->userdata('teacher_id');
        $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        $assignments = $this->db->select('cs.*, c.name as class_name, s.name as subject_name')
            ->from('class_subject cs')
            ->join('class c', 'c.class_id = cs.class_id')
            ->join('subject s', 's.subject_id = cs.subject_id')
            ->where('cs.teacher_id', $teacher_id)
            ->get()->result_array();
        
        $data = [];
        foreach($assignments as $assign) {
            $total_students = $this->db->where('class_id', $assign['class_id'])->count_all_results('student');
            
            $data[] = [
                'class_id' => $assign['class_id'],
                'subject_id' => $assign['subject_id'],
                'class_name' => $assign['class_name'],
                'subject_name' => $assign['subject_name'],
                'total_students' => $total_students,
                'portfolio_completion' => $this->get_portfolio_completion($assign['class_id'], $assign['subject_id'], $year, $term),
                'sba_completion' => $this->get_sba_completion($assign['class_id'], $assign['subject_id'], $year, $term),
                'exam_completion' => $this->get_exam_completion($assign['class_id'], $assign['subject_id'], $year, $term),
                'missing_students' => $this->get_missing_students($assign['class_id'], $assign['subject_id'], $year, $term)
            ];
        }
        
        echo json_encode(['status' => 'success', 'data' => $data]);
    }
    
    private function get_portfolio_completion($class_id, $subject_id, $year, $term) {
        $total = $this->db->where('class_id', $class_id)->count_all_results('student');
        if($total == 0) return 100;
        
        $completed = $this->db->where([
            'class_id' => $class_id,
            'subject_id' => $subject_id,
            'year' => $year,
            'term' => $term,
            'term_average IS NOT NULL' => NULL
        ])->count_all_results('portfolio_aggregates');
        
        return round(($completed / $total) * 100, 1);
    }
    
    private function get_sba_completion($class_id, $subject_id, $year, $term) {
        $total = $this->db->where('class_id', $class_id)->count_all_results('student');
        if($total == 0) return 100;
        
        $completed = $this->db->where([
            'class_id' => $class_id,
            'subject_id' => $subject_id,
            'year' => $year,
            'term' => $term,
            'total_sba >' => 0
        ])->count_all_results('sba_components');
        
        return round(($completed / $total) * 100, 1);
    }
    
    private function get_exam_completion($class_id, $subject_id, $year, $term) {
        if(!$this->db->table_exists('exam_marks')) return 100;
        
        $total = $this->db->where('class_id', $class_id)->count_all_results('student');
        if($total == 0) return 100;
        
        $completed = $this->db->where([
            'class_id' => $class_id,
            'subject_id' => $subject_id,
            'year' => $year,
            'term' => $term,
            'total_score >' => 0
        ])->count_all_results('exam_marks');
        
        return round(($completed / $total) * 100, 1);
    }
    
    private function get_missing_students($class_id, $subject_id, $year, $term) {
        $all_students = $this->db->select('student_id, name')
            ->where('class_id', $class_id)
            ->get('student')->result_array();
        
        $missing = [];
        foreach($all_students as $student) {
            $issues = [];
            
            // Check portfolio
            $portfolio = $this->db->where([
                'student_id' => $student['student_id'],
                'subject_id' => $subject_id,
                'year' => $year,
                'term' => $term
            ])->get('portfolio_aggregates')->row();
            
            if(!$portfolio || $portfolio->term_average === null) {
                $issues[] = 'Portfolio';
            }
            
            // Check SBA
            $sba = $this->db->where([
                'student_id' => $student['student_id'],
                'subject_id' => $subject_id,
                'year' => $year,
                'term' => $term
            ])->get('sba_components')->row();
            
            if(!$sba || $sba->total_sba == 0) {
                $issues[] = 'SBA';
            }
            
            if(!empty($issues)) {
                $missing[] = [
                    'student_name' => $student['name'],
                    'missing' => implode(', ', $issues)
                ];
            }
        }
        
        return $missing;
    }
}
