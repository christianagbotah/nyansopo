<!-- Enhanced Invoice Summary with Discounts -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo get_phrase('invoice_summary'); ?></h3>
    </div>
    <div class="panel-body">
        <table class="table table-bordered">
            <tr>
                <th><?php echo get_phrase('invoice_code'); ?></th>
                <td><?php echo $invoice_code; ?></td>
            </tr>
            <tr>
                <th><?php echo get_phrase('total_amount'); ?></th>
                <td><?php echo get_settings('currency') . ' ' . number_format($total_amount, 2); ?></td>
            </tr>
            <tr>
                <th><?php echo get_phrase('total_discount'); ?></th>
                <td class="text-success"><?php echo get_settings('currency') . ' ' . number_format($total_discount, 2); ?></td>
            </tr>
            <tr>
                <th><?php echo get_phrase('amount_paid'); ?></th>
                <td><?php echo get_settings('currency') . ' ' . number_format($total_paid, 2); ?></td>
            </tr>
            <tr>
                <th><?php echo get_phrase('balance_due'); ?></th>
                <td class="<?php echo $balance_due > 0 ? 'text-danger' : 'text-success'; ?>">
                    <strong><?php echo get_settings('currency') . ' ' . number_format($balance_due, 2); ?></strong>
                </td>
            </tr>
            <tr>
                <th><?php echo get_phrase('payment_status'); ?></th>
                <td>
                    <?php if($payment_status == 'paid'): ?>
                        <span class="label label-success"><?php echo get_phrase('paid'); ?></span>
                    <?php elseif($payment_status == 'partial'): ?>
                        <span class="label label-warning"><?php echo get_phrase('partial'); ?></span>
                    <?php else: ?>
                        <span class="label label-danger"><?php echo get_phrase('unpaid'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <div class="btn-group btn-group-justified">
            <a href="#" class="btn btn-info" onclick="showDiscountModal(<?php echo $student_id; ?>, '<?php echo $invoice_code; ?>')">
                <i class="fa fa-percent"></i> <?php echo get_phrase('apply_discount'); ?>
            </a>
            <a href="#" class="btn btn-warning" onclick="sendPaymentReminder(<?php echo $student_id; ?>, '<?php echo $invoice_code; ?>')">
                <i class="fa fa-envelope"></i> <?php echo get_phrase('send_reminder'); ?>
            </a>
        </div>
    </div>
</div>

<script>
function sendPaymentReminder(student_id, invoice_code) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_action'); ?>',
        '<?php echo get_phrase('send_payment_reminder_confirmation'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('sending'); ?>', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/send_payment_reminder'); ?>',
                type: 'POST',
                data: {student_id: student_id, invoice_code: invoice_code},
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
            });
        },
        '<?php echo get_phrase('send'); ?>',
        'primary'
    );
}
</script>
