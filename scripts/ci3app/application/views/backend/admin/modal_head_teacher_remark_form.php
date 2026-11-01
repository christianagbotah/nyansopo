<?php
$is_edit = isset($remark);
$form_id = 'head-teacher-remark-form';
$submit_url = $is_edit ? site_url('admin/head_teacher_remarks/update/' . $remark->id) : site_url('admin/head_teacher_remarks/create');
$modal_title = $is_edit ? get_phrase('edit_remark_range') : get_phrase('add_remark_range');
$icon = $is_edit ? 'pencil' : 'plus-circled';
?>

<!-- Modern Modal Header -->
<div class="modal-header" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;padding:24px 30px;border-radius:12px 12px 0 0;">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="color:#fff;opacity:0.9;font-size:32px;font-weight:300;text-shadow:none;">&times;</button>
    <div style="display:flex;align-items:center;gap:16px;">
        <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="entypo-<?php echo $icon; ?>" style="font-size:28px;color:#fff;"></i>
        </div>
        <div>
            <h4 class="modal-title" style="color:#fff;font-weight:700;margin:0;font-size:22px;letter-spacing:-0.5px;">
                <?php echo $modal_title; ?>
            </h4>
            <p style="color:rgba(255,255,255,0.85);margin:4px 0 0 0;font-size:14px;">
                <?php echo $is_edit ? get_phrase('update_remark_range_details') : get_phrase('create_percentage_based_remark_for_report_cards'); ?>
            </p>
        </div>
    </div>
</div>

<form id="<?php echo $form_id; ?>" method="post" action="<?php echo $submit_url; ?>">
    <!-- CSRF Token -->
    <?php if ($this->security->get_csrf_token_name()): ?>
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
    <?php endif; ?>
    
    <div class="modal-body" style="padding:32px 30px;background:#fafbfc;">
        
        <!-- Form Card -->
        <div style="background:#fff;border-radius:12px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e5e7eb;">
            
            <!-- Percentage Range Row -->
            <div class="row" style="margin-bottom:24px;">
                <!-- Min Percentage Field -->
                <div class="col-md-6">
                    <div class="form-group modern-form-group">
                        <label for="min_percentage" style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:15px;margin-bottom:10px;">
                            <i class="entypo-arrow-down" style="color:#667eea;font-size:18px;"></i>
                            <?php echo get_phrase('min_percentage'); ?> 
                            <span style="color:#ef4444;margin-left:2px;">*</span>
                        </label>
                        <input type="number" 
                               class="form-control modern-input" 
                               id="min_percentage" 
                               name="min_percentage" 
                               placeholder="<?php echo get_phrase('e_g_0_50_75'); ?>"
                               value="<?php echo $is_edit ? $remark->min_percentage : ''; ?>"
                               min="0"
                               max="100"
                               step="0.01"
                               required
                               style="height:48px;border:2px solid #e5e7eb;border-radius:10px;padding:12px 16px;font-size:15px;transition:all 0.2s;background:#fff;">
                        <small style="color:#6b7280;font-size:13px;margin-top:6px;display:block;">
                            <i class="entypo-info-circled" style="margin-right:4px;"></i>
                            <?php echo get_phrase('range_0_to_100'); ?>
                        </small>
                    </div>
                </div>
                
                <!-- Max Percentage Field -->
                <div class="col-md-6">
                    <div class="form-group modern-form-group">
                        <label for="max_percentage" style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:15px;margin-bottom:10px;">
                            <i class="entypo-arrow-up" style="color:#667eea;font-size:18px;"></i>
                            <?php echo get_phrase('max_percentage'); ?> 
                            <span style="color:#ef4444;margin-left:2px;">*</span>
                        </label>
                        <input type="number" 
                               class="form-control modern-input" 
                               id="max_percentage" 
                               name="max_percentage" 
                               placeholder="<?php echo get_phrase('e_g_49_100'); ?>"
                               value="<?php echo $is_edit ? $remark->max_percentage : ''; ?>"
                               min="0"
                               max="100"
                               step="0.01"
                               required
                               style="height:48px;border:2px solid #e5e7eb;border-radius:10px;padding:12px 16px;font-size:15px;transition:all 0.2s;background:#fff;">
                        <small style="color:#6b7280;font-size:13px;margin-top:6px;display:block;">
                            <i class="entypo-info-circled" style="margin-right:4px;"></i>
                            <?php echo get_phrase('range_0_to_100'); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Remark Text Field -->
            <div class="form-group modern-form-group" style="margin-bottom:24px;">
                <label for="remark_text" style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:15px;margin-bottom:10px;">
                    <i class="entypo-doc-text" style="color:#667eea;font-size:18px;"></i>
                    <?php echo get_phrase('remark_text'); ?> 
                    <span style="color:#ef4444;margin-left:2px;">*</span>
                </label>
                <textarea class="form-control modern-input" 
                          id="remark_text" 
                          name="remark_text" 
                          placeholder="<?php echo get_phrase('e_g_excellent_performance_outstanding_achievement'); ?>"
                          rows="3"
                          maxlength="255"
                          required
                          style="border:2px solid #e5e7eb;border-radius:10px;padding:12px 16px;font-size:15px;transition:all 0.2s;background:#fff;resize:vertical;"><?php echo $is_edit ? htmlspecialchars($remark->remark_text) : ''; ?></textarea>
                <small style="color:#6b7280;font-size:13px;margin-top:6px;display:block;">
                    <i class="entypo-info-circled" style="margin-right:4px;"></i>
                    <?php echo get_phrase('this_will_appear_on_student_report_cards'); ?> 
                    (<span id="char_count"><?php echo $is_edit ? strlen($remark->remark_text) : 0; ?></span>/255 <?php echo get_phrase('characters'); ?>)
                </small>
            </div>

            <!-- Display Order Field -->
            <div class="form-group modern-form-group" style="margin-bottom:24px;">
                <label for="display_order" style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:15px;margin-bottom:10px;">
                    <i class="entypo-sort-number-down" style="color:#667eea;font-size:18px;"></i>
                    <?php echo get_phrase('display_order'); ?>
                </label>
                <input type="number" 
                       class="form-control modern-input" 
                       id="display_order" 
                       name="display_order" 
                       placeholder="<?php echo get_phrase('e_g_1_2_3'); ?>"
                       value="<?php echo $is_edit ? $remark->display_order : ''; ?>"
                       min="1"
                       style="height:48px;border:2px solid #e5e7eb;border-radius:10px;padding:12px 16px;font-size:15px;transition:all 0.2s;background:#fff;">
                <small style="color:#6b7280;font-size:13px;margin-top:6px;display:block;">
                    <i class="entypo-info-circled" style="margin-right:4px;"></i>
                    <?php echo get_phrase('leave_blank_to_add_at_the_end_you_can_reorder_later'); ?>
                </small>
            </div>

            <!-- Active Status Toggle -->
            <div class="form-group modern-form-group" style="margin-bottom:0;">
                <label style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:15px;margin-bottom:12px;">
                    <i class="entypo-eye" style="color:#667eea;font-size:18px;"></i>
                    <?php echo get_phrase('visibility_status'); ?>
                </label>
                <div style="background:#f9fafb;border:2px solid #e5e7eb;border-radius:10px;padding:16px 18px;">
                    <label style="display:flex;align-items:center;cursor:pointer;margin:0;">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               <?php echo ($is_edit && $remark->is_active) || !$is_edit ? 'checked' : ''; ?>
                               style="width:22px;height:22px;margin:0 12px 0 0;cursor:pointer;accent-color:#667eea;">
                        <div>
                            <span style="font-weight:600;color:#1f2937;font-size:15px;display:block;margin-bottom:2px;">
                                <?php echo get_phrase('active_range'); ?>
                            </span>
                            <small style="color:#6b7280;font-size:13px;">
                                <?php echo get_phrase('inactive_ranges_are_not_used_for_automatic_remark_assignment'); ?>
                            </small>
                        </div>
                    </label>
                </div>
            </div>

        </div>

        <!-- Validation Errors Display -->
        <div id="remark-form-errors" class="alert" style="display:none;margin-top:20px;border-radius:10px;padding:16px 18px;background:#fee2e2;border:2px solid #fca5a5;color:#991b1b;">
            <i class="entypo-attention" style="margin-right:8px;font-size:18px;"></i>
            <span id="remark-form-errors-text"></span>
        </div>

    </div>

    <div class="modal-footer" style="background:#f9fafb;border-top:2px solid #e5e7eb;padding:20px 30px;border-radius:0 0 12px 12px;display:flex;gap:12px;justify-content:flex-end;">
        <button type="button" class="btn modern-btn-cancel" data-dismiss="modal" style="padding:12px 24px;border-radius:10px;border:2px solid #e5e7eb;background:#fff;color:#6b7280;font-weight:600;font-size:15px;transition:all 0.2s;min-width:110px;">
            <i class="entypo-cancel" style="margin-right:6px;"></i> 
            <?php echo get_phrase('cancel'); ?>
        </button>
        <button type="submit" class="btn modern-btn-primary" id="remark-submit-btn" style="padding:12px 24px;border-radius:10px;border:none;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;font-weight:600;font-size:15px;transition:all 0.2s;box-shadow:0 4px 12px rgba(102,126,234,0.3);min-width:110px;">
            <i class="entypo-<?php echo $is_edit ? 'check' : 'plus'; ?>" style="margin-right:6px;"></i> 
            <?php echo $is_edit ? get_phrase('update') : get_phrase('save'); ?>
        </button>
    </div>
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Style the inputs on focus
    $('.modern-input').on('focus', function() {
        $(this).css({
            'border-color': '#667eea',
            'box-shadow': '0 0 0 3px rgba(102,126,234,0.1)'
        });
    }).on('blur', function() {
        $(this).css({
            'border-color': '#e5e7eb',
            'box-shadow': 'none'
        });
    });
    
    // Hover effects for buttons
    $('.modern-btn-cancel').hover(
        function() {
            $(this).css({
                'background': '#f3f4f6',
                'border-color': '#d1d5db',
                'transform': 'translateY(-1px)'
            });
        },
        function() {
            $(this).css({
                'background': '#fff',
                'border-color': '#e5e7eb',
                'transform': 'translateY(0)'
            });
        }
    );
    
    $('.modern-btn-primary').hover(
        function() {
            $(this).css({
                'transform': 'translateY(-2px)',
                'box-shadow': '0 6px 16px rgba(102,126,234,0.4)'
            });
        },
        function() {
            $(this).css({
                'transform': 'translateY(0)',
                'box-shadow': '0 4px 12px rgba(102,126,234,0.3)'
            });
        }
    );
    
    // Character counter for remark text
    $('#remark_text').on('input', function() {
        var length = $(this).val().length;
        $('#char_count').text(length);
        
        var $small = $(this).siblings('small');
        var color = length > 240 ? '#ef4444' : (length > 200 ? '#f59e0b' : '#6b7280');
        $small.css('color', color);
    });
    
    // Range validation
    $('#min_percentage, #max_percentage').on('blur', function() {
        var min = parseFloat($('#min_percentage').val());
        var max = parseFloat($('#max_percentage').val());
        
        if (min && max && min > max) {
            $('#remark-form-errors-text').html('<?php echo get_phrase('minimum_percentage_cannot_be_greater_than_maximum'); ?>');
            $('#remark-form-errors').fadeIn(300);
        } else {
            $('#remark-form-errors').fadeOut(300);
        }
    });
    
    // Handle form submission via AJAX
    $('#<?php echo $form_id; ?>').submit(function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#remark-submit-btn');
        var $errorDiv = $('#remark-form-errors');
        var $errorText = $('#remark-form-errors-text');
        
        // Validate range
        var min = parseFloat($('#min_percentage').val());
        var max = parseFloat($('#max_percentage').val());
        
        if (min > max) {
            $errorText.html('<?php echo get_phrase('minimum_percentage_cannot_be_greater_than_maximum'); ?>');
            $errorDiv.fadeIn(300);
            $errorDiv[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        
        // Disable submit button with loading state
        $submitBtn.prop('disabled', true).html('<i class="entypo-hourglass"></i> <?php echo get_phrase('saving'); ?>...');
        $errorDiv.hide();
        
        // Show loading modal
        showAjaxModal_alert('<?php echo get_phrase('saving_changes'); ?>...', 'loading', false, false);
        
        // Serialize form data including CSRF token
        var formData = $form.serialize();
        
        console.log('Submitting to:', $form.attr('action'));
        console.log('Form data:', formData);
        
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Success response:', response);
                
                if (response.success) {
                    // Close form modal first
                    $('#modal_ajax').modal('hide');
                    
                    // Show success message WITHOUT reload
                    showAjaxModal_alert(response.message, 'success', false, false);
                    
                    // Reload just the table content via AJAX
                    setTimeout(function() {
                        $.ajax({
                            url: '<?php echo site_url('admin/head_teacher_remarks'); ?>',
                            type: 'GET',
                            success: function(html) {
                                // Extract and update just the table body
                                var newTable = $(html).find('#remarks-table tbody');
                                if (newTable.length) {
                                    $('#remarks-table tbody').html(newTable.html());
                                    
                                    // Re-initialize sortable after content update
                                    $("#remarks-table tbody").sortable('destroy');
                                    $("#remarks-table tbody").sortable({
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
                                            $('#remarks-table tbody tr').each(function(index) {
                                                var id = $(this).find('[data-id]').first().data('id');
                                                if (id) {
                                                    orderData.push({ id: id, order: index + 1 });
                                                }
                                            });
                                            
                                            if (orderData.length > 0) {
                                                $.ajax({
                                                    url: '<?php echo site_url('admin/head_teacher_remarks/reorder'); ?>',
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
                    
                    // Show error message in form modal
                    $errorText.html(response.message);
                    $errorDiv.fadeIn(300);
                    
                    // Restore submit button
                    $submitBtn.prop('disabled', false).html('<i class="entypo-<?php echo $is_edit ? 'check' : 'plus'; ?>"></i> <?php echo $is_edit ? get_phrase('update') : get_phrase('save'); ?>');
                    
                    // Scroll to error
                    $errorDiv[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                console.error('Response Text:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                
                // Hide loading modal
                $('#modal_alert').modal('hide');
                
                var errorMsg = '<?php echo get_phrase('error_saving_remark_range'); ?>';
                
                // Handle different error types
                if (xhr.status === 403) {
                    errorMsg = 'Access Forbidden (403). Please refresh the page and try again.';
                } else if (xhr.status === 404) {
                    errorMsg = 'Page Not Found (404). The form submission URL may be incorrect.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server Error (500). Please contact the administrator.';
                } else {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMsg = response.message;
                        }
                    } catch(e) {
                        errorMsg += ' <?php echo get_phrase('please_check_your_connection_and_try_again'); ?>';
                    }
                }
                
                // Show error in modal
                $errorText.html(errorMsg);
                $errorDiv.fadeIn(300);
                
                // Restore submit button
                $submitBtn.prop('disabled', false).html('<i class="entypo-<?php echo $is_edit ? 'check' : 'plus'; ?>"></i> <?php echo $is_edit ? get_phrase('update') : get_phrase('save'); ?>');
                
                // Also show modal alert for visibility
                showAjaxModal_alert(errorMsg, 'error', false, false);
            }
        });
    });
    
    // Focus on min percentage field when modal opens
    setTimeout(function() {
        $('#min_percentage').focus();
    }, 300);
    
});
</script>

<style>
/* Modern Form Styles */
#<?php echo $form_id; ?> .modern-input:focus {
    outline: none;
}

#<?php echo $form_id; ?> input[type="checkbox"]:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}

/* Prevent button style override */
#remark-submit-btn:hover,
#remark-submit-btn:focus {
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%) !important;
    color: #fff !important;
}

/* Animation for error message */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#remark-form-errors {
    animation: slideDown 0.3s ease-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-header {
        padding: 20px 20px !important;
    }
    
    .modal-header h4 {
        font-size: 18px !important;
    }
    
    .modal-header p {
        font-size: 13px !important;
    }
    
    .modal-body {
        padding: 24px 20px !important;
    }
    
    .modal-body > div {
        padding: 20px !important;
    }
    
    .modal-footer {
        padding: 16px 20px !important;
        flex-direction: column;
    }
    
    .modal-footer button {
        width: 100%;
        margin: 0 !important;
    }
}
</style>
