<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Examination model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Examination_model extends MY_Model {
    
    public function create_exam($data) {
        $this->db->insert('exam', $data);
        return $this->db->insert_id();
    }
    
    public function get_exams($year = null, $term = null) {
        $this->db->select('*');
        $this->db->from('exam');
        
        if($year) $this->db->where('year', $year);
        if($term) $this->db->where('term', $term);
        
        $this->db->order_by('exam_id', 'DESC');
        return $this->db->get()->result_array();
    }
    
    public function get_exam($exam_id) {
        return $this->db->where('exam_id', $exam_id)->get('exam')->row_array();
    }
    
    public function get_dashboard_stats($year, $term) {
        // Debug: Let's see what we're searching for
        log_message('debug', 'Searching for exams with year: ' . $year . ', term: ' . $term);
        
        // Debug: Let's see what's actually in the exam table
        $all_exams = $this->db->select('exam_id, name, year, term, date')->get('exam')->result_array();
        log_message('debug', 'All exams in database: ' . json_encode($all_exams));
        
        // Total exams this term
        $total_exams = $this->db->where('year', $year)->where('term', $term)->count_all_results('exam');
        
        // Active exams (future dates)
        $active_exams = $this->db->where('year', $year)->where('term', $term)
                                 ->where('date >=', time())->count_all_results('exam');
        
        // Completed exams (past dates)
        $completed_exams = $this->db->where('year', $year)->where('term', $term)
                                    ->where('date <', time())->count_all_results('exam');
        
        // Total students enrolled this year
        $total_students = $this->db->where('year', $year)->count_all_results('enroll');
        
        // Total marks entered - simplified
        $marks_entered = $this->db->where('year', $year)->count_all_results('mark');
        
        return [
            'total_exams' => $total_exams,
            'active_exams' => $active_exams,
            'completed_exams' => $completed_exams,
            'total_students' => $total_students,
            'marks_entered' => $marks_entered,
            'debug_info' => [
                'search_year' => $year,
                'search_term' => $term,
                'all_exams' => $all_exams
            ]
        ];
    }
    
    public function get_recent_exams($year, $term, $limit = 5) {
        return $this->db->select('*')
                        ->where('year', $year)
                        ->where('term', $term)
                        ->order_by('exam_id', 'DESC')
                        ->limit($limit)
                        ->get('exam')->result_array();
    }
    
    public function get_exam_progress($exam_id) {
        $exam = $this->get_exam($exam_id);
        if(!$exam) return ['progress' => 0];
        
        // Get total students for this year
        $total_students = $this->db->where('year', $exam['year'])->count_all_results('enroll');
        
        // Get total subjects
        $total_subjects = $this->db->count_all_results('subject');
        
        // Get marks entered for this exam - simplified
        $marks_entered = $this->db->where('exam_id', $exam_id)->count_all_results('mark');
        
        $expected_marks = $total_students * $total_subjects;
        $progress = $expected_marks > 0 ? round(($marks_entered / $expected_marks) * 100, 1) : 0;
        
        return [
            'exam' => $exam,
            'total_students' => $total_students,
            'total_subjects' => $total_subjects,
            'marks_entered' => $marks_entered,
            'expected_marks' => $expected_marks,
            'progress' => min($progress, 100) // Cap at 100%
        ];
    }
}
