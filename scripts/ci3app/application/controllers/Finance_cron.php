<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Finance Cron Jobs Controller
 * Run automated tasks for finance module
 * 
 * Setup: Add to crontab
 * 0 8 * * * curl https://yourschool.com/finance_cron/daily_tasks
 */

class Finance_cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Finance_cron_model');
        
        // Security: Only allow CLI or specific IP
        if (!$this->input->is_cli_request() && !$this->is_allowed_ip()) {
            show_404();
        }
    }

    private function is_allowed_ip() {
        $allowed_ips = ['127.0.0.1', '::1']; // Add your server IPs
        return in_array($this->input->ip_address(), $allowed_ips);
    }

    // ==================== DAILY TASKS ====================
    public function daily_tasks() {
        log_message('info', 'Finance Cron: Daily tasks started');
        
        $this->check_overdue_invoices();
        $this->send_payment_reminders();
        $this->update_installment_status();
        $this->clear_old_cache();
        
        log_message('info', 'Finance Cron: Daily tasks completed');
        echo "Daily tasks completed successfully\n";
    }

    // ==================== CHECK OVERDUE INVOICES ====================
    private function check_overdue_invoices() {
        $settings = $this->db->get('late_payment_settings')->row_array();
        $grace_period = $settings['grace_period_days'];
        
        $overdue_date = date('Y-m-d', strtotime("-{$grace_period} days"));
        
        $invoices = $this->db->select('i.*, s.name, s.parent_email')
            ->from('invoice i')
            ->join('student s', 's.student_id = i.student_id')
            ->where('i.status', 'due')
            ->where('i.due >', 0)
            ->where('DATE(FROM_UNIXTIME(i.creation_timestamp)) <=', $overdue_date)
            ->get()->result_array();
        
        foreach ($invoices as $invoice) {
            $late_fee = $this->calculate_late_fee($invoice, $settings);
            
            if ($late_fee > 0) {
                $this->db->where('invoice_id', $invoice['invoice_id'])->update('invoice', [
                    'due' => $invoice['due'] + $late_fee,
                    'amount' => $invoice['amount'] + $late_fee
                ]);
                
                log_message('info', "Late fee applied: Invoice {$invoice['invoice_code']}, Amount: {$late_fee}");
            }
        }
        
        echo "Checked " . count($invoices) . " overdue invoices\n";
    }

    private function calculate_late_fee($invoice, $settings) {
        $days_overdue = (time() - $invoice['creation_timestamp']) / 86400;
        
        if ($days_overdue <= $settings['grace_period_days']) {
            return 0;
        }
        
        if ($settings['late_fee_type'] == 'fixed') {
            return $settings['late_fee_value'];
        } else {
            return ($invoice['due'] * $settings['late_fee_value']) / 100;
        }
    }

    // ==================== SEND PAYMENT REMINDERS ====================
    private function send_payment_reminders() {
        $settings = $this->db->get('late_payment_settings')->row_array();
        $reminder_days = explode(',', $settings['reminder_days']);
        
        $sent_count = 0;
        
        foreach ($reminder_days as $days) {
            $target_date = date('Y-m-d', strtotime("-{$days} days"));
            
            $invoices = $this->db->select('i.*, s.name, s.parent_email, s.phone')
                ->from('invoice i')
                ->join('student s', 's.student_id = i.student_id')
                ->where('i.status', 'due')
                ->where('i.due >', 0)
                ->where('DATE(FROM_UNIXTIME(i.creation_timestamp))', $target_date)
                ->get()->result_array();
            
            foreach ($invoices as $invoice) {
                $this->send_reminder_email($invoice);
                $this->send_reminder_sms($invoice);
                $sent_count++;
            }
        }
        
        echo "Sent {$sent_count} payment reminders\n";
    }

    private function send_reminder_email($invoice) {
        $this->load->library('email');
        
        $currency = get_settings('currency');
        $school_name = get_settings('system_name');
        
        $message = "
            <h3>Payment Reminder</h3>
            <p>Dear Parent/Guardian,</p>
            <p>This is a friendly reminder that the following invoice is due:</p>
            <ul>
                <li><strong>Student:</strong> {$invoice['name']}</li>
                <li><strong>Invoice:</strong> {$invoice['invoice_code']}</li>
                <li><strong>Amount Due:</strong> {$currency}{$invoice['due']}</li>
            </ul>
            <p>Please make payment at your earliest convenience.</p>
            <p>Thank you,<br>{$school_name}</p>
        ";
        
        $this->email->from(get_settings('system_email'), $school_name);
        $this->email->to($invoice['parent_email']);
        $this->email->subject('Payment Reminder - ' . $invoice['invoice_code']);
        $this->email->message($message);
        
        if ($this->email->send()) {
            log_message('info', "Reminder sent: {$invoice['invoice_code']} to {$invoice['parent_email']}");
        }
    }

    private function send_reminder_sms($invoice) {
        // Implement SMS gateway integration
        // Example: Twilio, Africa's Talking, etc.
        $phone = $invoice['phone'];
        $message = "Payment reminder: Invoice {$invoice['invoice_code']} - Amount due: " . get_settings('currency') . $invoice['due'];
        
        // SMS API call here
        log_message('info', "SMS reminder queued for {$phone}");
    }

    // ==================== UPDATE INSTALLMENT STATUS ====================
    private function update_installment_status() {
        $today = date('Y-m-d');
        
        // Mark overdue installments
        $this->db->where('due_date <', $today)
            ->where('status', 'pending')
            ->update('payment_installments', ['status' => 'overdue']);
        
        // Check for completed payment plans
        $plans = $this->db->where('status', 'active')->get('payment_plans')->result_array();
        
        foreach ($plans as $plan) {
            $pending = $this->db->where('plan_id', $plan['plan_id'])
                ->where_in('status', ['pending', 'overdue'])
                ->count_all_results('payment_installments');
            
            if ($pending == 0) {
                $this->db->where('plan_id', $plan['plan_id'])->update('payment_plans', ['status' => 'completed']);
            }
        }
        
        echo "Updated installment statuses\n";
    }

    // ==================== CLEAR OLD CACHE ====================
    private function clear_old_cache() {
        $cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $this->db->where('updated_at <', $cutoff)->delete('finance_dashboard_cache');
        echo "Cleared old cache entries\n";
    }

    // ==================== MONTHLY REPORTS ====================
    public function monthly_reports() {
        log_message('info', 'Finance Cron: Monthly reports started');
        
        $this->generate_monthly_summary();
        $this->send_admin_report();
        
        log_message('info', 'Finance Cron: Monthly reports completed');
        echo "Monthly reports generated\n";
    }

    private function generate_monthly_summary() {
        $year = get_settings('running_year');
        $term = get_settings('running_term');
        $month = date('Y-m');
        
        $summary = $this->db->select('
            SUM(amount) as total_collected,
            COUNT(*) as transaction_count,
            COUNT(DISTINCT student_id) as unique_students
        ')
        ->where('year', $year)
        ->where('term', $term)
        ->where('DATE_FORMAT(FROM_UNIXTIME(day_timestamp), "%Y-%m")', $month)
        ->get('payment')->row_array();
        
        log_message('info', "Monthly summary: " . json_encode($summary));
    }

    private function send_admin_report() {
        // Send monthly financial report to admin
        $this->load->library('email');
        
        $report = $this->Finance_cron_model->generate_admin_report();
        
        $this->email->from(get_settings('system_email'), get_settings('system_name'));
        $this->email->to(get_settings('admin_email'));
        $this->email->subject('Monthly Financial Report - ' . date('F Y'));
        $this->email->message($report);
        $this->email->send();
    }

    // ==================== BACKUP ====================
    public function backup_financial_data() {
        log_message('info', 'Finance Cron: Backup started');
        
        $this->load->dbutil();
        
        $tables = [
            'invoice', 'payment', 'receipts', 'payment_plans',
            'payment_installments', 'credit_notes', 'payment_transactions'
        ];
        
        $backup = $this->dbutil->backup(['tables' => $tables]);
        
        $filename = 'finance_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $path = FCPATH . 'backups/finance/';
        
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        
        write_file($path . $filename, $backup);
        
        log_message('info', "Finance backup created: {$filename}");
        echo "Backup created: {$filename}\n";
    }

    // ==================== TEST CRON ====================
    public function test() {
        echo "Finance Cron is working!\n";
        echo "Current time: " . date('Y-m-d H:i:s') . "\n";
        echo "PHP version: " . PHP_VERSION . "\n";
    }
}
