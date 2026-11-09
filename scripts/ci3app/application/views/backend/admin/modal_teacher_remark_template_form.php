<?php
$is_edit = isset($template);
$form_id = 'teacher-remark-template-form';
$submit_url = $is_edit ? site_url('teacher_remarks_templates/update/' . $template->id) : site_url('teacher_remarks_templates/create');
$modal_title = $is_edit ? get_phrase('edit_remark_template') : get_phrase('add_remark_template');
$icon = $is_edit ? 'pencil' : 'plus-circled';
?>

<!-- Modern Modal Header -->
<div class="modal-header" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;padding:20px 24px;border-radius:8px 8px 0 0;">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="color:#fff;opacity:0.9;font-size:28px;font-weight:300;text-shadow:none;">&times;</button>
    <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="entypo-<?php echo $icon; ?>" style="font-size:24px;color:#fff;"></i>
        </div>
        <div>
            <h4 class="modal-title" style="color:#fff;font-weight:700;margin:0;font-size:18px;letter-spacing:-0.5px;">
                <?php echo $modal_title; ?>
            </h4>
            <p style="color:rgba(255,255,255,0.85);margin:4px 0 0 0;font-size:13px;">
                <?php echo $is_edit ? get_phrase('update_remark_template_details') : get_phrase('create_selectable_remark_for_teachers'); ?>
            </p>
        </div>
    </div>
</div>

<form id="<?php echo $form_id; ?>" method="post" action="<?php echo $submit_url; ?>">
    <!-- CSRF Token -->
    <?php if ($this->security->get_csrf_token_name()): ?>
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
    <?php endif; ?>
    
    <div class="modal-body" style="padding:24px;background:#fafbfc;">
        
        <!-- Form Card -->
        <div style="background:#fff;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e5e7eb;">
            
            <!-- Remark Text Field -->
            <div class="form-group modern-form-group" style="margin-bottom:20px;">
                <label for="remark_text" style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:14px;margin-bottom:8px;">
                    <i class="entypo-doc-text" style="color:#667eea;font-size:16px;"></i>
                    <?php echo get_phrase('remark_text'); ?> 
                    <span style="color:#ef4444;margin-left:2px;">*</span>
                </label>
                <textarea class="form-control modern-input" 
                          id="remark_text" 
                          name="remark_text" 
                          placeholder="<?php echo get_phrase('e_g_excellent_student_shows_great_improvement'); ?>"
                          rows="3"
                          required
                          style="border:2px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:14px;transition:all 0.2s;background:#fff;resize:vertical;"><?php echo $is_edit ? htmlspecialchars($template->remark_text) : ''; ?></textarea>
                <small style="color:#6b7280;font-size:12px;margin-top:4px;display:block;">
                    <i class="entypo-info-circled" style="margin-right:4px;"></i>
                    <?php echo get_phrase('teachers_will_select_this_from_dropdown'); ?>
                </small>
            </div>

            <!-- Category and Order Row -->
            <div class="row" style="margin-bottom:20px;">
                <!-- Category Field -->
                <div class="col-md-6">
                    <div class="form-group modern-form-group">
                        <label for="category" style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:14px;margin-bottom:8px;">
                            <i class="entypo-tag" style="color:#667eea;font-size:16px;"></i>
                            <?php echo get_phrase('category'); ?>
                        </label>
                        <select class="form-control modern-input" 
                                id="category" 
                                name="category"
                                style="height:40px;border:2px solid #e5e7eb;border-radius:8px;padding:8px 14px;font-size:14px;transition:all 0.2s;background:#fff;">
                            <option value=""><?php echo get_phrase('no_category'); ?></option>
                            <option value="positive" <?php echo ($is_edit && $template->category == 'positive') ? 'selected' : ''; ?>><?php echo get_phrase('positive'); ?></option>
                            <option value="neutral" <?php echo ($is_edit && $template->category == 'neutral') ? 'selected' : ''; ?>><?php echo get_phrase('neutral'); ?></option>
                            <option value="negative" <?php echo ($is_edit && $template->category == 'negative') ? 'selected' : ''; ?>><?php echo get_phrase('negative'); ?></option>
                        </select>
                        <small style="color:#6b7280;font-size:12px;margin-top:4px;display:block;">
                            <i class="entypo-info-circled" style="margin-right:4px;"></i>
                            <?php echo get_phrase('helps_organize_templates'); ?>
                        </small>
                    </div>
                </div>
                
                <!-- Display Order Field -->
                <div class="col-md-6">
                    <div class="form-group modern-form-group">
                        <label for="display_order" style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:14px;margin-bottom:8px;">
                            <i class="entypo-sort-number-down" style="color:#667eea;font-size:16px;"></i>
                            <?php echo get_phrase('display_order'); ?>
                        </label>
                        <input type="number" 
                               class="form-control modern-input" 
                               id="display_order" 
                               name="display_order" 
                               placeholder="<?php echo get_phrase('e_g_1_2_3'); ?>"
                               value="<?php echo $is_edit ? $template->display_order : ''; ?>"
                               min="1"
                               style="height:40px;border:2px solid #e5e7eb;border-radius:8px;padding:8px 14px;font-size:14px;transition:all 0.2s;background:#fff;">
                        <small style="color:#6b7280;font-size:12px;margin-top:4px;display:block;">
                            <i class="entypo-info-circled" style="margin-right:4px;"></i>
                            <?php echo get_phrase('leave_blank_for_auto'); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Active Status Toggle -->
            <div class="form-group modern-form-group" style="margin-bottom:0;">
                <label style="display:flex;align-items:center;gap:8px;font-weight:600;color:#1f2937;font-size:14px;margin-bottom:10px;">
                    <i class="entypo-eye" style="color:#667eea;font-size:16px;"></i>
                    <?php echo get_phrase('visibility_status'); ?>
                </label>
                <div style="background:#f9fafb;border:2px solid #e5e7eb;border-radius:8px;padding:12px 14px;">
                    <label style="display:flex;align-items:center;cursor:pointer;margin:0;">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               <?php echo ($is_edit && $template->is_active) || !$is_edit ? 'checked' : ''; ?>
                               style="width:20px;height:20px;margin:0 10px 0 0;cursor:pointer;accent-color:#667eea;">
                        <div>
                            <span style="font-weight:600;color:#1f2937;font-size:14px;display:block;margin-bottom:2px;">
                                <?php echo get_phrase('active_template'); ?>
                            </span>
                            <small style="color:#6b7280;font-size:12px;">
                                <?php echo get_phrase('inactive_templates_hidden_from_teachers'); ?>
                            </small>
                        </div>
                    </label>
                </div>
            </div>

        </div>

        <!-- Validation Errors Display -->
        <div id="template-form-errors" class="alert" style="display:none;margin-top:16px;border-radius:8px;padding:12px 14px;background:#fee2e2;border:2px solid #fca5a5;color:#991b1b;">
            <i class="entypo-attention" style="margin-right:8px;font-size:16px;"></i>
            <span id="template-form-errors-text"></span>
        </div>

    </div>

    <div class="modal-footer" style="background:#f9fafb;border-top:2px solid #e5e7eb;padding:16px 24px;border-radius:0 0 8px 8px;display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn modern-btn-cancel" data-dismiss="modal" style="padding:10px 20px;border-radius:8px;border:2px solid #e5e7eb;background:#fff;color:#6b7280;font-weight:600;font-size:14px;transition:all 0.2s;min-width:100px;">
            <i class="entypo-cancel" style="margin-right:6px;"></i> 
            <?php echo get_phrase('cancel'); ?>
        </button>
        <button type="submit" class="btn modern-btn-primary" id="template-submit-btn" style="padding:10px 20px;border-radius:8px;border:none;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;font-weight:600;font-size:14px;transition:all 0.2s;box-shadow:0 4px 12px rgba(102,126,234,0.3);min-width:100px;">
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
    
    // Handle form submission via AJAX
    $('#<?php echo $form_id; ?>').submit(function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#template-submit-btn');
        var $errorDiv = $('#template-form-errors');
        var $errorText = $('#template-form-errors-text');
        
        // Disable submit button with loading state
        $submitBtn.prop('disabled', true).html('<i class="entypo-hourglass"></i> <?php echo get_phrase('saving'); ?>...');
        $errorDiv.hide();
        
        // Serialize form data
        var formData = $form.serialize();
        
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close form modal first
                    $('#modal_ajax').modal('hide');
                    
                    // Show success alert modal
                    showAjaxModal_alert(response.message, 'success', false, false);
                    
                    // Reload table data via AJAX (using parent window function)
                    if (typeof window.parent.loadTable === 'function') {
                        window.parent.loadTable();
                    } else if (typeof loadTable === 'function') {
                        loadTable();
                    }
                    
                    // Close success modal after 1.5 seconds
                    setTimeout(function() {
                        $('#modal_alert').modal('hide');
                    }, 1500);
                } else {
                    // Show error message in form modal
                    $errorText.html(response.message);
                    $errorDiv.fadeIn(300);
                    
                    // Restore submit button
                    $submitBtn.prop('disabled', false).html('<i class="entypo-<?php echo $is_edit ? 'check' : 'plus'; ?>"></i> <?php echo $is_edit ? get_phrase('update') : get_phrase('save'); ?>');
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = '<?php echo get_phrase('error_saving_template'); ?>';
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch(e) {
                    errorMsg += ' <?php echo get_phrase('please_try_again'); ?>';
                }
                
                // Show error in modal
                $errorText.html(errorMsg);
                $errorDiv.fadeIn(300);
                
                // Restore submit button
                $submitBtn.prop('disabled', false).html('<i class="entypo-<?php echo $is_edit ? 'check' : 'plus'; ?>"></i> <?php echo $is_edit ? get_phrase('update') : get_phrase('save'); ?>');
            }
        });
    });
    
    // Focus on remark text field when modal opens
    setTimeout(function() {
        $('#remark_text').focus();
    }, 300);
    
});
</script>

<style>
#<?php echo $form_id; ?> .modern-input:focus {
    outline: none;
}

#<?php echo $form_id; ?> input[type="checkbox"]:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}

#template-submit-btn:hover,
#template-submit-btn:focus {
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%) !important;
    color: #fff !important;
}

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

#template-form-errors {
    animation: slideDown 0.3s ease-out;
}

@media (max-width: 768px) {
    .modal-header {
        padding: 20px 20px !important;
    }
    
    .modal-header h4 {
        font-size: 18px !important;
    }
    
    .modal-body {
        padding: 24px 20px !important;
    }
    
    .modal-footer {
        padding: 16px 20px !important;
        flex-direction: column;
    }
    
    .modal-footer button {
        width: 100%;
    }
}
</style>
