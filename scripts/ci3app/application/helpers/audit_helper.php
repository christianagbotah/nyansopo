<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Log audit trail entry
 */
function log_audit($record_type, $record_id, $action, $old_values = null, $new_values = null, $notes = null) {
    $CI =& get_instance();
    $CI->load->database();
    
    $data = [
        'record_type' => $record_type,
        'record_id' => $record_id,
        'action' => $action,
        'old_values' => $old_values ? json_encode($old_values) : null,
        'new_values' => $new_values ? json_encode($new_values) : null,
        'performed_by' => $CI->session->userdata('login_user_id'),
        'ip_address' => $CI->input->ip_address(),
        'user_agent' => substr($CI->input->user_agent(), 0, 255),
        'notes' => $notes
    ];
    
    return $CI->db->insert('audit_trail', $data);
}

/**
 * Get audit trail for specific record
 */
function get_audit_for_record($record_type, $record_id) {
    $CI =& get_instance();
    $CI->load->database();
    
    return $CI->db
        ->select('audit_trail.*, admin.name as performed_by_name')
        ->from('audit_trail')
        ->join('admin', 'admin.admin_id = audit_trail.performed_by', 'left')
        ->where('record_type', $record_type)
        ->where('record_id', $record_id)
        ->order_by('performed_at', 'DESC')
        ->get()
        ->result();
}

/**
 * Get recent audit entries
 */
function get_recent_audit($limit = 50) {
    $CI =& get_instance();
    $CI->load->database();
    
    return $CI->db
        ->select('audit_trail.*, admin.name as performed_by_name')
        ->from('audit_trail')
        ->join('admin', 'admin.admin_id = audit_trail.performed_by', 'left')
        ->order_by('performed_at', 'DESC')
        ->limit($limit)
        ->get()
        ->result();
}
