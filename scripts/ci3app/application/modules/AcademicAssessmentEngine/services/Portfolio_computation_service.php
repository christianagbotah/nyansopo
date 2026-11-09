<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Portfolio_computation_service {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
    }
    
    /**
     * Get SBA weight configuration for class category
     */
    public function get_sba_weights($class_category, $year, $term) {
        $config = $this->CI->db->get_where('sba_weight_config', [
            'class_category' => $class_category,
            'academic_year' => $year,
            'term' => $term,
            'deleted_at' => NULL
        ])->row();
        
        if(!$config) {
            return [
                'class_test_weight' => 30.00,
                'project_weight' => 10.00,
                'exam_weight' => 60.00,
                'portfolio_as_class_test' => true,
                'config_id' => null
            ];
        }
        
        return [
            'class_test_weight' => $config->class_test_weight,
            'project_weight' => $config->project_weight,
            'exam_weight' => $config->exam_weight,
            'portfolio_as_class_test' => $config->portfolio_as_class_test,
            'config_id' => $config->id
        ];
    }
    
    /**
     * Compute weekly average for a student
     */
    public function compute_weekly_average($student_id, $subject_id, $year, $term, $week_number) {
        $scores = $this->CI->db->select('ps.score, ph.max_score')
            ->from('portfolio_scores ps')
            ->join('portfolio_headers ph', 'ph.header_id = ps.header_id')
            ->where('ps.student_id', $student_id)
            ->where('ph.subject_id', $subject_id)
            ->where('ph.year', $year)
            ->where('ph.term', $term)
            ->where('ph.week_number', $week_number)
            ->where('ps.deleted_at IS NULL')
            ->get()->result_array();
        
        if(empty($scores)) return 0;
        
        $total = 0;
        $max_total = 0;
        foreach($scores as $s) {
            $total += $s['score'];
            $max_total += $s['max_score'];
        }
        
        return $max_total > 0 ? round(($total / $max_total) * 100, 2) : 0;
    }
    
    /**
     * Compute term average for portfolio
     */
    public function compute_term_average($student_id, $subject_id, $class_id, $year, $term, $semester = null) {
        $query = $this->CI->db->select('ps.score, ph.max_score')
            ->from('portfolio_scores ps')
            ->join('portfolio_headers ph', 'ph.header_id = ps.header_id')
            ->where('ps.student_id', $student_id)
            ->where('ph.subject_id', $subject_id)
            ->where('ph.class_id', $class_id)
            ->where('ph.year', $year)
            ->where('ph.term', $term)
            ->where('ps.deleted_at IS NULL')
            ->where('ph.status', 'published');
        
        if($semester) $query->where('ph.semester', $semester);
        
        $scores = $query->get()->result_array();
        
        if(empty($scores)) return 0;
        
        $total = 0;
        $max_total = 0;
        foreach($scores as $s) {
            $total += $s['score'];
            $max_total += $s['max_score'];
        }
        
        $average = $max_total > 0 ? round(($total / $max_total) * 100, 2) : 0;
        
        // Update aggregate table
        $this->CI->db->replace('portfolio_aggregates', [
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'class_id' => $class_id,
            'year' => $year,
            'term' => $term,
            'semester' => $semester,
            'term_average' => $average,
            'total_assessments' => count($scores)
        ]);
        
        return $average;
    }
    
    /**
     * Auto-fill SBA class test from portfolio average with audit trail
     */
    public function sync_to_sba($student_id, $subject_id, $class_id, $year, $term, $semester = null) {
        // Get class category
        $class = $this->CI->db->get_where('class', ['class_id' => $class_id])->row();
        $class_category = $class->category ?? 'JHS';
        
        // Get weight configuration
        $weights = $this->get_sba_weights($class_category, $year, $term);
        
        if(!$weights['portfolio_as_class_test']) {
            return ['status' => 'disabled', 'message' => 'Portfolio as class test is disabled'];
        }
        
        $term_average = $this->compute_term_average($student_id, $subject_id, $class_id, $year, $term, $semester);
        
        if($term_average == 0) {
            return ['status' => 'no_data', 'message' => 'No portfolio data'];
        }
        
        // Get portfolio aggregate for audit reference
        $aggregate = $this->CI->db->get_where('portfolio_aggregates', [
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'year' => $year,
            'term' => $term,
            'semester' => $semester
        ])->row();
        
        // Calculate class test score using configured weight
        $class_test_score = round(($term_average * $weights['class_test_weight']) / 100, 2);
        
        $this->CI->db->trans_start();
        
        // Update or insert SBA component
        $existing = $this->CI->db->get_where('sba_components', [
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'year' => $year,
            'term' => $term,
            'semester' => $semester
        ])->row();
        
        $sba_data = [
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'class_id' => $class_id,
            'year' => $year,
            'term' => $term,
            'semester' => $semester,
            'class_test' => $class_test_score,
            'applied_class_test_weight' => $weights['class_test_weight'],
            'applied_project_weight' => $weights['project_weight'],
            'applied_exam_weight' => $weights['exam_weight'],
            'weight_config_id' => $weights['config_id'],
            'auto_filled' => 1,
            'last_sync_at' => date('Y-m-d H:i:s')
        ];
        
        if($existing) {
            $this->CI->db->where('component_id', $existing->component_id)
                         ->update('sba_components', $sba_data);
            $component_id = $existing->component_id;
        } else {
            $this->CI->db->insert('sba_components', $sba_data);
            $component_id = $this->CI->db->insert_id();
        }
        
        // Insert audit trail in sba_score_sources
        $this->log_score_source([
            'sba_component_id' => $component_id,
            'component_type' => 'class_test',
            'source_type' => 'portfolio',
            'source_reference_id' => $aggregate ? $aggregate->aggregate_id : null,
            'original_value' => $term_average,
            'computed_value' => $class_test_score,
            'computation_formula' => "({$term_average} × {$weights['class_test_weight']}) / 100",
            'computed_by' => $this->CI->session->userdata('login_user_id'),
            'ip_address' => $this->CI->input->ip_address(),
            'user_agent' => $this->CI->input->user_agent(),
            'notes' => "Auto-synced from portfolio. Config ID: {$weights['config_id']}"
        ]);
        
        // Log in portfolio audit trail
        $this->log_audit('sync_sba', 'sba', $component_id, 
            json_encode(['old' => $existing ? $existing->class_test : null, 'new' => $class_test_score]));
        
        $this->CI->db->trans_complete();
        
        return [
            'status' => 'success',
            'class_test_score' => $class_test_score,
            'portfolio_average' => $term_average,
            'weight_applied' => $weights['class_test_weight'],
            'config_id' => $weights['config_id']
        ];
    }
    
    /**
     * Batch sync all students in a class/subject
     */
    public function batch_sync_class($class_id, $subject_id, $year, $term, $semester = null) {
        $students = $this->CI->db->select('student_id')
            ->from('enroll')
            ->where('class_id', $class_id)
            ->where('year', $year)
            ->get()->result_array();
        
        $results = [];
        foreach($students as $student) {
            $results[] = $this->sync_to_sba($student['student_id'], $subject_id, $class_id, $year, $term, $semester);
        }
        
        return $results;
    }
    
    /**
     * Log audit trail
     */
    private function log_audit($action, $entity_type, $entity_id, $value) {
        $this->CI->db->insert('portfolio_audit_trail', [
            'action_type' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'new_value' => $value,
            'user_id' => $this->CI->session->userdata('login_user_id'),
            'ip_address' => $this->CI->input->ip_address(),
            'user_agent' => $this->CI->input->user_agent()
        ]);
    }
}
