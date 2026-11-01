<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custom Controller with Automatic Sync Tracking
 * 
 * Extends CodeIgniter's controller to wrap database update operations
 * with automatic sync column injection. This provides transparent sync
 * tracking without modifying core files or requiring custom database drivers.
 * 
 * @package    School Manager
 * @subpackage Core
 * @category   Controllers
 * @author     School Manager Team
 * @version    1.0.0
 */
class MY_Controller extends CI_Controller {
    
    // PHP 8.2+ Fix: Explicitly declare CodeIgniter's dynamic properties to prevent deprecation warnings
    public $benchmark;
    public $hooks;
    public $config;
    public $log;
    public $utf8;
    public $uri;
    public $exceptions;
    public $router;
    public $output;
    public $security;
    public $input;
    public $lang;
    public $load;
    public $session;
    public $db;
    
    // Common libraries loaded dynamically
    public $pagination;
    public $xmlrpc;
    public $form_validation;
    public $email;
    public $upload;
    public $encryption;
    
    // Common models loaded dynamically
    public $email_model;
    public $crud_model;
    public $invoice_model;
    public $barcode_model;
    public $sms_model;
    public $frontend_model;
    public $financial_report_model;
    public $momo_model;
    public $boarding_model;
    public $audit_log;
    public $payroll_model;
    public $Finance_model;
    public $Accounts_model;
    public $Daily_fee_model;
    public $Daily_fee_discount_model;
    public $Discount_model;
    public $Notification_model;
    public $Payment_gateway_model;
    public $Setting_model;
    public $Sync_model;
    public $Transport_model;
    public $Login_model;
    public $Inventory_model;
    
    // Common libraries/helpers
    public $payroll_validator;
    public $tax_calculator;
    
    /**
     * Cache of tables with sync columns
     * @var array|null
     */
    private static $sync_enabled_tables = null;
    
    /**
     * Device ID for this local server
     * @var string|null
     */
    private static $device_id = null;
    
    /**
     * Sync wrapper enabled flag
     * Override this in child controllers that don't need sync
     */
    protected $sync_enabled = true;
    
    /**
     * Constructor
     * 
     * Wraps the database object to intercept update operations
     */
    public function __construct() {
        parent::__construct();
        
        // Only wrap database methods if sync is enabled for this controller
        // This prevents overhead on read-only or public-facing controllers
        if ($this->sync_enabled && isset($this->db)) {
            $this->wrap_database_methods();
        }
    }
    
    /**
     * Wrap database update methods to inject sync columns
     * 
     * @return void
     */
    private function wrap_database_methods() {
        // Store original db object
        $original_db = $this->db;
        
        // Create wrapper object
        $wrapper = new Sync_DB_Wrapper($original_db);
        
        // Replace db with wrapper
        $this->db = $wrapper;
        
        // Log wrapper initialization
        log_message('info', 'MY_Controller: Database wrapper initialized for ' . get_class($this));
    }
    
    /**
     * Get device ID from configuration or database
     * 
     * @return string Device ID
     */
    public static function get_device_id() {
        // Return cached value if available
        if (self::$device_id !== null) {
            return self::$device_id;
        }
        
        $CI =& get_instance();
        
        // Try to get from config first
        $device_id = $CI->config->item('device_id', 'sync');
        
        // If not in config, try database settings table
        if (empty($device_id)) {
            try {
                $setting = $CI->db->get_where('settings', ['type' => 'device_id'])->row();
                if ($setting) {
                    $device_id = $setting->description;
                }
            } catch (Exception $e) {
                log_message('error', 'MY_Controller: Failed to get device_id from database - ' . $e->getMessage());
            }
        }
        
        // Use default if still empty
        if (empty($device_id)) {
            $device_id = 'local-server-001';
        }
        
        // Cache the value
        self::$device_id = $device_id;
        
        return $device_id;
    }
    
    /**
     * Get current user ID from session
     * 
     * @return int|null User ID or NULL
     */
    public static function get_current_user_id() {
        $CI =& get_instance();
        
        // Check if session is loaded
        if (!isset($CI->session)) {
            return null;
        }
        
        // Try admin ID
        $user_id = $CI->session->userdata('admin_id');
        if ($user_id && is_numeric($user_id) && $user_id > 0) {
            return (int)$user_id;
        }
        
        // Try teacher ID
        $user_id = $CI->session->userdata('teacher_id');
        if ($user_id && is_numeric($user_id) && $user_id > 0) {
            return (int)$user_id;
        }
        
        // Try parent ID
        $user_id = $CI->session->userdata('parent_id');
        if ($user_id && is_numeric($user_id) && $user_id > 0) {
            return (int)$user_id;
        }
        
        return null;
    }
    
    /**
     * Build cache of sync-enabled tables
     * 
     * @return void
     */
    public static function build_sync_table_cache() {
        $CI =& get_instance();
        
        // Initialize cache
        self::$sync_enabled_tables = [];
        
        try {
            // Query to find tables with all 5 sync columns
            $query = "
                SELECT table_name
                FROM (
                    SELECT 
                        table_name, 
                        SUM(CASE WHEN column_name = 'sync_status' THEN 1 ELSE 0 END) as has_sync_status,
                        SUM(CASE WHEN column_name = 'last_modified_at' THEN 1 ELSE 0 END) as has_last_modified_at,
                        SUM(CASE WHEN column_name = 'device_id' THEN 1 ELSE 0 END) as has_device_id,
                        SUM(CASE WHEN column_name = 'last_modified_by' THEN 1 ELSE 0 END) as has_last_modified_by,
                        SUM(CASE WHEN column_name = 'version' THEN 1 ELSE 0 END) as has_version
                    FROM information_schema.columns
                    WHERE table_schema = DATABASE()
                      AND column_name IN ('sync_status', 'last_modified_at', 'device_id', 'last_modified_by', 'version')
                    GROUP BY table_name
                ) as sync_check
                WHERE has_sync_status = 1 
                  AND has_last_modified_at = 1 
                  AND has_device_id = 1 
                  AND has_last_modified_by = 1 
                  AND has_version = 1
            ";
            
            $result = $CI->db->query($query);
            
            if ($result && $result->num_rows() > 0) {
                foreach ($result->result() as $row) {
                    self::$sync_enabled_tables[$row->table_name] = true;
                }
                
                log_message('info', 'MY_Controller: Sync table cache built - ' . 
                           count(self::$sync_enabled_tables) . ' tables with sync columns');
            }
        } catch (Exception $e) {
            log_message('error', 'MY_Controller: Failed to build sync table cache - ' . $e->getMessage());
            self::$sync_enabled_tables = [];
        }
    }
    
    /**
     * Check if table has sync columns
     * 
     * @param string $table Table name
     * @return bool True if table has sync columns
     */
    public static function has_sync_columns($table) {
        // Build cache if not already built
        if (self::$sync_enabled_tables === null) {
            self::build_sync_table_cache();
        }
        
        // Check cache
        return isset(self::$sync_enabled_tables[$table]) && self::$sync_enabled_tables[$table] === true;
    }
}

/**
 * Database Wrapper for Sync Column Injection
 * 
 * Wraps the CodeIgniter database object to intercept update operations
 * and automatically inject sync tracking columns.
 */
class Sync_DB_Wrapper {
    
    /**
     * Original database object
     * @var CI_DB_query_builder
     */
    private $db;
    
    /**
     * Constructor
     * 
     * @param CI_DB_query_builder $db Original database object
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Magic method to forward all calls to original database object
     * 
     * @param string $method Method name
     * @param array $args Method arguments
     * @return mixed Method result
     */
    public function __call($method, $args) {
        // Intercept update() calls
        if ($method === 'update') {
            return $this->update_with_sync(...$args);
        }
        
        // Intercept update_batch() calls
        if ($method === 'update_batch') {
            return $this->update_batch_with_sync(...$args);
        }
        
        // Forward all other calls to original database object
        return call_user_func_array([$this->db, $method], $args);
    }
    
    /**
     * Magic method to forward property access to original database object
     * 
     * @param string $name Property name
     * @return mixed Property value
     */
    public function __get($name) {
        return $this->db->$name;
    }
    
    /**
     * Magic method to forward property setting to original database object
     * 
     * @param string $name Property name
     * @param mixed $value Property value
     * @return void
     */
    public function __set($name, $value) {
        $this->db->$name = $value;
    }
    
    /**
     * Update with automatic sync column injection
     * 
     * @param string $table Table name
     * @param array $set Update data
     * @param mixed $where WHERE conditions
     * @param int $limit Limit
     * @return bool Success status
     */
    private function update_with_sync($table = '', $set = NULL, $where = NULL, $limit = NULL) {
        try {
            // Extract table name
            $target_table = '';
            if (!empty($table)) {
                $target_table = $table;
            } elseif (!empty($this->db->qb_from) && is_array($this->db->qb_from)) {
                $target_table = $this->db->qb_from[0];
            }
            
            // If we can't determine the table or table doesn't have sync columns, pass through
            if (empty($target_table) || !MY_Controller::has_sync_columns($target_table)) {
                return $this->db->update($table, $set, $where, $limit);
            }
            
            // Table has sync columns - inject them
            log_message('debug', 'Sync_DB_Wrapper: Injecting sync columns for table "' . $target_table . '"');
            
            // If $set is provided, inject sync columns into it
            if ($set !== NULL && is_array($set)) {
                $set['sync_status'] = 'PENDING';
                $set['last_modified_at'] = date('Y-m-d H:i:s');
                $set['device_id'] = MY_Controller::get_device_id();
                $set['last_modified_by'] = MY_Controller::get_current_user_id();
            }
            
            // Handle version column as SQL expression
            $this->db->set('version', 'version + 1', FALSE);
            
            // Call original update
            $result = $this->db->update($table, $set, $where, $limit);
            
            log_message('debug', 'Sync_DB_Wrapper: Update completed for table "' . $target_table . '" with sync columns injected');
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Sync_DB_Wrapper: Sync injection failed - ' . $e->getMessage());
            
            // Try to execute the update without sync injection
            try {
                return $this->db->update($table, $set, $where, $limit);
            } catch (Exception $parent_e) {
                throw $parent_e;
            }
        }
    }
    
    /**
     * Batch update with automatic sync column injection
     * 
     * @param string $table Table name
     * @param array $set Batch data
     * @param string $index Key column
     * @param int $batch_size Batch size
     * @return int|bool Number of rows updated or FALSE
     */
    private function update_batch_with_sync($table = '', $set = NULL, $index = NULL, $batch_size = 100) {
        try {
            // Validate parameters
            if (empty($table) || $set === NULL || !is_array($set) || empty($set) || empty($index)) {
                return $this->db->update_batch($table, $set, $index, $batch_size);
            }
            
            // Check if table has sync columns
            if (!MY_Controller::has_sync_columns($table)) {
                return $this->db->update_batch($table, $set, $index, $batch_size);
            }
            
            // Table has sync columns - inject them
            log_message('debug', 'Sync_DB_Wrapper: Injecting sync columns for batch update on table "' . $table . '" (' . count($set) . ' records)');
            
            // Get sync data once
            $sync_data = [
                'sync_status' => 'PENDING',
                'last_modified_at' => date('Y-m-d H:i:s'),
                'device_id' => MY_Controller::get_device_id(),
                'last_modified_by' => MY_Controller::get_current_user_id()
            ];
            
            // Fetch current version values
            $index_values = array_column($set, $index);
            $version_map = [];
            
            if (!empty($index_values)) {
                try {
                    $this->db->where_in($index, $index_values);
                    $version_query = $this->db->select($index . ', version')->get($table);
                    
                    if ($version_query && $version_query->num_rows() > 0) {
                        foreach ($version_query->result_array() as $row) {
                            $version_map[$row[$index]] = isset($row['version']) ? (int)$row['version'] : 0;
                        }
                    }
                } catch (Exception $e) {
                    log_message('warning', 'Sync_DB_Wrapper: Failed to fetch version values - ' . $e->getMessage());
                }
            }
            
            // Inject sync columns into each batch record
            foreach ($set as &$record) {
                $record = array_merge($record, $sync_data);
                
                // Increment version
                if (isset($record[$index]) && isset($version_map[$record[$index]])) {
                    $record['version'] = $version_map[$record[$index]] + 1;
                } else {
                    $record['version'] = 1;
                }
            }
            unset($record);
            
            // Call original update_batch
            $result = $this->db->update_batch($table, $set, $index, $batch_size);
            
            log_message('debug', 'Sync_DB_Wrapper: Batch update completed for table "' . $table . '" with sync columns injected');
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Sync_DB_Wrapper: Sync injection failed for batch update - ' . $e->getMessage());
            
            try {
                return $this->db->update_batch($table, $set, $index, $batch_size);
            } catch (Exception $parent_e) {
                throw $parent_e;
            }
        }
    }
}
