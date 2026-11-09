<?php
/**
 * RECEIPT INTEGRATION - Add this to all receipt templates
 * Add this PHP code to the top of your receipt files after existing PHP code
 */

// Check if this payment created any credit
$this->load->model('Credit_model');
$credit_created = 0;
$credit_info = null;

// Check if this receipt created any credit
$credit_check = $this->db->get_where('student_credits', [
    'source_receipt_code' => $receipt_code,
    'student_id' => $student_id
])->row();

if ($credit_check) {
    $credit_created = $credit_check->credit_amount;
    $credit_info = $credit_check;
}

// Get student's current total credit balance
$total_student_credit = $this->Credit_model->get_student_total_credit($student_id);
?>

<!-- Add this HTML to your receipt templates after the payment details section -->

<?php if ($credit_created > 0): ?>
<!-- Credit Created Section -->
<tr style="background: linear-gradient(135deg, #d4edda, #c3e6cb); border: 2px solid #28a745;">
    <td colspan="<?php echo isset($colspan) ? $colspan : '4'; ?>" align="center" style="padding: 10px;">
        <div style="color: #155724; font-weight: bold; font-size: 14px;">
            <i class="fa fa-credit-card" style="font-size: 16px; margin-right: 5px;"></i>
            CREDIT CREATED: GH₵ <?=number_format($credit_created, 2)?>
        </div>
        <div style="color: #155724; font-size: 11px; margin-top: 3px;">
            This credit will be automatically applied to your next bill
        </div>
    </td>
</tr>
<tr><td colspan="<?php echo isset($colspan) ? $colspan : '4'; ?>" style="height: 5px;"></td></tr>
<?php endif; ?>

<?php if ($total_student_credit > 0 && $credit_created == 0): ?>
<!-- Existing Credit Balance Section -->
<tr style="background: linear-gradient(135deg, #cce5ff, #b3d9ff); border: 1px solid #007bff;">
    <td colspan="<?php echo isset($colspan) ? $colspan : '4'; ?>" align="center" style="padding: 8px;">
        <div style="color: #004085; font-weight: bold; font-size: 12px;">
            <i class="fa fa-info-circle" style="margin-right: 5px;"></i>
            AVAILABLE CREDIT BALANCE: GH₵ <?=number_format($total_student_credit, 2)?>
        </div>
        <div style="color: #004085; font-size: 10px; margin-top: 2px;">
            This credit will reduce your future bills
        </div>
    </td>
</tr>
<tr><td colspan="<?php echo isset($colspan) ? $colspan : '4'; ?>" style="height: 5px;"></td></tr>
<?php endif; ?>

<?php
/**
 * SPECIFIC INTEGRATION FOR EACH RECEIPT TEMPLATE
 */

// FOR modal_receipt.php - Add after line with "Arrears:" row
?>
<!-- Add this after the arrears row in modal_receipt.php -->
<?php if ($credit_created > 0): ?>
<tr><td colspan="4"><hr></td></tr>
<tr style="background: #d4edda;">
    <td colspan="4" align="center" style="color: #155724; font-weight: bold;">
        <i class="fa fa-credit-card"></i> CREDIT CREATED: GH₵ <?=number_format($credit_created, 2)?>
    </td>
</tr>
<tr>
    <td colspan="4" align="center" style="font-size: 10px; color: #155724;">
        This credit will be applied to your next bill
    </td>
</tr>
<?php endif; ?>

<?php
// FOR modal_receipt_2.php - Add before the signature section
?>
<!-- Add this before signature section in modal_receipt_2.php -->
<?php if ($credit_created > 0): ?>
<tr><td colspan="8"></td></tr>
<tr style="background: #d4edda;">
    <td align="left" style="color: #155724; font-weight: bold;">Credit Created:</td>
    <td align="left" colspan="7" style="color: #155724; font-weight: bold;">
        GH₵ <?=number_format($credit_created, 2)?> (Applied to next bill)
    </td>
</tr>
<tr><td colspan="8"></td></tr>
<?php endif; ?>

<?php
// FOR modal_receipt_3.php - Add after amount paid section
?>
<!-- Add this after amount paid section in modal_receipt_3.php -->
<?php if ($credit_created > 0): ?>
<tr>
    <td align="left" colspan="2" style="font-size: 13px; color: #27ae60; font-weight: bold;">Credit Created:</td>
    <td align="left" colspan="2" style="font-size: 14px; color: #27ae60; font-weight: bold;">
        <strong>GH₵ <?=number_format($credit_created, 2)?></strong>
    </td>
    <td align="right" colspan="2" style="text-align: right; font-size: 12px; color: #27ae60;">
        (Applied to next bill)
    </td>
</tr>
<?php endif; ?>

<?php
// FOR receipt.php - Add after balance due section
?>
<!-- Add this after balance due row in receipt.php -->
<?php if ($credit_created > 0): ?>
<tr style="background: #e8f5e8; border-top: 2px solid #27ae60;">
    <td colspan="2"><strong>Credit Created:</strong></td>
    <td class="amount"><strong style="color: #27ae60;">GH₵ <?=number_format($credit_created, 2)?></strong></td>
</tr>
<tr>
    <td colspan="3" style="font-size: 9px; color: #27ae60; text-align: center; font-style: italic;">
        This credit will be automatically applied to your next bill
    </td>
</tr>
<?php endif; ?>

<?php
// FOR receipt_2.php - Add before signature section
?>
<!-- Add this before signature section in receipt_2.php -->
<?php if ($credit_created > 0): ?>
<tr><td colspan="8"></td></tr>
<tr style="background: #d4edda;">
    <td align="left" style="color: #155724; font-weight: bold;">Credit Created:</td>
    <td align="left" colspan="7" style="color: #155724; font-weight: bold;">
        GH₵ <?=number_format($credit_created, 2)?> - Will be applied to next bill
    </td>
</tr>
<tr><td colspan="8"></td></tr>
<?php endif; ?>

<?php
// FOR receipt_3.php - Add after amount paid section
?>
<!-- Add this after amount paid section in receipt_3.php -->
<?php if ($credit_created > 0): ?>
<tr>
    <td align="left" colspan="2" style="font-size: 13px; color: #27ae60; font-weight: bold;">Credit Created:</td>
    <td align="left" colspan="2" style="font-size: 14px; color: #27ae60; font-weight: bold;">
        <strong>GH₵ <?=number_format($credit_created, 2)?></strong>
    </td>
    <td align="right" colspan="2" style="text-align: right; font-size: 12px; color: #27ae60;">
        (Applied to next bill)
    </td>
</tr>
<?php endif; ?>

<?php
/**
 * INVOICE INTEGRATION - Add this to invoice display views
 */
?>

<!-- Add this to invoice display views to show applied credits -->
<?php
$this->load->model('Credit_model');
$invoice_credit_applied = $invoice_row->credit_applied ?? 0;
$student_available_credit = $this->Credit_model->get_student_total_credit($invoice_row->student_id);
?>

<!-- Show applied credits on invoice -->
<?php if ($invoice_credit_applied > 0): ?>
<div class="alert alert-success" style="margin: 10px 0;">
    <h5><i class="fa fa-check-circle"></i> Credit Applied to This Invoice</h5>
    <p style="margin: 0;">
        <strong>Credit Applied:</strong> -GH₵ <?=number_format($invoice_credit_applied, 2)?><br>
        <small>This amount was automatically deducted from your available credit balance</small>
    </p>
</div>
<?php endif; ?>

<!-- Show available credits -->
<?php if ($student_available_credit > 0): ?>
<div class="alert alert-info" style="margin: 10px 0;">
    <h5><i class="fa fa-info-circle"></i> Available Credit Balance</h5>
    <p style="margin: 0;">
        <strong>Available Credit:</strong> GH₵ <?=number_format($student_available_credit, 2)?><br>
        <small>This credit will be automatically applied to future bills</small>
    </p>
</div>
<?php endif; ?>

<!-- Updated invoice amount display -->
<div class="invoice-amounts" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
    <div class="row">
        <div class="col-md-6">
            <strong>Original Amount:</strong> GH₵ <?=number_format($invoice_row->amount, 2)?>
        </div>
        <div class="col-md-6">
            <strong>Amount Paid:</strong> GH₵ <?=number_format($invoice_row->amount_paid, 2)?>
        </div>
    </div>
    <?php if ($invoice_credit_applied > 0): ?>
    <div class="row" style="color: #27ae60;">
        <div class="col-md-6">
            <strong>Credit Applied:</strong> -GH₵ <?=number_format($invoice_credit_applied, 2)?>
        </div>
        <div class="col-md-6">
            <strong>Net Due:</strong> GH₵ <?=number_format($invoice_row->net_due ?? ($invoice_row->amount - $invoice_row->amount_paid - $invoice_credit_applied), 2)?>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-md-6"></div>
        <div class="col-md-6">
            <strong>Amount Due:</strong> GH₵ <?=number_format($invoice_row->due, 2)?>
        </div>
    </div>
    <?php endif; ?>
</div>