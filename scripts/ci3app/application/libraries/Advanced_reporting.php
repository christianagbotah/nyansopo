<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Advanced_reporting {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Financial Summary Report
     */
    public function financial_summary($start_date, $end_date) {
        return [
            'revenue' => $this->get_revenue_summary($start_date, $end_date),
            'expenses' => $this->get_expense_summary($start_date, $end_date),
            'collections' => $this->get_collection_summary($start_date, $end_date),
            'outstanding' => $this->get_outstanding_summary(),
            'budget_performance' => $this->get_budget_performance()
        ];
    }
    
    /**
     * Revenue Summary
     */
    private function get_revenue_summary($start_date, $end_date) {
        $this->CI->db->select('
            COUNT(*) as total_invoices,
            SUM(net_amount) as total_billed,
            SUM(amount_paid) as total_collected,
            SUM(due) as total_outstanding
        ');
        $this->CI->db->where('creation_timestamp >=', $start_date);
        $this->CI->db->where('creation_timestamp <=', $end_date);
        
        return $this->CI->db->get('invoice')->row_array();
    }
    
    /**
     * Expense Summary
     */
    private function get_expense_summary($start_date, $end_date) {
        $this->CI->db->select('
            COUNT(*) as total_expenses,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending_amount
        ');
        $this->CI->db->where('expense_date >=', $start_date);
        $this->CI->db->where('expense_date <=', $end_date);
        
        return $this->CI->db->get('expenses_enhanced')->row_array();
    }
    
    /**
     * Collection Summary
     */
    private function get_collection_summary($start_date, $end_date) {
        $this->CI->db->select('
            COUNT(*) as total_payments,
            SUM(amount) as total_amount,
            AVG(amount) as average_payment
        ');
        $this->CI->db->where('timestamp >=', $start_date);
        $this->CI->db->where('timestamp <=', $end_date);
        
        return $this->CI->db->get('payment')->row_array();
    }
    
    /**
     * Outstanding Summary
     */
    private function get_outstanding_summary() {
        $this->CI->db->select('
            COUNT(*) as total_invoices,
            SUM(due) as total_outstanding,
            SUM(CASE WHEN due_date < CURDATE() THEN due ELSE 0 END) as overdue_amount,
            COUNT(CASE WHEN due_date < CURDATE() THEN 1 END) as overdue_count
        ');
        $this->CI->db->where('status !=', 'paid');
        
        return $this->CI->db->get('invoice')->row_array();
    }
    
    /**
     * Budget Performance
     */
    private function get_budget_performance() {
        $this->CI->db->select('
            COUNT(*) as total_lines,
            SUM(allocated_amount) as total_budget,
            SUM(utilized_amount) as total_utilized,
            AVG(utilization_percentage) as avg_utilization
        ');
        $this->CI->db->join('budgets b', 'budget_lines.budget_id = b.id');
        $this->CI->db->where('b.status', 'active');
        
        return $this->CI->db->get('budget_lines')->row_array();
    }
    
    /**
     * Cash Flow Report
     */
    public function cash_flow_report($start_date, $end_date, $interval = 'month') {
        $format = $interval == 'month' ? '%Y-%m' : '%Y-%m-%d';
        
        // Inflows (Payments)
        $this->CI->db->select("DATE_FORMAT(timestamp, '$format') as period, SUM(amount) as inflow");
        $this->CI->db->where('timestamp >=', $start_date);
        $this->CI->db->where('timestamp <=', $end_date);
        $this->CI->db->group_by('period');
        $this->CI->db->order_by('period', 'ASC');
        $inflows = $this->CI->db->get('payment')->result_array();
        
        // Outflows (Expenses)
        $this->CI->db->select("DATE_FORMAT(expense_date, '$format') as period, SUM(amount) as outflow");
        $this->CI->db->where('expense_date >=', $start_date);
        $this->CI->db->where('expense_date <=', $end_date);
        $this->CI->db->where('status', 'approved');
        $this->CI->db->group_by('period');
        $this->CI->db->order_by('period', 'ASC');
        $outflows = $this->CI->db->get('expenses_enhanced')->result_array();
        
        // Merge data
        $cash_flow = [];
        foreach ($inflows as $in) {
            $cash_flow[$in['period']]['period'] = $in['period'];
            $cash_flow[$in['period']]['inflow'] = $in['inflow'];
            $cash_flow[$in['period']]['outflow'] = 0;
        }
        
        foreach ($outflows as $out) {
            if (!isset($cash_flow[$out['period']])) {
                $cash_flow[$out['period']]['period'] = $out['period'];
                $cash_flow[$out['period']]['inflow'] = 0;
            }
            $cash_flow[$out['period']]['outflow'] = $out['outflow'];
        }
        
        // Calculate net flow
        foreach ($cash_flow as &$flow) {
            $flow['net_flow'] = $flow['inflow'] - $flow['outflow'];
        }
        
        return array_values($cash_flow);
    }
    
    /**
     * Aging Report
     */
    public function aging_report() {
        $this->CI->db->select('
            s.name as student_name,
            s.student_code,
            c.name as class_name,
            i.invoice_code,
            i.net_amount,
            i.amount_paid,
            i.due,
            i.due_date,
            DATEDIFF(CURDATE(), i.due_date) as days_overdue
        ');
        $this->CI->db->from('invoice i');
        $this->CI->db->join('student s', 'i.student_id = s.student_id');
        $this->CI->db->join('class c', 's.class_id = c.class_id');
        $this->CI->db->where('i.status !=', 'paid');
        $this->CI->db->where('i.due_date <', date('Y-m-d'));
        $this->CI->db->order_by('days_overdue', 'DESC');
        
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Category-wise Expense Report
     */
    public function category_expense_report($start_date, $end_date) {
        $this->CI->db->select('
            ec.name as category,
            COUNT(e.id) as transaction_count,
            SUM(e.amount) as total_amount,
            AVG(e.amount) as average_amount,
            MIN(e.amount) as min_amount,
            MAX(e.amount) as max_amount
        ');
        $this->CI->db->from('expenses_enhanced e');
        $this->CI->db->join('expense_categories_enhanced ec', 'e.category_id = ec.id');
        $this->CI->db->where('e.expense_date >=', $start_date);
        $this->CI->db->where('e.expense_date <=', $end_date);
        $this->CI->db->where('e.status', 'approved');
        $this->CI->db->group_by('e.category_id');
        $this->CI->db->order_by('total_amount', 'DESC');
        
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Payment Method Analysis
     */
    public function payment_method_analysis($start_date, $end_date) {
        $this->CI->db->select('
            method as payment_method,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount
        ');
        $this->CI->db->where('timestamp >=', $start_date);
        $this->CI->db->where('timestamp <=', $end_date);
        $this->CI->db->group_by('method');
        $this->CI->db->order_by('total_amount', 'DESC');
        
        return $this->CI->db->get('payment')->result_array();
    }
    
    /**
     * Student Financial Profile
     */
    public function student_financial_profile($student_id) {
        return [
            'summary' => $this->get_student_summary($student_id),
            'invoices' => $this->get_student_invoices($student_id),
            'payments' => $this->get_student_payments($student_id),
            'payment_history' => $this->get_student_payment_trend($student_id)
        ];
    }
    
    private function get_student_summary($student_id) {
        $this->CI->db->select('
            COUNT(DISTINCT i.invoice_id) as total_invoices,
            SUM(i.net_amount) as total_billed,
            SUM(i.amount_paid) as total_paid,
            SUM(i.due) as total_outstanding
        ');
        $this->CI->db->from('invoice i');
        $this->CI->db->where('i.student_id', $student_id);
        
        return $this->CI->db->get()->row_array();
    }
    
    private function get_student_invoices($student_id) {
        $this->CI->db->where('student_id', $student_id);
        $this->CI->db->order_by('creation_timestamp', 'DESC');
        return $this->CI->db->get('invoice')->result_array();
    }
    
    private function get_student_payments($student_id) {
        $this->CI->db->where('student_id', $student_id);
        $this->CI->db->order_by('timestamp', 'DESC');
        return $this->CI->db->get('payment')->result_array();
    }
    
    private function get_student_payment_trend($student_id) {
        $this->CI->db->select('DATE_FORMAT(timestamp, "%Y-%m") as month, SUM(amount) as total');
        $this->CI->db->where('student_id', $student_id);
        $this->CI->db->group_by('month');
        $this->CI->db->order_by('month', 'ASC');
        return $this->CI->db->get('payment')->result_array();
    }
    
    /**
     * Export Report to Excel
     */
    public function export_report($report_type, $data, $filename) {
        $this->CI->load->helper('export');
        
        $headers = $this->get_report_headers($report_type);
        $rows = $this->format_report_data($report_type, $data);
        
        export_to_excel($rows, $filename, $headers);
    }
    
    private function get_report_headers($report_type) {
        $headers = [
            'aging' => ['Student', 'Code', 'Class', 'Invoice', 'Amount', 'Paid', 'Due', 'Due Date', 'Days Overdue'],
            'cash_flow' => ['Period', 'Inflow', 'Outflow', 'Net Flow'],
            'category_expense' => ['Category', 'Count', 'Total', 'Average', 'Min', 'Max']
        ];
        
        return $headers[$report_type] ?? [];
    }
    
    private function format_report_data($report_type, $data) {
        $rows = [];
        foreach ($data as $row) {
            $rows[] = array_values($row);
        }
        return $rows;
    }
}
