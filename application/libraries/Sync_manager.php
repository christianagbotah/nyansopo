<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Manager Library
 * Handles server-side sync operations and conflict resolution
 */
class Sync_manager {
    
    protected $CI;
    protected $conflict_strategy = 'timestamp';
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->config('sync_config');
        $this->conflict_strategy = $this->CI->config->item('conflict_resolution');
    }
    
    /**
     * Process synced data from client with conflict resolution
     */
    public function process_sync_data($data) {
        $this->CI->db->trans_start();
        
        try {
            $table = $data['table'] ?? $data['table_name'] ?? null;
            $operation = $data['operation'] ?? 'insert';
            $record = $data['record'] ?? $data['record_data'] ?? [];
            $user_id = $data['user_id'] ?? null;
            $timestamp = $data['timestamp'] ?? time();
            
            if (!$table || empty($record)) {
                throw new Exception('Invalid sync data');
            }

            // Add sync metadata
            $record['synced_from_offline'] = 1;
            $record['sync_timestamp'] = date('Y-m-d H:i:s', $timestamp / 1000);
            $record['sync_user_id'] = $user_id;
            
            // Handle conflicts
            $conflict = $this->check_conflict($table, $record, $operation);
            if ($conflict) {
                $resolution = $this->resolve_conflict($table, $record, $conflict, $timestamp);
                if ($resolution['action'] === 'skip') {
                    return ['success' => true, 'message' => 'Skipped due to conflict', 'conflict' => true];
                }
                $record = $resolution['data'];
                $operation = $resolution['action'];
            }
            
            switch ($operation) {
                case 'insert':
                case 'create':
                    unset($record['local_id']);
                    $this->CI->db->insert($table, $record);
                    $insert_id = $this->CI->db->insert_id();
                    break;
                    
                case 'update':
                    $id_field = $this->get_id_field($table);
                    $id_value = $record[$id_field] ?? $record['id'] ?? null;
                    if ($id_value) {
                        $this->CI->db->where($id_field, $id_value);
                        $this->CI->db->update($table, $record);
                    }
                    break;
                    
                case 'delete':
                    $id_field = $this->get_id_field($table);
                    $id_value = $record[$id_field] ?? $record['id'] ?? null;
                    if ($id_value) {
                        $this->CI->db->where($id_field, $id_value);
                        $this->CI->db->update($table, ['can_delete' => 'trash']);
                    }
                    break;
            }
            
            $this->CI->db->trans_complete();
            
            if ($this->CI->db->trans_status() === FALSE) {
                throw new Exception('Database transaction failed');
            }
            
            return [
                'success' => true, 
                'message' => 'Data synced successfully',
                'insert_id' => $insert_id ?? null
            ];
            
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function get_id_field($table) {
        $id_map = [
            'student' => 'student_id',
            'teacher' => 'teacher_id',
            'class' => 'class_id',
            'invoice' => 'invoice_id',
            'payment' => 'payment_id'
        ];
        return $id_map[$table] ?? 'id';
    }

    private function check_conflict($table, $record, $operation) {
        if ($operation === 'insert' || $operation === 'create') return false;
        
        $id_field = $this->get_id_field($table);
        $id_value = $record[$id_field] ?? $record['id'] ?? null;
        
        if (!$id_value) return false;
        
        $this->CI->db->where($id_field, $id_value);
        $existing = $this->CI->db->get($table)->row_array();
        
        return $existing ?: false;
    }

   
    
    /**
     * Get pending sync items for a user
     */
    public function get_pending_syncs($user_id, $limit = 50) {
        $this->CI->db->where('user_id', $user_id);
        $this->CI->db->where('synced', 0);
        $this->CI->db->order_by('timestamp', 'ASC');
        $this->CI->db->limit($limit);
        return $this->CI->db->get('sync_queue')->result_array();
    }
    
    /**
     * Mark sync item as completed
     */
    public function mark_synced($sync_id) {
        $this->CI->db->where('id', $sync_id);
        $this->CI->db->update('sync_queue', ['synced' => 1, 'synced_at' => date('Y-m-d H:i:s')]);
        return $this->CI->db->affected_rows() > 0;
    }
    
    /**
     * Resolve conflicts using last-write-wins strategy
     */
    // public function resolve_conflict($table, $id_field, $id_value, $local_data, $server_timestamp) {
    //     $this->CI->db->where($id_field, $id_value);
    //     $server_record = $this->CI->db->get($table)->row_array();
        
    //     if (!$server_record) {
    //         return ['action' => 'insert', 'data' => $local_data];
    //     }
        
    //     $server_modified = strtotime($server_record['updated_at'] ?? $server_record['timestamp'] ?? 0);
    //     $local_modified = strtotime($local_data['updated_at'] ?? $local_data['timestamp'] ?? 0);
        
    //     if ($local_modified > $server_modified) {
    //         return ['action' => 'update', 'data' => $local_data];
    //     }
        
    //     return ['action' => 'skip', 'data' => $server_record];
    // }

     private function resolve_conflict($table, $local_data, $server_data, $local_timestamp) {
        if ($this->conflict_strategy === 'server_wins') {
            return ['action' => 'skip', 'data' => $server_data];
        }
        
        if ($this->conflict_strategy === 'client_wins') {
            return ['action' => 'update', 'data' => $local_data];
        }
        
        // Timestamp-based (last write wins)
        $server_time = strtotime($server_data['timestamp'] ?? $server_data['updated_at'] ?? 0);
        $local_time = $local_timestamp / 1000;
        
        if ($local_time > $server_time) {
            return ['action' => 'update', 'data' => array_merge($server_data, $local_data)];
        }
        
        return ['action' => 'skip', 'data' => $server_data];
    }
}
