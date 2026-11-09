<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('request_unlock')) {
    function request_unlock($record_type, $record_id, $action_type, $reason, $requested_by) {
        $CI =& get_instance();
        
        $CI->db->insert('unlock_requests', [
            'record_type' => $record_type,
            'record_id' => $record_id,
            'action_type' => $action_type,
            'requested_by' => $requested_by,
            'reason' => $reason,
            'status' => 'pending'
        ]);
        
        return $CI->db->insert_id();
    }
}

if (!function_exists('unlock_record')) {
    function unlock_record($record_type, $record_id) {
        $CI =& get_instance();
        
        $table = $record_type;
        $id_field = $record_type . '_id';
        
        $CI->db->where($id_field, $record_id);
        $CI->db->update($table, [
            'can_edit' => 'approved',
            'can_delete' => 'approved'
        ]);
    }
}

if (!function_exists('relock_record')) {
    function relock_record($record_type, $record_id, $reason = 'Re-locked after approved edit') {
        $CI =& get_instance();
        
        if ($record_type == 'invoice') {
            lock_invoice($record_id, $reason);
        } else {
            lock_payment($record_id, $reason);
        }
    }
}
