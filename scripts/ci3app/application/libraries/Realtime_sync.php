<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Real-time Sync Library
 * 
 * Handles immediate sync operations for critical tables like payments and invoices.
 * Provides instant synchronization instead of waiting for scheduled sync.
 * 
 * @package    School Manager
 * @subpackage Libraries
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 6 - Real-time Sync for Critical Tables
 */
class Realtime_sync {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    private $CI;
    
    /**
     * Queue for pending real-time sync operations
     * @var array
     */
    private $queue = [];
    
    /**
     * Maximum retry attempts
     */
    const MAX_RETRIES = 3;
    
    /**
     * Timeout for real-time sync (seconds)
     */
    const TIMEOUT = 5;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->library('Audit_logger');
    }
    
    /**
     * Queue a record for real-time sync
     * 
     * @param string $table Table name
     * @param int $record_id Record ID
     * @param array $data Record data
     * @param string $operation Operation type (insert, update, delete)
     * @return bool Success status
     */
    public function queue_sync($table, $record_id, $data, $operation = 'update') {
        // Check if real-time sync is enabled for this table
        if (!$this->is_realtime_enabled($table)) {
            return false;
        }
        
        $queue_item = [
            'table' => $table,
            'record_id' => $record_id,
            'data' => $data,
            'operation' => $operation,
            'queued_at' => date('Y-m-d H:i:s'),
            'device_id' => $this->get_device_id(),
            'retry_count' => 0
        ];
        
        // Store in database queue for reliability
        $this->store_in_queue($queue_item);
        
        // Attempt immediate sync
        return $this->process_immediate($queue_item);
    }
    
    /**
     * Check if real-time sync is enabled for a table
     * 
     * @param string $table Table name
     * @return bool True if enabled
     */
    public function is_realtime_enabled($table) {
        $metadata = $this->CI->db->get_where('sync_metadata', ['table_name' => $table])->row();
        
        return $metadata && !empty($metadata->real_time_sync);
    }
    
    /**
     * Process immediate sync
     * 
     * @param array $item Queue item
     * @return bool Success status
     */
    private function process_immediate($item) {
        $start_time = microtime(true);
        
        // Check internet connectivity
        if (!$this->is_online()) {
            // Will be processed by scheduled sync
            log_message('info', "Real-time sync queued (offline): {$item['table']} #{$item['record_id']}");
            return false;
        }
        
        try {
            // Get remote database connection
            $remote_db = $this->get_remote_db();
            
            if (!$remote_db) {
                throw new Exception('Cannot connect to remote database');
            }
            
            // Perform sync based on operation
            switch ($item['operation']) {
                case 'insert':
                case 'update':
                    $remote_db->replace($item['table'], $item['data']);
                    break;
                case 'delete':
                    $primary_key = $this->get_primary_key($item['table']);
                    $remote_db->where($primary_key, $item['record_id'])
                             ->delete($item['table']);
                    break;
            }
            
            // Mark as synced locally
            $this->mark_synced($item['table'], $item['record_id']);
            
            // Remove from queue
            $this->remove_from_queue($item);
            
            // Log the operation
            $duration = round((microtime(true) - $start_time) * 1000);
            $this->CI->audit_logger->log_push(
                $item['table'],
                $item['record_id'],
                $item['data'],
                $item['device_id'],
                $duration,
                'success'
            );
            
            log_message('info', "Real-time sync success: {$item['table']} #{$item['record_id']} ({$duration}ms)");
            
            return true;
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $start_time) * 1000);
            
            // Log failure
            $this->CI->audit_logger->log_push(
                $item['table'],
                $item['record_id'],
                $item['data'],
                $item['device_id'],
                $duration,
                'failed'
            );
            
            log_message('error', "Real-time sync failed: {$item['table']} #{$item['record_id']} - " . $e->getMessage());
            
            // Increment retry count
            $this->increment_retry($item);
            
            return false;
        }
    }
    
    /**
     * Process queued real-time sync operations
     * 
     * @param int $limit Maximum items to process
     * @return array Results
     */
    public function process_queue($limit = 100) {
        $queue = $this->get_pending_queue($limit);
        
        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0
        ];
        
        foreach ($queue as $item) {
            $results['processed']++;
            
            if ($this->process_immediate($item)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Store item in sync queue
     * 
     * @param array $item Queue item
     * @return int Queue ID
     */
    private function store_in_queue($item) {
        $this->CI->db->insert('sync_queue', [
            'table_name' => $item['table'],
            'record_id' => $item['record_id'],
            'operation' => strtoupper($item['operation']),
            'data' => json_encode($item['data']),
            'device_id' => $item['device_id'],
            'retry_count' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->CI->db->insert_id();
    }
    
    /**
     * Get pending queue items
     * 
     * @param int $limit Limit
     * @return array Queue items
     */
    private function get_pending_queue($limit = 100) {
        // Get tables with real-time sync enabled
        $realtime_tables = $this->CI->db->select('table_name')
                                        ->where('real_time_sync', TRUE)
                                        ->get('sync_metadata')
                                        ->result_array();
        
        $table_names = array_column($realtime_tables, 'table_name');
        
        if (empty($table_names)) {
            return [];
        }
        
        $this->CI->db->where_in('table_name', $table_names);
        $this->CI->db->where('retry_count <', self::MAX_RETRIES);
        $this->CI->db->order_by('created_at', 'ASC');
        
        $items = $this->CI->db->get('sync_queue', $limit)->result_array();
        
        // Decode JSON data
        foreach ($items as &$item) {
            $item['data'] = json_decode($item['data'], true);
        }
        
        return $items;
    }
    
    /**
     * Remove item from queue
     * 
     * @param array $item Queue item
     */
    private function remove_from_queue($item) {
        if (isset($item['id'])) {
            $this->CI->db->where('id', $item['id'])->delete('sync_queue');
        }
    }
    
    /**
     * Increment retry count
     * 
     * @param array $item Queue item
     */
    private function increment_retry($item) {
        if (isset($item['id'])) {
            $this->CI->db->where('id', $item['id'])
                       ->set('retry_count', 'retry_count + 1', false)
                       ->update('sync_queue');
        }
    }
    
    /**
     * Mark record as synced
     * 
     * @param string $table Table name
     * @param int $record_id Record ID
     */
    private function mark_synced($table, $record_id) {
        $primary_key = $this->get_primary_key($table);
        
        $this->CI->db->where($primary_key, $record_id)
                    ->update($table, [
                        'sync_status' => 'SYNCED',
                        'last_modified_at' => date('Y-m-d H:i:s')
                    ]);
    }
    
    /**
     * Check if online
     * 
     * @return bool True if online
     */
    private function is_online() {
        $host = $this->get_setting('remote_db_host');
        $port = $this->get_setting('remote_db_port', 3306);
        
        if ($host) {
            $connection = @fsockopen($host, $port, $errno, $errstr, 2);
            if ($connection) {
                fclose($connection);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get remote database connection
     * 
     * @return object|false Database connection
     */
    private function get_remote_db() {
        static $remote_db = null;
        
        if ($remote_db !== null) {
            return $remote_db;
        }
        
        $host = $this->get_setting('remote_db_host');
        $port = $this->get_setting('remote_db_port', 3306);
        $user = $this->get_setting('remote_db_user');
        $pass = $this->get_setting('remote_db_pass');
        // Decode password (Setup Wizard saves it as base64 encoded)
        if ($pass) {
            $pass = base64_decode($pass);
        }
        $dbname = $this->get_setting('remote_db_name');
        
        if (!$host || !$user || !$dbname) {
            return false;
        }
        
        $config = [
            'hostname' => $host . ':' . $port,
            'username' => $user,
            'password' => $pass,
            'database' => $dbname,
            'dbdriver' => 'mysqli',
            'pconnect' => FALSE,
            'db_debug' => FALSE,
            'cache_on' => FALSE,
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci'
        ];
        
        $remote_db = $this->CI->load->database($config, TRUE);
        
        return $remote_db;
    }
    
    /**
     * Get setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    private function get_setting($key, $default = null) {
        $setting = $this->CI->db->get_where('settings', ['type' => $key])->row();
        return $setting ? $setting->description : $default;
    }
    
    /**
     * Get device ID
     * 
     * @return string
     */
    private function get_device_id() {
        $setting = $this->CI->db->get_where('settings', ['type' => 'device_id'])->row();
        return $setting ? $setting->description : 'local-server-001';
    }
    
    /**
     * Get primary key for table
     * 
     * @param string $table Table name
     * @return string|null Primary key
     */
    private function get_primary_key($table) {
        static $cache = [];
        
        if (!isset($cache[$table])) {
            $query = $this->CI->db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
            $result = $query->row_array();
            $cache[$table] = $result ? $result['Column_name'] : null;
        }
        
        return $cache[$table];
    }
    
    /**
     * Get real-time sync status
     * 
     * @return array Status information
     */
    public function get_status() {
        $realtime_tables = $this->CI->db->select('table_name')
                                        ->where('real_time_sync', TRUE)
                                        ->get('sync_metadata')
                                        ->result_array();
        
        $table_names = array_column($realtime_tables, 'table_name');
        
        $pending_count = 0;
        if (!empty($table_names)) {
            $pending_count = $this->CI->db->where_in('table_name', $table_names)
                                         ->where('retry_count <', self::MAX_RETRIES)
                                         ->count_all_results('sync_queue');
        }
        
        return [
            'enabled' => !empty($table_names),
            'tables' => $table_names,
            'pending_count' => $pending_count,
            'online' => $this->is_online()
        ];
    }
}
