<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Credit System Integration Hooks
 * Automatically handles credit creation and management
 */
class Credit_hooks {
    
    private $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Credit_model');
    }
    
    /**
     * Hook: After payment is recorded
     * Automatically detect and process overpayments
     */
    public function after_payment_recorded($payment_data) {
        if (!isset($payment_data['invoice_id']) || !$payment_data['invoice_id']) {
            return; // No invoice, skip credit processing
        }
        
        // Process potential overpayment
        $result = $this->CI->Credit_model->process_overpayment($payment_data);
        
        if ($result['status'] === 'success' && isset($result['overpayment'])) {
            // Log the automatic credit creation
            log_message('info', "Automatic credit created: Student {$payment_data['student_id']}, Amount: {$result['overpayment']}");
        }
    }
    
    /**
     * Hook: After invoice is created
     * Automatically apply available credits
     */
    public function after_invoice_created($invoice_id) {
        $result = $this->CI->Credit_model->apply_credits_to_invoice($invoice_id);
        
        if ($result['status'] === 'success' && $result['credit_applied'] > 0) {
            log_message('info', "Credits auto-applied to invoice {$invoice_id}: {$result['credit_applied']}");
        }
    }
    
    /**
     * Hook: After invoice amount is modified
     * Recalculate credit applications
     */
    public function after_invoice_modified($invoice_id, $old_amount, $new_amount) {
        $this->CI->db->trans_start();
        
        // Get invoice details
        $invoice = $this->CI->db->get_where('invoice', ['invoice_id' => $invoice_id])->row();
        if (!$invoice) return;
        
        $amount_difference = $new_amount - $old_amount;
        
        if ($amount_difference > 0) {
            // Invoice amount increased - apply more credits if available
            $this->CI->Credit_model->apply_credits_to_invoice($invoice_id);
        } else {
            // Invoice amount decreased - may create overpayment credit
            $total_paid = $invoice->amount_paid + ($invoice->credit_applied ?? 0);
            if ($total_paid > $new_amount) {
                $overpayment = $total_paid - $new_amount;
                
                // Create credit for the overpayment
                $credit_data = [
                    'student_id' => $invoice->student_id,
                    'credit_amount' => $overpayment,
                    'source_invoice_code' => $invoice->invoice_code,
                    'created_by' => $this->CI->session->userdata('login_user_id'),
                    'notes' => "Credit from invoice modification - Invoice #{$invoice->invoice_code}"
                ];
                
                $this->CI->db->insert('student_credits', $credit_data);
                
                // Update invoice to reflect proper amounts
                $this->CI->db->where('invoice_id', $invoice_id)
                           ->update('invoice', [
                               'amount_paid' => min($invoice->amount_paid, $new_amount),
                               'credit_applied' => min($invoice->credit_applied ?? 0, $new_amount - $invoice->amount_paid),
                               'due' => max(0, $new_amount - $invoice->amount_paid - ($invoice->credit_applied ?? 0))
                           ]);
            }
        }
        
        $this->CI->db->trans_complete();
    }
    
    /**
     * Hook: After discount is applied/modified
     * Recalculate credit applications
     */
    public function after_discount_applied($invoice_id, $discount_amount) {
        // When discount is applied, invoice amount effectively decreases
        $invoice = $this->CI->db->get_where('invoice', ['invoice_id' => $invoice_id])->row();
        if (!$invoice) return;
        
        $effective_amount = $invoice->amount - $discount_amount;
        $total_paid = $invoice->amount_paid + ($invoice->credit_applied ?? 0);
        
        if ($total_paid > $effective_amount) {
            // Overpayment due to discount - create credit
            $overpayment = $total_paid - $effective_amount;
            
            $credit_data = [
                'student_id' => $invoice->student_id,
                'credit_amount' => $overpayment,
                'source_invoice_code' => $invoice->invoice_code,
                'created_by' => $this->CI->session->userdata('login_user_id'),
                'notes' => "Credit from discount application - Invoice #{$invoice->invoice_code}"
            ];
            
            $this->CI->db->insert('student_credits', $credit_data);
            
            // Update invoice
            $this->CI->db->where('invoice_id', $invoice_id)
                       ->update('invoice', [
                           'due' => 0,
                           'status' => 'paid'
                       ]);
        }
    }
    
    /**
     * Hook: Before invoice/payment deletion
     * Handle credit reversals
     */
    public function before_payment_deleted($payment_id) {
        // Get payment details
        $payment = $this->CI->db->get_where('payment', ['payment_id' => $payment_id])->row();
        if (!$payment) return;
        
        // Check if this payment created any credits
        $credits = $this->CI->db->get_where('student_credits', [
            'source_payment_id' => $payment_id,
            'status' => 'active'
        ])->result_array();
        
        foreach ($credits as $credit) {
            if ($credit['applied_amount'] > 0) {
                // Credit has been used - cannot delete payment
                throw new Exception("Cannot delete payment. Credit has been applied to other invoices.");
            } else {
                // Mark credit as cancelled
                $this->CI->db->where('credit_id', $credit['credit_id'])
                           ->update('student_credits', [
                               'status' => 'cancelled',
                               'notes' => $credit['notes'] . ' [CANCELLED - Source payment deleted]'
                           ]);
            }
        }
    }
}