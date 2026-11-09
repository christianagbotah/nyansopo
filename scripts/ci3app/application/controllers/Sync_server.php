<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Server Controller
 * 
 * Orchestrates scheduled sync operations between local WAMP server and remote cloud server.
 * This controller is the main entry point for automated and manual sync operations.
 * 
 * @package    School Manager
 * @subpackage Controllers
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: 2.1, 2.2, 3.1
 */
class Sync_server extends CI_Controller {
    
    /**
     * Remote database connection
     * @var object
     */
    private $remote_db;
    
    /**
     * Device ID for this local server
     * @var string
     */
    private $device_id;
    
    /**
     * Sync progress tracking
     * @var array
     */
    private static $sync_progress = [
        'running' => false,
        'progress' => 0,
        'message' => '',
        'current_table' => '',
        'table_progress' => '',
        'complete' => false,
        'final_message' => ''
    ];
    
    /**
     * Constructor
     * 
     * Loads required models, libraries, and checks authentication
     */
    public function __construct() {
        parent::__construct();
        
        // Load dependencies
        $this->load->library('Offline_sync');
        $this->load->database();
        $this->load->config('sync');
        
        // Load bidirectional sync libraries
        $this->load->library('Conflict_resolver');
        $this->load->library('Audit_logger');
        $this->load->library('Location_manager');
        
        // Get device ID from settings
        $this->device_id = $this->get_setting('device_id', 'local-server-001');
        
        // Check authentication (admin only) - skip for CLI mode
        if (!$this->is_cli()) {
            $this->check_auth();
        }
    }
    
    /**
     * Check if sync module is enabled
     * 
     * Redirects to dashboard with error message if sync is disabled.
     * Priority: Database settings > Config file > Default TRUE
     */
    private function check_sync_enabled() {
        // Check database setting first
        $setting = $this->db->get_where('settings', ['type' => 'offline_online_mode'])->row();
        
        if ($setting) {
            $sync_enabled = ($setting->description === '1' || $setting->description === 1);
        } else {
            // Fall back to config file
            $sync_enabled = $this->config->item('sync_enabled', 'sync') ?? TRUE;
        }
        
        if (!$sync_enabled) {
            // Sync module is disabled - show warning in the dashboard
            $this->session->set_flashdata('warning_message', get_phrase('sync_module_disabled_enable_in_settings'));
        }
        
        return $sync_enabled;
    }
    
    /**
     * Check if running in CLI mode
     * 
     * @return bool
     */
    private function is_cli() {
        return (php_sapi_name() === 'cli' || defined('STDIN'));
    }
    
    /**
     * Check admin authentication
     * 
     * Ensures only admin users can access sync operations
     * Redirects to login page if session expired or unauthorized
     * For AJAX requests, returns JSON error instead of redirecting
     */
    private function check_auth() {
        $is_ajax = $this->input->is_ajax_request();
        
        // DEBUG: Log session state
        log_message('debug', 'Sync_server check_auth: admin_login=' . var_export($this->session->userdata('admin_login'), true));
        log_message('debug', 'Sync_server check_auth: admin_id=' . var_export($this->session->userdata('admin_id'), true));
        log_message('debug', 'Sync_server check_auth: login_type=' . var_export($this->session->userdata('login_type'), true));
        
        // Check both admin_login and login_type (required by view system)
        if (!$this->session->userdata('admin_login') || $this->session->userdata('login_type') != 'admin') {
            // Session expired or not logged in
            log_message('debug', 'Sync_server check_auth: FAILED - admin_login not set or login_type not admin');
            if ($is_ajax) {
                // For AJAX requests, return JSON error
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Session expired. Please login again.',
                    'redirect' => site_url('login')
                ]);
                exit;
            } else {
                // For regular requests, redirect to login
                redirect(site_url('login'), 'refresh');
                exit;
            }
        }
        
        // Note: Admin level permission check removed
        // The navigation menu already controls visibility of sync dashboard based on admin level
        // All authenticated admin users can access sync AJAX operations (push/pull/status)
        
        log_message('debug', 'Sync_server check_auth: PASSED');
    }
    
    /**
     * Get setting value from settings table
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value
     */
    private function get_setting($key, $default = null) {
        $setting = $this->db->get_where('settings', ['type' => $key])->row();
        return $setting ? $setting->description : $default;
    }
    
    /**
     * Update setting value in settings table
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success status
     */
    private function update_setting($key, $value) {
        $exists = $this->db->get_where('settings', ['type' => $key])->row();
        
        if ($exists) {
            return $this->db->where('type', $key)
                           ->update('settings', ['description' => $value]);
        } else {
            return $this->db->insert('settings', [
                'type' => $key,
                'description' => $value
            ]);
        }
    }
    
    /**
     * Log message to file and database
     * 
     * @param string $message Log message
     * @param string $level Log level (info, error, warning)
     */
    private function log_message_custom($message, $level = 'info') {
        // Log to file
        log_message($level, "[Sync Server] $message");
        
        // Log to console if CLI
        if ($this->is_cli()) {
            echo "[" . date('Y-m-d H:i:s') . "] [$level] $message\n";
        }
    }
    
    /**
     * Check internet connectivity (pre-flight check)
     * 
     * Verifies internet connection by pinging external DNS or making HTTP request
     * This is the FIRST check in the sequential pre-flight system
     * 
     * Requirements: 2.1
     * Task: 3.2
     * 
     * @return array ['passed' => bool, 'error_message' => string]
     */
    private function check_internet_connectivity() {
        $this->log_message_custom('Pre-flight Check 1: Internet Connectivity');
        
        // Try multiple methods to verify internet connectivity
        $methods_tried = [];
        
        // Method 1: Try curl to reliable endpoints
        if (function_exists('curl_init')) {
            $test_urls = [
                'http://www.google.com',
                'http://8.8.8.8', // Google DNS
                'http://1.1.1.1'  // Cloudflare DNS
            ];
            
            foreach ($test_urls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                $methods_tried[] = "curl to $url: " . ($http_code > 0 ? 'success' : 'failed');
                
                if ($http_code > 0) {
                    $this->log_message_custom('Internet connectivity: PASSED (curl to ' . $url . ')');
                    return ['passed' => true, 'error_message' => ''];
                }
            }
        }
        
        // Method 2: Try fsockopen to DNS servers
        if (function_exists('fsockopen')) {
            $test_hosts = [
                ['host' => '8.8.8.8', 'port' => 53],  // Google DNS
                ['host' => '1.1.1.1', 'port' => 53],  // Cloudflare DNS
                ['host' => 'www.google.com', 'port' => 80]
            ];
            
            foreach ($test_hosts as $test) {
                $connection = @fsockopen($test['host'], $test['port'], $errno, $errstr, 2);
                $methods_tried[] = "fsockopen to {$test['host']}:{$test['port']}: " . ($connection ? 'success' : 'failed');
                
                if ($connection) {
                    fclose($connection);
                    $this->log_message_custom('Internet connectivity: PASSED (fsockopen to ' . $test['host'] . ')');
                    return ['passed' => true, 'error_message' => ''];
                }
            }
        }
        
        // All methods failed
        $this->log_message_custom('Internet connectivity: FAILED (all methods tried)', 'error');
        $this->log_message_custom('Methods tried: ' . implode(', ', $methods_tried), 'info');
        
        return [
            'passed' => false,
            'error_message' => 'No internet connection detected. Please check your network connection and try again.'
        ];
    }
    
    /**
     * Check remote database connection (pre-flight check)
     * 
     * Tests database credentials and network reachability to remote server
     * This is the SECOND check in the sequential pre-flight system
     * Returns detailed error information for different failure types
     * 
     * Requirements: 2.1, 2.2
     * Task: 3.2
     * 
     * @return array ['passed' => bool, 'error_message' => string, 'error_type' => string, 'diagnostics' => array]
     */
    private function check_remote_connection() {
        $this->log_message_custom('Pre-flight Check 2: Remote Database Connection');
        
        // Get remote connection settings
        $host = $this->get_setting('remote_db_host');
        $port = $this->get_setting('remote_db_port', 3306);
        $username = $this->get_setting('remote_db_username');
        $password = $this->get_setting('remote_db_password');
        $database = $this->get_setting('remote_db_name');
        
        // Validate settings are configured
        if (empty($host) || empty($username) || empty($database)) {
            $this->log_message_custom('Remote connection: FAILED - Settings not configured', 'error');
            return [
                'passed' => false,
                'error_message' => 'Remote database settings not configured. Please configure sync settings first.',
                'error_type' => 'not_configured',
                'diagnostics' => [
                    'host_configured' => !empty($host),
                    'username_configured' => !empty($username),
                    'database_configured' => !empty($database),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        $diagnostics = [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Step 1: Check network reachability (socket test)
        $this->log_message_custom("Testing network reachability to $host:$port");
        $socket = @fsockopen($host, $port, $errno, $errstr, 5);
        
        if (!$socket) {
            $this->log_message_custom("Network reachability: FAILED - $errstr ($errno)", 'error');
            $diagnostics['socket_error'] = $errstr;
            $diagnostics['socket_errno'] = $errno;
            
            // Determine specific error type
            $error_type = 'network_unreachable';
            $error_message = "Cannot reach remote server at $host:$port. ";
            
            if ($errno == 110 || $errno == 10060) { // Connection timeout
                $error_type = 'connection_timeout';
                $error_message .= 'Connection timed out. The server may be down or firewall is blocking the connection.';
            } elseif ($errno == 111 || $errno == 10061) { // Connection refused
                $error_type = 'connection_refused';
                $error_message .= 'Connection refused. MySQL service may not be running or port is incorrect.';
            } else {
                $error_message .= "Network error: $errstr";
            }
            
            return [
                'passed' => false,
                'error_message' => $error_message,
                'error_type' => $error_type,
                'diagnostics' => $diagnostics
            ];
        }
        
        fclose($socket);
        $this->log_message_custom('Network reachability: PASSED');
        $diagnostics['network_reachable'] = true;
        
        // Step 2: Test database authentication
        $this->log_message_custom("Testing database authentication");
        
        try {
            // Attempt to create mysqli connection
            $mysqli = @new mysqli($host, $username, $password, $database, $port);
            
            // Check for connection errors
            if ($mysqli->connect_error) {
                $error_code = $mysqli->connect_errno;
                $error_msg = $mysqli->connect_error;
                
                $this->log_message_custom("Database connection: FAILED - $error_msg ($error_code)", 'error');
                $diagnostics['mysql_error'] = $error_msg;
                $diagnostics['mysql_errno'] = $error_code;
                
                // Determine specific error type
                $error_type = 'authentication_failed';
                $error_message = '';
                
                if ($error_code == 1045) { // Access denied
                    $error_type = 'authentication_failed';
                    $error_message = "Authentication failed for user '$username'. Please check your database credentials.";
                } elseif ($error_code == 1049) { // Unknown database
                    $error_type = 'database_not_found';
                    $error_message = "Database '$database' does not exist on the remote server.";
                } elseif ($error_code == 2002) { // Can't connect
                    $error_type = 'connection_failed';
                    $error_message = "Cannot connect to MySQL server at $host:$port. Server may be down.";
                } elseif ($error_code == 2003) { // Can't connect
                    $error_type = 'connection_refused';
                    $error_message = "Connection refused by MySQL server at $host:$port.";
                } else {
                    $error_message = "Database connection error: $error_msg";
                }
                
                return [
                    'passed' => false,
                    'error_message' => $error_message,
                    'error_type' => $error_type,
                    'diagnostics' => $diagnostics
                ];
            }
            
            // Connection successful - verify it works
            $result = $mysqli->query('SELECT 1');
            if (!$result) {
                $this->log_message_custom("Database query test: FAILED", 'error');
                $mysqli->close();
                return [
                    'passed' => false,
                    'error_message' => 'Connected to database but cannot execute queries. Database may have issues.',
                    'error_type' => 'query_failed',
                    'diagnostics' => $diagnostics
                ];
            }
            
            $mysqli->close();
            $this->log_message_custom('Remote database connection: PASSED');
            $diagnostics['connection_successful'] = true;
            
            return [
                'passed' => true,
                'error_message' => '',
                'error_type' => '',
                'diagnostics' => $diagnostics
            ];
            
        } catch (Exception $e) {
            $this->log_message_custom('Database connection exception: ' . $e->getMessage(), 'error');
            $diagnostics['exception'] = $e->getMessage();
            
            return [
                'passed' => false,
                'error_message' => 'Database connection failed: ' . $e->getMessage(),
                'error_type' => 'connection_exception',
                'diagnostics' => $diagnostics
            ];
        }
    }
    
    /**
     * Check system readiness for sync (pre-flight check)
     * 
     * Validates system state: disk space, sync table integrity, required permissions
     * This is the THIRD check in the sequential pre-flight system
     * 
     * Requirements: 2.1
     * Task: 3.2
     * 
     * @return array ['passed' => bool, 'error_message' => string, 'warnings' => array]
     */
    private function check_system_readiness() {
        $this->log_message_custom('Pre-flight Check 3: System Readiness');
        
        $warnings = [];
        $errors = [];
        
        // Check 1: Disk space
        $disk_status = $this->check_disk_space();
        if ($disk_status['status'] == 'critical') {
            $errors[] = $disk_status['message'];
            $this->log_message_custom('Disk space: CRITICAL - ' . $disk_status['message'], 'error');
        } elseif ($disk_status['status'] == 'warning') {
            $warnings[] = $disk_status['message'];
            $this->log_message_custom('Disk space: WARNING - ' . $disk_status['message'], 'warning');
        } else {
            $this->log_message_custom('Disk space: OK (' . $disk_status['free_space_formatted'] . ' free)');
        }
        
        // Check 2: Sync metadata table integrity
        $this->log_message_custom('Checking sync_metadata table integrity');
        if ($this->db->table_exists('sync_metadata')) {
            $count = $this->db->count_all('sync_metadata');
            $this->log_message_custom("sync_metadata table: OK ($count entries)");
        } else {
            $warnings[] = 'sync_metadata table does not exist. Sync tracking may be limited.';
            $this->log_message_custom('sync_metadata table: MISSING', 'warning');
        }
        
        // Check 3: Required settings exist
        $this->log_message_custom('Checking required settings');
        $required_settings = ['device_id', 'remote_db_host', 'remote_db_username', 'remote_db_name'];
        $missing_settings = [];
        
        foreach ($required_settings as $setting) {
            $value = $this->get_setting($setting);
            if (empty($value)) {
                $missing_settings[] = $setting;
            }
        }
        
        if (!empty($missing_settings)) {
            $errors[] = 'Missing required settings: ' . implode(', ', $missing_settings);
            $this->log_message_custom('Settings check: FAILED - Missing: ' . implode(', ', $missing_settings), 'error');
        } else {
            $this->log_message_custom('Settings check: OK');
        }
        
        // Check 4: Database write permissions
        $this->log_message_custom('Checking database write permissions');
        try {
            // Try to update a setting to test write access
            $test_result = $this->db->where('type', 'last_sync_status')
                                    ->update('settings', ['description' => $this->get_setting('last_sync_status')]);
            
            if ($test_result === FALSE) {
                $warnings[] = 'Database write test failed. May have permission issues.';
                $this->log_message_custom('Database write permissions: WARNING', 'warning');
            } else {
                $this->log_message_custom('Database write permissions: OK');
            }
        } catch (Exception $e) {
            $warnings[] = 'Database write test exception: ' . $e->getMessage();
            $this->log_message_custom('Database write permissions: EXCEPTION - ' . $e->getMessage(), 'warning');
        }
        
        // Check 5: PHP extensions required for sync
        $this->log_message_custom('Checking required PHP extensions');
        $required_extensions = ['mysqli', 'json'];
        $missing_extensions = [];
        
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
        
        if (!empty($missing_extensions)) {
            $errors[] = 'Missing required PHP extensions: ' . implode(', ', $missing_extensions);
            $this->log_message_custom('PHP extensions: FAILED - Missing: ' . implode(', ', $missing_extensions), 'error');
        } else {
            $this->log_message_custom('PHP extensions: OK');
        }
        
        // Determine overall status
        if (!empty($errors)) {
            $this->log_message_custom('System readiness: FAILED', 'error');
            return [
                'passed' => false,
                'error_message' => implode(' ', $errors),
                'warnings' => $warnings
            ];
        }
        
        if (!empty($warnings)) {
            $this->log_message_custom('System readiness: PASSED (with warnings)');
        } else {
            $this->log_message_custom('System readiness: PASSED');
        }
        
        return [
            'passed' => true,
            'error_message' => '',
            'warnings' => $warnings
        ];
    }
    
    /**
     * Run comprehensive pre-flight checks before sync
     * 
     * Executes checks in sequence: Internet → Database → System readiness
     * ABORTS immediately if any check fails
     * Does NOT proceed to sync or show progress if checks fail
     * 
     * Requirements: 2.1, 2.2, 2.8
     * Task: 3.2
     * 
     * @return array ['passed' => bool, 'failed_check' => string, 'error_message' => string, 'corrective_action' => string]
     */
    public function run_preflight_checks() {
        $this->log_message_custom('=== STARTING PRE-FLIGHT CHECKS ===');
        $start_time = microtime(true);
        
        // CHECK 1: Internet Connectivity
        $internet_check = $this->check_internet_connectivity();
        if (!$internet_check['passed']) {
            $duration = round(microtime(true) - $start_time, 2);
            $this->log_message_custom("=== PRE-FLIGHT CHECKS FAILED (Internet) in {$duration}s ===", 'error');
            
            return [
                'passed' => false,
                'failed_check' => 'internet_connectivity',
                'error_message' => $internet_check['error_message'],
                'corrective_action' => 'Check your internet connection. Ensure network cable is connected or WiFi is enabled. Try accessing a website to verify internet connectivity.'
            ];
        }
        
        // CHECK 2: Remote Database Connection
        $db_check = $this->check_remote_connection();
        if (!$db_check['passed']) {
            $duration = round(microtime(true) - $start_time, 2);
            $this->log_message_custom("=== PRE-FLIGHT CHECKS FAILED (Database) in {$duration}s ===", 'error');
            
            // Determine corrective action based on error type
            $corrective_action = '';
            switch ($db_check['error_type']) {
                case 'not_configured':
                    $corrective_action = 'Configure remote database settings in Sync Settings page. Enter host, username, password, and database name.';
                    break;
                case 'network_unreachable':
                case 'connection_timeout':
                    $corrective_action = 'Verify remote server IP address and port. Check firewall settings. Ensure MySQL port (3306) is open.';
                    break;
                case 'connection_refused':
                    $corrective_action = 'Verify MySQL service is running on remote server. Check that MySQL is configured to accept remote connections.';
                    break;
                case 'authentication_failed':
                    $corrective_action = 'Verify database username and password in Sync Settings. Ensure user has remote access permissions on MySQL server.';
                    break;
                case 'database_not_found':
                    $corrective_action = 'Verify database name is correct. Ensure the database exists on the remote server.';
                    break;
                default:
                    $corrective_action = 'Check sync settings and remote server configuration. Review error diagnostics for details.';
            }
            
            return [
                'passed' => false,
                'failed_check' => 'remote_database_connection',
                'error_message' => $db_check['error_message'],
                'corrective_action' => $corrective_action,
                'diagnostics' => $db_check['diagnostics']
            ];
        }
        
        // CHECK 3: System Readiness
        $system_check = $this->check_system_readiness();
        if (!$system_check['passed']) {
            $duration = round(microtime(true) - $start_time, 2);
            $this->log_message_custom("=== PRE-FLIGHT CHECKS FAILED (System) in {$duration}s ===", 'error');
            
            return [
                'passed' => false,
                'failed_check' => 'system_readiness',
                'error_message' => $system_check['error_message'],
                'corrective_action' => 'Resolve system issues: Free up disk space if low. Verify database permissions. Install missing PHP extensions.',
                'warnings' => $system_check['warnings']
            ];
        }
        
        // All checks passed
        $duration = round(microtime(true) - $start_time, 2);
        $this->log_message_custom("=== PRE-FLIGHT CHECKS PASSED in {$duration}s ===");
        
        $result = [
            'passed' => true,
            'failed_check' => '',
            'error_message' => '',
            'corrective_action' => ''
        ];
        
        // Include warnings if any
        if (!empty($system_check['warnings'])) {
            $result['warnings'] = $system_check['warnings'];
            $this->log_message_custom('Note: System has warnings but sync can proceed: ' . implode('; ', $system_check['warnings']), 'warning');
        }
        
        return $result;
    }
    
    /**
     * Check if internet connection is available
     * 
     * Tests connection to remote server by attempting to open a socket
     * If remote server not configured, tests connection to common public servers
     * 
     * Requirements: 2.2, 3.2
     * 
     * @return bool True if online, false if offline
     */
    public function is_online() {
        $host = $this->get_setting('remote_db_host');
        $port = $this->get_setting('remote_db_port', 3306);
        
        // Method 1: Try curl if available (more reliable on Windows)
        if (function_exists('curl_init')) {
            // If remote host is configured, check it first
            if ($host) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://$host");
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code > 0) {
                    $this->log_message_custom('Internet connection: ONLINE (remote server reachable via curl)');
                    return true;
                }
            }
            
            // Try public servers
            $test_urls = [
                'http://www.google.com',
                'http://www.cloudflare.com',
                'http://www.microsoft.com'
            ];
            
            foreach ($test_urls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code > 0) {
                    $this->log_message_custom('Internet connection: ONLINE (via curl to ' . $url . ')');
                    return true;
                }
            }
        }
        
        // Method 2: Try fsockopen (fallback)
        if (function_exists('fsockopen')) {
            // If remote host is configured, check connection to it
            if ($host) {
                $connection = @fsockopen($host, $port, $errno, $errstr, 3);
                
                if ($connection) {
                    fclose($connection);
                    $this->log_message_custom('Internet connection: ONLINE (remote server reachable via fsockopen)');
                    return true;
                }
                
                $this->log_message_custom("Remote server not reachable: $errstr", 'info');
            }
            
            // Try multiple reliable public servers
            $test_hosts = [
                ['host' => 'www.google.com', 'port' => 80],
                ['host' => '8.8.8.8', 'port' => 53], // Google DNS
                ['host' => '1.1.1.1', 'port' => 53]  // Cloudflare DNS
            ];
            
            foreach ($test_hosts as $test) {
                $connection = @fsockopen($test['host'], $test['port'], $errno, $errstr, 2);
                
                if ($connection) {
                    fclose($connection);
                    $this->log_message_custom('Internet connection: ONLINE (via fsockopen to ' . $test['host'] . ')');
                    return true;
                }
            }
        }
        
        // Method 3: Try file_get_contents with timeout (last resort)
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true
            ]
        ]);
        
        $result = @file_get_contents('http://www.google.com', false, $context);
        if ($result !== false) {
            $this->log_message_custom('Internet connection: ONLINE (via file_get_contents)');
            return true;
        }
        
        $this->log_message_custom("Internet connection: OFFLINE (all methods failed)", 'info');
        return false;
    }

    /**
     * Main automatic sync entry point
     * 
     * Called by Windows Task Scheduler or manual trigger
     * Orchestrates the entire sync process
     * 
     * Requirements: 2.1, 2.2, 3.1, 3.2
     * 
     * @return array Sync results
     */
    public function auto_sync() {
        // Increase execution time limit for sync operations
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        
        $start_time = microtime(true);
        $this->log_message_custom('=== AUTO SYNC STARTED ===');
        
        // Load Sync_notifier library
        $this->load->library('Sync_notifier');
        
        // Check if sync is enabled
        $sync_enabled = $this->get_setting('sync_enabled', '1');
        if ($sync_enabled != '1') {
            $this->log_message_custom('Sync is disabled in settings', 'info');
            return ['status' => 'disabled', 'message' => 'Sync is disabled'];
        }
        
        // ====== START: PRE-FLIGHT CHECKS (Task 3.7) ======
        $this->log_message_custom('Running pre-flight checks...');
        $preflight_result = $this->run_preflight_checks();
        
        if (!$preflight_result['passed']) {
            // Pre-flight check failed - ABORT sync
            $this->log_message_custom('Pre-flight checks failed: ' . $preflight_result['error_message'], 'error');
            
            // Determine last_sync_status based on failed check
            $status_mapping = [
                'internet_connectivity' => 'failed_no_internet',
                'remote_database_connection' => 'failed_connection',
                'system_readiness' => 'failed_system'
            ];
            
            $last_sync_status = $status_mapping[$preflight_result['failed_check']] ?? 'failed';
            
            // Update settings
            $this->db->query("UPDATE settings SET description = '{$last_sync_status}', sync_status = 'SYNCED' WHERE type = 'last_sync_status'");
            
            // Store error details
            $error_data = [
                'error_message' => $preflight_result['error_message'],
                'failed_check' => $preflight_result['failed_check'],
                'corrective_action' => $preflight_result['corrective_action'],
                'diagnostics' => $preflight_result['diagnostics'] ?? [],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $error_json = $this->db->escape_str(json_encode($error_data));
            $this->db->query("UPDATE settings SET description = '{$error_json}', sync_status = 'SYNCED' WHERE type = 'last_sync_error'");
            
            // Track failure with Sync_notifier
            $this->sync_notifier->track_sync_attempt($last_sync_status, [
                'error_message' => $preflight_result['error_message'],
                'error_type' => $preflight_result['failed_check'],
                'diagnostic_info' => $preflight_result['diagnostics'] ?? []
            ]);
            
            return [
                'status' => 'error',
                'error' => 'Pre-flight check failed',
                'failed_check' => $preflight_result['failed_check'],
                'message' => $preflight_result['error_message'],
                'action' => $preflight_result['corrective_action']
            ];
        }
        
        $this->log_message_custom('Pre-flight checks: PASSED');
        // ====== END: PRE-FLIGHT CHECKS ======
        
        // Start progress tracking (Task 3.5)
        $this->start_progress_tracking();
        
        // Perform sync
        try {
            $results = $this->sync_all_tables();
            
            // Complete progress tracking (Task 3.5)
            $this->complete_progress_tracking('Sync completed successfully');
            
            // Use direct SQL to update BOTH settings AND mark as SYNCED in one operation
            $current_time = date('Y-m-d H:i:s');
            $this->db->query("UPDATE settings SET description = '$current_time', sync_status = 'SYNCED' WHERE type = 'last_sync_time'");
            $this->db->query("UPDATE settings SET description = 'success', sync_status = 'SYNCED' WHERE type = 'last_sync_status'");
            $this->db->query("UPDATE settings SET description = '', sync_status = 'SYNCED' WHERE type = 'last_sync_error'");
            
            // Track success with Sync_notifier (resets failure counter)
            $this->sync_notifier->track_sync_attempt('success');
            
            $duration = round(microtime(true) - $start_time, 2);
            $this->log_message_custom("=== AUTO SYNC COMPLETED in {$duration}s ===");
            
            return [
                'status' => 'success',
                'results' => $results,
                'duration' => $duration,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $this->log_message_custom('Sync failed: ' . $e->getMessage(), 'error');
            
            // Complete progress tracking with error (Task 3.5)
            $this->complete_progress_tracking('Sync failed: ' . $e->getMessage());
            
            // Use direct SQL to update setting AND mark as SYNCED in one operation
            $this->db->query("UPDATE settings SET description = 'failed', sync_status = 'SYNCED' WHERE type = 'last_sync_status'");
            
            $error_data = [
                'error_message' => $e->getMessage(),
                'error_type' => 'sync_exception',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $error_json = $this->db->escape_str(json_encode($error_data));
            $this->db->query("UPDATE settings SET description = '{$error_json}', sync_status = 'SYNCED' WHERE type = 'last_sync_error'");
            
            // Track failure with Sync_notifier
            $this->sync_notifier->track_sync_attempt('failed', [
                'error_message' => $e->getMessage(),
                'error_type' => 'sync_exception'
            ]);
            
            // Check if it's an authentication error
            if (strpos($e->getMessage(), 'Access denied') !== false || 
                strpos($e->getMessage(), 'authentication') !== false) {
                $this->handle_auth_error($e->getMessage());
            }
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Sync all tables in dependency order
     * 
     * Requirements: 2.1, 2.3
     * 
     * @return array Results per table
     */
    private function sync_all_tables() {
        // Check if bidirectional sync is enabled
        $bidirectional_enabled = $this->config->item('bidirectional_sync_enabled');
        
        if ($bidirectional_enabled) {
            return $this->sync_bidirectional();
        }
        
        // Legacy one-way sync (push only)
        $tables = $this->get_sync_order();
        $results = [];
        
        $this->log_message_custom('Syncing ' . count($tables) . ' tables (one-way mode)');
        
        foreach ($tables as $table) {
            $this->log_message_custom("Syncing table: $table");
            $results[$table] = $this->sync_table($table);
        }
        
        return $results;
    }
    
    /**
     * Perform bidirectional sync (pull then push)
     * 
     * Requirements: 1, 2
     * Task: 2.6
     * 
     * @return array Results with pull and push phases
     */
    private function sync_bidirectional() {
        $this->log_message_custom('=== BIDIRECTIONAL SYNC MODE ===');
        
        $results = [
            'mode' => 'bidirectional',
            'pull_phase' => [],
            'push_phase' => [],
            'deletion_pull' => [],
            'deletion_push' => []
        ];
        
        // PHASE 0: Snapshot local PENDING records before pull
        // This ensures we push local changes even if pull phase updates them
        $this->log_message_custom('--- PHASE 0: Snapshot Local PENDING Records ---');
        $local_pending_snapshot = $this->snapshot_local_pending_records();
        $this->log_message_custom("Captured " . count($local_pending_snapshot) . " tables with pending records");
        
        // PHASE 1: PULL (Remote → Local)
        $this->log_message_custom('--- PHASE 1: PULL (Remote → Local) ---');
        $pull_start = microtime(true);
        
        try {
            $results['pull_phase'] = $this->pull_all_tables();
            $results['pull_duration'] = round(microtime(true) - $pull_start, 2);
            $this->log_message_custom("Pull phase completed in {$results['pull_duration']}s");
        } catch (Exception $e) {
            $this->log_message_custom('Pull phase failed: ' . $e->getMessage(), 'error');
            $results['pull_phase'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // PHASE 1.5: APPLY REMOTE DELETIONS (Remote Deletions → Local)
        $this->log_message_custom('--- PHASE 1.5: APPLY REMOTE DELETIONS ---');
        $deletion_pull_start = microtime(true);
        
        try {
            $results['deletion_pull'] = $this->apply_remote_deletions();
            $results['deletion_pull_duration'] = round(microtime(true) - $deletion_pull_start, 2);
            $this->log_message_custom("Deletion pull phase completed in {$results['deletion_pull_duration']}s - " .
                                    "Applied: {$results['deletion_pull']['applied']}, " .
                                    "Conflicts: {$results['deletion_pull']['conflicts']}");
        } catch (Exception $e) {
            $this->log_message_custom('Deletion pull phase failed: ' . $e->getMessage(), 'error');
            $results['deletion_pull'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // PHASE 2: PUSH (Local → Remote)
        // Push the records that were PENDING before pull phase
        $this->log_message_custom('--- PHASE 2: PUSH (Local → Remote) ---');
        $push_start = microtime(true);
        
        try {
            $results['push_phase'] = $this->push_all_tables_bidirectional($local_pending_snapshot);
            $results['push_duration'] = round(microtime(true) - $push_start, 2);
            $this->log_message_custom("Push phase completed in {$results['push_duration']}s");
        } catch (Exception $e) {
            $this->log_message_custom('Push phase failed: ' . $e->getMessage(), 'error');
            $results['push_phase'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        // PHASE 2.5: SYNC LOCAL DELETIONS (Local Deletions → Remote)
        $this->log_message_custom('--- PHASE 2.5: SYNC LOCAL DELETIONS ---');
        $deletion_push_start = microtime(true);
        
        try {
            $results['deletion_push'] = $this->sync_deletions();
            $results['deletion_push_duration'] = round(microtime(true) - $deletion_push_start, 2);
            $this->log_message_custom("Deletion push phase completed in {$results['deletion_push_duration']}s - " .
                                    "Synced: {$results['deletion_push']['synced']}, " .
                                    "Failed: {$results['deletion_push']['failed']}");
        } catch (Exception $e) {
            $this->log_message_custom('Deletion push phase failed: ' . $e->getMessage(), 'error');
            $results['deletion_push'] = ['status' => 'error', 'message' => $e->getMessage()];
        }
        
        return $results;
    }
    
    /**
     * Pull changes from remote server to local server
     * 
     * Requirements: 1, 2
     * Task: 2.4
     * 
     * @return array Results per table
     */
    private function pull_all_tables() {
        $tables = $this->get_sync_order();
        $results = [];
        
        $this->log_message_custom('Pulling ' . count($tables) . ' tables from remote');
        
        foreach ($tables as $table) {
            $this->log_message_custom("Pulling table: $table");
            $results[$table] = $this->pull_table($table);
        }
        
        return $results;
    }
    
    /**
     * Snapshot local PENDING records before pull phase
     * Returns array of table => [record_ids]
     */
    private function snapshot_local_pending_records() {
        $tables = $this->get_sync_order();
        $snapshot = [];
        
        foreach ($tables as $table) {
            // Check if table has sync columns
            if (!$this->table_has_sync_columns($table)) {
                continue;
            }
            
            // Get primary key
            $primary_key = $this->get_primary_key($table);
            if (!$primary_key) {
                continue;
            }
            
            // Get all PENDING record IDs
            $pending_ids = $this->db->select($primary_key)
                                   ->where('sync_status', 'PENDING')
                                   ->get($table)
                                   ->result_array();
            
            if (!empty($pending_ids)) {
                $snapshot[$table] = array_column($pending_ids, $primary_key);
                $this->log_message_custom("Table $table: " . count($snapshot[$table]) . " pending records");
            }
        }
        
        return $snapshot;
    }
    
    /**
     * Push tables using snapshot of PENDING records (for bidirectional sync)
     * This ensures we push records that were PENDING before pull phase
     */
    private function push_all_tables_bidirectional($pending_snapshot) {
        $tables = $this->get_sync_order();
        $results = [];
        
        $this->log_message_custom('=== PUSH ALL TABLES (Bidirectional Mode) ===');
        $this->log_message_custom('Pushing ' . count($tables) . ' tables to remote');
        $this->log_message_custom('Using snapshot of records that were PENDING before pull phase');
        
        foreach ($tables as $table) {
            // Skip if no pending records in snapshot
            if (!isset($pending_snapshot[$table]) || empty($pending_snapshot[$table])) {
                $this->log_message_custom("Table $table: No pending records in snapshot, skipping");
                $results[$table] = ['synced' => 0, 'failed' => 0, 'status' => 'no_pending'];
                continue;
            }
            
            $this->log_message_custom("Pushing table: $table (bidirectional mode)");
            $results[$table] = $this->sync_table_bidirectional($table, $pending_snapshot[$table]);
        }
        
        $this->log_message_custom('=== PUSH ALL TABLES COMPLETED ===');
        return $results;
    }
    
    /**
     * Push changes from local server to remote server
     * 
     * Requirements: 1, 2
     * Task: 2.5
     * 
     * @return array Results per table
     */
    private function push_all_tables() {
        $tables = $this->get_sync_order();
        $results = [];
        
        $this->log_message_custom('=== PUSH ALL TABLES (Push-Only Mode) ===');
        $this->log_message_custom('Pushing ' . count($tables) . ' tables to remote');
        $this->log_message_custom('Bidirectional sync is DISABLED for this operation');

        // Calculate progress increments for real-time updates
        $total_tables = count($tables);
        $progress_per_table = 80 / $total_tables; // 10% to 90% range
        $current_progress = 10; // Starting from 10%
        
        foreach ($tables as $index => $table) {
            $table_number = $index + 1;
            $this->log_message_custom("Pushing table: $table (push-only) [$table_number/$total_tables]");
            
            // Update progress before syncing table
            $this->update_sync_progress(
                $current_progress, 
                "Syncing $table...", 
                $table, 
                "$table_number/$total_tables",
                false,
                ''
            );
            
            $results[$table] = $this->sync_table($table); // Use existing sync_table for push
            
            // Increment progress after each table
            $current_progress += $progress_per_table;
        }
        
        // DELETION FIX: Sync pending deletions to remote
        $this->log_message_custom('Syncing pending deletions to remote...');
        $this->update_sync_progress(90, 'Syncing deletions...', '', '', false, '');
        
        try {
            $deletion_results = $this->sync_deletions();
            $results['deletions'] = $deletion_results;
            $this->log_message_custom("Deletion sync completed: {$deletion_results['synced']} synced, {$deletion_results['failed']} failed", 
                                     $deletion_results['failed'] > 0 ? 'warning' : 'info');
        } catch (Exception $e) {
            $this->log_message_custom('Failed to sync deletions: ' . $e->getMessage(), 'error');
            $results['deletions'] = [
                'synced' => 0,
                'failed' => 0,
                'skipped' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
        
        $this->log_message_custom('=== PUSH ALL TABLES COMPLETED ===');
        return $results;
    }
    
    /**
     * Pull changes for a single table from remote
     * 
     * Requirements: 1, 2, 16
     * Task: 2.4, 9.1
     * 
     * @param string $table Table name
     * @return array Pull results
     */
    private function pull_table($table) {
        try {
            // Check if table has sync columns
            if (!$this->table_has_sync_columns($table)) {
                $this->log_message_custom("Table $table does not have sync columns, skipping", 'warning');
                return ['pulled' => 0, 'conflicts' => 0, 'status' => 'skipped'];
            }
            
            // Get remote database connection
            try {
                $remote_db = $this->get_remote_db();
            } catch (Exception $e) {
                // Catch all exception types (DatabaseConnectionException, AuthenticationException, NetworkException, NoInternetException)
                $error_msg = 'Failed to connect to remote database: ' . $e->getMessage();
                $this->log_message_custom($error_msg, 'error');
                
                // Log exception details if available
                if (method_exists($e, 'getDiagnostics')) {
                    $diagnostics = $e->getDiagnostics();
                    $this->log_message_custom('Connection error diagnostics: ' . json_encode($diagnostics), 'error');
                }
                
                $this->handle_pull_connection_error($table, $error_msg);
                throw new Exception($error_msg);
            }
            
            // Get last sync timestamp for this table
            $last_sync = $this->get_setting("last_pull_sync_$table", '1970-01-01 00:00:00');
            
            // Get remote changes since last sync
            $remote_changes = $this->get_remote_changes($remote_db, $table, $last_sync);
            
            if (empty($remote_changes)) {
                $this->log_message_custom("No remote changes for $table");
                return ['pulled' => 0, 'conflicts' => 0, 'status' => 'no_changes'];
            }
            
            $this->log_message_custom("Found " . count($remote_changes) . " remote changes in $table");
            
            // Load libraries
            $this->load->library('Conflict_resolver');
            $this->load->library('Audit_logger');
            $this->load->library('Sync_notifier');
            
            $pulled = 0;
            $conflicts = 0;
            $errors = 0;
            $error_details = [];
            
            // Process each remote change
            foreach ($remote_changes as $remote_record) {
                try {
                    $result = $this->apply_remote_change($table, $remote_record);
                    
                    if ($result['status'] === 'applied') {
                        $pulled++;
                    } elseif ($result['status'] === 'conflict') {
                        $conflicts++;
                        
                        // Send conflict notification if MANUAL_REVIEW
                        if (isset($result['resolution']) && $result['resolution']['resolution'] === 'MANUAL_REVIEW') {
                            $this->sync_notifier->notify_conflict([
                                'table_name' => $table,
                                'record_id' => $remote_record[$this->get_primary_key($table)] ?? 'unknown',
                                'conflict_strategy' => 'MANUAL_REVIEW',
                                'local_value' => $result['resolution']['local_data'] ?? null,
                                'remote_value' => $result['resolution']['remote_data'] ?? null
                            ]);
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    $error_msg = $e->getMessage();
                    $error_details[] = $error_msg;
                    $this->log_message_custom("Error applying remote change in $table: " . $error_msg, 'error');
                    
                    // Log to audit trail
                    $this->audit_logger->log(
                        $table,
                        $remote_record[$this->get_primary_key($table)] ?? 0,
                        'PULL_ERROR',
                        'remote-server',
                        $this->device_id,
                        null,
                        $remote_record,
                        'pull',
                        null,
                        0,
                        'failed',
                        $error_msg
                    );
                }
            }
            
            // Update last sync timestamp
            $this->update_setting("last_pull_sync_$table", date('Y-m-d H:i:s'));
            
            $this->log_message_custom("Table $table: $pulled pulled, $conflicts conflicts, $errors errors");
            
            // Send notification if there were errors
            if ($errors > 0) {
                $this->sync_notifier->notify_sync_failure([
                    'table_name' => $table,
                    'operation' => 'pull',
                    'error_message' => implode('; ', array_slice($error_details, 0, 3)), // First 3 errors
                    'retry_count' => 0
                ]);
            }
            
            return [
                'pulled' => $pulled,
                'conflicts' => $conflicts,
                'errors' => $errors,
                'status' => $errors > 0 ? 'partial' : 'success',
                'error_details' => $error_details
            ];
            
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            $this->log_message_custom("Error pulling table $table: " . $error_msg, 'error');
            
            // Send notification for pull failure
            $this->load->library('Sync_notifier');
            $this->sync_notifier->notify_sync_failure([
                'table_name' => $table,
                'operation' => 'pull',
                'error_message' => $error_msg,
                'retry_count' => 0
            ]);
            
            return [
                'pulled' => 0,
                'conflicts' => 0,
                'errors' => 1,
                'status' => 'error',
                'error' => $error_msg
            ];
        }
    }
    
    /**
     * Get remote changes since last sync
     * 
     * Requirements: 1
     * Task: 2.4
     * 
     * @param object $remote_db Remote database connection
     * @param string $table Table name
     * @param string $last_sync Last sync timestamp
     * @return array Remote records
     */
    private function get_remote_changes($remote_db, $table, $last_sync) {
        try {
            // Query remote database for changes since last sync
            // Exclude records from this device to avoid circular sync
            $query = $remote_db->where('last_modified_at >', $last_sync)
                              ->where('device_id !=', $this->device_id)
                              ->order_by('last_modified_at', 'ASC')
                              ->get($table);
            
            return $query->result_array();
            
        } catch (Exception $e) {
            $this->log_message_custom("Error getting remote changes for $table: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Apply a remote change to local database
     * 
     * Requirements: 1, 2, 3
     * Task: 2.4
     * 
     * @param string $table Table name
     * @param array $remote_record Remote record
     * @return array Result with status
     */
    private function apply_remote_change($table, $remote_record) {
        $start_time = microtime(true);
        $primary_key = $this->get_primary_key($table);
        
        if (!$primary_key || !isset($remote_record[$primary_key])) {
            return ['status' => 'error', 'message' => 'Primary key not found'];
        }
        
        $record_id = $remote_record[$primary_key];
        
        // Check if local version exists
        $local_record = $this->db->where($primary_key, $record_id)->get($table)->row_array();
        
        if (!$local_record) {
            // No local version - insert remote record
            $this->db->insert($table, $remote_record);
            
            // Log to audit trail
            $duration_ms = round((microtime(true) - $start_time) * 1000);
            $this->audit_logger->log(
                $table,
                $record_id,
                'INSERT',
                $remote_record['device_id'] ?? 'remote-server',
                $this->device_id,
                null,
                $remote_record,
                'pull',
                null,
                $duration_ms,
                'success'
            );
            
            return ['status' => 'applied', 'action' => 'insert'];
        }
        
        // Local version exists - check for conflicts
        $conflict_detected = $this->detect_pull_conflict($table, $local_record, $remote_record);
        
        if ($conflict_detected) {
            // Conflict detected - resolve using strategy
            $strategy = $this->conflict_resolver->get_table_strategy($table);
            $resolution = $this->conflict_resolver->resolve($table, $local_record, $remote_record, $strategy);
            
            // Log to audit trail
            $duration_ms = round((microtime(true) - $start_time) * 1000);
            $this->audit_logger->log(
                $table,
                $record_id,
                'CONFLICT',
                $remote_record['device_id'] ?? 'remote-server',
                $this->device_id,
                $local_record,
                $remote_record,
                'pull',
                null,
                $duration_ms,
                'conflict',
                "Conflict resolved: {$resolution['resolution']}"
            );
            
            return ['status' => 'conflict', 'resolution' => $resolution];
        }
        
        // No conflict - apply remote changes
        $this->db->where($primary_key, $record_id)->update($table, $remote_record);
        
        // Log to audit trail
        $duration_ms = round((microtime(true) - $start_time) * 1000);
        $this->audit_logger->log(
            $table,
            $record_id,
            'UPDATE',
            $remote_record['device_id'] ?? 'remote-server',
            $this->device_id,
            $local_record,
            $remote_record,
            'pull',
            null,
            $duration_ms,
            'success'
        );
        
        return ['status' => 'applied', 'action' => 'update'];
    }
    
    /**
     * Detect if there's a conflict between local and remote versions
     * 
     * Requirements: 2
     * Task: 2.4
     * 
     * @param string $table Table name
     * @param array $local_record Local record
     * @param array $remote_record Remote record
     * @return bool True if conflict detected
     */
    private function detect_pull_conflict($table, $local_record, $remote_record) {
        // Get version numbers
        $local_version = isset($local_record['version']) ? (int)$local_record['version'] : 0;
        $remote_version = isset($remote_record['version']) ? (int)$remote_record['version'] : 0;
        
        // If versions match, no conflict
        if ($local_version === $remote_version) {
            return false;
        }
        
        // If local is SYNCED, remote is newer - no conflict
        if (isset($local_record['sync_status']) && $local_record['sync_status'] === 'SYNCED') {
            return false;
        }
        
        // If local is PENDING and versions differ - CONFLICT!
        if (isset($local_record['sync_status']) && $local_record['sync_status'] === 'PENDING') {
            $this->log_message_custom("Conflict detected in $table: local PENDING with different version", 'info');
            return true;
        }
        
        // Default: no conflict
        return false;
    }
    
    /**
     * Get sync order from sync_metadata table
     * 
     * Requirements: 2.3, 2.5
     * 
     * @return array Table names in sync order
     */
    private function get_sync_order() {
        $query = $this->db->select('table_name')
                         ->from('sync_metadata')
                         ->where('sync_enabled', 1)
                         ->order_by('priority', 'DESC')
                         ->get();
        
        if ($query->num_rows() == 0) {
            // Fallback to hardcoded order if metadata not populated
            return [
                'admin', 'teacher',
                'class', 'section', 'subject',
                'student', 'parent', 'enroll',
                'discount_profiles', 'student_discount_assignments',
                'invoice', 'payment',
                'daily_fee_wallet', 'daily_fee_transactions',
                'attendance',
                'exam', 'exam_marks', 'grade'
            ];
        }
        
        return array_column($query->result_array(), 'table_name');
    }
    
    /**
     * Sync a single table
     * 
     * Requirements: 2.1, 2.3, 2.4, 2.5, 16
     * Task: 9.1
     * 
     * @param string $table Table name
     * @return array Sync results
     */
    private function sync_table($table) {
        $this->log_message_custom("sync_table() called for $table (PUSH-ONLY)");
        
        try {
            // Check if table has sync columns
            if (!$this->table_has_sync_columns($table)) {
                $this->log_message_custom("Table $table does not have sync columns, skipping", 'warning');
                return ['synced' => 0, 'failed' => 0, 'status' => 'skipped'];
            }
            
            // Get pending records
            $pending = $this->db->where('sync_status', 'PENDING')
                               ->get($table)
                               ->result_array();
            
            if (empty($pending)) {
                $this->log_message_custom("No pending records in $table (PUSH-ONLY)");
                return ['synced' => 0, 'failed' => 0, 'status' => 'no_pending'];
            }
            
            $this->log_message_custom("Found " . count($pending) . " pending records in $table (PUSH-ONLY)");
            
            // Get remote database connection
            try {
                $remote_db = $this->get_remote_db();
            } catch (Exception $e) {
                // Catch all exception types (DatabaseConnectionException, AuthenticationException, NetworkException, NoInternetException)
                $error_msg = 'Failed to connect to remote database: ' . $e->getMessage();
                $this->log_message_custom($error_msg, 'error');
                
                // Log exception details if available
                if (method_exists($e, 'getDiagnostics')) {
                    $diagnostics = $e->getDiagnostics();
                    $this->log_message_custom('Connection error diagnostics: ' . json_encode($diagnostics), 'error');
                }
                
                $this->handle_push_connection_error($table, $error_msg);
                throw new Exception($error_msg);
            }
            
            // Set timeout for this table (Task 12.5)
            $this->set_db_timeout($remote_db, 60); // Increased from 30 to 60 seconds
            
            $synced = 0;
            $failed = 0;
            $manual_review = 0;
            $error_details = [];
            
            // PERFORMANCE OPTIMIZATION: Sync records in batches with transactions
            $batch_size = 50; // Reduced from 100 for more frequent commits
            $batches = array_chunk($pending, $batch_size);
            
            $this->log_message_custom("Processing " . count($batches) . " batches of up to $batch_size records each");
            
            $batch_num = 0;
            foreach ($batches as $batch) {
                $batch_num++;
                $batch_start_time = microtime(true);
                
                $this->log_message_custom("Processing batch $batch_num/" . count($batches) . " (" . count($batch) . " records)");
                
                // Start transaction for this batch
                $remote_db->trans_start();
                
                $batch_synced = 0;
                $batch_failed = 0;
                
                foreach ($batch as $record) {
                    try {
                        // Check if should retry (Task 12.2)
                        if (!$this->should_retry_record($table, $record)) {
                            $manual_review++;
                            continue;
                        }
                        
                        // Bug Fix: Task 3.2 - Track if remote write succeeded
                        $remote_write_succeeded = false;
                        
                        // Prepare record for remote - set sync_status to SYNCED
                        $remote_record = $record;
                        $remote_record['sync_status'] = 'SYNCED';
                        $remote_record['last_modified_at'] = date('Y-m-d H:i:s');
                        
                        // Filter record to only include columns that exist on remote table
                        $remote_record = $this->filter_record_for_remote($remote_db, $table, $remote_record);
                        
                        // Use INSERT ... ON DUPLICATE KEY UPDATE to avoid foreign key errors
                        $this->insert_or_update_remote($remote_db, $table, $remote_record);
                        
                        // Bug Fix: Task 3.2 - Mark that remote write succeeded
                        $remote_write_succeeded = true;
                        
                        // AUDIT LOGGING FIX: Log successful push to audit trail
                        // This is needed for sync graphs and compliance tracking
                        $primary_key = $this->get_primary_key($table);
                        $record_id = $record[$primary_key] ?? 0;
                        $sync_duration = round((microtime(true) - $batch_start_time) * 1000 / count($batch));
                        
                        // Determine operation type from record
                        $operation = strtoupper($record['sync_operation_type'] ?? 'UPDATE');
                        if ($operation === 'BOTH') {
                            $operation = 'UPSERT';  // Could be INSERT or UPDATE
                        } elseif ($operation === 'INSERT') {
                            $operation = 'INSERT';
                        } else {
                            $operation = 'UPDATE';
                        }
                        
                        // Log to audit trail
                        $this->audit_logger->log(
                            $table,
                            $record_id,
                            $operation,
                            $this->device_id,
                            $this->get_remote_device_id(),
                            null,  // old_value not available in push-only mode
                            $remote_record,
                            'push',
                            $this->session->userdata('login_user_id') ?? null,
                            $sync_duration,
                            'success'
                        );
                        
                        // Mark as synced locally (defer until batch commit)
                        $this->mark_synced($table, $record);
                        $batch_synced++;
                        $synced++;
                        
                    } catch (Exception $e) {
                        // Check for specific error types
                        $error_msg = $e->getMessage();
                        
                        // Duplicate key is OK - it means record already synced
                        if (strpos($error_msg, 'Duplicate entry') !== false) {
                            $this->log_message_custom("Record already exists on remote (duplicate key) - marking as synced", 'debug');
                            
                            // AUDIT LOGGING FIX: Log this as a successful sync (idempotent)
                            $primary_key = $this->get_primary_key($table);
                            $record_id = $record[$primary_key] ?? 0;
                            $sync_duration = round((microtime(true) - $batch_start_time) * 1000 / count($batch));
                            
                            $this->audit_logger->log(
                                $table,
                                $record_id,
                                'UPDATE',  // Already exists, so it's an update
                                $this->device_id,
                                $this->get_remote_device_id(),
                                null,
                                $record,
                                'push',
                                $this->session->userdata('login_user_id') ?? null,
                                $sync_duration,
                                'success',
                                'Record already exists on remote (idempotent)'
                            );
                            
                            $this->mark_synced($table, $record);
                            $batch_synced++;
                            $synced++;
                            continue;
                        }
                        
                        $error_details[] = $error_msg;
                        
                        $error_type = 'unknown';
                        $verification_performed = false;
                        $record_exists_on_remote = false;
                        
                        // Constraint violation (Task 12.2)
                        if (strpos($error_msg, 'constraint') !== false || 
                            strpos($error_msg, 'foreign key') !== false) {
                            $this->log_message_custom("Constraint violation in $table: $error_msg", 'info');
                            $this->handle_push_constraint_error($table, $record, $error_msg);
                            $error_type = 'remote_failure';
                        }
                        
                        // Timeout error (Task 12.5)
                        elseif (strpos($error_msg, 'timeout') !== false || 
                            strpos($error_msg, 'timed out') !== false) {
                            $this->log_message_custom("Timeout syncing $table: $error_msg", 'error');
                            $this->handle_push_timeout_error($table, $batch, $error_msg);
                            $error_type = 'timeout';
                            
                            // Rollback this batch and break
                            $remote_db->trans_rollback();
                            break 2; // Exit both loops
                        }
                        
                        // Other errors
                        else {
                            $this->handle_push_generic_error($table, $record, $error_msg);
                            $error_type = 'remote_failure';
                        }
                        
                        // Mark as failed with error message and classification
                        $this->mark_failed($table, $record, $error_msg, $error_type, $verification_performed ? $record_exists_on_remote : null);
                        $batch_failed++;
                        $failed++;
                        $this->log_message_custom("Failed to sync record in $table: " . $error_msg . " [error_type=$error_type]", 'error');
                    }
                }
                
                // Commit transaction for this batch
                $remote_db->trans_complete();
                
                if ($remote_db->trans_status() === FALSE) {
                    $this->log_message_custom("Batch $batch_num transaction failed - rolling back", 'error');
                    // Transaction already rolled back automatically
                } else {
                    $batch_duration = round(microtime(true) - $batch_start_time, 2);
                    $this->log_message_custom("Batch $batch_num completed: $batch_synced synced, $batch_failed failed in {$batch_duration}s");
                }
            }
            
            $this->log_message_custom("Table $table: $synced synced, $failed failed, $manual_review need manual review");
            
            // Send notification if there were failures
            if ($failed > 0) {
                $this->load->library('Sync_notifier');
                $this->sync_notifier->notify_sync_failure([
                    'table_name' => $table,
                    'operation' => 'push',
                    'error_message' => implode('; ', array_slice($error_details, 0, 3)), // First 3 errors
                    'retry_count' => 0
                ]);
            }
            
            return [
                'synced' => $synced,
                'failed' => $failed,
                'manual_review' => $manual_review,
                'status' => $failed > 0 ? 'partial' : 'success',
                'error_details' => $error_details
            ];
            
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            $this->log_message_custom("Error syncing table $table: " . $error_msg, 'error');
            
            // Send notification for push failure
            $this->load->library('Sync_notifier');
            $this->sync_notifier->notify_sync_failure([
                'table_name' => $table,
                'operation' => 'push',
                'error_message' => $error_msg,
                'retry_count' => 0
            ]);
            
            return [
                'synced' => 0,
                'failed' => 0,
                'status' => 'error',
                'error' => $error_msg
            ];
        }
    }
    
    /**
     * Sync specific records from a table (for bidirectional sync)
     * Pushes records that were PENDING before pull phase, checking for conflicts
     */
    private function sync_table_bidirectional($table, $pending_record_ids) {
        $this->log_message_custom("sync_table_bidirectional() called for $table");
        
        try {
            // Check if table has sync columns
            if (!$this->table_has_sync_columns($table)) {
                $this->log_message_custom("Table $table does not have sync columns, skipping", 'warning');
                return ['synced' => 0, 'failed' => 0, 'status' => 'skipped'];
            }
            
            // Get primary key
            $primary_key = $this->get_primary_key($table);
            if (!$primary_key) {
                throw new Exception("Cannot find primary key for table $table");
            }
            
            // Get current state of these records (they may have been updated by pull phase)
            $records = $this->db->where_in($primary_key, $pending_record_ids)
                               ->get($table)
                               ->result_array();
            
            if (empty($records)) {
                $this->log_message_custom("No records found for pending IDs in $table");
                return ['synced' => 0, 'failed' => 0, 'status' => 'no_pending'];
            }
            
            $this->log_message_custom("Found " . count($records) . " records to push in $table (bidirectional)");
            
            // Get remote database connection
            try {
                $remote_db = $this->get_remote_db();
            } catch (Exception $e) {
                // Catch all exception types (DatabaseConnectionException, AuthenticationException, NetworkException, NoInternetException)
                $error_msg = 'Failed to connect to remote database: ' . $e->getMessage();
                $this->log_message_custom($error_msg, 'error');
                
                // Log exception details if available
                if (method_exists($e, 'getDiagnostics')) {
                    $diagnostics = $e->getDiagnostics();
                    $this->log_message_custom('Connection error diagnostics: ' . json_encode($diagnostics), 'error');
                }
                
                $this->handle_push_connection_error($table, $error_msg);
                throw new Exception($error_msg);
            }
            
            // Set timeout for this table
            $this->set_db_timeout($remote_db, 30);
            
            // Load conflict resolver
            $this->load->library('Conflict_resolver');
            
            $synced = 0;
            $failed = 0;
            $conflicts = 0;
            $manual_review = 0;
            $error_details = [];
            
            // Sync records in batches
            $batch_size = 100;
            $batches = array_chunk($records, $batch_size);
            
            foreach ($batches as $batch) {
                foreach ($batch as $record) {
                    try {
                        // Check if should retry
                        if (!$this->should_retry_record($table, $record)) {
                            $manual_review++;
                            continue;
                        }
                        
                        // Check if remote version exists
                        $remote_record = $remote_db->where($primary_key, $record[$primary_key])
                                                   ->get($table)
                                                   ->row_array();
                        
                        if ($remote_record) {
                            // Remote version exists - check for conflict
                            $conflict_detected = $this->detect_push_conflict($table, $record, $remote_record);
                            
                            if ($conflict_detected) {
                                // Conflict detected - resolve using strategy
                                $strategy = $this->conflict_resolver->get_table_strategy($table);
                                $resolution = $this->conflict_resolver->resolve_push_conflict($table, $record, $remote_record, $strategy);
                                
                                $conflicts++;
                                $this->log_message_custom("Conflict detected and resolved for $table record {$record[$primary_key]}: {$resolution['resolution']}");
                                
                                // If resolution is MANUAL_REVIEW, skip this record
                                if ($resolution['resolution'] === 'MANUAL_REVIEW') {
                                    $manual_review++;
                                    continue;
                                }
                                
                                // If resolution is LOCAL_WINS, push local version
                                if ($resolution['resolution'] === 'local_wins') {
                                    // Push local version to remote
                                    $remote_record_to_push = $record;
                                    $remote_record_to_push['sync_status'] = 'SYNCED';
                                    $remote_record_to_push['last_modified_at'] = date('Y-m-d H:i:s');
                                    
                                    // Filter record to only include columns that exist on remote table
                                    $remote_record_to_push = $this->filter_record_for_remote($remote_db, $table, $remote_record_to_push);
                                    
                                    // Use INSERT ... ON DUPLICATE KEY UPDATE to avoid foreign key errors
                                    $this->insert_or_update_remote($remote_db, $table, $remote_record_to_push);
                                    
                                    // Mark as synced locally
                                    $this->mark_synced($table, $record);
                                    $synced++;
                                }
                                // If resolution is REMOTE_WINS, don't push (remote already has correct version)
                                else {
                                    $this->log_message_custom("Remote version wins, not pushing local record");
                                }
                                
                                continue;
                            }
                        }
                        
                        // No conflict or no remote version - push local version
                        $remote_record_to_push = $record;
                        $remote_record_to_push['sync_status'] = 'SYNCED';
                        $remote_record_to_push['last_modified_at'] = date('Y-m-d H:i:s');
                        
                        // Filter record to only include columns that exist on remote table
                        $remote_record_to_push = $this->filter_record_for_remote($remote_db, $table, $remote_record_to_push);
                        
                        // Use INSERT ... ON DUPLICATE KEY UPDATE to avoid foreign key errors
                        $this->insert_or_update_remote($remote_db, $table, $remote_record_to_push);
                        
                        // Mark as synced locally
                        $this->mark_synced($table, $record);
                        $synced++;
                        
                    } catch (Exception $e) {
                        // Check for specific error types
                        $error_msg = $e->getMessage();
                        $error_details[] = $error_msg;
                        
                        // Constraint violation
                        if (strpos($error_msg, 'constraint') !== false || 
                            strpos($error_msg, 'foreign key') !== false ||
                            strpos($error_msg, 'duplicate') !== false) {
                            $this->log_message_custom("Constraint violation in $table: $error_msg", 'info');
                            $this->handle_push_constraint_error($table, $record, $error_msg);
                        }
                        
                        // Timeout error
                        elseif (strpos($error_msg, 'timeout') !== false || 
                            strpos($error_msg, 'timed out') !== false) {
                            $this->log_message_custom("Timeout syncing $table: $error_msg", 'error');
                            $this->handle_push_timeout_error($table, $batch, $error_msg);
                            break; // Exit batch loop
                        }
                        
                        // Other errors
                        else {
                            $this->handle_push_generic_error($table, $record, $error_msg);
                        }
                        
                        // Mark as failed with error message
                        $this->mark_failed($table, $record, $error_msg);
                        $failed++;
                        $this->log_message_custom("Failed to sync record in $table: " . $error_msg, 'error');
                    }
                }
            }
            
            $this->log_message_custom("Table $table: $synced synced, $conflicts conflicts, $failed failed, $manual_review need manual review");
            
            // Send notification if there were failures
            if ($failed > 0) {
                $this->load->library('Sync_notifier');
                $this->sync_notifier->notify_sync_failure([
                    'table_name' => $table,
                    'operation' => 'push',
                    'error_message' => implode('; ', array_slice($error_details, 0, 3)),
                    'retry_count' => 0
                ]);
            }
            
            return [
                'synced' => $synced,
                'conflicts' => $conflicts,
                'failed' => $failed,
                'manual_review' => $manual_review,
                'status' => $failed > 0 ? 'partial' : 'success',
                'error_details' => $error_details
            ];
            
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            $this->log_message_custom("Error syncing table $table: " . $error_msg, 'error');
            
            // Send notification for push failure
            $this->load->library('Sync_notifier');
            $this->sync_notifier->notify_sync_failure([
                'table_name' => $table,
                'operation' => 'push',
                'error_message' => $error_msg,
                'retry_count' => 0
            ]);
            
            return [
                'synced' => 0,
                'conflicts' => 0,
                'failed' => 0,
                'status' => 'error',
                'error' => $error_msg
            ];
        }
    }
    
    /**
     * Detect conflict during push phase (bidirectional sync)
     * Returns true if local and remote versions conflict
     */
    private function detect_push_conflict($table, $local_record, $remote_record) {
        // Get version numbers
        $local_version = isset($local_record['version']) ? (int)$local_record['version'] : 0;
        $remote_version = isset($remote_record['version']) ? (int)$remote_record['version'] : 0;
        
        // If versions match, no conflict
        if ($local_version === $remote_version) {
            return false;
        }
        
        // If remote is SYNCED and local version is higher - no conflict, push local
        if (isset($remote_record['sync_status']) && $remote_record['sync_status'] === 'SYNCED' && $local_version > $remote_version) {
            return false;
        }
        
        // If remote is PENDING and versions differ - CONFLICT!
        if (isset($remote_record['sync_status']) && $remote_record['sync_status'] === 'PENDING') {
            $this->log_message_custom("Conflict detected in $table: remote PENDING with different version", 'info');
            return true;
        }
        
        // If versions differ significantly - CONFLICT!
        if (abs($local_version - $remote_version) > 1) {
            $this->log_message_custom("Conflict detected in $table: version mismatch (local: $local_version, remote: $remote_version)", 'info');
            return true;
        }
        
        // Default: no conflict
        return false;
    }
    
    /**
     * Check if table has sync columns
     * 
     * @param string $table Table name
     * @return bool
     */
    private function table_has_sync_columns($table) {
        $fields = $this->db->list_fields($table);
        return in_array('sync_status', $fields) && 
               in_array('device_id', $fields);
    }
    
    /**
     * Mark record as synced
     * 
     * TASK 3.11: Preserve sync_operation_type after successful sync for auditing
     * 
     * @param string $table Table name
     * @param array $record Record data
     */
    private function mark_synced($table, $record) {
        $primary_key = $this->get_primary_key($table);
        
        // Use direct query to bypass MY_DB_mysqli_driver sync tracking
        // This prevents the driver from setting sync_status back to PENDING
        // NOTE: We only update sync_status and last_modified_at, NOT sync_operation_type
        // This preserves the operation type for audit trail purposes
        $this->db->query(
            "UPDATE `$table` SET sync_status = 'SYNCED', last_modified_at = NOW() WHERE `$primary_key` = ?",
            [$record[$primary_key]]
        );
        
        // Log for verification
        if (isset($record['sync_operation_type'])) {
            $this->log_message_custom("Marked $table record as SYNCED (operation was {$record['sync_operation_type']})", 'debug');
        }
    }
    
    /**
     * Mark record as failed
     * 
     * Mark record as failed
     * 
     * Bug Fix: Task 4.1 - Enhanced with error classification
     * Accepts error_type and verification status to distinguish between:
     * - remote_failure: Failed to write to remote database
     * - local_status_failure: Remote write succeeded but local update failed (false failure)
     * - genuine_failure: Verified record does not exist on remote
     * - verification_error: Could not verify remote state
     * 
     * @param string $table Table name
     * @param array $record Record data
     * @param string $error Error message
     * @param string $error_type Error classification (remote_failure, local_status_failure, genuine_failure, verification_error, unknown)
     * @param bool|null $verified_not_on_remote TRUE if verified not on remote, FALSE if verified on remote, NULL if not verified
     */
    private function mark_failed($table, $record, $error, $error_type = 'unknown', $verified_not_on_remote = null) {
        $primary_key = $this->get_primary_key($table);
        
        // Determine verification status
        $verification_status = 'not_verified';
        if ($verified_not_on_remote === true) {
            $verification_status = 'verified_not_exists';
        } elseif ($verified_not_on_remote === false) {
            $verification_status = 'verified_exists';
        }
        
        // Log classification for debugging
        $this->log_message_custom("Marking $table record as FAILED with error_type=$error_type, verification_status=$verification_status", 'info');
        
        // Use direct query to bypass MY_DB_mysqli_driver sync tracking
        // This prevents the driver from interfering with sync status updates
        $this->db->query(
            "UPDATE `$table` SET sync_status = 'FAILED' WHERE `$primary_key` = ?",
            [$record[$primary_key]]
        );
        
        // Log to sync_queue for retry with enhanced classification
        $queue_data = [
            'table_name' => $table,
            'record_id' => $record[$primary_key],
            'operation' => 'UPDATE',
            'data' => json_encode($record),
            'error_message' => $error,
            'retry_count' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Add error classification columns if they exist (Task 4.2)
        // Check if columns exist first to maintain backward compatibility
        $queue_columns = $this->db->list_fields('sync_queue');
        if (in_array('error_type', $queue_columns)) {
            $queue_data['error_type'] = $error_type;
        }
        if (in_array('verification_status', $queue_columns)) {
            $queue_data['verification_status'] = $verification_status;
        }
        
        $this->db->insert('sync_queue', $queue_data);
        
        // Log to audit trail for administrator visibility
        if (method_exists($this, 'audit_logger') && isset($this->audit_logger)) {
            $classification_note = "Error Type: $error_type, Verification: $verification_status";
            
            $this->audit_logger->log(
                $table,
                $record[$primary_key],
                'FAILED',
                $this->device_id,
                $this->get_remote_device_id(),
                null,
                null,
                'push',
                $this->session->userdata('login_user_id'),
                0,
                'failed',
                $classification_note . " | " . $error
            );
        }
    }
    
    /**
     * Retry a single failed sync record
     * 
     * Bug Fix: Task 5.1 - Manual Retry Mechanism
     * Allows administrators to manually retry failed sync operations.
     * Uses natural key matching for idempotency - won't create duplicates.
     * 
     * @param string $table Table name
     * @param int $record_id Record primary key value
     * @return array Status and message
     */
    public function retry_failed_record($table, $record_id) {
        try {
            $this->log_message_custom("Manual retry requested for $table record $record_id", 'info');
            
            // Get primary key column
            $primary_key = $this->get_primary_key($table);
            
            // Query local database for failed record
            $record = $this->db->where($primary_key, $record_id)
                              ->where('sync_status', 'FAILED')
                              ->get($table)
                              ->row_array();
            
            if (!$record) {
                $this->log_message_custom("Record not found or not in FAILED status", 'warning');
                return [
                    'status' => 'error',
                    'message' => "Record not found or not in FAILED status",
                    'record_id' => $record_id,
                    'table' => $table
                ];
            }
            
            // Get remote database connection
            try {
                $remote_db = $this->get_remote_db();
            } catch (Exception $e) {
                $this->log_message_custom("Failed to connect to remote database: " . $e->getMessage(), 'error');
                return [
                    'status' => 'error',
                    'message' => 'Cannot connect to remote database: ' . $e->getMessage(),
                    'record_id' => $record_id,
                    'table' => $table
                ];
            }
            
            // Prepare record for remote sync
            $remote_record = $record;
            $remote_record['sync_status'] = 'SYNCED';
            $remote_record['last_modified_at'] = date('Y-m-d H:i:s');
            
            // Filter record to match remote schema
            $remote_record = $this->filter_record_for_remote($remote_db, $table, $remote_record);
            
            // Attempt sync (leverages natural key matching for idempotency)
            try {
                $this->insert_or_update_remote($remote_db, $table, $remote_record);
                
                // Mark as synced locally
                $this->mark_synced($table, $record);
                
                // Update retry count in sync_queue
                $this->db->where('table_name', $table)
                        ->where('record_id', $record_id)
                        ->set('retry_count', 'retry_count + 1', FALSE)
                        ->set('last_retry_at', date('Y-m-d H:i:s'))
                        ->update('sync_queue');
                
                $this->log_message_custom("✓ Retry successful for $table record $record_id", 'success');
                
                return [
                    'status' => 'success',
                    'message' => 'Record synced successfully',
                    'record_id' => $record_id,
                    'table' => $table
                ];
                
            } catch (Exception $e) {
                // Retry failed - increment retry count
                $error_msg = $e->getMessage();
                
                $this->db->where('table_name', $table)
                        ->where('record_id', $record_id)
                        ->set('retry_count', 'retry_count + 1', FALSE)
                        ->set('last_retry_at', date('Y-m-d H:i:s'))
                        ->set('error_message', $error_msg)
                        ->update('sync_queue');
                
                $this->log_message_custom("✗ Retry failed for $table record $record_id: " . $error_msg, 'error');
                
                return [
                    'status' => 'error',
                    'message' => 'Sync failed: ' . $error_msg,
                    'record_id' => $record_id,
                    'table' => $table
                ];
            }
            
        } catch (Exception $e) {
            $this->log_message_custom("Exception during retry: " . $e->getMessage(), 'error');
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'record_id' => $record_id,
                'table' => $table
            ];
        }
    }
    
    /**
     * Retry all failed sync records (with optional table filter)
     * 
     * Bug Fix: Task 5.2 - Bulk Retry Mechanism
     * Allows administrators to retry all failed records at once.
     * Processes in batches to prevent timeout on large datasets.
     * 
     * @param string|null $table Optional table name filter (null = all tables)
     * @param int $limit Maximum records to process (default 50)
     * @return array Statistics and results
     */
    public function retry_all_failed_records($table = null, $limit = 50) {
        try {
            $this->log_message_custom("Bulk retry requested" . ($table ? " for table $table" : " for all tables"), 'info');
            
            // Query failed records
            $this->db->select('DISTINCT table_name, record_id')
                    ->where('sync_status', 'FAILED')
                    ->from('sync_queue')
                    ->limit($limit);
            
            if ($table) {
                $this->db->where('table_name', $table);
            }
            
            $failed_records = $this->db->get()->result_array();
            
            if (empty($failed_records)) {
                $this->log_message_custom("No failed records found", 'info');
                return [
                    'status' => 'success',
                    'message' => 'No failed records to retry',
                    'total_retried' => 0,
                    'succeeded' => 0,
                    'still_failed' => 0,
                    'results' => []
                ];
            }
            
            $this->log_message_custom("Found " . count($failed_records) . " failed records to retry", 'info');
            
            // Process each failed record
            $total_retried = 0;
            $succeeded = 0;
            $still_failed = 0;
            $results = [];
            
            foreach ($failed_records as $failed_record) {
                $retry_table = $failed_record['table_name'];
                $retry_id = $failed_record['record_id'];
                
                $total_retried++;
                
                // Retry the record
                $retry_result = $this->retry_failed_record($retry_table, $retry_id);
                
                if ($retry_result['status'] === 'success') {
                    $succeeded++;
                } else {
                    $still_failed++;
                }
                
                $results[] = $retry_result;
                
                // Log progress every 10 records
                if ($total_retried % 10 === 0) {
                    $this->log_message_custom("Progress: $total_retried retried, $succeeded succeeded, $still_failed still failed", 'info');
                }
            }
            
            $this->log_message_custom("✓ Bulk retry complete: $total_retried retried, $succeeded succeeded, $still_failed still failed", 'success');
            
            return [
                'status' => 'success',
                'message' => "Bulk retry complete",
                'total_retried' => $total_retried,
                'succeeded' => $succeeded,
                'still_failed' => $still_failed,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->log_message_custom("Exception during bulk retry: " . $e->getMessage(), 'error');
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'total_retried' => 0,
                'succeeded' => 0,
                'still_failed' => 0,
                'results' => []
            ];
        }
    }
    
    /**
     * Get list of failed sync records (Helper method for internal use)
     * 
     * Bug Fix: Task 5.3 - Failed Records List
     * Returns paginated list of failed records with details for dashboard display.
     * 
     * @param int $limit Records per page (default 50, max 100)
     * @param int $offset Pagination offset (default 0)
     * @param string|null $table Optional table name filter
     * @return array Failed records with pagination metadata
     */
    private function get_failed_records_internal($limit = 50, $offset = 0, $table = null) {
        try {
            // Validate and cap limit
            $limit = min(max(1, (int)$limit), 100);
            $offset = max(0, (int)$offset);
            
            $this->log_message_custom("Getting failed records: limit=$limit, offset=$offset" . ($table ? ", table=$table" : ""), 'info');
            
            // Build query
            $this->db->select('sq.*, 
                               sq.table_name, 
                               sq.record_id, 
                               sq.error_message, 
                               sq.retry_count, 
                               sq.created_at as failed_at,
                               sq.last_retry_at')
                    ->from('sync_queue sq')
                    ->order_by('sq.created_at', 'DESC')
                    ->limit($limit, $offset);
            
            // Check if error classification columns exist
            $queue_columns = $this->db->list_fields('sync_queue');
            if (in_array('error_type', $queue_columns)) {
                $this->db->select('sq.error_type');
            }
            if (in_array('verification_status', $queue_columns)) {
                $this->db->select('sq.verification_status');
            }
            
            if ($table) {
                $this->db->where('sq.table_name', $table);
            }
            
            $failed_records = $this->db->get()->result_array();
            
            // Get total count for pagination
            $this->db->from('sync_queue');
            if ($table) {
                $this->db->where('table_name', $table);
            }
            $total_count = $this->db->count_all_results();
            
            $this->log_message_custom("Found " . count($failed_records) . " failed records (total: $total_count)", 'info');
            
            return [
                'status' => 'success',
                'records' => $failed_records,
                'pagination' => [
                    'total' => $total_count,
                    'limit' => $limit,
                    'offset' => $offset,
                    'pages' => ceil($total_count / $limit),
                    'current_page' => floor($offset / $limit) + 1
                ]
            ];
            
        } catch (Exception $e) {
            $this->log_message_custom("Exception getting failed records: " . $e->getMessage(), 'error');
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'records' => [],
                'pagination' => [
                    'total' => 0,
                    'limit' => $limit,
                    'offset' => $offset,
                    'pages' => 0,
                    'current_page' => 1
                ]
            ];
        }
    }
    
    /**
     * Get primary key for table
     * 
     * @param string $table Table name
     * @return string Primary key field name
     */
    


    /**
     * Get remote database connection
     * 
     * Enhanced with exception throwing for proper error communication
     * Instead of returning false, throws specific exceptions with detailed error information
     * 
     * Task: 3.3 - Enhance get_remote_db() error handling with exceptions
     * Requirements: 2.1, 2.3, 2.8
     * 
     * @return object Database connection
     * @throws DatabaseConnectionException For connection refused errors
     * @throws AuthenticationException For authentication failures
     * @throws NetworkException For network timeouts or unreachable host
     * @throws NoInternetException For no internet connectivity
     */
    private function get_remote_db() {
        // Return cached connection if already established
        if ($this->remote_db) {
            return $this->remote_db;
        }
        
        // Log connection attempt with timestamp
        $attempt_timestamp = date('Y-m-d H:i:s');
        $this->log_message_custom("[$attempt_timestamp] Attempting remote database connection", 'info');
        
        // Get remote connection settings
        $host = $this->get_setting('remote_db_host');
        $port = $this->get_setting('remote_db_port', 3306);
        $user = $this->get_setting('remote_db_user');
        $pass = $this->get_setting('remote_db_pass');
        $dbname = $this->get_setting('remote_db_name');
        
        // Decode password (Setup Wizard saves it as base64 encoded)
        if ($pass && !empty($pass)) {
            // Try to decode with strict mode
            $decoded_pass = base64_decode($pass, true);
            
            // Check if decode was successful and result is not empty
            if ($decoded_pass !== false && !empty(trim($decoded_pass))) {
                $pass = $decoded_pass;
            }
            // If decode failed or empty, use original password (might not be encoded)
        }
        
        // Trim whitespace from password
        $pass = trim($pass);
        
        // Build diagnostics array for error reporting
        $diagnostics = [
            'host' => $host,
            'port' => $port,
            'username' => $user,
            'database' => $dbname,
            'timestamp' => $attempt_timestamp,
            'credentials_configured' => [
                'host' => !empty($host),
                'username' => !empty($user),
                'password' => !empty($pass),
                'database' => !empty($dbname)
            ]
        ];
        
        // Check if any required credentials are missing
        if (!$host || !$user || !$dbname || empty($pass)) {
            $missing = [];
            if (!$host) $missing[] = 'host';
            if (!$user) $missing[] = 'username';
            if (!$dbname) $missing[] = 'database';
            if (empty($pass)) $missing[] = 'password';
            
            $diagnostics['missing_credentials'] = $missing;
            
            // Log configuration error with timestamp
            $this->log_message_custom("[$attempt_timestamp] Remote database credentials not configured - Missing: " . implode(', ', $missing), 'error');
            
            // Throw DatabaseConnectionException for missing configuration
            require_once APPPATH . 'libraries/DatabaseConnectionException.php';
            throw new DatabaseConnectionException(
                "Remote database credentials not configured. Missing: " . implode(', ', $missing),
                $diagnostics,
                1001
            );
        }
        
        $this->log_message_custom("Attempting remote connection: host={$host}:{$port}, user={$user}, db={$dbname}, pass_length=" . strlen($pass), 'info');
        
        try {
            // Create database configuration
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
            
            // Attempt to establish connection
            $this->remote_db = $this->load->database($config, TRUE);
            
            // Check if connection was established
            if (!$this->remote_db || !$this->remote_db->conn_id) {
                // Connection failed - determine specific error type
                
                // Try to get mysqli error details
                $mysqli_error = '';
                $mysqli_errno = 0;
                
                if (function_exists('mysqli_connect_error')) {
                    $mysqli_error = mysqli_connect_error();
                    $mysqli_errno = mysqli_connect_errno();
                }
                
                $diagnostics['mysqli_error'] = $mysqli_error;
                $diagnostics['mysqli_errno'] = $mysqli_errno;
                
                // Log failure with timestamp
                $this->log_message_custom("[$attempt_timestamp] Failed to establish remote database connection - Error: $mysqli_error ($mysqli_errno)", 'error');
                
                // Determine exception type based on error code
                if ($mysqli_errno == 1045) {
                    // Authentication failed (Access denied)
                    require_once APPPATH . 'libraries/AuthenticationException.php';
                    throw new AuthenticationException(
                        "Authentication failed for user '$user'@'$host'. Please check your database credentials.",
                        $diagnostics,
                        $mysqli_errno
                    );
                } elseif ($mysqli_errno == 1049) {
                    // Database not found
                    require_once APPPATH . 'libraries/AuthenticationException.php';
                    throw new AuthenticationException(
                        "Database '$dbname' does not exist on the remote server at $host:$port",
                        $diagnostics,
                        $mysqli_errno
                    );
                } elseif ($mysqli_errno == 2002 || $mysqli_errno == 2003) {
                    // Connection refused or can't connect
                    require_once APPPATH . 'libraries/DatabaseConnectionException.php';
                    throw new DatabaseConnectionException(
                        "Cannot connect to MySQL server at $host:$port. Server may be down or unreachable.",
                        $diagnostics,
                        $mysqli_errno
                    );
                } elseif ($mysqli_errno == 2006 || $mysqli_errno == 2013) {
                    // Server has gone away or connection lost
                    require_once APPPATH . 'libraries/NetworkException.php';
                    throw new NetworkException(
                        "Connection to MySQL server at $host:$port was lost. Network timeout or server disconnection.",
                        $diagnostics,
                        $mysqli_errno
                    );
                } else {
                    // Generic database connection error
                    require_once APPPATH . 'libraries/DatabaseConnectionException.php';
                    throw new DatabaseConnectionException(
                        "Failed to connect to remote database at $host:$port" . ($mysqli_error ? ": $mysqli_error" : ""),
                        $diagnostics,
                        $mysqli_errno ?: 1000
                    );
                }
            }
            
            // Connection established - verify it works with a test query
            try {
                $test_query = $this->remote_db->query("SELECT 1 as test");
                if (!$test_query) {
                    // Query failed - connection may be unstable
                    $diagnostics['test_query_failed'] = true;
                    $this->log_message_custom("[$attempt_timestamp] Remote database connection test query failed", 'error');
                    
                    require_once APPPATH . 'libraries/DatabaseConnectionException.php';
                    throw new DatabaseConnectionException(
                        "Connected to remote database at $host:$port but cannot execute queries. Database may have issues.",
                        $diagnostics,
                        1002
                    );
                }
            } catch (Exception $e) {
                // Exception during test query
                $diagnostics['test_query_exception'] = $e->getMessage();
                $this->log_message_custom("[$attempt_timestamp] Remote database connection test failed: " . $e->getMessage(), 'error');
                
                require_once APPPATH . 'libraries/DatabaseConnectionException.php';
                throw new DatabaseConnectionException(
                    "Connection test failed for remote database at $host:$port: " . $e->getMessage(),
                    $diagnostics,
                    1003
                );
            }
            
            // Success - log and return connection
            $this->log_message_custom("[$attempt_timestamp] Successfully connected to remote database at $host:$port");
            return $this->remote_db;
            
        } catch (DatabaseConnectionException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (AuthenticationException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (NetworkException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (Exception $e) {
            // Catch any other exceptions and wrap them
            $diagnostics['exception'] = $e->getMessage();
            $diagnostics['exception_code'] = $e->getCode();
            
            $this->log_message_custom("[$attempt_timestamp] Database connection exception: " . $e->getMessage(), 'error');
            
            // Determine exception type based on message content
            $error_msg = strtolower($e->getMessage());
            
            if (strpos($error_msg, 'access denied') !== false || strpos($error_msg, 'authentication') !== false) {
                require_once APPPATH . 'libraries/AuthenticationException.php';
                throw new AuthenticationException(
                    "Authentication failed for remote database at $host:$port: " . $e->getMessage(),
                    $diagnostics,
                    $e->getCode()
                );
            } elseif (strpos($error_msg, 'timeout') !== false || strpos($error_msg, 'timed out') !== false) {
                require_once APPPATH . 'libraries/NetworkException.php';
                throw new NetworkException(
                    "Network timeout connecting to remote database at $host:$port: " . $e->getMessage(),
                    $diagnostics,
                    $e->getCode()
                );
            } elseif (strpos($error_msg, 'refused') !== false || strpos($error_msg, 'unreachable') !== false) {
                require_once APPPATH . 'libraries/DatabaseConnectionException.php';
                throw new DatabaseConnectionException(
                    "Connection refused to remote database at $host:$port: " . $e->getMessage(),
                    $diagnostics,
                    $e->getCode()
                );
            } else {
                // Generic database connection error
                require_once APPPATH . 'libraries/DatabaseConnectionException.php';
                throw new DatabaseConnectionException(
                    "Failed to connect to remote database at $host:$port: " . $e->getMessage(),
                    $diagnostics,
                    $e->getCode() ?: 1999
                );
            }
        }
    }
    
    /**
     * Manual sync trigger (for dashboard)
     * 
     * Requirements: 3.1, 4.1
     * 
     * @return void Outputs JSON
     */
    public function manual_sync() {
        $result = $this->auto_sync();
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Sync alias (for cron jobs and scheduled tasks)
     * 
     * This method is an alias for auto_sync() to support legacy
     * cron job URLs that call Sync_server/sync
     * 
     * Requirements: 2.1, 2.2, 3.1
     * 
     * @return void Outputs JSON
     */
    public function sync() {
        $result = $this->auto_sync();
        
        // For CLI mode, output simple text
        if ($this->is_cli()) {
            echo "Sync Status: " . $result['status'] . "\n";
            if (isset($result['duration'])) {
                echo "Duration: " . $result['duration'] . "s\n";
            }
            if (isset($result['message'])) {
                echo "Message: " . $result['message'] . "\n";
            }
            return;
        }
        
        // For web requests, output JSON
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Get sync status for dashboard
     * 
     * Requirements: 4.1, 4.2
     * 
     * @return array Sync status data
     */
    /**
     * Get Sync Status for Dashboard
     * 
     * Task 6.4: Updated to include genuine failures and corrected false failures metrics
     * 
     * Requirements: 2.5
     * 
     * @return array Sync status data
     */
    public function get_sync_status() {
        $status = [
            'internet_online' => $this->is_online(),
            'last_sync' => $this->get_setting('last_sync_time'),
            'last_sync_status' => $this->get_setting('last_sync_status'),
            'last_sync_error' => null, // Task 3.8: Error data
            'pending_total' => 0,
            'pending_by_table' => [],
            'pending_by_operation_type' => [ // Task 4.5: Operation type breakdown
                'insert' => 0,
                'update' => 0,
                'both' => 0
            ],
            'pending_deletions' => 0,
            'sync_enabled' => $this->get_setting('sync_enabled', '1') == '1',
            // Multi-location data
            'locations' => [],
            'active_locations' => 0,
            'total_locations' => 0,
            'conflicts_count' => 0,
            'realtime_enabled' => false,
            'metrics' => [],
            // Task 6.4: Add genuine failures and corrected false failures metrics
            'genuine_failures_count' => 0,
            'corrected_false_failures_count' => 0
        ];
        
        // Task 3.8: Get last sync error information
        $error_setting = $this->get_setting('last_sync_error');
        if (!empty($error_setting)) {
            $error_data = json_decode($error_setting, true);
            if ($error_data) {
                $status['last_sync_error'] = $error_data;
            }
        }
        
        // Get pending counts per table
        $tables = $this->get_sync_order();
        foreach ($tables as $table) {
            if ($this->table_has_sync_columns($table)) {
                $count = $this->db->where('sync_status', 'PENDING')
                                 ->count_all_results($table);
                if ($count > 0) {
                    $status['pending_by_table'][$table] = $count;
                    $status['pending_total'] += $count;
                }
                
                // Task 4.5: Get operation type breakdown if column exists
                if ($this->db->field_exists('sync_operation_type', $table)) {
                    // Count by operation type
                    $op_types = ['insert', 'update', 'both'];
                    foreach ($op_types as $op_type) {
                        $op_count = $this->db->where('sync_status', 'PENDING')
                                            ->where('sync_operation_type', $op_type)
                                            ->count_all_results($table);
                        if ($op_count > 0) {
                            $status['pending_by_operation_type'][$op_type] += $op_count;
                        }
                    }
                }
            }
        }
        
        // Get location data if location_registry table exists
        if ($this->db->table_exists('location_registry')) {
            $this->load->model('Location_registry_model');
            
            // Get all locations
            $locations = $this->db->get('location_registry')->result_array();
            $status['total_locations'] = count($locations);
            
            // Count active locations
            $active = $this->db->where('status', 'active')
                              ->where('sync_enabled', TRUE)
                              ->count_all_results('location_registry');
            $status['active_locations'] = $active;
            
            // Format location data for dashboard
            foreach ($locations as $location) {
                $status['locations'][] = [
                    'id' => $location['id'],
                    'location_name' => $location['location_name'],
                    'device_id' => $location['device_id'],
                    'status' => $location['status'],
                    'last_sync' => $location['last_sync_at'],
                    'is_online' => $location['status'] === 'active' && $location['sync_enabled']
                ];
            }
        }
        
        // Get conflicts count if sync_conflicts table exists
        if ($this->db->table_exists('sync_conflicts')) {
            $status['conflicts_count'] = $this->db->where('status', 'pending')
                                                  ->count_all_results('sync_conflicts');
        }
        
        // Check if real-time sync is enabled for any table
        if ($this->db->table_exists('sync_metadata')) {
            $realtime_tables = $this->db->where('real_time_sync', TRUE)
                                       ->count_all_results('sync_metadata');
            $status['realtime_enabled'] = $realtime_tables > 0;
        }
        
        // Get pending deletions count if sync_deletions table exists
        if ($this->db->table_exists('sync_deletions')) {
            $status['pending_deletions'] = $this->db->where('sync_status', 'PENDING')
                                                   ->count_all_results('sync_deletions');
        }
        
        // Task 6.4: Get genuine failures and corrected false failures from sync_failures table
        if ($this->db->table_exists('sync_failures')) {
            // Get genuine failures (excluding records verified to exist on remote)
            // Formula: COUNT(*) WHERE resolved=0 AND (verification_status='verified_not_exists' OR verification_status='not_verified' OR verification_status IS NULL)
            $genuine_failures_query = $this->db->select('COUNT(*) as count')
                ->from('sync_failures')
                ->where('resolved', 0)
                ->group_start()
                    ->where('verification_status', 'verified_not_exists')
                    ->or_where('verification_status', 'not_verified')
                    ->or_where('verification_status IS NULL', null, false)
                ->group_end()
                ->get();
            
            if ($genuine_failures_query->num_rows() > 0) {
                $status['genuine_failures_count'] = $genuine_failures_query->row()->count;
            }
            
            // Task 6.4: Get corrected false failures count
            // Count records where verification confirmed they exist on remote (verification_status='verified_exists')
            $false_failures_query = $this->db->select('COUNT(*) as count')
                ->from('sync_failures')
                ->where('verification_status', 'verified_exists')
                ->get();
            
            if ($false_failures_query->num_rows() > 0) {
                $status['corrected_false_failures_count'] = $false_failures_query->row()->count;
            }
        }
        
        return $status;
    }
    
    /**
     * Test remote connection (for setup wizard)
     * 
     * Requirements: 5.1
     * 
     * @return array Test results
     */
    public function test_connection() {
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];
        
        // Test internet connectivity
        if (!$this->is_online()) {
            $result['message'] = 'Cannot reach remote server';
            $result['details'][] = 'Check internet connection';
            $result['details'][] = 'Verify remote server is online';
            return $result;
        }
        
        // Test database connection
        try {
            $remote_db = $this->get_remote_db();
        } catch (Exception $e) {
            // Catch all exception types (DatabaseConnectionException, AuthenticationException, NetworkException, NoInternetException)
            $result['message'] = 'Cannot connect to remote database: ' . $e->getMessage();
            $result['details'][] = 'Check database credentials';
            $result['details'][] = 'Verify database user has permissions';
            
            // Add exception diagnostics if available
            if (method_exists($e, 'getDiagnostics')) {
                $diagnostics = $e->getDiagnostics();
                $result['details'][] = 'Error diagnostics: ' . json_encode($diagnostics);
            }
            
            return $result;
        }
        
        // Test database access
        try {
            $remote_db->query('SELECT 1');
            $result['success'] = true;
            $result['message'] = 'Connection successful';
            $result['details'][] = 'Remote server is reachable';
            $result['details'][] = 'Database connection established';
            $result['details'][] = 'Ready to sync';
        } catch (Exception $e) {
            $result['message'] = 'Database query failed: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    // ============================================================================
    // DASHBOARD METHODS
    // ============================================================================
    
    /**
     * Sync Dashboard Page
     * 
     * Display sync monitoring and control interface
     * Requirements: 4.1
     */
    public function dashboard() {
        // DEBUG: Log that dashboard method was reached
        log_message('debug', 'Sync_server::dashboard() - START');
        
        // Authentication is already checked in __construct() via check_auth()
        // No need for duplicate check here - it causes redirect loops
        
        // Check if sync module is enabled
        $this->check_sync_enabled();
        
        // Get sync status
        $page_data['sync_status'] = $this->get_sync_status();
        $page_data['page_name'] = 'sync_dashboard';
        $page_data['page_title'] = get_phrase('sync_dashboard');
        $page_data['skip_datatables'] = TRUE; // Don't load DataTables assets on sync dashboard
        
        log_message('debug', 'Sync_server::dashboard() - Loading view');
        $this->load->view('backend/main', $page_data);
        log_message('debug', 'Sync_server::dashboard() - END');
    }
    
    /**
     * Sync Status AJAX Endpoint
     * 
     * Returns current sync status as JSON
     * Requirements: 4.2
     */
    public function status_ajax() {
        if ($this->session->userdata('admin_login') != 1) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        // Get sync status
        $status = $this->get_sync_status();
        
        header('Content-Type: application/json');
        echo json_encode($status);
    }
    
    /**
     * Trigger Manual Sync
     * 
     * Initiates manual sync operation with support for different sync types
     * Requirements: 4.1, 4.4, 16.4, 16.5, 16.6
     * 
     * @param string $type Sync type: 'full' (default), 'pull', or 'push'
     */
    public function trigger_sync() {
        // Note: Authentication already handled by check_auth() in constructor
        
        // PERFORMANCE FIX: Close session to prevent recursive session handler errors during long operations
        session_write_close();
        
        // Increase execution time limit for sync operations
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M'); // Increase memory limit for large syncs
        
        // Get sync type from POST data
        $sync_type = $this->input->post('type') ?: 'full';
       
        $start_time = microtime(true);
        $this->log_message_custom("=== MANUAL SYNC STARTED (Type: $sync_type) ===");
        
        // Initialize progress tracking
        $this->update_sync_progress(0, 'Initializing sync...', '', '', false, '');
        
        try {
            // Check internet connectivity
            // Note: Manual sync buttons work regardless of sync_enabled setting
            // The sync_enabled setting only controls automatic/scheduled sync
            if (!$this->is_online()) {
                $this->log_message_custom('Sync skipped - no internet connection', 'info');
                $result = ['status' => 'skipped', 'message' => 'No internet connection'];
            }
            // Perform sync based on type
            else {
                switch ($sync_type) {
                    case 'pull':
                        $this->log_message_custom('Performing PULL operation only');
                        $this->update_sync_progress(10, 'Starting pull from remote...', '', '', false, '');
                        $results = $this->pull_all_tables();
                        $this->update_sync_progress(90, 'Pull operation completed', '', '', false, '');
                        
                        // Check if there was actually anything to pull
                        $total_pulled = 0;
                        $total_conflicts = 0;
                        $all_no_changes = true;
                        $has_errors = false;
                        $error_messages = [];
                        
                        foreach ($results as $table => $table_result) {
                            $total_pulled += $table_result['pulled'] ?? 0;
                            $total_conflicts += $table_result['conflicts'] ?? 0;
                            
                            // Check for error status (connection failures, etc.)
                            if (($table_result['status'] ?? '') === 'error') {
                                $has_errors = true;
                                $error_msg = $table_result['error'] ?? 'Unknown error';
                                $error_messages[] = "$table: $error_msg";
                            }
                            
                            if (($table_result['status'] ?? '') !== 'no_changes' && ($table_result['status'] ?? '') !== 'skipped') {
                                $all_no_changes = false;
                            }
                        }
                        
                        // If there were connection or other errors, return error status
                        if ($has_errors) {
                            // Extract unique error messages (many tables may have the same connection error)
                            $unique_errors = array_unique(array_map(function($msg) {
                                // Extract just the error part after the table name
                                $parts = explode(': ', $msg, 2);
                                return isset($parts[1]) ? $parts[1] : $msg;
                            }, $error_messages));
                            
                            // Count how many tables failed
                            $failed_table_count = count($error_messages);
                            
                            // Build a concise error message
                            if (count($unique_errors) === 1) {
                                // Same error for all tables - show it once with table count
                                $error_summary = reset($unique_errors);
                                if ($failed_table_count > 1) {
                                    $error_summary .= " ($failed_table_count tables affected)";
                                }
                            } else {
                                // Multiple different errors - show first unique error + count
                                $error_summary = reset($unique_errors) . " (and " . (count($unique_errors) - 1) . " other error types)";
                            }
                            
                            $this->update_sync_progress(100, 'Pull failed', '', '', true, $error_summary);
                            $result = [
                                'status' => 'error',
                                'operation' => 'pull',
                                'message' => $error_summary,
                                'results' => $results,
                                'duration' => round(microtime(true) - $start_time, 2),
                                'timestamp' => date('Y-m-d H:i:s'),
                                'total_pulled' => $total_pulled,
                                'total_conflicts' => $total_conflicts,
                                'error_details' => $error_messages // Full details still available for debugging
                            ];
                        }
                        // If nothing was pulled and all tables had no changes
                        elseif ($total_pulled === 0 && $total_conflicts === 0 && $all_no_changes) {
                            $this->update_sync_progress(100, 'No remote changes to pull', '', '', true, 'All data is up to date');
                            $result = [
                                'status' => 'info',
                                'operation' => 'pull',
                                'message' => 'No remote changes to pull. All data is up to date.',
                                'results' => $results,
                                'duration' => round(microtime(true) - $start_time, 2),
                                'timestamp' => date('Y-m-d H:i:s'),
                                'total_pulled' => 0,
                                'total_conflicts' => 0
                            ];
                        } else {
                            $final_msg = "Pulled $total_pulled records" . ($total_conflicts > 0 ? ", $total_conflicts conflicts" : "");
                            $this->update_sync_progress(100, $final_msg, '', '', true, $final_msg);
                            $result = [
                                'status' => 'success',
                                'operation' => 'pull',
                                'message' => "Pull from remote completed successfully. Pulled: $total_pulled, Conflicts: $total_conflicts",
                                'results' => $results,
                                'duration' => round(microtime(true) - $start_time, 2),
                                'timestamp' => date('Y-m-d H:i:s'),
                                'total_pulled' => $total_pulled,
                                'total_conflicts' => $total_conflicts
                            ];
                        }
                        break;
                        
                    case 'push':
                        $this->log_message_custom('=== PUSH-ONLY MODE ACTIVATED ===');
                        $this->log_message_custom('Performing PUSH operation only (no PULL)');
                        $this->update_sync_progress(10, 'Starting push to remote...', '', '', false, '');
                        $results = $this->push_all_tables();
                        $this->update_sync_progress(90, 'Push operation completed', '', '', false, '');
                        $this->log_message_custom('=== PUSH-ONLY MODE COMPLETED ===');
                        
                        // Check if there was actually anything to sync
                        $total_synced = 0;
                        $total_failed = 0;
                        $all_no_pending = true;
                        $has_errors = false;
                        $error_messages = [];
                        
                        foreach ($results as $table => $table_result) {
                            $total_synced += $table_result['synced'] ?? 0;
                            $total_failed += $table_result['failed'] ?? 0;
                            
                            // Check for error status (connection failures, etc.)
                            if (($table_result['status'] ?? '') === 'error') {
                                $has_errors = true;
                                $error_msg = $table_result['error'] ?? 'Unknown error';
                                $error_messages[] = "$table: $error_msg";
                            }
                            
                            if (($table_result['status'] ?? '') !== 'no_pending' && ($table_result['status'] ?? '') !== 'skipped') {
                                $all_no_pending = false;
                            }
                        }
                        
                        // If there were connection or other errors, return error status
                        if ($has_errors) {
                            // Extract unique error messages (many tables may have the same connection error)
                            $unique_errors = array_unique(array_map(function($msg) {
                                // Extract just the error part after the table name
                                $parts = explode(': ', $msg, 2);
                                return isset($parts[1]) ? $parts[1] : $msg;
                            }, $error_messages));
                            
                            // Count how many tables failed
                            $failed_table_count = count($error_messages);
                            
                            // Build a concise error message
                            if (count($unique_errors) === 1) {
                                // Same error for all tables - show it once with table count
                                $error_summary = reset($unique_errors);
                                if ($failed_table_count > 1) {
                                    $error_summary .= " ($failed_table_count tables affected)";
                                }
                            } else {
                                // Multiple different errors - show first unique error + count
                                $error_summary = reset($unique_errors) . " (and " . (count($unique_errors) - 1) . " other error types)";
                            }
                            
                            $this->update_sync_progress(100, 'Sync failed', '', '', true, $error_summary);
                            $result = [
                                'status' => 'error',
                                'operation' => 'push',
                                'message' => $error_summary,
                                'results' => $results,
                                'duration' => round(microtime(true) - $start_time, 2),
                                'timestamp' => date('Y-m-d H:i:s'),
                                'total_synced' => $total_synced,
                                'total_failed' => $total_failed,
                                'error_details' => $error_messages // Full details still available for debugging
                            ];
                        }
                        // If nothing was synced and all tables had no pending records
                        elseif ($total_synced === 0 && $total_failed === 0 && $all_no_pending) {
                            $this->update_sync_progress(100, 'No pending changes to push', '', '', true, 'All data is already synced');
                            $result = [
                                'status' => 'info',
                                'operation' => 'push',
                                'message' => 'No pending changes to push. All data is already synced.',
                                'results' => $results,
                                'duration' => round(microtime(true) - $start_time, 2),
                                'timestamp' => date('Y-m-d H:i:s'),
                                'total_synced' => 0,
                                'total_failed' => 0
                            ];
                        } else {
                            $final_msg = "Synced $total_synced records" . ($total_failed > 0 ? ", $total_failed failed" : "");
                            $this->update_sync_progress(100, $final_msg, '', '', true, $final_msg);
                            $result = [
                                'status' => 'success',
                                'operation' => 'push',
                                'message' => "Push to remote completed successfully. Synced: $total_synced, Failed: $total_failed",
                                'results' => $results,
                                'duration' => round(microtime(true) - $start_time, 2),
                                'timestamp' => date('Y-m-d H:i:s'),
                                'total_synced' => $total_synced,
                                'total_failed' => $total_failed
                            ];
                        }
                        break;
                        
                    case 'full':
                    default:
                        $this->log_message_custom('Performing FULL bidirectional sync (manual)');
                        $this->update_sync_progress(10, 'Starting full bidirectional sync...', '', '', false, '');
                        // For manual full sync, call sync_bidirectional() directly
                        // instead of auto_sync() to bypass the sync_enabled check
                        $results = $this->sync_bidirectional();
                        $this->update_sync_progress(90, 'Full sync completed', '', '', false, '');
                        
                        // Check for errors in both phases
                        $has_pull_errors = false;
                        $has_push_errors = false;
                        $error_messages = [];
                        
                        if (isset($results['pull_phase']) && is_array($results['pull_phase'])) {
                            foreach ($results['pull_phase'] as $table => $table_result) {
                                if (($table_result['status'] ?? '') === 'error') {
                                    $has_pull_errors = true;
                                    $error_msg = $table_result['error'] ?? 'Unknown error';
                                    $error_messages[] = "Pull $table: $error_msg";
                                }
                            }
                        }
                        
                        if (isset($results['push_phase']) && is_array($results['push_phase'])) {
                            foreach ($results['push_phase'] as $table => $table_result) {
                                if (($table_result['status'] ?? '') === 'error') {
                                    $has_push_errors = true;
                                    $error_msg = $table_result['error'] ?? 'Unknown error';
                                    $error_messages[] = "Push $table: $error_msg";
                                }
                            }
                        }
                        
                        // If there were errors in either phase, return error status
                        if ($has_pull_errors || $has_push_errors) {
                            // Extract unique error messages (many tables may have the same connection error)
                            $unique_errors = array_unique(array_map(function($msg) {
                                // Extract just the error part after the table name and phase
                                $parts = explode(': ', $msg, 2);
                                return isset($parts[1]) ? $parts[1] : $msg;
                            }, $error_messages));
                            
                            // Count how many tables failed
                            $failed_table_count = count($error_messages);
                            
                            // Build a concise error message
                            if (count($unique_errors) === 1) {
                                // Same error for all tables - show it once with table count
                                $error_summary = reset($unique_errors);
                                if ($failed_table_count > 1) {
                                    $error_summary .= " ($failed_table_count operations affected)";
                                }
                            } else {
                                // Multiple different errors - show first unique error + count
                                $error_summary = reset($unique_errors) . " (and " . (count($unique_errors) - 1) . " other error types)";
                            }
                            
                            $this->update_sync_progress(100, 'Sync failed', '', '', true, $error_summary);
                            $result = [
                                'status' => 'error',
                                'operation' => 'full',
                                'message' => $error_summary,
                                'results' => $results,
                                'duration' => round(microtime(true) - $start_time, 2),
                                'timestamp' => date('Y-m-d H:i:s'),
                                'error_details' => $error_messages // Full details still available for debugging
                            ];
                        }
                        // Check if there was actually anything to sync in both phases
                        else {
                            $pull_had_changes = false;
                            if (isset($results['pull_phase']) && is_array($results['pull_phase'])) {
                                foreach ($results['pull_phase'] as $table => $table_result) {
                                    $pulled = $table_result['pulled'] ?? 0;
                                    $conflicts = $table_result['conflicts'] ?? 0;
                                    $status = $table_result['status'] ?? '';
                                    
                                    if ($pulled > 0 || $conflicts > 0 || ($status !== 'no_changes' && $status !== 'skipped')) {
                                        $pull_had_changes = true;
                                        break;
                                    }
                                }
                            }
                            
                            // Check push phase
                            $push_had_changes = false;
                            if (isset($results['push_phase']) && is_array($results['push_phase'])) {
                                foreach ($results['push_phase'] as $table => $table_result) {
                                    $synced = $table_result['synced'] ?? 0;
                                    $failed = $table_result['failed'] ?? 0;
                                    $status = $table_result['status'] ?? '';
                                    
                                    if ($synced > 0 || $failed > 0 || ($status !== 'no_pending' && $status !== 'skipped')) {
                                        $push_had_changes = true;
                                        break;
                                    }
                                }
                            }
                            
                            // If neither phase had changes, return info status
                            if (!$pull_had_changes && !$push_had_changes) {
                                $this->update_sync_progress(100, 'No changes to sync', '', '', true, 'All data is up to date');
                                $result = [
                                    'status' => 'info',
                                    'operation' => 'full',
                                    'message' => 'No changes to sync. All data is up to date in both directions.',
                                    'results' => $results,
                                    'duration' => round(microtime(true) - $start_time, 2),
                                    'timestamp' => date('Y-m-d H:i:s')
                                ];
                            } else {
                                // Build a descriptive message
                                $messages = [];
                                
                                if ($pull_had_changes) {
                                    $total_pulled = 0;
                                    $total_conflicts = 0;
                                    foreach ($results['pull_phase'] as $table_result) {
                                        $total_pulled += $table_result['pulled'] ?? 0;
                                        $total_conflicts += $table_result['conflicts'] ?? 0;
                                    }
                                    $messages[] = "Pulled: $total_pulled" . ($total_conflicts > 0 ? ", Conflicts: $total_conflicts" : "");
                                }
                                
                                if ($push_had_changes) {
                                    $total_synced = 0;
                                    $total_failed = 0;
                                    foreach ($results['push_phase'] as $table_result) {
                                        $total_synced += $table_result['synced'] ?? 0;
                                        $total_failed += $table_result['failed'] ?? 0;
                                    }
                                    $messages[] = "Pushed: $total_synced" . ($total_failed > 0 ? ", Failed: $total_failed" : "");
                                }
                                
                                $final_msg = implode(', ', $messages);
                                $this->update_sync_progress(100, $final_msg, '', '', true, $final_msg);
                                $result = [
                                    'status' => 'success',
                                    'operation' => 'full',
                                    'message' => 'Full bidirectional sync completed. ' . $final_msg,
                                    'results' => $results,
                                    'duration' => round(microtime(true) - $start_time, 2),
                                    'timestamp' => date('Y-m-d H:i:s')
                                ];
                            }
                        }
                        break;
                }
                
                // Update last sync time for successful manual syncs and mark as SYNCED
                if ($result['status'] === 'success') {
                    // Use direct SQL to update BOTH settings AND mark as SYNCED in one operation
                    $current_time = date('Y-m-d H:i:s');
                    $this->db->query("UPDATE settings SET description = '$current_time', sync_status = 'SYNCED' WHERE type = 'last_sync_time'");
                    $this->db->query("UPDATE settings SET description = 'success', sync_status = 'SYNCED' WHERE type = 'last_sync_status'");
                }
            }
            
            $duration = round(microtime(true) - $start_time, 2);
            $this->log_message_custom("=== MANUAL SYNC COMPLETED in {$duration}s ===");
            
        } catch (Exception $e) {
            $this->log_message_custom('Manual sync failed: ' . $e->getMessage(), 'error');
            // Use direct SQL to update setting AND mark as SYNCED in one operation
            $this->db->query("UPDATE settings SET description = 'failed', sync_status = 'SYNCED' WHERE type = 'last_sync_status'");
            
            $this->update_sync_progress(100, 'Sync failed', '', '', true, 'Error: ' . $e->getMessage());
            $result = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Get Sync Progress
     * 
     * Returns current sync progress for real-time UI updates
     * This endpoint is polled by the frontend during sync operations
     * Reads from temporary file because session is closed during sync
     * 
     * @return void Outputs JSON response
     */
    public function get_sync_progress() {
        if ($this->session->userdata('admin_login') != 1) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        // Read progress from temporary file (primary method)
        $progress_file = APPPATH . 'cache/sync_progress.json';
        $progress_data = null;
        
        if (file_exists($progress_file) && is_readable($progress_file)) {
            $json_content = file_get_contents($progress_file);
            $progress_data = json_decode($json_content, true);
            
            // Check if file is stale (older than 5 minutes)
            if ($progress_data && isset($progress_data['updated_at'])) {
                if (time() - $progress_data['updated_at'] > 300) {
                    $progress_data = null; // Treat as expired
                }
            }
        }
        
        // Fallback: Try session if file doesn't exist
        if (!$progress_data) {
            $progress_data = $this->session->userdata('sync_progress');
        }
        
        if (!$progress_data || !isset($progress_data['running']) || !$progress_data['running']) {
            // No sync currently running
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'not_running',
                'progress' => 0,
                'message' => 'No sync operation in progress'
            ]);
            return;
        }
        
        // Return current progress
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'progress' => $progress_data['progress'] ?? 0,
            'message' => $progress_data['message'] ?? '',
            'current_table' => $progress_data['current_table'] ?? '',
            'table_progress' => $progress_data['table_progress'] ?? '',
            'complete' => $progress_data['complete'] ?? false,
            'final_message' => $progress_data['final_message'] ?? ''
        ]);
    }
    
    /**
     * Update Sync Progress
     * 
     * Internal method to update sync progress during operations
     * Uses temporary file storage instead of session because session is closed during sync
     * 
     * @param int $progress Progress percentage (0-100)
     * @param string $message Progress message
     * @param string $current_table Current table being synced
     * @param string $table_progress Table-specific progress (e.g., "15/50")
     * @param bool $complete Whether sync is complete
     * @param string $final_message Final completion message
     */
    private function update_sync_progress($progress, $message = '', $current_table = '', $table_progress = '', $complete = false, $final_message = '') {
        $progress_data = [
            'running' => !$complete,
            'progress' => min(100, max(0, $progress)),
            'message' => $message,
            'current_table' => $current_table,
            'table_progress' => $table_progress,
            'complete' => $complete,
            'final_message' => $final_message,
            'updated_at' => time()
        ];
        
        // Use temporary file storage because session is closed during sync operations
        $progress_file = APPPATH . 'cache/sync_progress.json';
        
        // Write to cache directory (writable by web server)
        if (is_writable(APPPATH . 'cache/')) {
            file_put_contents($progress_file, json_encode($progress_data));
        } else {
            // Fallback: Try to reopen session temporarily
            session_start();
            $this->session->set_userdata('sync_progress', $progress_data);
            session_write_close();
        }
    }
    
    /**
     * Start progress tracking
     * 
     * Initializes progress tracking at the beginning of sync operations
     * Requirements: 2.4, 2.11
     * Task: 3.5
     */
    private function start_progress_tracking() {
        $this->update_sync_progress(
            0, 
            'Initializing sync...', 
            '', 
            '', 
            false, 
            ''
        );
        $this->log_message_custom('Progress tracking started', 'info');
    }
    
    /**
     * Complete progress tracking
     * 
     * Marks sync operation as complete and sets final message
     * Requirements: 2.4, 2.11
     * Task: 3.5
     * 
     * @param string $final_message Final status message
     */
    private function complete_progress_tracking($final_message) {
        $this->update_sync_progress(
            100, 
            $final_message, 
            '', 
            '', 
            true, 
            $final_message
        );
        $this->log_message_custom("Progress tracking completed: $final_message", 'info');
    }
    
    /**
     * Log record-level sync failure
     * 
     * Logs individual record failures to sync_failures table for tracking and retry
     * Requirements: 2.10
     * Task: 3.6
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @param string $operation 'push' or 'pull'
     * @param string $error_type Error type (foreign_key_violation, duplicate_entry, data_constraint, unknown)
     * @param string $error_message Error message
     * @param array $error_details Additional error details (optional)
     */
    private function log_record_failure($table_name, $record_id, $operation, $error_type, $error_message, $error_details = []) {
        try {
            // Prepare data for insertion
            $data = [
                'record_id' => $record_id,
                'table_name' => $table_name,
                'operation' => $operation,
                'error_type' => $error_type,
                'error_message' => substr($error_message, 0, 1000), // Limit length
                'error_details' => json_encode($error_details),
                'timestamp' => date('Y-m-d H:i:s'),
                'retry_count' => 0,
                'resolved' => 0
            ];
            
            // Insert into sync_failures table
            $this->db->insert('sync_failures', $data);
            
            $this->log_message_custom(
                "[Sync Record Failure] {$table_name}.{$record_id} - {$operation} - {$error_type}: {$error_message}", 
                'error'
            );
            
        } catch (Exception $e) {
            // Log error but don't halt sync
            $this->log_message_custom(
                "[Sync] Failed to log record failure for {$table_name}.{$record_id}: " . $e->getMessage(), 
                'error'
            );
        }
    }
    
    // ============================================================================
    // SETTINGS MANAGEMENT
    // ============================================================================
    
    /**
     * Sync Settings Page
     * 
     * Display and manage sync configuration
     * Requirements: 5.3
     */
    public function settings() {
        if ($this->session->userdata('admin_login') != 1)
            redirect(site_url('login'), 'refresh');
        
        // Check if sync module is enabled
        $this->check_sync_enabled();
        
        // Get current settings
        $page_data['remote_host'] = $this->get_setting('remote_db_host', '');
        $page_data['remote_port'] = $this->get_setting('remote_db_port', '3306');
        $page_data['remote_user'] = $this->get_setting('remote_db_user', '');
        $page_data['remote_database'] = $this->get_setting('remote_db_name', '');
        $page_data['sync_frequency'] = $this->get_setting('sync_frequency_hours', '2');
        $page_data['sync_enabled'] = $this->get_setting('sync_enabled', '1');
        $page_data['device_id'] = $this->get_setting('device_id', 'local-server-001');
        $page_data['remote_device_id'] = $this->get_setting('remote_device_id', '');
        
        // Get sync status for online/offline indicator
        $page_data['sync_status'] = $this->get_sync_status();
        
        $page_data['page_name'] = 'sync_settings';
        $page_data['page_title'] = get_phrase('sync_settings');
        
        $this->load->view('backend/main', $page_data);
    }
    
    /**
     * Save Sync Settings
     * 
     * Update sync configuration
     * Requirements: 5.3
     */
    public function save_settings() {
        if ($this->session->userdata('admin_login') != 1) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        // Validate device IDs
        $device_id = trim($this->input->post('device_id'));
        if (empty($device_id)) {
            $this->session->set_flashdata('error_message', 'Local Device ID is required');
            redirect(site_url('sync_server/settings'));
            return;
        }
        
        if (!preg_match('/^[a-zA-Z0-9-_]+$/', $device_id)) {
            $this->session->set_flashdata('error_message', 'Device ID can only contain letters, numbers, hyphens, and underscores');
            redirect(site_url('sync_server/settings'));
            return;
        }
        
        $remote_device_id = trim($this->input->post('remote_device_id'));
        if (!empty($remote_device_id) && !preg_match('/^[a-zA-Z0-9-_]+$/', $remote_device_id)) {
            $this->session->set_flashdata('error_message', 'Remote Device ID can only contain letters, numbers, hyphens, and underscores');
            redirect(site_url('sync_server/settings'));
            return;
        }
        
        // Get form data
        $settings = [
            'remote_db_host' => $this->input->post('remote_host'),
            'remote_db_port' => $this->input->post('remote_port'),
            'remote_db_user' => $this->input->post('remote_user'),
            'remote_db_name' => $this->input->post('remote_database'),
            'sync_frequency_hours' => $this->input->post('sync_frequency'),
            'sync_enabled' => '0', // ALWAYS DISABLED - Use manual sync from dashboard instead
            'device_id' => $device_id,
            'remote_device_id' => $remote_device_id
        ];
        
        // Handle password separately - only update if provided
        $remote_pass = $this->input->post('remote_pass');
        if (!empty($remote_pass)) {
            $settings['remote_db_pass'] = base64_encode($remote_pass);
        }
        
        // Save each setting
        foreach ($settings as $key => $value) {
            // Skip empty remote_device_id (it's optional)
            if ($key === 'remote_device_id' && empty($value)) {
                continue;
            }
            $this->update_setting($key, $value);
        }
        
        // Test connection if credentials provided
        if (!empty($settings['remote_db_host']) && !empty($settings['remote_db_user'])) {
            $test_result = $this->test_connection();
            
            if ($test_result['success']) {
                $message = 'Settings saved successfully! Connection test passed.';
            } else {
                $message = 'Settings saved but connection test failed: ' . $test_result['message'];
            }
        } else {
            $message = 'Settings saved successfully!';
        }
        
        $this->session->set_flashdata('flash_message', $message);
        redirect(site_url('sync_server/settings'));
    }
    
    /**
     * Toggle sync enabled setting via AJAX
     * 
     * Allows immediate update of the sync_enabled setting without form submission
     * 
     * @return void (outputs JSON)
     */
    public function toggle_sync_enabled() {
        header('Content-Type: application/json');
        
        if ($this->session->userdata('admin_login') != 1) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        
        // ALWAYS DISABLED - Automatic sync is permanently disabled
        // Users should use manual sync from the dashboard instead
        echo json_encode([
            'status' => 'error',
            'message' => 'Automatic sync is disabled. Please use manual sync from the dashboard instead.',
            'enabled' => false
        ]);
    }
    
    // ============================================================================
    // ADVANCED ERROR HANDLING METHODS (Task 12)
    // ============================================================================
    
    /**
     * Check disk space before sync
     * 
     * Requirements: 3.2 (Task 12.4)
     * 
     * @return array Status with available space
     */
    private function check_disk_space() {
        $disk_free = disk_free_space('.');
        $disk_total = disk_total_space('.');
        
        $free_gb = round($disk_free / (1024 * 1024 * 1024), 2);
        $free_mb = round($disk_free / (1024 * 1024), 2);
        
        $status = [
            'free_bytes' => $disk_free,
            'free_gb' => $free_gb,
            'free_mb' => $free_mb,
            'total_gb' => round($disk_total / (1024 * 1024 * 1024), 2),
            'status' => 'ok'
        ];
        
        // Critical: < 100MB free
        if ($free_mb < 100) {
            $status['status'] = 'critical';
            $status['message'] = "Critical: Only {$free_mb}MB free. Sync aborted.";
            $this->log_message_custom($status['message'], 'error');
            $this->send_admin_alert('Disk Space Critical', $status['message']);
            return $status;
        }
        
        // Warning: < 1GB free
        if ($free_gb < 1) {
            $status['status'] = 'warning';
            $status['message'] = "Warning: Only {$free_gb}GB free. Consider freeing up space.";
            $this->log_message_custom($status['message'], 'info');
        }
        
        return $status;
    }
    

    /**
     * Check if record should be retried or flagged for manual review
     * 
     * Requirements: 3.2 (Task 12.2)
     * 
     * @param string $table Table name
     * @param array $record Record data
     * @return bool True if should retry, false if needs manual review
     */
    private function should_retry_record($table, $record) {
        // Get current retry count
        $retry_count = isset($record['retry_count']) ? (int)$record['retry_count'] : 0;
        
        // Flag for manual review after 3 retries
        if ($retry_count >= 3) {
            $this->log_message_custom("Record in $table has failed 3 times, flagging for manual review", 'warning');
            
            // Mark as needs manual review
            $primary_key = $this->get_primary_key($table);
            if ($primary_key && isset($record[$primary_key])) {
                $this->db->where($primary_key, $record[$primary_key])
                         ->update($table, [
                             'sync_status' => 'MANUAL_REVIEW',
                             'sync_error' => 'Failed after 3 retry attempts'
                         ]);
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Get primary key column name for a table
     * 
     * @param string $table Table name
     * @return string|null Primary key column name
     */
    private function get_primary_key($table) {
        $query = $this->db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        $result = $query->row_array();
        return $result ? $result['Column_name'] : null;
    }
    
    /**
     * Get natural key columns for a table
     * 
     * Natural keys are business identifiers used to match records across databases
     * when AUTO_INCREMENT primary keys differ between local and remote.
     * 
     * This prevents duplicate inserts during sync when:
     * - Local has invoice_id=100
     * - Remote has invoice_id=500 for the SAME invoice
     * - Without natural key matching, second sync creates duplicate
     * 
     * @param string $table Table name
     * @return array|null Array of column names that form natural key, or null if not configured
     */
    private function get_natural_key($table) {
        $natural_keys = [
            // Invoice: invoice_code + student_id uniquely identifies an invoice
            'invoice' => ['invoice_code', 'student_id'],
            
            // Student: student_code is the business identifier
            'student' => ['student_code'],
            
            // Teacher: teacher_code is the business identifier
            'teacher' => ['teacher_code'],
            
            // Parent: phone is the unique identifier
            'parent' => ['phone'],
            
            // Enrollment: combination uniquely identifies a student's enrollment
            'enroll' => ['student_id', 'class_id', 'year', 'term'],
            
            // Payment: invoice + timestamp uniquely identifies a payment
            'payment' => ['invoice_code', 'payment_timestamp'],
            
            // Daily fee transaction: student + date + meal type is unique
            'daily_fee_transaction' => ['student_id', 'date', 'meal_type'],
            
            // Invoice items: invoice + item title is unique
            'invoice_item' => ['invoice_code', 'title'],
            
            // Add more tables as needed
        ];
        
        return isset($natural_keys[$table]) ? $natural_keys[$table] : null;
    }
    
    /**
     * Find a record by natural key (device_id + record_id or business identifiers)
     * 
     * Helper method extracted for reuse in sync_operation_type logic (Task 3.9)
     * 
     * @param object $remote_db Remote database connection
     * @param string $table Table name
     * @param array $record Record data containing natural key values
     * @return array|null Remote record if found, null otherwise
     */
    private function find_by_natural_key($remote_db, $table, $record) {
        $natural_key_cols = $this->get_natural_key($table);
        
        if (!$natural_key_cols) {
            return null;
        }
        
        // Build WHERE clause using natural key columns
        $all_cols_present = true;
        foreach ($natural_key_cols as $col) {
            if (isset($record[$col])) {
                $remote_db->where($col, $record[$col]);
            } else {
                // Natural key incomplete
                $this->log_message_custom("Natural key column '$col' missing from record for $table", 'debug');
                $all_cols_present = false;
                break;
            }
        }
        
        if (!$all_cols_present) {
            return null;
        }
        
        // Execute query
        $result = $remote_db->get($table)->row_array();
        
        if ($result) {
            $this->log_message_custom("Found record in $table by natural key", 'debug');
        }
        
        return $result;
    }
    
    /**
     * Verify if a record exists on the remote database using natural key
     * 
     * This method is used to distinguish between genuine sync failures (record not on remote)
     * and false failures (record on remote but local status update failed).
     * 
     * Bug Fix: Task 3.1 - Sync Status Accuracy and Retry
     * When a sync operation succeeds on remote but fails during local status update,
     * we verify the remote state before marking the record as FAILED.
     * 
     * @param object $remote_db Remote database connection
     * @param string $table Table name
     * @param array $record Record data containing natural key values
     * @return bool TRUE if record exists on remote, FALSE otherwise
     */
    private function verify_record_exists_on_remote($remote_db, $table, $record) {
        try {
            $this->log_message_custom("Verifying remote state for $table record", 'info');
            
            // Extract natural key from record
            $natural_key_cols = $this->get_natural_key($table);
            
            if (!$natural_key_cols) {
                $this->log_message_custom("No natural key configured for $table, cannot verify remote state", 'warning');
                return false; // Conservative: assume genuine failure if can't verify
            }
            
            // Build SELECT query using natural key
            $where_conditions = [];
            $missing_cols = [];
            
            foreach ($natural_key_cols as $col) {
                if (isset($record[$col])) {
                    $value = $record[$col];
                    $escaped_value = is_numeric($value) ? $value : "'" . $remote_db->escape_str($value) . "'";
                    $where_conditions[] = "`$col` = $escaped_value";
                } else {
                    $missing_cols[] = $col;
                }
            }
            
            if (!empty($missing_cols)) {
                $this->log_message_custom("Cannot verify remote state for $table: missing natural key columns (" . implode(', ', $missing_cols) . ")", 'warning');
                return false; // Conservative: assume genuine failure if natural key incomplete
            }
            
            // Build and execute verification query
            $where_clause = implode(' AND ', $where_conditions);
            $verify_query = "SELECT COUNT(*) as count FROM `$table` WHERE $where_clause";
            
            $this->log_message_custom("Verification query: $verify_query", 'debug');
            
            $result = $remote_db->query($verify_query);
            
            if (!$result) {
                $error = $remote_db->error();
                $this->log_message_custom("Verification query failed: " . $error['message'], 'error');
                return false; // Conservative: assume genuine failure on verification error
            }
            
            $row = $result->row_array();
            $count = $row['count'] ?? 0;
            
            if ($count > 0) {
                $this->log_message_custom("✓ Verification SUCCESS: Record exists on remote (count=$count)", 'info');
                $this->log_message_custom("  Table: $table", 'info');
                $this->log_message_custom("  Natural key: " . implode(', ', $natural_key_cols), 'info');
                
                // Log to audit trail for administrator visibility
                if (method_exists($this, 'audit_logger') && isset($this->audit_logger)) {
                    $primary_key = $this->get_primary_key($table);
                    $record_id = $record[$primary_key] ?? 0;
                    
                    $this->audit_logger->log(
                        $table,
                        $record_id,
                        'VERIFY',
                        $this->device_id,
                        $this->get_remote_device_id(),
                        null,
                        null,
                        'verification',
                        $this->session->userdata('login_user_id'),
                        0,
                        'success',
                        "Record verified to exist on remote (false failure corrected)"
                    );
                }
                
                return true;
            } else {
                $this->log_message_custom("✗ Verification FAILED: Record NOT found on remote", 'warning');
                $this->log_message_custom("  Table: $table", 'info');
                $this->log_message_custom("  Natural key: " . implode(', ', $natural_key_cols), 'info');
                return false;
            }
            
        } catch (Exception $e) {
            // Handle connection errors during verification
            $this->log_message_custom("Exception during remote verification: " . $e->getMessage(), 'error');
            
            // Conservative approach: if verification fails due to error, assume genuine failure
            // This prevents marking records as SYNCED when we can't actually verify
            return false;
        }
    }
    
    /**
     * Filter record to only include columns that exist on remote table
     * 
     * This prevents "Unknown column" errors when local and remote schemas differ.
     * Caches remote table structures for performance.
     * 
     * @param object $remote_db Remote database connection
     * @param string $table Table name
     * @param array $record Record data
     * @return array Filtered record with only columns that exist on remote
     */
    private function filter_record_for_remote($remote_db, $table, $record) {
        static $remote_columns_cache = [];
        
        try {
            // Check cache first
            if (!isset($remote_columns_cache[$table])) {
                // Get remote table columns
                $query = $remote_db->query("SHOW COLUMNS FROM `$table`");
                $columns = [];
                
                if ($query && $query->num_rows() > 0) {
                    foreach ($query->result() as $row) {
                        $columns[] = $row->Field;
                    }
                }
                
                $remote_columns_cache[$table] = $columns;
                $this->log_message_custom("Cached remote columns for $table: " . count($columns) . " columns", 'debug');
            }
            
            $remote_columns = $remote_columns_cache[$table];
            
            // Filter record to only include columns that exist on remote
            $filtered_record = [];
            $removed_columns = [];
            
            foreach ($record as $column => $value) {
                if (in_array($column, $remote_columns)) {
                    $filtered_record[$column] = $value;
                } else {
                    $removed_columns[] = $column;
                }
            }
            
            // Log if columns were removed
            if (!empty($removed_columns)) {
                $this->log_message_custom("Filtered out columns for $table: " . implode(', ', $removed_columns), 'debug');
            }
            
            return $filtered_record;
            
        } catch (Exception $e) {
            // If filtering fails, log error and return original record
            $this->log_message_custom("Failed to filter record for $table: " . $e->getMessage(), 'warning');
            return $record;
        }
    }
    
    /**
     * Insert or update a record on remote database using natural key matching
     * 
     * CRITICAL FIX FOR DUPLICATE INSERT BUG:
     * 
     * Problem: AUTO_INCREMENT primary keys are NOT globally unique across databases.
     * - Local creates invoice with invoice_id=100
     * - First sync: Remote assigns invoice_id=500 (different ID for same record)
     * - Update locally (still invoice_id=100)
     * - Second sync: Tries to insert invoice_id=100 → Remote creates NEW record (invoice_id=501)
     * - Result: DUPLICATE invoices!
     * 
     * Solution: Use NATURAL KEYS (business identifiers) to match records:
     * - Check if record exists by invoice_code + student_id (not invoice_id)
     * - If exists: UPDATE using remote's primary key
     * - If not exists: INSERT (let remote assign new AUTO_INCREMENT ID)
     * 
     * This method avoids foreign key constraint errors that occur with REPLACE INTO.
     * REPLACE tries to DELETE then INSERT, which fails if child records exist.
     * 
     * @param object $remote_db Remote database connection
     * @param string $table Table name
     * @param array $record Record data (already filtered for remote columns)
     * @return bool True on success
     * @throws Exception on failure
     */
    private function insert_or_update_remote($remote_db, $table, $record) {
        try {
            // Get primary key for this table
            $primary_key = $this->get_primary_key($table);
            if (!$primary_key) {
                throw new Exception("Cannot find primary key for table $table");
            }
            
            // Get generated columns to exclude them from INSERT/UPDATE
            $generated_columns = $this->get_generated_columns($remote_db, $table);
            
            // Remove generated columns from record
            foreach ($generated_columns as $col) {
                unset($record[$col]);
            }
            
            // If no columns to sync, skip
            if (empty($record)) {
                $this->log_message_custom("Skipping $table - no columns to sync", 'warning');
                return true;
            }
            
            // PERFORMANCE FIX: Use INSERT ... ON DUPLICATE KEY UPDATE
            // This is atomic and faster than checking existence first
            
            // Prepare column names and values for INSERT
            $columns = array_keys($record);
            $escaped_columns = array_map(function($col) use ($remote_db) {
                return $remote_db->escape_identifiers($col);
            }, $columns);
            
            // Prepare values (escaped)
            $values = array_values($record);
            $escaped_values = array_map(function($val) use ($remote_db) {
                return $remote_db->escape($val);
            }, $values);
            
            // Prepare UPDATE clause (all columns except primary key)
            $update_parts = [];
            foreach ($columns as $col) {
                if ($col !== $primary_key) {
                    $escaped_col = $remote_db->escape_identifiers($col);
                    $update_parts[] = "$escaped_col = VALUES($escaped_col)";
                }
            }
            
            // If no columns to update (only PK), still allow INSERT
            if (empty($update_parts)) {
                $update_clause = "$primary_key = $primary_key"; // No-op update
            } else {
                $update_clause = implode(', ', $update_parts);
            }
            
            // Build and execute query
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s",
                $remote_db->escape_identifiers($table),
                implode(', ', $escaped_columns),
                implode(', ', $escaped_values),
                $update_clause
            );
            
            $this->log_message_custom("Executing upsert for $table (PK={$record[$primary_key]})", 'debug');
            
            $result = $remote_db->query($sql);
            
            if ($result) {
                $affected = $remote_db->affected_rows();
                
                // affected_rows: 1 = inserted, 2 = updated, 0 = no change
                if ($affected == 1) {
                    $this->log_message_custom("Inserted new $table record (PK={$record[$primary_key]})", 'info');
                } elseif ($affected == 2) {
                    $this->log_message_custom("Updated existing $table record (PK={$record[$primary_key]})", 'info');
                } else {
                    $this->log_message_custom("No change for $table record (PK={$record[$primary_key]})", 'debug');
                }
                
                return true;
            } else {
                $error = $remote_db->error();
                throw new Exception("Upsert failed: " . $error['message']);
            }
            
        } catch (Exception $e) {
            $this->log_message_custom("insert_or_update_remote failed for $table: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Get list of generated columns for a table
     * 
     * Generated columns are computed automatically by MySQL and cannot be
     * explicitly set in INSERT/UPDATE queries
     * 
     * @param object $db Database connection
     * @param string $table Table name
     * @return array List of generated column names
     */
    private function get_generated_columns($db, $table) {
        static $cache = [];
        
        // Return cached result if available
        $cache_key = get_class($db) . '_' . $table;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }
        
        $generated = [];
        
        try {
            // Query INFORMATION_SCHEMA to find generated columns
            $query = "SELECT COLUMN_NAME 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = ? 
                     AND IS_GENERATED = 'ALWAYS'";
            
            $result = $db->query($query, [$table]);
            
            if ($result && $result->num_rows() > 0) {
                foreach ($result->result_array() as $row) {
                    $generated[] = $row['COLUMN_NAME'];
                }
                
                if (!empty($generated)) {
                    $this->log_message_custom("Table $table has generated columns: " . implode(', ', $generated), 'info');
                }
            }
        } catch (Exception $e) {
            // If query fails, log but don't break sync
            $this->log_message_custom("Could not detect generated columns for $table: " . $e->getMessage(), 'warning');
        }
        
        // Cache the result
        $cache[$cache_key] = $generated;
        
        return $generated;
    }
    
    /**
     * Get remote device ID
     * 
     * Returns the device ID of the remote server for audit logging
     * 
     * @return string Remote device ID
     */
    private function get_remote_device_id() {
        // Try to get from settings first
        $remote_device_id = $this->get_setting('remote_device_id', null);
        
        if ($remote_device_id) {
            return $remote_device_id;
        }
        
        // Fallback: use remote host as identifier
        $remote_host = $this->get_setting('remote_db_host', 'remote-server');
        return 'remote-' . preg_replace('/[^a-zA-Z0-9-]/', '-', $remote_host);
    }
    
    /**
     * Set timeout for database operations
     * 
     * Requirements: 3.2 (Task 12.5)
     * 
     * @param object $db Database connection
     * @param int $timeout Timeout in seconds
     */
    private function set_db_timeout($db, $timeout = 30) {
        try {
            // Set connection timeout
            $db->query("SET SESSION wait_timeout = $timeout");
            $db->query("SET SESSION interactive_timeout = $timeout");
            
            // Set query timeout (MySQL 5.7.8+)
            $db->query("SET SESSION max_execution_time = " . ($timeout * 1000)); // milliseconds
            
            $this->log_message_custom("Database timeout set to {$timeout}s");
        } catch (Exception $e) {
            $this->log_message_custom('Could not set database timeout: ' . $e->getMessage(), 'info');
        }
    }
    
    /**
     * Sync Table Management Page
     * 
     * Shows a UI for managing which tables have sync enabled/disabled
     */
    public function table_management() {
        // Check if sync module is enabled
        $this->check_sync_enabled();
        
        $data['page_name'] = 'sync_table_management';
        $data['page_title'] = 'Sync Table Management';
        $data['sync_status'] = $this->get_sync_status();
        
        $this->load->view('backend/main', $data);
    }
    
    /**
     * Get all tables with their sync status
     * 
     * Returns JSON with table information including:
     * - table_name
     * - has_sync_columns
     * - sync_enabled
     * - record_count
     * - description
     */
    /**
     * Get user-friendly exclusion reason for a table
     * 
     * @param string $table_name The table name to get exclusion reason for
     * @return string The reason why the table is excluded from sync
     */
    private static function get_exclusion_reason($table_name) {
        // Session tables - transient, high-volume data
        if (in_array($table_name, ['sessions', 'ci_sessions'])) {
            return 'Session table - transient data, high volume';
        }
        
        // Sync system tables - prevent recursion
        if (in_array($table_name, ['sync_audit_log', 'sync_conflicts', 'sync_deletions', 'sync_metadata'])) {
            return 'Sync system table - excluded to prevent recursion';
        }
        
        // Default exclusion reason
        return 'Excluded from sync by system configuration';
    }

    public function get_all_tables_status() {
        try {
            // Load sync configuration to get excluded tables list
            $this->config->load('sync', TRUE);
            $excluded_tables = $this->config->item('deletion_tracking_excluded_tables', 'sync') ?: [];
            
            // Get all tables in database
            $all_tables = $this->db->list_tables();
            
            $tables_info = [];
            
            foreach ($all_tables as $table) {
                // Skip sync system tables
                if (strpos($table, 'sync_') === 0) {
                    continue;
                }
                
                // Check if table has sync columns
                $fields = $this->db->list_fields($table);
                
                // Check for all required sync columns
                $required_sync_columns = ['sync_status', 'last_modified_at', 'device_id', 'version'];
                $has_sync_columns = true;
                foreach ($required_sync_columns as $col) {
                    if (!in_array($col, $fields)) {
                        $has_sync_columns = false;
                        break;
                    }
                }
                
                // Get sync metadata if exists
                $metadata = $this->db->get_where('sync_metadata', ['table_name' => $table])->row();
                
                // Get record count
                $record_count = $this->db->count_all($table);
                
                // Check if table is excluded from sync operations
                $is_excluded = in_array($table, $excluded_tables);
                
                $tables_info[] = [
                    'table_name' => $table,
                    'has_sync_columns' => $has_sync_columns,
                    'sync_enabled' => $metadata ? (bool)$metadata->sync_enabled : false,
                    'record_count' => $record_count,
                    'description' => $metadata ? $metadata->description : ucwords(str_replace('_', ' ', $table)),
                    'sync_order' => $metadata ? $metadata->sync_order : 999,
                    'is_excluded' => $is_excluded,
                    'exclusion_reason' => $is_excluded ? self::get_exclusion_reason($table) : null
                ];
            }
            
            // Sort by sync_order
            usort($tables_info, function($a, $b) {
                return $a['sync_order'] - $b['sync_order'];
            });
            
            echo json_encode([
                'status' => 'success',
                'tables' => $tables_info
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Toggle sync for a specific table
     * 
     * POST parameters:
     * - table_name: Name of the table
     * - enabled: 1 or 0
     */
    public function toggle_table_sync() {
        try {
            $table_name = $this->input->post('table_name');
            $enabled = (int)$this->input->post('enabled');
            
            if (empty($table_name)) {
                throw new Exception('Table name is required');
            }
            
            // Check if table is excluded from sync operations
            $this->config->load('sync', TRUE);
            $excluded_tables = $this->config->item('deletion_tracking_excluded_tables', 'sync') ?: [];
            
            if (in_array($table_name, $excluded_tables)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'This table is excluded from sync operations by system configuration.'
                ]);
                return;
            }
            
            // Check if table exists in sync_metadata
            $exists = $this->db->get_where('sync_metadata', ['table_name' => $table_name])->row();
            
            if ($exists) {
                // Update existing entry
                $this->db->where('table_name', $table_name)
                        ->update('sync_metadata', ['sync_enabled' => $enabled]);
            } else {
                // Insert new entry
                $this->db->insert('sync_metadata', [
                    'table_name' => $table_name,
                    'sync_enabled' => $enabled,
                    'sync_order' => 999,
                    'conflict_strategy' => 'REMOTE_WINS',
                    'batch_size' => 100,
                    'description' => ucwords(str_replace('_', ' ', $table_name))
                ]);
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Sync ' . ($enabled ? 'enabled' : 'disabled') . ' for ' . $table_name
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Bulk toggle sync for multiple tables
     * 
     * POST parameters:
     * - tables: Array of table names
     * - enabled: 1 or 0
     */
    public function bulk_toggle_sync() {
        try {
            $tables = $this->input->post('tables');
            $enabled = (int)$this->input->post('enabled');
            
            if (empty($tables) || !is_array($tables)) {
                throw new Exception('Tables array is required');
            }
            
            // Check if any tables are excluded from sync operations
            $this->config->load('sync', TRUE);
            $excluded_tables = $this->config->item('deletion_tracking_excluded_tables', 'sync') ?: [];
            
            // Filter out excluded tables from the operation
            $tables = array_filter($tables, function($table) use ($excluded_tables) {
                return !in_array($table, $excluded_tables);
            });
            
            if (empty($tables)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No valid tables selected. Excluded tables cannot be modified.'
                ]);
                return;
            }
            
            $updated = 0;
            foreach ($tables as $table_name) {
                $exists = $this->db->get_where('sync_metadata', ['table_name' => $table_name])->row();
                
                if ($exists) {
                    $this->db->where('table_name', $table_name)
                            ->update('sync_metadata', ['sync_enabled' => $enabled]);
                    $updated++;
                } else {
                    $this->db->insert('sync_metadata', [
                        'table_name' => $table_name,
                        'sync_enabled' => $enabled,
                        'sync_order' => 999,
                        'conflict_strategy' => 'REMOTE_WINS',
                        'batch_size' => 100,
                        'description' => ucwords(str_replace('_', ' ', $table_name))
                    ]);
                    $updated++;
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Updated ' . $updated . ' tables'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * UI for adding sync columns to tables
     */
    public function add_sync_columns_ui() {
        // Load sync configuration to check for excluded tables
        $this->config->load('sync', TRUE);
        $excluded_tables = $this->config->item('deletion_tracking_excluded_tables', 'sync') ?: [];
        
        // Handle multiple table parameters from query string
        // PHP only keeps the last value when you use ?table=x&table=y&table=z
        // So we need to parse the query string manually
        $tables_from_query = [];
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $query_params);
            
            // Check if there are multiple 'table' parameters in the raw query string
            if (preg_match_all('/table=([^&]+)/', $_SERVER['QUERY_STRING'], $matches)) {
                foreach ($matches[1] as $table_value) {
                    $tables_from_query[] = urldecode($table_value);
                }
            }
        }
        
        // Use $_GET directly as fallback if CI Input class filters it out
        $table = $this->input->get('table');
        if ($table === NULL && isset($_GET['table'])) {
            $table = $this->security->xss_clean($_GET['table']);
        }
        
        $tables_param = $this->input->get('tables');
        if ($tables_param === NULL && isset($_GET['tables'])) {
            $tables_param = $_GET['tables'];
            if (is_array($tables_param)) {
                $tables_param = array_map([$this->security, 'xss_clean'], $tables_param);
            }
        }
        
        // If we found multiple table parameters in query string, use those
        if (!empty($tables_from_query)) {
            $tables_param = array_map([$this->security, 'xss_clean'], $tables_from_query);
            $table = null; // Clear single table since we have multiple
        }
        
        // Check if single table is excluded
        if ($table && in_array($table, $excluded_tables)) {
            $this->session->set_flashdata('error', 'Cannot add sync columns to excluded table: ' . $table);
            redirect('sync_server/table_management');
            return;
        }
        
        // Check if any table in bulk request is excluded
        if ($tables_param && is_array($tables_param)) {
            $excluded_in_request = array_intersect($tables_param, $excluded_tables);
            if (!empty($excluded_in_request)) {
                $this->session->set_flashdata('error', 'Cannot add sync columns to excluded tables: ' . implode(', ', $excluded_in_request));
                redirect('sync_server/table_management');
                return;
            }
        }
        
        // DEBUG: Log what we're receiving
        log_message('debug', 'add_sync_columns_ui called');
        log_message('debug', 'GET table param: ' . var_export($table, true));
        log_message('debug', 'GET tables param: ' . var_export($tables_param, true));
        log_message('debug', 'Tables from query string: ' . var_export($tables_from_query, true));
        log_message('debug', 'All GET params: ' . var_export($_GET, true));
        
        $data['page_name'] = 'add_sync_columns';
        $data['page_title'] = 'Add Sync Columns';
        $data['selected_table'] = $table;
        $data['sync_status'] = $this->get_sync_status();
        
        // Get tables without sync columns
        $all_tables = $this->db->list_tables();
        $tables_without_sync = [];
        
        foreach ($all_tables as $tbl) {
            // Skip sync metadata tables
            if (strpos($tbl, 'sync_') === 0) continue;
            
            $fields = $this->db->list_fields($tbl);
            if (!in_array('sync_status', $fields)) {
                $tables_without_sync[] = $tbl;
            }
        }
        
        log_message('debug', 'Tables without sync before filter: ' . count($tables_without_sync));
        
        // If specific table(s) requested, filter the list
        if ($table) {
            log_message('debug', 'Filtering for single table: ' . $table);
            // Single table specified
            if (in_array($table, $tables_without_sync)) {
                log_message('debug', 'Table found in list, setting to array with 1 item');
                $tables_without_sync = [$table];
            } else {
                log_message('debug', 'Table NOT found in list, setting to empty array');
                // Table already has sync columns or doesn't exist
                $tables_without_sync = [];
            }
        } elseif ($tables_param && is_array($tables_param)) {
            log_message('debug', 'Filtering for multiple tables');
            // Multiple tables specified
            $tables_without_sync = array_intersect($tables_without_sync, $tables_param);
        }
        
        log_message('debug', 'Tables without sync after filter: ' . count($tables_without_sync));
        
        $data['tables_without_sync'] = $tables_without_sync;
        
        $this->load->view('backend/main', $data);
    }
    
    /**
     * Add sync columns to a specific table
     * 
     * POST parameters:
     * - table_name: Name of the table
     */
    public function add_sync_columns_to_table() {
        try {
            $table_name = $this->input->post('table_name');
            
            if (empty($table_name)) {
                throw new Exception('Table name is required');
            }
            
            // Check if table exists
            if (!$this->db->table_exists($table_name)) {
                throw new Exception('Table does not exist');
            }
            
            // Get existing fields
            $fields = $this->db->list_fields($table_name);
            
            // Define all sync columns (8 standard sync columns)
            $sync_columns = [
                'sync_status' => "ADD COLUMN `sync_status` ENUM('PENDING', 'SYNCED', 'FAILED', 'MANUAL_REVIEW') DEFAULT 'PENDING'",
                'sync_operation_type' => "ADD COLUMN `sync_operation_type` ENUM('insert', 'update', 'both') DEFAULT 'insert'",
                'last_modified_at' => "ADD COLUMN `last_modified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
                'last_modified_by' => "ADD COLUMN `last_modified_by` INT DEFAULT NULL",
                'device_id' => "ADD COLUMN `device_id` VARCHAR(50) DEFAULT 'local-server-001'",
                'version' => "ADD COLUMN `version` INT DEFAULT 1",
                'retry_count' => "ADD COLUMN `retry_count` INT DEFAULT 0",
                'sync_error' => "ADD COLUMN `sync_error` TEXT DEFAULT NULL"
            ];
            
            // Define indexes
            $indexes = [
                'idx_sync_status' => "ADD INDEX `idx_sync_status` (`sync_status`)",
                'idx_last_modified' => "ADD INDEX `idx_last_modified` (`last_modified_at`)"
            ];
            
            // Filter out columns that already exist
            $columns_to_add = [];
            foreach ($sync_columns as $column_name => $sql_fragment) {
                if (!in_array($column_name, $fields)) {
                    $columns_to_add[] = $sql_fragment;
                }
            }
            
            // Check if all sync columns already exist
            if (empty($columns_to_add)) {
                // All columns exist, just ensure metadata is set up
                $this->ensure_sync_metadata($table_name);
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Table already has all sync columns. Metadata updated.'
                ]);
                return;
            }
            
            // Check existing indexes
            $existing_indexes = $this->get_table_indexes($table_name);
            $indexes_to_add = [];
            foreach ($indexes as $index_name => $sql_fragment) {
                if (!in_array($index_name, $existing_indexes)) {
                    $indexes_to_add[] = $sql_fragment;
                }
            }
            
            // Combine columns and indexes
            $all_alterations = array_merge($columns_to_add, $indexes_to_add);
            
            if (!empty($all_alterations)) {
                // Build ALTER TABLE statement
                $sql = "ALTER TABLE `$table_name` \n" . implode(",\n", $all_alterations);
                
                $this->db->query($sql);
            }
            
            // Mark existing records as PENDING (only if sync_status column was just added or exists)
            if (in_array('sync_status', $fields) || in_array('sync_status', array_keys($sync_columns))) {
                $this->db->where('sync_status', NULL)
                        ->or_where('sync_status', '')
                        ->update($table_name, ['sync_status' => 'PENDING']);
            }
            
            // Add to sync_metadata if not exists
            $this->ensure_sync_metadata($table_name);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Sync columns added successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Ensure sync metadata exists for a table
     * 
     * @param string $table_name Table name
     */
    private function ensure_sync_metadata($table_name) {
        $exists = $this->db->get_where('sync_metadata', ['table_name' => $table_name])->row();
        
        if (!$exists) {
            $max_order = $this->db->select_max('sync_order')->get('sync_metadata')->row()->sync_order ?? 0;
            
            $this->db->insert('sync_metadata', [
                'table_name' => $table_name,
                'sync_order' => $max_order + 1,
                'sync_enabled' => 1,
                'conflict_strategy' => 'REMOTE_WINS',
                'batch_size' => 100,
                'description' => ucwords(str_replace('_', ' ', $table_name))
            ]);
        } else {
            // Update sync_enabled to 1 if it was 0
            $this->db->where('table_name', $table_name)
                    ->update('sync_metadata', ['sync_enabled' => 1]);
        }
    }
    
    /**
     * Get table indexes
     * 
     * @param string $table_name Table name
     * @return array Index names
     */
    private function get_table_indexes($table_name) {
        $query = $this->db->query("SHOW INDEX FROM `$table_name`");
        $indexes = [];
        foreach ($query->result_array() as $row) {
            $indexes[] = $row['Key_name'];
        }
        return array_unique($indexes);
    }
    
    /**
     * Remove sync columns from a table
     * 
     * POST Parameters:
     * - table_name: Name of the table
     * 
     * @return JSON response
     */
    public function remove_sync_columns_from_table() {
        try {
            $table_name = $this->input->post('table_name');
            
            if (empty($table_name)) {
                throw new Exception('Table name is required');
            }
            
            // Check if table exists
            if (!$this->db->table_exists($table_name)) {
                throw new Exception('Table does not exist');
            }
            
            // Get existing fields
            $fields = $this->db->list_fields($table_name);
            
            // Define all sync columns to remove
            $sync_columns = [
                'sync_status',
                'last_modified_at',
                'last_modified_by',
                'device_id',
                'version',
                'retry_count',
                'sync_error'
            ];
            
            // Define indexes to remove
            $sync_indexes = [
                'idx_sync_status',
                'idx_last_modified'
            ];
            
            // Filter columns that exist
            $columns_to_remove = [];
            foreach ($sync_columns as $column_name) {
                if (in_array($column_name, $fields)) {
                    $columns_to_remove[] = "DROP COLUMN `$column_name`";
                }
            }
            
            // Check if no sync columns exist
            if (empty($columns_to_remove)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Table does not have sync columns'
                ]);
                return;
            }
            
            // Check existing indexes
            $existing_indexes = $this->get_table_indexes($table_name);
            $indexes_to_remove = [];
            foreach ($sync_indexes as $index_name) {
                if (in_array($index_name, $existing_indexes)) {
                    $indexes_to_remove[] = "DROP INDEX `$index_name`";
                }
            }
            
            // Combine indexes and columns (indexes must be dropped first)
            $all_alterations = array_merge($indexes_to_remove, $columns_to_remove);
            
            if (!empty($all_alterations)) {
                // Build ALTER TABLE statement
                $sql = "ALTER TABLE `$table_name` \n" . implode(",\n", $all_alterations);
                
                $this->db->query($sql);
            }
            
            // Remove from sync_metadata or disable sync
            $this->db->where('table_name', $table_name)
                    ->update('sync_metadata', ['sync_enabled' => 0]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Sync columns removed successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Bulk remove sync columns from multiple tables
     * 
     * POST Parameters:
     * - tables: Array of table names
     * 
     * @return JSON response
     */
    public function bulk_remove_sync_columns() {
        try {
            $tables = $this->input->post('tables');
            
            if (empty($tables) || !is_array($tables)) {
                throw new Exception('Tables array is required');
            }
            
            $success_count = 0;
            $errors = [];
            
            foreach ($tables as $table_name) {
                try {
                    // Check if table exists
                    if (!$this->db->table_exists($table_name)) {
                        $errors[] = "$table_name: Table does not exist";
                        continue;
                    }
                    
                    // Get existing fields
                    $fields = $this->db->list_fields($table_name);
                    
                    // Define all sync columns to remove
                    $sync_columns = [
                        'sync_status',
                        'last_modified_at',
                        'last_modified_by',
                        'device_id',
                        'version',
                        'retry_count',
                        'sync_error'
                    ];
                    
                    // Define indexes to remove
                    $sync_indexes = [
                        'idx_sync_status',
                        'idx_last_modified'
                    ];
                    
                    // Filter columns that exist
                    $columns_to_remove = [];
                    foreach ($sync_columns as $column_name) {
                        if (in_array($column_name, $fields)) {
                            $columns_to_remove[] = "DROP COLUMN `$column_name`";
                        }
                    }
                    
                    // Skip if no sync columns exist
                    if (empty($columns_to_remove)) {
                        continue;
                    }
                    
                    // Check existing indexes
                    $existing_indexes = $this->get_table_indexes($table_name);
                    $indexes_to_remove = [];
                    foreach ($sync_indexes as $index_name) {
                        if (in_array($index_name, $existing_indexes)) {
                            $indexes_to_remove[] = "DROP INDEX `$index_name`";
                        }
                    }
                    
                    // Combine indexes and columns (indexes must be dropped first)
                    $all_alterations = array_merge($indexes_to_remove, $columns_to_remove);
                    
                    if (!empty($all_alterations)) {
                        // Build ALTER TABLE statement
                        $sql = "ALTER TABLE `$table_name` \n" . implode(",\n", $all_alterations);
                        
                        $this->db->query($sql);
                    }
                    
                    // Remove from sync_metadata or disable sync
                    $this->db->where('table_name', $table_name)
                            ->update('sync_metadata', ['sync_enabled' => 0]);
                    
                    $success_count++;
                    
                } catch (Exception $e) {
                    $errors[] = "$table_name: " . $e->getMessage();
                }
            }
            
            $message = "Successfully removed sync columns from $success_count table(s)";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'success_count' => $success_count,
                'error_count' => count($errors),
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Mark all records in all tables with sync columns as synced
     * 
     * This method updates all records across all tables that have sync_status column
     * to indicate they are fully synced with no pending changes.
     * 
     * Optimized version with increased timeout and batch processing
     * 
     * @return JSON response
     */
    public function mark_all_as_synced() {
        try {
            // Increase execution time limit to 5 minutes
            set_time_limit(300);
            
            // Increase memory limit
            ini_set('memory_limit', '512M');
            
            // Get all tables with sync_status column
            $tables_query = "
                SELECT DISTINCT TABLE_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND COLUMN_NAME = 'sync_status'
                ORDER BY TABLE_NAME
            ";
            
            $tables_result = $this->db->query($tables_query);
            $tables = $tables_result->result_array();
            
            if (empty($tables)) {
                throw new Exception('No tables with sync columns found');
            }
            
            $success_count = 0;
            $total_records = 0;
            $errors = [];
            
            // Disable foreign key checks for faster updates
            $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Disable autocommit for better performance
            $this->db->query("SET autocommit = 0");
            
            foreach ($tables as $table_row) {
                $table_name = $table_row['TABLE_NAME'];
                
                try {
                    // Use direct query for better performance
                    $this->db->query("UPDATE `$table_name` SET sync_status = 'SYNCED' WHERE 1=1");
                    
                    $affected = $this->db->affected_rows();
                    $total_records += $affected;
                    $success_count++;
                    
                    // Commit every 10 tables to avoid long transactions
                    if ($success_count % 10 == 0) {
                        $this->db->query("COMMIT");
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "$table_name: " . $e->getMessage();
                }
            }
            
            // Final commit
            $this->db->query("COMMIT");
            
            // Re-enable autocommit
            $this->db->query("SET autocommit = 1");
            
            // Re-enable foreign key checks
            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
            
            $message = "Successfully marked $total_records records as synced across $success_count table(s)";
            if (!empty($errors)) {
                $message .= ". Some errors occurred: " . implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= " and " . (count($errors) - 3) . " more";
                }
            }
            
            // Log the operation
            $this->log_message_custom("Mark all as synced: $message", 'info');
            
            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'tables_updated' => $success_count,
                'total_records' => $total_records,
                'error_count' => count($errors),
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            // Re-enable autocommit and foreign key checks in case of error
            $this->db->query("SET autocommit = 1");
            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
            
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    // ============================================================================
    // ENHANCED ERROR HANDLING METHODS (Task 9.1)
    // ============================================================================
    
    /**
     * Handle pull connection error
     * 
     * Requirements: 16
     * Task: 9.1
     * 
     * @param string $table Table name
     * @param string $error_message Error message
     */
    private function handle_pull_connection_error($table, $error_message) {
        // Only log connection errors once per hour per table to avoid flooding logs
        $log_key = "last_pull_error_$table";
        $last_log = $this->get_setting($log_key, '1970-01-01 00:00:00');
        $time_since_last = strtotime('now') - strtotime($last_log);
        
        if ($time_since_last > 3600) { // 1 hour
            $this->log_message_custom("Pull connection error for $table: $error_message", 'info');
            $this->update_setting($log_key, date('Y-m-d H:i:s'));
            
            // Log to audit trail
            $this->load->library('Audit_logger');
            $this->audit_logger->log(
                $table,
                0,
                'PULL_CONNECTION_ERROR',
                'remote-server',
                $this->device_id,
                null,
                null,
                'pull',
                null,
                0,
                'failed',
                $error_message
            );
        }
    }
    
    /**
     * Handle push connection error
     * 
     * Requirements: 16
     * Task: 9.1
     * 
     * @param string $table Table name
     * @param string $error_message Error message
     */
    private function handle_push_connection_error($table, $error_message) {
        // Only log connection errors once per hour per table to avoid flooding logs
        $log_key = "last_push_error_$table";
        $last_log = $this->get_setting($log_key, '1970-01-01 00:00:00');
        $time_since_last = strtotime('now') - strtotime($last_log);
        
        if ($time_since_last > 3600) { // 1 hour
            $this->log_message_custom("Push connection error for $table: $error_message", 'info');
            $this->update_setting($log_key, date('Y-m-d H:i:s'));
            
            // Log to audit trail (only when actually logging)
            $this->load->library('Audit_logger');
            $this->audit_logger->log(
                $table,
                0,
                'PUSH_CONNECTION_ERROR',
                $this->device_id,
                'remote-server',
                null,
                null,
                'push',
                null,
                0,
                'failed',
                $error_message
            );
        }
    }
    
    /**
     * Handle push constraint error
     * 
     * Requirements: 16
     * Task: 9.1
     * 
     * @param string $table Table name
     * @param array $record Record data
     * @param string $error_message Error message
     */
    private function handle_push_constraint_error($table, $record, $error_message) {
        $primary_key = $this->get_primary_key($table);
        $record_id = $record[$primary_key] ?? 0;
        
        $this->log_message_custom("Push constraint error for $table record $record_id: $error_message", 'info');
        
        // Log to audit trail
        $this->load->library('Audit_logger');
        $this->audit_logger->log(
            $table,
            $record_id,
            'PUSH_CONSTRAINT_ERROR',
            $this->device_id,
            'remote-server',
            null,
            $record,
            'push',
            null,
            0,
            'failed',
            $error_message
        );
    }
    
    /**
     * Handle push timeout error
     * 
     * Requirements: 16
     * Task: 9.1
     * 
     * @param string $table Table name
     * @param array $batch Batch of records
     * @param string $error_message Error message
     */
    private function handle_push_timeout_error($table, $batch, $error_message) {
        $this->log_message_custom("Push timeout error for $table: $error_message", 'error');
        
        // Mark entire batch as failed and retry on next sync
        foreach ($batch as $batch_record) {
            $this->mark_failed($table, $batch_record, 'Timeout - will retry');
        }
        
        // Log to audit trail
        $this->load->library('Audit_logger');
        $this->audit_logger->log(
            $table,
            0,
            'PUSH_TIMEOUT_ERROR',
            $this->device_id,
            'remote-server',
            null,
            null,
            'push',
            null,
            0,
            'failed',
            $error_message . ' (batch size: ' . count($batch) . ')'
        );
    }
    
    /**
     * Handle push generic error
     * 
     * Requirements: 16
     * Task: 9.1
     * 
     * @param string $table Table name
     * @param array $record Record data
     * @param string $error_message Error message
     */
    private function handle_push_generic_error($table, $record, $error_message) {
        $primary_key = $this->get_primary_key($table);
        $record_id = $record[$primary_key] ?? 0;
        
        $this->log_message_custom("Push error for $table record $record_id: $error_message", 'error');
        
        // Log to audit trail
        $this->load->library('Audit_logger');
        $this->audit_logger->log(
            $table,
            $record_id,
            'PUSH_ERROR',
            $this->device_id,
            'remote-server',
            null,
            $record,
            'push',
            null,
            0,
            'failed',
            $error_message
        );
    }
    
    // ============================================================================
    // LOCATION OFFLINE DETECTION (Task 9.3)
    // ============================================================================
    
    /**
     * Check for offline locations and send alerts
     * 
     * Requirements: 7, 16
     * Task: 9.3
     * 
     * @return array Locations that triggered alerts
     */
    public function check_offline_locations() {
        $this->load->library('Sync_notifier');
        $alerted = $this->sync_notifier->check_offline_locations();
        
        if (!empty($alerted)) {
            $this->log_message_custom('Offline location alerts sent for: ' . implode(', ', $alerted), 'warning');
        }
        
        return $alerted;
    }
    
    /**
     * Get location offline status
     * 
     * Requirements: 7, 16
     * Task: 9.3
     * 
     * @return array Offline locations with duration
     */
    public function get_offline_locations() {
        // Get locations that have been offline for more than 1 hour
        $offline_locations = $this->db
            ->where('status', 'active')
            ->where('last_sync_at <', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->get('location_registry')
            ->result_array();
        
        $result = [];
        
        foreach ($offline_locations as $location) {
            // Calculate offline duration
            $last_sync = strtotime($location['last_sync_at']);
            $now = time();
            $offline_minutes = round(($now - $last_sync) / 60);
            
            // Determine severity
            $severity = 'warning';
            if ($offline_minutes > 1440) { // 24 hours
                $severity = 'critical';
            } elseif ($offline_minutes > 60) {
                $severity = 'high';
            }
            
            $result[] = [
                'location_id' => $location['id'],
                'location_name' => $location['location_name'],
                'device_id' => $location['device_id'],
                'last_sync_at' => $location['last_sync_at'],
                'offline_minutes' => $offline_minutes,
                'offline_hours' => round($offline_minutes / 60, 1),
                'severity' => $severity
            ];
        }
        
        return $result;
    }
    
    /**
     * Get Performance Chart Data
     * 
     * Returns sync performance metrics for the last 7 days
     * Requirements: 15.1-15.10
     * 
     * @return void (outputs JSON)
     */
    public function get_performance_data() {
        if ($this->session->userdata('admin_login') != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        // Get last 7 days of sync metrics
        $data = [
            'push' => [0, 0, 0, 0, 0, 0, 0],
            'pull' => [0, 0, 0, 0, 0, 0, 0],
            'conflicts' => [0, 0, 0, 0, 0, 0, 0]
        ];
        
        // Check if sync_audit_log table exists
        if ($this->db->table_exists('sync_audit_log')) {
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                
                // Get total push records count (COUNT of records synced per date)
                $push_count = $this->db->where('DATE(synced_at)', $date)
                                      ->where('sync_direction', 'push')
                                      ->where('status', 'success')
                                      ->count_all_results('sync_audit_log');
                $data['push'][6 - $i] = $push_count;
                
                // Get total pull records count (COUNT of records synced per date)
                $pull_count = $this->db->where('DATE(synced_at)', $date)
                                      ->where('sync_direction', 'pull')
                                      ->where('status', 'success')
                                      ->count_all_results('sync_audit_log');
                $data['pull'][6 - $i] = $pull_count;
                
                // Get conflicts count
                $conflicts_count = $this->db->where('DATE(synced_at)', $date)
                                           ->where('status', 'conflict')
                                           ->count_all_results('sync_audit_log');
                $data['conflicts'][6 - $i] = $conflicts_count;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * Get Status Chart Data
     * 
     * Returns sync status distribution (success, failed, conflicts)
     * Requirements: 15.1-15.10
     * 
     * @return void (outputs JSON)
     */
    /**
     * Get Status Data for Dashboard Metrics
     * 
     * Task 6.4: Updated to distinguish between genuine failures and false failures
     * 
     * Returns sync metrics including:
     * - Success count
     * - Genuine failure count (excludes false failures)
     * - Corrected false failures count
     * - Conflicts count
     * 
     * Requirements: 2.5
     * 
     * @return void Outputs JSON response
     */
    public function get_status_data() {
        if ($this->session->userdata('admin_login') != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $data = [
            'success' => 0,
            'failed' => 0,
            'genuine_failures' => 0,
            'corrected_false_failures' => 0,
            'conflicts' => 0
        ];
        
        // Check if sync_audit_log table exists
        if ($this->db->table_exists('sync_audit_log')) {
            // Get counts for last 30 days
            $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
            
            // Success count
            $data['success'] = $this->db->where('synced_at >=', $thirty_days_ago)
                                       ->where('status', 'success')
                                       ->count_all_results('sync_audit_log');
            
            // Failed count (all failures including false failures - for backwards compatibility)
            $data['failed'] = $this->db->where('synced_at >=', $thirty_days_ago)
                                      ->where('status', 'failed')
                                      ->count_all_results('sync_audit_log');
            
            // Conflicts count
            $data['conflicts'] = $this->db->where('synced_at >=', $thirty_days_ago)
                                         ->where('status', 'conflict')
                                         ->count_all_results('sync_audit_log');
        }
        
        // Task 6.4: Get genuine failures and corrected false failures from sync_failures table
        if ($this->db->table_exists('sync_failures')) {
            // Get genuine failures (excluding records verified to exist on remote)
            // Count failures where verification_status is NOT 'verified_exists'
            $genuine_failures_query = $this->db->select('COUNT(*) as count')
                ->from('sync_failures')
                ->where('resolved', 0)
                ->group_start()
                    ->where('verification_status', 'verified_not_exists')
                    ->or_where('verification_status', 'not_verified')
                    ->or_where('verification_status IS NULL', null, false)
                ->group_end()
                ->get();
            
            if ($genuine_failures_query->num_rows() > 0) {
                $data['genuine_failures'] = $genuine_failures_query->row()->count;
            }
            
            // Task 6.4: Get corrected false failures count
            // Count records where verification confirmed they exist on remote
            $false_failures_query = $this->db->select('COUNT(*) as count')
                ->from('sync_failures')
                ->where('verification_status', 'verified_exists')
                ->get();
            
            if ($false_failures_query->num_rows() > 0) {
                $data['corrected_false_failures'] = $false_failures_query->row()->count;
            }
        }
        
        // If sync_failures table doesn't exist, fall back to sync_audit_log data
        if ($data['genuine_failures'] == 0 && $data['corrected_false_failures'] == 0) {
            $data['genuine_failures'] = $data['failed'];
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    }
    
    // ========================================================================
    // DELETION TRACKING METHODS
    // ========================================================================
    
    /**
     * Decode record identifier JSON to WHERE conditions
     * 
     * Decodes the JSON-encoded record_id from sync_deletions table
     * and returns an associative array suitable for WHERE conditions.
     * 
     * Requirements: 3.6, 4.4, 5.4
     * Task: 5.1
     * 
     * @param string $record_id_json JSON-encoded record identifier
     * @return array Associative array of column => value pairs
     */
    private function decode_record_identifier($record_id_json) {
        try {
            // Defensive check: ensure input is a string
            if (!is_string($record_id_json)) {
                throw new Exception('Record identifier must be a string, received: ' . gettype($record_id_json));
            }
            
            // Log the raw input for debugging
            $this->log_message_custom('Decoding record identifier: ' . substr($record_id_json, 0, 200), 'debug');
            
            $conditions = json_decode($record_id_json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON decode error: ' . json_last_error_msg() . ' - Input: ' . substr($record_id_json, 0, 100));
            }
            
            if (!is_array($conditions) || empty($conditions)) {
                throw new Exception('Invalid record identifier format - decoded to: ' . var_export($conditions, true));
            }
            
            return $conditions;
            
        } catch (Exception $e) {
            $this->log_message_custom('Failed to decode record identifier: ' . $e->getMessage(), 'error');
            // Return null instead of empty array to distinguish from success case
            return null;
        }
    }
    
    /**
     * Validate table name exists in database
     * 
     * Queries information_schema to verify table exists.
     * Prevents SQL injection by validating table names.
     * 
     * Requirements: 16.3
     * Task: 5.2
     * 
     * @param string $table_name Table name to validate
     * @return bool True if table exists
     */
    private function validate_table_name($table_name) {
        try {
            $query = $this->db->query(
                "SELECT 1 FROM information_schema.TABLES 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = ?
                 LIMIT 1",
                [$table_name]
            );
            
            return $query && $query->num_rows() > 0;
            
        } catch (Exception $e) {
            $this->log_message_custom('Table validation failed for "' . $table_name . '": ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Validate column names exist in table
     * 
     * Queries information_schema to verify all columns exist in the table.
     * Prevents SQL injection by validating column names.
     * 
     * Requirements: 16.2
     * Task: 5.3
     * 
     * @param string $table_name Table name
     * @param array $columns Array of column names to validate
     * @return bool True if all columns exist
     * @throws Exception If invalid column found
     */
    private function validate_column_names($table_name, $columns) {
        try {
            // Get valid columns for this table
            $query = $this->db->query(
                "SELECT COLUMN_NAME 
                 FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = ?",
                [$table_name]
            );
            
            if (!$query || $query->num_rows() === 0) {
                throw new Exception("No columns found for table: $table_name");
            }
            
            $valid_columns = array_column($query->result_array(), 'COLUMN_NAME');
            
            // Check each column
            foreach ($columns as $column) {
                if (!in_array($column, $valid_columns)) {
                    throw new Exception("Invalid column name: $column");
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->log_message_custom('Column validation failed: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Sync local deletions to remote location (Push Phase)
     * 
     * Queries local sync_deletions for PENDING records and syncs them to remote.
     * Groups deletions by table for batch processing.
     * Implements retry mechanism with exponential backoff.
     * 
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 11.4, 11.5, 
     *               15.1, 15.2, 15.3, 15.4, 15.5, 16.1, 16.3, 16.4
     * Task: 5.4
     * 
     * @return array Summary of sync results
     */
    private function sync_deletions() {
        $this->log_message_custom('=== DELETION SYNC (Push Phase) ===');
        
        $summary = [
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        try {
            // Get remote database connection
            try {
                $remote_db = $this->get_remote_db();
            } catch (Exception $e) {
                // Catch all exception types (DatabaseConnectionException, AuthenticationException, NetworkException, NoInternetException)
                $error_msg = 'Failed to connect to remote database: ' . $e->getMessage();
                $this->log_message_custom($error_msg, 'error');
                
                // Log exception details if available
                if (method_exists($e, 'getDiagnostics')) {
                    $diagnostics = $e->getDiagnostics();
                    $this->log_message_custom('Connection error diagnostics: ' . json_encode($diagnostics), 'error');
                }
                
                throw new Exception($error_msg);
            }
            
            // Load config
            $max_retries = $this->config->item('deletion_max_retries', 'sync') ?? 3;
            $batch_size = $this->config->item('deletion_batch_size', 'sync') ?? 50; // Reduced from 100
            
            // Query local PENDING deletions
            $pending_deletions = $this->db->select('*')
                                         ->from('sync_deletions')
                                         ->where('sync_status', 'PENDING')
                                         ->where('retry_count <', $max_retries)
                                         ->order_by('deleted_at', 'ASC')
                                         ->limit($batch_size)
                                         ->get()
                                         ->result_array();
            
            if (empty($pending_deletions)) {
                $this->log_message_custom('No pending deletions to sync');
                return $summary;
            }
            
            $this->log_message_custom('Found ' . count($pending_deletions) . ' pending deletions');
            
            // Group by table for batch processing
            $deletions_by_table = [];
            foreach ($pending_deletions as $deletion) {
                $table = $deletion['table_name'];
                if (!isset($deletions_by_table[$table])) {
                    $deletions_by_table[$table] = [];
                }
                $deletions_by_table[$table][] = $deletion;
            }
            
            // PERFORMANCE OPTIMIZATION: Process each table with transaction batching
            foreach ($deletions_by_table as $table => $deletions) {
                $this->log_message_custom("Processing " . count($deletions) . " deletions for table: $table");
                
                // Validate table name
                if (!$this->validate_table_name($table)) {
                    $this->log_message_custom("Invalid table name: $table", 'error');
                    $summary['skipped'] += count($deletions);
                    continue;
                }
                
                // Start transaction for this table's deletions
                $remote_db->trans_start();
                
                $table_synced = 0;
                $table_failed = 0;
                
                // Process each deletion
                foreach ($deletions as $deletion) {
                    try {
                        // Decode record identifier
                        $conditions = $this->decode_record_identifier($deletion['record_id']);
                        
                        if ($conditions === null || empty($conditions)) {
                            throw new Exception('Failed to decode record identifier for deletion ID: ' . $deletion['id'] . 
                                              ', record_id value: ' . substr($deletion['record_id'], 0, 100));
                        }
                        
                        // Validate column names
                        $this->validate_column_names($table, array_keys($conditions));
                        
                        // Execute DELETE on remote database
                        $remote_db->where($conditions)->delete($table);
                        
                        // Update sync_status to SYNCED (defer until transaction commits)
                        $this->db->where('id', $deletion['id'])
                                ->update('sync_deletions', [
                                    'sync_status' => 'SYNCED',
                                    'retry_count' => 0,
                                    'last_modified_at' => date('Y-m-d H:i:s'),
                                    'error_message' => NULL
                                ]);
                        
                        $table_synced++;
                        
                    } catch (Exception $e) {
                        // Increment retry count
                        $retry_count = $deletion['retry_count'] + 1;
                        $status = ($retry_count >= $max_retries) ? 'FAILED_PERMANENT' : 'FAILED';
                        
                        $this->db->where('id', $deletion['id'])
                                ->update('sync_deletions', [
                                    'sync_status' => $status,
                                    'retry_count' => $retry_count,
                                    'last_modified_at' => date('Y-m-d H:i:s'),
                                    'error_message' => $e->getMessage()
                                ]);
                        
                        $table_failed++;
                        $summary['errors'][] = "Table $table: " . $e->getMessage();
                        
                        $this->log_message_custom("Failed to sync deletion for table $table: " . $e->getMessage(), 'error');
                    }
                }
                
                // Commit transaction for this table
                $remote_db->trans_complete();
                
                if ($remote_db->trans_status() === FALSE) {
                    // Transaction failed - rollback already done automatically
                    $this->log_message_custom("Deletion batch for $table failed - transaction rolled back", 'error');
                    $summary['failed'] += $table_failed;
                } else {
                    // Transaction succeeded
                    $this->log_message_custom("Deletion batch for $table completed: $table_synced synced, $table_failed failed");
                    $summary['synced'] += $table_synced;
                    $summary['failed'] += $table_failed;
                }
            }
            
            $this->log_message_custom("Deletion sync completed - Synced: {$summary['synced']}, Failed: {$summary['failed']}, Skipped: {$summary['skipped']}");
            
        } catch (Exception $e) {
            $this->log_message_custom('Deletion sync failed: ' . $e->getMessage(), 'error');
            $summary['errors'][] = $e->getMessage();
        }
        
        return $summary;
    }
    
    /**
     * Apply remote deletions to local database (Pull Phase)
     * 
     * Queries remote sync_deletions for new deletions and applies them locally.
     * Implements conflict detection and resolution.
     * Treats "record not found" as success (idempotent).
     * 
     * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 
     *               6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 16.1, 16.3
     * Task: 5.5
     * 
     * @return array Summary of apply results
     */
    private function apply_remote_deletions() {
        $this->log_message_custom('=== DELETION SYNC (Pull Phase) ===');
        
        $summary = [
            'applied' => 0,
            'conflicts' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        try {
            // Get remote database connection
            try {
                $remote_db = $this->get_remote_db();
            } catch (Exception $e) {
                // Catch all exception types (DatabaseConnectionException, AuthenticationException, NetworkException, NoInternetException)
                $error_msg = 'Failed to connect to remote database: ' . $e->getMessage();
                $this->log_message_custom($error_msg, 'error');
                
                // Log exception details if available
                if (method_exists($e, 'getDiagnostics')) {
                    $diagnostics = $e->getDiagnostics();
                    $this->log_message_custom('Connection error diagnostics: ' . json_encode($diagnostics), 'error');
                }
                
                throw new Exception($error_msg);
            }
            
            // Get last pull timestamp
            $last_pull = $this->get_setting('last_deletion_pull_sync', '1970-01-01 00:00:00');
            
            // Load config
            $batch_size = $this->config->item('deletion_batch_size', 'sync') ?? 100;
            
            // Query remote deletions
            $remote_deletions = $remote_db->select('*')
                                         ->from('sync_deletions')
                                         ->where('device_id !=', $this->device_id)
                                         ->where('deleted_at >', $last_pull)
                                         ->order_by('deleted_at', 'ASC')
                                         ->limit($batch_size)
                                         ->get()
                                         ->result_array();
            
            if (empty($remote_deletions)) {
                $this->log_message_custom('No remote deletions to apply');
                return $summary;
            }
            
            $this->log_message_custom('Found ' . count($remote_deletions) . ' remote deletions');
            
            // Process each deletion
            foreach ($remote_deletions as $deletion) {
                $table = $deletion['table_name'];
                
                try {
                    // Validate table name
                    if (!$this->validate_table_name($table)) {
                        $this->log_message_custom("Invalid table name: $table", 'error');
                        $summary['skipped']++;
                        continue;
                    }
                    
                    // Decode record identifier
                    $conditions = $this->decode_record_identifier($deletion['record_id']);
                    
                    if ($conditions === null || empty($conditions)) {
                        throw new Exception('Failed to decode record identifier for remote deletion, table: ' . $table . 
                                          ', record_id value: ' . substr($deletion['record_id'], 0, 100));
                    }
                    
                    // Validate column names
                    $this->validate_column_names($table, array_keys($conditions));
                    
                    // Check for conflict (local record has PENDING status)
                    $local_record = $this->db->select('sync_status')
                                            ->where($conditions)
                                            ->get($table)
                                            ->row();
                    
                    if ($local_record && $local_record->sync_status === 'PENDING') {
                        // Conflict detected
                        $this->log_message_custom("Conflict detected for table $table", 'warning');
                        
                        // Log conflict
                        $this->conflict_resolver->log_conflict([
                            'table_name' => $table,
                            'record_id' => $deletion['record_id'],
                            'conflict_type' => 'DELETE_VS_UPDATE',
                            'local_status' => 'PENDING',
                            'remote_status' => 'DELETED'
                        ]);
                        
                        // Apply resolution strategy
                        $strategy = $this->config->item('default_conflict_strategy', 'sync') ?? 'REMOTE_WINS';
                        
                        if ($strategy === 'REMOTE_WINS') {
                            // Delete local record
                            $this->db->where($conditions)->delete($table);
                            $summary['applied']++;
                            $this->log_message_custom("Conflict resolved: REMOTE_WINS - deleted local record");
                        } else {
                            // Skip deletion
                            $summary['conflicts']++;
                            $this->log_message_custom("Conflict resolved: LOCAL_WINS - kept local record");
                            continue;
                        }
                        
                        // Log to audit trail if method exists
                        if (is_object($this->audit_logger) && method_exists($this->audit_logger, 'log')) {
                            try {
                                $this->audit_logger->log(
                                    $table,
                                    $deletion['record_id'],
                                    'CONFLICT',
                                    $deletion['device_id'],
                                    $this->device_id,
                                    null,
                                    null,
                                    'pull',
                                    $this->session->userdata('user_id'),
                                    0,
                                    'conflict',
                                    "Deletion conflict resolved: $strategy"
                                );
                            } catch (Exception $log_ex) {
                                // Audit logging is non-critical, continue
                                $this->log_message_custom('Audit log failed: ' . $log_ex->getMessage(), 'debug');
                            }
                        }
                        
                    } else {
                        // No conflict - apply deletion
                        $affected = $this->db->where($conditions)->delete($table);
                        
                        if ($affected > 0) {
                            $summary['applied']++;
                            $this->log_message_custom("Applied deletion for table $table");
                        } else {
                            // Record not found - idempotent (already deleted)
                            $summary['applied']++;
                            $this->log_message_custom("Record already deleted for table $table (idempotent)");
                        }
                        
                        // Log to audit trail if method exists
                        if (is_object($this->audit_logger) && method_exists($this->audit_logger, 'log')) {
                            try {
                                $this->audit_logger->log(
                                    $table,
                                    $deletion['record_id'],
                                    'DELETE',
                                    $deletion['device_id'],
                                    $this->device_id,
                                    null,
                                    null,
                                    'pull',
                                    $this->session->userdata('user_id'),
                                    0,
                                    'success',
                                    null
                                );
                            } catch (Exception $log_ex) {
                                // Audit logging is non-critical, continue
                                $this->log_message_custom('Audit log failed: ' . $log_ex->getMessage(), 'debug');
                            }
                        }
                    }
                    
                } catch (Exception $e) {
                    $summary['skipped']++;
                    $summary['errors'][] = "Table $table: " . $e->getMessage();
                    $this->log_message_custom("Failed to apply deletion for table $table: " . $e->getMessage(), 'error');
                }
            }
            
            // Update last pull timestamp
            $this->update_setting('last_deletion_pull_sync', date('Y-m-d H:i:s'));
            
            $this->log_message_custom("Deletion apply completed - Applied: {$summary['applied']}, Conflicts: {$summary['conflicts']}, Skipped: {$summary['skipped']}");
            
        } catch (Exception $e) {
            $this->log_message_custom('Deletion apply failed: ' . $e->getMessage(), 'error');
            $summary['errors'][] = $e->getMessage();
        }
        
        return $summary;
    }
    
    /**
     * Cleanup old synced deletion records
     * 
     * Deletes SYNCED records older than retention period.
     * Preserves PENDING and FAILED records regardless of age.
     * 
     * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6
     * Task: 5.6
     * 
     * @param int $retention_days Retention period in days (default: 30)
     * @return int Count of cleaned up records
     */
    public function cleanup_synced_deletions($retention_days = null) {
        $this->log_message_custom('=== DELETION CLEANUP ===');
        
        try {
            // Get retention period from config if not provided
            if ($retention_days === null) {
                $retention_days = $this->config->item('deletion_retention_days', 'sync') ?? 30;
            }
            
            // Calculate cutoff date
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));
            
            $this->log_message_custom("Cleaning up SYNCED deletions older than $retention_days days (before $cutoff_date)");
            
            // Delete old SYNCED records
            $this->db->where('sync_status', 'SYNCED')
                    ->where('deleted_at <', $cutoff_date)
                    ->delete('sync_deletions');
            
            $count = $this->db->affected_rows();
            
            $this->log_message_custom("Cleaned up $count old deletion records");
            
            return $count;
            
        } catch (Exception $e) {
            $this->log_message_custom('Deletion cleanup failed: ' . $e->getMessage(), 'error');
            return 0;
        }
    }
    
    /**
     * AJAX endpoint: Get sync error log
     * 
     * Returns recent sync errors with filtering and pagination support.
     * Queries database settings for last_sync_error and audit logs.
     * 
     * Requirements: 2.9
     * Task: 3.14
     * 
     * @return void (outputs JSON)
     */
    public function get_error_log() {
        // Check authentication
        $this->check_auth();
        
        // Get filter parameters
        $error_type = $this->input->get('error_type') ?: 'all';
        $limit = (int)($this->input->get('limit') ?: 50);
        $offset = (int)($this->input->get('offset') ?: 0);
        
        try {
            $errors = [];
            
            // Get last sync error from settings
            $last_error_setting = $this->db->get_where('settings', ['type' => 'last_sync_error'])->row();
            if ($last_error_setting && !empty($last_error_setting->description)) {
                $error_data = json_decode($last_error_setting->description, true);
                if ($error_data) {
                    $errors[] = [
                        'timestamp' => $error_data['timestamp'] ?? date('Y-m-d H:i:s'),
                        'error_type' => $error_data['error_type'] ?? 'unknown',
                        'error_message' => $error_data['error_message'] ?? 'Unknown error',
                        'affected_operations' => $error_data['affected_operations'] ?? [],
                        'source' => 'last_sync_error'
                    ];
                }
            }
            
            // Get errors from audit log (failed operations)
            $audit_query = $this->db->select('table_name, record_id, operation, source_device, target_device, sync_direction, timestamp, duration_ms, status, error_message')
                                   ->from('sync_audit_log')
                                   ->where('status', 'failed')
                                   ->or_where('status', 'conflict');
            
            // Apply error type filter
            if ($error_type !== 'all') {
                $audit_query->like('error_message', $error_type);
            }
            
            $audit_query->order_by('timestamp', 'DESC')
                       ->limit($limit, $offset);
            
            $audit_errors = $audit_query->get()->result_array();
            
            foreach ($audit_errors as $audit_error) {
                $errors[] = [
                    'timestamp' => $audit_error['timestamp'],
                    'error_type' => $this->extract_error_type($audit_error['error_message']),
                    'error_message' => $audit_error['error_message'] ?: 'No error message',
                    'affected_operations' => [
                        'table' => $audit_error['table_name'],
                        'record_id' => $audit_error['record_id'],
                        'operation' => $audit_error['operation'],
                        'direction' => $audit_error['sync_direction']
                    ],
                    'source' => 'audit_log'
                ];
            }
            
            // Get total count for pagination
            $total_query = $this->db->from('sync_audit_log')
                                   ->where('status', 'failed')
                                   ->or_where('status', 'conflict');
            
            if ($error_type !== 'all') {
                $total_query->like('error_message', $error_type);
            }
            
            $total_count = $total_query->count_all_results();
            
            // Sort by timestamp descending
            usort($errors, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
            // Limit to requested amount
            $errors = array_slice($errors, 0, $limit);
            
            // Return JSON response
            $response = [
                'status' => 'success',
                'total' => $total_count + 1, // +1 for last_sync_error
                'count' => count($errors),
                'offset' => $offset,
                'limit' => $limit,
                'errors' => $errors
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to retrieve error log: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * AJAX endpoint: Get failed records
     * 
     * Returns individual record failures with filtering and grouping.
     * Queries sync_failures table for unresolved failures.
     * 
     * Requirements: 2.10
     * Task: 3.14
     * 
     * @return void (outputs JSON)
     */
    public function get_failed_records() {
        // Check authentication
        $this->check_auth();
        
        // Get filter parameters
        $table_name = $this->input->get('table_name') ?: 'all';
        $error_type = $this->input->get('error_type') ?: 'all';
        $limit = (int)($this->input->get('limit') ?: 100);
        $offset = (int)($this->input->get('offset') ?: 0);
        
        try {
            // Check if sync_failures table exists
            if (!$this->db->table_exists('sync_failures')) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'sync_failures table does not exist. Run database migration first.'
                ]);
                return;
            }
            
            // Build query for unresolved failures
            $query = $this->db->select('*')
                             ->from('sync_failures')
                             ->where('resolved', 0);
            
            // Apply table name filter
            if ($table_name !== 'all') {
                $query->where('table_name', $table_name);
            }
            
            // Apply error type filter
            if ($error_type !== 'all') {
                $query->where('error_type', $error_type);
            }
            
            $query->order_by('timestamp', 'DESC')
                  ->limit($limit, $offset);
            
            $records = $query->get()->result_array();
            
            // Get total count
            $count_query = $this->db->from('sync_failures')
                                   ->where('resolved', 0);
            
            if ($table_name !== 'all') {
                $count_query->where('table_name', $table_name);
            }
            
            if ($error_type !== 'all') {
                $count_query->where('error_type', $error_type);
            }
            
            $total_count = $count_query->count_all_results();
            
            // Group by error type for summary
            $summary_query = $this->db->select('error_type, COUNT(*) as count')
                                     ->from('sync_failures')
                                     ->where('resolved', 0)
                                     ->group_by('error_type')
                                     ->get();
            
            $by_type = [];
            foreach ($summary_query->result_array() as $row) {
                $by_type[$row['error_type']] = (int)$row['count'];
            }
            
            // Decode error_details JSON field
            foreach ($records as &$record) {
                if (!empty($record['error_details'])) {
                    $record['error_details'] = json_decode($record['error_details'], true);
                }
            }
            
            // Return JSON response
            $response = [
                'status' => 'success',
                'total' => $total_count,
                'count' => count($records),
                'offset' => $offset,
                'limit' => $limit,
                'by_type' => $by_type,
                'records' => $records
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to retrieve failed records: ' . $e->getMessage()
            ]);
        }
    }
    
    
    /**
     * AJAX endpoint: Retry failed records
     * 
     * Re-attempts sync for specific failed records by ID.
     * Updates resolved flag on success, increments retry count on failure.
     * 
     * Requirements: 2.10
     * Task: 3.14
     * 
     * @return void (outputs JSON)
     */
    public function retry_failed_records() {
        // Check authentication
        $this->check_auth();
        
        // Get record IDs from POST data
        $record_ids = $this->input->post('record_ids');
        
        if (empty($record_ids) || !is_array($record_ids)) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'No record IDs provided. Send record_ids as array in POST data.'
            ]);
            return;
        }
        
        try {
            // Check if sync_failures table exists
            if (!$this->db->table_exists('sync_failures')) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'sync_failures table does not exist. Run database migration first.'
                ]);
                return;
            }
            
            // Get failed records by IDs
            $failed_records = $this->db->where_in('id', $record_ids)
                                      ->get('sync_failures')
                                      ->result_array();
            
            if (empty($failed_records)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No failed records found with the provided IDs.'
                ]);
                return;
            }
            
            $attempted = 0;
            $success = 0;
            $still_failed = 0;
            $errors = [];
            
            // Get remote database connection
            try {
                $remote_db = $this->get_remote_db();
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to connect to remote database: ' . $e->getMessage()
                ]);
                return;
            }
            
            // Retry each failed record
            foreach ($failed_records as $failed_record) {
                $attempted++;
                
                $table_name = $failed_record['table_name'];
                $record_id = $failed_record['record_id'];
                $operation = $failed_record['operation'];
                
                try {
                    // Get the actual record from the table
                    $primary_key = $this->get_primary_key($table_name);
                    $record = $this->db->where($primary_key, $record_id)
                                      ->get($table_name)
                                      ->row_array();
                    
                    if (!$record) {
                        $errors[] = "Record $record_id not found in table $table_name";
                        $still_failed++;
                        continue;
                    }
                    
                    // Retry the operation
                    if ($operation === 'push' || $operation === 'insert' || $operation === 'update') {
                        // Prepare record for remote
                        $remote_record = $record;
                        $remote_record['sync_status'] = 'SYNCED';
                        $remote_record['last_modified_at'] = date('Y-m-d H:i:s');
                        
                        // Filter record for remote table
                        $remote_record = $this->filter_record_for_remote($remote_db, $table_name, $remote_record);
                        
                        // Use INSERT ... ON DUPLICATE KEY UPDATE
                        $this->insert_or_update_remote($remote_db, $table_name, $remote_record);
                        
                        // Mark local record as synced
                        $this->mark_synced($table_name, $record);
                        
                        // Mark failure as resolved
                        $this->db->where('id', $failed_record['id'])
                                ->update('sync_failures', [
                                    'resolved' => 1,
                                    'retry_count' => $failed_record['retry_count'] + 1
                                ]);
                        
                        $success++;
                        
                    } else {
                        $errors[] = "Unsupported operation type: $operation";
                        $still_failed++;
                    }
                    
                } catch (Exception $e) {
                    $error_msg = $e->getMessage();
                    $errors[] = "Failed to retry record $record_id in $table_name: $error_msg";
                    $still_failed++;
                    
                    // Increment retry count
                    $this->db->where('id', $failed_record['id'])
                            ->update('sync_failures', [
                                'retry_count' => $failed_record['retry_count'] + 1,
                                'error_message' => $error_msg
                            ]);
                }
            }
            
            // Return results
            $response = [
                'status' => 'success',
                'attempted' => $attempted,
                'success' => $success,
                'still_failed' => $still_failed,
                'errors' => $errors
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to retry records: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * AJAX endpoint: Retry single failed record
     * 
     * Re-attempts sync for a specific failed record.
     * This endpoint wraps the retry_failed_record() method to provide JSON output.
     * 
     * Requirements: 2.4
     * Task: 6.2
     * 
     * @return void (outputs JSON)
     */
    public function retry_single_failed_record() {
        // Check authentication
        $this->check_auth();
        
        // Get parameters from POST data
        $table = $this->input->post('table');
        $record_id = $this->input->post('record_id');
        
        // Validate inputs
        if (empty($table) || empty($record_id)) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required parameters: table and record_id'
            ]);
            return;
        }
        
        // Validate table name (security check - prevent SQL injection)
        if (!$this->db->table_exists($table)) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid table name'
            ]);
            return;
        }
        
        try {
            // Call the retry method
            $result = $this->retry_failed_record($table, $record_id);
            
            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode($result);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Exception during retry: ' . $e->getMessage(),
                'table' => $table,
                'record_id' => $record_id
            ]);
        }
    }
    
    /**
     * Helper: Extract error type from error message
     * 
     * Analyzes error message to determine error category.
     * 
     * @param string $error_message Error message
     * @return string Error type (connection, foreign_key, duplicate, constraint, timeout, other)
     */
    private function extract_error_type($error_message) {
        if (empty($error_message)) {
            return 'unknown';
        }
        
        $error_lower = strtolower($error_message);
        
        // Check for connection errors
        if (strpos($error_lower, 'connection') !== false || 
            strpos($error_lower, 'connect') !== false ||
            strpos($error_lower, 'network') !== false) {
            return 'connection';
        }
        
        // Check for foreign key errors
        if (strpos($error_lower, 'foreign key') !== false ||
            strpos($error_lower, 'fk_') !== false) {
            return 'foreign_key';
        }
        
        // Check for duplicate errors
        if (strpos($error_lower, 'duplicate') !== false ||
            strpos($error_lower, 'unique') !== false) {
            return 'duplicate';
        }
        
        // Check for constraint errors
        if (strpos($error_lower, 'constraint') !== false) {
            return 'constraint';
        }
        
        // Check for timeout errors
        if (strpos($error_lower, 'timeout') !== false ||
            strpos($error_lower, 'timed out') !== false) {
            return 'timeout';
        }
        
        // Check for authentication errors
        if (strpos($error_lower, 'access denied') !== false ||
            strpos($error_lower, 'authentication') !== false) {
            return 'authentication';
        }
        
        // Check for no internet
        if (strpos($error_lower, 'internet') !== false ||
            strpos($error_lower, 'offline') !== false) {
            return 'no_internet';
        }
        
        return 'other';
    }
}


