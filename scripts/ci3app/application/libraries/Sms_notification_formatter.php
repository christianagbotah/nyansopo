<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SMS Notification Formatter Library
 * 
 * Formats payroll approval event data into concise SMS messages that stay
 * within the 160-character limit for single-page SMS delivery.
 * All messages include system identification prefix and essential information.
 * 
 * @package    SchoolManager
 * @subpackage Libraries
 * @category   Notifications
 * @author     Kiro AI Assistant
 * @version    1.0.0
 * @since      June 6, 2026
 */
class Sms_notification_formatter {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    private $CI;
    
    /**
     * System name prefix for all SMS messages (fetched from database)
     * @var string
     */
    private $system_name;
    
    /**
     * Maximum SMS length for single-page delivery
     * @var int
     */
    private $max_length = 160;
    
    /**
     * Constructor
     * Loads CodeIgniter instance and fetches system name from database
     */
    public function __construct() {
        // Get CodeIgniter instance
        $this->CI =& get_instance();
        
        // Fetch system name from database settings
        // Fallback to 'SchoolManager' if not set
        $system_name_setting = $this->CI->db->get_where('settings', array('type' => 'system_name'));
        
        if ($system_name_setting && $system_name_setting->num_rows() > 0) {
            $this->system_name = $system_name_setting->row()->description;
        } else {
            $this->system_name = 'SchoolManager'; // Fallback
        }
    }
    
    /**
     * Format submission notification SMS
     * 
     * Creates message when payroll is submitted for approval.
     * Includes: payroll reference, employee name, month/year, amount
     * 
     * @param array $payroll_data Payroll record with employee details
     * @return string SMS message (<= 160 chars)
     */
    public function format_submission_sms($payroll_data) {
        // Validate required fields
        if (!isset($payroll_data['employee_name'], $payroll_data['reference'], 
                   $payroll_data['net_salary'], $payroll_data['month'], $payroll_data['year'])) {
            throw new InvalidArgumentException('Missing required payroll data fields for submission SMS');
        }
        
        // Format currency with thousand separators
        $amount = $this->format_currency($payroll_data['net_salary']);
        
        // Build message
        $message = sprintf(
            "%s: Payroll submitted for %s. Ref: %s, Amt: %s for %s %s. Awaiting approval.",
            $this->system_name,
            $payroll_data['employee_name'],
            $payroll_data['reference'],
            $amount,
            $payroll_data['month'],
            $payroll_data['year']
        );
        
        return $this->truncate_to_limit($message);
    }
    
    /**
     * Format approval notification SMS
     * 
     * Creates message when payroll is approved.
     * Includes: approval confirmation, payroll reference, employee name, approver name
     * 
     * @param array $payroll_data Payroll record with employee details
     * @param string $approver_name Full name of approver
     * @return string SMS message (<= 160 chars)
     */
    public function format_approval_sms($payroll_data, $approver_name) {
        // Validate required fields
        if (!isset($payroll_data['employee_name'], $payroll_data['reference'], $payroll_data['net_salary'])) {
            throw new InvalidArgumentException('Missing required payroll data fields for approval SMS');
        }
        
        if (empty($approver_name)) {
            throw new InvalidArgumentException('Approver name is required for approval SMS');
        }
        
        // Format currency
        $amount = $this->format_currency($payroll_data['net_salary']);
        
        // Build message
        $message = sprintf(
            "%s: Payroll APPROVED for %s. Ref: %s, Amt: %s. Approved by %s.",
            $this->system_name,
            $payroll_data['employee_name'],
            $payroll_data['reference'],
            $amount,
            $approver_name
        );
        
        return $this->truncate_to_limit($message);
    }
    
    /**
     * Format rejection notification SMS
     * 
     * Creates message when payroll is rejected.
     * Includes: rejection confirmation, payroll reference, employee name, rejector name, reason (truncated)
     * 
     * @param array $payroll_data Payroll record with employee details
     * @param string $rejector_name Full name of rejector
     * @param string $reason Rejection reason (will be truncated to fit)
     * @return string SMS message (<= 160 chars)
     */
    public function format_rejection_sms($payroll_data, $rejector_name, $reason) {
        // Validate required fields
        if (!isset($payroll_data['employee_name'], $payroll_data['reference'])) {
            throw new InvalidArgumentException('Missing required payroll data fields for rejection SMS');
        }
        
        if (empty($rejector_name)) {
            throw new InvalidArgumentException('Rejector name is required for rejection SMS');
        }
        
        if (empty($reason)) {
            $reason = 'Not specified';
        }
        
        // Truncate reason to maximum 50 characters as per requirements
        $reason_truncated = $this->truncate_string($reason, 50);
        
        // Build message
        $message = sprintf(
            "%s: Payroll REJECTED for %s. Ref: %s. Reason: %s. Rejected by %s.",
            $this->system_name,
            $payroll_data['employee_name'],
            $payroll_data['reference'],
            $reason_truncated,
            $rejector_name
        );
        
        return $this->truncate_to_limit($message);
    }
    
    /**
     * Format payment notification SMS
     * 
     * Creates message when payroll is marked as paid.
     * Includes: payment confirmation, payroll reference, employee name, amount
     * 
     * @param array $payroll_data Payroll record with employee details
     * @return string SMS message (<= 160 chars)
     */
    public function format_payment_sms($payroll_data) {
        // Validate required fields
        if (!isset($payroll_data['employee_name'], $payroll_data['reference'], 
                   $payroll_data['net_salary'], $payroll_data['month'], $payroll_data['year'])) {
            throw new InvalidArgumentException('Missing required payroll data fields for payment SMS');
        }
        
        // Format currency
        $amount = $this->format_currency($payroll_data['net_salary']);
        
        // Build message
        $message = sprintf(
            "%s: Payroll PAID for %s. Ref: %s, Amt: %s for %s %s. Check your account.",
            $this->system_name,
            $payroll_data['employee_name'],
            $payroll_data['reference'],
            $amount,
            $payroll_data['month'],
            $payroll_data['year']
        );
        
        return $this->truncate_to_limit($message);
    }
    
    /**
     * Format currency amount with GH¢ symbol and thousand separators
     * 
     * @param float $amount Amount to format
     * @return string Formatted currency string (e.g., "GH¢ 1,234.56")
     */
    private function format_currency($amount) {
        // Convert to float to ensure proper formatting
        $amount = floatval($amount);
        
        // Format with thousand separators and 2 decimal places
        return 'GH¢ ' . number_format($amount, 2);
    }
    
    /**
     * Truncate message to SMS length limit
     * 
     * Ensures message stays within 160-character single-page SMS limit.
     * Adds ellipsis (...) if truncation occurs.
     * 
     * @param string $message Message text
     * @return string Truncated message (<= 160 chars)
     */
    private function truncate_to_limit($message) {
        // Check if message is within limit
        if (strlen($message) <= $this->max_length) {
            return $message;
        }
        
        // Truncate to max_length - 3 to make room for ellipsis
        return substr($message, 0, $this->max_length - 3) . '...';
    }
    
    /**
     * Truncate string with ellipsis
     * 
     * Helper method for truncating individual fields (e.g., rejection reason)
     * 
     * @param string $string Input string
     * @param int $max_length Maximum length
     * @return string Truncated string
     */
    private function truncate_string($string, $max_length) {
        // Check if string is within limit
        if (strlen($string) <= $max_length) {
            return $string;
        }
        
        // Truncate to max_length - 3 to make room for ellipsis
        return substr($string, 0, $max_length - 3) . '...';
    }
}
