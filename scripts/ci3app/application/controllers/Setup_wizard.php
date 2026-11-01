<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Setup Wizard Controller
 * 
 * Guides administrators through the initial setup of the Local WAMP + Cloud Sync system.
 * Multi-step wizard for configuring network, remote server, and sync settings.
 */
class Setup_wizard extends CI_Controller {
    
    private $steps = [
        1 => 'welcome',
        2 => 'network',
        3 => 'remote_credentials',
        4 => 'install_sync_columns',
        5 => 'test_sync',
        6 => 'schedule',
        7 => 'complete'
    ];
    
    public function __construct() {
        parent::__construct();
        
        // Load dependencies
        $this->load->library('session');
        $this->load->database();
        
        // Check if user is admin
        if ($this->session->userdata('admin_login') != 1) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Check admin level (only level 1 can access setup wizard)
        $admin_id = $this->session->userdata('admin_id');
        $admin = $this->db->get_where('admin', ['admin_id' => $admin_id])->row();
        if ($admin->level != 1) {
            show_error('Access denied. Only super administrators can access the setup wizard.', 403);
        }
    }
    
    /**
     * Main entry point - redirects to current step
     */
    public function index() {
        // Check if reset is requested
        if ($this->input->get('reset') == '1') {
            $this->reset_wizard();
            redirect(site_url('setup_wizard/step/1'));
            return;
        }
        
        $current_step = $this->get_current_step();
        $this->step($current_step);
    }
    
    /**
     * Display specific step
     */
    public function step($step_number = 1) {
        $step_number = (int)$step_number;
        
        // Validate step number
        if (!isset($this->steps[$step_number])) {
            show_404();
        }
        
        $step_method = 'step_' . $this->steps[$step_number];
        // Call the step method
        if (method_exists($this, $step_method)) {
            
            $this->$step_method();
        } else {
            show_404();
        }
    }
    
    /**
     * Get current step from session or settings
     */
    private function get_current_step() {
        $setup_step = $this->db->get_where('settings', ['type' => 'setup_wizard_step'])->row();
        return $setup_step ? (int)$setup_step->description : 1;
    }
    
    /**
     * Save current step to settings
     */
    private function save_current_step($step) {
        $this->db->where('type', 'setup_wizard_step');
        $exists = $this->db->get('settings')->num_rows() > 0;
        
        if ($exists) {
            $this->db->where('type', 'setup_wizard_step');
            $this->db->update('settings', ['description' => $step]);
        } else {
            $this->db->insert('settings', [
                'type' => 'setup_wizard_step',
                'description' => $step
            ]);
        }
    }
    
    /**
     * Step 1: Welcome and system check
     */
    public function step_welcome() {
        $data = [];
        $data['page_name'] = 'setup_wizard';
        $data['page_title'] = 'Setup Wizard - Welcome';
        $data['account_type'] = $this->session->userdata('login_type');
        $data['current_step'] = 1;
        $data['total_steps'] = count($this->steps);
        
        // Perform system checks
        $data['checks'] = [
            'php_version' => [
                'name' => 'PHP Version (>= 7.4)',
                'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'value' => PHP_VERSION
            ],
            'mysqli' => [
                'name' => 'MySQLi Extension',
                'status' => extension_loaded('mysqli'),
                'value' => extension_loaded('mysqli') ? 'Installed' : 'Not installed'
            ],
            'curl' => [
                'name' => 'cURL Extension',
                'status' => extension_loaded('curl'),
                'value' => extension_loaded('curl') ? 'Installed' : 'Not installed'
            ],
            'json' => [
                'name' => 'JSON Extension',
                'status' => extension_loaded('json'),
                'value' => extension_loaded('json') ? 'Installed' : 'Not installed'
            ],
            'mbstring' => [
                'name' => 'Mbstring Extension',
                'status' => extension_loaded('mbstring'),
                'value' => extension_loaded('mbstring') ? 'Installed' : 'Not installed'
            ],
            'writable_logs' => [
                'name' => 'Logs Directory Writable',
                'status' => is_writable(APPPATH . '../logs'),
                'value' => is_writable(APPPATH . '../logs') ? 'Writable' : 'Not writable'
            ]
        ];
        
        // Check if all requirements are met
        $data['all_checks_passed'] = true;
        foreach ($data['checks'] as $check) {
            if (!$check['status']) {
                $data['all_checks_passed'] = false;
                break;
            }
        }
        
        $this->load->view('backend/main', $data);
    }

    /**
     * Step 2: Network configuration
     */
    public function step_network() {
        $data = [];
        $data['page_name'] = 'setup_wizard';
        $data['page_title'] = 'Setup Wizard - Network Configuration';
        $data['account_type'] = $this->session->userdata('login_type');
        $data['current_step'] = 2;
        $data['total_steps'] = count($this->steps);
        
        // Get current IP address
        $data['local_ip'] = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
        $data['server_name'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        // Check if IP is static (192.168.x.x range)
        $data['is_static_ip'] = preg_match('/^192\.168\.\d+\.\d+$/', $data['local_ip']);
        
        $this->load->view('backend/main', $data);
    }
    
    /**
     * Step 3: Remote server credentials
     */
    public function step_remote_credentials() {
        // Check if this is an AJAX request - check for ajax parameter OR X-Requested-With header OR Accept header
        $is_ajax = $this->input->post('ajax') == '1' || 
                   $this->input->is_ajax_request() ||
                   (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
                   
        // Check if this is an AJAX request and test_connection is requested
        if ($is_ajax && $this->input->post('test_connection')) {
            $this->handle_ajax_connection_test();
            return;
        }
        
        
        // Regular page load
        $data = [];
        $data['page_name'] = 'setup_wizard';
        $data['page_title'] = 'Setup Wizard - Remote Server Credentials';
        $data['account_type'] = $this->session->userdata('login_type');
        $data['current_step'] = 3;
        $data['total_steps'] = count($this->steps);
        
        // Handle form submission (non-AJAX fallback)
        if ($this->input->post('test_connection')) {
            $credentials = [
                'host' => $this->input->post('remote_host'),
                'port' => $this->input->post('remote_port'),
                'user' => $this->input->post('remote_user'),
                'pass' => $this->input->post('remote_pass'),
                'database' => $this->input->post('remote_database')
            ];
            
            $test_result = $this->test_remote_connection($credentials);
            $data['test_result'] = $test_result;
            
            // If successful, save credentials
            if ($test_result['success']) {
                $this->save_remote_credentials($credentials);
                $data['credentials_saved'] = true;
            }
        }
        
        // Load existing credentials from settings table first
        $data['remote_host'] = $this->get_setting('remote_db_host', '');
        $data['remote_port'] = $this->get_setting('remote_db_port', '3306');
        $data['remote_user'] = $this->get_setting('remote_db_user', '');
        $data['remote_database'] = $this->get_setting('remote_db_name', '');
        
        // Load password from settings (decode it since it's base64 encoded)
        $encoded_pass = $this->get_setting('remote_db_pass', '');
        $data['remote_password'] = !empty($encoded_pass) ? base64_decode($encoded_pass) : '';
        
        // If no credentials in settings, auto-populate from database.php config
        if (empty($data['remote_host'])) {
            $remote_config = $this->get_remote_db_config();
            if ($remote_config) {
                $data['remote_host'] = $remote_config['hostname'];
                $data['remote_port'] = !empty($remote_config['port']) ? $remote_config['port'] : '3306';
                $data['remote_user'] = $remote_config['username'];
                $data['remote_database'] = $remote_config['database'];
                $data['remote_password'] = $remote_config['password']; // Pass password to view for auto-fill
                $data['auto_populated'] = true; // Flag to show user that values were auto-populated
            }
        }
        
        $this->load->view('backend/main', $data);
    }
    
    /**
     * Get remote database configuration from database.php config file
     */
    private function get_remote_db_config() {
        // Load database config
        $config_file = APPPATH . 'config/database.php';
        
        if (!file_exists($config_file)) {
            return false;
        }
        
        // Include the config file to get $db array
        include($config_file);
        
        // Check if remote_db configuration exists
        if (isset($db['remote_db']) && is_array($db['remote_db'])) {
            // Extract port from hostname if it contains port (e.g., "host:port")
            $hostname = $db['remote_db']['hostname'];
            $port = '3306'; // default
            
            if (strpos($hostname, ':') !== false) {
                list($hostname, $port) = explode(':', $hostname);
            }
            
            return [
                'hostname' => $hostname,
                'port' => $port,
                'username' => $db['remote_db']['username'],
                'password' => $db['remote_db']['password'],
                'database' => $db['remote_db']['database']
            ];
        }
        
        return false;
    }
    
    /**
     * Handle AJAX connection test
     */
    private function handle_ajax_connection_test() {
        $credentials = [
            'host' => $this->input->post('remote_host'),
            'port' => $this->input->post('remote_port'),
            'user' => $this->input->post('remote_user'),
            'pass' => $this->input->post('remote_pass'),
            'database' => $this->input->post('remote_database')
        ];
        
        $test_result = $this->test_remote_connection($credentials);
        
        // If successful, save credentials
        if ($test_result['success']) {
            $this->save_remote_credentials($credentials);
            $test_result['data'] = [
                'host' => $credentials['host'],
                'database' => $credentials['database'],
                'status' => 'Credentials saved successfully'
            ];
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($test_result));
    }
    
    /**
     * Step 4: Install sync columns
     */
    public function step_install_sync_columns() {
        // Check if this is an AJAX request
        if ($this->input->is_ajax_request() && $this->input->post('install_columns')) {
            $this->handle_ajax_install_columns();
            return;
        }
        
        // Regular page load
        $data = [];
        $data['page_name'] = 'setup_wizard';
        $data['page_title'] = 'Setup Wizard - Install Sync Columns';
        $data['account_type'] = $this->session->userdata('login_type');
        $data['current_step'] = 4;
        $data['total_steps'] = count($this->steps);
        
        // Check if sync columns are already installed
        $data['columns_installed'] = $this->check_sync_columns_installed();
        
        // Handle installation request (non-AJAX fallback)
        if ($this->input->post('install_columns') && !$data['columns_installed']) {
            $result = $this->install_sync_columns();
            $data['installation_result'] = $result;
            $data['columns_installed'] = $result['success'];
        }
        
        $this->load->view('backend/main', $data);
    }
    
    /**
     * Handle AJAX install columns request
     */
    private function handle_ajax_install_columns() {
        $result = $this->install_sync_columns();
        
        if ($result['success']) {
            $result['data'] = [
                'tables_updated' => 'All tables updated with sync columns',
                'metadata_initialized' => 'Sync metadata table initialized'
            ];
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }
    
    /**
     * Step 5: Test sync connection
     */
    public function step_test_sync() {
        // Check if this is an AJAX request
        if ($this->input->is_ajax_request() && $this->input->post('run_test_sync')) {
            $this->handle_ajax_test_sync();
            return;
        }
        
        // Regular page load
        $data = [];
        $data['page_name'] = 'setup_wizard';
        $data['page_title'] = 'Setup Wizard - Test Sync';
        $data['account_type'] = $this->session->userdata('login_type');
        $data['current_step'] = 5;
        $data['total_steps'] = count($this->steps);
        
        // Handle test sync request (non-AJAX)
        if ($this->input->post('run_test_sync')) {
            $result = $this->run_test_sync();
            $data['test_result'] = $result;
        }
        
        $this->load->view('backend/main', $data);
    }
    
    /**
     * Handle AJAX test sync request
     */
    private function handle_ajax_test_sync() {
        $result = $this->run_test_sync();
        
        // If successful, include test results in response data
        if ($result['success'] && isset($result['details'])) {
            $result['data'] = [
                'test_results' => $result['details'],
                'status' => 'Test sync completed successfully'
            ];
            // Remove details from top level to keep response structure consistent
            unset($result['details']);
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }
    
    /**
     * Step 6: Schedule configuration
     */
    public function step_schedule() {
        // Check if this is an AJAX request
        if ($this->input->is_ajax_request() && $this->input->post('create_batch')) {
            $this->handle_ajax_create_batch();
            return;
        }
        
        // Regular page load logic for non-AJAX requests
        $data = [];
        $data['page_name'] = 'setup_wizard';
        $data['page_title'] = 'Setup Wizard - Schedule Configuration';
        $data['account_type'] = $this->session->userdata('login_type');
        $data['current_step'] = 6;
        $data['total_steps'] = count($this->steps);
        
        // Generate batch file content
        $data['batch_file_content'] = $this->generate_batch_file();
        $data['php_path'] = PHP_BINARY;
        $data['project_path'] = FCPATH;
        
        // Get sync frequency setting
        $data['sync_frequency'] = $this->get_setting('sync_frequency_hours', '2');
        
        $this->load->view('backend/main', $data);
    }
    
    /**
     * Handle AJAX create batch file request
     */
    private function handle_ajax_create_batch() {
        try {
            $batch_content = $this->generate_batch_file();
            $batch_path = FCPATH . 'sync_to_cloud.bat';
            
            // Check if directory is writable before attempting to write
            $directory = dirname($batch_path);
            if (!is_writable($directory)) {
                $response = [
                    'success' => false,
                    'message' => 'Failed to create batch file. Directory is not writable. Please check permissions for: ' . $directory
                ];
                
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($response));
                return;
            }
            
            // Wrap file_put_contents in try-catch for additional error handling
            try {
                $result = file_put_contents($batch_path, $batch_content);
                
                // Check for false return value
                if ($result === false) {
                    $response = [
                        'success' => false,
                        'message' => 'Failed to create batch file. The file operation returned an error. Please verify write permissions for: ' . $batch_path
                    ];
                } else {
                    // Verify file was actually created and is readable
                    if (!file_exists($batch_path)) {
                        $response = [
                            'success' => false,
                            'message' => 'Batch file write reported success but file does not exist. Please check filesystem permissions.'
                        ];
                    } else {
                        $response = [
                            'success' => true,
                            'message' => 'Batch file created successfully!',
                            'data' => [
                                'file_path' => $batch_path,
                                'file_size' => filesize($batch_path) . ' bytes',
                                'instructions' => 'Use Windows Task Scheduler to run this file every 5-10 minutes'
                            ]
                        ];
                    }
                }
            } catch (Exception $e) {
                // Catch file operation specific errors
                $error_message = $e->getMessage();
                
                // Provide more specific error messages based on common issues
                if (strpos($error_message, 'Permission denied') !== false) {
                    $response = [
                        'success' => false,
                        'message' => 'Permission denied: Unable to write batch file. Please ensure the web server has write permissions for: ' . $batch_path
                    ];
                } elseif (strpos($error_message, 'No such file or directory') !== false) {
                    $response = [
                        'success' => false,
                        'message' => 'Directory not found: The target directory does not exist. Please verify the path: ' . $directory
                    ];
                } elseif (strpos($error_message, 'Disk full') !== false || strpos($error_message, 'No space left') !== false) {
                    $response = [
                        'success' => false,
                        'message' => 'Insufficient disk space: Unable to write batch file. Please free up disk space and try again.'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'File operation error: ' . $error_message
                    ];
                }
            }
        } catch (Exception $e) {
            // Catch any other unexpected errors
            $response = [
                'success' => false,
                'message' => 'Unexpected error while creating batch file: ' . $e->getMessage()
            ];
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    
    /**
     * Step 7: Completion
     */
    public function step_complete() {
        $data = [];
        $data['page_name'] = 'setup_wizard';
        $data['page_title'] = 'Setup Wizard - Complete';
        $data['account_type'] = $this->session->userdata('login_type');
        $data['current_step'] = 7;
        $data['total_steps'] = count($this->steps);
        
        // Mark setup as complete
        $this->mark_setup_complete();
        
        $this->load->view('backend/main', $data);
    }
    
    /**
     * Test remote database connection
     */
    private function test_remote_connection($credentials) {
        try {
            // Validate credentials before attempting connection
            if (empty($credentials['host']) || empty($credentials['user']) || empty($credentials['database'])) {
                return [
                    'success' => false,
                    'message' => 'Missing required connection parameters. Please provide host, user, and database name.'
                ];
            }
            
            // Suppress PHP warnings and handle errors through mysqli
            mysqli_report(MYSQLI_REPORT_OFF);
            
            // Attempt connection with error handling
            $mysqli = @new mysqli(
                $credentials['host'],
                $credentials['user'],
                $credentials['pass'],
                $credentials['database'],
                $credentials['port']
            );
            
            // Check for connection errors
            if ($mysqli->connect_errno) {
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $mysqli->connect_error,
                    'error_code' => $mysqli->connect_errno
                ];
            }
            
            // Verify connection is actually working by running a simple query
            $result = $mysqli->query("SELECT 1");
            if (!$result) {
                $error_message = $mysqli->error;
                $mysqli->close();
                return [
                    'success' => false,
                    'message' => 'Connection established but query failed: ' . $error_message
                ];
            }
            
            // Get server info for confirmation
            $server_info = $mysqli->server_info;
            $mysqli->close();
            
            return [
                'success' => true,
                'message' => 'Connection successful!',
                'server_version' => $server_info
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database connection error: ' . $e->getMessage()
            ];
        } catch (Error $e) {
            // Catch PHP 7+ errors (like mysqli constructor failures)
            return [
                'success' => false,
                'message' => 'Database connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Save remote credentials to settings
     */
    private function save_remote_credentials($credentials) {
        $settings = [
            'remote_db_host' => $credentials['host'],
            'remote_db_port' => $credentials['port'],
            'remote_db_user' => $credentials['user'],
            'remote_db_pass' => base64_encode($credentials['pass']), // Basic encoding
            'remote_db_name' => $credentials['database']
        ];
        
        foreach ($settings as $type => $value) {
            $this->db->where('type', $type);
            $exists = $this->db->get('settings')->num_rows() > 0;
            
            if ($exists) {
                $this->db->where('type', $type);
                $this->db->update('settings', ['description' => $value]);
            } else {
                $this->db->insert('settings', [
                    'type' => $type,
                    'description' => $value
                ]);
            }
        }
    }
    
    /**
     * Check if sync columns are installed
     */
    private function check_sync_columns_installed() {
        // Check if sync_status column exists in student table
        $query = $this->db->query("SHOW COLUMNS FROM student LIKE 'sync_status'");
        return $query->num_rows() > 0;
    }
    
    /**
     * Install sync columns
     */
    private function install_sync_columns() {
        try {
            // Read and execute SQL file
            $sql_file = FCPATH . 'database/add_sync_columns.sql';
            
            if (!file_exists($sql_file)) {
                return [
                    'success' => false,
                    'message' => 'SQL file not found: ' . $sql_file
                ];
            }
            
            $sql = file_get_contents($sql_file);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $this->db->query($statement);
                        
                        // Check for database errors
                        if ($this->db->error()['code'] !== 0) {
                            $db_error = $this->db->error();
                            return [
                                'success' => false,
                                'message' => 'SQL execution error: ' . $db_error['message'],
                                'error_code' => $db_error['code'],
                                'sql_statement' => substr($statement, 0, 200) // First 200 chars for debugging
                            ];
                        }
                    } catch (Exception $e) {
                        return [
                            'success' => false,
                            'message' => 'SQL execution error: ' . $e->getMessage(),
                            'sql_statement' => substr($statement, 0, 200) // First 200 chars for debugging
                        ];
                    }
                }
            }
            
            // Also run initialize_sync_metadata.sql
            $metadata_file = FCPATH . 'database/initialize_sync_metadata.sql';
            if (file_exists($metadata_file)) {
                $sql = file_get_contents($metadata_file);
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            $this->db->query($statement);
                            
                            // Check for database errors
                            if ($this->db->error()['code'] !== 0) {
                                $db_error = $this->db->error();
                                return [
                                    'success' => false,
                                    'message' => 'SQL execution error in metadata initialization: ' . $db_error['message'],
                                    'error_code' => $db_error['code'],
                                    'sql_statement' => substr($statement, 0, 200) // First 200 chars for debugging
                                ];
                            }
                        } catch (Exception $e) {
                            return [
                                'success' => false,
                                'message' => 'SQL execution error in metadata initialization: ' . $e->getMessage(),
                                'sql_statement' => substr($statement, 0, 200) // First 200 chars for debugging
                            ];
                        }
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => 'Sync columns installed successfully!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Run test sync
     */
    private function run_test_sync() {
        try {
            // Check if remote credentials are configured
            $remote_host = $this->db->get_where('settings', ['type' => 'remote_db_host'])->row();
            $remote_user = $this->db->get_where('settings', ['type' => 'remote_db_user'])->row();
            $remote_pass = $this->db->get_where('settings', ['type' => 'remote_db_pass'])->row();
            $remote_name = $this->db->get_where('settings', ['type' => 'remote_db_name'])->row();
            
            if (!$remote_host || empty($remote_host->description)) {
                return [
                    'success' => false,
                    'message' => 'Remote database credentials not configured. Please complete Step 3 first.'
                ];
            }
            
            // Test 1: Check internet connectivity
            $online = $this->check_internet_connection();
            if (!$online) {
                return [
                    'success' => false,
                    'message' => 'No internet connection. Please check your network.'
                ];
            }
            
            // Test 2: Try to connect to remote database
            // Decode the password since it was base64 encoded when saved
            $decoded_pass = $remote_pass ? base64_decode($remote_pass->description) : '';
            
            $connection_test = $this->verify_remote_db_connection(
                $remote_host->description,
                $remote_user ? $remote_user->description : '',
                $decoded_pass,
                $remote_name ? $remote_name->description : ''
            );
            
            if (!$connection_test['success']) {
                return $connection_test;
            }
            
            // Test 3: Check if sync columns are installed
            $sync_columns_check = $this->check_sync_columns_installed();
            if (!$sync_columns_check) {
                return [
                    'success' => false,
                    'message' => 'Sync columns not installed. Please complete Step 4 first.'
                ];
            }
            
            // All tests passed
            return [
                'success' => true,
                'message' => 'Test sync completed successfully! All connectivity tests passed.',
                'details' => [
                    'internet_connection' => 'OK',
                    'remote_database_connection' => 'OK',
                    'sync_columns_installed' => 'OK',
                    'remote_host' => $remote_host->description,
                    'remote_database' => $remote_name ? $remote_name->description : 'N/A'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check internet connection
     */
    private function check_internet_connection() {
        // Try curl first (more reliable on Windows)
        if (function_exists('curl_init')) {
            $test_urls = ['http://www.google.com', 'http://www.cloudflare.com'];
            
            foreach ($test_urls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code > 0) {
                    return true;
                }
            }
        }
        
        // Fallback to fsockopen
        if (function_exists('fsockopen')) {
            $connection = @fsockopen('www.google.com', 80, $errno, $errstr, 3);
            if ($connection) {
                fclose($connection);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verify remote database connection for test sync
     */
    private function verify_remote_db_connection($host, $user, $pass, $name) {
        try {
            // Get port from settings or use default
            $port_setting = $this->db->get_where('settings', ['type' => 'remote_db_port'])->row();
            $port = $port_setting ? $port_setting->description : 3306;
            
            // Try to connect to remote database
            $mysqli = @new mysqli($host, $user, $pass, $name, $port);
            
            if ($mysqli->connect_error) {
                return [
                    'success' => false,
                    'message' => 'Remote database connection failed: ' . $mysqli->connect_error
                ];
            }
            
            // Connection successful
            $mysqli->close();
            
            return [
                'success' => true,
                'message' => 'Remote database connection successful'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Remote database connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate batch file content
     */
    private function generate_batch_file() {
        $php_path = PHP_BINARY;
        $project_path = FCPATH;
        
        $content = "@echo off\n";
        $content .= "cd /d \"$project_path\"\n";
        $content .= "\"$php_path\" index.php sync_server auto_sync >> logs\\sync.log 2>&1\n";
        
        return $content;
    }
    
    /**
     * Mark setup as complete
     */
    private function mark_setup_complete() {
        $this->db->where('type', 'setup_wizard_complete');
        $exists = $this->db->get('settings')->num_rows() > 0;
        
        if ($exists) {
            $this->db->where('type', 'setup_wizard_complete');
            $this->db->update('settings', ['description' => '1']);
        } else {
            $this->db->insert('settings', [
                'type' => 'setup_wizard_complete',
                'description' => '1'
            ]);
        }
        
        // Enable sync by default
        $this->db->where('type', 'sync_enabled');
        $exists = $this->db->get('settings')->num_rows() > 0;
        
        if ($exists) {
            $this->db->where('type', 'sync_enabled');
            $this->db->update('settings', ['description' => '1']);
        } else {
            $this->db->insert('settings', [
                'type' => 'sync_enabled',
                'description' => '1'
            ]);
        }
    }
    
    /**
     * Get setting value
     */
    private function get_setting($type, $default = '') {
        $setting = $this->db->get_where('settings', ['type' => $type])->row();
        return $setting ? $setting->description : $default;
    }
    
    /**
     * Navigate to next step
     */
    public function next_step() {
        $current_step = $this->get_current_step();
        $next_step = min($current_step + 1, count($this->steps));
        $this->save_current_step($next_step);
        redirect(site_url('setup_wizard/step/' . $next_step));
    }
    
    /**
     * Navigate to previous step
     */
    public function previous_step() {
        $current_step = $this->get_current_step();
        $previous_step = max($current_step - 1, 1);
        $this->save_current_step($previous_step);
        redirect(site_url('setup_wizard/step/' . $previous_step));
    }
    
    /**
     * Reset wizard to step 1
     */
    public function reset_wizard() {
        // Reset wizard step to 1
        $this->save_current_step(1);
        
        // Optionally clear setup completion flag
        $this->db->where('type', 'setup_wizard_complete');
        $this->db->delete('settings');
        
        return true;
    }
    
    /**
     * Public reset method accessible via URL
     */
    public function reset() {
        $this->reset_wizard();
        redirect(site_url('setup_wizard/step/1'));
    }
}
