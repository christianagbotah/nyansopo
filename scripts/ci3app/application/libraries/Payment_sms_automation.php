<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_sms_automation {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->library('sms_sender');
    }
    
    /**
     * Send payment confirmation SMS
     */
    public function payment_received($payment_data) {
        $student = $this->get_student($payment_data['student_id']);
        $parent = $this->get_parent($student['parent_id']);
        
        $message = "Payment received for {$student['name']}. Amount: GHS {$payment_data['amount']}. Receipt: {$payment_data['receipt_number']}. Thank you! - " . get_settings('system_name');
        
        return $this->send_sms($parent['phone'], $message, 'payment_confirmation');
    }
    
    /**
     * Send payment reminder SMS
     */
    public function payment_reminder($invoice_data) {
        $student = $this->get_student($invoice_data['student_id']);
        $parent = $this->get_parent($student['parent_id']);
        
        $message = "Reminder: Invoice {$invoice_data['invoice_code']} for {$student['name']} is due on {$invoice_data['due_date']}. Amount: GHS {$invoice_data['amount']}. - " . get_settings('system_name');
        
        return $this->send_sms($parent['phone'], $message, 'payment_reminder');
    }
    
    /**
     * Send overdue payment SMS
     */
    public function payment_overdue($invoice_data) {
        $student = $this->get_student($invoice_data['student_id']);
        $parent = $this->get_parent($student['parent_id']);
        
        $message = "OVERDUE: Invoice {$invoice_data['invoice_code']} for {$student['name']} is {$invoice_data['days_overdue']} days overdue. Amount: GHS {$invoice_data['amount']}. Please pay immediately. - " . get_settings('system_name');
        
        return $this->send_sms($parent['phone'], $message, 'payment_overdue');
    }
    
    /**
     * Send bulk payment reminders
     */
    public function send_bulk_reminders() {
        // Get all unpaid invoices due in next 7 days
        $this->CI->db->select('i.*, s.name as student_name, s.parent_id');
        $this->CI->db->from('invoice i');
        $this->CI->db->join('student s', 'i.student_id = s.student_id');
        $this->CI->db->where('i.status', 'unpaid');
        $this->CI->db->where('i.due_date >=', date('Y-m-d'));
        $this->CI->db->where('i.due_date <=', date('Y-m-d', strtotime('+7 days')));
        
        $invoices = $this->CI->db->get()->result_array();
        
        $sent = 0;
        foreach ($invoices as $invoice) {
            if ($this->payment_reminder($invoice)) {
                $sent++;
            }
        }
        
        return $sent;
    }
    
    /**
     * Send bulk overdue notices
     */
    public function send_bulk_overdue_notices() {
        // Get all overdue invoices
        $this->CI->db->select('i.*, s.name as student_name, s.parent_id, 
                              DATEDIFF(CURDATE(), i.due_date) as days_overdue');
        $this->CI->db->from('invoice i');
        $this->CI->db->join('student s', 'i.student_id = s.student_id');
        $this->CI->db->where('i.status', 'unpaid');
        $this->CI->db->where('i.due_date <', date('Y-m-d'));
        
        $invoices = $this->CI->db->get()->result_array();
        
        $sent = 0;
        foreach ($invoices as $invoice) {
            if ($this->payment_overdue($invoice)) {
                $sent++;
            }
        }
        
        return $sent;
    }
    
    /**
     * Auto-schedule SMS reminders
     */
    public function schedule_auto_reminders() {
        // Schedule reminder 3 days before due date
        $this->CI->db->insert('sms_automations', [
            'trigger_event' => 'invoice_due_soon',
            'trigger_days' => 3,
            'recipient_type' => 'parent',
            'message_template' => 'Payment reminder: Invoice {invoice_code} for {student_name} is due on {due_date}. Amount: GHS {amount}.',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Schedule overdue notice 1 day after due date
        $this->CI->db->insert('sms_automations', [
            'trigger_event' => 'invoice_overdue',
            'trigger_days' => -1,
            'recipient_type' => 'parent',
            'message_template' => 'OVERDUE: Invoice {invoice_code} for {student_name} is overdue. Amount: GHS {amount}. Please pay immediately.',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Send SMS
     */
    private function send_sms($phone, $message, $type) {
        $result = $this->CI->sms_sender->send($phone, $message);
        
        // Log SMS
        $this->CI->db->insert('sms_logs', [
            'phone' => $phone,
            'message' => $message,
            'type' => $type,
            'status' => $result ? 'sent' : 'failed',
            'sent_at' => date('Y-m-d H:i:s')
        ]);
        
        return $result;
    }
    
    /**
     * Get student data
     */
    private function get_student($student_id) {
        return $this->CI->db->where('student_id', $student_id)->get('student')->row_array();
    }
    
    /**
     * Get parent data
     */
    private function get_parent($parent_id) {
        return $this->CI->db->where('parent_id', $parent_id)->get('parent')->row_array();
    }
}
