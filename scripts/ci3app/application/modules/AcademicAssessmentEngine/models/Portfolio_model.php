<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Portfolio_model extends CI_Model {
    
    public function create_header($data) {
        $this->db->trans_start();
        $this->db->insert('portfolio_headers', $data);
        $header_id = $this->db->insert_id();
        $this->db->trans_complete();
        return $header_id;
    }
    
    public function save_scores($header_id, $scores) {
        $this->db->trans_start();
        
        foreach($scores as $score) {
            $this->db->replace('portfolio_scores', [
                'header_id' => $header_id,
                'student_id' => $score['student_id'],
                'score' => $score['score'],
                'remarks' => $score['remarks'] ?? null,
                'recorded_by' => $this->session->userdata('login_user_id')
            ]);
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    public function get_headers($filters) {
        $this->db->select('ph.*, s.name as subject_name, c.name as class_name')
            ->from('portfolio_headers ph')
            ->join('subject s', 's.subject_id = ph.subject_id')
            ->join('class c', 'c.class_id = ph.class_id')
            ->where('ph.deleted_at IS NULL');
        
        if(isset($filters['class_id'])) $this->db->where('ph.class_id', $filters['class_id']);
        if(isset($filters['subject_id'])) $this->db->where('ph.subject_id', $filters['subject_id']);
        if(isset($filters['teacher_id'])) $this->db->where('ph.teacher_id', $filters['teacher_id']);
        if(isset($filters['year'])) $this->db->where('ph.year', $filters['year']);
        if(isset($filters['term'])) $this->db->where('ph.term', $filters['term']);
        
        return $this->db->get()->result_array();
    }
    
    public function get_scores($header_id) {
        return $this->db->select('ps.*, s.name as student_name')
            ->from('portfolio_scores ps')
            ->join('student s', 's.student_id = ps.student_id')
            ->where('ps.header_id', $header_id)
            ->where('ps.deleted_at IS NULL')
            ->get()->result_array();
    }
    
    public function get_student_portfolio($student_id, $subject_id, $year, $term) {
        return $this->db->select('ps.score, ph.strand_topic, ph.assessment_date, ph.max_score')
            ->from('portfolio_scores ps')
            ->join('portfolio_headers ph', 'ph.header_id = ps.header_id')
            ->where('ps.student_id', $student_id)
            ->where('ph.subject_id', $subject_id)
            ->where('ph.year', $year)
            ->where('ph.term', $term)
            ->where('ps.deleted_at IS NULL')
            ->order_by('ph.assessment_date', 'ASC')
            ->get()->result_array();
    }
}
