<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Exams enterprise model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Exams_enterprise_model extends MY_Model {
    
    public function get_grade($score) {
        return $this->db->where('min_score <=', $score)
                        ->where('max_score >=', $score)
                        ->get('waec_grading_scale')
                        ->row_array();
    }
}
