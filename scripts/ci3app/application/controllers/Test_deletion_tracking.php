<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Deletion Tracking System Test Controller
 * 
 * Tests the custom MY_DB_mysqli_driver delete() method with deletion tracking
 * 
 * Usage: Access via browser: http://localhost/schoolmanager/index.php/test_deletion_tracking
 */
class Test_deletion_tracking extends CI_Controller {
    
    public function index() {
        // Set content type
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Deletion Tracking System Tests</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .pass { background-color: #d4edda; border-color: #c3e6cb; }
        .fail { background-color: #f8d7da; border-color: #f5c6cb; }
        h2 { margin-top: 0; }
        .summary { font-weight: bold; font-size: 1.2em; margin: 20px 0; padding: 15px; background-color: #e7f3ff; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Deletion Tracking System Tests</h1>
';
        
        // Initialize test results
        $passed = 0;
        $failed = 0;
        
        // Test 1: Verify sync_deletions table exists
        echo $this->run_test("Verify sync_deletions table exists", function() {
            $tables = $this->db->list_tables();
            $exists = in_array('sync_deletions', $tables);
            echo "<p>sync_deletions table " . ($exists ? "EXISTS" : "DOES NOT EXIST") . "</p>";
            return $exists;
        }, $passed, $failed);
        
        // Create test table
        echo "<div class='test'><h2>Setup: Creating test_deletion_tracking table</h2>";
        $this->db->query("DROP TABLE IF EXISTS test_deletion_tracking");
        $this->db->query("
            CREATE TABLE test_deletion_tracking (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p>Test table created successfully</p></div>";
        
        // Insert test records
        $this->db->insert('test_deletion_tracking', ['name' => 'Test Record 1']);
        $insert_id1 = $this->db->insert_id();
        
        $this->db->insert('test_deletion_tracking', ['name' => 'Test Record 2']);
        $insert_id2 = $this->db->insert_id();
        
        $this->db->insert('test_deletion_tracking', ['name' => 'Test Record 3']);
        $insert_id3 = $this->db->insert_id();
        
        echo "<div class='test'><h2>Setup: Inserted 3 test records</h2>";
        echo "<p>Record IDs: {$insert_id1}, {$insert_id2}, {$insert_id3}</p></div>";
        
        // Test 2: Delete with array WHERE parameter
        echo $this->run_test("Delete with array WHERE parameter", function() use ($insert_id1) {
            $before_count = $this->db->count_all('test_deletion_tracking');
            echo "<p>Records before deletion: {$before_count}</p>";
            
            // Perform delete with array WHERE
            $this->db->delete('test_deletion_tracking', ['id' => $insert_id1]);
            
            $after_count = $this->db->count_all('test_deletion_tracking');
            echo "<p>Records after deletion: {$after_count}</p>";
            
            // Verify record was deleted
            $exists = $this->db->get_where('test_deletion_tracking', ['id' => $insert_id1])->num_rows();
            echo "<p>Record " . ($exists == 0 ? "DELETED" : "STILL EXISTS") . "</p>";
            
            // Check sync_deletions table
            $deletion_logged = $this->db->get_where('sync_deletions', [
                'table_name' => 'test_deletion_tracking',
                'record_id' => '{"id":"' . $insert_id1 . '"}'
            ])->num_rows();
            
            echo "<p>Deletion " . ($deletion_logged > 0 ? "WAS" : "WAS NOT") . " logged in sync_deletions</p>";
            
            return ($before_count - $after_count == 1) && ($deletion_logged > 0);
        }, $passed, $failed);
        
        // Test 3: Delete with string WHERE parameter
        echo $this->run_test("Delete with string WHERE parameter", function() use ($insert_id2) {
            $before_count = $this->db->count_all('test_deletion_tracking');
            echo "<p>Records before deletion: {$before_count}</p>";
            
            // Perform delete with string WHERE
            $this->db->delete('test_deletion_tracking', "id = {$insert_id2}");
            
            $after_count = $this->db->count_all('test_deletion_tracking');
            echo "<p>Records after deletion: {$after_count}</p>";
            
            // Verify record was deleted
            $exists = $this->db->get_where('test_deletion_tracking', ['id' => $insert_id2])->num_rows();
            echo "<p>Record " . ($exists == 0 ? "DELETED" : "STILL EXISTS") . "</p>";
            
            // Check sync_deletions table
            $deletion_logged = $this->db->get_where('sync_deletions', [
                'table_name' => 'test_deletion_tracking',
                'record_id' => '{"id":"' . $insert_id2 . '"}'
            ])->num_rows();
            
            echo "<p>Deletion " . ($deletion_logged > 0 ? "WAS" : "WAS NOT") . " logged in sync_deletions</p>";
            
            return ($before_count - $after_count == 1) && ($deletion_logged > 0);
        }, $passed, $failed);
        
        // Test 4: Delete with query builder WHERE
        echo $this->run_test("Delete with query builder WHERE", function() use ($insert_id3) {
            $before_count = $this->db->count_all('test_deletion_tracking');
            echo "<p>Records before deletion: {$before_count}</p>";
            echo "<p>Deleting record with ID: {$insert_id3} using query builder WHERE</p>";
            
            // Perform delete with query builder
            $this->db->where('id', $insert_id3);
            
            // DEBUG: Check qb_where state before calling delete()
            $reflection = new ReflectionClass($this->db);
            $qb_where_property = $reflection->getProperty('qb_where');
            $qb_where_property->setAccessible(true);
            $qb_where_value = $qb_where_property->getValue($this->db);
            echo "<p><strong>DEBUG:</strong> qb_where before delete() = " . json_encode($qb_where_value) . "</p>";
            echo "<p><strong>DEBUG:</strong> qb_where count = " . count($qb_where_value) . "</p>";
            
            try {
                $this->db->delete('test_deletion_tracking');
                
                $after_count = $this->db->count_all('test_deletion_tracking');
                echo "<p>Records after deletion: {$after_count}</p>";
                
                // Verify record was deleted
                $exists = $this->db->get_where('test_deletion_tracking', ['id' => $insert_id3])->num_rows();
                echo "<p>Record " . ($exists == 0 ? "DELETED" : "STILL EXISTS") . "</p>";
                
                // Check sync_deletions table
                $deletion_logged = $this->db->get_where('sync_deletions', [
                    'table_name' => 'test_deletion_tracking',
                    'record_id' => '{"id":"' . $insert_id3 . '"}'
                ])->num_rows();
                
                echo "<p>Deletion " . ($deletion_logged > 0 ? "WAS" : "WAS NOT") . " logged in sync_deletions</p>";
                
                return ($before_count - $after_count == 1) && ($deletion_logged > 0);
            } catch (Exception $e) {
                echo "<p><strong>ERROR:</strong> " . $e->getMessage() . "</p>";
                return false;
            }
        }, $passed, $failed);
        
        // Cleanup
        echo "<div class='test'><h2>Cleanup</h2>";
        $this->db->query("DROP TABLE IF EXISTS test_deletion_tracking");
        echo "<p>Test table dropped successfully</p></div>";
        
        // Summary
        $total = $passed + $failed;
        echo "<div class='summary'>";
        echo "<h2>Test Summary</h2>";
        echo "<p>Total Tests: {$total}</p>";
        echo "<p>Passed: {$passed}</p>";
        echo "<p>Failed: {$failed}</p>";
        echo "<p>Success Rate: " . ($total > 0 ? round(($passed / $total) * 100, 2) : 0) . "%</p>";
        echo "</div>";
        
        echo '</body></html>';
    }
    
    /**
     * Helper function to run a test
     */
    private function run_test($name, $callback, &$passed, &$failed) {
        echo "<div class='test";
        
        try {
            $result = $callback();
            
            if ($result) {
                echo " pass'><h2>✓ {$name}</h2>";
                $passed++;
            } else {
                echo " fail'><h2>✗ {$name}</h2>";
                $failed++;
            }
        } catch (Exception $e) {
            echo " fail'><h2>✗ {$name}</h2>";
            echo "<p><strong>Exception:</strong> " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
            $failed++;
        }
        
        echo "</div>";
    }
}
