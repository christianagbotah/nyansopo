<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Location Manager Library
 * 
 * Manages location registry and location-based operations for multi-location
 * bidirectional sync system. Handles location registration, status tracking,
 * and location-based filtering.
 * 
 * @package    School Manager
 * @subpackage Libraries
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: 7, 8
 */
class Location_manager {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    private $CI;
    
    // Location status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    
    // Sync status constants
    const SYNC_SUCCESS = 'success';
    const SYNC_FAILED = 'failed';
    const SYNC_PARTIAL = 'partial';
    const SYNC_PENDING = 'pending';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Register a new location
     * 
     * @param array $data Location data: location_name, api_endpoint, contact_email, contact_phone, timezone, priority
     * @return array Result with status, location_id, device_id, message
     */
    public function register_location($data) {
        try {
            // Validate required fields
            if (empty($data['location_name'])) {
                return [
                    'status' => 'error',
                    'message' => 'Location name is required'
                ];
            }
            
            // Check if location name already exists
            $existing = $this->CI->db->get_where('location_registry', ['location_name' => $data['location_name']])->row();
            
            if ($existing) {
                return [
                    'status' => 'error',
                    'message' => 'Location name already exists'
                ];
            }
            
            // Generate unique device_id
            $device_id = $this->generate_device_id($data['location_name']);
            
            // Validate connection if api_endpoint provided
            if (!empty($data['api_endpoint'])) {
                $connection_test = $this->test_connection($data['api_endpoint']);
                
                if (!$connection_test['success']) {
                    return [
                        'status' => 'error',
                        'message' => 'Connection test failed: ' . $connection_test['message']
                    ];
                }
            }
            
            // Prepare location data
            $location_data = [
                'location_name' => $data['location_name'],
                'device_id' => $device_id,
                'api_endpoint' => $data['api_endpoint'] ?? null,
                'status' => self::STATUS_ACTIVE,
                'priority' => $data['priority'] ?? 0,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'timezone' => $data['timezone'] ?? 'UTC',
                'created_at' => date('Y-m-d H:i:s'),
                'last_sync_status' => self::SYNC_PENDING
            ];
            
            // Insert location
            $this->CI->db->insert('location_registry', $location_data);
            $location_id = $this->CI->db->insert_id();
            
            log_message('info', "[Location Manager] Registered new location: {$data['location_name']} (ID: $location_id, Device: $device_id)");
            
            return [
                'status' => 'success',
                'location_id' => $location_id,
                'device_id' => $device_id,
                'message' => 'Location registered successfully'
            ];
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error registering location: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error registering location: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate unique device ID for a location
     * 
     * @param string $location_name Location name
     * @return string Device ID
     */
    public function generate_device_id($location_name) {
        // Create base from location name
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $location_name));
        $base = substr($base, 0, 20); // Limit length
        
        // Add timestamp component for uniqueness
        $timestamp = substr(md5(microtime()), 0, 8);
        
        $device_id = "loc-{$base}-{$timestamp}";
        
        // Ensure uniqueness
        $existing = $this->CI->db->get_where('location_registry', ['device_id' => $device_id])->row();
        
        if ($existing) {
            // Add random suffix if collision
            $device_id .= '-' . substr(md5(rand()), 0, 4);
        }
        
        return $device_id;
    }
    
    /**
     * Get all active locations
     * 
     * @return array Active locations
     */
    public function get_active_locations() {
        try {
            $query = $this->CI->db->where('status', self::STATUS_ACTIVE)
                                 ->order_by('priority', 'DESC')
                                 ->order_by('location_name', 'ASC')
                                 ->get('location_registry');
            
            return $query->result_array();
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error getting active locations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all locations (including inactive)
     * 
     * @return array All locations
     */
    public function get_all_locations() {
        try {
            $query = $this->CI->db->order_by('priority', 'DESC')
                                 ->order_by('location_name', 'ASC')
                                 ->get('location_registry');
            
            return $query->result_array();
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error getting all locations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get location by device_id
     * 
     * @param string $device_id Device ID
     * @return object|null Location object
     */
    public function get_location_by_device_id($device_id) {
        try {
            return $this->CI->db->get_where('location_registry', ['device_id' => $device_id])->row();
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error getting location by device_id: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get location by ID
     * 
     * @param int $location_id Location ID
     * @return object|null Location object
     */
    public function get_location_by_id($location_id) {
        try {
            return $this->CI->db->get_where('location_registry', ['id' => $location_id])->row();
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error getting location by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update location sync status
     * 
     * @param string $device_id Device ID
     * @param string $status Sync status (success, failed, partial, pending)
     * @return bool Success status
     */
    public function update_sync_status($device_id, $status) {
        try {
            $update_data = [
                'last_sync_at' => date('Y-m-d H:i:s'),
                'last_sync_status' => $status
            ];
            
            $this->CI->db->where('device_id', $device_id)
                        ->update('location_registry', $update_data);
            
            log_message('info', "[Location Manager] Updated sync status for $device_id to $status");
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error updating sync status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if location is active
     * 
     * @param string $device_id Device ID
     * @return bool True if active
     */
    public function is_location_active($device_id) {
        try {
            $location = $this->get_location_by_device_id($device_id);
            return $location && $location->status === self::STATUS_ACTIVE;
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error checking location status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deactivate a location
     * 
     * @param int $location_id Location ID
     * @return bool Success status
     */
    public function deactivate_location($location_id) {
        try {
            $this->CI->db->where('id', $location_id)
                        ->update('location_registry', ['status' => self::STATUS_INACTIVE]);
            
            log_message('info', "[Location Manager] Deactivated location ID: $location_id");
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error deactivating location: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activate a location
     * 
     * @param int $location_id Location ID
     * @return bool Success status
     */
    public function activate_location($location_id) {
        try {
            $this->CI->db->where('id', $location_id)
                        ->update('location_registry', ['status' => self::STATUS_ACTIVE]);
            
            log_message('info', "[Location Manager] Activated location ID: $location_id");
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error activating location: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update location details
     * 
     * @param int $location_id Location ID
     * @param array $data Update data
     * @return bool Success status
     */
    public function update_location($location_id, $data) {
        try {
            // Remove fields that shouldn't be updated
            unset($data['id']);
            unset($data['device_id']);
            unset($data['created_at']);
            
            $this->CI->db->where('id', $location_id)
                        ->update('location_registry', $data);
            
            log_message('info', "[Location Manager] Updated location ID: $location_id");
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error updating location: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test connection to a location
     * 
     * @param string $api_endpoint API endpoint URL
     * @return array Result with success flag and message
     */
    public function test_connection($api_endpoint) {
        try {
            // Parse URL to get host and port
            $url_parts = parse_url($api_endpoint);
            
            if (!$url_parts || !isset($url_parts['host'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid API endpoint URL'
                ];
            }
            
            $host = $url_parts['host'];
            $port = $url_parts['port'] ?? 80;
            
            // Attempt socket connection
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
            
            if ($connection) {
                fclose($connection);
                return [
                    'success' => true,
                    'message' => 'Connection successful'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Connection failed: $errstr (Error: $errno)"
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get locations that haven't synced recently
     * 
     * @param int $hours Hours threshold
     * @return array Offline locations
     */
    public function get_offline_locations($hours = 24) {
        try {
            $cutoff_time = date('Y-m-d H:i:s', strtotime("-$hours hours"));
            
            $query = $this->CI->db->where('status', self::STATUS_ACTIVE)
                                 ->where('last_sync_at <', $cutoff_time)
                                 ->or_where('last_sync_at IS NULL')
                                 ->get('location_registry');
            
            return $query->result_array();
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error getting offline locations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get sync statistics for a location
     * 
     * @param string $device_id Device ID
     * @return array Statistics
     */
    public function get_location_stats($device_id) {
        try {
            $location = $this->get_location_by_device_id($device_id);
            
            if (!$location) {
                return [
                    'error' => 'Location not found'
                ];
            }
            
            // Get pending record count from sync_audit_log
            $pending_count = $this->CI->db->where('source_device_id', $device_id)
                                         ->where('status', 'pending')
                                         ->count_all_results('sync_audit_log');
            
            // Get recent sync operations
            $recent_syncs = $this->CI->db->where('source_device_id', $device_id)
                                        ->or_where('target_device_id', $device_id)
                                        ->order_by('synced_at', 'DESC')
                                        ->limit(10)
                                        ->get('sync_audit_log')
                                        ->result_array();
            
            // Calculate success rate
            $total_syncs = count($recent_syncs);
            $successful_syncs = 0;
            
            foreach ($recent_syncs as $sync) {
                if ($sync['status'] === 'success') {
                    $successful_syncs++;
                }
            }
            
            $success_rate = $total_syncs > 0 ? round(($successful_syncs / $total_syncs) * 100, 2) : 0;
            
            // Calculate time since last sync
            $last_sync_time = $location->last_sync_at ? strtotime($location->last_sync_at) : null;
            $hours_since_sync = $last_sync_time ? round((time() - $last_sync_time) / 3600, 1) : null;
            
            return [
                'location_name' => $location->location_name,
                'device_id' => $device_id,
                'status' => $location->status,
                'last_sync_at' => $location->last_sync_at,
                'last_sync_status' => $location->last_sync_status,
                'hours_since_sync' => $hours_since_sync,
                'pending_count' => $pending_count,
                'recent_syncs' => $recent_syncs,
                'success_rate' => $success_rate,
                'total_syncs' => $total_syncs
            ];
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error getting location stats: " . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get current location (this server)
     * 
     * @return object|null Current location
     */
    public function get_current_location() {
        try {
            // Get device ID from settings
            $setting = $this->CI->db->get_where('settings', ['type' => 'device_id'])->row();
            $device_id = $setting ? $setting->description : 'local-server-001';
            
            return $this->get_location_by_device_id($device_id);
            
        } catch (Exception $e) {
            log_message('error', "[Location Manager] Error getting current location: " . $e->getMessage());
            return null;
        }
    }
}
