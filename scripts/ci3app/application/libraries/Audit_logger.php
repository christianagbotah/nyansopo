<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit Logger Library
 * 
 * Records all sync operations with before/after states for compliance
 * and revert capabilities. Provides comprehensive audit trail for all
 * sync activities across multiple locations.
 * 
 * @package    School Manager
 * @subpackage Libraries
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: 9, 10
 */
class Audit_logger {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    private $CI;
    
    /**
     * Device ID for this local server
     * @var string
     */
    private $device_id;
    
    // Operation types
    const OP_INSERT = 'INSERT';
    const OP_UPDATE = 'UPDATE';
    const OP_DELETE = 'DELETE';
    const OP_CONFLICT = 'CONFLICT';
    const OP_REVERT = 'REVERT';
    
    // Sync directions
    const DIR_PUSH = 'push';
    const DIR_PULL = 'pull';
    
    // Status types
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CONFLICT = 'conflict';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Get device ID from settings
        $setting = $this->CI->db->get_where('settings', ['type' => 'device_id'])->row();
        $this->device_id = $setting ? $setting->description : 'local-server-001';
    }
    
    /**
     * Log a sync operation
     * 
     * @param string $table Table name
     * @param int $record_id Record ID
     * @param string $operation Operation type (INSERT, UPDATE, DELETE, CONFLICT, REVERT)
     * @param string $source_device_id Source device ID
     * @param string|null $target_device_id Target device ID
     * @param array|null $old_value Old record state
     * @param array|null $new_value New record state
     * @param string $direction Sync direction (push or pull)
     * @param int|null $synced_by User ID who triggered sync
     * @param int $duration_ms Duration in milliseconds
     * @param string $status Status (success, failed, conflict)
     * @param string|null $error_message Error message if failed
     * @return int Audit log ID
     */
    public function log(
        $table,
        $record_id,
        $operation,
        $source_device_id,
        $target_device_id = null,
        $old_value = null,
        $new_value = null,
        $direction = self::DIR_PUSH,
        $synced_by = null,
        $duration_ms = 0,
        $status = self::STATUS_SUCCESS,
        $error_message = null
    ) {
        try {
            $data = [
                'table_name' => $table,
                'record_id' => $record_id,
                'operation' => $operation,
                'source_device_id' => $source_device_id,
                'target_device_id' => $target_device_id,
                'old_value' => $old_value ? json_encode($old_value) : null,
                'new_value' => $new_value ? json_encode($new_value) : null,
                'sync_direction' => $direction,
                'synced_by' => $synced_by,
                'duration_ms' => $duration_ms,
                'status' => $status,
                'error_message' => $error_message,
                'synced_at' => date('Y-m-d H:i:s')
            ];
            
            $this->CI->db->insert('sync_audit_log', $data);
            $audit_id = $this->CI->db->insert_id();
            
            log_message('info', "[Audit Logger] Logged $operation on $table record $record_id (audit_id: $audit_id)");
            
            return $audit_id;
            
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Failed to log audit entry: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get audit log entries with filtering
     * 
     * @param array $filters Filters: table_name, record_id, operation, device_id, date_from, date_to, status
     * @param int $limit Limit
     * @param int $offset Offset
     * @param string $order_by Order by field
     * @param string $order_dir Order direction (ASC or DESC)
     * @return array Audit log entries
     */
    public function get_logs($filters = [], $limit = 100, $offset = 0, $order_by = 'synced_at', $order_dir = 'DESC') {
        try {
            // Apply filters
            if (isset($filters['table_name']) && !empty($filters['table_name'])) {
                $this->CI->db->where('table_name', $filters['table_name']);
            }
            
            if (isset($filters['record_id']) && !empty($filters['record_id'])) {
                $this->CI->db->where('record_id', $filters['record_id']);
            }
            
            if (isset($filters['operation']) && !empty($filters['operation'])) {
                $this->CI->db->where('operation', $filters['operation']);
            }
            
            if (isset($filters['device_id']) && !empty($filters['device_id'])) {
                $this->CI->db->group_start();
                $this->CI->db->where('source_device_id', $filters['device_id']);
                $this->CI->db->or_where('target_device_id', $filters['device_id']);
                $this->CI->db->group_end();
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $this->CI->db->where('synced_at >=', $filters['date_from']);
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $this->CI->db->where('synced_at <=', $filters['date_to']);
            }
            
            if (isset($filters['status']) && !empty($filters['status'])) {
                $this->CI->db->where('status', $filters['status']);
            }
            
            if (isset($filters['sync_direction']) && !empty($filters['sync_direction'])) {
                $this->CI->db->where('sync_direction', $filters['sync_direction']);
            }
            
            // Apply ordering
            $this->CI->db->order_by($order_by, $order_dir);
            
            // Apply limit and offset
            $this->CI->db->limit($limit, $offset);
            
            // Get results
            $query = $this->CI->db->get('sync_audit_log');
            return $query->result_array();
            
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Error getting logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audit log count with filtering
     * 
     * @param array $filters Filters
     * @return int Count
     */
    public function count_logs($filters = []) {
        try {
            // Apply same filters as get_logs
            if (isset($filters['table_name']) && !empty($filters['table_name'])) {
                $this->CI->db->where('table_name', $filters['table_name']);
            }
            
            if (isset($filters['record_id']) && !empty($filters['record_id'])) {
                $this->CI->db->where('record_id', $filters['record_id']);
            }
            
            if (isset($filters['operation']) && !empty($filters['operation'])) {
                $this->CI->db->where('operation', $filters['operation']);
            }
            
            if (isset($filters['device_id']) && !empty($filters['device_id'])) {
                $this->CI->db->group_start();
                $this->CI->db->where('source_device_id', $filters['device_id']);
                $this->CI->db->or_where('target_device_id', $filters['device_id']);
                $this->CI->db->group_end();
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $this->CI->db->where('synced_at >=', $filters['date_from']);
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $this->CI->db->where('synced_at <=', $filters['date_to']);
            }
            
            if (isset($filters['status']) && !empty($filters['status'])) {
                $this->CI->db->where('status', $filters['status']);
            }
            
            if (isset($filters['sync_direction']) && !empty($filters['sync_direction'])) {
                $this->CI->db->where('sync_direction', $filters['sync_direction']);
            }
            
            return $this->CI->db->count_all_results('sync_audit_log');
            
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Error counting logs: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get audit log entry by ID
     * 
     * @param int $audit_log_id Audit log ID
     * @return object|null Audit log entry
     */
    public function get_by_id($audit_log_id) {
        try {
            return $this->CI->db->get_where('sync_audit_log', ['id' => $audit_log_id])->row();
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Error getting audit log by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get audit history for a specific record
     * 
     * @param string $table Table name
     * @param int $record_id Record ID
     * @param int $limit Limit
     * @return array Audit log entries
     */
    public function get_by_record($table, $record_id, $limit = 50) {
        try {
            $query = $this->CI->db->where('table_name', $table)
                                 ->where('record_id', $record_id)
                                 ->order_by('synced_at', 'DESC')
                                 ->limit($limit)
                                 ->get('sync_audit_log');
            
            return $query->result_array();
            
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Error getting record history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Revert a record to its previous state
     * 
     * @param int $audit_log_id Audit log ID to revert
     * @param int $reverted_by User ID performing revert
     * @return array Result with status and message
     */
    public function revert($audit_log_id, $reverted_by) {
        try {
            // Get audit log entry
            $audit_entry = $this->get_by_id($audit_log_id);
            
            if (!$audit_entry) {
                return [
                    'status' => 'error',
                    'message' => 'Audit log entry not found'
                ];
            }
            
            // Only UPDATE and DELETE operations can be reverted
            if (!in_array($audit_entry->operation, [self::OP_UPDATE, self::OP_DELETE])) {
                return [
                    'status' => 'error',
                    'message' => 'Only UPDATE and DELETE operations can be reverted'
                ];
            }
            
            // Get old value
            $old_value = json_decode($audit_entry->old_value, true);
            
            if (!$old_value) {
                return [
                    'status' => 'error',
                    'message' => 'No previous state available to revert to'
                ];
            }
            
            // Get primary key
            $primary_key = $this->get_primary_key($audit_entry->table_name);
            
            if (!$primary_key) {
                return [
                    'status' => 'error',
                    'message' => 'Could not determine primary key for table'
                ];
            }
            
            // Check referential integrity
            $integrity_check = $this->check_referential_integrity($audit_entry->table_name, $old_value);
            
            if (!$integrity_check['valid']) {
                return [
                    'status' => 'error',
                    'message' => 'Revert would violate referential integrity: ' . $integrity_check['message']
                ];
            }
            
            // Perform revert
            $this->CI->db->trans_start();
            
            // Restore old values
            $restore_data = $old_value;
            $restore_data['sync_status'] = 'PENDING'; // Mark for sync
            $restore_data['last_modified_at'] = date('Y-m-d H:i:s');
            $restore_data['last_modified_by'] = $reverted_by;
            
            $this->CI->db->where($primary_key, $audit_entry->record_id)
                        ->update($audit_entry->table_name, $restore_data);
            
            // Log the revert operation
            $this->log(
                $audit_entry->table_name,
                $audit_entry->record_id,
                self::OP_REVERT,
                $this->device_id,
                null,
                json_decode($audit_entry->new_value, true),
                $old_value,
                self::DIR_PUSH,
                $reverted_by,
                0,
                self::STATUS_SUCCESS,
                "Reverted from audit log ID: $audit_log_id"
            );
            
            $this->CI->db->trans_complete();
            
            if ($this->CI->db->trans_status() === FALSE) {
                return [
                    'status' => 'error',
                    'message' => 'Database transaction failed'
                ];
            }
            
            log_message('info', "[Audit Logger] Reverted {$audit_entry->table_name} record {$audit_entry->record_id}");
            
            return [
                'status' => 'success',
                'message' => 'Record reverted successfully',
                'reverted_to' => $old_value
            ];
            
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Error reverting record: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error reverting record: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check referential integrity before revert
     * 
     * @param string $table Table name
     * @param array $data Data to check
     * @return array Result with valid flag and message
     */
    private function check_referential_integrity($table, $data) {
        // Basic implementation - can be enhanced with actual FK checks
        // For now, just return valid
        return [
            'valid' => true,
            'message' => ''
        ];
    }
    
    /**
     * Get primary key column name for a table
     * 
     * @param string $table Table name
     * @return string|null Primary key column name
     */
    private function get_primary_key($table) {
        try {
            $query = $this->CI->db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
            $result = $query->row_array();
            return $result ? $result['Column_name'] : null;
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Error getting primary key for $table: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Clean up old audit logs based on retention policy
     * 
     * @param int $retention_days Number of days to retain logs
     * @return int Number of records deleted
     */
    public function cleanup_old_logs($retention_days = 90) {
        try {
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));
            
            $this->CI->db->where('synced_at <', $cutoff_date);
            $this->CI->db->delete('sync_audit_log');
            
            $deleted = $this->CI->db->affected_rows();
            
            log_message('info', "[Audit Logger] Cleaned up $deleted old audit log entries");
            
            return $deleted;
            
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Error cleaning up old logs: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Export audit logs for compliance reporting
     * 
     * @param array $filters Filters
     * @param string $format Format (csv, json)
     * @return string|array Exported data
     */
    public function export($filters = [], $format = 'csv') {
        try {
            // Get all matching logs (no limit)
            $logs = $this->get_logs($filters, 999999, 0);
            
            if ($format === 'json') {
                return json_encode($logs, JSON_PRETTY_PRINT);
            }
            
            if ($format === 'csv') {
                $csv = "ID,Table,Record ID,Operation,Source Device,Target Device,Direction,Status,Synced At,Duration (ms),Error\n";
                
                foreach ($logs as $log) {
                    $csv .= sprintf(
                        "%d,%s,%d,%s,%s,%s,%s,%s,%s,%d,%s\n",
                        $log['id'],
                        $log['table_name'],
                        $log['record_id'],
                        $log['operation'],
                        $log['source_device_id'],
                        $log['target_device_id'] ?? '',
                        $log['sync_direction'],
                        $log['status'],
                        $log['synced_at'],
                        $log['duration_ms'],
                        str_replace(["\n", "\r", ","], [" ", " ", ";"], $log['error_message'] ?? '')
                    );
                }
                
                return $csv;
            }
            
            return $logs;
            
        } catch (Exception $e) {
            log_message('error', "[Audit Logger] Error exporting logs: " . $e->getMessage());
            return [];
        }
    }
}
