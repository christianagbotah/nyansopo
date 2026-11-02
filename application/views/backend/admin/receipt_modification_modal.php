<?php
$payment_id = $this->uri->segment(3);
$payment = $this->db->where('payment_id', $payment_id)->get('payment')->row();

if(!$payment) {
    echo '<div style="padding: 40px; text-align: center;">';
    echo '<i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 16px;"></i>';
    echo '<h3>Payment not found</h3>';
    echo '<p>The payment record could not be found.</p>';
    echo '</div>';
    return;
}

$invoice = $this->db->where('invoice_id', $payment->invoice_id)->get('invoice')->row();
$student = $this->db->where('student_id', $payment->student_id)->get('student')->row();
$running_year_row = $this->db->get_where('settings', array('type' => 'running_year'))->row();
$running_year = $running_year_row ? $running_year_row->description : date('Y') . '-' . (date('Y') + 1);
$enroll = $this->db->where('student_id', $payment->student_id)->where('year', $running_year)->get('enroll')->row();
$class_id = $enroll ? $enroll->class_id : null;
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';
$theme_color_row = $this->db->get_where('settings', array('type' => 'theme_color'))->row();
$theme_color = $theme_color_row ? $theme_color_row->description : '667eea';

if(strpos($theme_color, '#') !== 0) {
    $theme_color = '#' . $theme_color;
}

function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
$gradient_light = adjustBrightness($theme_color, 20);
$gradient_dark = adjustBrightness($theme_color, -30);
?>

<style>
#receiptModificationForm .action-btn {
    flex: 1;
    padding: 20px;
    border: 3px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
    position: relative;
    overflow: hidden;
}
#receiptModificationForm .action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}
#receiptModificationForm .action-btn.selected {
    border-color: currentColor;
    box-shadow: 0 0 0 3px rgba(255,255,255,0.5), 0 8px 16px rgba(0,0,0,0.2);
}
#receiptModificationForm .action-btn-edit {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}
#receiptModificationForm .action-btn-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}
#receiptModificationForm .action-btn i {
    font-size: 32px;
    display: block;
    margin-bottom: 8px;
}
#receiptModificationForm .action-btn-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
}
#receiptModificationForm .action-btn-desc {
    font-size: 13px;
    opacity: 0.9;
}
</style>

<?php echo form_open('admin/request_receipt_modification', array('id' => 'receiptModificationForm')); ?>
    <input type="hidden" name="receipt_code" value="<?php echo $payment->receipt_code; ?>">
    <input type="hidden" name="payment_id" value="<?php echo $payment_id; ?>">
    <input type="hidden" name="request_type" id="request_type" value="">
    
    <div style="padding: 24px;">
        <!-- Receipt Info Card -->
        <div style="background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
            <div style="display: flex; align-items: center; gap: 16px; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="background: rgba(255,255,255,0.2); padding: 16px; border-radius: 50%; backdrop-filter: blur(10px);">
                        <i class="fa fa-receipt" style="font-size: 28px;"></i>
                    </div>
                    <div>
                        <div style="font-size: 13px; opacity: 0.9; margin-bottom: 4px;">Receipt #<?php echo $payment->receipt_code; ?></div>
                        <div style="font-size: 28px; font-weight: 700;"><?php echo $currency . ' ' . number_format(($payment->amount ?? 0), 2); ?></div>
                    </div>
                </div>
                <div style="text-align: right; border-left: 2px solid rgba(255,255,255,0.3); padding-left: 20px;">
                    <div style="font-size: 15px; font-weight: 600; margin-bottom: 6px;">
                        <i class="fa fa-user"></i> <?php echo $student->name; ?>
                    </div>
                    <div style="font-size: 13px; opacity: 0.9;">
                        <i class="fa fa-id-card"></i> <?php echo $student->student_code; ?>
                    </div>
                    <div style="font-size: 13px; opacity: 0.9;">
                        <i class="fa fa-school"></i> <?php echo $class_id ? $this->crud_model->getFullClassName($class_id) : 'N/A'; ?>
                    </div>
                    <div style="font-size: 12px; opacity: 0.85; margin-top: 4px;">
                        <i class="fa fa-calendar"></i> <?php echo date('d M Y', $payment->timestamp); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Selection -->
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1f2937; margin-bottom: 12px;">
                <i class="fa fa-hand-pointer"></i> Select Action
            </label>
            <div style="display: flex; gap: 16px;">
                <div class="action-btn action-btn-edit" onclick="selectAction('edit')" id="btn-edit">
                    <i class="fa fa-edit"></i>
                    <div class="action-btn-title">Edit Receipt</div>
                    <div class="action-btn-desc">Modify amount or payment method</div>
                </div>
                <div class="action-btn action-btn-delete" onclick="selectAction('delete')" id="btn-delete">
                    <i class="fa fa-trash-alt"></i>
                    <div class="action-btn-title">Delete Receipt</div>
                    <div class="action-btn-desc">Remove this receipt completely</div>
                </div>
            </div>
        </div>

        <!-- Edit Fields -->
        <div id="editFields" style="display: none; margin-bottom: 24px;">
            <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: 12px; padding: 20px;">
                <div style="font-size: 15px; font-weight: 700; color: #166534; margin-bottom: 16px;">
                    <i class="fa fa-edit"></i> Edit Details
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">New Amount</label>
                    <input type="number" name="new_amount" class="form-control" step="0.01" value="<?php echo ($payment->amount ?? 0); ?>" 
                           style="height: 48px; border: 2px solid #d1fae5; border-radius: 8px; font-size: 16px; font-weight: 600;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">New Payment Method</label>
                    <select name="new_method" class="form-control" style="height: 48px; border: 2px solid #d1fae5; border-radius: 8px; font-size: 15px;">
                        <option value="cash" <?php echo (isset($payment->payment_method) && $payment->payment_method == 'cash') ? 'selected' : ''; ?>>Cash</option>
                        <option value="momo" <?php echo (isset($payment->payment_method) && $payment->payment_method == 'momo') ? 'selected' : ''; ?>>Mobile Money</option>
                        <option value="cheque" <?php echo (isset($payment->payment_method) && $payment->payment_method == 'cheque') ? 'selected' : ''; ?>>Cheque</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Delete Warning -->
        <div id="deleteWarning" style="display: none; margin-bottom: 24px;">
            <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border: 2px solid #ef4444; border-radius: 12px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fa fa-exclamation-circle" style="color: #dc2626; font-size: 32px;"></i>
                    <div style="font-size: 16px; font-weight: 700; color: #991b1b;">⚠️ Permanent Deletion Warning</div>
                </div>
                <div style="color: #7f1d1d; font-size: 14px; line-height: 1.6;">
                    <p style="margin: 0 0 8px 0;"><strong>You are about to permanently delete this receipt:</strong></p>
                    <ul style="margin: 8px 0; padding-left: 20px;">
                        <li>Receipt #<?php echo $payment->receipt_code; ?> for <strong><?php echo $student->name; ?> (<?php echo $student->student_code; ?>)</strong></li>
                        <li>Class: <strong><?php echo $class_id ? $this->crud_model->getFullClassName($class_id) : 'N/A'; ?></strong></li>
                        <li>The payment of <strong><?php echo $currency . ' ' . number_format(($payment->amount ?? 0), 2); ?></strong> will be reversed</li>
                        <li>The invoice balance will be updated accordingly</li>
                        <li>This action cannot be undone once approved</li>
                    </ul>
                    <p style="margin: 8px 0 0 0; font-weight: 600;">⚠️ Please ensure this is the correct action before proceeding.</p>
                </div>
            </div>
        </div>

        <!-- Reason Field -->
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">
                <i class="fa fa-comment-dots"></i> Reason for Modification <span style="color: #ef4444;">*</span>
            </label>
            <textarea name="reason" class="form-control" rows="4" required 
                      placeholder="Please provide a detailed reason for this modification request..."
                      style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px; font-size: 14px; resize: vertical; transition: all 0.3s;"
                      onfocus="this.style.borderColor='<?php echo $theme_color; ?>'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                      onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'"></textarea>
        </div>

        <!-- Warning Box -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fa fa-exclamation-triangle" style="color: #92400e; font-size: 24px;"></i>
                <div style="color: #78350f; font-size: 14px; line-height: 1.6;">
                    <strong>Important:</strong> This request will be sent to super admin for approval. You will be notified once a decision is made.
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="btn btn-default" data-dismiss="modal" 
                    style="padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; border: 2px solid #e5e7eb;">
                <i class="fa fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary" 
                    style="padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); border: none; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);">
                <i class="fa fa-paper-plane"></i> Submit Request
            </button>
        </div>
    </div>
<?php echo form_close(); ?>

<script>
(function() {
    var selectedAction = null;
    
    window.selectAction = function(action) {
        selectedAction = action;
        $('#request_type').val(action);
        
        // Update button states
        $('#receiptModificationForm .action-btn').removeClass('selected');
        $('#receiptModificationForm #btn-' + action).addClass('selected');
        
        // Show/hide edit fields or delete warning
        if(action === 'edit') {
            $('#editFields').slideDown(300);
            $('#deleteWarning').slideUp(300);
        } else if(action === 'delete') {
            $('#deleteWarning').slideDown(300);
            $('#editFields').slideUp(300);
        }
    };
    
    window.getSelectedAction = function() {
        return selectedAction;
    };
})();

// Event delegation for form submission
$(document).off('submit', '#receiptModificationForm').on('submit', '#receiptModificationForm', function(e) {
    e.preventDefault();
    
    if(!window.getSelectedAction()) {
        showAjaxModal_alert('Please select an action (Edit or Delete)', 'warning');
        return;
    }
    
    showAjaxModal_alert('Submitting request...', 'loading');
    
    var formData = $(this).serialize();
    
    // Add new_data for edit requests
    if(window.getSelectedAction() === 'edit') {
        var newData = {
            amount: $('input[name="new_amount"]').val(),
            payment_method: $('select[name="new_method"]').val()
        };
        formData += '&new_data=' + encodeURIComponent(JSON.stringify(newData));
    }
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success', false);
            setTimeout(function() {
                $('#ajaxModal, #receiptModificationModal, .modal').modal('hide');
                $('.modal-backdrop').remove();
                if(typeof refreshBulkInvoices === 'function') {
                    refreshBulkInvoices();
                } else {
                    location.reload();
                }
            }, 2000);
        } else {
            showAjaxModal_alert(response.message, response.status);
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});
</script>
