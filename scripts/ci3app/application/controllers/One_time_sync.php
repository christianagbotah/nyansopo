<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-Time Sync Controller
 * 
 * Provides one-time sync functionality for tables that don't have sync columns.
 * This is useful for syncing existing data or tables that don't need ongoing tracking.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 */
class One_time_sync extends CI_Controller {
    
    private $remote_db;
    private $log_file;
    
    public function __construct() {
        parent::__construct();
        
        $this->load->database();
        $this->log_file = APPPATH . 'logs/one_time_sync_' . date('Y-m-d') . '.log';
        
        // Check authentication (admin only) - skip for CLI mode
        if (!$this->is_cli()) {
            $this->check_auth();
            $this->check_sync_enabled();
        }
    }
    
    /**
     * Check if sync module is enabled
     * 
     * Redirects to dashboard with error message if sync is disabled.
     */
    private function check_sync_enabled() {
        // Load sync configuration
        $this->config->load('sync', TRUE);
        
        // Check database setting first
        $setting = $this->db->get_where('settings', ['type' => 'offline_online_mode'])->row();
        
        if ($setting) {
            $sync_enabled = ($setting->description === '1' || $setting->description === 1);
        } else {
            // Fall back to config file
            $sync_enabled = $this->config->item('sync_enabled', 'sync') ?? TRUE;
        }
        
        if (!$sync_enabled) {
            // Sync module is disabled
            $this->session->set_flashdata('error_message', get_phrase('sync_module_disabled'));
            redirect(site_url('admin/dashboard'), 'refresh');
        }
    }
    
    /**
     * Check if running in CLI mode
     */
    private function is_cli() {
        return (php_sapi_name() === 'cli' || defined('STDIN'));
    }
    
    /**
     * Check admin authentication
     */
    private function check_auth() {
        if (!$this->session->userdata('admin_login')) {
            show_error('Unauthorized access', 403);
        }
        
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        
        if (!$admin || $admin->level >= 4) {
            show_error('Insufficient permissions', 403);
        }
    }
    
    /**
     * Get remote database connection
     */
    private function get_remote_db() {
        if ($this->remote_db) {
            return $this->remote_db;
        }
        
        // Get remote connection settings
        $remote_host = $this->get_setting('remote_host');
        $remote_port = $this->get_setting('remote_port', '3306');
        $remote_user = $this->get_setting('remote_user');
        $remote_pass = $this->get_setting('remote_pass');
        $remote_database = $this->get_setting('remote_database');
        
        if (!$remote_host || !$remote_user || !$remote_database) {
            $this->log_message('Remote database not configured');
            return false;
        }
        
        // Create remote connection
        $config = [
            'dsn'      => '',
            'hostname' => $remote_host . ':' . $remote_port,
            'username' => $remote_user,
            'password' => $remote_pass,
            'database' => $remote_database,
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => FALSE,
            'db_debug' => FALSE,
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'encrypt'  => FALSE,
            'compress' => FALSE,
            'stricton' => FALSE,
            'failover' => []
        ];
        
        try {
            $this->remote_db = $this->load->database($config, TRUE);
            return $this->remote_db;
        } catch (Exception $e) {
            $this->log_message('Failed to connect to remote database: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get setting value
     */
    private function get_setting($key, $default = null) {
        $setting = $this->db->get_where('settings', ['type' => $key])->row();
        return $setting ? $setting->description : $default;
    }
    
    /**
     * Log message to file
     */
    private function log_message($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message\n";
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
        
        if ($this->is_cli()) {
            echo $log_entry;
        }
    }
    
    /**
     * Get list of tables without sync columns
     */
    public function get_tables_without_sync() {
        $tables = $this->db->list_tables();
        $without_sync = [];
        
        foreach ($tables as $table) {
            // Skip sync system tables
            if (strpos($table, 'sync_') === 0) {
                continue;
            }
            
            // Check if table has sync_status column
            $fields = $this->db->list_fields($table);
            if (!in_array('sync_status', $fields)) {
                $without_sync[] = $table;
            }
        }
        
        return $without_sync;
    }
    
    /**
     * Sync a specific table (one-time full sync)
     * 
     * @param string $table Table name
     * @param bool $dry_run If true, only count records without syncing
     * @return array Sync results
     */
    public function sync_table($table, $dry_run = false) {
        $this->log_message("=== Starting one-time sync for table: $table ===");
        
        // Check if table exists
        if (!$this->db->table_exists($table)) {
            $this->log_message("ERROR: Table $table does not exist");
            return ['status' => 'error', 'message' => 'Table does not exist'];
        }
        
        // Get all records from local table
        $records = $this->db->get($table)->result_array();
        $total_records = count($records);
        
        $this->log_message("Found $total_records records in $table");
        
        if ($total_records == 0) {
            $this->log_message("No records to sync");
            return ['status' => 'success', 'synced' => 0, 'failed' => 0];
        }
        
        if ($dry_run) {
            $this->log_message("DRY RUN: Would sync $total_records records");
            return ['status' => 'dry_run', 'would_sync' => $total_records];
        }
        
        // Get remote database connection
        $remote_db = $this->get_remote_db();
        if (!$remote_db) {
            $this->log_message("ERROR: Failed to connect to remote database");
            return ['status' => 'error', 'message' => 'Remote connection failed'];
        }
        
        // Check if remote table exists
        if (!$remote_db->table_exists($table)) {
            $this->log_message("ERROR: Remote table $table does not exist");
            return ['status' => 'error', 'message' => 'Remote table does not exist'];
        }
        
        // Sync records in batches
        $batch_size = 100;
        $batches = array_chunk($records, $batch_size);
        $synced = 0;
        $failed = 0;
        
        foreach ($batches as $batch_num => $batch) {
            $this->log_message("Processing batch " . ($batch_num + 1) . " of " . count($batches));
            
            foreach ($batch as $record) {
                try {
                    // Use REPLACE INTO for idempotent sync
                    $remote_db->replace($table, $record);
                    $synced++;
                } catch (Exception $e) {
                    $failed++;
                    $this->log_message("ERROR syncing record: " . $e->getMessage());
                }
            }
        }
        
        $this->log_message("=== Sync complete: $synced synced, $failed failed ===");
        
        return [
            'status' => $failed > 0 ? 'partial' : 'success',
            'synced' => $synced,
            'failed' => $failed,
            'total' => $total_records
        ];
    }
    
    /**
     * Sync multiple tables
     * 
     * @param array $tables Array of table names
     * @param bool $dry_run If true, only count records without syncing
     * @return array Sync results for all tables
     */
    public function sync_multiple_tables($tables, $dry_run = false) {
        $results = [];
        
        foreach ($tables as $table) {
            $results[$table] = $this->sync_table($table, $dry_run);
        }
        
        return $results;
    }
    
    /**
     * Sync all tables without sync columns
     * 
     * @param bool $dry_run If true, only count records without syncing
     */
    public function sync_all_without_sync_columns($dry_run = false) {
        $this->log_message("=== ONE-TIME SYNC: All tables without sync columns ===");
        
        $tables = $this->get_tables_without_sync();
        $this->log_message("Found " . count($tables) . " tables without sync columns");
        
        if ($dry_run) {
            $this->log_message("DRY RUN MODE - No data will be synced");
        }
        
        $results = $this->sync_multiple_tables($tables, $dry_run);
        
        // Summary
        $total_synced = 0;
        $total_failed = 0;
        
        foreach ($results as $table => $result) {
            if (isset($result['synced'])) {
                $total_synced += $result['synced'];
            }
            if (isset($result['failed'])) {
                $total_failed += $result['failed'];
            }
        }
        
        $this->log_message("=== SUMMARY ===");
        $this->log_message("Tables processed: " . count($tables));
        $this->log_message("Total records synced: $total_synced");
        $this->log_message("Total records failed: $total_failed");
        
        // Return results
        if ($this->is_cli()) {
            return;
        }
        
        // Web interface
        $data = [
            'results' => $results,
            'total_synced' => $total_synced,
            'total_failed' => $total_failed,
            'tables_processed' => count($tables)
        ];
        
        $this->load->view('backend/admin/one_time_sync_results', $data);
    }
    
    /**
     * Show sync interface
     */
    public function index() {
        $data = [
            'tables_without_sync' => $this->get_tables_without_sync()
        ];
        
        $this->load->view('backend/admin/one_time_sync', $data);
    }
}
