<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync API - Additional Endpoints for Multi-User Support
 * Add these methods to api/Sync.php controller
 */

/**
 * Get pending queue items (for local device)
 */
public function get_pending() {
    $this->load->library('Offline_sync');
    $queue = $this->offline_sync->get_pending(100);
    echo json_encode($queue);
}

/**
 * Mark items as synced
 */
public function mark_synced() {
    $ids = $this->input->post('ids');
    $this->load->library('Offline_sync');
    $this->offline_sync->mark_synced($ids);
    echo json_encode(['status' => 'success']);
}

/**
 * Apply server changes to local database
 */
public function apply_changes() {
    $changes = $this->input->post('changes');
    
    $this->db->trans_start();
    
    foreach($changes as $table => $records) {
        foreach($records as $record) {
            $this->apply_record($table, $record);
        }
    }
    
    $this->db->trans_complete();
    
    echo json_encode(['status' => 'success']);
}

/**
 * Apply single record with optimistic locking
 */
private function apply_record($table, $record) {
    $primary_key = $this->Sync_model->get_primary_key($table);
    $record_id = $record[$primary_key];
    
    // Check if record exists
    $existing = $this->db->where($primary_key, $record_id)->get($table)->row_array();
    
    if($existing) {
        // Check version for optimistic locking
        if(isset($existing['version']) && isset($record['version'])) {
            if($record['version'] <= $existing['version']) {
                // Server version is older or same, skip
                return;
            }
        }
        
        // Update
        $this->db->where($primary_key, $record_id)->update($table, $record);
    } else {
        // Insert
        $this->db->insert($table, $record);
    }
}

/**
 * Get conflicts for device
 */
public function get_conflicts() {
    $conflicts = $this->db->where('resolution', 'PENDING')
        ->order_by('created_at', 'DESC')
        ->get('sync_conflicts')
        ->result_array();
    
    echo json_encode(['status' => 'success', 'conflicts' => $conflicts]);
}

/**
 * Batch sync for multiple tables
 */
public function batch_sync() {
    $tables = $this->input->post('tables');
    $last_sync = $this->input->post('last_sync');
    
    $results = [];
    
    foreach($tables as $table) {
        $changes = $this->Sync_model->get_changes_since($table, $last_sync, $this->device_id);
        $results[$table] = [
            'count' => count($changes),
            'records' => $changes
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Health check endpoint
 */
public function health() {
    echo json_encode([
        'status' => 'online',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
}
