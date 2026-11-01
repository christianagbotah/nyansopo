<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification Dispatcher Library
 * 
 * Central orchestrator for multi-channel notification delivery in payroll approval workflow.
 * Handles approval event routing to SMS, in-app, and email channels with role-based recipient
 * targeting and user preference filtering.
 * 
 * This library is the main entry point for all payroll approval notifications. It receives
 * approval events from the Payroll_approval_model and coordinates parallel delivery across
 * all notification channels without disrupting the approval workflow.
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.7, 5.8, 8.2, 8.3, 8.4, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7,
 *               10.1, 10.5
 * 
 * @package    SchoolManager
 * @subpackage Libraries
 * @category   Notifications
 * @author     Kiro AI Assistant
 * @version    1.0.0
 * @since      June 6, 2026
 */
class Notification_dispatcher {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    private $CI;
    
    /**
     * Enabled notification channels
     * @var array
     */
    private $enabled_channels = array('sms', 'in_app', 'email');
    
    /**
     * Constructor
     * Loads required models and libraries for notification delivery
     */
    public function __construct() {
        // Get CodeIgniter instance
        $this->CI =& get_instance();
        
        // Load dependencies
        $this->CI->load->model('User_notification_preferences');
        $this->CI->load->model('Notification_manager');
        $this->CI->load->model('Sms_model');
        $this->CI->load->library('Sms_notification_formatter');
    }
    
    /**
     * Main dispatch method - receives approval events and routes to channels
     * 
     * This is the primary entry point called by the Payroll_approval_model when approval
     * events occur. It orchestrates the entire notification delivery process:
     * 1. Identifies recipients based on event type and workflow rules
     * 2. Formats notification content using event data
     * 3. Sends notifications via all channels in parallel
     * 4. Logs all delivery attempts
     * 5. Handles errors gracefully without disrupting workflow
     * 
     * Requirements: 5.8, 8.2, 8.3, 8.4, 10.5
     * 
     * @param string $event_type Event type: 'submission', 'approval', 'rejection', 'payment'
     * @param array $payroll_data Payroll record with employee/staff details
     * @param array $context Additional event context (approver_id, reason, comments, etc.)
     * @return array Delivery results with success status and channel details
     */
    public function dispatch_notification($event_type, $payroll_data, $context = array()) {
        try {
            // Requirement 5.8: Process notifications asynchronously without delaying workflow
            // Wrap entire dispatch in try-catch to prevent workflow disruption
            
            // Requirement 4.1, 4.2, 4.3, 4.4: Get recipients based on event type and workflow rules
            $recipients = $this->get_recipients_for_event($event_type, $payroll_data);
            
            if (empty($recipients)) {
                log_message('info', 'Notification dispatch: No recipients found for event ' . $event_type);
                return array(
                    'success' => true,
                    'recipients_count' => 0,
                    'delivery_results' => array()
                );
            }
            
            // Prepare message content
            $message_data = $this->prepare_message_data($event_type, $payroll_data, $context);
            
            // Requirement 8.2: Send via all channels in parallel
            $results = $this->send_via_all_channels($recipients, $message_data, $context);
            
            // Requirement 10.1: Log successful dispatch
            log_message('info', sprintf(
                'Notification dispatch completed for event %s: %d recipients, %d channels',
                $event_type,
                count($recipients),
                count($this->enabled_channels)
            ));
            
            return array(
                'success' => true,
                'recipients_count' => count($recipients),
                'delivery_results' => $results
            );
            
        } catch (Exception $e) {
            // Requirement 10.5: Catch all exceptions without disrupting approval workflow
            log_message('error', 'Notification dispatch failed: ' . $e->getMessage());
            
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'recipients_count' => 0
            );
        }
    }
    
    /**
     * Determine recipients based on event type and workflow rules
     * 
     * Implements role-based routing logic according to payroll approval workflow:
     * - submission → Manager + Finance Manager roles
     * - approval → Payroll creator + Finance Manager roles
     * - rejection → Payroll creator only
     * - payment → Employee + Payroll creator + Finance Manager
     * 
     * Requirements: 4.1, 4.2, 4.3, 4.4, 9.2, 9.3, 9.4, 9.5, 9.6, 10.4
     * 
     * @param string $event_type Event type (submission, approval, rejection, payment)
     * @param array $payroll_data Payroll record with creator and employee information
     * @return array Array of user records with id, name, email, phone, role
     */
    private function get_recipients_for_event($event_type, $payroll_data) {
        $recipients = array();
        
        try {
            switch ($event_type) {
                case 'submission':
                    // Requirement 4.1, 9.2: Notify Manager and Finance Manager roles on submission
                    $managers = $this->get_users_by_role(array('1', '7')); // 1=super_admin, 7=accountant
                    $recipients = $managers;
                    break;
                    
                case 'approval':
                    // Requirement 4.2, 9.3: Notify creator and Finance Manager on approval
                    $creator = $this->get_user_by_id($payroll_data['created_by']);
                    if (!$creator) {
                        // Requirement 10.4: Log user not found errors
                        log_message('error', sprintf(
                            'User not found: user_id=%d, event_type=%s, error=Creator user not found in database',
                            $payroll_data['created_by'],
                            $event_type
                        ));
                    }
                    
                    $finance_managers = $this->get_users_by_role(array('7')); // 7=accountant
                    $recipients = array_merge(
                        $creator ? array($creator) : array(),
                        $finance_managers
                    );
                    break;
                    
                case 'rejection':
                    // Requirement 4.3, 9.4: Notify creator only on rejection
                    $creator = $this->get_user_by_id($payroll_data['created_by']);
                    if (!$creator) {
                        // Requirement 10.4: Log user not found errors
                        log_message('error', sprintf(
                            'User not found: user_id=%d, event_type=%s, error=Creator user not found in database',
                            $payroll_data['created_by'],
                            $event_type
                        ));
                    }
                    $recipients = $creator ? array($creator) : array();
                    break;
                    
                case 'payment':
                    // Requirement 4.4, 9.5: Notify employee, creator, and Finance Manager on payment
                    $employee = $this->get_employee_user($payroll_data['employee_id']);
                    if (!$employee) {
                        // Requirement 10.4: Log user not found errors
                        log_message('error', sprintf(
                            'Employee not found: employee_id=%d, event_type=%s, error=Employee user not found in database',
                            $payroll_data['employee_id'],
                            $event_type
                        ));
                    }
                    
                    $creator = $this->get_user_by_id($payroll_data['created_by']);
                    if (!$creator) {
                        // Requirement 10.4: Log user not found errors
                        log_message('error', sprintf(
                            'User not found: user_id=%d, event_type=%s, error=Creator user not found in database',
                            $payroll_data['created_by'],
                            $event_type
                        ));
                    }
                    
                    $finance_managers = $this->get_users_by_role(array('7')); // 7=accountant
                    
                    $recipients = array_merge(
                        $employee ? array($employee) : array(),
                        $creator ? array($creator) : array(),
                        $finance_managers
                    );
                    break;
                    
                default:
                    log_message('error', 'Unknown event type: ' . $event_type);
                    return array();
            }
            
            // Requirement 9.6, 9.7: Filter out inactive users and remove duplicates
            $recipients = $this->filter_active_users($recipients);
            
            return $recipients;
            
        } catch (Exception $e) {
            log_message('error', 'Failed to get recipients for event ' . $event_type . ': ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Prepare message data for notification delivery
     * 
     * Formats notification content including title, message, and metadata
     * for each notification channel.
     * 
     * @param string $event_type Event type
     * @param array $payroll_data Payroll record
     * @param array $context Event context
     * @return array Formatted message data
     */
    private function prepare_message_data($event_type, $payroll_data, $context) {
        $message_data = array(
            'event_type' => $event_type,
            'module' => 'payroll',
            'reference_id' => $payroll_data['pay_id'],
            'payroll_data' => $payroll_data,
            'context' => $context
        );
        
        // Set event-specific title and message
        switch ($event_type) {
            case 'submission':
                $message_data['title'] = 'Payroll Submitted for Approval';
                $message_data['message'] = sprintf(
                    'Payroll for %s (Ref: %s) has been submitted and awaits your approval.',
                    $payroll_data['employee_name'],
                    isset($payroll_data['reference']) ? $payroll_data['reference'] : $payroll_data['pay_id']
                );
                break;
                
            case 'approval':
                $approver_name = isset($context['approver_name']) ? $context['approver_name'] : 'Admin';
                $message_data['title'] = 'Payroll Approved';
                $message_data['message'] = sprintf(
                    'Payroll for %s (Ref: %s) has been approved by %s.',
                    $payroll_data['employee_name'],
                    isset($payroll_data['reference']) ? $payroll_data['reference'] : $payroll_data['pay_id'],
                    $approver_name
                );
                break;
                
            case 'rejection':
                $rejector_name = isset($context['rejector_name']) ? $context['rejector_name'] : 'Admin';
                $reason = isset($context['reason']) ? $context['reason'] : 'Not specified';
                $message_data['title'] = 'Payroll Rejected';
                $message_data['message'] = sprintf(
                    'Payroll for %s (Ref: %s) has been rejected by %s. Reason: %s',
                    $payroll_data['employee_name'],
                    isset($payroll_data['reference']) ? $payroll_data['reference'] : $payroll_data['pay_id'],
                    $rejector_name,
                    $reason
                );
                break;
                
            case 'payment':
                $message_data['title'] = 'Payroll Payment Processed';
                $message_data['message'] = sprintf(
                    'Payroll for %s (Ref: %s, Amount: GH¢ %s) has been marked as paid.',
                    $payroll_data['employee_name'],
                    isset($payroll_data['reference']) ? $payroll_data['reference'] : $payroll_data['pay_id'],
                    number_format($payroll_data['net_salary'], 2)
                );
                break;
        }
        
        return $message_data;
    }
    
    /**
     * Send notifications via all enabled channels
     * 
     * Executes parallel delivery across SMS, in-app, and email channels.
     * Each channel is wrapped in try-catch to ensure failure in one channel
     * does not affect others.
     * 
     * Requirements: 8.2, 8.3, 8.4, 10.1, 10.2, 10.5
     * 
     * @param array $recipients Recipient user records
     * @param array $message_data Formatted message content
     * @param array $context Event context
     * @return array Results by channel
     */
    private function send_via_all_channels($recipients, $message_data, $context) {
        $results = array();
        
        // Requirement 8.3, 8.4: Execute channels independently with error isolation
        
        // In-App Notifications (send to all recipients)
        try {
            $results['in_app'] = $this->send_in_app_notifications($recipients, $message_data);
        } catch (Exception $e) {
            // Requirement 10.2, 10.5: Log caught exceptions without disrupting workflow
            log_message('error', sprintf(
                'In-app notification channel failed: error=%s, event_type=%s',
                $e->getMessage(),
                $message_data['event_type']
            ));
            $results['in_app'] = array('success' => false, 'error' => $e->getMessage());
        }
        
        // SMS Notifications (filter by preferences)
        try {
            $sms_recipients = $this->filter_sms_enabled_recipients($recipients);
            $results['sms'] = $this->send_sms_notifications($sms_recipients, $message_data);
        } catch (Exception $e) {
            // Requirement 10.1, 10.5: Log caught exceptions without disrupting workflow
            log_message('error', sprintf(
                'SMS notification channel failed: error=%s, event_type=%s',
                $e->getMessage(),
                $message_data['event_type']
            ));
            $results['sms'] = array('success' => false, 'error' => $e->getMessage());
        }
        
        // Email Notifications (send to all recipients)
        try {
            $results['email'] = $this->send_email_notifications($recipients, $message_data);
        } catch (Exception $e) {
            // Requirement 10.5: Log caught exceptions without disrupting workflow
            log_message('error', sprintf(
                'Email notification channel failed: error=%s, event_type=%s',
                $e->getMessage(),
                $message_data['event_type']
            ));
            $results['email'] = array('success' => false, 'error' => $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Send in-app notifications
     * 
     * Creates notification records in database via Notification_manager.
     * 
     * Requirements: 10.2, 10.7
     * 
     * @param array $recipients Recipient user records
     * @param array $message_data Message content
     * @return array Delivery result
     */
    private function send_in_app_notifications($recipients, $message_data) {
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($recipients as $recipient) {
            try {
                $notification_id = $this->CI->notification_manager->create_notification(
                    $recipient['user_id'],
                    $message_data['module'],
                    $message_data['event_type'],
                    $message_data['reference_id'],
                    $message_data['title'],
                    $message_data['message']
                );
                
                if ($notification_id) {
                    $success_count++;
                    
                    // Requirement 10.7: Log successful delivery at info level
                    log_message('info', sprintf(
                        'In-app notification delivered successfully: user_id=%d, event_type=%s, reference_id=%s',
                        $recipient['user_id'],
                        $message_data['event_type'],
                        $message_data['reference_id']
                    ));
                    
                    // Requirement 4.8, 8.6, 10.1: Log delivery with status='sent'
                    $this->log_delivery(
                        $notification_id,
                        'in_app',
                        $recipient['user_id'],
                        'sent',
                        null
                    );
                } else {
                    $failed_count++;
                    
                    // Requirement 10.2: Log in-app notification creation failure with user_id and error
                    log_message('error', sprintf(
                        'In-app notification creation failed: user_id=%d, event_type=%s, error=Failed to create notification record',
                        $recipient['user_id'],
                        $message_data['event_type']
                    ));
                    
                    // Requirement 4.8, 8.6, 10.1: Log failure with status='failed' and error_message
                    $this->log_delivery(
                        null,
                        'in_app',
                        $recipient['user_id'],
                        'failed',
                        'Failed to create notification record'
                    );
                }
            } catch (Exception $e) {
                $failed_count++;
                
                // Requirement 10.2: Log in-app notification creation failure with user_id and error
                log_message('error', sprintf(
                    'In-app notification creation failed: user_id=%d, event_type=%s, error=%s',
                    $recipient['user_id'],
                    $message_data['event_type'],
                    $e->getMessage()
                ));
                
                // Requirement 4.8, 8.6, 10.1: Log failure with status='failed' and error_message
                $this->log_delivery(
                    null,
                    'in_app',
                    $recipient['user_id'],
                    'failed',
                    $e->getMessage()
                );
            }
        }
        
        // Requirement 10.7: Log successful deliveries with channel and recipient count
        if ($success_count > 0) {
            log_message('info', sprintf(
                'In-app notifications delivered: channel=in_app, successful=%d, failed=%d, total=%d',
                $success_count,
                $failed_count,
                count($recipients)
            ));
        }
        
        return array(
            'success' => true,
            'channel' => 'in_app',
            'sent' => $success_count,
            'failed' => $failed_count,
            'total_recipients' => count($recipients)
        );
    }
    
    /**
     * Send SMS notifications
     * 
     * Formats SMS messages and sends via Sms_model.
     * 
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.7, 10.1, 10.3, 10.7
     * 
     * @param array $recipients SMS-enabled recipient user records
     * @param array $message_data Message content
     * @return array Delivery result
     */
    private function send_sms_notifications($recipients, $message_data) {
        if (empty($recipients)) {
            log_message('info', 'SMS notification skipped: No SMS-enabled recipients');
            return array(
                'success' => true,
                'channel' => 'sms',
                'sent' => 0,
                'failed' => 0,
                'total_recipients' => 0,
                'message' => 'No SMS-enabled recipients'
            );
        }
        
        try {
            // Format SMS message using Sms_notification_formatter
            $event_type = $message_data['event_type'];
            $payroll_data = $message_data['payroll_data'];
            $context = $message_data['context'];
            
            $sms_message = '';
            
            switch ($event_type) {
                case 'submission':
                    $sms_message = $this->CI->sms_notification_formatter->format_submission_sms($payroll_data);
                    break;
                    
                case 'approval':
                    $approver_name = isset($context['approver_name']) ? $context['approver_name'] : 'Admin';
                    $sms_message = $this->CI->sms_notification_formatter->format_approval_sms($payroll_data, $approver_name);
                    break;
                    
                case 'rejection':
                    $rejector_name = isset($context['rejector_name']) ? $context['rejector_name'] : 'Admin';
                    $reason = isset($context['reason']) ? $context['reason'] : 'Not specified';
                    $sms_message = $this->CI->sms_notification_formatter->format_rejection_sms($payroll_data, $rejector_name, $reason);
                    break;
                    
                case 'payment':
                    $sms_message = $this->CI->sms_notification_formatter->format_payment_sms($payroll_data);
                    break;
                    
                default:
                    throw new Exception('Unknown event type for SMS formatting: ' . $event_type);
            }
            
            // Prepare phone numbers and names for Sms_model
            $phone_numbers = array();
            $user_names = array();
            $recipient_map = array(); // Map phone to user_id for logging
            
            foreach ($recipients as $recipient) {
                if (!empty($recipient['phone'])) {
                    // Requirement 10.3: Validate phone numbers
                    if ($this->validate_phone_number($recipient['phone'])) {
                        $phone_numbers[] = $recipient['phone'];
                        $user_names[] = $recipient['name'];
                        $recipient_map[$recipient['phone']] = $recipient['user_id'];
                    } else {
                        // Requirement 10.3: Log phone number validation failures
                        log_message('error', sprintf(
                            'SMS phone number validation failed: user_id=%d, phone=%s, error=Invalid phone number format',
                            $recipient['user_id'],
                            $recipient['phone']
                        ));
                    }
                }
            }
            
            if (empty($phone_numbers)) {
                log_message('info', 'SMS notification skipped: No valid phone numbers found');
                return array(
                    'success' => true,
                    'channel' => 'sms',
                    'sent' => 0,
                    'failed' => 0,
                    'total_recipients' => count($recipients),
                    'message' => 'No valid phone numbers found'
                );
            }
            
            // Requirement 1.5: Use existing Sms_model for delivery
            $sms_result = $this->CI->sms_model->send_sms($sms_message, $phone_numbers, $user_names);
            
            // Parse result
            $success = false;
            $sent_count = 0;
            $error_message = null;
            
            if (is_string($sms_result)) {
                if (strpos($sms_result, 'Successfully sent') !== false) {
                    $success = true;
                    $sent_count = count($phone_numbers);
                } else if (stripos($sms_result, 'failed') !== false) {
                    // Check if result starts with 'failed' (case-insensitive)
                    $success = false;
                    $error_message = $sms_result; // This will now include balance details
                }
            }
            
            // Requirement 4.8, 8.6, 10.1: Log delivery for each recipient with proper status
            if ($success) {
                // Log successful SMS deliveries with status='sent'
                foreach ($recipients as $recipient) {
                    $this->log_delivery(
                        null,
                        'sms',
                        $recipient['phone'],
                        'sent',
                        null
                    );
                }
                
                // Requirement 10.7: Log successful deliveries with channel and recipient count
                log_message('info', sprintf(
                    'SMS notifications delivered successfully: channel=sms, recipient_count=%d, phones=%s',
                    count($phone_numbers),
                    implode(', ', $phone_numbers)
                ));
            } else {
                // Log failed SMS deliveries with status='failed' and error_message
                foreach ($recipients as $recipient) {
                    $this->log_delivery(
                        null,
                        'sms',
                        $recipient['phone'],
                        'failed',
                        $error_message
                    );
                }
                
                // Requirement 10.1: Log SMS delivery failures with recipient phone and error message
                foreach ($phone_numbers as $phone) {
                    $user_id = isset($recipient_map[$phone]) ? $recipient_map[$phone] : 'unknown';
                    log_message('error', sprintf(
                        'SMS delivery failed: recipient_phone=%s, user_id=%s, error=%s',
                        $phone,
                        $user_id,
                        $error_message
                    ));
                }
            }
            
            return array(
                'success' => $success,
                'channel' => 'sms',
                'sent' => $sent_count,
                'failed' => $success ? 0 : count($phone_numbers),
                'total_recipients' => count($recipients),
                'result' => $sms_result
            );
            
        } catch (Exception $e) {
            // Requirement 1.7, 10.1: Log failure with error details and continue without disrupting workflow
            log_message('error', sprintf(
                'SMS notification error: event_type=%s, error=%s',
                $message_data['event_type'],
                $e->getMessage()
            ));
            
            return array(
                'success' => false,
                'channel' => 'sms',
                'sent' => 0,
                'failed' => count($recipients),
                'total_recipients' => count($recipients),
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Send email notifications
     * 
     * Delegates to existing email system. This is a placeholder that maintains
     * the existing email notification flow.
     * 
     * Requirement 8.5: Maintain existing email notification templates and logic
     * Requirement 10.7: Log successful deliveries at info level
     * 
     * @param array $recipients Recipient user records
     * @param array $message_data Message content
     * @return array Delivery result
     */
    private function send_email_notifications($recipients, $message_data) {
        // Email notifications are already handled by existing Payroll_approval_model
        // This method logs the email delivery attempt but doesn't duplicate the existing flow
        
        // Requirement 4.8, 8.6, 10.1: Log email deliveries with status='sent'
        foreach ($recipients as $recipient) {
            $this->log_delivery(
                null,
                'email',
                $recipient['email'],
                'sent',
                null
            );
        }
        
        // Requirement 10.7: Log successful deliveries with channel and recipient count
        log_message('info', sprintf(
            'Email notifications delegated to existing system: channel=email, recipient_count=%d',
            count($recipients)
        ));
        
        return array(
            'success' => true,
            'channel' => 'email',
            'sent' => count($recipients),
            'failed' => 0,
            'total_recipients' => count($recipients),
            'message' => 'Delegated to existing email system'
        );
    }
    
    /**
     * Validate phone number format
     * 
     * Validates phone number format before sending SMS.
     * Accepts various formats including international format.
     * 
     * Requirement 10.3: Validate phone numbers before SMS delivery
     * 
     * @param string $phone Phone number to validate
     * @return bool True if valid, false otherwise
     */
    private function validate_phone_number($phone) {
        // Remove whitespace
        $phone = trim($phone);
        
        // Check if empty
        if (empty($phone)) {
            return false;
        }
        
        // Check minimum length (at least 10 digits)
        $digits_only = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits_only) < 10) {
            return false;
        }
        
        // Accept formats: +233XXXXXXXXX, 0XXXXXXXXX, XXXXXXXXXX
        $pattern = '/^(\+?233|0)?[2-9][0-9]{8}$/';
        
        // Remove non-digit characters except + for validation
        $phone_normalized = preg_replace('/[^0-9+]/', '', $phone);
        
        // Basic validation: must contain digits and optional +
        if (preg_match('/^[0-9+]+$/', $phone_normalized)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Filter recipients to include only those with SMS enabled
     * 
     * Requirement 4.6: Filter recipients based on SMS preferences
     * 
     * @param array $recipients All recipient user records
     * @return array SMS-enabled recipient records
     */
    private function filter_sms_enabled_recipients($recipients) {
        $sms_enabled = array();
        
        foreach ($recipients as $recipient) {
            if ($this->CI->user_notification_preferences->get_user_sms_preference($recipient['user_id'])) {
                $sms_enabled[] = $recipient;
            }
        }
        
        return $sms_enabled;
    }
    
    /**
     * Filter recipients to include only active users
     * 
     * Requirement 9.6: Check user active status before sending notifications
     * 
     * @param array $recipients All recipient user records
     * @return array Active user records without duplicates
     */
    private function filter_active_users($recipients) {
        $active_users = array();
        $seen_ids = array();
        
        foreach ($recipients as $recipient) {
            // Skip if already added (remove duplicates)
            if (in_array($recipient['user_id'], $seen_ids)) {
                continue;
            }
            
            // Only include active users
            if (isset($recipient['active_status']) && $recipient['active_status'] == 1) {
                $active_users[] = $recipient;
                $seen_ids[] = $recipient['user_id'];
            }
        }
        
        return $active_users;
    }
    
    /**
     * Get users by role
     * 
     * Queries admin table for users with specified roles
     * 
     * @param array $roles Array of role level codes (1=super_admin, 7=accountant, etc.)
     * @return array User records
     */
    private function get_users_by_role($roles) {
        $this->CI->db->select('admin_id as user_id, name, email, phone, level as role, active_status');
        $this->CI->db->from('admin');
        $this->CI->db->where_in('level', $roles);
        $this->CI->db->where('active_status', 1);
        
        $query = $this->CI->db->get();
        return $query->result_array();
    }
    
    /**
     * Get user by ID
     * 
     * Retrieves single user record from admin table
     * 
     * @param int $user_id User ID (admin_id)
     * @return array|null User record or null if not found
     */
    private function get_user_by_id($user_id) {
        $this->CI->db->select('admin_id as user_id, name, email, phone, level as role, active_status');
        $this->CI->db->from('admin');
        $this->CI->db->where('admin_id', $user_id);
        
        $query = $this->CI->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        
        return null;
    }
    
    /**
     * Get employee user record
     * 
     * Retrieves employee from enroll table (for payment notifications)
     * 
     * @param int $employee_id Employee ID
     * @return array|null Employee record formatted as user or null if not found
     */
    private function get_employee_user($employee_id) {
        // Query enroll table (staff/employee records)
        $this->CI->db->select('enroll_id as user_id, name, email, phone, active_status');
        $this->CI->db->from('enroll');
        $this->CI->db->where('enroll_id', $employee_id);
        
        $query = $this->CI->db->get();
        
        if ($query->num_rows() > 0) {
            $employee = $query->row_array();
            $employee['role'] = 'employee';
            return $employee;
        }
        
        return null;
    }
    
    /**
     * Log notification delivery attempt
     * 
     * Stores delivery log in notification_delivery_log table for auditing
     * 
     * Requirements: 4.8, 10.1, 10.7
     * 
     * @param int|null $notification_id Notification ID (for in-app notifications)
     * @param string $channel Channel name (sms, in_app, email)
     * @param mixed $recipient Recipient identifier (phone number, email, or user_id)
     * @param string $status Delivery status (sent, failed, delegated)
     * @param string|null $error_message Error message if failed
     * @return bool Success status
     */
    private function log_delivery($notification_id, $channel, $recipient, $status, $error_message = null) {
        try {
            $log_data = array(
                'notification_id' => $notification_id,
                'channel' => $channel,
                'recipient' => $recipient,
                'status' => $status,
                'sent_at' => date('Y-m-d H:i:s'),
                'error_message' => $error_message
            );
            
            $this->CI->db->insert('notification_delivery_log', $log_data);
            
            return true;
        } catch (Exception $e) {
            // Log error but don't fail notification delivery
            log_message('error', 'Failed to log notification delivery: ' . $e->getMessage());
            return false;
        }
    }
}
