<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Helper Functions
 * 
 * Provides helper functions for injecting sync tracking columns into database update operations.
 * This is a simpler, more reliable alternative to the MY_Controller wrapper approach.
 * 
 * @package    School Manager
 * @subpackage Helpers
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
 */

/**
 * Cache for table sync column detection
 * @var array
 */
$_sync_table_cache = null;

/**
 * Prepare sync column data for injection into update operations
 * 
 * Returns an array with sync tracking columns that should be added to update data.
 * Does NOT include 'version' - that should be handled at SQL level using:
 * $this->db->set('version', 'version + 1', FALSE);
 * 
 * @param array $exclude Optional array of column names to exclude
 * @return array Sync column data
 * 
 * Requirements: 2.1, 2.3, 2.4, 2.5, 2.6
 */
function prepare_sync_data($exclude = []) {
    try {
        $sync_data = [];
        
        // sync_status
        if (!in_array('sync_status', $exclude)) {
            $sync_data['sync_status'] = 'PENDING';
        }
        
        // last_modified_at
        if (!in_array('last_modified_at', $exclude)) {
            $sync_data['last_modified_at'] = date('Y-m-d H:i:s');
        }
        
        // device_id
        if (!in_array('device_id', $exclude)) {
            $sync_data['device_id'] = get_sync_device_id();
        }
        
        // last_modified_by
        if (!in_array('last_modified_by', $exclude)) {
            $sync_data['last_modified_by'] = get_sync_user_id();
        }
        
        return $sync_data;
        
    } catch (Exception $e) {
        log_message('error', 'prepare_sync_data() failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * Check if a table has all required sync columns
 * 
 * Uses caching to avoid repeated database queries.
 * 
 * @param string $table Table name
 * @return bool True if table has all 5 sync columns
 * 
 * Requirements: 2.6, 3.3
 */
function has_sync_columns($table) {
    global $_sync_table_cache;
    
    // Build cache if not already built
    if ($_sync_table_cache === null) {
        build_sync_table_cache();
    }
    
    // Check cache
    return isset($_sync_table_cache[$table]) && $_sync_table_cache[$table] === true;
}

/**
 * Build cache of tables with sync columns
 * 
 * Queries information_schema to find all tables that have all 5 sync columns.
 * Results are cached in a global variable for the duration of the request.
 * 
 * Requirements: 2.6, 3.3
 */
function build_sync_table_cache() {
    global $_sync_table_cache;
    
    $_sync_table_cache = [];
    
    try {
        $CI =& get_instance();
        
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
                $_sync_table_cache[$row->table_name] = true;
            }
            
            log_message('debug', 'Sync table cache built: ' . count($_sync_table_cache) . ' tables with sync columns');
        }
        
    } catch (Exception $e) {
        log_message('error', 'build_sync_table_cache() failed: ' . $e->getMessage());
        $_sync_table_cache = [];
    }
}

/**
 * Get device ID from configuration or database
 * 
 * Checks sync config file first, then falls back to settings table.
 * Returns a default value if not found.
 * 
 * @return string Device ID
 * 
 * Requirements: 2.4
 */
function get_sync_device_id() {
    try {
        $CI =& get_instance();
        
        // Try to get from sync config first
        $device_id = $CI->config->item('device_id', 'sync');
        
        // If not in config, try database settings table
        if (empty($device_id)) {
            $setting = $CI->db->get_where('settings', ['type' => 'device_id'])->row();
            if ($setting && !empty($setting->description)) {
                $device_id = $setting->description;
            }
        }
        
        // Use default if still empty
        if (empty($device_id)) {
            $device_id = 'DEVICE_UNKNOWN';
            log_message('warning', 'get_sync_device_id(): No device_id configured, using default');
        }
        
        return $device_id;
        
    } catch (Exception $e) {
        log_message('error', 'get_sync_device_id() failed: ' . $e->getMessage());
        return 'DEVICE_UNKNOWN';
    }
}

/**
 * Get current user ID from session
 * 
 * Checks for admin_id, teacher_id, and parent_id in that order.
 * Returns NULL if no user is logged in.
 * 
 * @return int|null User ID or NULL
 * 
 * Requirements: 2.5
 */
function get_sync_user_id() {
    try {
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
        
    } catch (Exception $e) {
        log_message('error', 'get_sync_user_id() failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Apply sync columns to update data array (convenience function)
 * 
 * Modifies the data array in place by merging sync column data.
 * Only applies sync columns if the table has them.
 * Respects manually provided sync column values (doesn't override).
 * 
 * @param array &$data Update data array (passed by reference)
 * @param string $table Table name
 * @return bool True if sync columns were applied, false otherwise
 * 
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.2
 */
function apply_sync_to_update(&$data, $table) {
    try {
        // Validate input
        if (!is_array($data)) {
            log_message('warning', 'apply_sync_to_update(): $data is not an array');
            return false;
        }
        
        if (empty($table)) {
            log_message('warning', 'apply_sync_to_update(): $table is empty');
            return false;
        }
        
        // Check if table has sync columns
        if (!has_sync_columns($table)) {
            log_message('debug', "apply_sync_to_update(): Table '$table' does not have sync columns");
            return false;
        }
        
        // Determine which columns to exclude (already manually provided)
        $exclude = [];
        if (isset($data['sync_status'])) $exclude[] = 'sync_status';
        if (isset($data['last_modified_at'])) $exclude[] = 'last_modified_at';
        if (isset($data['device_id'])) $exclude[] = 'device_id';
        if (isset($data['last_modified_by'])) $exclude[] = 'last_modified_by';
        
        // Get sync data
        $sync_data = prepare_sync_data($exclude);
        
        // Merge sync data into update data
        $data = array_merge($data, $sync_data);
        
        log_message('debug', "apply_sync_to_update(): Applied sync columns to table '$table'");
        
        return true;
        
    } catch (Exception $e) {
        log_message('error', 'apply_sync_to_update() failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Apply sync columns to batch update data array (convenience function)
 * 
 * Modifies each record in the batch array by adding sync column data.
 * Only applies sync columns if the table has them.
 * 
 * @param array &$batch_data Batch update data array (passed by reference)
 * @param string $table Table name
 * @param string $index_key Key column name for batch update
 * @return bool True if sync columns were applied, false otherwise
 * 
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.2
 */
function apply_sync_to_batch(&$batch_data, $table, $index_key) {
    try {
        // Validate input
        if (!is_array($batch_data) || empty($batch_data)) {
            log_message('warning', 'apply_sync_to_batch(): $batch_data is not a valid array');
            return false;
        }
        
        if (empty($table)) {
            log_message('warning', 'apply_sync_to_batch(): $table is empty');
            return false;
        }
        
        if (empty($index_key)) {
            log_message('warning', 'apply_sync_to_batch(): $index_key is empty');
            return false;
        }
        
        // Check if table has sync columns
        if (!has_sync_columns($table)) {
            log_message('debug', "apply_sync_to_batch(): Table '$table' does not have sync columns");
            return false;
        }
        
        // Get sync data once (same for all records)
        $sync_data = prepare_sync_data();
        
        // Apply sync data to each record
        foreach ($batch_data as &$record) {
            if (is_array($record)) {
                // Respect manually provided sync values
                foreach ($sync_data as $key => $value) {
                    if (!isset($record[$key])) {
                        $record[$key] = $value;
                    }
                }
            }
        }
        unset($record); // Break reference
        
        log_message('debug', "apply_sync_to_batch(): Applied sync columns to " . count($batch_data) . " records in table '$table'");
        
        return true;
        
    } catch (Exception $e) {
        log_message('error', 'apply_sync_to_batch() failed: ' . $e->getMessage());
        return false;
    }
}
