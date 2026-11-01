<?php
$edit_mode = isset($discount_type);
$discount_type_id = $edit_mode ? $discount_type['id'] : '';
$discount_name = $edit_mode ? $discount_type['name'] : '';
$discount_description = $edit_mode ? $discount_type['description'] : '';
$is_percentage = $edit_mode ? $discount_type['is_percentage'] : '1';
$default_value = $edit_mode ? $discount_type['default_value'] : '';
$max_amount = $edit_mode ? $discount_type['max_amount'] : '';
$requires_approval = $edit_mode ? $discount_type['requires_approval'] : '0';
$is_active = $edit_mode ? $discount_type['is_active'] : '1';
?>

<?php echo form_open('admin/save_discount_type', array('id' => 'discountTypeForm')); ?>
    <input type="hidden" name="discount_type_id" value="<?php echo $discount_type_id; ?>">
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Discount Name *</label>
                <input type="text" class="form-control" name="discount_name" value="<?php echo $discount_name; ?>" required>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label>Default Value Type *</label>
                <select class="form-control" name="is_percentage" required>
                    <option value="1" <?php echo $is_percentage == '1' ? 'selected' : ''; ?>>Percentage (%)</option>
                    <option value="0" <?php echo $is_percentage == '0' ? 'selected' : ''; ?>>Fixed Amount</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label>Description</label>
        <textarea class="form-control" name="discount_description" rows="3"><?php echo $discount_description; ?></textarea>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Default Value</label>
                <input type="number" class="form-control" name="default_value" value="<?php echo $default_value; ?>" step="0.01">
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label>Maximum Amount</label>
                <input type="number" class="form-control" name="max_amount" value="<?php echo $max_amount; ?>" step="0.01">
                <small class="text-muted">Leave blank for no limit</small>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="requires_approval" value="1" <?php echo $requires_approval == '1' ? 'checked' : ''; ?>>
                    Requires Approval
                </label>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo $is_active == '1' ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>
        </div>
    </div>
    
    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> <?php echo $edit_mode ? 'Update' : 'Save'; ?>
        </button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
    </div>
<?php echo form_close(); ?>

<script>
$('#discountTypeForm').submit(function(e) {
    e.preventDefault();
    $('.close')[0].click();
    showAjaxModal_alert('Saving...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/save_discount_type"); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
        } else {
            showAjaxModal_alert(response.message || 'Failed to save', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});
</script>
