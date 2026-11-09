<?php

/**
 * Test Bootstrap
 * 
 * Minimal bootstrap for testing Payroll_parser library
 */

// Define environment as testing
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Define basic constants
if (!defined('BASEPATH')) {
    define('BASEPATH', realpath(__DIR__ . '/../../system') . '/');
}

if (!defined('APPPATH')) {
    define('APPPATH', realpath(__DIR__ . '/../') . '/');
}

// Set error reporting for testing (suppress warnings for cleaner test output)
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 1);

// Mock the log_message function to prevent errors
if (!function_exists('log_message')) {
    function log_message($level, $message, $php_error = FALSE) {
        // Suppress logging during tests
        return TRUE;
    }
}

// Create a minimal CI mock for the parser tests
class CI_Mock {
    public $payroll_parser;
    public $payroll_printer;
    
    public function load_library($library) {
        if ($library === 'payroll_parser') {
            require_once APPPATH . 'libraries/Payroll_parser.php';
            $this->payroll_parser = new Payroll_parser();
        } elseif ($library === 'payroll_printer') {
            require_once APPPATH . 'libraries/Payroll_printer.php';
            $this->payroll_printer = new Payroll_printer();
        }
    }
    
    public function library($library) {
        $this->load_library($library);
    }
}

// Global CI instance - Initialize immediately
$_CI_mock = new CI_Mock();

// Mock get_instance function - always return the mock
if (!function_exists('get_instance')) {
    function &get_instance() {
        global $_CI_mock;
        // Make sure we always return the mock instance
        if ($_CI_mock === null) {
            $_CI_mock = new CI_Mock();
        }
        return $_CI_mock;
    }
}

echo "Minimal Test Bootstrap Loaded\n";