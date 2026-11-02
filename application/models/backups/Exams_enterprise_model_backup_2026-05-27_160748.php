<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exams_enterprise_model extends CI_Model {
    
    public function get_grade($score) {
        return $this->db->where('min_score <=', $score)
                        ->where('max_score >=', $score)
                        ->get('waec_grading_scale')
                        ->row_array();
    }
}
