<?php
/**
 * INTEGRATION HOOKS - Add these to your existing methods
 */

// 1. MODIFY EXISTING PAYMENT PROCESSING
// In your existing Admin::student_payment() or similar method, add this:

public function student_payment($param1 = '', $param2 = '', $param3 = '') {
    // ... existing code ...
    
    // ADD THIS AFTER SUCCESSFUL PAYMENT PROCESSING
    if ($payment_successful) {
        $this->load->model('Credit_model');
        
        // Check for overpayment and create credit
        $payment_data = [
            'student_id' => $student_id,
            'invoice_id' => $invoice_id,
            'amount' => $amount_paid,
            'receipt_code' => $receipt_code,
            'payment_id' => $payment_id
        ];
        
        $credit_result = $this->Credit_model->process_overpayment($payment_data);
        
        if ($credit_result['status'] == 'success') {
            // Show credit creation message
            $this->session->set_flashdata('credit_created', $credit_result['message']);
        }
    }
    
    // ... rest of existing code ...
}

// 2. MODIFY EXISTING INVOICE CREATION
// In your existing Admin::student() create section, add this:

public function student($param1 = '', $param2 = '', $param3 = '') {
    if ($param1 == 'create') {
        // ... existing invoice creation code ...
        
        // ADD THIS AFTER INVOICE CREATION
        if ($invoice_created_successfully) {
            $this->load->model('Credit_model');
            
            // Apply available credits to new invoice
            $credit_result = $this->Credit_model->apply_credits_to_invoice($invoice_id);
            
            if ($credit_result['credit_applied'] > 0) {
                $this->session->set_flashdata('credit_applied', $credit_result['message']);
            }
        }
    }
    
    // ... rest of existing code ...
}

// 3. MODIFY INVOICE DISPLAY
// In your invoice views, add this to show credit information:

// Add to invoice display view (before amount due)
<?php
$this->load->model('Credit_model');
$student_credit = $this->Credit_model->get_student_total_credit($student_id);
$credit_applied = $invoice_row->credit_applied ?? 0;
?>

<?php if ($student_credit > 0): ?>
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    <strong>Available Credit:</strong> GH₵ <?=number_format($student_credit, 2)?>
    <small>(Will be applied to future bills)</small>
</div>
<?php endif; ?>

<?php if ($credit_applied > 0): ?>
<div class="alert alert-success">
    <i class="fa fa-check-circle"></i>
    <strong>Credit Applied:</strong> -GH₵ <?=number_format($credit_applied, 2)?>
</div>
<?php endif; ?>

// 4. MODIFY STUDENT PROFILE
// Add to student profile view:

<div class="row">
    <div class="col-md-6">
        <!-- Existing student info -->
    </div>
    <div class="col-md-6">
        <?php 
        $this->load->model('Credit_model');
        $student_id = $student_row['student_id'];
        include 'student_credit_widget.php'; 
        ?>
    </div>
</div>

// 5. MODIFY PAYMENT RECEIPT
// Add to receipt templates to show credit information:

<?php
// Add this to receipt PHP section
$this->load->model('Credit_model');
$credit_created = 0;

// Check if this payment created any credit
$credit_check = $this->db->get_where('student_credits', [
    'source_receipt_code' => $receipt_code,
    'student_id' => $student_id
])->row();

if ($credit_check) {
    $credit_created = $credit_check->credit_amount;
}
?>

<!-- Add this to receipt HTML -->
<?php if ($credit_created > 0): ?>
<tr style="background: #d4edda;">
    <td colspan="3" align="center" style="color: #155724; font-weight: bold;">
        CREDIT CREATED: GH₵ <?=number_format($credit_created, 2)?>
    </td>
</tr>
<tr>
    <td colspan="3" align="center" style="font-size: 10px; color: #155724;">
        This credit will be applied to your next bill
    </td>
</tr>
<?php endif; ?>

// 6. ADD TO NAVIGATION MENU
// Add credit management to admin navigation:

<li>
    <a href="<?php echo site_url('admin/student_credits'); ?>">
        <i class="fa fa-credit-card"></i>
        <span><?php echo get_phrase('student_credits'); ?></span>
    </a>
</li>

// 7. LANGUAGE PHRASES TO ADD
// Add to language file:

$lang['student_credits'] = 'Student Credits';
$lang['credit_balance'] = 'Credit Balance';
$lang['credit_applied'] = 'Credit Applied';
$lang['overpayment_credited'] = 'Overpayment Credited';
$lang['credit_history'] = 'Credit History';
$lang['adjust_credit'] = 'Adjust Credit';
$lang['credit_adjustment'] = 'Credit Adjustment';
$lang['available_credit'] = 'Available Credit';
$lang['credit_will_be_applied'] = 'Credit will be applied to next bill';

// 8. DASHBOARD WIDGET
// Add to admin dashboard to show total credits:

<div class="col-lg-3 col-md-6">
    <div class="panel panel-success">
        <div class="panel-heading">
            <div class="row">
                <div class="col-xs-3">
                    <i class="fa fa-credit-card fa-5x"></i>
                </div>
                <div class="col-xs-9 text-right">
                    <?php
                    $this->load->model('Credit_model');
                    $total_credits = $this->db->select_sum('remaining_amount')
                                             ->where('status', 'active')
                                             ->get('student_credits')
                                             ->row()->remaining_amount ?? 0;
                    ?>
                    <div class="huge">GH₵ <?=number_format($total_credits, 0)?></div>
                    <div>Student Credits</div>
                </div>
            </div>
        </div>
        <a href="<?=site_url('admin/student_credits')?>">
            <div class="panel-footer">
                <span class="pull-left">View Details</span>
                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                <div class="clearfix"></div>
            </div>
        </a>
    </div>
</div>