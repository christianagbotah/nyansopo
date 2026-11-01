<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Result_processor {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
    }
    
    public function compute_exam_results($exam_id) {
        $this->CI->db->trans_start();
        
        // Get all students for this exam
        $exam = $this->CI->db->get_where('enterprise_exams', ['exam_id' => $exam_id])->row_array();
        
        $this->CI->db->select('s.student_id');
        $this->CI->db->from('enroll e');
        $this->CI->db->join('student s', 's.student_id = e.student_id');
        $this->CI->db->where('e.class_id', $exam['class_id']);
        $this->CI->db->where('e.year', $exam['year']);
        $students = $this->CI->db->get()->result_array();
        
        // Calculate positions for each subject
        $this->CI->db->select('DISTINCT subject_id');
        $this->CI->db->where('exam_id', $exam_id);
        $subjects = $this->CI->db->get('enterprise_student_marks')->result_array();
        
        foreach($subjects as $subject) {
            $this->calculate_subject_positions($exam_id, $subject['subject_id']);
        }
        
        // Calculate overall positions
        $this->calculate_overall_positions($exam_id);
        
        $this->CI->db->trans_complete();
        
        return $this->CI->db->trans_status();
    }
    
    private function calculate_subject_positions($exam_id, $subject_id) {
        // Get all marks for this subject, ordered by total score
        $this->CI->db->select('mark_id, total_score');
        $this->CI->db->where('exam_id', $exam_id);
        $this->CI->db->where('subject_id', $subject_id);
        $this->CI->db->order_by('total_score', 'DESC');
        $marks = $this->CI->db->get('enterprise_student_marks')->result_array();
        
        $position = 1;
        foreach($marks as $mark) {
            $this->CI->db->where('mark_id', $mark['mark_id']);
            $this->CI->db->update('enterprise_student_marks', ['position' => $position]);
            $position++;
        }
    }
    
    private function calculate_overall_positions($exam_id) {
        // Get students with their total scores across all subjects
        $sql = "SELECT student_id, SUM(total_score) as overall_total, AVG(total_score) as average
                FROM enterprise_student_marks 
                WHERE exam_id = ? 
                GROUP BY student_id 
                ORDER BY overall_total DESC";
        
        $results = $this->CI->db->query($sql, [$exam_id])->result_array();
        
        $position = 1;
        foreach($results as $result) {
            // Update terminal report or create if not exists
            $report_data = [
                'student_id' => $result['student_id'],
                'exam_id' => $exam_id,
                'total_score' => $result['overall_total'],
                'average_score' => $result['average'],
                'position' => $position,
                'out_of' => count($results)
            ];
            
            $this->CI->db->replace('terminal_reports', $report_data);
            $position++;
        }
    }
}