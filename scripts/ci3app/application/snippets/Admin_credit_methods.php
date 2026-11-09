<?php
// Add these methods to your existing Admin.php controller

/**
 * Enhanced payment processing with credit management
 */
public function process_payment_with_credits() {
    $this->load->model('Credit_model');
    
    $payment_data = [
        'student_id' => $this->input->post('student_id'),
        'invoice_id' => $this->input->post('invoice_id'),
        'amount' => $this->input->post('amount'),
        'payment_method' => $this->input->post('payment_method'),
        'receipt_code' => $this->generate_receipt_code()
    ];
    
    $this->db->trans_start();
    
    // 1. Process normal payment
    $payment_result = $this->process_normal_payment($payment_data);
    
    if ($payment_result['status'] == 'success') {
        // 2. Check for overpayment and create credit
        $credit_result = $this->Credit_model->process_overpayment($payment_data);
        
        if ($credit_result['status'] == 'success') {
            $payment_result['credit_created'] = $credit_result;
        }
    }
    
    $this->db->trans_complete();
    
    echo json_encode($payment_result);
}

/**
 * Enhanced invoice creation with automatic credit application
 */
public function create_invoice_with_credits() {
    $this->load->model('Credit_model');
    
    // 1. Create invoice normally
    $invoice_data = $this->input->post();
    $invoice_id = $this->create_normal_invoice($invoice_data);
    
    if ($invoice_id) {
        // 2. Apply available credits automatically
        $credit_result = $this->Credit_model->apply_credits_to_invoice($invoice_id);
        
        echo json_encode([
            'status' => 'success',
            'invoice_id' => $invoice_id,
            'credit_applied' => $credit_result
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create invoice']);
    }
}

/**
 * Get student credit information for display
 */
public function get_student_credit_info($student_id) {
    $this->load->model('Credit_model');
    
    $total_credit = $this->Credit_model->get_student_total_credit($student_id);
    $credit_history = $this->Credit_model->get_credit_history($student_id);
    
    echo json_encode([
        'total_credit' => $total_credit,
        'history' => $credit_history
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
    
    $this->load->model('Credit_model');
    
    $student_id = $this->input->post('student_id');
    $amount = $this->input->post('amount');
    $reason = $this->input->post('reason');
    $admin_id = $this->session->userdata('login_user_id');
    
    $result = $this->Credit_model->adjust_credit($student_id, $amount, $reason, $admin_id);
    echo json_encode($result);
}

/**
 * Generate unique receipt code
 */
private function generate_receipt_code() {
    return 'RCP' . date('Ymd') . rand(1000, 9999);
}

/**
 * Process normal payment (existing logic)
 */
private function process_normal_payment($payment_data) {
    // Your existing payment processing logic here
    // This should handle the basic payment recording
    
    return ['status' => 'success', 'payment_id' => 123]; // Example
}

/**
 * Create normal invoice (existing logic)
 */
private function create_normal_invoice($invoice_data) {
    // Your existing invoice creation logic here
    // This should create the invoice and return invoice_id
    
    return 456; // Example invoice_id
}