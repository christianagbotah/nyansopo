<?php

/**
 * Bug Condition Exploration Test for Silent Sync Failures
 * 
 * **Property 1: Bug Condition** - Silent Sync Failure Detection
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6
 * 
 * **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
 * **DO NOT attempt to fix the test or the code when it fails**
 * **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
 * 
 * **GOAL**: Surface counterexamples that demonstrate silent sync failures exist in production
 * 
 * This test simulates scenarios where `get_remote_db()` returns `false` due to:
 * - Wrong credentials (incorrect password)
 * - Network unreachable (blocked port, server down)
 * - Missing configuration
 * 
 * On UNFIXED code, we expect to observe:
 * - UI displays "Sync successful - 0 records synced" despite connection failure
 * - Dashboard shows green success status when it should show red error
 * - No error messages visible in sync dashboard UI
 * - Error messages only in server log files
 * 
 * On FIXED code, we expect:
 * - Pre-flight checks abort sync before it starts
 * - UI displays specific error messages (e.g., "Sync Failed: Cannot connect to remote database")
 * - Dashboard shows red status indicators
 * - last_sync_status is 'failed_connection' or 'failed_no_internet'
 * - Error details are visible in sync dashboard UI
 */

use PHPUnit\Framework\TestCase;

class Sync_server_bug_exploration_test extends TestCase {
    
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
            self::$db->query("UPDATE settings SET description = '{$value}' WHERE type = '{$setting}'");
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
     * Execute sync via CLI to simulate actual sync behavior
     * This avoids complex CI bootstrapping and directly tests the sync endpoint
     */
    private function executeSyncViaCli() {
        $php_path = 'php';
        $index_path = realpath(__DIR__ . '/../../../index.php');
        
        // Execute sync via CLI (simulates scheduled task)
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
     * Test Scenario 1: Wrong Credentials
     * 
     * WHEN remote database credentials are incorrect (wrong password)
     * THEN on UNFIXED code: UI shows "Sync successful - 0 records synced"
     * THEN on FIXED code: UI shows "Sync Failed: Cannot connect to remote database - check credentials"
     *                     AND last_sync_status is 'failed_connection'
     *                     AND error message visible in dashboard
     */
    public function test_sync_failure_with_wrong_credentials_is_properly_communicated() {
        // ARRANGE: Set up connection with intentionally wrong credentials
        $this->updateSetting('remote_db_host', 'localhost');
        $this->updateSetting('remote_db_port', '3306');
        $this->updateSetting('remote_db_user', 'root');
        $this->updateSetting('remote_db_pass', base64_encode('WRONG_PASSWORD_12345')); // Intentionally wrong
        $this->updateSetting('remote_db_name', 'test_db_nonexistent');
        $this->updateSetting('sync_enabled', '1');
        
        // Clear any previous sync status
        $this->updateSetting('last_sync_status', 'none');
        $this->updateSetting('last_sync_error', '');
        
        // ACT: Trigger sync operation via CLI
        $result = $this->executeSyncViaCli();
        
        // Wait a moment for database updates to complete
        sleep(1);
        
        // Read results from database (this is what the dashboard would see)
        $last_sync_status = $this->getSetting('last_sync_status');
        $last_sync_error = $this->getSetting('last_sync_error');
        
        // ASSERT: Expected behavior (will FAIL on unfixed code, PASS on fixed code)
        
        // 1. last_sync_status should indicate connection failure (not success)
        $this->assertContains(
            $last_sync_status,
            ['failed_connection', 'failed', 'failed_no_internet', 'error'],
            "last_sync_status should indicate connection failure, got: {$last_sync_status}. " .
            "COUNTEREXAMPLE: Status is '{$last_sync_status}' instead of failure state. " .
            "This confirms the bug - system doesn't properly track connection failures. " .
            "Dashboard will show 'Sync successful' because status indicates success."
        );
        
        // 2. Error message should be set and accessible to dashboard
        $this->assertNotEmpty(
            $last_sync_error,
            "last_sync_error should contain error details for dashboard display. " .
            "COUNTEREXAMPLE: Error message is empty. " .
            "This confirms the bug - error details are not stored for UI display. " .
            "Dashboard has no error information to show users."
        );
        
        // 3. Error message should mention connection or credentials
        if (!empty($last_sync_error)) {
            $this->assertMatchesRegularExpression(
                '/connect|credential|authentication|access denied|database/i',
                $last_sync_error,
                "Error message should mention connection/credentials issue. " .
                "COUNTEREXAMPLE: Error message is '{$last_sync_error}'. " .
                "This indicates error messages are not informative enough for users."
            );
        }
        
        // Document the counterexample found
        echo "\n\n=== COUNTEREXAMPLE FOUND (Wrong Credentials) ===\n";
        echo "last_sync_status: {$last_sync_status}\n";
        echo "last_sync_error: {$last_sync_error}\n";
        echo "CLI output: {$result['output']}\n";
        echo "Expected: last_sync_status='failed_connection', last_sync_error containing error details\n";
        echo "========================================\n\n";
    }
    /**
     * Test Scenario 2: Missing Configuration
     * 
     * WHEN remote database configuration is missing or incomplete
     * THEN on UNFIXED code: Sync proceeds and shows "Sync successful - 0 records synced"
     * THEN on FIXED code: Pre-flight checks detect missing config and abort with clear error
     */
    public function test_sync_failure_with_missing_configuration_is_properly_communicated() {
        // ARRANGE: Set up incomplete configuration (empty host)
        $this->updateSetting('remote_db_host', ''); // Missing host
        $this->updateSetting('remote_db_port', '3306');
        $this->updateSetting('remote_db_user', 'root');
        $this->updateSetting('remote_db_pass', base64_encode('some_password')); // Valid but host is empty
        $this->updateSetting('remote_db_name', 'test_db');
        $this->updateSetting('sync_enabled', '1');
        
        $this->updateSetting('last_sync_status', 'none');
        $this->updateSetting('last_sync_error', '');
        
        // ACT: Trigger sync
        $result = $this->executeSyncViaCli();
        sleep(1);
        
        // Read results
        $last_sync_status = $this->getSetting('last_sync_status');
        $last_sync_error = $this->getSetting('last_sync_error');
        
        // ASSERT: Expected behavior
        
        // 1. Sync should fail or skip (not succeed)
        // On unfixed code, this might return 'success' even with empty host
        $this->assertContains(
            $last_sync_status,
            ['failed', 'failed_connection', 'skipped', 'disabled', 'error'],
            "last_sync_status should indicate failure/skip when configuration is incomplete. " .
            "COUNTEREXAMPLE: Status is '{$last_sync_status}' despite missing host. " .
            "This demonstrates the bug - system doesn't validate configuration before sync."
        );
        
        // Document counterexample
        echo "\n\n=== COUNTEREXAMPLE FOUND (Missing Configuration) ===\n";
        echo "last_sync_status: {$last_sync_status}\n";
        echo "last_sync_error: {$last_sync_error}\n";
        echo "Expected: Configuration validation error before sync starts\n";
        echo "========================================\n\n";
    }
    
    
    /**
     * Test Scenario 3: Network Unreachable
     * 
     * WHEN remote database host is unreachable (wrong host, network issue)
     * THEN on UNFIXED code: Connection attempt times out, shows "Sync successful - 0 records"
     * THEN on FIXED code: Pre-flight check detects unreachable host, aborts with network error
     */
    public function test_sync_failure_with_unreachable_host_is_properly_communicated() {
        // ARRANGE: Set up connection to unreachable host
        $this->updateSetting('remote_db_host', '192.0.2.1'); // TEST-NET-1 (guaranteed unreachable)
        $this->updateSetting('remote_db_port', '3306');
        $this->updateSetting('remote_db_user', 'root');
        $this->updateSetting('remote_db_pass', base64_encode('password'));
        $this->updateSetting('remote_db_name', 'test_db');
        $this->updateSetting('sync_enabled', '1');
        
        $this->updateSetting('last_sync_status', 'none');
        $this->updateSetting('last_sync_error', '');
        
        // ACT: Trigger sync (this may take a few seconds due to connection timeout)
        $start_time = time();
        $result = $this->executeSyncViaCli();
        $duration = time() - $start_time;
        sleep(1);
        
        // Read results
        $last_sync_status = $this->getSetting('last_sync_status');
        $last_sync_error = $this->getSetting('last_sync_error');
        
        // ASSERT: Expected behavior
        
        // 1. Sync should fail
        $this->assertNotEquals(
            'success',
            $last_sync_status,
            "last_sync_status should not be 'success' when host is unreachable. " .
            "COUNTEREXAMPLE: Status is '{$last_sync_status}' with unreachable host. " .
            "Bug confirmed - no network connectivity validation."
        );
        
        // 2. Error should indicate network/connection issue
        $this->assertContains(
            $last_sync_status,
            ['failed_connection', 'failed', 'failed_no_internet', 'skipped_offline'],
            "last_sync_status should indicate connection failure, got: {$last_sync_status}. " .
            "COUNTEREXAMPLE: Status doesn't reflect network issue."
        );
        
        // Document counterexample
        echo "\n\n=== COUNTEREXAMPLE FOUND (Network Unreachable) ===\n";
        echo "last_sync_status: {$last_sync_status}\n";
        echo "last_sync_error: {$last_sync_error}\n";
        echo "Duration: {$duration}s\n";
        echo "Expected: Fast-fail with network error (< 10s)\n";
        echo "========================================\n\n";
    }
    
    /**
     * Test Scenario 4: Dashboard UI Error Visibility
     * 
     * WHEN sync fails due to connection error
     * THEN on UNFIXED code: Error messages only in log files, not in UI
     * THEN on FIXED code: Error messages visible in dashboard UI via last_sync_error setting
     * 
     * This test verifies that error details are stored in a way that the dashboard can display them
     */
    public function test_sync_errors_are_stored_for_dashboard_display() {
        // ARRANGE: Wrong credentials scenario
        $this->updateSetting('remote_db_host', 'localhost');
        $this->updateSetting('remote_db_port', '3306');
        $this->updateSetting('remote_db_user', 'root');
        $this->updateSetting('remote_db_pass', base64_encode('INTENTIONALLY_WRONG_PASSWORD'));
        $this->updateSetting('remote_db_name', 'test_db_nonexistent');
        $this->updateSetting('sync_enabled', '1');
        
        $this->updateSetting('last_sync_status', 'none');
        $this->updateSetting('last_sync_error', '');
        
        // ACT: Trigger sync
        $result = $this->executeSyncViaCli();
        sleep(1);
        
        // Read results
        $last_sync_status = $this->getSetting('last_sync_status');
        $last_sync_error = $this->getSetting('last_sync_error');
        
        // ASSERT: Error information should be accessible to dashboard
        
        // 1. last_sync_error setting should exist and contain error details
        $this->assertNotEmpty(
            $last_sync_error,
            "last_sync_error should be populated for dashboard display. " .
            "COUNTEREXAMPLE: last_sync_error is empty. " .
            "Bug confirmed: Errors are logged to files but not stored for UI display. " .
            "Dashboard will show 'Sync successful' because it has no error information."
        );
        
        // 2. Error should contain actionable information
        if (!empty($last_sync_error)) {
            $this->assertMatchesRegularExpression(
                '/check|verify|configure|credential|connect/i',
                $last_sync_error,
                "Error message should suggest corrective action. " .
                "COUNTEREXAMPLE: Error is '{$last_sync_error}'. " .
                "Bug: Error messages not user-friendly or actionable."
            );
        }
        
        // 3. last_sync_status should be failure state (not success)
        $this->assertNotEquals(
            'success',
            $last_sync_status,
            "last_sync_status should indicate failure for UI visual indicators. " .
            "COUNTEREXAMPLE: Status is '{$last_sync_status}'. " .
            "Bug: Dashboard will show green success indicator despite failure."
        );
        
        // 4. Verify dashboard would show error (simulate dashboard query)
        $dashboard_error_visible = !empty($last_sync_error) && 
                                   in_array($last_sync_status, ['failed', 'failed_connection', 'failed_no_internet', 'error']);
        
        $this->assertTrue(
            $dashboard_error_visible,
            "Dashboard should be able to display error based on settings. " .
            "COUNTEREXAMPLE: last_sync_error='{$last_sync_error}', last_sync_status='{$last_sync_status}'. " .
            "Bug: Dashboard cannot determine that sync failed and should show error."
        );
        
        // Document counterexample
        echo "\n\n=== COUNTEREXAMPLE FOUND (Dashboard Error Visibility) ===\n";
        echo "last_sync_status: {$last_sync_status}\n";
        echo "last_sync_error: {$last_sync_error}\n";
        echo "dashboard_error_visible: " . ($dashboard_error_visible ? 'YES' : 'NO') . "\n";
        echo "Expected: Error details accessible to dashboard for display\n";
        echo "========================================\n\n";
    }
    
    /**
     * Documentation of Expected Counterexamples on UNFIXED code:
     * 
     * This test suite will FAIL on unfixed code and produce these counterexamples:
     * 
     * 1. Wrong Credentials Test:
     *    - last_sync_status is 'success', 'skipped_offline', or 'none' instead of 'failed_connection'
     *    - last_sync_error is empty (no error details for UI)
     *    - System shows "Sync successful - 0 records synced" despite connection failure
     * 
     * 2. Missing Configuration Test:
     *    - Sync proceeds without validating configuration
     *    - last_sync_status doesn't indicate configuration error
     *    - No configuration error message stored
     * 
     * 3. Network Unreachable Test:
     *    - Connection timeout takes full duration (30+ seconds)
     *    - Eventually completes with 'success' or 'skipped_offline' status
     *    - No fast-fail pre-flight check
     *    - last_sync_status doesn't reflect network issue
     * 
     * 4. Dashboard UI Visibility Test:
     *    - last_sync_error setting is empty
     *    - Dashboard cannot display error information
     *    - Dashboard shows green success indicator despite failure
     *    - Only log files contain error details (not accessible to users)
     * 
     * These counterexamples demonstrate the bug: Sync failures are silent, with errors only in
     * log files and misleading "success" messages in the UI.
     * 
     * When the fix is implemented (Task 3), these same tests should PASS, confirming:
     * - Pre-flight checks detect failures before sync starts
     * - Proper error detection in sync methods
     * - Clear error messages in UI (via last_sync_error setting)
     * - Visual status indicators (via last_sync_status)
     * - Fast-fail behavior (< 10 seconds for connection issues)
     */
}
