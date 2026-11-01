<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custom MySQLi Database Driver with Automatic Sync Tracking
 * 
 * Extends CodeIgniter's mysqli driver to automatically inject sync columns
 * (sync_status, last_modified_at, device_id, last_modified_by, version)
 * into UPDATE operations on tables that have these columns.
 * 
 * This driver is a drop-in replacement for CI_DB_mysqli_driver and requires
 * no changes to existing application code. It transparently intercepts UPDATE
 * operations and ensures all data modifications are properly tracked for
 * bidirectional synchronization.
 * 
 * @package    School Manager
 * @subpackage Database
 * @category   Drivers
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: 6.1, 6.2
 * Task: 1
 */
class MY_DB_mysqli_driver extends CI_DB_mysqli_driver {
    
    // ===== STATIC CACHE PROPERTIES (Request-Level Persistence) =====
    
    /**
     * Static flag to track if configuration has been loaded
     * @var bool
     */
    private static $config_loaded = false;
    
    /**
     * Static cache for master sync enabled flag
     * @var bool|null
     */
    private static $sync_master_enabled_cache = null;
    
    /**
     * Static cache for auto inject enabled flag
     * @var bool|null
     */
    private static $sync_injection_enabled_cache = null;
    
    /**
     * Static cache for device ID
     * @var string|null
     */
    private static $device_id_cache = null;
    
    /**
     * Static cache for sync-enabled tables
     * Structure: ['table_name' => true/false]
     * @var array|null
     */
    private static $sync_enabled_tables_static = null;
    
    /**
     * Static cache for primary key columns
     * Structure: ['table_name' => 'column_name']
     * @var array
     */
    private static $primary_key_cache_static = [];
    
    /**
     * Instrumentation: Counter for initialize_configuration() calls
     * Used for performance testing and verification
     * @var int
     */
    private static $init_call_count = 0;
    
    /**
     * Instrumentation: Counter for build_sync_table_cache() calls
     * Used for performance testing and verification
     * @var int
     */
    private static $cache_build_call_count = 0;
    
    // ===== INSTANCE PROPERTIES (Backward Compatibility) =====
    
    /**
     * Cache of tables with sync columns
     * 
     * Structure: ['table_name' => true/false]
     * NULL = not built yet
     * 
     * @var array|null
     */
    private $sync_enabled_tables = null;
    
    /**
     * Enable/disable automatic sync column injection
     * 
     * Can be disabled for testing or troubleshooting
     * 
     * @var bool
     */
    private $sync_injection_enabled = true;
    
    /**
     * Device ID for this local server
     * 
     * Loaded from configuration or database settings
     * 
     * @var string|null
     */
    private $device_id = null;
    
    /**
     * Cache of sync data for current request
     * 
     * Reused across multiple updates to avoid repeated computation
     * 
     * @var array|null
     */
    private $sync_data_cache = null;
    
    /**
     * Cache of primary key columns per table
     * 
     * Structure: ['table_name' => ['column1', 'column2', ...]]
     * 
     * @var array
     */
    private $primary_key_cache = [];
    
    /**
     * Deletion tracking configuration
     * 
     * @var array
     */
    private $deletion_tracking_config = null;
    
    /**
     * Constructor
     * 
     * Initializes the custom driver and loads configuration.
     * Calls parent constructor to maintain full compatibility.
     * 
     * @param array $params Database connection parameters
     */
    public function __construct($params) {
        // Call parent constructor first
        parent::__construct($params);
        
        // Fix MySQL 5.7+ ONLY_FULL_GROUP_BY mode to prevent GROUP BY errors
        try {
            $this->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            log_message('debug', 'MY_DB_mysqli_driver: SQL mode adjusted - ONLY_FULL_GROUP_BY removed');
        } catch (Exception $e) {
            log_message('warning', 'MY_DB_mysqli_driver: Could not adjust SQL mode - ' . $e->getMessage());
        }
        
        // Initialize configuration
        $this->initialize_configuration();
        
        // Log driver initialization
        log_message('info', 'MY_DB_mysqli_driver: Custom database driver initialized');
    }
    
    /**
     * Initialize configuration settings
     * 
     * Loads sync configuration and sets up driver behavior.
     * Uses fallback defaults if configuration is not available.
     * 
     * Priority order for offline_online_mode setting:
     * 1. Database settings table (offline_online_mode)
     * 2. Config file sync.php ($config['sync_enabled'])
     * 3. Default: TRUE (enabled)
     * 
     * Requirements: 8.1, 8.2
     * 
     * @return void
     */
    private function initialize_configuration() {
        // Instrumentation: Increment call counter
        self::$init_call_count++;
        
        // Check if configuration already loaded in this request (static cache check)
        if (self::$config_loaded) {
            // Reuse static cached values
            $this->sync_injection_enabled = self::$sync_injection_enabled_cache;
            $this->device_id = self::$device_id_cache;
            log_message('debug', 'MY_DB_mysqli_driver: Reusing cached configuration (call #' . self::$init_call_count . ')');
            return;
        }
        
        try {
            log_message('debug', 'MY_DB_mysqli_driver: Loading configuration...');
            
            // Get CodeIgniter instance
            $CI =& get_instance();
            
            // Load sync configuration
            $CI->config->load('sync', TRUE);
            
            // Check master sync enabled flag - PRIORITY ORDER:
            // 1. Try database settings table first (allows per-installation control)
            $sync_master_enabled = $this->get_offline_online_mode_from_db();
            
            // 2. Fall back to config file if database setting not available
            if ($sync_master_enabled === null) {
                $sync_master_enabled = $CI->config->item('sync_enabled', 'sync') ?? TRUE;
                log_message('debug', 'MY_DB_mysqli_driver: Using sync_enabled from config file: ' . 
                           ($sync_master_enabled ? 'TRUE' : 'FALSE'));
            } else {
                log_message('debug', 'MY_DB_mysqli_driver: Using sync_enabled from database: ' . 
                           ($sync_master_enabled ? 'TRUE' : 'FALSE'));
            }
            
            // If sync is disabled at master level, disable all sync features
            if (!$sync_master_enabled) {
                $this->sync_injection_enabled = FALSE;
                log_message('info', 'MY_DB_mysqli_driver: *** SYNC MODULE DISABLED BY MASTER SWITCH ***');
                
                // Cache the disabled state for future instances
                self::$sync_master_enabled_cache = $sync_master_enabled;
                self::$sync_injection_enabled_cache = $this->sync_injection_enabled;
                self::$device_id_cache = null;
                self::$config_loaded = TRUE;
                
                return;
            }
            
            // Sync is enabled - get sub-feature flags
            $this->sync_injection_enabled = $CI->config->item('auto_inject_sync_columns', 'sync') ?? TRUE;
            
            // Get device ID (will be loaded on first use)
            $this->device_id = null;
            
            log_message('debug', 'MY_DB_mysqli_driver: Configuration initialized - sync_injection_enabled=' . 
                       ($this->sync_injection_enabled ? 'true' : 'false'));
            
            // Cache configuration for future instances in this request
            self::$sync_master_enabled_cache = $sync_master_enabled;
            self::$sync_injection_enabled_cache = $this->sync_injection_enabled;
            self::$device_id_cache = $this->device_id;
            self::$config_loaded = TRUE;
            
        } catch (Exception $e) {
            // Log error but don't fail - use defaults
            log_message('error', 'MY_DB_mysqli_driver: Failed to load configuration - ' . $e->getMessage());
            $this->sync_injection_enabled = TRUE;
            // Don't set config_loaded flag - allow retry on next instance
        }
    }
    
    /**
     * Get offline_online_mode setting from database
     * 
     * Queries the settings table to check if SYNC INFRASTRUCTURE is enabled.
     * This is Level 1 control - controls whether sync infrastructure exists at all.
     * Returns null if setting not found (use config file fallback).
     * 
     * @return bool|null TRUE if enabled, FALSE if disabled, NULL if not found
     */
    private function get_offline_online_mode_from_db() {
        try {
            // Query settings table for offline_online_mode
            $query = $this->query("SELECT description FROM settings WHERE type = 'offline_online_mode' LIMIT 1");
            
            if ($query && $query->num_rows() > 0) {
                $row = $query->row();
                $value = $row->description;
                
                // Convert to boolean (1 = enabled, 0 = disabled)
                return ($value === '1' || $value === 1 || $value === true || $value === 'true');
            }
            
            // Setting not found - return null to use config file fallback
            return null;
            
        } catch (Exception $e) {
            // Database error - return null to use config file fallback
            log_message('warning', 'MY_DB_mysqli_driver: Could not read offline_online_mode from database - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get sync_enabled setting from database
     * 
     * Queries the settings table to check if AUTOMATIC SYNC is enabled.
     * This is Level 2 control - controls automatic vs manual sync behavior.
     * Only relevant when offline_online_mode = 1 (infrastructure enabled).
     * Returns null if setting not found (use config file fallback).
     * 
     * @return bool|null TRUE if enabled, FALSE if disabled, NULL if not found
     */
    private function get_sync_enabled_from_db() {
        try {
            // Query settings table for sync_enabled
            $query = $this->query("SELECT description FROM settings WHERE type = 'sync_enabled' LIMIT 1");
            
            if ($query && $query->num_rows() > 0) {
                $row = $query->row();
                $value = $row->description;
                
                // Convert to boolean (1 = enabled, 0 = disabled)
                return ($value === '1' || $value === 1 || $value === true || $value === 'true');
            }
            
            // Setting not found - return null to use config file fallback
            return null;
            
        } catch (Exception $e) {
            // Database error - return null to use config file fallback
            log_message('warning', 'MY_DB_mysqli_driver: Could not read sync_enabled from database - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get device ID from configuration or database
     * 
     * Retrieves the device ID that identifies this local server instance.
     * Tries configuration first, then database settings, then uses default.
     * Validates format for security.
     * Uses static cache to avoid repeated database queries across instances.
     * 
     * Requirements: 8.4
     * Task: 1.3
     * 
     * @return string Device ID
     */
    private function get_device_id() {
        // Check static cache first (shared across all instances)
        if (self::$device_id_cache !== null) {
            log_message('debug', 'MY_DB_mysqli_driver: Reusing cached device ID');
            return self::$device_id_cache;
        }
        
        // Check instance cache as fallback
        if ($this->device_id !== null) {
            return $this->device_id;
        }
        
        try {
            log_message('debug', 'MY_DB_mysqli_driver: Loading device ID...');
            
            // Get CodeIgniter instance
            $CI =& get_instance();
            
            // Try to get from config first
            $device_id = $CI->config->item('device_id', 'sync');
            
            // If not in config, try database settings table
            if (empty($device_id)) {
                $setting = $this->query("SELECT description FROM settings WHERE type = 'device_id' LIMIT 1");
                if ($setting && $setting->num_rows() > 0) {
                    $row = $setting->row();
                    $device_id = $row->description;
                }
            }
            
            // Use default if still empty
            if (empty($device_id)) {
                $device_id = 'local-server-001';
                log_message('warning', 'MY_DB_mysqli_driver: Device ID not configured, using default: ' . $device_id);
            }
            
            // Validate format (alphanumeric, hyphens, underscores, max 50 chars)
            if (!preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $device_id)) {
                log_message('warning', 'MY_DB_mysqli_driver: Invalid device_id format: ' . $device_id . ', using default');
                $device_id = 'local-server-001';
            }
            
            // Cache the value in both static and instance caches
            self::$device_id_cache = $device_id;
            $this->device_id = $device_id;
            
            log_message('debug', 'MY_DB_mysqli_driver: Device ID loaded: ' . $device_id);
            
            return $device_id;
            
        } catch (Exception $e) {
            log_message('error', 'MY_DB_mysqli_driver: Failed to get device_id - ' . $e->getMessage());
            $device_id = 'local-server-001';
            
            // Cache the fallback value in both caches
            self::$device_id_cache = $device_id;
            $this->device_id = $device_id;
            
            return $device_id;
        }
    }
    
    /**
     * Get current user ID from session
     * 
     * Retrieves the ID of the currently logged-in user from the session.
     * Tries admin_id, teacher_id, and parent_id in that order.
     * Returns NULL for system operations without user context.
     * 
     * Requirements: 8.5
     * Task: 4
     * 
     * @return int|null User ID or NULL
     */
    private function get_current_user_id() {
        try {
            // Get CodeIgniter instance
            $CI =& get_instance();
            
            // Check if session is loaded
            if (!isset($CI->session)) {
                return null;
            }
            
            // Try admin ID
            $user_id = $CI->session->userdata('admin_id');
            if ($user_id) {
                // Validate
                if (is_numeric($user_id) && $user_id > 0) {
                    return (int)$user_id;
                }
            }
            
            // Try teacher ID
            $user_id = $CI->session->userdata('teacher_id');
            if ($user_id) {
                // Validate
                if (is_numeric($user_id) && $user_id > 0) {
                    return (int)$user_id;
                }
            }
            
            // Try parent ID
            $user_id = $CI->session->userdata('parent_id');
            if ($user_id) {
                // Validate
                if (is_numeric($user_id) && $user_id > 0) {
                    return (int)$user_id;
                }
            }
            
            // No user in session (system operation)
            return null;
            
        } catch (Exception $e) {
            log_message('error', 'MY_DB_mysqli_driver: Failed to get user_id - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Build cache of sync-enabled tables
     * 
     * Queries the database schema to identify all tables that have all five
     * sync columns (sync_status, last_modified_at, device_id, last_modified_by, version).
     * Results are cached in static memory shared across all instances.
     * Uses static cache to avoid repeated database queries.
     * 
     * Requirements: 2.1, 2.2, 2.3, 2.4
     * Task: 2.1, 1.4
     * 
     * @return void
     */
    private function build_sync_table_cache() {
        // Instrumentation: Increment call counter
        self::$cache_build_call_count++;
        
        // Check static cache first (shared across all instances)
        if (self::$sync_enabled_tables_static !== null) {
            $this->sync_enabled_tables = self::$sync_enabled_tables_static;
            log_message('debug', 'MY_DB_mysqli_driver: Reusing static sync table cache - ' . 
                       count($this->sync_enabled_tables) . ' tables (call #' . self::$cache_build_call_count . ')');
            return;
        }
        
        try {
            log_message('debug', 'MY_DB_mysqli_driver: Building sync table cache...');
            
            // Initialize cache
            $this->sync_enabled_tables = [];
            
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
            
            $result = $this->query($query);
            
            if ($result && $result->num_rows() > 0) {
                foreach ($result->result() as $row) {
                    $this->sync_enabled_tables[$row->table_name] = true;
                }
                
                log_message('info', 'MY_DB_mysqli_driver: Sync table cache built - ' . 
                           count($this->sync_enabled_tables) . ' tables with sync columns');
            } else {
                log_message('warning', 'MY_DB_mysqli_driver: No tables with sync columns found');
            }
            
            // Cache in static storage for future instances
            self::$sync_enabled_tables_static = $this->sync_enabled_tables;
            
        } catch (Exception $e) {
            // Log error and treat all tables as non-sync (graceful degradation)
            log_message('error', 'MY_DB_mysqli_driver: Failed to build sync table cache - ' . $e->getMessage());
            $this->sync_enabled_tables = [];
            // Cache the empty array to avoid repeated failures
            self::$sync_enabled_tables_static = $this->sync_enabled_tables;
        }
    }
    
    /**
     * Check if table has sync columns
     * 
     * Checks the cache to determine if a table has all five sync columns.
     * Builds the cache on first call if not already built.
     * 
     * Requirements: 2.1, 2.2, 2.3
     * Task: 2.2
     * 
     * @param string $table Table name
     * @return bool True if table has sync columns
     */
    private function has_sync_columns($table) {
        // Check if static cache exists and populate instance property from it
        if ($this->sync_enabled_tables === null && self::$sync_enabled_tables_static !== null) {
            $this->sync_enabled_tables = self::$sync_enabled_tables_static;
            log_message('debug', 'MY_DB_mysqli_driver: Loaded sync table cache from static storage - ' . 
                       count($this->sync_enabled_tables) . ' tables');
        }
        
        // Build cache if not already built (neither instance nor static cache exists)
        if ($this->sync_enabled_tables === null) {
            $this->build_sync_table_cache();
        }
        
        // Check cache
        return isset($this->sync_enabled_tables[$table]) && $this->sync_enabled_tables[$table] === true;
    }
    
    /**
     * Refresh the sync-enabled table cache
     * 
     * Clears and rebuilds the cache of tables with sync columns.
     * Clears both static (shared) and instance caches.
     * Useful after schema changes (adding sync columns to tables).
     * 
     * Requirements: 2.5
     * Task: 2.3, 1.4
     * 
     * @return void
     */
    public function refresh_sync_cache() {
        log_message('info', 'MY_DB_mysqli_driver: Refreshing sync table cache...');
        
        // Clear both static and instance caches
        self::$sync_enabled_tables_static = null;
        $this->sync_enabled_tables = null;
        
        // Rebuild cache
        $this->build_sync_table_cache();
        
        log_message('info', 'MY_DB_mysqli_driver: Sync table cache refreshed');
    }
    
    /**
     * Get initialize_configuration() call count (for performance testing)
     * 
     * Returns the number of times initialize_configuration() has been called
     * during this request. Used for performance verification and testing.
     * 
     * Expected value with static caching:
     * - First request: varies (3-4 typical)
     * - With optimization: Only first call loads config, rest use cache
     * 
     * Performance Verification Task: 5.3
     * 
     * @return int Number of times initialize_configuration() was called
     */
    public static function get_init_call_count() {
        return self::$init_call_count;
    }
    
    /**
     * Reset initialize_configuration() call counter (for testing)
     * 
     * Resets the call counter to zero. Useful for testing scenarios
     * where you want to measure calls across specific operations.
     * 
     * WARNING: Only use this in testing/verification scripts.
     * 
     * Performance Verification Task: 5.3
     * 
     * @return void
     */
    public static function reset_init_call_count() {
        self::$init_call_count = 0;
    }
    
    /**
     * Get build_sync_table_cache() call counter (for performance verification)
     * 
     * Returns the number of times build_sync_table_cache() was called
     * in the current request. Used to verify that static caching is working
     * correctly and the table cache is only built once per request.
     * 
     * Expected value with static caching:
     * - First request: varies (3-4 typical without optimization)
     * - With optimization: Only first call builds cache, rest use static cache
     * 
     * Performance Verification Task: 5.4
     * 
     * @return int Number of times build_sync_table_cache() was called
     */
    public static function get_cache_build_call_count() {
        return self::$cache_build_call_count;
    }
    
    /**
     * Reset build_sync_table_cache() call counter (for testing)
     * 
     * Resets the call counter to zero. Useful for testing scenarios
     * where you want to measure cache builds across specific operations.
     * 
     * WARNING: Only use this in testing/verification scripts.
     * 
     * Performance Verification Task: 5.4
     * 
     * @return void
     */
    public static function reset_cache_build_call_count() {
        self::$cache_build_call_count = 0;
    }
    
    /**
     * Get sync data for injection
     * 
     * Returns the sync column data to be injected into updates.
     * Caches the data for the request lifecycle to avoid repeated computation.
     * 
     * Requirements: 5.1, 5.2
     * Task: 5.2
     * 
     * @return array Sync column data
     */
    private function get_sync_data() {
        // Return cached value if available
        if ($this->sync_data_cache !== null) {
            return $this->sync_data_cache;
        }
        
        // Build sync data
        $this->sync_data_cache = [
            'sync_status' => 'PENDING',
            'last_modified_at' => date('Y-m-d H:i:s'),
            'device_id' => $this->get_device_id(),
            'last_modified_by' => $this->get_current_user_id()
        ];
        
        return $this->sync_data_cache;
    }
    
    /**
     * Inject sync columns into update data
     * 
     * Adds sync tracking columns to the update data array.
     * Overrides any existing sync column values to ensure consistency.
     * Version column is handled separately as a SQL expression.
     * 
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5
     * Task: 5.1
     * 
     * @param array $data Original update data
     * @param string $table Table name
     * @param mixed $where WHERE conditions (for fetching current record state)
     * @return array Modified update data with sync columns
     */
    private function inject_sync_columns($data, $table = '', $where = null) {
        // Get sync data (cached)
        $sync_data = $this->get_sync_data();
        
        // Merge sync data into update data (sync data overrides existing values)
        $data = array_merge($data, $sync_data);
        
        // Handle sync_operation_type transitions (similar to MY_Model logic)
        // Only if table has sync_operation_type column
        if (!empty($table)) {
            try {
                // Check if table has sync_operation_type column
                $fields = $this->list_fields($table);
                if (in_array('sync_operation_type', $fields)) {
                    // Fetch current record state to determine operation type
                    // We need to know if the record was SYNCED or PENDING
                    $current = $this->get_current_record_state($table, $where);
                    
                    if ($current) {
                        if ($current['sync_status'] === 'SYNCED') {
                            // Previously synced record being updated - mark as 'update'
                            $data['sync_operation_type'] = 'update';
                            log_message('debug', "MY_DB_mysqli_driver: Setting sync_operation_type='update' (was SYNCED)");
                        } elseif ($current['sync_status'] === 'PENDING') {
                            // Still pending from previous operation
                            if (in_array($current['sync_operation_type'], ['insert', 'both'])) {
                                // Record was inserted but not synced yet, now being updated
                                $data['sync_operation_type'] = 'both';
                                log_message('debug', "MY_DB_mysqli_driver: Setting sync_operation_type='both' (was PENDING with 'insert')");
                            } elseif ($current['sync_operation_type'] === 'update') {
                                // Already marked for update, keep it as update
                                $data['sync_operation_type'] = 'update';
                                log_message('debug', "MY_DB_mysqli_driver: Keeping sync_operation_type='update'");
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error but don't fail the update
                log_message('error', 'MY_DB_mysqli_driver: Failed to set sync_operation_type - ' . $e->getMessage());
            }
        }
        
        // Note: version column will be handled separately in update() method
        // using set('version', 'version + 1', FALSE) to avoid race conditions
        
        return $data;
    }
    
    /**
     * Get current record state for sync_operation_type determination
     * 
     * Fetches the current sync_status and sync_operation_type of the record being updated.
     * Used to determine whether to set operation_type to 'update' or 'both'.
     * 
     * @param string $table Table name
     * @param mixed $where WHERE conditions
     * @return array|null Current record state or null
     */
    private function get_current_record_state($table, $where) {
        try {
            // We need to build a raw query to avoid interfering with the main query builder state
            // This is tricky - we'll need to build WHERE clause from the current qb_where
            
            // Get primary key for this table
            $pk = $this->get_primary_key($table);
            if (empty($pk)) {
                log_message('debug', "MY_DB_mysqli_driver: Cannot determine primary key for table '{$table}'");
                return null;
            }
            
            // Try to extract the ID from WHERE conditions
            $id = null;
            
            // Check if $where is a simple array with primary key
            if (is_array($where) && isset($where[$pk])) {
                $id = $where[$pk];
                log_message('debug', "MY_DB_mysqli_driver: Extracted ID={$id} from \$where parameter");
            }
            
            // Check qb_where for primary key condition
            if ($id === null && !empty($this->qb_where)) {
                log_message('debug', "MY_DB_mysqli_driver: Checking qb_where for primary key '{$pk}', qb_where contains " . count($this->qb_where) . " items");
                
                // CodeIgniter's qb_where contains arrays with structure like:
                // array('condition' => 'settings_id = ', 'value' => 486, 'escape' => TRUE)
                foreach ($this->qb_where as $idx => $where_item) {
                    log_message('debug', "MY_DB_mysqli_driver: qb_where[{$idx}] type = " . gettype($where_item));
                    
                    // Handle array structure (CodeIgniter 3.x format)
                    if (is_array($where_item) && isset($where_item['condition'])) {
                        $condition = $where_item['condition'];
                        log_message('debug', "MY_DB_mysqli_driver: qb_where[{$idx}]['condition'] = '{$condition}'");
                        
                        // Check if condition involves the primary key
                        if (stripos($condition, $pk) !== false) {
                            // Check if value is in the array
                            if (isset($where_item['value']) && is_numeric($where_item['value'])) {
                                $id = $where_item['value'];
                                log_message('debug', "MY_DB_mysqli_driver: Extracted ID={$id} from qb_where['value']");
                                break;
                            }
                        }
                    }
                    // Handle string format (older CI versions or custom queries)
                    elseif (is_string($where_item)) {
                        // Try to match: `column` = value or column = value
                        $pattern = "/[`]?" . preg_quote($pk, '/') . "[`]?\s*=\s*['\"]?(\d+)['\"]?/";
                        if (preg_match($pattern, $where_item, $matches)) {
                            $id = $matches[1];
                            log_message('debug', "MY_DB_mysqli_driver: Extracted ID={$id} from qb_where string: {$where_item}");
                            break;
                        }
                    }
                }
            }
            
            // Check qb_binds if still no ID found (for prepared statements)
            if ($id === null && !empty($this->qb_where) && !empty($this->qb_binds)) {
                // qb_binds contains the values for bound parameters
                // Try to match the first bind value if qb_where references the primary key
                foreach ($this->qb_where as $where_item) {
                    if (is_string($where_item) && stripos($where_item, $pk) !== false) {
                        // This WHERE clause involves the primary key
                        // Assume the first bind is the ID (common case)
                        if (isset($this->qb_binds[0]) && is_numeric($this->qb_binds[0])) {
                            $id = $this->qb_binds[0];
                            log_message('debug', "MY_DB_mysqli_driver: Extracted ID={$id} from qb_binds");
                            break;
                        }
                    }
                }
            }
            
            if ($id === null) {
                // Can't determine the record ID, skip operation_type logic
                log_message('debug', "MY_DB_mysqli_driver: Cannot extract record ID from WHERE conditions for table '{$table}', skipping sync_operation_type logic");
                return null;
            }
            
            // Fetch current state using a new query to avoid interfering with the main query builder
            // We need to create a fresh query to avoid corrupting the qb_where state
            $sql = "SELECT sync_status, sync_operation_type FROM `{$table}` WHERE `{$pk}` = ? LIMIT 1";
            $result = $this->query($sql, [$id]);
            
            if ($result && $result->num_rows() > 0) {
                $state = $result->row_array();
                log_message('debug', "MY_DB_mysqli_driver: Fetched current record state for ID={$id}: sync_status={$state['sync_status']}, sync_operation_type=" . ($state['sync_operation_type'] ?? 'NULL'));
                return $state;
            }
            
            log_message('debug', "MY_DB_mysqli_driver: No record found with ID={$id} in table '{$table}'");
            return null;
            
        } catch (Exception $e) {
            log_message('error', 'MY_DB_mysqli_driver: Failed to get current record state - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get primary key column name for a table
     * 
     * Uses static cache shared across all instances to avoid repeated database queries.
     * 
     * Requirements: 3.3
     * Task: 1.5
     * 
     * @param string $table Table name
     * @return string|null Primary key column name or null
     */
    private function get_primary_key($table) {
        // Check static cache first (shared across all instances)
        if (isset(self::$primary_key_cache_static[$table])) {
            return self::$primary_key_cache_static[$table];
        }
        
        // Check instance cache as fallback
        if (isset($this->primary_key_cache[$table])) {
            return $this->primary_key_cache[$table];
        }
        
        try {
            $result = $this->query("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND CONSTRAINT_NAME = 'PRIMARY'
                LIMIT 1
            ", [$table]);
            
            if ($result && $result->num_rows() > 0) {
                $row = $result->row_array();
                $pk = $row['COLUMN_NAME'];
                
                // Cache in both static and instance caches
                self::$primary_key_cache_static[$table] = $pk;
                $this->primary_key_cache[$table] = $pk;
                
                return $pk;
            }
            
            // Fallback: assume 'id' column
            $pk = 'id';
            self::$primary_key_cache_static[$table] = $pk;
            $this->primary_key_cache[$table] = $pk;
            
            return $pk;
            
        } catch (Exception $e) {
            log_message('error', 'MY_DB_mysqli_driver: Failed to get primary key for table ' . $table . ' - ' . $e->getMessage());
            
            // Cache fallback value
            $pk = 'id';
            self::$primary_key_cache_static[$table] = $pk;
            $this->primary_key_cache[$table] = $pk;
            
            return $pk;
        }
    }
    
    /**
     * Override update() method to inject sync columns
     * 
     * Intercepts UPDATE operations and automatically injects sync tracking columns
     * when operating on tables that have sync columns. Preserves all WHERE conditions,
     * LIMIT, and other parameters. Handles version column as SQL expression to avoid
     * race conditions.
     * 
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 3.1, 3.2, 3.3, 3.4, 3.5, 7.1, 7.2, 7.3
     * Task: 6.1
     * 
     * @param string $table Table name
     * @param array $set Update data
     * @param mixed $where WHERE conditions
     * @param int $limit Limit
     * @return bool Success status
     */
    public function update($table = '', $set = NULL, $where = NULL, $limit = NULL) {
        try {
            // Check if sync injection is enabled
            if (!$this->sync_injection_enabled) {
                log_message('debug', 'MY_DB_mysqli_driver: Sync injection disabled, passing through to parent');
                return parent::update($table, $set, $where, $limit);
            }
            
            // Extract table name - it could be in $table parameter or in qb_from
            $target_table = '';
            if (!empty($table)) {
                $target_table = $table;
            } elseif (!empty($this->qb_from) && is_array($this->qb_from)) {
                $target_table = $this->qb_from[0];
            }
            
            // If we can't determine the table, pass through to parent
            if (empty($target_table)) {
                log_message('debug', 'MY_DB_mysqli_driver: Cannot determine table name, passing through to parent');
                return parent::update($table, $set, $where, $limit);
            }
            
            // Check if table has sync columns
            if (!$this->has_sync_columns($target_table)) {
                log_message('debug', 'MY_DB_mysqli_driver: Table "' . $target_table . '" does not have sync columns, passing through to parent');
                return parent::update($table, $set, $where, $limit);
            }
            
            // Table has sync columns - inject them
            log_message('debug', 'MY_DB_mysqli_driver: Injecting sync columns for table "' . $target_table . '"');
            
            // Get sync data including sync_operation_type logic
            $sync_columns = $this->inject_sync_columns([], $target_table, $where);
            
            // Apply sync columns using set() method (works for both $set parameter and query builder)
            foreach ($sync_columns as $column => $value) {
                // Regular value
                $this->set($column, $value);
            }
            
            // Handle version column as SQL expression (always increment)
            $this->set('version', 'version + 1', FALSE);
            
            // If $set is provided, merge sync columns into it for consistency
            if ($set !== NULL && is_array($set)) {
                $set = array_merge($set, $sync_columns);
            }
            
            // Call parent update with modified data
            $result = parent::update($table, $set, $where, $limit);
            
            log_message('debug', 'MY_DB_mysqli_driver: Update completed for table "' . $target_table . '" with sync columns injected');
            
            return $result;
            
        } catch (Exception $e) {
            // Log error but don't fail the update - pass through to parent
            log_message('error', 'MY_DB_mysqli_driver: Sync injection failed - ' . $e->getMessage());
            
            // Try to execute the update without sync injection
            try {
                return parent::update($table, $set, $where, $limit);
            } catch (Exception $parent_e) {
                // Re-throw parent exception to maintain backward compatibility
                throw $parent_e;
            }
        }
    }
    
    /**
     * Override update_batch() method for batch updates
     * 
     * Intercepts batch UPDATE operations and automatically injects sync tracking columns
     * into each record in the batch. Uses the same timestamp and device_id for all records
     * to maintain consistency. Handles version column increment by fetching current values
     * and incrementing them (CodeIgniter's batch update doesn't support SQL expressions).
     * Preserves all original batch parameters (index, where conditions).
     * 
     * Requirements: 4.1, 4.2, 4.3, 4.4, 7.4
     * Task: 7
     * 
     * @param string $table Table name
     * @param array $set Batch data (array of associative arrays)
     * @param string $index Key column for WHERE clause
     * @param int $batch_size Number of records per batch
     * @return int|bool Number of rows updated or FALSE on failure
     */
    public function update_batch($table = '', $set = NULL, $index = NULL, $batch_size = 100) {
        try {
            // Check if sync injection is enabled
            if (!$this->sync_injection_enabled) {
                log_message('debug', 'MY_DB_mysqli_driver: Sync injection disabled for batch update, passing through to parent');
                return parent::update_batch($table, $set, $index, $batch_size);
            }
            
            // Validate table parameter
            if (empty($table)) {
                log_message('debug', 'MY_DB_mysqli_driver: Empty table name in batch update, passing through to parent');
                return parent::update_batch($table, $set, $index, $batch_size);
            }
            
            // Validate set parameter
            if ($set === NULL || !is_array($set) || empty($set)) {
                log_message('debug', 'MY_DB_mysqli_driver: Invalid or empty batch data, passing through to parent');
                return parent::update_batch($table, $set, $index, $batch_size);
            }
            
            // Validate index parameter
            if (empty($index)) {
                log_message('debug', 'MY_DB_mysqli_driver: Empty index in batch update, passing through to parent');
                return parent::update_batch($table, $set, $index, $batch_size);
            }
            
            // Check if table has sync columns
            if (!$this->has_sync_columns($table)) {
                log_message('debug', 'MY_DB_mysqli_driver: Table "' . $table . '" does not have sync columns for batch update, passing through to parent');
                return parent::update_batch($table, $set, $index, $batch_size);
            }
            
            // Table has sync columns - inject them into each batch record
            log_message('debug', 'MY_DB_mysqli_driver: Injecting sync columns for batch update on table "' . $table . '" (' . count($set) . ' records)');
            
            // Get sync data once (same timestamp and device_id for all records in batch)
            $sync_data = $this->get_sync_data();
            
            // Fetch current version values for all records in the batch
            // This is necessary because CodeIgniter's update_batch() escapes all values
            // and doesn't support SQL expressions like 'version + 1'
            $index_values = array_column($set, $index);
            $version_map = [];
            
            if (!empty($index_values)) {
                try {
                    // Build WHERE IN clause for fetching versions
                    $this->where_in($index, $index_values);
                    $version_query = $this->select($index . ', version')->get($table);
                    
                    if ($version_query && $version_query->num_rows() > 0) {
                        foreach ($version_query->result_array() as $row) {
                            $version_map[$row[$index]] = isset($row['version']) ? (int)$row['version'] : 0;
                        }
                    }
                } catch (Exception $e) {
                    log_message('warning', 'MY_DB_mysqli_driver: Failed to fetch version values for batch update - ' . $e->getMessage());
                    // Continue without version increment if fetch fails
                }
            }
            
            // Inject sync columns into each batch record
            foreach ($set as &$record) {
                // Merge sync data into this record
                $record = array_merge($record, $sync_data);
                
                // Increment version based on current value
                if (isset($record[$index]) && isset($version_map[$record[$index]])) {
                    $record['version'] = $version_map[$record[$index]] + 1;
                } else {
                    // If we couldn't fetch the version, just increment by 1 from default
                    $record['version'] = 1;
                }
            }
            unset($record); // Break reference
            
            // Call parent update_batch with modified batch data
            $result = parent::update_batch($table, $set, $index, $batch_size);
            
            log_message('debug', 'MY_DB_mysqli_driver: Batch update completed for table "' . $table . '" with sync columns injected');
            
            return $result;
            
        } catch (Exception $e) {
            // Log error but don't fail the batch update
            log_message('error', 'MY_DB_mysqli_driver: Sync injection failed for batch update - ' . $e->getMessage());
            
            // Try to execute the batch update without sync injection
            try {
                return parent::update_batch($table, $set, $index, $batch_size);
            } catch (Exception $parent_e) {
                // Re-throw parent exception to maintain backward compatibility
                throw $parent_e;
            }
        }
    }
    
    // ========================================================================
    // DELETION TRACKING METHODS
    // ========================================================================
    
    /**
     * Load deletion tracking configuration
     * 
     * Loads configuration settings for deletion tracking from sync.php config file.
     * Uses fallback defaults if configuration is not available.
     * 
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6
     * Task: 4.2
     * 
     * @return void
     */
    private function load_deletion_tracking_config() {
        if ($this->deletion_tracking_config !== null) {
            return; // Already loaded
        }
        
        try {
            $CI =& get_instance();
            
            // Load sync configuration
            $CI->config->load('sync', TRUE);
            
            // Check master sync enabled flag - same priority as initialize_configuration()
            $sync_master_enabled = $this->get_offline_online_mode_from_db();
            if ($sync_master_enabled === null) {
                $sync_master_enabled = $CI->config->item('sync_enabled', 'sync') ?? TRUE;
            }
            
            // If sync is disabled at master level, disable deletion tracking too
            if (!$sync_master_enabled) {
                $this->deletion_tracking_config = ['enabled' => FALSE];
                log_message('info', 'MY_DB_mysqli_driver: Deletion tracking DISABLED by master switch');
                return;
            }
            
            // Load deletion tracking settings with defaults
            $this->deletion_tracking_config = [
                'enabled' => $CI->config->item('deletion_tracking_enabled', 'sync') ?? TRUE,
                'excluded_tables' => $CI->config->item('deletion_tracking_excluded_tables', 'sync') ?? [
                    // Session tables - excluded because sessions are transient and high-volume
                    'sessions',
                    'ci_sessions',
                    
                    // Sync system tables - excluded to prevent infinite recursion
                    // (deletion tracking itself creates records in these tables)
                    'sync_audit_log',
                    'sync_conflicts',
                    'sync_deletions',
                    'sync_metadata'
                    
                    // NOTE: payment, journal_entries, journal_entry_lines previously excluded
                    // as workarounds for WHERE clause parsing bug - now removed since bug is fixed
                ],
                'retention_days' => $CI->config->item('deletion_retention_days', 'sync') ?? 30,
                'max_retries' => $CI->config->item('deletion_max_retries', 'sync') ?? 3,
                'batch_size' => $CI->config->item('deletion_batch_size', 'sync') ?? 100,
                'batch_warning_threshold' => $CI->config->item('deletion_batch_warning_threshold', 'sync') ?? 1000
            ];
            
            log_message('debug', 'MY_DB_mysqli_driver: Deletion tracking config loaded - enabled=' . 
                       ($this->deletion_tracking_config['enabled'] ? 'true' : 'false'));
            
        } catch (Exception $e) {
            log_message('error', 'MY_DB_mysqli_driver: Failed to load deletion tracking config - ' . $e->getMessage());
            
            // Use defaults
            $this->deletion_tracking_config = [
                'enabled' => TRUE,
                'excluded_tables' => ['sessions', 'ci_sessions', 'sync_audit_log', 'sync_conflicts', 'sync_deletions', 'sync_metadata', 'journal_entry_lines', 'journal_entries', 'payment'],
                'retention_days' => 30,
                'max_retries' => 3,
                'batch_size' => 100,
                'batch_warning_threshold' => 1000
            ];
        }
    }
    
    /**
     * Get primary key columns for a table
     * 
     * Queries information_schema to retrieve primary key column names for a table.
     * Supports both single-column and composite primary keys.
     * Results are cached for performance.
     * 
     * Requirements: 3.1, 3.2
     * Task: 2.1
     * 
     * @param string $table Table name
     * @return array Array of primary key column names
     */
    private function get_primary_key_columns($table) {
        // Check cache first
        if (isset($this->primary_key_cache[$table])) {
            return $this->primary_key_cache[$table];
        }
        
        try {
            // Query information_schema for primary key columns
            $query = "
                SELECT COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND CONSTRAINT_NAME = 'PRIMARY'
                ORDER BY ORDINAL_POSITION
            ";
            
            $result = $this->query($query, [$table]);
            
            $pk_columns = [];
            if ($result && $result->num_rows() > 0) {
                foreach ($result->result() as $row) {
                    $pk_columns[] = $row->COLUMN_NAME;
                }
            }
            
            // Cache the result
            $this->primary_key_cache[$table] = $pk_columns;
            
            log_message('debug', 'MY_DB_mysqli_driver: Primary key columns for table "' . $table . '": ' . 
                       implode(', ', $pk_columns));
            
            return $pk_columns;
            
        } catch (Exception $e) {
            log_message('error', 'MY_DB_mysqli_driver: Failed to get primary key columns for table "' . $table . '" - ' . 
                       $e->getMessage());
            
            // Return empty array on error
            $this->primary_key_cache[$table] = [];
            return [];
        }
    }
    
    /**
     * Encode record identifier as JSON
     * 
     * Encodes primary key column(s) and value(s) as JSON for storage in sync_deletions table.
     * Supports both single-column and composite primary keys.
     * 
     * Format examples:
     * - Single PK: {"id":"123"}
     * - Composite PK: {"student_id":"456","class_id":"789"}
     * 
     * Requirements: 3.3, 3.4, 3.5
     * Task: 2.2
     * 
     * @param array $pk_columns Array of primary key column names
     * @param array $record Record data containing primary key values
     * @return string JSON-encoded record identifier
     */
    private function encode_record_identifier($pk_columns, $record) {
        try {
            // Defensive check: ensure $pk_columns is an array
            if (!is_array($pk_columns)) {
                if (is_string($pk_columns) && !empty($pk_columns)) {
                    // Convert single column name string to array
                    $pk_columns = [$pk_columns];
                    log_message('warning', 'MY_DB_mysqli_driver: encode_record_identifier received string instead of array, converted to array');
                } else {
                    log_message('error', 'MY_DB_mysqli_driver: encode_record_identifier received invalid $pk_columns type: ' . gettype($pk_columns));
                    throw new Exception('Invalid $pk_columns parameter type');
                }
            }
            
            // Defensive check: ensure $record is an array
            if (!is_array($record)) {
                log_message('error', 'MY_DB_mysqli_driver: encode_record_identifier received invalid $record type: ' . gettype($record));
                throw new Exception('Invalid $record parameter type');
            }
            
            $identifier = [];
            
            foreach ($pk_columns as $column) {
                if (isset($record[$column])) {
                    // Convert to string to ensure consistent JSON encoding
                    $identifier[$column] = (string)$record[$column];
                } else {
                    log_message('warning', 'MY_DB_mysqli_driver: Primary key column "' . $column . '" not found in record');
                    $identifier[$column] = '';
                }
            }
            
            // Encode as JSON with unescaped unicode
            $json = json_encode($identifier, JSON_UNESCAPED_UNICODE);
            
            if ($json === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }
            
            return $json;
            
        } catch (Exception $e) {
            log_message('error', 'MY_DB_mysqli_driver: Failed to encode record identifier - ' . $e->getMessage());
            return '{}';
        }
    }
    
    /**
     * Log deletion to sync_deletions table
     * 
     * Inserts deletion records into sync_deletions table for synchronization.
     * Uses batch insert for efficiency when multiple records are deleted.
     * Handles errors gracefully to never block DELETE operations.
     * 
     * Requirements: 2.6, 2.9, 12.2, 12.3
     * Task: 2.3
     * 
     * @param string $table Table name where deletion occurred
     * @param array $affected_records Array of records that were deleted
     * @return bool Success status
     */
    private function log_deletion($table, $affected_records) {
        try {
            // Get sync data
            $sync_data = $this->get_sync_data();
            $deleted_at = date('Y-m-d H:i:s');
            $deleted_by = $this->get_current_user_id();
            $device_id = $this->get_device_id();
            
            // Get primary key columns
            $pk_columns = $this->get_primary_key_columns($table);
            
            if (empty($pk_columns)) {
                log_message('warning', 'MY_DB_mysqli_driver: Cannot log deletion for table "' . $table . '" - no primary key found');
                return false;
            }
            
            // Build array of deletion records for batch insert
            $deletion_records = [];
            
            foreach ($affected_records as $record) {
                // Encode record identifier as JSON
                $record_id = $this->encode_record_identifier($pk_columns, $record);
                
                $deletion_records[] = [
                    'table_name' => $table,
                    'record_id' => $record_id,
                    'deleted_at' => $deleted_at,
                    'deleted_by' => $deleted_by,
                    'device_id' => $device_id,
                    'sync_status' => 'PENDING',
                    'last_modified_at' => $deleted_at,
                    'version' => 1,
                    'retry_count' => 0,
                    'error_message' => NULL
                ];
            }
            
            // Check batch size warning threshold
            $config = $this->deletion_tracking_config;
            if (count($deletion_records) > $config['batch_warning_threshold']) {
                log_message('warning', 'MY_DB_mysqli_driver: Large batch deletion detected - ' . 
                           count($deletion_records) . ' records from table "' . $table . '"');
            }
            
            // Insert deletion records using batch insert
            if (!empty($deletion_records)) {
                $this->insert_batch('sync_deletions', $deletion_records);
                
                log_message('info', 'MY_DB_mysqli_driver: Logged ' . count($deletion_records) . 
                           ' deletion(s) for table "' . $table . '"');
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            // Log error but don't fail - graceful degradation
            log_message('error', 'MY_DB_mysqli_driver: Failed to log deletion for table "' . $table . '" - ' . 
                       $e->getMessage());
            return false;
        }
    }
    
    /**
     * Override delete() method to intercept DELETE operations
     * 
     * Intercepts DELETE operations and logs them to sync_deletions table before execution.
     * Queries affected records BEFORE deletion to capture primary key values.
     * Handles errors gracefully to never block DELETE operations.
     * 
     * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.7, 2.8, 11.1, 11.2, 11.3
     * Task: 2.4
     * 
     * @param string $table Table name
     * @param mixed $where WHERE conditions
     * @param int $limit Limit
     * @param bool $reset_data Reset query builder data
     * @return bool Success status
     */
    public function delete($table = '', $where = '', $limit = NULL, $reset_data = TRUE) {
        $start_time = microtime(true);
        
        try {
            // Load deletion tracking config
            $this->load_deletion_tracking_config();
            
            // Check if deletion tracking is enabled
            if (!$this->deletion_tracking_config['enabled']) {
                log_message('debug', 'MY_DB_mysqli_driver: Deletion tracking disabled, passing through to parent');
                return parent::delete($table, $where, $limit, $reset_data);
            }
            
            // Extract table name
            $target_table = '';
            if (!empty($table)) {
                $target_table = $table;
            } elseif (!empty($this->qb_from) && is_array($this->qb_from)) {
                $target_table = $this->qb_from[0];
            }
            
            // If we can't determine the table, pass through to parent
            if (empty($target_table)) {
                log_message('debug', 'MY_DB_mysqli_driver: Cannot determine table name for deletion, passing through to parent');
                return parent::delete($table, $where, $limit, $reset_data);
            }
            
            // Check if table is in excluded list
            if (in_array($target_table, $this->deletion_tracking_config['excluded_tables'])) {
                log_message('debug', 'MY_DB_mysqli_driver: Table "' . $target_table . '" is excluded from deletion tracking');
                return parent::delete($table, $where, $limit, $reset_data);
            }
            
            // CRITICAL FIX: Ensure WHERE conditions are properly set in query builder
            // CodeIgniter's parent delete() method checks if qb_where is empty and blocks deletion
            // We need to ensure qb_where is populated BEFORE calling parent::delete()
            
            // If $where parameter is provided, apply it to query builder
            // If $where is empty, use existing qb_where from previous where() calls
            if (!empty($where)) {
                // Clear any existing WHERE to avoid conflicts when explicit $where is passed
                $this->qb_where = [];
                
                // Apply WHERE using CodeIgniter's where() method
                if (is_array($where)) {
                    // Array format: ['column' => 'value', 'column2' => 'value2']
                    $this->where($where);
                    log_message('debug', 'MY_DB_mysqli_driver: Applied array WHERE parameter to query builder - ' . count($where) . ' condition(s)');
                } else if (is_string($where)) {
                    // String format: "column = 'value' AND column2 = 'value2'"
                    $this->where($where, NULL, FALSE);
                    log_message('debug', 'MY_DB_mysqli_driver: Applied string WHERE parameter to query builder: ' . $where);
                }
            } else {
                // No $where parameter - use existing query builder conditions from where() calls
                log_message('debug', 'MY_DB_mysqli_driver: No WHERE parameter provided, using existing query builder conditions (' . count($this->qb_where) . ' condition(s))');
            }
            
            // Verify qb_where is not empty (CodeIgniter safety check)
            if (empty($this->qb_where)) {
                log_message('error', 'MY_DB_mysqli_driver: DELETE attempted without WHERE clause on table "' . $target_table . '" - BLOCKED for safety');
                // Return error to match CodeIgniter's behavior
                if ($this->db_debug) {
                    return $this->display_error('db_del_must_use_where');
                }
                return FALSE;
            }
            
            log_message('debug', 'MY_DB_mysqli_driver: qb_where contains ' . count($this->qb_where) . ' condition(s)');
            
            // CRITICAL: Save qb_where NOW before it gets modified by _compile_wh()
            // We'll need this later when calling parent::delete()
            $saved_qb_where_for_delete = $this->qb_where;
            
            // Query affected records BEFORE deletion to get primary key values
            $affected_records = [];
            
            try {
                // DEBUG: Save qb_where before any operations
                $saved_qb_where = $this->qb_where;
                
                // Use CodeIgniter's built-in method to compile WHERE clause
                $sql_where = $this->_compile_wh('qb_where');
                
                // PHP 8.x Fix: Suppress warning for intentional qb_where modification during deletion tracking
                // This is expected behavior when compiling WHERE clauses for deletion operations
                // Log at debug level instead of warning level for genuine investigation needs
                if ($this->qb_where !== $saved_qb_where) {
                    log_message('debug', 'MY_DB_mysqli_driver: _compile_wh() modified qb_where during deletion tracking (expected behavior)');
                    // Restore qb_where
                    $this->qb_where = $saved_qb_where;
                }
                
                // Add limit if specified
                $sql_limit = '';
                if ($limit !== NULL) {
                    $sql_limit = ' LIMIT ' . (int)$limit;
                }
                
                // Build SELECT query
                $select_sql = 'SELECT * FROM ' . $this->escape_identifiers($target_table) . $sql_where . $sql_limit;
                
                log_message('debug', 'MY_DB_mysqli_driver: Deletion tracking SELECT: ' . $select_sql);
                
                // Execute using raw mysqli to avoid query builder conflicts
                $result = $this->conn_id->query($select_sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $affected_records[] = $row;
                    }
                    $result->free();
                }
                
                log_message('debug', 'MY_DB_mysqli_driver: Fetched ' . count($affected_records) . ' records for deletion tracking');
                
            } catch (Exception $e) {
                log_message('warning', 'MY_DB_mysqli_driver: Failed to query affected records before deletion - ' . 
                           $e->getMessage());
                // Continue with deletion even if we couldn't query affected records
            }
            
            // Log deletion if we have affected records
            if (!empty($affected_records)) {
                try {
                    $this->log_deletion($target_table, $affected_records);
                } catch (Exception $log_error) {
                    log_message('error', 'MY_DB_mysqli_driver: Failed to log deletion - ' . $log_error->getMessage());
                    // Continue with deletion even if logging failed
                }
            }
            
            // Execute the actual DELETE operation
            // IMPORTANT: Pass compiled WHERE string to parent to avoid query builder state issues
            // Don't pass the $where parameter directly - use the compiled qb_where instead
            if (empty($where) && !empty($saved_qb_where_for_delete)) {
                // Query builder was used - compile the SAVED qb_where to a WHERE string
                // We can't use $this->qb_where because _compile_wh() already cleared it
                
                // Temporarily restore qb_where so we can compile it
                $this->qb_where = $saved_qb_where_for_delete;
                $where_string = $this->_compile_wh('qb_where');
                
                // Remove the leading " WHERE " or "\nWHERE " from the compiled string
                $where_string = preg_replace('/^\s*WHERE\s+/i', '', $where_string);
                
                log_message('debug', 'MY_DB_mysqli_driver: _compile_wh returned: ' . $where_string);
                log_message('debug', 'MY_DB_mysqli_driver: Passing compiled WHERE string to parent: ' . $where_string);
                
                // Call parent with the WHERE string instead of relying on qb_where
                // Set reset_data to FALSE to preserve qb_where for parent's use
                $result = parent::delete($table, $where_string, $limit, $reset_data);
            } else {
                // $where parameter was provided - use it directly
                $result = parent::delete($table, $where, $limit, $reset_data);
            }
            
            // Log operation metrics
            $duration_ms = round((microtime(true) - $start_time) * 1000, 2);
            log_message('debug', 'MY_DB_mysqli_driver: DELETE completed for table "' . $target_table . '" - ' . 
                       count($affected_records) . ' records logged, ' . $duration_ms . 'ms');
            
            return $result;
            
        } catch (Exception $e) {
            // Log error but try to execute the DELETE anyway
            log_message('error', 'MY_DB_mysqli_driver: Deletion tracking failed - ' . $e->getMessage());
            log_message('error', 'MY_DB_mysqli_driver: Stack trace: ' . $e->getTraceAsString());
            
            try {
                return parent::delete($table, $where, $limit, $reset_data);
            } catch (Exception $parent_e) {
                // Re-throw parent exception to maintain backward compatibility
                throw $parent_e;
            }
        }
    }
}
