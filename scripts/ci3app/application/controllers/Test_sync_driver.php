<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Test Controller for MY_DB_mysqli_driver update() method
 * 
 * Access via: http://localhost/schoolmanager/index.php/test_sync_driver
 */
class Test_sync_driver extends CI_Controller {
    
    public function index() {
        // Set content type to plain text for better readability
        header('Content-Type: text/plain; charset=utf-8');
        
        echo "=== Testing MY_DB_mysqli_driver update() Method ===\n\n";
        
        // Test 1: Check if custom driver is loaded
        echo "Test 1: Verify custom driver is loaded\n";
        $driver_class = get_class($this->db);
        echo "Driver class: " . $driver_class . "\n";
        if ($driver_class === 'MY_DB_mysqli_driver') {
            echo "✓ Custom driver loaded successfully\n\n";
        } else {
            echo "✗ Custom driver NOT loaded (using " . $driver_class . ")\n\n";
            echo "Please ensure MY_DB_mysqli_driver.php is in application/core/\n";
            return;
        }
        
        // Test 2: Check if sync table cache can be built
        echo "Test 2: Build sync table cache\n";
        try {
            // Force cache rebuild
            $this->db->refresh_sync_cache();
            echo "✓ Sync table cache built successfully\n\n";
        } catch (Exception $e) {
            echo "✗ Failed to build sync table cache: " . $e->getMessage() . "\n\n";
        }
        
        // Test 3: Test update on a sync-enabled table (students table)
        echo "Test 3: Test update on sync-enabled table (students)\n";
        try {
            // Check if students table exists
            $query = $this->db->query("SHOW TABLES LIKE 'students'");
            if ($query->num_rows() == 0) {
                echo "⚠ Students table does not exist, skipping test\n\n";
            } else {
                // Get a student record to update
                $student = $this->db->select('student_id, name, sync_status, version')
                                    ->from('students')
                                    ->limit(1)
                                    ->get()
                                    ->row();
                
                if ($student) {
                    echo "Found student: ID=" . $student->student_id . ", Name=" . $student->name . "\n";
                    echo "Before update: sync_status=" . $student->sync_status . ", version=" . $student->version . "\n";
                    
                    // Store original name to restore later
                    $original_name = $student->name;
                    $test_name = $original_name . " [TEST]";
                    
                    // Perform update
                    $this->db->where('student_id', $student->student_id);
                    $this->db->update('students', ['name' => $test_name]);
                    
                    // Fetch updated record
                    $updated_student = $this->db->select('student_id, name, sync_status, version, last_modified_at, device_id, last_modified_by')
                                                ->from('students')
                                                ->where('student_id', $student->student_id)
                                                ->get()
                                                ->row();
                    
                    echo "After update: sync_status=" . $updated_student->sync_status . ", version=" . $updated_student->version . "\n";
                    echo "  last_modified_at=" . $updated_student->last_modified_at . "\n";
                    echo "  device_id=" . $updated_student->device_id . "\n";
                    echo "  last_modified_by=" . ($updated_student->last_modified_by ?? 'NULL') . "\n";
                    
                    // Verify sync columns were injected
                    $success = true;
                    if ($updated_student->sync_status !== 'PENDING') {
                        echo "✗ sync_status should be 'PENDING' but is '" . $updated_student->sync_status . "'\n";
                        $success = false;
                    }
                    if ($updated_student->version <= $student->version) {
                        echo "✗ version should be incremented but is " . $updated_student->version . " (was " . $student->version . ")\n";
                        $success = false;
                    }
                    if (empty($updated_student->device_id)) {
                        echo "✗ device_id should not be empty\n";
                        $success = false;
                    }
                    
                    if ($success) {
                        echo "✓ Sync columns injected successfully\n";
                        echo "✓ Version incremented from " . $student->version . " to " . $updated_student->version . "\n";
                    }
                    
                    // Restore original name
                    $this->db->where('student_id', $student->student_id);
                    $this->db->update('students', ['name' => $original_name]);
                    echo "✓ Original name restored\n\n";
                    
                } else {
                    echo "⚠ No student records found, skipping test\n\n";
                }
            }
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
        }
        
        // Test 4: Test update with WHERE conditions
        echo "Test 4: Test update with WHERE conditions and LIMIT\n";
        try {
            $query = $this->db->query("SHOW TABLES LIKE 'students'");
            if ($query->num_rows() > 0) {
                // Count records before update
                $count_before = $this->db->where('student_id >', 0)
                                        ->where('name IS NOT NULL', NULL, FALSE)
                                        ->count_all_results('students');
                
                if ($count_before > 0) {
                    // Update with complex WHERE conditions
                    $this->db->where('student_id >', 0);
                    $this->db->where('name IS NOT NULL', NULL, FALSE);
                    $this->db->limit(1);
                    $result = $this->db->update('students', ['name' => 'Test Student']);
                    
                    if ($result) {
                        echo "✓ Update with WHERE conditions and LIMIT executed successfully\n";
                        
                        // Verify the record was marked as PENDING
                        $updated = $this->db->select('sync_status')
                                           ->from('students')
                                           ->where('name', 'Test Student')
                                           ->limit(1)
                                           ->get()
                                           ->row();
                        
                        if ($updated && $updated->sync_status === 'PENDING') {
                            echo "✓ Record marked as PENDING\n\n";
                        } else {
                            echo "⚠ Record not marked as PENDING\n\n";
                        }
                    } else {
                        echo "⚠ Update returned false\n\n";
                    }
                } else {
                    echo "⚠ No matching records found, skipping test\n\n";
                }
            } else {
                echo "⚠ Students table does not exist, skipping test\n\n";
            }
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n\n";
        }
        
        // Test 5: Test with set() method chaining
        echo "Test 5: Test update with set() method chaining\n";
        try {
            $query = $this->db->query("SHOW TABLES LIKE 'students'");
            if ($query->num_rows() > 0) {
                $student = $this->db->select('student_id, name, version')
                                    ->from('students')
                                    ->limit(1)
                                    ->get()
                                    ->row();
                
                if ($student) {
                    $original_version = $student->version;
                    
                    // Use set() method chaining
                    $this->db->set('name', $student->name);
                    $this->db->where('student_id', $student->student_id);
                    $this->db->update('students');
                    
                    // Verify version was incremented
                    $updated = $this->db->select('version, sync_status')
                                       ->from('students')
                                       ->where('student_id', $student->student_id)
                                       ->get()
                                       ->row();
                    
                    if ($updated->version > $original_version && $updated->sync_status === 'PENDING') {
                        echo "✓ set() method chaining works correctly\n";
                        echo "✓ Version incremented from " . $original_version . " to " . $updated->version . "\n\n";
                    } else {
                        echo "✗ set() method chaining did not inject sync columns correctly\n\n";
                    }
                } else {
                    echo "⚠ No student records found, skipping test\n\n";
                }
            } else {
                echo "⚠ Students table does not exist, skipping test\n\n";
            }
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n\n";
        }
        
        echo "=== Test Complete ===\n";
    }
}
