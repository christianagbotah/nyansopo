<?php
if (!isset($active_modules) || !isset($running_year) || !isset($running_term)) {
    echo '<div class="alert alert-danger">Required data not found</div>';
    return;
}
?>

<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px;">
            <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">&times;</button>
            <h4 class="modal-title"><i class="fa fa-users"></i> Bulk Adjust Balances</h4>
        </div>

        <div class="modal-body" style="padding: 30px;">
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> <strong>Warning:</strong> This will adjust balances for all selected students. This action will be logged in the audit trail.
            </div>

            <div id="bulk-students-preview" style="margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; max-height: 200px; overflow-y: auto;">
                <p><strong>Selected Students:</strong> <span id="selected-count">0</span></p>
                <div id="selected-student-list"></div>
            </div>

            <form id="bulkAdjustBalanceForm">
                <input type="hidden" name="student_ids" id="bulk_student_ids" value="">
                <input type="hidden" name="year" value="<?php echo $running_year; ?>">
                <input type="hidden" name="term" value="<?php echo $running_term; ?>">

                <?php foreach ($active_modules as $module): ?>
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <input type="checkbox" id="enable_<?php echo $module; ?>" name="enabled_modules[]" value="<?php echo $module; ?>" style="width: 20px; height: 20px;">
                        <label for="enable_<?php echo $module; ?>" style="margin: 0; font-weight: 600; flex: 1;">
                            <i class="fa fa-utensils"></i> <?php echo ucfirst($module); ?>
                        </label>
                        <select class="form-control" name="adjustment_type_<?php echo $module; ?>" style="width: 150px;" disabled>
                            <option value="arrears">Arrears</option>
                            <option value="prepaid">Prepaid</option>
                        </select>
                        <input type="number" class="form-control" name="amount_<?php echo $module; ?>" step="0.01" min="0" placeholder="0.00" style="width: 150px;" disabled>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="form-group" style="margin-top: 25px;">
                    <label><i class="fa fa-comment"></i> Reason for Bulk Adjustment</label>
                    <textarea class="form-control" name="reason" rows="3" required placeholder="Explain why this bulk adjustment is being made..."></textarea>
                </div>
            </form>
        </div>

        <div class="modal-footer" style="padding: 15px 30px; background: #f8fafc;">
            <button type="button" class="btn btn-default" data-dismiss="modal">
                <i class="fa fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-warning" onclick="submitBulkAdjustment()">
                <i class="fa fa-check-double"></i> Apply Bulk Adjustment
            </button>
        </div>
    </div>
</div>

<script>
// Enable/disable fields when checkboxes are toggled
<?php foreach ($active_modules as $module): ?>
$('#enable_<?php echo $module; ?>').change(function() {
    var isChecked = $(this).is(':checked');
    $('select[name="adjustment_type_<?php echo $module; ?>"]').prop('disabled', !isChecked);
    $('input[name="amount_<?php echo $module; ?>"]').prop('disabled', !isChecked);
});
<?php endforeach; ?>

function submitBulkAdjustment() {
    var formData = $('#bulkAdjustBalanceForm').serialize();
    
    // Validate at least one module is enabled
    var enabledModules = $('input[name="enabled_modules[]"]:checked').length;
    if (enabledModules === 0) {
        $.gritter.add({
            title: 'Validation Error',
            text: 'Please enable at least one fee module',
            class_name: 'gritter-error'
        });
        return;
    }

    $.ajax({
        url: '<?php echo base_url(); ?>admin/bulk_adjust_balances',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $.gritter.add({
                title: 'Processing',
                text: 'Applying bulk adjustments...',
                class_name: 'gritter-info'
            });
        },
        success: function(response) {
            if (response.success) {
                $.gritter.add({
                    title: 'Success',
                    text: response.message || 'Bulk adjustments applied successfully',
                    class_name: 'gritter-success'
                });
                $('.modal').modal('hide');
                $('.bulk-select-checkbox').prop('checked', false);
                $('#bulk-action-bar').removeClass('active');
                loadStudents(); // Reload student list
            } else {
                $.gritter.add({
                    title: 'Error',
                    text: response.message || 'Failed to apply bulk adjustments',
                    class_name: 'gritter-error'
                });
            }
        },
        error: function() {
            $.gritter.add({
                title: 'Error',
                text: 'An error occurred while processing the bulk adjustments',
                class_name: 'gritter-error'
            });
        }
    });
}
</script>
