<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Portfolio_computation_service_v2 {
    
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
        
        // Default GES weights if not configured
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
     * Sync to SBA with audit trail
     */
    public function sync_to_sba_with_audit($student_id, $subject_id, $class_id, $year, $term, $semester = null) {
        // Get class category
        $class = $this->CI->db->get_where('class', ['class_id' => $class_id])->row();
        $class_category = $class->category ?? 'JHS';
        
        // Get weight configuration
        $weights = $this->get_sba_weights($class_category, $year, $term);
        
        // Check if portfolio should be used for class test
        if(!$weights['portfolio_as_class_test']) {
            return ['status' => 'disabled', 'message' => 'Portfolio as class test is disabled for this category'];
        }
        
        // Get portfolio aggregate
        $aggregate = $this->CI->db->get_where('portfolio_aggregates', [
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'year' => $year,
            'term' => $term,
            'semester' => $semester
        ])->row();
        
        if(!$aggregate || !$aggregate->term_average) {
            return ['status' => 'no_data', 'message' => 'No portfolio data'];
        }
        
        // Calculate class test score using configured weight
        $portfolio_avg = $aggregate->term_average;
        $class_test_score = round(($portfolio_avg * $weights['class_test_weight']) / 100, 2);
        
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
        
        // Insert audit trail
        $this->log_score_source([
            'sba_component_id' => $component_id,
            'component_type' => 'class_test',
            'source_type' => 'portfolio',
            'source_reference_id' => $aggregate->aggregate_id,
            'original_value' => $portfolio_avg,
            'computed_value' => $class_test_score,
            'computation_formula' => "({$portfolio_avg} × {$weights['class_test_weight']}) / 100",
            'computed_by' => $this->CI->session->userdata('login_user_id'),
            'ip_address' => $this->CI->input->ip_address(),
            'user_agent' => $this->CI->input->user_agent(),
            'notes' => "Auto-synced from portfolio. Config ID: {$weights['config_id']}"
        ]);
        
        $this->CI->db->trans_complete();
        
        return [
            'status' => 'success',
            'class_test_score' => $class_test_score,
            'portfolio_average' => $portfolio_avg,
            'weight_applied' => $weights['class_test_weight'],
            'config_id' => $weights['config_id']
        ];
    }
    
    /**
     * Log score source for audit
     */
    private function log_score_source($data) {
        $this->CI->db->insert('sba_score_sources', $data);
    }
    
    /**
     * Get audit trail for SBA component
     */
    public function get_audit_trail($sba_component_id) {
        return $this->CI->db->select('ss.*, u.name as computed_by_name')
            ->from('sba_score_sources ss')
            ->join('admin u', 'u.admin_id = ss.computed_by', 'left')
            ->where('ss.sba_component_id', $sba_component_id)
            ->order_by('ss.computed_at', 'DESC')
            ->get()->result_array();
    }
    
    /**
     * Calculate final score with dynamic weights
     */
    public function calculate_final_score($student_id, $subject_id, $class_id, $year, $term, $exam_score) {
        $class = $this->CI->db->get_where('class', ['class_id' => $class_id])->row();
        $class_category = $class->category ?? 'JHS';
        
        $weights = $this->get_sba_weights($class_category, $year, $term);
        
        // Get SBA components
        $sba = $this->CI->db->get_where('sba_components', [
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'year' => $year,
            'term' => $term
        ])->row();
        
        $sba_total = $sba ? $sba->total_sba : 0;
        
        // Final Score = (SBA Total × SBA Weight) + (Exam Score × Exam Weight)
        $sba_weight = $weights['class_test_weight'] + $weights['project_weight'];
        $exam_weight = $weights['exam_weight'];
        
        $final_score = ($sba_total * $sba_weight / 100) + ($exam_score * $exam_weight / 100);
        
        return [
            'final_score' => round($final_score, 2),
            'sba_total' => $sba_total,
            'sba_weight' => $sba_weight,
            'exam_score' => $exam_score,
            'exam_weight' => $exam_weight,
            'breakdown' => [
                'sba_contribution' => round($sba_total * $sba_weight / 100, 2),
                'exam_contribution' => round($exam_score * $exam_weight / 100, 2)
            ]
        ];
    }
}
