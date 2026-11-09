<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Test Controller
 * 
 * Provides web-based interface to run unit tests for libraries.
 * Access via: http://yoursite.com/index.php/test_controller/[test_name]
 * 
 * @package    SchoolManager
 * @subpackage Controllers
 * @category   Testing
 * @author     Kiro AI Assistant
 * @version    1.0.0
 * @since      June 4, 2026
 */
class Test_controller extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Security: Only allow access in development mode or for admins
        if (ENVIRONMENT !== 'development') {
            // Check if user is logged in as admin
            if (!$this->session->userdata('admin_login') && !$this->session->userdata('user_id')) {
                show_error('Tests can only be run in development mode or by administrators.', 403);
            }
        }
    }
    
    /**
     * Index page - Show available tests
     */
    public function index() {
        echo '<html><head><title>Unit Tests</title>';
        echo '<style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            h1 { color: #333; }
            .test-list { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            .test-list a { display: block; padding: 10px; margin: 5px 0; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
            .test-list a:hover { background: #0056b3; }
            .info { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-bottom: 20px; }
        </style></head><body>';
        
        echo '<h1>🧪 Unit Test Suite</h1>';
        
        echo '<div class="info">';
        echo '<strong>ℹ️ Information:</strong><br>';
        echo 'Run individual test suites by clicking the links below.<br>';
        echo 'Tests will output results directly to the browser with pass/fail status.';
        echo '</div>';
        
        echo '<div class="test-list">';
        echo '<h2>Available Tests:</h2>';
        echo '<a href="' . site_url('test_controller/payroll_validator') . '">📋 Payroll Validator Tests (12 tests)</a>';
        echo '<a href="' . site_url('test_controller/tax_calculator') . '">💰 Tax Calculator Tests (14 tests)</a>';
        echo '<a href="' . site_url('test_controller/audit_logger') . '">📊 Audit Logger Tests (8 tests)</a>';
        echo '<a href="' . site_url('test_controller/run_all') . '">▶️ Run All Tests</a>';
        echo '</div>';
        
        echo '</body></html>';
    }
    
    /**
     * Run Payroll_validator tests
     */
    public function payroll_validator() {
        // Load test class
        require_once APPPATH . '../tests/libraries/Payroll_validator_test.php';
        
        // Start output buffering with pre tag for formatting
        echo '<html><head><title>Payroll Validator Tests</title>';
        echo '<style>
            body { font-family: "Courier New", monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
            pre { white-space: pre-wrap; }
            .back-link { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin-bottom: 20px; }
            .back-link:hover { background: #0056b3; }
        </style></head><body>';
        
        echo '<a href="' . site_url('test_controller') . '" class="back-link">← Back to Test Suite</a>';
        echo '<pre>';
        
        // Run tests
        $test = new Payroll_validator_test();
        $test->run_all_tests();
        
        echo '</pre>';
        echo '<a href="' . site_url('test_controller') . '" class="back-link">← Back to Test Suite</a>';
        echo '</body></html>';
    }
    
    /**
     * Run Tax_calculator tests
     */
    public function tax_calculator() {
        // Load test class
        require_once APPPATH . '../tests/libraries/Tax_calculator_test.php';
        
        // Start output buffering with pre tag for formatting
        echo '<html><head><title>Tax Calculator Tests</title>';
        echo '<style>
            body { font-family: "Courier New", monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
            pre { white-space: pre-wrap; }
            .back-link { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin-bottom: 20px; }
            .back-link:hover { background: #0056b3; }
        </style></head><body>';
        
        echo '<a href="' . site_url('test_controller') . '" class="back-link">← Back to Test Suite</a>';
        echo '<pre>';
        
        // Run tests
        $test = new Tax_calculator_test();
        $test->run_all_tests();
        
        echo '</pre>';
        echo '<a href="' . site_url('test_controller') . '" class="back-link">← Back to Test Suite</a>';
        echo '</body></html>';
    }
    
    /**
     * Run Audit_logger tests
     */
    public function audit_logger() {
        // Load test class
        require_once APPPATH . '../tests/libraries/Audit_logger_test.php';
        
        // Start output buffering with pre tag for formatting
        echo '<html><head><title>Audit Logger Tests</title>';
        echo '<style>
            body { font-family: "Courier New", monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
            pre { white-space: pre-wrap; }
            .back-link { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin-bottom: 20px; }
            .back-link:hover { background: #0056b3; }
        </style></head><body>';
        
        echo '<a href="' . site_url('test_controller') . '" class="back-link">← Back to Test Suite</a>';
        echo '<pre>';
        
        // Run tests
        $test = new Audit_logger_test();
        $test->run_all_tests();
        
        echo '</pre>';
        echo '<a href="' . site_url('test_controller') . '" class="back-link">← Back to Test Suite</a>';
        echo '</body></html>';
    }
    
    /**
     * Run all tests
     */
    public function run_all() {
        echo '<html><head><title>All Tests</title>';
        echo '<style>
            body { font-family: "Courier New", monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
            pre { white-space: pre-wrap; }
            .back-link { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin-bottom: 20px; }
            .back-link:hover { background: #0056b3; }
            .test-section { border-top: 2px solid #007bff; margin-top: 30px; padding-top: 10px; }
        </style></head><body>';
        
        echo '<a href="' . site_url('test_controller') . '" class="back-link">← Back to Test Suite</a>';
        
        // Run Payroll Validator tests
        echo '<div class="test-section"><pre>';
        require_once APPPATH . '../tests/libraries/Payroll_validator_test.php';
        $test1 = new Payroll_validator_test();
        $test1->run_all_tests();
        echo '</pre></div>';
        
        // Run Tax Calculator tests
        echo '<div class="test-section"><pre>';
        require_once APPPATH . '../tests/libraries/Tax_calculator_test.php';
        $test2 = new Tax_calculator_test();
        $test2->run_all_tests();
        echo '</pre></div>';
        
        // Run Audit Logger tests
        echo '<div class="test-section"><pre>';
        require_once APPPATH . '../tests/libraries/Audit_logger_test.php';
        $test3 = new Audit_logger_test();
        $test3->run_all_tests();
        echo '</pre></div>';
        
        echo '<a href="' . site_url('test_controller') . '" class="back-link">← Back to Test Suite</a>';
        echo '</body></html>';
    }
}
