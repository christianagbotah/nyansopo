<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Offline Sync Library
 * Handles sync queue management and operations
 */
class Offline_sync {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Queue a record for sync
     */
    public function queue($table, $record_id, $operation, $data) {
        $this->CI->db->insert('sync_queue', [
            'table_name' => $table,
            'record_id' => $record_id,
            'operation' => $operation,
            'data' => json_encode($data),
            'synced' => FALSE
        ]);
    }
    
    /**
     * Get pending sync items
     */
    public function get_pending($limit = 100) {
        return $this->CI->db->where('synced', FALSE)
            ->where('retry_count <', 3)
            ->limit($limit)
            ->get('sync_queue')
            ->result_array();
    }
    
    /**
     * Mark items as synced
     */
    public function mark_synced($ids) {
        $this->CI->db->where_in('id', $ids)
            ->update('sync_queue', [
                'synced' => TRUE,
                'synced_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Mark sync failed
     */
    public function mark_failed($id, $error) {
        $this->CI->db->where('id', $id)
            ->set('retry_count', 'retry_count + 1', FALSE)
            ->set('error_message', $error)
            ->update('sync_queue');
    }
    
    /**
     * Log sync operation
     */
    public function log_sync($type, $table, $count, $status, $error = null) {
        $this->CI->db->insert('sync_log', [
            'sync_type' => $type,
            'table_name' => $table,
            'records_count' => $count,
            'status' => $status,
            'completed_at' => date('Y-m-d H:i:s'),
            'error_details' => $error
        ]);
    }
    
    /**
     * Update sync metadata
     */
    public function update_metadata($table, $type) {
        $field = $type === 'PUSH' ? 'last_push_at' : 'last_pull_at';
        
        $this->CI->db->where('table_name', $table)
            ->update('sync_metadata', [
                $field => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Get last sync time
     */
    public function get_last_sync($table, $type = 'PULL') {
        $field = $type === 'PUSH' ? 'last_push_at' : 'last_pull_at';
        
        $result = $this->CI->db->select($field)
            ->where('table_name', $table)
            ->get('sync_metadata')
            ->row();
        
        return $result ? $result->$field : null;
    }
}
