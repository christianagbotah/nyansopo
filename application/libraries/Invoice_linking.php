<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_linking {
    
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Link payment to invoice
     */
    public function link_payment_to_invoice($payment_id, $invoice_id) {
        $payment = $this->CI->db->where('id', $payment_id)->get('payment')->row_array();
        $invoice = $this->CI->db->where('invoice_id', $invoice_id)->get('invoice')->row_array();
        
        if (!$payment || !$invoice) return false;
        
        // Create link
        $this->CI->db->insert('invoice_payment_links', [
            'invoice_id' => $invoice_id,
            'payment_id' => $payment_id,
            'amount' => $payment['amount'],
            'linked_at' => date('Y-m-d H:i:s'),
            'linked_by' => $this->CI->session->userdata('admin_id')
        ]);
        
        // Update invoice status
        $this->update_invoice_status($invoice_id);
        
        return true;
    }
    
    /**
     * Auto-link payment to invoices
     */
    public function auto_link_payment($payment_id) {
        $payment = $this->CI->db->where('id', $payment_id)->get('payment')->row_array();
        
        if (!$payment) return false;
        
        // Get unpaid invoices for this student
        $this->CI->db->where('student_id', $payment['student_id']);
        $this->CI->db->where('status', 'unpaid');
        $this->CI->db->order_by('due_date', 'ASC');
        $invoices = $this->CI->db->get('invoice')->result_array();
        
        $remaining_amount = $payment['amount'];
        $linked_count = 0;
        
        foreach ($invoices as $invoice) {
            if ($remaining_amount <= 0) break;
            
            $invoice_balance = $invoice['net_amount'] - $this->get_invoice_paid_amount($invoice['invoice_id']);
            
            if ($invoice_balance > 0) {
                $amount_to_apply = min($remaining_amount, $invoice_balance);
                
                $this->CI->db->insert('invoice_payment_links', [
                    'invoice_id' => $invoice['invoice_id'],
                    'payment_id' => $payment_id,
                    'amount' => $amount_to_apply,
                    'linked_at' => date('Y-m-d H:i:s'),
                    'linked_by' => $this->CI->session->userdata('admin_id')
                ]);
                
                $remaining_amount -= $amount_to_apply;
                $linked_count++;
                
                $this->update_invoice_status($invoice['invoice_id']);
            }
        }
        
        return $linked_count;
    }
    
    /**
     * Get invoice paid amount
     */
    public function get_invoice_paid_amount($invoice_id) {
        $result = $this->CI->db->select_sum('amount')
                              ->where('invoice_id', $invoice_id)
                              ->get('invoice_payment_links')
                              ->row();
        
        return $result->amount ?: 0;
    }
    
    /**
     * Update invoice status
     */
    private function update_invoice_status($invoice_id) {
        $invoice = $this->CI->db->where('invoice_id', $invoice_id)->get('invoice')->row_array();
        $paid_amount = $this->get_invoice_paid_amount($invoice_id);
        
        $status = 'unpaid';
        if ($paid_amount >= $invoice['net_amount']) {
            $status = 'paid';
        } elseif ($paid_amount > 0) {
            $status = 'partial';
        }
        
        // Use existing columns: amount_paid and due
        $this->CI->db->where('invoice_id', $invoice_id)->update('invoice', [
            'status' => $status,
            'amount_paid' => $paid_amount,
            'due' => $invoice['net_amount'] - $paid_amount
        ]);
    }
    
    /**
     * Get invoice payment history
     */
    public function get_invoice_payment_history($invoice_id) {
        $this->CI->db->select('ipl.*, p.payment_date, p.payment_method, p.reference_number');
        $this->CI->db->from('invoice_payment_links ipl');
        $this->CI->db->join('payment p', 'ipl.payment_id = p.id');
        $this->CI->db->where('ipl.invoice_id', $invoice_id);
        $this->CI->db->order_by('ipl.linked_at', 'DESC');
        
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Get student payment summary
     */
    public function get_student_payment_summary($student_id) {
        $this->CI->db->select('
            COUNT(DISTINCT i.invoice_id) as total_invoices,
            SUM(i.net_amount) as total_billed,
            SUM(i.amount_paid) as total_paid,
            SUM(i.due) as total_balance
        ');
        $this->CI->db->from('invoice i');
        $this->CI->db->where('i.student_id', $student_id);
        
        return $this->CI->db->get()->row_array();
    }
    
    /**
     * Unlink payment from invoice
     */
    public function unlink_payment($link_id) {
        $link = $this->CI->db->where('id', $link_id)->get('invoice_payment_links')->row_array();
        
        if (!$link) return false;
        
        $this->CI->db->where('id', $link_id)->delete('invoice_payment_links');
        $this->update_invoice_status($link['invoice_id']);
        
        return true;
    }
}
