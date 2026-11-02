<?php
/**
 * ADD THESE METHODS TO Admin.php CONTROLLER
 * These integrate the credit system with existing payment/invoice operations
 */

/**
 * Enhanced payment recording with automatic credit processing
 * Replace the existing student_payment_create method
 */
public function student_payment_create() {
    $this->db->trans_start();
    
    // Original payment data
    $payment_data = array(
        'invoice_id' => $this->input->post('invoice_id'),
        'student_id' => $this->input->post('student_id'),
        'class_id' => $this->input->post('class_id'),
        'payment_type' => $this->input->post('payment_type'),
        'method' => $this->input->post('method'),
        'description' => $this->input->post('description'),
        'amount' => $this->input->post('amount'),
        'timestamp' => strtotime($this->input->post('date')),
        'year' => $this->input->post('year'),
        'receipt_code' => $this->generate_receipt_code()
    );
    
    // Insert payment
    $this->db->insert('payment', $payment_data);
    $payment_id = $this->db->insert_id();
    $payment_data['payment_id'] = $payment_id;
    
    // Get invoice details for credit processing
    $invoice = $this->db->get_where('invoice', ['invoice_id' => $payment_data['invoice_id']])->row();
    
    if ($invoice) {
        // Calculate new totals
        $new_amount_paid = $invoice->amount_paid + $payment_data['amount'];
        $new_due = $invoice->amount - $new_amount_paid - ($invoice->credit_applied ?? 0);
        
        if ($new_due <= 0) {
            // Payment covers invoice completely
            $actual_payment = $payment_data['amount'] + $new_due; // Reduce payment to exact amount needed
            $overpayment = $payment_data['amount'] - $actual_payment;
            
            // Update invoice - mark as fully paid
            $this->db->where('invoice_id', $payment_data['invoice_id'])
                     ->update('invoice', [
                         'amount_paid' => $invoice->amount - ($invoice->credit_applied ?? 0),
                         'due' => 0,
                         'status' => 'paid'
                     ]);
            
            // Process overpayment if any
            if ($overpayment > 0) {
                $this->load->model('Credit_model');
                $credit_result = $this->Credit_model->process_overpayment($payment_data);
                
                if ($credit_result['status'] === 'success') {
                    $message = "Payment recorded successfully. Overpayment of GH₵ " . number_format($overpayment, 2) . " credited to student account.";
                } else {
                    $message = "Payment recorded successfully.";
                }
            } else {
                $message = "Payment recorded successfully.";
            }
        } else {
            // Partial payment
            $this->db->where('invoice_id', $payment_data['invoice_id'])
                     ->update('invoice', [
                         'amount_paid' => $new_amount_paid,
                         'due' => $new_due,
                         'status' => 'partial'
                     ]);
            
            $message = "Payment recorded successfully.";
        }
    }
    
    $this->db->trans_complete();
    
    if ($this->db->trans_status() === FALSE) {
        echo json_encode(['status' => 'error', 'message' => 'Payment recording failed']);
    } else {
        echo json_encode(['status' => 'success', 'message' => $message]);
    }
}

/**
 * Enhanced invoice creation with automatic credit application
 * Add this to the end of your existing invoice creation method
 */
public function apply_credits_after_invoice_creation($invoice_id) {
    $this->load->model('Credit_model');
    $result = $this->Credit_model->apply_credits_to_invoice($invoice_id);
    
    if ($result['status'] === 'success' && $result['credit_applied'] > 0) {
        // Update the response to include credit information
        return [
            'credit_applied' => $result['credit_applied'],
            'new_due_amount' => $result['new_due_amount'],
            'message' => $result['message']
        ];
    }
    
    return null;
}

/**
 * Enhanced invoice modification with credit recalculation
 * Call this after any invoice amount modification
 */
public function handle_invoice_modification($invoice_id, $old_amount, $new_amount) {
    $this->load->hooks('Credit_hooks');
    $credit_hooks = new Credit_hooks();
    $credit_hooks->after_invoice_modified($invoice_id, $old_amount, $new_amount);
}

/**
 * Enhanced discount application with credit handling
 * Call this after discount is applied
 */
public function handle_discount_application($invoice_id, $discount_amount) {
    $this->load->hooks('Credit_hooks');
    $credit_hooks = new Credit_hooks();
    $credit_hooks->after_discount_applied($invoice_id, $discount_amount);
}

/**
 * Get student's credit summary for display
 */
public function get_student_credit_summary() {
    $student_id = $this->input->post('student_id');
    
    $this->load->model('Credit_model');
    $total_credit = $this->Credit_model->get_student_total_credit($student_id);
    $credits = $this->Credit_model->get_student_credits($student_id, true);
    
    echo json_encode([
        'status' => 'success',
        'total_credit' => $total_credit,
        'active_credits' => count($credits),
        'credits' => $credits
    ]);
}

/**
 * Manual credit adjustment (admin only)
 */
public function adjust_student_credit() {
    if ($this->session->userdata('admin_login') != 1) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }
    
    $student_id = $this->input->post('student_id');
    $amount = floatval($this->input->post('amount'));
    $reason = $this->input->post('reason');
    $admin_id = $this->session->userdata('login_user_id');
    
    $this->load->model('Credit_model');
    $result = $this->Credit_model->adjust_credit($student_id, $amount, $reason, $admin_id);
    
    echo json_encode($result);
}

/**
 * Generate unique receipt code
 */
private function generate_receipt_code() {
    $year = date('Y');
    $month = date('m');
    
    // Get last receipt number for this month
    $this->db->select('receipt_code');
    $this->db->like('receipt_code', "RCP{$year}{$month}", 'after');
    $this->db->order_by('payment_id', 'DESC');
    $this->db->limit(1);
    $last_receipt = $this->db->get('payment')->row();
    
    if ($last_receipt) {
        $last_number = intval(substr($last_receipt->receipt_code, -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return "RCP{$year}{$month}" . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}