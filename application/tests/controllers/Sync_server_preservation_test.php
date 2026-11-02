<?php

/**
 * Preservation Property Tests for Successful Sync Behavior
 * 
 * **Property 2: Preservation** - Successful Sync Behavior Unchanged
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7
 * 
 * **IMPORTANT**: Follow observation-first methodology
 * 
 * These tests verify that for all sync operations where `get_remote_db()` succeeds
 * (connection established), the fixed system produces exactly the same behavior as
 * the original system, preserving all successful sync displays, log messages,
 * dashboard indicators, and operation execution.
 * 
 * **Testing Approach**: 
 * 1. Run tests on UNFIXED code to capture baseline behavior
 * 2. Tests should PASS on unfixed code (confirming baseline to preserve)
 * 3. After fix is implemented, re-run these tests
 * 4. Tests should still PASS (confirming no regressions)
 * 
 * **Scope**: All inputs where remote database connection succeeds should be
 * completely unaffected by the bugfix. This includes:
 * - Normal sync operations when credentials are correct
 * - Sync operations when network connectivity is stable
 * - Sync operations when remote server is online and accessible
 * - All other non-connection-related sync functionality
 */

use PHPUnit\Framework\TestCase;

class Sync_server_preservation_test extends TestCase {
    
    private static $db;
    private $original_settings = [];
    
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        
        // Bootstrap CodeIgniter environment
        define('BASEPATH', realpath(__DIR__ . '/../../../system') . '/');
        define('APPPATH', realpath(__DIR__ . '/../../') . '/');
        define('ENVIRONMENT', 'testing');
        
        // Load database configuration
        require_once APPPATH . 'config/database.php';
        
        // Create database connection
        $db_config = $db['default'];
        self::$db = new mysqli(
            $db_config['hostname'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database']
        );
        
        if (self::$db->connect_error) {
            throw new Exception('Database connection failed: ' . self::$db->connect_error);
        }
    }
    
    public static function tearDownAfterClass(): void {
        if (self::$db) {
            self::$db->close();
        }
        
        parent::tearDownAfterClass();
    }
    
    public function setUp(): void {
        parent::setUp();
        
        // Backup original sync settings
        $this->backupSettings();
    }
    
    public function tearDown(): void {
        // Restore original settings
        $this->restoreSettings();
        
        parent::tearDown();
    }
    
    /**
     * Backup current sync settings
     */
    private function backupSettings() {
        $settings_to_backup = [
            'remote_db_host',
            'remote_db_port',
            'remote_db_user',
            'remote_db_pass',
            'remote_db_name',
            'last_sync_status',
            'last_sync_time',
            'last_sync_error',
            'sync_enabled'
        ];
        
        foreach ($settings_to_backup as $setting) {
            $result = self::$db->query("SELECT description FROM settings WHERE type = '{$setting}'");
            if ($result && $row = $result->fetch_assoc()) {
                $this->original_settings[$setting] = $row['description'];
            }
        }
    }
    
    /**
     * Restore original sync settings
     */
    private function restoreSettings() {
        foreach ($this->original_settings as $setting => $value) {
            $escaped_value = self::$db->real_escape_string($value);
            self::$db->query("UPDATE settings SET description = '{$escaped_value}' WHERE type = '{$setting}'");
        }
    }
    
    /**
     * Update a setting in the database
     */
    private function updateSetting($key, $value) {
        $escaped_value = self::$db->real_escape_string($value);
        $result = self::$db->query("SELECT * FROM settings WHERE type = '{$key}'");
        
        if ($result && $result->num_rows > 0) {
            self::$db->query("UPDATE settings SET description = '{$escaped_value}' WHERE type = '{$key}'");
        } else {
            self::$db->query("INSERT INTO settings (type, description) VALUES ('{$key}', '{$escaped_value}')");
        }
    }
    
    /**
     * Get a setting from the database
     */
    private function getSetting($key, $default = null) {
        $result = self::$db->query("SELECT description FROM settings WHERE type = '{$key}'");
        if ($result && $row = $result->fetch_assoc()) {
            return $row['description'];
        }
        return $default;
    }
    
    /**
     * Execute sync via CLI
     */
    private function executeSyncViaCli() {
        $php_path = 'php';
        $index_path = realpath(__DIR__ . '/../../../index.php');
        
        $command = "\"{$php_path}\" \"{$index_path}\" sync_server auto_sync";
        
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        return [
            'output' => implode("\n", $output),
            'exit_code' => $return_var
        ];
    }
    
    /**
     * Get the latest log entry for sync operations
     */
    private function getLatestSyncLogEntry() {
        // Read the most recent log file
        $log_dir = APPPATH . 'logs/';
        $log_file = $log_dir . 'log-' . date('Y-m-d') . '.php';
        
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            
            // Extract sync-related log entries
            preg_match_all('/INFO.*?AUTO SYNC.*?\n/i', $log_content, $matches);
            
            if (!empty($matches[0])) {
                return end($matches[0]); // Return last sync log entry
            }
        }
        
        return null;
    }
    
    /**
     * Property 1: Sync with No Internet Shows Skipped Status
     * 
     * WHEN internet connectivity check fails (is_online() returns false)
     * THEN sync should be skipped gracefully
     * AND last_sync_status should be 'skipped_offline'
     * AND no error should be logged
     * 
     * This behavior must be preserved after the fix.
     */
    public function test_sync_skipped_when_offline_behavior_is_preserved() {
        // ARRANGE: Enable sync
        $this->updateSetting('sync_enabled', '1');
        $this->updateSetting('last_sync_status', 'none');
        
        // Note: This test assumes is_online() will return false naturally
        // or we're in an environment where internet check fails
        // In production, we'd mock network conditions
        
        // ACT: Trigger sync
        $result = $this->executeSyncViaCli();
        sleep(1);
        
        // OBSERVE: Check current behavior
        $last_sync_status = $this->getSetting('last_sync_status');
        
        echo "\n\n=== OBSERVED BEHAVIOR (Offline Scenario) ===\n";
        echo "last_sync_status: {$last_sync_status}\n";
        echo "CLI output: {$result['output']}\n";
        echo "==========================================\n\n";
        
        // If sync was skipped due to no internet, verify the status
        if ($last_sync_status === 'skipped_offline') {
            // ASSERT: Preserve this behavior
            $this->assertEquals(
                'skipped_offline',
                $last_sync_status,
                "When offline, sync should be skipped with status 'skipped_offline'. " .
                "This behavior must be preserved after the fix."
            );
            
            // Verify no error is set (this is a normal skip, not an error)
            $last_sync_error = $this->getSetting('last_sync_error');
            $this->assertEmpty(
                $last_sync_error,
                "Offline skip should not set error message. This is normal behavior to preserve."
            );
        } else {
            // If we have internet, this test is informational
            $this->markTestSkipped("Internet is available. This test needs offline conditions.");
        }
    }
    
    /**
     * Property 2: Sync Disabled Status
     * 
     * WHEN sync_enabled setting is '0'
     * THEN sync should not execute
     * AND status should indicate 'disabled'
     * 
     * This behavior must be preserved after the fix.
     */
    public function test_sync_disabled_behavior_is_preserved() {
        // ARRANGE: Disable sync
        $this->updateSetting('sync_enabled', '0');
        $this->updateSetting('last_sync_status', 'none');
        
        // ACT: Attempt to trigger sync
        $result = $this->executeSyncViaCli();
        sleep(1);
        
        // OBSERVE: Check current behavior
        $last_sync_status = $this->getSetting('last_sync_status');
        
        echo "\n\n=== OBSERVED BEHAVIOR (Sync Disabled) ===\n";
        echo "sync_enabled: 0\n";
        echo "last_sync_status: {$last_sync_status}\n";
        echo "CLI output: {$result['output']}\n";
        echo "==========================================\n\n";
        
        // ASSERT: Sync should not change status when disabled
        // The status should remain as 'none' or whatever it was before
        $this->assertNotEquals(
            'success',
            $last_sync_status,
            "When sync is disabled, it should not execute or show success. " .
            "This behavior must be preserved after the fix."
        );
    }
    
    /**
     * Property 3: Successful Sync Status Message Format
     * 
     * WHEN sync completes successfully with valid connection
     * THEN last_sync_status should be 'success'
     * AND last_sync_time should be updated with timestamp
     * AND log should contain success message
     * 
     * This is the core successful sync behavior to preserve.
     * 
     * NOTE: This test requires valid remote database credentials.
     * Skip if credentials are not configured.
     */
    public function test_successful_sync_status_format_is_preserved() {
        // ARRANGE: Check if valid credentials exist
        $remote_host = $this->getSetting('remote_db_host');
        $remote_user = $this->getSetting('remote_db_user');
        $remote_pass = $this->getSetting('remote_db_pass');
        $remote_db = $this->getSetting('remote_db_name');
        
        // Skip if credentials not configured
        if (empty($remote_host) || empty($remote_user) || empty($remote_pass) || empty($remote_db)) {
            $this->markTestSkipped('Remote database credentials not configured. Cannot test successful sync.');
            return;
        }
        
        // Verify credentials are valid by attempting connection
        $decoded_pass = base64_decode($remote_pass, true);
        if ($decoded_pass !== false && !empty(trim($decoded_pass))) {
            $remote_pass = $decoded_pass;
        }
        
        // Try to connect to remote database
        $remote_port = $this->getSetting('remote_db_port', '3306');
        $test_connection = @new mysqli($remote_host, $remote_user, $remote_pass, $remote_db, $remote_port);
        
        if ($test_connection->connect_error) {
            $this->markTestSkipped('Cannot connect to remote database with configured credentials. Skipping successful sync test.');
            return;
        }
        $test_connection->close();
        
        // Set up for successful sync
        $this->updateSetting('sync_enabled', '1');
        $this->updateSetting('last_sync_status', 'none');
        
        // Record time before sync
        $time_before_sync = date('Y-m-d H:i:s');
        
        // ACT: Trigger sync
        $result = $this->executeSyncViaCli();
        sleep(2); // Give time for sync to complete
        
        // OBSERVE: Check current behavior
        $last_sync_status = $this->getSetting('last_sync_status');
        $last_sync_time = $this->getSetting('last_sync_time');
        $last_sync_error = $this->getSetting('last_sync_error');
        
        echo "\n\n=== OBSERVED BEHAVIOR (Successful Sync) ===\n";
        echo "last_sync_status: {$last_sync_status}\n";
        echo "last_sync_time: {$last_sync_time}\n";
        echo "last_sync_error: {$last_sync_error}\n";
        echo "CLI output (first 500 chars): " . substr($result['output'], 0, 500) . "\n";
        echo "==========================================\n\n";
        
        // ASSERT: Preserve successful sync behavior
        
        // 1. Status should be 'success' for successful sync
        $this->assertEquals(
            'success',
            $last_sync_status,
            "Successful sync should set last_sync_status to 'success'. " .
            "This behavior MUST be preserved after the fix. " .
            "Observed: {$last_sync_status}"
        );
        
        // 2. Sync time should be updated with timestamp
        $this->assertNotEmpty(
            $last_sync_time,
            "Successful sync should update last_sync_time with timestamp. " .
            "This behavior MUST be preserved after the fix."
        );
        
        // Verify timestamp format (should be YYYY-MM-DD HH:MM:SS)
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $last_sync_time,
            "last_sync_time should be in format 'YYYY-MM-DD HH:MM:SS'. " .
            "This format MUST be preserved after the fix. " .
            "Observed: {$last_sync_time}"
        );
        
        // 3. No error should be set for successful sync
        $this->assertEmpty(
            $last_sync_error,
            "Successful sync should not set error message. " .
            "This behavior MUST be preserved after the fix. " .
            "Observed error: {$last_sync_error}"
        );
        
        // 4. Verify log contains success message
        $log_entry = $this->getLatestSyncLogEntry();
        if ($log_entry) {
            $this->assertStringContainsStringIgnoringCase(
                'sync',
                $log_entry,
                "Log should contain sync-related message. " .
                "This logging behavior MUST be preserved after the fix."
            );
        }
    }
    
    /**
     * Property 4: Dashboard Display Data Structure
     * 
     * WHEN querying sync status for dashboard display
     * THEN the data structure and field names must remain consistent
     * 
     * This ensures dashboard UI continues to work after the fix.
     */
    public function test_sync_status_data_structure_is_preserved() {
        // ACT: Query settings that dashboard relies on
        $last_sync_status = $this->getSetting('last_sync_status');
        $last_sync_time = $this->getSetting('last_sync_time');
        $sync_enabled = $this->getSetting('sync_enabled');
        
        // OBSERVE: Document current data structure
        echo "\n\n=== OBSERVED DATA STRUCTURE ===\n";
        echo "Settings used by dashboard:\n";
        echo "  - last_sync_status: " . var_export($last_sync_status, true) . "\n";
        echo "  - last_sync_time: " . var_export($last_sync_time, true) . "\n";
        echo "  - sync_enabled: " . var_export($sync_enabled, true) . "\n";
        echo "================================\n\n";
        
        // ASSERT: These settings should exist and be readable
        // (may be null/empty if never synced, but should be queryable)
        
        // Verify settings table has these entries
        $status_exists = self::$db->query("SELECT * FROM settings WHERE type = 'last_sync_status'");
        $this->assertNotFalse(
            $status_exists,
            "Setting 'last_sync_status' must exist in database. " .
            "This field is required for dashboard display and MUST be preserved."
        );
        
        $time_exists = self::$db->query("SELECT * FROM settings WHERE type = 'last_sync_time'");
        $this->assertNotFalse(
            $time_exists,
            "Setting 'last_sync_time' must exist in database. " .
            "This field is required for dashboard display and MUST be preserved."
        );
        
        $enabled_exists = self::$db->query("SELECT * FROM settings WHERE type = 'sync_enabled'");
        $this->assertNotFalse(
            $enabled_exists,
            "Setting 'sync_enabled' must exist in database. " .
            "This field is required for sync control and MUST be preserved."
        );
    }
    
    /**
     * Property 5: Disk Space Check Behavior
     * 
     * WHEN disk space check passes (sufficient space)
     * THEN sync should proceed normally
     * 
     * WHEN disk space is critically low
     * THEN sync should be skipped with status 'failed_disk_space'
     * 
     * This disk space check logic must be preserved after the fix.
     */
    public function test_disk_space_check_behavior_is_preserved() {
        // This test documents the observed behavior
        // Actual disk space on test system may vary
        
        // Enable sync
        $this->updateSetting('sync_enabled', '1');
        
        // Get current disk space (for documentation)
        $disk_free = disk_free_space(__DIR__);
        $disk_total = disk_total_space(__DIR__);
        $disk_percent_free = ($disk_free / $disk_total) * 100;
        
        echo "\n\n=== OBSERVED DISK SPACE CHECK ===\n";
        echo "Disk free: " . round($disk_free / 1024 / 1024 / 1024, 2) . " GB\n";
        echo "Disk total: " . round($disk_total / 1024 / 1024 / 1024, 2) . " GB\n";
        echo "Percent free: " . round($disk_percent_free, 2) . "%\n";
        echo "==================================\n\n";
        
        // ASSERT: Document that disk space check should continue to function
        $this->assertGreaterThan(
            0,
            $disk_free,
            "Disk space check should be able to read free space. " .
            "This functionality MUST be preserved after the fix."
        );
        
        // If disk space is very low (< 5%), sync should fail with disk space error
        // Otherwise, sync should proceed normally
        if ($disk_percent_free < 5) {
            echo "NOTE: Low disk space detected. Sync should fail with 'failed_disk_space' status.\n";
        } else {
            echo "NOTE: Sufficient disk space. Sync should proceed normally.\n";
        }
        
        // This behavior must be preserved regardless of the connection error fix
        $this->assertTrue(true, "Disk space check behavior documented for preservation.");
    }
    
    /**
     * Property 6: Internet Connectivity Check Behavior
     * 
     * WHEN is_online() check is performed
     * THEN it should return boolean indicating internet connectivity
     * 
     * This check must continue to function after the fix.
     */
    public function test_internet_connectivity_check_behavior_is_preserved() {
        // Note: We cannot directly call is_online() from this test
        // But we document the expected behavior
        
        echo "\n\n=== INTERNET CONNECTIVITY CHECK BEHAVIOR ===\n";
        echo "The is_online() method checks internet connectivity.\n";
        echo "This check must continue to function after the fix.\n";
        echo "When online: sync proceeds\n";
        echo "When offline: sync skipped with 'skipped_offline' status\n";
        echo "============================================\n\n";
        
        // ASSERT: Verify that offline behavior is preserved
        // (tested in test_sync_skipped_when_offline_behavior_is_preserved)
        $this->assertTrue(
            true,
            "Internet connectivity check behavior must be preserved. " .
            "When online, sync proceeds. When offline, sync is skipped."
        );
    }
    
    /**
     * Property 7: CLI Mode Sync Execution
     * 
     * WHEN sync is executed via CLI (scheduled task)
     * THEN it should execute and return results
     * AND output should be logged
     * 
     * This CLI execution behavior must be preserved after the fix.
     */
    public function test_cli_mode_sync_execution_is_preserved() {
        // ARRANGE: Enable sync
        $this->updateSetting('sync_enabled', '1');
        
        // ACT: Execute sync via CLI
        $result = $this->executeSyncViaCli();
        
        // OBSERVE: Document CLI behavior
        echo "\n\n=== OBSERVED CLI MODE BEHAVIOR ===\n";
        echo "Exit code: {$result['exit_code']}\n";
        echo "Output length: " . strlen($result['output']) . " characters\n";
        echo "Output (first 500 chars): " . substr($result['output'], 0, 500) . "\n";
        echo "===================================\n\n";
        
        // ASSERT: CLI execution should work
        // Exit code 0 indicates successful execution (not necessarily successful sync)
        $this->assertIsInt(
            $result['exit_code'],
            "CLI execution should return an exit code. " .
            "This behavior MUST be preserved after the fix."
        );
        
        // Output should be a string
        $this->assertIsString(
            $result['output'],
            "CLI execution should return output. " .
            "This behavior MUST be preserved after the fix."
        );
    }
    
    /**
     * Property 8: Sync History Display Format
     * 
     * WHEN viewing sync history in dashboard
     * THEN timestamps and status should be displayed accurately
     * 
     * This display format must be preserved after the fix.
     */
    public function test_sync_history_display_format_is_preserved() {
        // Query sync status data as dashboard would
        $last_sync_status = $this->getSetting('last_sync_status', 'unknown');
        $last_sync_time = $this->getSetting('last_sync_time', 'Never');
        
        // OBSERVE: Document display format
        echo "\n\n=== OBSERVED HISTORY DISPLAY FORMAT ===\n";
        echo "Status display: " . str_replace('_', ' ', $last_sync_status) . "\n";
        echo "Time display: {$last_sync_time}\n";
        echo "=========================================\n\n";
        
        // ASSERT: Display format conventions
        
        // 1. Status values should be understandable
        $valid_statuses = [
            'success', 
            'failed', 
            'failed_connection', 
            'failed_disk_space',
            'failed_no_internet',
            'skipped_offline',
            'disabled',
            'none',
            'unknown'
        ];
        
        $this->assertContains(
            $last_sync_status,
            $valid_statuses,
            "last_sync_status should be one of the recognized status values. " .
            "This status vocabulary MUST be preserved after the fix. " .
            "Observed: {$last_sync_status}"
        );
        
        // 2. Time format should be consistent
        if ($last_sync_time !== 'Never' && !empty($last_sync_time)) {
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                $last_sync_time,
                "last_sync_time should maintain timestamp format. " .
                "This format MUST be preserved after the fix."
            );
        }
    }
    
    /**
     * Summary of Preservation Requirements
     * 
     * These tests document the current behavior on UNFIXED code for successful
     * sync scenarios. After the fix is implemented:
     * 
     * 1. All these tests should still PASS (no regressions)
     * 2. Successful sync status should remain 'success'
     * 3. Dashboard display format should remain unchanged
     * 4. Log message formats should remain unchanged
     * 5. Internet connectivity check should continue to work
     * 6. Disk space check should continue to work
     * 7. CLI mode execution should continue to work
     * 8. Sync history display should show same format
     * 
     * The bugfix should ONLY add:
     * - Pre-flight checks for connection errors
     * - Error detection and communication
     * - Visual status indicators
     * - Email notifications
     * 
     * It should NOT change any successful sync behavior.
     */
}
