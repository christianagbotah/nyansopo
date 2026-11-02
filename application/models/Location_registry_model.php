<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Location Registry Model
 * 
 * Manages location records for multi-location sync support.
 * Each location represents a physical school branch with its own
 * local WAMP server installation.
 * 
 * @package    School Manager
 * @subpackage Models
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 7 - Location Registry and Management
 */
class Location_registry_model extends CI_Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'location_registry';
    
    /**
     * Primary key
     * @var string
     */
    protected $primary_key = 'id';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get all locations (regardless of status)
     * 
     * @return array Array of all location objects
     */
    public function get_all_locations() {
        return $this->db->order_by('location_name', 'ASC')
                        ->get($this->table)
                        ->result_array();
    }
    
    /**
     * Get all active locations
     * 
     * @return array Array of active location objects
     */
    public function get_active_locations() {
        return $this->db->where('status', 'active')
                        ->where('sync_enabled', TRUE)
                        ->order_by('priority', 'DESC')
                        ->get($this->table)
                        ->result_array();
    }
    
    /**
     * Get location by device_id
     * 
     * @param string $device_id The unique device identifier
     * @return object|null Location object or null if not found
     */
    public function get_location_by_device_id($device_id) {
        return $this->db->where('device_id', $device_id)
                        ->get($this->table)
                        ->row();
    }
    
    /**
     * Get location by ID
     * 
     * @param int $id Location ID
     * @return object|null Location object or null if not found
     */
    public function get_location($id) {
        return $this->db->where($this->primary_key, $id)
                        ->get($this->table)
                        ->row();
    }
    
    /**
     * Register a new location
     * 
     * @param array $data Location data
     * @return int|bool Insert ID or false on failure
     */
    public function register_location($data) {
        // Generate unique device_id if not provided
        if (empty($data['device_id'])) {
            $data['device_id'] = $this->generate_device_id();
        }
        
        // Set defaults
        $data['status'] = $data['status'] ?? 'active';
        $data['sync_enabled'] = $data['sync_enabled'] ?? TRUE;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->insert($data);
    }
    
    /**
     * Update location sync status
     * 
     * @param string $device_id The device identifier
     * @param string $status Sync status (success, failed, partial, pending)
     * @return bool Success status
     */
    public function update_sync_status($device_id, $status) {
        return $this->db->where('device_id', $device_id)
                        ->update($this->table, [
                            'last_sync_at' => date('Y-m-d H:i:s'),
                            'last_sync_status' => $status
                        ]);
    }
    
    /**
     * Check if location is active
     * 
     * @param string $device_id The device identifier
     * @return bool True if active, false otherwise
     */
    public function is_location_active($device_id) {
        $location = $this->db->where('device_id', $device_id)
                             ->where('status', 'active')
                             ->where('sync_enabled', TRUE)
                             ->get($this->table)
                             ->row();
        
        return !empty($location);
    }
    
    /**
     * Deactivate a location
     * 
     * @param int $id Location ID
     * @return bool Success status
     */
    public function deactivate_location($id) {
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table, [
                            'status' => 'inactive',
                            'sync_enabled' => FALSE
                        ]);
    }
    
    /**
     * Activate a location
     * 
     * @param int $id Location ID
     * @return bool Success status
     */
    public function activate_location($id) {
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table, [
                            'status' => 'active',
                            'sync_enabled' => TRUE
                        ]);
    }
    
    /**
     * Suspend a location
     * 
     * @param int $id Location ID
     * @param string $reason Reason for suspension
     * @return bool Success status
     */
    public function suspend_location($id, $reason = '') {
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table, [
                            'status' => 'suspended',
                            'description' => $reason
                        ]);
    }
    
    /**
     * Generate a unique device ID
     * 
     * @return string Unique device ID in format loc-branch-xxx
     */
    public function generate_device_id() {
        $prefix = 'loc-branch-';
        $suffix = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $device_id = $prefix . $suffix;
        
        // Ensure uniqueness
        while ($this->device_id_exists($device_id)) {
            $suffix = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $device_id = $prefix . $suffix;
        }
        
        return $device_id;
    }
    
    /**
     * Check if device_id already exists
     * 
     * @param string $device_id The device identifier
     * @return bool True if exists, false otherwise
     */
    public function device_id_exists($device_id) {
        return $this->db->where('device_id', $device_id)
                        ->count_all_results($this->table) > 0;
    }
    
    /**
     * Get locations that haven't synced recently
     * 
     * @param int $hours Number of hours threshold
     * @return array Array of location objects
     */
    public function get_stale_locations($hours = 24) {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        return $this->db->where('status', 'active')
                        ->where('sync_enabled', TRUE)
                        ->group_start()
                            ->where('last_sync_at <', $threshold)
                            ->or_where('last_sync_at IS NULL')
                        ->group_end()
                        ->get($this->table)
                        ->result_array();
    }
    
    /**
     * Get location statistics
     * 
     * @return array Statistics array
     */
    public function get_location_stats() {
        $stats = [
            'total' => $this->count(['status' => 'active']),
            'active' => $this->count(['status' => 'active', 'sync_enabled' => TRUE]),
            'inactive' => $this->count(['status' => 'inactive']),
            'suspended' => $this->count(['status' => 'suspended']),
            'stale' => count($this->get_stale_locations(24))
        ];
        
        return $stats;
    }
    
    /**
     * Get current location (this server's location)
     * 
     * @return object|null Current location object
     */
    public function get_current_location() {
        // Get device_id from settings
        $device_id = $this->get_device_id();
        
        return $this->get_location_by_device_id($device_id);
    }
    
    /**
     * Get device_id from settings (inherited from Sync_aware_model)
     * 
     * @return string
     */
    private function get_device_id() {
        $setting = $this->db->get_where('settings', ['type' => 'device_id'])->row();
        return $setting ? $setting->description : 'local-server-001';
    }
    
    /**
     * Insert record
     * 
     * @param array $data Record data
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
     * Update record
     * 
     * @param mixed $id Primary key value
     * @param array $data Update data
     * @return bool Success status
     */
    public function update($id, $data) {
        return $this->db->where($this->primary_key, $id)
                       ->update($this->table, $data);
    }
    
    /**
     * Count records
     * 
     * @param array $where Optional where conditions
     * @return int Record count
     */
    public function count($where = []) {
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        return $this->db->count_all_results($this->table);
    }
}
