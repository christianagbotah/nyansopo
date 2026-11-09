<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sync_model extends CI_Model {
    
    public function verify_device($device_id, $auth_token) {
        return $this->db->where([
            'device_id' => $device_id,
            'status' => 'ACTIVE'
        ])->get('sync_devices')->row();
    }
    
    public function register_device($device_name, $school_id) {
        $device_id = uniqid('device_', true);
        $auth_token = bin2hex(random_bytes(32));
        
        $this->db->insert('sync_devices', [
            'device_id' => $device_id,
            'device_name' => $device_name,
            'school_id' => $school_id,
            'status' => 'ACTIVE'
        ]);
        
        return [
            'status' => 'success',
            'device_id' => $device_id,
            'auth_token' => $auth_token
        ];
    }
    
    public function process_push_item($item, $device_id) {
        $table = $item['table_name'];
        $operation = $item['operation'];
        $data = json_decode($item['data'], true);
        $record_id = $item['record_id'];
        
        // Check for conflicts
        $conflict = $this->check_conflict($table, $record_id, $data, $device_id);
        if($conflict) {
            return [
                'id' => $item['id'],
                'status' => 'conflict',
                'conflict_id' => $conflict['id']
            ];
        }
        
        // Apply operation
        switch($operation) {
            case 'INSERT':
                $this->db->insert($table, $data);
                break;
            case 'UPDATE':
                $this->db->where($this->get_primary_key($table), $record_id)->update($table, $data);
                break;
            case 'DELETE':
                $this->db->where($this->get_primary_key($table), $record_id)->delete($table);
                break;
        }
        
        return [
            'id' => $item['id'],
            'status' => 'success'
        ];
    }
    
    public function get_changes_since($table, $last_sync, $device_id) {
        $primary_key = $this->get_primary_key($table);
        
        $query = $this->db->select('*')
            ->from($table)
            ->where('last_modified_at >', $last_sync)
            ->where('device_id !=', $device_id)
            ->get();
        
        return $query->result_array();
    }
    
    public function get_syncable_tables() {
        return $this->db->select('table_name')
            ->where('sync_enabled', TRUE)
            ->get('sync_metadata')
            ->result_array();
    }
    
    public function update_device_sync($device_id) {
        $this->db->where('device_id', $device_id)
            ->update('sync_devices', ['last_sync_at' => date('Y-m-d H:i:s')]);
    }
    
    public function check_conflict($table, $record_id, $local_data, $device_id) {
        $primary_key = $this->get_primary_key($table);
        $server_record = $this->db->where($primary_key, $record_id)->get($table)->row_array();
        
        if(!$server_record) return null;
        
        $local_timestamp = strtotime($local_data['last_modified_at']);
        $server_timestamp = strtotime($server_record['last_modified_at']);
        
        if($server_timestamp > $local_timestamp && $server_record['device_id'] != $device_id) {
            // Conflict detected
            $this->db->insert('sync_conflicts', [
                'table_name' => $table,
                'record_id' => $record_id,
                'local_data' => json_encode($local_data),
                'server_data' => json_encode($server_record),
                'local_timestamp' => date('Y-m-d H:i:s', $local_timestamp),
                'server_timestamp' => date('Y-m-d H:i:s', $server_timestamp)
            ]);
            
            return ['id' => $this->db->insert_id()];
        }
        
        return null;
    }
    
    public function resolve_conflict($conflict_id, $resolution, $merged_data = null) {
        $conflict = $this->db->where('id', $conflict_id)->get('sync_conflicts')->row();
        
        if(!$conflict) {
            return ['status' => 'error', 'message' => 'Conflict not found'];
        }
        
        $data_to_apply = null;
        
        switch($resolution) {
            case 'LOCAL_WINS':
                $data_to_apply = json_decode($conflict->local_data, true);
                break;
            case 'SERVER_WINS':
                $data_to_apply = json_decode($conflict->server_data, true);
                break;
            case 'MERGED':
                $data_to_apply = $merged_data;
                break;
        }
        
        if($data_to_apply) {
            $primary_key = $this->get_primary_key($conflict->table_name);
            $this->db->where($primary_key, $conflict->record_id)
                ->update($conflict->table_name, $data_to_apply);
        }
        
        $this->db->where('id', $conflict_id)->update('sync_conflicts', [
            'resolution' => $resolution,
            'resolved_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['status' => 'success'];
    }
    
    public function get_sync_status($device_id) {
        $pending = $this->db->where('synced', FALSE)->count_all_results('sync_queue');
        $conflicts = $this->db->where('resolution', 'PENDING')->count_all_results('sync_conflicts');
        $last_sync = $this->db->select('last_sync_at')->where('device_id', $device_id)->get('sync_devices')->row();
        
        return [
            'pending_sync' => $pending,
            'conflicts' => $conflicts,
            'last_sync' => $last_sync->last_sync_at ?? null
        ];
    }
    
    private function get_primary_key($table) {
        $keys = [
            'student' => 'student_id',
            'class' => 'class_id',
            'subject' => 'subject_id',
            'teacher' => 'teacher_id',
            'admin' => 'admin_id',
            'attendance' => 'attendance_id',
            'invoice' => 'invoice_id',
            'payment' => 'payment_id'
        ];
        
        return $keys[$table] ?? 'id';
    }
}
