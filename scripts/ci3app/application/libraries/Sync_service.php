<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Service Library
 * 
 * Core service for handling bidirectional sync operations between
 * local and remote databases. Provides transaction support, conflict
 * detection, and comprehensive error handling.
 * 
 * @package    School Manager
 * @subpackage Libraries
 * @category   Sync
 * @author     School Manager Team
 * @version    2.0.0
 */
class Sync_service {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    protected $CI;
    
    /**
     * Maximum retry attempts for failed operations
     * @var int
     */
    protected $max_retries = 3;
    
    /**
     * Batch size for bulk operations
     * @var int
     */
    protected $batch_size = 100;
    
    /**
     * Last error message
     * @var string|null
     */
    protected $last_error;
    
    /**
     * Conflict resolver instance
     * @var object|null
     */
    protected $conflict_resolver;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model(['Sync_conflict_model', 'Sync_audit_log_model', 'Sync_metrics_model']);
        
        // Load config
        $this->max_retries = $this->CI->config->item('max_retry_attempts') ?: 3;
        $this->batch_size = $this->CI->config->item('default_batch_size') ?: 100;
    }
    
    /**
     * Register device with user
     * 
     * @param string $device_id Device identifier
     * @param string $device_name Device name
     * @param int $user_id User ID
     * @param string $user_type User type (admin, teacher, parent)
     * @return string Device ID
     */
    public function register_device($device_id, $device_name, $user_id, $user_type) {
        $data = [
            'device_id' => $device_id,
            'device_name' => $device_name,
            'user_id' => $user_id,
            'user_type' => $user_type,
            'status' => 'ACTIVE',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->CI->db->replace('sync_devices', $data);
        return $device_id;
    }
    
    /**
     * Push local changes to remote server
     * 
     * @param string $device_id Device identifier
     * @param array|null $batch_data Optional batch data to push
     * @return array Result with synced, conflicts, and failed counts
     */
    public function push_changes($device_id, $batch_data = null) {
        $result = [
            'synced' => 0,
            'conflicts' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Get pending items from queue if no batch provided
        if ($batch_data === null) {
            $queue = $this->get_pending_queue($device_id);
        } else {
            $queue = $batch_data;
        }
        
        if (empty($queue)) {
            return $result;
        }
        
        // Start transaction
        $this->CI->db->trans_start();
        
        try {
            foreach ($queue as $item) {
                $sync_result = $this->process_queue_item($item, $device_id);
                
                if ($sync_result['status'] === 'success') {
                    $result['synced']++;
                    $this->mark_queue_item_synced($item['id']);
                } elseif ($sync_result['status'] === 'conflict') {
                    $result['conflicts']++;
                    $this->handle_conflict($item, $sync_result['conflict_data']);
                } else {
                    $result['failed']++;
                    $result['errors'][] = $sync_result['error'];
                    $this->mark_queue_item_failed($item['id'], $sync_result['error']);
                }
            }
            
            $this->CI->db->trans_complete();
            
            if ($this->CI->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed during push operation');
            }
            
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            $this->last_error = $e->getMessage();
            log_message('error', '[Sync Service] Push failed: ' . $e->getMessage());
            
            $result['failed'] = count($queue);
            $result['errors'][] = $e->getMessage();
        }
        
        // Update device last sync time
        $this->update_device_sync_time($device_id);
        
        // Record metrics
        $this->CI->Sync_metrics_model->record_synced($result['synced']);
        if ($result['conflicts'] > 0) {
            $this->CI->Sync_metrics_model->record_conflicts_detected($result['conflicts']);
        }
        if ($result['failed'] > 0) {
            $this->CI->Sync_metrics_model->record_failed($result['failed']);
        }
        
        return $result;
    }
    
    /**
     * Pull changes from remote server
     * 
     * @param string $device_id Device identifier
     * @param string $last_sync Last sync timestamp
     * @param array|null $tables Optional list of tables to sync
     * @return array Changes grouped by table
     */
    public function pull_changes($device_id, $last_sync, $tables = null) {
        $changes = [];
        
        // Get tables to sync
        if ($tables === null) {
            $tables = $this->get_syncable_tables();
        }
        
        foreach ($tables as $table) {
            try {
                $table_changes = $this->get_table_changes($table, $device_id, $last_sync);
                
                if (!empty($table_changes)) {
                    $changes[$table] = $table_changes;
                }
            } catch (Exception $e) {
                log_message('error', "[Sync Service] Pull failed for table {$table}: " . $e->getMessage());
            }
        }
        
        return $changes;
    }
    
    /**
     * Get pending items from sync queue
     * 
     * @param string $device_id Device identifier
     * @return array Queue items
     */
    protected function get_pending_queue($device_id) {
        return $this->CI->db->where('synced', FALSE)
                           ->where('device_id', $device_id)
                           ->where('retry_count <', $this->max_retries)
                           ->order_by('created_at', 'ASC')
                           ->limit($this->batch_size)
                           ->get('sync_queue')
                           ->result_array();
    }
    
    /**
     * Process a single queue item
     * 
     * @param array $item Queue item
     * @param string $device_id Device identifier
     * @return array Processing result
     */
    protected function process_queue_item($item, $device_id) {
        $data = json_decode($item['data'], TRUE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'error' => 'Invalid JSON data in queue item'
            ];
        }
        
        // Check for conflicts on UPDATE operations
        if ($item['operation'] === 'UPDATE') {
            $conflict = $this->detect_conflict($item['table_name'], $item['record_id'], $data, $device_id);
            
            if ($conflict) {
                return [
                    'status' => 'conflict',
                    'conflict_data' => $conflict
                ];
            }
            
            // Increment version
            if (isset($data['version'])) {
                $data['version'] = $data['version'] + 1;
            }
        }
        
        // Apply the change
        try {
            $this->apply_change($item['table_name'], $item['record_id'], $item['operation'], $data);
            
            // Log audit - optional if audit logger is available
            if (isset($this->CI->audit_logger) && is_object($this->CI->audit_logger) && method_exists($this->CI->audit_logger, 'log_operation')) {
                try {
                    $this->CI->audit_logger->log_operation($item, 'success');
                } catch (Exception $e) {
                    // Audit logging failed but don't break the sync operation
                    log_message('error', 'Audit logging failed: ' . $e->getMessage());
                }
            }
            
            return ['status' => 'success'];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Apply a change to the database
     * 
     * @param string $table Table name
     * @param int $record_id Record ID
     * @param string $operation Operation type
     * @param array $data Record data
     */
    protected function apply_change($table, $record_id, $operation, $data) {
        $primary_key = $this->get_primary_key($table);
        
        switch ($operation) {
            case 'INSERT':
                $this->CI->db->insert($table, $data);
                break;
                
            case 'UPDATE':
                $this->CI->db->where($primary_key, $record_id)
                            ->update($table, $data);
                break;
                
            case 'DELETE':
                // Check for soft delete
                $fields = $this->CI->db->list_fields($table);
                if (in_array('deleted_at', $fields)) {
                    $this->CI->db->where($primary_key, $record_id)
                                ->update($table, ['deleted_at' => date('Y-m-d H:i:s')]);
                } else {
                    $this->CI->db->where($primary_key, $record_id)
                                ->delete($table);
                }
                break;
                
            default:
                throw new Exception("Unknown operation: {$operation}");
        }
    }
    
    /**
     * Detect conflict between local and server versions
     * 
     * @param string $table Table name
     * @param int $record_id Record ID
     * @param array $local_data Local data
     * @param string $device_id Device identifier
     * @return array|null Conflict data or null if no conflict
     */
    protected function detect_conflict($table, $record_id, $local_data, $device_id) {
        $primary_key = $this->get_primary_key($table);
        
        $server_record = $this->CI->db->where($primary_key, $record_id)
                                      ->get($table)
                                      ->row_array();
        
        if (!$server_record) {
            return null; // No conflict if record doesn't exist
        }
        
        // Check if modified by different device after local modification
        $local_timestamp = strtotime($local_data['last_modified_at'] ?? 'now');
        $server_timestamp = strtotime($server_record['last_modified_at'] ?? 'now');
        
        $server_device = $server_record['device_id'] ?? null;
        
        // Conflict if server was modified more recently by a different device
        if ($server_timestamp > $local_timestamp && $server_device !== $device_id) {
            return [
                'table_name' => $table,
                'record_id' => $record_id,
                'local_data' => $local_data,
                'server_data' => $server_record,
                'local_timestamp' => $local_data['last_modified_at'] ?? date('c'),
                'server_timestamp' => $server_record['last_modified_at'] ?? date('c'),
                'local_device_id' => $device_id,
                'remote_device_id' => $server_device
            ];
        }
        
        // Check version-based conflict
        if (isset($local_data['version']) && isset($server_record['version'])) {
            if ($server_record['version'] > $local_data['version']) {
                return [
                    'table_name' => $table,
                    'record_id' => $record_id,
                    'local_data' => $local_data,
                    'server_data' => $server_record,
                    'local_version' => $local_data['version'],
                    'server_version' => $server_record['version'],
                    'local_device_id' => $device_id,
                    'remote_device_id' => $server_device
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Handle detected conflict
     * 
     * @param array $item Queue item
     * @param array $conflict_data Conflict data
     */
    protected function handle_conflict($item, $conflict_data) {
        // Get conflict strategy for this table
        $strategy = $this->get_table_conflict_strategy($item['table_name']);
        
        // Log the conflict
        $conflict_id = $this->CI->Sync_conflict_model->create_conflict([
            'table_name' => $item['table_name'],
            'record_id' => $item['record_id'],
            'local_device_id' => $conflict_data['local_device_id'],
            'remote_device_id' => $conflict_data['remote_device_id'],
            'local_version' => $conflict_data['local_version'] ?? 0,
            'remote_version' => $conflict_data['server_version'] ?? 0,
            'local_data' => $conflict_data['local_data'],
            'remote_data' => $conflict_data['server_data'],
            'local_modified_at' => $conflict_data['local_timestamp'] ?? date('Y-m-d H:i:s'),
            'remote_modified_at' => $conflict_data['server_timestamp'] ?? date('Y-m-d H:i:s'),
            'conflict_strategy' => $strategy
        ]);
        
        // If auto-resolve is enabled and strategy is not MANUAL_REVIEW
        if ($this->CI->config->item('auto_resolve_conflicts') && $strategy !== 'MANUAL_REVIEW') {
            $this->auto_resolve_conflict($conflict_id, $strategy, $conflict_data);
        }
    }
    
    /**
     * Auto-resolve a conflict based on strategy
     * 
     * @param int $conflict_id Conflict ID
     * @param string $strategy Resolution strategy
     * @param array $conflict_data Conflict data
     */
    protected function auto_resolve_conflict($conflict_id, $strategy, $conflict_data) {
        $resolution = null;
        $winning_data = null;
        
        switch ($strategy) {
            case 'REMOTE_WINS':
                $resolution = 'remote_wins';
                $winning_data = $conflict_data['server_data'];
                break;
                
            case 'LOCAL_WINS':
                $resolution = 'local_wins';
                $winning_data = $conflict_data['local_data'];
                break;
                
            case 'TIMESTAMP_WINS':
                $local_time = strtotime($conflict_data['local_timestamp']);
                $server_time = strtotime($conflict_data['server_timestamp']);
                
                if ($local_time >= $server_time) {
                    $resolution = 'local_wins';
                    $winning_data = $conflict_data['local_data'];
                } else {
                    $resolution = 'remote_wins';
                    $winning_data = $conflict_data['server_data'];
                }
                break;
                
            case 'VERSION_WINS':
                $local_version = $conflict_data['local_version'] ?? 0;
                $server_version = $conflict_data['server_version'] ?? 0;
                
                if ($local_version >= $server_version) {
                    $resolution = 'local_wins';
                    $winning_data = $conflict_data['local_data'];
                } else {
                    $resolution = 'remote_wins';
                    $winning_data = $conflict_data['server_data'];
                }
                break;
                
            default:
                return; // Cannot auto-resolve
        }
        
        // Apply the winning data
        if ($winning_data) {
            $primary_key = $this->get_primary_key($conflict_data['table_name']);
            $this->CI->db->where($primary_key, $conflict_data['record_id'])
                        ->update($conflict_data['table_name'], $winning_data);
        }
        
        // Mark conflict as resolved
        $this->CI->Sync_conflict_model->resolve_conflict($conflict_id, $resolution, 0);
    }
    
    /**
     * Get conflict strategy for a table
     * 
     * @param string $table Table name
     * @return string Strategy name
     */
    protected function get_table_conflict_strategy($table) {
        $metadata = $this->CI->db->where('table_name', $table)
                                 ->get('sync_metadata')
                                 ->row();
        
        return $metadata->conflict_strategy ?? $this->CI->config->item('default_conflict_strategy') ?? 'TIMESTAMP_WINS';
    }
    
    /**
     * Get list of syncable tables
     * 
     * @return array Table names
     */
    protected function get_syncable_tables() {
        $result = $this->CI->db->select('table_name')
                               ->where('sync_enabled', TRUE)
                               ->order_by('priority', 'DESC')
                               ->get('sync_metadata')
                               ->result_array();
        
        return array_column($result, 'table_name');
    }
    
    /**
     * Get changes for a specific table since last sync
     * 
     * @param string $table Table name
     * @param string $device_id Device identifier
     * @param string $last_sync Last sync timestamp
     * @return array Changed records
     */
    protected function get_table_changes($table, $device_id, $last_sync) {
        // Check if table has sync columns
        $fields = $this->CI->db->list_fields($table);
        
        if (!in_array('last_modified_at', $fields)) {
            return [];
        }
        
        $query = $this->CI->db->where('last_modified_at >', $last_sync);
        
        // Exclude own changes
        if (in_array('device_id', $fields)) {
            $query->where('device_id !=', $device_id);
        }
        
        return $query->get($table)->result_array();
    }
    
    /**
     * Mark queue item as synced
     * 
     * @param int $id Queue item ID
     */
    protected function mark_queue_item_synced($id) {
        $this->CI->db->where('id', $id)
                    ->update('sync_queue', [
                        'synced' => TRUE,
                        'synced_at' => date('Y-m-d H:i:s')
                    ]);
    }
    
    /**
     * Mark queue item as failed
     * 
     * @param int $id Queue item ID
     * @param string $error Error message
     */
    protected function mark_queue_item_failed($id, $error) {
        $this->CI->db->where('id', $id)
                    ->set('retry_count', 'retry_count + 1', FALSE)
                    ->set('error_message', $error)
                    ->update('sync_queue');
    }
    
    /**
     * Update device last sync time
     * 
     * @param string $device_id Device identifier
     */
    protected function update_device_sync_time($device_id) {
        $this->CI->db->where('device_id', $device_id)
                    ->update('sync_devices', [
                        'last_sync_at' => date('Y-m-d H:i:s')
                    ]);
    }
    
    /**
     * Log sync operation
     * 
     * @param array $item Queue item
     * @param string $status Status
     */
    protected function log_operation($item, $status) {
        $this->CI->Sync_audit_log_model->log([
            'table_name' => $item['table_name'],
            'record_id' => $item['record_id'],
            'operation' => $item['operation'],
            'source_device_id' => $item['device_id'],
            'sync_direction' => 'push',
            'status' => $status
        ]);
    }
    
    /**
     * Get primary key for a table
     * 
     * @param string $table Table name
     * @return string Primary key column name
     */
    protected function get_primary_key($table) {
        // Known primary keys
        $keys = [
            'student' => 'student_id',
            'parent' => 'parent_id',
            'enroll' => 'enroll_id',
            'class' => 'class_id',
            'section' => 'section_id',
            'subject' => 'subject_id',
            'teacher' => 'teacher_id',
            'admin' => 'admin_id',
            'attendance' => 'attendance_id',
            'invoice' => 'invoice_id',
            'payment' => 'payment_id',
            'exam' => 'exam_id',
            'exam_marks' => 'exam_mark_id',
            'grade' => 'grade_id',
            'discount_profiles' => 'discount_profile_id',
            'discount_categories' => 'discount_category_id',
            'student_discount_assignments' => 'id',
            'discount_profile_rules' => 'id',
            'discount_rule_applications' => 'id',
            'daily_fee_wallet' => 'wallet_id',
            'daily_fee_transactions' => 'transaction_id'
        ];
        
        if (isset($keys[$table])) {
            return $keys[$table];
        }
        
        // Try to get from database
        $query = $this->CI->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");
        $result = $query->row_array();
        
        return $result ? $result['Column_name'] : 'id';
    }
    
    /**
     * Get last error message
     * 
     * @return string|null
     */
    public function get_last_error() {
        return $this->last_error;
    }
    
    /**
     * Queue a record for sync
     * 
     * @param string $table Table name
     * @param int $record_id Record ID
     * @param string $operation Operation type (INSERT, UPDATE, DELETE)
     * @param array $data Record data
     * @param string $device_id Device identifier
     * @return int Queue item ID
     */
    public function queue_record($table, $record_id, $operation, $data, $device_id) {
        $this->CI->db->insert('sync_queue', [
            'device_id' => $device_id,
            'table_name' => $table,
            'record_id' => $record_id,
            'operation' => strtoupper($operation),
            'data' => json_encode($data),
            'created_at' => date('Y-m-d H:i:s'),
            'synced' => FALSE
        ]);
        
        return $this->CI->db->insert_id();
    }
    
    /**
     * Get pending count for a device
     * 
     * @param string $device_id Device identifier
     * @return int Pending count
     */
    public function get_pending_count($device_id) {
        return $this->CI->db->where('device_id', $device_id)
                           ->where('synced', FALSE)
                           ->count_all_results('sync_queue');
    }
}
