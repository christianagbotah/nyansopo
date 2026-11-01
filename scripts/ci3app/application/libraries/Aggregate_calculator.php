<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Aggregate_calculator {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
    }
    
    public function format_for_report($student_id, $exam_id) {
        // Get student marks
        $this->CI->db->select('m.*, s.name as subject_name, s.subject_type');
        $this->CI->db->from('enterprise_student_marks m');
        $this->CI->db->join('subject s', 's.subject_id = m.subject_id');
        $this->CI->db->where('m.student_id', $student_id);
        $this->CI->db->where('m.exam_id', $exam_id);
        $this->CI->db->order_by('s.subject_type', 'ASC');
        $this->CI->db->order_by('s.name', 'ASC');
        $marks = $this->CI->db->get()->result_array();
        
        // Calculate aggregate
        $total_points = 0;
        $subject_count = 0;
        $core_subjects = 0;
        $elective_subjects = 0;
        
        foreach($marks as $mark) {
            $grade_point = $this->get_grade_point($mark['grade']);
            if($grade_point !== null) {
                $total_points += $grade_point;
                $subject_count++;
                
                if($mark['subject_type'] == 'core') {
                    $core_subjects++;
                } else {
                    $elective_subjects++;
                }
            }
        }
        
        $aggregate = $subject_count > 0 ? round($total_points / $subject_count, 2) : 0;
        
        return [
            'subjects' => $marks,
            'aggregate' => $aggregate,
            'total_subjects' => $subject_count,
            'core_subjects' => $core_subjects,
            'elective_subjects' => $elective_subjects
        ];
    }
    
    private function get_grade_point($grade) {
        $result = $this->CI->db->where('grade', $grade)
                                ->get('waec_grading_scale')
                                ->row();
        return $result ? $result->grade_point : null;
    }
}