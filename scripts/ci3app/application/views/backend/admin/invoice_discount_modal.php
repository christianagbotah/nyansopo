<!-- Apply Discount Modal -->
<div class="modal fade" id="discountModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo get_phrase('apply_discount'); ?></h4>
            </div>
            <form id="discountForm" method="post" action="<?php echo site_url('admin/apply_discount'); ?>">
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="discount_student_id">
                    <input type="hidden" name="invoice_code" id="discount_invoice_code">
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('discount_type'); ?></label>
                        <select name="discount_type" class="form-control" required>
                            <option value="early_payment"><?php echo get_phrase('early_payment'); ?></option>
                            <option value="sibling"><?php echo get_phrase('sibling_discount'); ?></option>
                            <option value="hardship"><?php echo get_phrase('financial_hardship'); ?></option>
                            <option value="staff_child"><?php echo get_phrase('staff_child'); ?></option>
                            <option value="scholarship"><?php echo get_phrase('scholarship'); ?></option>
                            <option value="other"><?php echo get_phrase('other'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('discount_method'); ?></label>
                        <select name="discount_method" id="discount_method" class="form-control" required>
                            <option value="percentage"><?php echo get_phrase('percentage'); ?></option>
                            <option value="fixed"><?php echo get_phrase('fixed_amount'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('discount_value'); ?></label>
                        <input type="number" name="discount_value" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo get_phrase('reason'); ?></label>
                        <textarea name="reason" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo get_phrase('apply_discount'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showDiscountModal(student_id, invoice_code) {
    $('#discount_student_id').val(student_id);
    $('#discount_invoice_code').val(invoice_code);
    $('#discountModal').modal('show');
}

$('#discountForm').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('<?php echo get_phrase('processing'); ?>', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase('operation_failed'); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});
</script>
