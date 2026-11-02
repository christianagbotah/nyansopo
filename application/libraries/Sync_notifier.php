<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Notifier Library
 * 
 * Handles email notifications for sync failures and repeated failure detection.
 * Sends alerts to administrators when sync operations fail repeatedly.
 * 
 * @package    School Manager
 * @subpackage Libraries
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: 2.5, 2.7
 */
class Sync_notifier {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    private $CI;
    
    /**
     * Failure threshold for email notification
     * @var int
     */
    private $failure_threshold = 3;
    
    /**
     * Constructor
     * 
     * Loads required CodeIgniter resources
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->library('email');
    }
    
    /**
     * Notify sync failure via email
     * 
     * Sends email notification to administrators with error details,
     * failure count, timestamp, diagnostic information, and actionable steps.
     * 
     * @param array $error_data Error data containing:
     *   - error_message: The error message
     *   - error_type: Type of error (connection, authentication, network, no_internet)
     *   - timestamp: When the error occurred
     *   - failure_count: Number of consecutive failures
     *   - diagnostic_info: Additional diagnostic information
     * @return bool Success status
     */
    public function notify_sync_failure($error_data) {
        try {
            // Get admin emails
            $admin_emails = $this->get_admin_emails();
            
            if (empty($admin_emails)) {
                log_message('error', '[Sync Notifier] No administrator emails found for sync failure notification');
                return false;
            }
            
            // Check Do Not Disturb setting
            if ($this->is_do_not_disturb_enabled()) {
                log_message('info', '[Sync Notifier] Email notification suppressed - Do Not Disturb mode enabled');
                return false;
            }
            
            // Prepare email content
            $subject = 'URGENT: Sync Failure Alert - School Manager';
            $message = $this->build_email_body($error_data);
            
            // Configure email
            $this->CI->email->clear();
            $this->CI->email->from($this->get_system_email(), 'School Manager System');
            $this->CI->email->to($admin_emails);
            $this->CI->email->subject($subject);
            $this->CI->email->message($message);
            $this->CI->email->set_mailtype('html');
            
            // Send email
            $result = $this->CI->email->send();
            
            // Log notification attempt
            $this->log_notification([
                'sent_to' => implode(', ', $admin_emails),
                'subject' => $subject,
                'error_type' => $error_data['error_type'] ?? 'unknown',
                'failure_count' => $error_data['failure_count'] ?? 0,
                'success' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                log_message('info', '[Sync Notifier] Email notification sent successfully to ' . count($admin_emails) . ' administrators');
            } else {
                log_message('error', '[Sync Notifier] Failed to send email notification: ' . $this->CI->email->print_debugger());
            }
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', '[Sync Notifier] Exception sending email notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check for repeated failures
     * 
     * Detects 3+ consecutive sync failures and triggers email notification.
     * Returns the current failure count.
     * 
     * @return int Current failure count
     */
    public function check_repeated_failures() {
        $failure_count = $this->get_failure_count();
        
        if ($failure_count >= $this->failure_threshold) {
            log_message('warning', "[Sync Notifier] Repeated failures detected: {$failure_count} consecutive failures");
            return $failure_count;
        }
        
        return $failure_count;
    }
    
    /**
     * Track sync attempt
     * 
     * Records sync attempt result and updates failure counter.
     * Resets counter on success, increments on failure.
     * Triggers email notification when threshold is reached.
     * 
     * @param string $status Sync status ('success', 'failed', 'failed_connection', etc.)
     * @param array $error_data Optional error data if status is failed
     * @return void
     */
    public function track_sync_attempt($status, $error_data = []) {
        $is_success = (strpos($status, 'success') !== false || $status === 'synced');
        
        if ($is_success) {
            // Reset failure counter on success
            $this->reset_failure_count();
            log_message('info', '[Sync Notifier] Sync successful - failure counter reset');
        } else {
            // Increment failure counter
            $failure_count = $this->increment_failure_count();
            log_message('warning', "[Sync Notifier] Sync failed - failure count: {$failure_count}");
            
            // Trigger email notification if threshold reached
            if ($failure_count >= $this->failure_threshold) {
                // Prepare error data with failure count
                $notification_data = array_merge($error_data, [
                    'failure_count' => $failure_count,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                // Send notification
                $this->notify_sync_failure($notification_data);
            }
        }
    }
    
    /**
     * Get administrator emails
     * 
     * Retrieves email addresses of administrators who should receive
     * sync failure notifications.
     * 
     * @return array List of administrator email addresses
     */
    public function get_admin_emails() {
        try {
            // Query admin users (level < 4 = admin users)
            $admins = $this->CI->db
                ->select('email')
                ->from('admin')
                ->where('level <', 4)
                ->where('email IS NOT NULL')
                ->where('email !=', '')
                ->get()
                ->result_array();
            
            $emails = array_column($admins, 'email');
            
            // Filter out invalid emails
            $valid_emails = array_filter($emails, function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            
            return array_values($valid_emails);
            
        } catch (Exception $e) {
            log_message('error', '[Sync Notifier] Error fetching admin emails: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get current failure count
     * 
     * @return int Current failure count
     */
    private function get_failure_count() {
        $setting = $this->CI->db
            ->get_where('settings', ['type' => 'sync_failure_count'])
            ->row();
        
        return $setting ? (int)$setting->description : 0;
    }
    
    /**
     * Increment failure count
     * 
     * @return int New failure count
     */
    private function increment_failure_count() {
        $current_count = $this->get_failure_count();
        $new_count = $current_count + 1;
        
        $this->update_setting('sync_failure_count', $new_count);
        
        return $new_count;
    }
    
    /**
     * Reset failure count to zero
     * 
     * @return void
     */
    private function reset_failure_count() {
        $this->update_setting('sync_failure_count', 0);
    }
    
    /**
     * Update setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success status
     */
    private function update_setting($key, $value) {
        $exists = $this->CI->db
            ->get_where('settings', ['type' => $key])
            ->row();
        
        if ($exists) {
            return $this->CI->db
                ->where('type', $key)
                ->update('settings', ['description' => $value]);
        } else {
            return $this->CI->db->insert('settings', [
                'type' => $key,
                'description' => $value
            ]);
        }
    }
    
    /**
     * Build email body HTML
     * 
     * @param array $error_data Error data
     * @return string HTML email body
     */
    private function build_email_body($error_data) {
        $error_message = $error_data['error_message'] ?? 'Unknown error';
        $error_type = $error_data['error_type'] ?? 'Unknown';
        $failure_count = $error_data['failure_count'] ?? 0;
        $timestamp = $error_data['timestamp'] ?? date('Y-m-d H:i:s');
        $diagnostic_info = $error_data['diagnostic_info'] ?? [];
        
        // Build diagnostic information HTML
        $diagnostic_html = '';
        if (!empty($diagnostic_info)) {
            $diagnostic_html = '<ul>';
            foreach ($diagnostic_info as $key => $value) {
                $diagnostic_html .= '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</li>';
            }
            $diagnostic_html .= '</ul>';
        }
        
        // Get corrective action based on error type
        $corrective_action = $this->get_corrective_action($error_type);
        
        // Get sync dashboard URL
        $dashboard_url = site_url('sync_server/sync_dashboard');
        
        // Build email HTML
        $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #d9534f; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; }
        .error-box { background-color: #fff; border-left: 4px solid #d9534f; padding: 15px; margin: 15px 0; }
        .info-box { background-color: #fff; border-left: 4px solid #5bc0de; padding: 15px; margin: 15px 0; }
        .action-box { background-color: #fcf8e3; border: 1px solid #faebcc; padding: 15px; margin: 15px 0; }
        .button { display: inline-block; padding: 10px 20px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 3px; margin: 10px 0; }
        .footer { text-align: center; padding: 15px; font-size: 12px; color: #666; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">⚠ URGENT: Sync Failure Alert</h1>
            <p style="margin: 10px 0 0 0;">School Manager System</p>
        </div>
        <div class="content">
            <p><strong>Dear Administrator,</strong></p>
            
            <p>The School Manager sync system has encountered <strong>' . $failure_count . ' consecutive failures</strong>. Immediate attention is required.</p>
            
            <div class="error-box">
                <h3 style="margin-top: 0; color: #d9534f;">Error Details</h3>
                <p><strong>Error Type:</strong> ' . htmlspecialchars($error_type) . '</p>
                <p><strong>Error Message:</strong> ' . htmlspecialchars($error_message) . '</p>
                <p><strong>Timestamp:</strong> ' . htmlspecialchars($timestamp) . '</p>
                <p><strong>Consecutive Failures:</strong> ' . $failure_count . '</p>
            </div>
            
            ' . (!empty($diagnostic_html) ? '
            <div class="info-box">
                <h3 style="margin-top: 0; color: #5bc0de;">Diagnostic Information</h3>
                ' . $diagnostic_html . '
            </div>
            ' : '') . '
            
            <div class="action-box">
                <h3 style="margin-top: 0; color: #8a6d3b;">Recommended Actions</h3>
                ' . $corrective_action . '
            </div>
            
            <p style="text-align: center;">
                <a href="' . $dashboard_url . '" class="button">View Sync Dashboard</a>
            </p>
            
            <p><strong>Important:</strong> Until this issue is resolved, data synchronization between your local and remote servers is not functioning. This may result in data inconsistencies between locations.</p>
            
            <p>If you need assistance, please contact technical support with the error details above.</p>
        </div>
        <div class="footer">
            <p>This is an automated notification from the School Manager System.<br>
            Please do not reply to this email.</p>
            <p><em>Generated: ' . date('Y-m-d H:i:s') . '</em></p>
        </div>
    </div>
</body>
</html>
';
        
        return $html;
    }
    
    /**
     * Get corrective action HTML based on error type
     * 
     * @param string $error_type Error type
     * @return string HTML with corrective action steps
     */
    private function get_corrective_action($error_type) {
        $actions = [
            'connection' => '<ol>
                <li>Verify remote database credentials in sync settings</li>
                <li>Check if the remote database server is running</li>
                <li>Verify network connectivity to the remote server</li>
                <li>Check firewall settings to ensure port 3306 is accessible</li>
            </ol>',
            
            'authentication' => '<ol>
                <li>Verify the database username and password are correct</li>
                <li>Check if the database user has proper permissions</li>
                <li>Ensure the user is allowed to connect from your IP address</li>
                <li>Reset database credentials if necessary</li>
            </ol>',
            
            'network' => '<ol>
                <li>Check your internet connection</li>
                <li>Verify the remote server hostname or IP address</li>
                <li>Check for network firewall or router issues</li>
                <li>Contact your network administrator if problems persist</li>
            </ol>',
            
            'no_internet' => '<ol>
                <li>Check your internet connection</li>
                <li>Verify your router is online and functioning</li>
                <li>Check if other internet services are working</li>
                <li>Contact your internet service provider if necessary</li>
            </ol>',
            
            'system' => '<ol>
                <li>Check available disk space on the server</li>
                <li>Verify sync table integrity in the database</li>
                <li>Check file system permissions</li>
                <li>Review system logs for additional error details</li>
            </ol>'
        ];
        
        return $actions[$error_type] ?? '<ol>
            <li>Review the sync dashboard for detailed error information</li>
            <li>Check system logs for additional error details</li>
            <li>Contact technical support with the error details above</li>
        </ol>';
    }
    
    /**
     * Log notification attempt
     * 
     * @param array $log_data Notification log data
     * @return void
     */
    private function log_notification($log_data) {
        try {
            // Store in settings table as JSON for audit trail
            $log_entry = json_encode($log_data);
            
            // Get existing notification log
            $setting = $this->CI->db
                ->get_where('settings', ['type' => 'sync_notification_log'])
                ->row();
            
            if ($setting) {
                $existing_log = json_decode($setting->description, true) ?? [];
            } else {
                $existing_log = [];
            }
            
            // Add new entry (keep last 50 entries)
            array_unshift($existing_log, $log_data);
            $existing_log = array_slice($existing_log, 0, 50);
            
            // Update setting
            $this->update_setting('sync_notification_log', json_encode($existing_log));
            
        } catch (Exception $e) {
            log_message('error', '[Sync Notifier] Error logging notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if Do Not Disturb mode is enabled
     * 
     * @return bool True if DND is enabled
     */
    private function is_do_not_disturb_enabled() {
        $setting = $this->CI->db
            ->get_where('settings', ['type' => 'sync_notification_dnd'])
            ->row();
        
        return $setting && ($setting->description === '1' || $setting->description === 1);
    }
    
    /**
     * Get system email address
     * 
     * @return string System email address
     */
    private function get_system_email() {
        $setting = $this->CI->db
            ->get_where('settings', ['type' => 'system_email'])
            ->row();
        
        return $setting ? $setting->description : 'noreply@schoolmanager.com';
    }
}
