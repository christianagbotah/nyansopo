<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Test Locations Controller
 * 
 * Simple test controller to verify the Locations functionality works
 */
class Test_locations extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    public function index() {
        echo "<h1>Location System Test</h1>";
        
        // Test 1: Check if location_registry table exists
        echo "<h2>Test 1: Database Table</h2>";
        if ($this->db->table_exists('location_registry')) {
            echo "✅ location_registry table exists<br>";
            
            $count = $this->db->count_all_results('location_registry');
            echo "Total locations: $count<br>";
        } else {
            echo "❌ location_registry table does not exist<br>";
        }
        
        // Test 2: Try to load the model
        echo "<h2>Test 2: Load Model</h2>";
        try {
            $this->load->model('Location_registry_model');
            echo "✅ Location_registry_model loaded successfully<br>";
            
            // Test a method
            $locations = $this->Location_registry_model->get_active_locations();
            echo "Active locations: " . count($locations) . "<br>";
        } catch (Exception $e) {
            echo "❌ Failed to load model: " . $e->getMessage() . "<br>";
        }
        
        // Test 3: Try to load the library
        echo "<h2>Test 3: Load Library</h2>";
        try {
            $this->load->library('Location_manager');
            if (isset($this->Location_manager)) {
                echo "✅ Location_manager library loaded successfully<br>";
                
                // Test a method
                $all_locations = $this->Location_manager->get_all_locations();
                echo "All locations: " . count($all_locations) . "<br>";
            } else {
                echo "❌ Location_manager is null after loading<br>";
            }
        } catch (Exception $e) {
            echo "❌ Failed to load library: " . $e->getMessage() . "<br>";
        }
        
        // Test 4: Check MY_Model
        echo "<h2>Test 4: MY_Model Check</h2>";
        $my_model_path = APPPATH . 'core/MY_Model.php';
        if (file_exists($my_model_path)) {
            echo "✅ MY_Model.php exists at: $my_model_path<br>";
            echo "File size: " . filesize($my_model_path) . " bytes<br>";
        } else {
            echo "❌ MY_Model.php not found<br>";
        }
        
        // Test 5: OPcache status
        echo "<h2>Test 5: OPcache Status</h2>";
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status(false);
            if ($status) {
                echo "OPcache enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "<br>";
                echo "Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "<br>";
                
                if (function_exists('opcache_reset')) {
                    echo "<form method='post'>";
                    echo "<button type='submit' name='clear_cache' value='1'>Clear OPcache</button>";
                    echo "</form>";
                }
            } else {
                echo "OPcache status not available<br>";
            }
        } else {
            echo "OPcache not installed<br>";
        }
        
        // Handle cache clear
        if (isset($_POST['clear_cache']) && function_exists('opcache_reset')) {
            if (opcache_reset()) {
                echo "<p style='color:green'>✅ OPcache cleared successfully!</p>";
                echo "<p>Please refresh this page to see the changes.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to clear OPcache</p>";
            }
        }
        
        echo "<hr>";
        echo "<p><a href='" . site_url('locations') . "'>Go to Locations</a></p>";
    }
}
