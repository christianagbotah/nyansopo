<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Email_notification {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->library('email');
        $this->CI->load->database();
    }
    
    /**
     * Send payment received notification
     */
    public function payment_received($payment_data) {
        $student = $this->get_student($payment_data['student_id']);
        $parent = $this->get_parent($student['parent_id']);
        
        $subject = 'Payment Received - ' . get_settings('system_name');
        $message = $this->load_template('payment_received', [
            'student_name' => $student['name'],
            'amount' => $payment_data['amount'],
            'payment_date' => $payment_data['date'],
            'receipt_number' => $payment_data['receipt_number'],
            'payment_method' => $payment_data['method']
        ]);
        
        return $this->send_email($parent['email'], $subject, $message);
    }
    
    /**
     * Send invoice overdue notification
     */
    public function invoice_overdue($invoice_data) {
        $student = $this->get_student($invoice_data['student_id']);
        $parent = $this->get_parent($student['parent_id']);
        
        $subject = 'Invoice Overdue - ' . get_settings('system_name');
        $message = $this->load_template('invoice_overdue', [
            'student_name' => $student['name'],
            'invoice_number' => $invoice_data['invoice_code'],
            'amount' => $invoice_data['amount'],
            'due_date' => $invoice_data['due_date'],
            'days_overdue' => $invoice_data['days_overdue']
        ]);
        
        return $this->send_email($parent['email'], $subject, $message);
    }
    
    /**
     * Send receipt generated notification
     */
    public function receipt_generated($receipt_data) {
        $student = $this->get_student($receipt_data['student_id']);
        $parent = $this->get_parent($student['parent_id']);
        
        $subject = 'Receipt Generated - ' . get_settings('system_name');
        $message = $this->load_template('receipt_generated', [
            'student_name' => $student['name'],
            'receipt_number' => $receipt_data['receipt_number'],
            'amount' => $receipt_data['amount'],
            'date' => $receipt_data['date']
        ]);
        
        return $this->send_email($parent['email'], $subject, $message);
    }
    
    /**
     * Send expense approved notification
     */
    public function expense_approved($expense_data) {
        $requester = $this->get_admin($expense_data['requested_by']);
        
        $subject = 'Expense Approved - ' . get_settings('system_name');
        $message = $this->load_template('expense_approved', [
            'description' => $expense_data['description'],
            'amount' => $expense_data['amount'],
            'approved_by' => $expense_data['approved_by_name'],
            'approved_date' => $expense_data['approved_at']
        ]);
        
        return $this->send_email($requester['email'], $subject, $message);
    }
    
    /**
     * Send expense rejected notification
     */
    public function expense_rejected($expense_data) {
        $requester = $this->get_admin($expense_data['requested_by']);
        
        $subject = 'Expense Rejected - ' . get_settings('system_name');
        $message = $this->load_template('expense_rejected', [
            'description' => $expense_data['description'],
            'amount' => $expense_data['amount'],
            'rejected_by' => $expense_data['approved_by_name'],
            'rejection_reason' => $expense_data['rejection_reason']
        ]);
        
        return $this->send_email($requester['email'], $subject, $message);
    }
    
    /**
     * Load email template
     */
    private function load_template($template_name, $data) {
        $template_path = APPPATH . 'views/email_templates/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            return $this->default_template($data);
        }
        
        ob_start();
        extract($data);
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Default email template
     */
    private function default_template($data) {
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $html .= '<div style="background: #4CAF50; color: white; padding: 20px; text-align: center;">';
        $html .= '<h2>' . get_settings('system_name') . '</h2>';
        $html .= '</div>';
        $html .= '<div style="padding: 20px; background: #f9f9f9;">';
        
        foreach ($data as $key => $value) {
            $html .= '<p><strong>' . ucwords(str_replace('_', ' ', $key)) . ':</strong> ' . $value . '</p>';
        }
        
        $html .= '</div>';
        $html .= '<div style="background: #333; color: white; padding: 10px; text-align: center; font-size: 12px;">';
        $html .= '&copy; ' . date('Y') . ' ' . get_settings('system_name');
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Send email
     */
    private function send_email($to, $subject, $message) {
        $this->CI->email->from(get_settings('system_email'), get_settings('system_name'));
        $this->CI->email->to($to);
        $this->CI->email->subject($subject);
        $this->CI->email->message($message);
        
        $sent = $this->CI->email->send();
        
        // Log email
        $this->log_email($to, $subject, $sent);
        
        return $sent;
    }
    
    /**
     * Log email
     */
    private function log_email($to, $subject, $status) {
        $this->CI->db->insert('email_logs', [
            'recipient' => $to,
            'subject' => $subject,
            'status' => $status ? 'sent' : 'failed',
            'sent_at' => date('Y-m-d H:i:s')
        ]);
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
    
    /**
     * Get admin data
     */
    private function get_admin($admin_id) {
        return $this->CI->db->where('admin_id', $admin_id)->get('admin')->row_array();
    }
}
