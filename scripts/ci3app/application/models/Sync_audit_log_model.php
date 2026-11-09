<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Audit Log Model
 * 
 * Manages sync audit log records for comprehensive tracking of all sync operations.
 * Provides before/after states for compliance and revert capabilities.
 * 
 * @package    School Manager
 * @subpackage Models
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 9, 10 - Sync Audit Trail and Revert Capability
 */
class Sync_audit_log_model extends CI_Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'sync_audit_log';
    
    /**
     * Primary key
     * @var string
     */
    protected $primary_key = 'id';
    
    /**
     * Operation type constants
     */
    const OP_INSERT = 'INSERT';
    const OP_UPDATE = 'UPDATE';
    const OP_DELETE = 'DELETE';
    const OP_CONFLICT = 'CONFLICT';
    const OP_REVERT = 'REVERT';
    
    /**
     * Sync direction constants
     */
    const DIRECTION_PUSH = 'push';
    const DIRECTION_PULL = 'pull';
    
    /**
     * Status constants
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CONFLICT = 'conflict';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Log a sync operation
     * 
     * @param array $data Log data
     * @return int|bool Insert ID or false on failure
     */
    public function log($data) {
        $log_entry = [
            'table_name' => $data['table_name'],
            'record_id' => $data['record_id'],
            'operation' => $data['operation'],
            'source_device_id' => $data['source_device_id'],
            'target_device_id' => $data['target_device_id'] ?? null,
            'old_value' => isset($data['old_value']) ? json_encode($data['old_value']) : null,
            'new_value' => isset($data['new_value']) ? json_encode($data['new_value']) : null,
            'sync_direction' => $data['sync_direction'],
            'synced_by' => $data['synced_by'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? null,
            'status' => $data['status'] ?? self::STATUS_SUCCESS,
            'error_message' => $data['error_message'] ?? null,
            'synced_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert($log_entry);
    }
    
    /**
     * Insert log entry
     * 
     * @param array $data Log data
     * @return int|bool Insert ID or false on failure
     */
    public function insert($data) {
        $result = $this->db->insert($this->table, $data);
        
        if ($result) {
            return $this->db->insert_id();
        }
        
        return false;
    }
    
    /**
     * Get logs with filtering
     * 
     * @param array $filters Filters (table_name, record_id, operation, source_device_id, date_from, date_to)
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of log entries
     */
    public function get_logs($filters = [], $limit = 100, $offset = 0) {
        // Apply filters
        if (!empty($filters['table_name'])) {
            $this->db->where('table_name', $filters['table_name']);
        }
        
        if (!empty($filters['record_id'])) {
            $this->db->where('record_id', $filters['record_id']);
        }
        
        if (!empty($filters['operation'])) {
            $this->db->where('operation', $filters['operation']);
        }
        
        if (!empty($filters['source_device_id'])) {
            $this->db->where('source_device_id', $filters['source_device_id']);
        }
        
        if (!empty($filters['sync_direction'])) {
            $this->db->where('sync_direction', $filters['sync_direction']);
        }
        
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        
        if (!empty($filters['date_from'])) {
            $this->db->where('synced_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('synced_at <=', $filters['date_to']);
        }
        
        $this->db->order_by('synced_at', 'DESC');
        
        $logs = $this->db->get($this->table, $limit, $offset)->result_array();
        
        // Decode JSON fields
        foreach ($logs as &$log) {
            if ($log['old_value']) {
                $log['old_value'] = json_decode($log['old_value'], true);
            }
            if ($log['new_value']) {
                $log['new_value'] = json_decode($log['new_value'], true);
            }
        }
        
        return $logs;
    }
    
    /**
     * Get log entry by ID
     * 
     * @param int $id Log ID
     * @return object|null Log entry or null
     */
    public function get_log($id) {
        $log = $this->db->where($this->primary_key, $id)
                        ->get($this->table)
                        ->row();
        
        if ($log) {
            // Decode JSON fields
            if ($log->old_value) {
                $log->old_value = json_decode($log->old_value, true);
            }
            if ($log->new_value) {
                $log->new_value = json_decode($log->new_value, true);
            }
        }
        
        return $log;
    }
    
    /**
     * Get audit history for a specific record
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @param int $limit Limit
     * @return array Array of log entries
     */
    public function get_record_history($table_name, $record_id, $limit = 50) {
        return $this->get_logs([
            'table_name' => $table_name,
            'record_id' => $record_id
        ], $limit);
    }
    
    /**
     * Revert a record to its previous state
     * 
     * @param int $log_id Log ID of the operation to revert
     * @param int $reverted_by Admin ID performing the revert
     * @return array Result with success status and message
     */
    public function revert($log_id, $reverted_by) {
        $log = $this->get_log($log_id);
        
        if (!$log) {
            return ['success' => false, 'message' => 'Log entry not found'];
        }
        
        // Can only revert UPDATE and DELETE operations
        if (!in_array($log->operation, [self::OP_UPDATE, self::OP_DELETE])) {
            return ['success' => false, 'message' => 'Can only revert UPDATE and DELETE operations'];
        }
        
        if (empty($log->old_value)) {
            return ['success' => false, 'message' => 'No previous state available for revert'];
        }
        
        // Get the table's primary key
        $primary_key = $this->get_primary_key($log->table_name);
        
        if (!$primary_key) {
            return ['success' => false, 'message' => 'Could not determine primary key for table'];
        }
        
        // Start transaction
        $this->db->trans_start();
        
        try {
            // Restore the old value
            $old_value = $log->old_value;
            
            if ($log->operation === self::OP_DELETE) {
                // For DELETE, re-insert the old record
                $this->db->insert($log->table_name, $old_value);
            } else {
                // For UPDATE, restore the old values
                $this->db->where($primary_key, $log->record_id)
                         ->update($log->table_name, $old_value);
            }
            
            // Mark as PENDING for sync
            if ($this->table_has_sync_columns($log->table_name)) {
                $this->db->where($primary_key, $log->record_id)
                         ->update($log->table_name, [
                             'sync_status' => 'PENDING',
                             'last_modified_at' => date('Y-m-d H:i:s'),
                             'last_modified_by' => $reverted_by
                         ]);
            }
            
            // Log the revert operation
            $this->log([
                'table_name' => $log->table_name,
                'record_id' => $log->record_id,
                'operation' => self::OP_REVERT,
                'source_device_id' => $log->source_device_id,
                'old_value' => $log->new_value,
                'new_value' => $log->old_value,
                'sync_direction' => self::DIRECTION_PUSH,
                'synced_by' => $reverted_by,
                'status' => self::STATUS_SUCCESS
            ]);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'message' => 'Transaction failed'];
            }
            
            return [
                'success' => true,
                'message' => 'Record reverted successfully',
                'table_name' => $log->table_name,
                'record_id' => $log->record_id
            ];
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get primary key for a table
     * 
     * @param string $table Table name
     * @return string|null Primary key column name
     */
    private function get_primary_key($table) {
        $query = $this->db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        $result = $query->row_array();
        return $result ? $result['Column_name'] : null;
    }
    
    /**
     * Check if table has sync columns
     * 
     * @param string $table Table name
     * @return bool
     */
    private function table_has_sync_columns($table) {
        $fields = $this->db->list_fields($table);
        return in_array('sync_status', $fields);
    }
    
    /**
     * Get log statistics
     * 
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return array Statistics array
     */
    public function get_stats($date_from = null, $date_to = null) {
        $stats = [
            'total' => 0,
            'by_operation' => [],
            'by_status' => [],
            'by_direction' => [],
            'by_table' => []
        ];
        
        // Build base query
        $this->db->select('operation, status, sync_direction, table_name, COUNT(*) as count');
        
        if ($date_from) {
            $this->db->where('synced_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('synced_at <=', $date_to);
        }
        
        // Get aggregated stats
        $results = $this->db->group_by(['operation', 'status', 'sync_direction', 'table_name'])
                           ->get($this->table)
                           ->result_array();
        
        foreach ($results as $row) {
            $stats['total'] += $row['count'];
            
            // By operation
            if (!isset($stats['by_operation'][$row['operation']])) {
                $stats['by_operation'][$row['operation']] = 0;
            }
            $stats['by_operation'][$row['operation']] += $row['count'];
            
            // By status
            if (!isset($stats['by_status'][$row['status']])) {
                $stats['by_status'][$row['status']] = 0;
            }
            $stats['by_status'][$row['status']] += $row['count'];
            
            // By direction
            if (!isset($stats['by_direction'][$row['sync_direction']])) {
                $stats['by_direction'][$row['sync_direction']] = 0;
            }
            $stats['by_direction'][$row['sync_direction']] += $row['count'];
            
            // By table
            if (!isset($stats['by_table'][$row['table_name']])) {
                $stats['by_table'][$row['table_name']] = 0;
            }
            $stats['by_table'][$row['table_name']] += $row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Export logs for compliance reporting
     * 
     * @param array $filters Filters
     * @param string $format Export format (json, csv)
     * @return string Exported data
     */
    public function export_logs($filters = [], $format = 'json') {
        $logs = $this->get_logs($filters, 10000);
        
        if ($format === 'csv') {
            return $this->to_csv($logs);
        }
        
        return json_encode($logs, JSON_PRETTY_PRINT);
    }
    
    /**
     * Convert logs to CSV format
     * 
     * @param array $logs Log entries
     * @return string CSV data
     */
    private function to_csv($logs) {
        if (empty($logs)) {
            return '';
        }
        
        $headers = ['id', 'table_name', 'record_id', 'operation', 'source_device_id', 
                   'sync_direction', 'synced_at', 'synced_by', 'status', 'error_message'];
        
        $output = implode(',', $headers) . "\n";
        
        foreach ($logs as $log) {
            $row = [];
            foreach ($headers as $header) {
                $value = $log[$header] ?? '';
                // Escape quotes and wrap in quotes
                $value = str_replace('"', '""', $value);
                $row[] = '"' . $value . '"';
            }
            $output .= implode(',', $row) . "\n";
        }
        
        return $output;
    }
    
    /**
     * Clean up old logs (retention policy)
     * 
     * @param int $days Days to keep (default 90)
     * @return int Number of deleted records
     */
    public function cleanup_old_logs($days = 90) {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $this->db->where('synced_at <', $cutoff);
        $this->db->delete($this->table);
        
        return $this->db->affected_rows();
    }
}
