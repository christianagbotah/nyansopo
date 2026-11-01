<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Aware Model
 * 
 * Base model that automatically marks records as PENDING when created or updated.
 * All models that need sync functionality should extend this class.
 * 
 * @package    School Manager
 * @subpackage Models
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: 2.1, 2.2
 */
class MY_Model extends CI_Model {
    
    /**
     * Table name (must be set by child class)
     * @var string
     */
    protected $table;
    
    /**
     * Primary key field (must be set by child class)
     * @var string
     */
    protected $primary_key = 'id';
    
    /**
     * Device ID for this local server
     * @var string
     */
    private $device_id;
    
    /**
     * Cache: Is sync module enabled?
     * @var bool|null
     */
    private $sync_enabled = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Ensure database is loaded
        if (!isset($this->db)) {
            $this->load->database();
        }
        
        // Check if sync is enabled
        $this->sync_enabled = $this->is_sync_enabled();
        
        // Get device ID only if sync is enabled
        if ($this->sync_enabled) {
            $this->device_id = $this->get_device_id();
        } else {
            $this->device_id = null;
        }
    }
    
    /**
     * Check if sync module is enabled
     * 
     * Priority order:
     * 1. Database settings table (offline_online_mode)
     * 2. Config file sync.php
     * 3. Default: TRUE
     * 
     * @return bool
     */
    private function is_sync_enabled() {
        try {
            // Try database first
            $setting = $this->db->get_where('settings', ['type' => 'offline_online_mode'])->row();
            if ($setting) {
                return ($setting->description === '1' || $setting->description === 1);
            }
            
            // Fall back to config file
            $this->config->load('sync', TRUE);
            return $this->config->item('sync_enabled', 'sync') ?? TRUE;
            
        } catch (Exception $e) {
            // Default to enabled if we can't determine
            log_message('warning', 'MY_Model: Could not determine sync_enabled status - ' . $e->getMessage());
            return TRUE;
        }
    }
    
    /**
     * Get device ID from settings
     * 
     * @return string
     */
    private function get_device_id() {
        $setting = $this->db->get_where('settings', ['type' => 'device_id'])->row();
        return $setting ? $setting->description : 'local-server-001';
    }
    
    /**
     * Get location ID for this device
     * 
     * Requirements: 7
     * Task: 2.7
     * 
     * @return int|null Location ID
     */
    private function get_location_id() {
        // Try to get from location_registry based on device_id
        $location = $this->db->get_where('location_registry', ['device_id' => $this->device_id])->row();
        return $location ? $location->id : null;
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null
     */
    private function get_current_user_id() {
        // Try to get admin ID
        if ($this->session->userdata('admin_id')) {
            return $this->session->userdata('admin_id');
        }
        
        // Try to get teacher ID
        if ($this->session->userdata('teacher_id')) {
            return $this->session->userdata('teacher_id');
        }
        
        // Try to get parent ID
        if ($this->session->userdata('parent_id')) {
            return $this->session->userdata('parent_id');
        }
        
        return null;
    }
    
    /**
     * Check if table has sync columns
     * 
     * @return bool
     */
    protected function has_sync_columns() {
        if (!$this->table) {
            return false;
        }
        
        $fields = $this->db->list_fields($this->table);
        return in_array('sync_status', $fields) && 
               in_array('last_modified_at', $fields);
    }
    
    /**
     * Insert record with sync tracking
     * 
     * Requirements: 2.1, 2.2, 7
     * Task: 2.7
     * 
     * @param array $data Record data
     * @return int|bool Insert ID or false on failure
     */
    public function insert($data) {
        if (!$this->table) {
            log_message('error', 'Sync_aware_model: Table name not set');
            return false;
        }
        
        // Add sync columns only if sync is enabled and table supports them
        if ($this->sync_enabled && $this->has_sync_columns()) {
            $data['sync_status'] = 'PENDING';
            $data['last_modified_at'] = date('Y-m-d H:i:s');
            $data['last_modified_by'] = $this->get_current_user_id();
            $data['device_id'] = $this->device_id;
            
            // Add location_id if column exists
            $fields = $this->db->list_fields($this->table);
            if (in_array('location_id', $fields)) {
                $data['location_id'] = $this->get_location_id();
            }
            
            // Task 3.5: Explicitly set sync_operation_type='insert' for clarity
            // Even though the database has a DEFAULT 'insert' value, setting it explicitly
            // ensures consistency and clarity in the application code
            // Requirements: 2.3 - New records get 'insert' operation type
            if (in_array('sync_operation_type', $fields)) {
                $data['sync_operation_type'] = 'insert';
            }
            
            // Initialize version if column exists
            if (in_array('version', $fields)) {
                $data['version'] = 1;
            }
        }
        
        // Insert record
        $result = $this->db->insert($this->table, $data);
        
        if ($result) {
            return $this->db->insert_id();
        }
        
        return false;
    }
    
    /**
     * Update record with sync tracking
     * 
     * Requirements: 2.1, 2.2, 7
     * Task: 2.7
     * 
     * @param mixed $id Primary key value
     * @param array $data Update data
     * @return bool Success status
     */
    public function update($id, $data) {
        if (!$this->table || !$this->primary_key) {
            log_message('error', 'Sync_aware_model: Table name or primary key not set');
            return false;
        }
        
        // Add sync columns only if sync is enabled and table supports them
        if ($this->sync_enabled && $this->has_sync_columns()) {
            $fields = $this->db->list_fields($this->table);
            
            // Track previous sync status and set operation type (Task 3.7)
            // Requirements: 1.1, 2.1, 2.5 - Distinguish UPDATE vs INSERT operations
            if (in_array('sync_operation_type', $fields)) {
                // Get current sync status and operation type
                $current = $this->db->select('sync_status, sync_operation_type')
                                   ->where($this->primary_key, $id)
                                   ->get($this->table)
                                   ->row();
                
                if (!$current) {
                    log_message('error', "Cannot find record to update: $this->table ID=$id");
                    return false;
                }
                
                // Determine new operation type based on current state
                if ($current->sync_status === 'SYNCED') {
                    // Previously synced record being updated - mark as 'update'
                    $data['sync_operation_type'] = 'update';
                    log_message('debug', "Update: $this->table ID=$id, setting operation_type='update' (was SYNCED)");
                } elseif ($current->sync_status === 'PENDING') {
                    // Still pending from previous operation
                    if (in_array($current->sync_operation_type, ['insert', 'both'])) {
                        // Record inserted but not synced yet, now being updated
                        // Use 'both' to indicate INSERT is still needed (not UPDATE)
                        $data['sync_operation_type'] = 'both';
                        log_message('debug', "Update: $this->table ID=$id, setting operation_type='both' (was PENDING with 'insert')");
                    } elseif ($current->sync_operation_type === 'update') {
                        // Already marked for update, keep it as update
                        $data['sync_operation_type'] = 'update';
                        log_message('debug', "Update: $this->table ID=$id, keeping operation_type='update'");
                    }
                }
            }
            
            $data['sync_status'] = 'PENDING';
            $data['last_modified_at'] = date('Y-m-d H:i:s');
            $data['last_modified_by'] = $this->get_current_user_id();
            $data['device_id'] = $this->device_id;
            
            // Add location_id if column exists
            if (in_array('location_id', $fields)) {
                $data['location_id'] = $this->get_location_id();
            }
            
            // Increment version if column exists
            if (in_array('version', $fields)) {
                // Get current version (may already have $current from above)
                if (!isset($current) || !isset($current->version)) {
                    $current = $this->db->select('version')
                                       ->where($this->primary_key, $id)
                                       ->get($this->table)
                                       ->row();
                }
                
                if ($current) {
                    $data['version'] = ($current->version ?? 0) + 1;
                }
            }
        }
        
        // Update record
        return $this->db->where($this->primary_key, $id)
                       ->update($this->table, $data);
    }
    
    /**
     * Delete record (marks as deleted if soft delete column exists)
     * 
     * @param mixed $id Primary key value
     * @return bool Success status
     */
    public function delete($id) {
        if (!$this->table || !$this->primary_key) {
            log_message('error', 'Sync_aware_model: Table name or primary key not set');
            return false;
        }
        
        $fields = $this->db->list_fields($this->table);
        
        // Check if table has soft delete column
        if (in_array('deleted_at', $fields) || in_array('is_deleted', $fields)) {
            // Soft delete
            $data = [];
            
            if (in_array('deleted_at', $fields)) {
                $data['deleted_at'] = date('Y-m-d H:i:s');
            }
            
            if (in_array('is_deleted', $fields)) {
                $data['is_deleted'] = 1;
            }
            
            // Mark as pending for sync
            if ($this->has_sync_columns()) {
                $data['sync_status'] = 'PENDING';
                $data['last_modified_at'] = date('Y-m-d H:i:s');
                $data['last_modified_by'] = $this->get_current_user_id();
                $data['device_id'] = $this->device_id;
            }
            
            return $this->db->where($this->primary_key, $id)
                           ->update($this->table, $data);
        } else {
            // Hard delete
            return $this->db->where($this->primary_key, $id)
                           ->delete($this->table);
        }
    }
    
    /**
     * Get record by ID
     * 
     * @param mixed $id Primary key value
     * @return object|null Record object or null
     */
    public function get($id) {
        if (!$this->table || !$this->primary_key) {
            return null;
        }
        
        return $this->db->where($this->primary_key, $id)
                       ->get($this->table)
                       ->row();
    }
    
    /**
     * Get all records
     * 
     * @param array $where Optional where conditions
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Records array
     */
    public function get_all($where = [], $limit = null, $offset = null) {
        if (!$this->table) {
            return [];
        }
        
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        
        return $this->db->get($this->table)->result_array();
    }
    
    /**
     * Count records
     * 
     * @param array $where Optional where conditions
     * @return int Record count
     */
    public function count($where = []) {
        if (!$this->table) {
            return 0;
        }
        
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        return $this->db->count_all_results($this->table);
    }
    
    /**
     * Batch insert with sync tracking
     * 
     * @param array $data Array of records to insert
     * @return bool Success status
     */
    public function batch_insert($data) {
        if (!$this->table || empty($data)) {
            return false;
        }
        
        // Add sync columns to all records only if sync is enabled and table supports them
        if ($this->sync_enabled && $this->has_sync_columns()) {
            $sync_data = [
                'sync_status' => 'PENDING',
                'sync_operation_type' => 'insert', // Task 3.6: Explicitly set for batch inserts
                'last_modified_at' => date('Y-m-d H:i:s'),
                'last_modified_by' => $this->get_current_user_id(),
                'device_id' => $this->device_id
            ];
            
            $fields = $this->db->list_fields($this->table);
            
            // Task 3.6: Set sync_operation_type='insert' for new batch records
            // Even though database has DEFAULT 'insert', setting explicitly ensures consistency
            // Requirements: 2.3 - New batch records get 'insert' operation type
            
            if (in_array('version', $fields)) {
                $sync_data['version'] = 1;
            }
            
            foreach ($data as &$record) {
                $record = array_merge($record, $sync_data);
            }
        }
        
        return $this->db->insert_batch($this->table, $data);
    }
    
    /**
     * Batch update with sync tracking
     * 
     * @param array $data Array of records to update
     * @param string $key Key field for matching records
     * @return bool Success status
     */
    public function batch_update($data, $key = null) {
        if (!$this->table || empty($data)) {
            return false;
        }
        
        $key = $key ?? $this->primary_key;
        
        // Add sync columns to all records only if sync is enabled and table supports them
        if ($this->sync_enabled && $this->has_sync_columns()) {
            $fields = $this->db->list_fields($this->table);
            
            // Track previous sync status for batch updates (Task 3.8)
            // Requirements: 1.1, 2.1, 2.5 - Distinguish UPDATE vs INSERT operations
            if (in_array('sync_operation_type', $fields)) {
                // Get current sync status for all records being updated
                $keys = array_column($data, $key);
                $current_records = $this->db->select("$key, sync_status, sync_operation_type")
                                            ->where_in($key, $keys)
                                            ->get($this->table)
                                            ->result_array();
                
                // Index by key for quick lookup
                $current_map = [];
                foreach ($current_records as $record) {
                    $current_map[$record[$key]] = $record;
                }
                
                // Apply operation type logic for each record in batch
                foreach ($data as &$record) {
                    $current = $current_map[$record[$key]] ?? null;
                    
                    if ($current) {
                        if ($current['sync_status'] === 'SYNCED') {
                            // Previously synced record being updated - mark as 'update'
                            $record['sync_operation_type'] = 'update';
                        } elseif ($current['sync_status'] === 'PENDING') {
                            // Still pending from previous operation
                            if (in_array($current['sync_operation_type'], ['insert', 'both'])) {
                                // Record inserted but not synced yet, now being updated
                                // Use 'both' to indicate INSERT is still needed (not UPDATE)
                                $record['sync_operation_type'] = 'both';
                            } elseif ($current['sync_operation_type'] === 'update') {
                                // Already marked for update, keep it as update
                                $record['sync_operation_type'] = 'update';
                            }
                        }
                    }
                }
            }
            
            $sync_data = [
                'sync_status' => 'PENDING',
                'last_modified_at' => date('Y-m-d H:i:s'),
                'last_modified_by' => $this->get_current_user_id(),
                'device_id' => $this->device_id
            ];
            
            foreach ($data as &$record) {
                $record = array_merge($record, $sync_data);
            }
        }
        
        return $this->db->update_batch($this->table, $data, $key);
    }
}
