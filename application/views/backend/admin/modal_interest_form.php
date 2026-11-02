<?php
$is_edit = isset($interest_item);
$form_id = 'interest-item-form';
$submit_url = $is_edit ? site_url('admin/interest_items/edit/' . $interest_item->id) : site_url('admin/interest_items/create');
?>

<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 6px 6px 0 0;">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="color: #fff; opacity: 0.8;">&times;</button>
    <h4 class="modal-title" style="font-weight: 600;">
        <i class="entypo-<?php echo $is_edit ? 'pencil' : 'plus'; ?>"></i>
        <?php echo $is_edit ? get_phrase('edit_interest_item') : get_phrase('add_interest_item'); ?>
    </h4>
</div>

<form id="<?php echo $form_id; ?>" method="post" action="<?php echo $submit_url; ?>">
    <?php if ($this->security->get_csrf_token_name()): ?>
    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
    <?php endif; ?>
    
    <div class="modal-body" style="padding: 24px;">
        
        <!-- Name Field -->
        <div class="form-group">
            <label for="interest_name" style="font-weight: 600; color: #1f2937; font-size: 14px;">
                <?php echo get_phrase('interest_item_name'); ?> <span style="color: #e74c3c;">*</span>
            </label>
            <input type="text" 
                   class="form-control" 
                   id="interest_name" 
                   name="name" 
                   placeholder="<?php echo get_phrase('enter_interest_item_name'); ?>"
                   value="<?php echo $is_edit ? htmlspecialchars($interest_item->name) : ''; ?>"
                   maxlength="100"
                   style="font-size: 14px; padding: 10px 12px; border-radius: 6px;"
                   required>
            <small class="text-muted" style="font-size: 13px;">
                <?php echo get_phrase('max_100_characters'); ?>
            </small>
        </div>

        <!-- Display Order Field -->
        <div class="form-group">
            <label for="interest_display_order" style="font-weight: 600; color: #1f2937; font-size: 14px;">
                <?php echo get_phrase('display_order'); ?>
            </label>
            <input type="number" 
                   class="form-control" 
                   id="interest_display_order" 
                   name="display_order" 
                   placeholder="<?php echo get_phrase('enter_display_order'); ?>"
                   value="<?php echo $is_edit ? $interest_item->display_order : ''; ?>"
                   min="1"
                   style="font-size: 14px; padding: 10px 12px; border-radius: 6px;">
            <small class="text-muted" style="font-size: 13px;">
                <?php echo get_phrase('leave_blank_for_auto_order'); ?>
            </small>
        </div>

        <!-- Active Status Checkbox -->
        <div class="form-group">
            <div class="checkbox">
                <label style="font-size: 14px; font-weight: 500;">
                    <input type="checkbox" 
                           id="interest_is_active" 
                           name="is_active" 
                           value="1"
                           <?php echo ($is_edit && $interest_item->is_active) || !$is_edit ? 'checked' : ''; ?>>
                    <?php echo get_phrase('active'); ?>
                    <small class="text-muted" style="font-size: 13px;">
                        (<?php echo get_phrase('inactive_items_hidden_from_teachers'); ?>)
                    </small>
                </label>
            </div>
        </div>

        <!-- Validation Errors Display -->
        <div id="interest-form-errors" class="alert alert-danger" style="display: none; font-size: 14px; border-radius: 6px;"></div>

    </div>

    <div class="modal-footer" style="background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 24px;">
        <button type="button" class="btn btn-default" data-dismiss="modal" style="font-size: 14px; padding: 8px 16px;">
            <i class="entypo-cancel"></i> <?php echo get_phrase('cancel'); ?>
        </button>
        <button type="submit" class="btn btn-primary" id="interest-submit-btn" style="font-size: 14px; padding: 8px 20px; background: #667eea; border: none;">
            <i class="entypo-check"></i> 
            <?php echo $is_edit ? get_phrase('update') : get_phrase('save'); ?>
        </button>
    </div>
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Handle form submission via AJAX
    $('#<?php echo $form_id; ?>').submit(function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#interest-submit-btn');
        var $errorDiv = $('#interest-form-errors');
        
        // Disable submit button
        $submitBtn.prop('disabled', true).html('<i class="entypo-hourglass"></i> <?php echo get_phrase('saving'); ?>...');
        $errorDiv.hide();
        
        // Show loading modal
        showAjaxModal_alert('<?php echo get_phrase('saving_changes'); ?>...', 'loading', false, false);
        
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Close form modal first
                    $('#modal_ajax').modal('hide');
                    
                    // Show success message WITHOUT reload
                    showAjaxModal_alert(response.message, 'success', false, false);
                    
                    // Reload just the table content via AJAX
                    setTimeout(function() {
                        $.ajax({
                            url: '<?php echo site_url('admin/interest_items'); ?>',
                            type: 'GET',
                            success: function(html) {
                                // Extract and update just the table body
                                var newTable = $(html).find('#interest-items-table tbody');
                                if (newTable.length) {
                                    $('#interest-items-table tbody').html(newTable.html());
                                    
                                    // Re-initialize sortable after content update
                                    $("#interest-items-table tbody").sortable('destroy');
                                    $("#interest-items-table tbody").sortable({
                                        items: 'tr',
                                        cursor: 'move',
                                        opacity: 0.6,
                                        helper: function(e, tr) {
                                            var $originals = tr.children();
                                            var $helper = tr.clone();
                                            $helper.children().each(function(index) {
                                                $(this).width($originals.eq(index).width());
                                            });
                                            return $helper;
                                        },
                                        update: function(event, ui) {
                                            var orderData = [];
                                            $('#interest-items-table tbody tr').each(function(index) {
                                                var id = $(this).find('[data-id]').first().data('id');
                                                if (id) {
                                                    orderData.push({ id: id, order: index + 1 });
                                                }
                                            });
                                            
                                            if (orderData.length > 0) {
                                                $.ajax({
                                                    url: '<?php echo site_url('admin/interest_items/reorder'); ?>',
                                                    type: 'POST',
                                                    data: { order_data: orderData },
                                                    dataType: 'json',
                                                    success: function(response) {
                                                        if (response.success) {
                                                            toastr.success(response.message || '<?php echo get_phrase('order_updated_successfully'); ?>');
                                                        } else {
                                                            toastr.error(response.message || '<?php echo get_phrase('error_updating_order'); ?>');
                                                            location.reload();
                                                        }
                                                    },
                                                    error: function(xhr, status, error) {
                                                        toastr.error('<?php echo get_phrase('error_updating_order'); ?>');
                                                        location.reload();
                                                    }
                                                });
                                            }
                                        }
                                    });
                                }
                                
                                // Close success modal after reload
                                setTimeout(function() {
                                    $('#modal_alert').modal('hide');
                                }, 1500);
                            },
                            error: function() {
                                // Fallback to full page reload if AJAX fails
                                location.reload();
                            }
                        });
                    }, 500);
                } else {
                    // Hide loading modal
                    $('#modal_alert').modal('hide');
                    
                    // Show error message in form
                    $errorDiv.html(response.message).show();
                    $submitBtn.prop('disabled', false).html('<i class="entypo-check"></i> <?php echo $is_edit ? get_phrase('update') : get_phrase('save'); ?>');
                }
            },
            error: function(xhr, status, error) {
                // Hide loading modal
                $('#modal_alert').modal('hide');
                
                var errorMsg = '<?php echo get_phrase('error_saving_interest_item'); ?>';
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch(e) {
                    // Use default error message
                }
                
                $errorDiv.html(errorMsg).show();
                $submitBtn.prop('disabled', false).html('<i class="entypo-check"></i> <?php echo $is_edit ? get_phrase('update') : get_phrase('save'); ?>');
            }
        });
    });
    
    // Focus on name field when modal opens
    $('#interest_name').focus();
    
});
</script>

<style>
.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-primary:hover {
    background: #5568d3 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
</style>
