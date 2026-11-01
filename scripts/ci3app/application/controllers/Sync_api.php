<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync API Controller
 * 
 * REST API endpoints for multi-location bidirectional sync.
 * All endpoints require API key authentication via X-Sync-API-Key header.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Sync
 * @author     School Manager Team
 * @version    2.0.0
 */
class Sync_api extends CI_Controller {
    
    /**
     * Request start time for duration tracking
     * @var float
     */
    private $request_start_time;
    
    /**
     * Request ID for logging
     * @var string
     */
    private $request_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->request_start_time = microtime(true);
        $this->request_id = uniqid('sync_api_', true);
        
        // Set JSON header
        header('Content-Type: application/json');
        
        // Load dependencies
        $this->load->library('Sync_service');
        $this->load->model('Sync_audit_log_model');
        $this->load->model('Sync_metrics_model');
        
        // Authenticate request (except for health check)
        if ($this->router->fetch_method() !== 'health') {
            $this->authenticate();
        }
    }
    
    /**
     * Authenticate API request
     * 
     * Validates API key from header or request parameter.
     * Also validates IP whitelist if configured.
     */
    private function authenticate() {
        // Get API key from header or parameter
        $api_key = $this->input->get_request_header('X-Sync-API-Key');
        
        if (empty($api_key)) {
            $api_key = $this->input->get_post('api_key');
        }
        
        // Get configured API key
        $config_key = $this->get_config_api_key();
        
        // Check if API key is configured
        if (empty($config_key)) {
            $this->send_error('API key not configured on server', 500, 'config_error');
            return;
        }
        
        // Validate API key
        if (empty($api_key) || !hash_equals($config_key, $api_key)) {
            $this->log_unauthorized_access($api_key);
            $this->send_error('Invalid or missing API key', 401, 'unauthorized');
            return;
        }
        
        // Validate IP whitelist if configured
        $allowed_ips = $this->config->item('allowed_ips');
        if (!empty($allowed_ips) && is_array($allowed_ips)) {
            $client_ip = $this->get_client_ip();
            if (!in_array($client_ip, $allowed_ips) && !in_array('*', $allowed_ips)) {
                $this->log_unauthorized_access($api_key, $client_ip);
                $this->send_error('IP address not whitelisted', 403, 'forbidden');
                return;
            }
        }
        
        // Check rate limiting
        if (!$this->check_rate_limit()) {
            $this->send_error('Rate limit exceeded. Please try again later.', 429, 'rate_limit');
            return;
        }
    }
    
    /**
     * Get configured API key
     * 
     * @return string|null
     */
    private function get_config_api_key() {
        // First check config file
        $config_key = $this->config->item('sync_api_key');
        
        if (!empty($config_key)) {
            return $config_key;
        }
        
        // Then check database settings
        $setting = $this->db->get_where('settings', ['type' => 'sync_api_key'])->row();
        
        if ($setting && !empty($setting->description)) {
            return $setting->description;
        }
        
        // Check environment variable
        return getenv('SYNC_API_KEY') ?: null;
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip = $this->input->server('REMOTE_ADDR');
        
        // Check for proxy headers
        $forwarded = $this->input->server('HTTP_X_FORWARDED_FOR');
        if (!empty($forwarded)) {
            $ips = explode(',', $forwarded);
            $ip = trim($ips[0]);
        }
        
        $real_ip = $this->input->server('HTTP_X_REAL_IP');
        if (!empty($real_ip)) {
            $ip = $real_ip;
        }
        
        return $ip;
    }
    
    /**
     * Check rate limiting
     * 
     * @return bool True if within limits
     */
    private function check_rate_limit() {
        $client_ip = $this->get_client_ip();
        $cache_key = 'sync_rate_limit_' . md5($client_ip);
        
        // Use simple file-based rate limiting
        $cache_file = APPPATH . 'cache/' . $cache_key;
        $limit = 100; // requests per minute
        $window = 60; // seconds
        
        $current_time = time();
        $requests = [];
        
        if (file_exists($cache_file)) {
            $data = json_decode(file_get_contents($cache_file), true);
            if ($data && isset($data['requests'])) {
                // Filter out old requests
                $requests = array_filter($data['requests'], function($time) use ($current_time, $window) {
                    return ($current_time - $time) < $window;
                });
            }
        }
        
        if (count($requests) >= $limit) {
            return false;
        }
        
        // Add current request
        $requests[] = $current_time;
        file_put_contents($cache_file, json_encode(['requests' => $requests]));
        
        return true;
    }
    
    /**
     * Log unauthorized access attempt
     * 
     * @param string|null $api_key Provided API key
     * @param string|null $ip Client IP
     */
    private function log_unauthorized_access($api_key = null, $ip = null) {
        $ip = $ip ?: $this->get_client_ip();
        
        log_message('info', sprintf(
            '[Sync API] Unauthorized access attempt - IP: %s, Key: %s, Endpoint: %s',
            $ip,
            $api_key ? substr($api_key, 0, 8) . '...' : 'none',
            $this->router->fetch_method()
        ));
    }
    
    /**
     * Send JSON error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param string $error_code Internal error code
     */
    private function send_error($message, $code = 400, $error_code = 'error') {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'error_code' => $error_code,
            'message' => $message,
            'request_id' => $this->request_id,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    /**
     * Send JSON success response
     * 
     * @param array $data Response data
     */
    private function send_response($data = []) {
        $duration = round((microtime(true) - $this->request_start_time) * 1000, 2);
        
        $response = array_merge([
            'status' => 'success',
            'request_id' => $this->request_id,
            'timestamp' => date('c'),
            'duration_ms' => $duration
        ], $data);
        
        echo json_encode($response);
    }
    
    /**
     * Health check endpoint (no authentication required)
     */
    public function health() {
        $this->send_response([
            'healthy' => true,
            'version' => '2.0.0',
            'timestamp' => date('c')
        ]);
    }
    
    /**
     * Register device
     * 
     * Registers a new device for sync operations.
     * Returns device_id and auth_token for future requests.
     */
    public function register() {
        $device_id = $this->input->post('device_id');
        $device_name = $this->input->post('device_name');
        $user_id = $this->input->post('user_id');
        $user_type = $this->input->post('user_type');
        $location_name = $this->input->post('location_name');
        
        // Validate required fields
        if (empty($device_name)) {
            $this->send_error('device_name is required', 400, 'validation_error');
            return;
        }
        
        // Generate device_id if not provided
        if (empty($device_id)) {
            $device_id = 'loc-' . strtolower(preg_replace('/[^a-z0-9]/i', '', $device_name)) . '-' . substr(md5(uniqid()), 0, 8);
        }
        
        // Validate user_type
        if (!empty($user_type) && !in_array($user_type, ['admin', 'teacher', 'parent'])) {
            $this->send_error('Invalid user_type. Must be admin, teacher, or parent', 400, 'validation_error');
            return;
        }
        
        try {
            $id = $this->sync_service->register_device($device_id, $device_name, $user_id, $user_type);
            
            // Log registration
            $this->log_audit('register', 'sync_devices', $id, [
                'device_id' => $device_id,
                'device_name' => $device_name
            ]);
            
            $this->send_response([
                'device_id' => $id,
                'message' => 'Device registered successfully'
            ]);
        } catch (Exception $e) {
            log_message('error', '[Sync API] Registration failed: ' . $e->getMessage());
            $this->send_error('Registration failed: ' . $e->getMessage(), 500, 'registration_error');
        }
    }
    
    /**
     * Push changes from local to remote
     * 
     * Accepts a batch of changes and applies them to the remote database.
     * Returns count of synced records and any conflicts detected.
     */
    public function push() {
        $device_id = $this->input->post('device_id');
        $batch_data = $this->input->post('batch');
        
        if (empty($device_id)) {
            $this->send_error('device_id is required', 400, 'validation_error');
            return;
        }
        
        // Parse batch data if JSON string
        if (is_string($batch_data)) {
            $batch_data = json_decode($batch_data, true);
        }
        
        try {
            $result = $this->sync_service->push_changes($device_id, $batch_data);
            
            // Log push operation
            $this->log_audit('push', 'sync_queue', null, [
                'device_id' => $device_id,
                'synced' => $result['synced'] ?? 0,
                'conflicts' => $result['conflicts'] ?? 0
            ]);
            
            // Record metrics
            $this->Sync_metrics_model->record_synced($result['synced'] ?? 0, null, null);
            if (!empty($result['conflicts'])) {
                $this->Sync_metrics_model->record_conflicts_detected($result['conflicts'], null, null);
            }
            
            $this->send_response([
                'synced' => $result['synced'] ?? 0,
                'conflicts' => $result['conflicts'] ?? 0,
                'failed' => $result['failed'] ?? 0,
                'message' => 'Push completed successfully'
            ]);
        } catch (Exception $e) {
            log_message('error', '[Sync API] Push failed: ' . $e->getMessage());
            $this->send_error('Push failed: ' . $e->getMessage(), 500, 'push_error');
        }
    }
    
    /**
     * Pull changes from remote to local
     * 
     * Returns all changes since the last sync timestamp.
     */
    public function pull() {
        $device_id = $this->input->get_post('device_id');
        $last_sync = $this->input->get_post('last_sync');
        $tables = $this->input->get_post('tables');
        
        if (empty($device_id)) {
            $this->send_error('device_id is required', 400, 'validation_error');
            return;
        }
        
        // Default last_sync to 24 hours ago if not provided
        if (empty($last_sync)) {
            $last_sync = date('Y-m-d H:i:s', strtotime('-24 hours'));
        }
        
        // Parse tables if provided
        if (is_string($tables)) {
            $tables = json_decode($tables, true);
        }
        
        try {
            $changes = $this->sync_service->pull_changes($device_id, $last_sync, $tables);
            
            $total_records = 0;
            foreach ($changes as $table_records) {
                $total_records += count($table_records);
            }
            
            // Log pull operation
            $this->log_audit('pull', 'multiple', null, [
                'device_id' => $device_id,
                'last_sync' => $last_sync,
                'records_count' => $total_records
            ]);
            
            $this->send_response([
                'changes' => $changes,
                'total_records' => $total_records,
                'last_sync' => date('Y-m-d H:i:s'),
                'message' => 'Pull completed successfully'
            ]);
        } catch (Exception $e) {
            log_message('error', '[Sync API] Pull failed: ' . $e->getMessage());
            $this->send_error('Pull failed: ' . $e->getMessage(), 500, 'pull_error');
        }
    }
    
    /**
     * Get pending conflicts
     * 
     * Returns all conflicts awaiting resolution.
     */
    public function conflicts() {
        $status = $this->input->get('status') ?: 'pending';
        $table = $this->input->get('table');
        $limit = (int) $this->input->get('limit') ?: 100;
        $offset = (int) $this->input->get('offset') ?: 0;
        
        $this->load->model('Sync_conflict_model');
        
        $filters = [];
        if (!empty($table)) {
            $filters['table_name'] = $table;
        }
        
        $conflicts = $this->Sync_conflict_model->get_pending_conflicts($filters, $limit, $offset);
        $total = $this->Sync_conflict_model->get_pending_count();
        
        $this->send_response([
            'conflicts' => $conflicts,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    /**
     * Get conflict details
     * 
     * @param int $id Conflict ID
     */
    public function conflict($id) {
        if (empty($id)) {
            $this->send_error('Conflict ID is required', 400, 'validation_error');
            return;
        }
        
        $this->load->model('Sync_conflict_model');
        $conflict = $this->Sync_conflict_model->get_conflict($id);
        
        if (!$conflict) {
            $this->send_error('Conflict not found', 404, 'not_found');
            return;
        }
        
        $this->send_response([
            'conflict' => $conflict
        ]);
    }
    
    /**
     * Resolve a conflict
     */
    public function resolve() {
        $id = $this->input->post('conflict_id');
        $resolution = $this->input->post('resolution');
        $merged_data = $this->input->post('merged_data');
        
        if (empty($id) || empty($resolution)) {
            $this->send_error('conflict_id and resolution are required', 400, 'validation_error');
            return;
        }
        
        // Validate resolution type
        $valid_resolutions = ['local_wins', 'remote_wins', 'merged', 'ignored'];
        if (!in_array($resolution, $valid_resolutions)) {
            $this->send_error('Invalid resolution. Must be: ' . implode(', ', $valid_resolutions), 400, 'validation_error');
            return;
        }
        
        // Parse merged_data if JSON string
        if (is_string($merged_data)) {
            $merged_data = json_decode($merged_data, true);
        }
        
        $this->load->model('Sync_conflict_model');
        
        $resolved_by = $this->input->post('resolved_by') ?: 0;
        
        if ($this->Sync_conflict_model->resolve_conflict($id, $resolution, $resolved_by, $merged_data)) {
            // Log resolution
            $this->log_audit('resolve_conflict', 'sync_conflicts', $id, [
                'resolution' => $resolution
            ]);
            
            // Record metric
            $this->Sync_metrics_model->record_conflicts_resolved(1);
            
            $this->send_response([
                'message' => 'Conflict resolved successfully',
                'resolution' => $resolution
            ]);
        } else {
            $this->send_error('Failed to resolve conflict', 500, 'resolution_error');
        }
    }
    
    /**
     * Get sync status
     */
    public function status() {
        $device_id = $this->input->get('device_id');
        
        
        $status = $this->Sync_model->get_sync_status($device_id);
        
        $this->send_response($status);
    }
    
    /**
     * Acknowledge sync completion
     * 
     * Called by client after successfully applying pulled changes.
     */
    public function acknowledge() {
        $device_id = $this->input->post('device_id');
        $sync_time = $this->input->post('sync_time');
        
        if (empty($device_id)) {
            $this->send_error('device_id is required', 400, 'validation_error');
            return;
        }
        
        // Update last sync time for device
        $this->db->where('device_id', $device_id)
                 ->update('sync_devices', [
                     'last_sync_at' => $sync_time ?: date('Y-m-d H:i:s')
                 ]);
        
        $this->send_response([
            'message' => 'Acknowledged',
            'device_id' => $device_id
        ]);
    }
    
    /**
     * Log audit entry
     * 
     * @param string $operation Operation type
     * @param string $table Table name
     * @param int|null $record_id Record ID
     * @param array $metadata Additional data
     */
    private function log_audit($operation, $table, $record_id, $metadata = []) {
        try {
            $this->Sync_audit_log_model->log([
                'table_name' => $table,
                'record_id' => $record_id,
                'operation' => strtoupper($operation),
                'source_device_id' => $this->input->post('device_id') ?: 'api',
                'sync_direction' => 'push',
                'status' => 'success',
                'new_value' => $metadata
            ]);
        } catch (Exception $e) {
            log_message('error', '[Sync API] Audit log failed: ' . $e->getMessage());
        }
    }
}
