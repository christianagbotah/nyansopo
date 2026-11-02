<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Configuration Controller
 * 
 * Manages sync configuration settings for the multi-location bidirectional sync system.
 * Allows administrators to configure conflict strategies, real-time sync, data scope,
 * and other sync parameters per table.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 8 - Sync Configuration Management
 */
class Sync_config extends CI_Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Check authentication
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Check authorization - only admin level 1-2 can access
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if (!$admin || $admin->level > 2) {
            $this->session->set_flashdata('error_message', 'You do not have permission to access sync configuration');
            redirect(site_url('admin/dashboard'), 'refresh');
        }
        
        // Load dependencies
        $this->load->model('Sync_metrics_model');
    }
    
    /**
     * Configuration page
     */
    public function index() {
        // Get all sync-enabled tables
        $page_data['tables'] = $this->get_sync_tables_config();
        
        // Get global settings
        $page_data['global_settings'] = $this->get_global_settings();
        
        // Get conflict strategy options
        $page_data['conflict_strategies'] = [
            'TIMESTAMP_WINS' => 'Timestamp Wins (Most recent change wins)',
            'REMOTE_WINS' => 'Remote Wins (Cloud data always overwrites local)',
            'LOCAL_WINS' => 'Local Wins (Local data always overwrites remote)',
            'VERSION_WINS' => 'Version Wins (Higher version number wins)',
            'MANUAL_REVIEW' => 'Manual Review (Requires human intervention)'
        ];
        
        // Get data scope options
        $page_data['data_scopes'] = [
            'GLOBAL' => 'Global (Synced across all locations)',
            'LOCATION_SPECIFIC' => 'Location Specific (Only synced to originating location)',
            'REGIONAL' => 'Regional (Synced within a region only)'
        ];
        
        // Get locations for sync targets
        $page_data['locations'] = $this->db->select('location_id, location_name, device_id')
                                           ->where('status', 'active')
                                           ->get('location_registry')
                                           ->result_array();
        
        $page_data['page_name'] = 'sync_config';
        $page_data['page_title'] = get_phrase('sync_configuration');
        
        $this->load->view('backend/index', $page_data);
    }
    
    /**
     * Update configuration (AJAX)
     */
    public function update() {
        // Verify CSRF token
        $token = $this->input->post('csrf_token');
        if (!$this->security->verify_csrf_hash($token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        $table_name = $this->input->post('table_name');
        $config = [
            'conflict_strategy' => $this->input->post('conflict_strategy'),
            'real_time_sync' => $this->input->post('real_time_sync') ? 1 : 0,
            'data_scope' => $this->input->post('data_scope'),
            'batch_size' => (int) $this->input->post('batch_size'),
            'sync_targets' => $this->input->post('sync_targets') ? json_encode($this->input->post('sync_targets')) : null,
            'priority' => (int) $this->input->post('priority')
        ];
        
        // Validate
        $validation = $this->validate_config($config);
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }
        
        // Update or insert
        $existing = $this->db->where('table_name', $table_name)
                            ->get('sync_metadata')
                            ->row();
        
        if ($existing) {
            $this->db->where('table_name', $table_name)
                     ->update('sync_metadata', $config);
        } else {
            $config['table_name'] = $table_name;
            $config['sync_enabled'] = 1;
            $this->db->insert('sync_metadata', $config);
        }
        
        // Log the change
        $this->log_config_change($table_name, $config);
        
        echo json_encode([
            'success' => true,
            'message' => 'Configuration updated successfully'
        ]);
    }
    
    /**
     * Bulk update configuration (AJAX)
     */
    public function bulk_update() {
        // Verify CSRF token
        $token = $this->input->post('csrf_token');
        if (!$this->security->verify_csrf_hash($token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        $tables = $this->input->post('tables');
        $config = [
            'conflict_strategy' => $this->input->post('conflict_strategy'),
            'real_time_sync' => $this->input->post('real_time_sync') ? 1 : 0,
            'data_scope' => $this->input->post('data_scope'),
            'batch_size' => (int) $this->input->post('batch_size'),
            'priority' => (int) $this->input->post('priority')
        ];
        
        // Remove empty values
        $config = array_filter($config, function($v) {
            return $v !== null && $v !== '';
        });
        
        if (empty($tables)) {
            echo json_encode(['success' => false, 'message' => 'No tables selected']);
            return;
        }
        
        // Validate
        $validation = $this->validate_config($config);
        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }
        
        // Update each table
        $updated = 0;
        foreach ($tables as $table_name) {
            $existing = $this->db->where('table_name', $table_name)
                                ->get('sync_metadata')
                                ->row();
            
            if ($existing) {
                $this->db->where('table_name', $table_name)
                         ->update('sync_metadata', $config);
            } else {
                $config['table_name'] = $table_name;
                $config['sync_enabled'] = 1;
                $this->db->insert('sync_metadata', $config);
                unset($config['table_name']);
            }
            $updated++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Updated configuration for {$updated} tables"
        ]);
    }
    
    /**
     * Reset to defaults (AJAX)
     */
    public function reset_defaults() {
        // Verify CSRF token
        $token = $this->input->post('csrf_token');
        if (!$this->security->verify_csrf_hash($token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        $table_name = $this->input->post('table_name');
        
        $defaults = [
            'conflict_strategy' => 'TIMESTAMP_WINS',
            'real_time_sync' => 0,
            'data_scope' => 'GLOBAL',
            'batch_size' => 100,
            'sync_targets' => null,
            'priority' => 5
        ];
        
        $this->db->where('table_name', $table_name)
                 ->update('sync_metadata', $defaults);
        
        echo json_encode([
            'success' => true,
            'message' => 'Configuration reset to defaults'
        ]);
    }
    
    /**
     * Update global settings (AJAX)
     */
    public function update_global() {
        // Verify CSRF token
        $token = $this->input->post('csrf_token');
        if (!$this->security->verify_csrf_hash($token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }
        
        $settings = [
            'auto_sync_enabled' => $this->input->post('auto_sync_enabled') ? 1 : 0,
            'sync_interval_minutes' => (int) $this->input->post('sync_interval_minutes'),
            'conflict_notification_email' => $this->input->post('conflict_notification_email'),
            'max_retry_attempts' => (int) $this->input->post('max_retry_attempts'),
            'offline_mode_enabled' => $this->input->post('offline_mode_enabled') ? 1 : 0,
            'realtime_sync_enabled' => $this->input->post('realtime_sync_enabled') ? 1 : 0
        ];
        
        // Validate
        if ($settings['sync_interval_minutes'] < 1 || $settings['sync_interval_minutes'] > 1440) {
            echo json_encode(['success' => false, 'message' => 'Sync interval must be between 1 and 1440 minutes']);
            return;
        }
        
        if ($settings['max_retry_attempts'] < 1 || $settings['max_retry_attempts'] > 10) {
            echo json_encode(['success' => false, 'message' => 'Max retry attempts must be between 1 and 10']);
            return;
        }
        
        // Update settings
        foreach ($settings as $key => $value) {
            $this->db->replace('sync_settings', [
                'setting_key' => $key,
                'setting_value' => is_array($value) ? json_encode($value) : $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Global settings updated successfully'
        ]);
    }
    
    /**
     * Get table configuration details (AJAX)
     */
    public function get_table_config($table_name) {
        $config = $this->db->where('table_name', $table_name)
                          ->get('sync_metadata')
                          ->row_array();
        
        if ($config && $config['sync_targets']) {
            $config['sync_targets'] = json_decode($config['sync_targets'], true);
        }
        
        header('Content-Type: application/json');
        echo json_encode($config ?: ['success' => false]);
    }
    
    /**
     * Get sync tables configuration
     * 
     * @return array Tables with their sync configuration
     */
    private function get_sync_tables_config() {
        // Get all tables with sync columns
        $tables_with_sync = $this->db->query("
            SELECT DISTINCT TABLE_NAME as table_name
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND COLUMN_NAME = 'sync_status'
            ORDER BY TABLE_NAME
        ")->result_array();
        
        $tables = [];
        foreach ($tables_with_sync as $t) {
            $table_name = $t['table_name'];
            
            // Get config from sync_metadata
            $config = $this->db->where('table_name', $table_name)
                              ->get('sync_metadata')
                              ->row_array();
            
            // Get pending count
            $pending = $this->db->where('sync_status', 'PENDING')
                               ->count_all_results($table_name);
            
            // Get synced count
            $synced = $this->db->where('sync_status', 'SYNCED')
                              ->count_all_results($table_name);
            
            $tables[] = [
                'table_name' => $table_name,
                'conflict_strategy' => $config['conflict_strategy'] ?? 'TIMESTAMP_WINS',
                'real_time_sync' => $config['real_time_sync'] ?? 0,
                'data_scope' => $config['data_scope'] ?? 'GLOBAL',
                'batch_size' => $config['batch_size'] ?? 100,
                'priority' => $config['priority'] ?? 5,
                'sync_targets' => isset($config['sync_targets']) ? json_decode($config['sync_targets'], true) : null,
                'pending_count' => $pending,
                'synced_count' => $synced,
                'sync_enabled' => $config['sync_enabled'] ?? 1
            ];
        }
        
        return $tables;
    }
    
    /**
     * Get global settings
     * 
     * @return array Global settings
     */
    private function get_global_settings() {
        $settings = $this->db->get('sync_settings')->result_array();
        
        $result = [
            'auto_sync_enabled' => 1,
            'sync_interval_minutes' => 5,
            'conflict_notification_email' => '',
            'max_retry_attempts' => 3,
            'offline_mode_enabled' => 1,
            'realtime_sync_enabled' => 0
        ];
        
        foreach ($settings as $s) {
            $result[$s['setting_key']] = $s['setting_value'];
        }
        
        return $result;
    }
    
    /**
     * Validate configuration
     * 
     * @param array $config Configuration to validate
     * @return array Validation result
     */
    private function validate_config($config) {
        $valid_strategies = ['TIMESTAMP_WINS', 'REMOTE_WINS', 'LOCAL_WINS', 'VERSION_WINS', 'MANUAL_REVIEW'];
        $valid_scopes = ['GLOBAL', 'LOCATION_SPECIFIC', 'REGIONAL'];
        
        if (isset($config['conflict_strategy']) && !in_array($config['conflict_strategy'], $valid_strategies)) {
            return ['valid' => false, 'message' => 'Invalid conflict strategy'];
        }
        
        if (isset($config['data_scope']) && !in_array($config['data_scope'], $valid_scopes)) {
            return ['valid' => false, 'message' => 'Invalid data scope'];
        }
        
        if (isset($config['batch_size']) && ($config['batch_size'] < 1 || $config['batch_size'] > 1000)) {
            return ['valid' => false, 'message' => 'Batch size must be between 1 and 1000'];
        }
        
        if (isset($config['priority']) && ($config['priority'] < 1 || $config['priority'] > 10)) {
            return ['valid' => false, 'message' => 'Priority must be between 1 and 10'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Log configuration change
     * 
     * @param string $table_name Table name
     * @param array $config New configuration
     */
    private function log_config_change($table_name, $config) {
        $this->db->insert('sync_audit_log', [
            'table_name' => 'sync_metadata',
            'record_id' => 0,
            'operation' => 'UPDATE',
            'source_device_id' => 'config',
            'sync_direction' => 'config',
            'old_value' => json_encode(['table' => $table_name]),
            'new_value' => json_encode($config),
            'synced_by' => $this->session->userdata('admin_id'),
            'status' => 'success',
            'synced_at' => date('Y-m-d H:i:s')
        ]);
    }
}
