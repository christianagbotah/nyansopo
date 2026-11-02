<?php
// Provider ID is passed from Modal controller
if (!isset($provider_id)) {
    echo '<div class="alert alert-danger">Provider ID not found</div>';
    return;
}

$provider = $this->db->get_where('pension_tier2_providers', array('provider_id' => $provider_id))->row_array();

if (empty($provider)) {
    echo '<div class="alert alert-danger">' . get_phrase('provider_not_found') . '</div>';
    return;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo base_url('assets/tailwindcss/output.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/fontawesome/6.7.2/css/all.min.css');?>">
    <style>
        /* Modern Card System - Matching Payroll Design */
        .modern-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .modern-card-header {
            padding: 16px 24px;
            font-weight: 700;
            font-size: 16px;
            color: white;
            display: flex;
            align-items: center;
        }

        .gradient-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .gradient-green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .gradient-gray {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }

        .modern-card-body {
            padding: 24px;
        }

        /* Form Grid System - 24px Gaps */
        .provider-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .provider-grid-full {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        /* Form Elements */
        .provider-form-group {
            display: flex;
            flex-direction: column;
        }

        .provider-form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .provider-form-label .required {
            color: #ef4444;
            margin-left: 4px;
        }

        .provider-form-input {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .provider-form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .provider-form-input::placeholder {
            color: #9ca3af;
        }

        .provider-form-textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
            transition: all 0.2s ease;
        }

        .provider-form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Checkbox Styling */
        .provider-checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .provider-checkbox-wrapper:hover {
            background: #f3f4f6;
            border-color: #3b82f6;
        }

        .provider-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #3b82f6;
        }

        .provider-checkbox-label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            user-select: none;
        }

        /* Helper Text */
        .helper-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
            display: block;
        }

        /* Action Buttons */
        .provider-button {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .provider-button-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .provider-button-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .provider-button-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1.5px solid #e5e7eb;
        }

        .provider-button-secondary:hover {
            background: #e5e7eb;
        }

        .provider-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .provider-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .modern-card-body {
                padding: 16px;
            }

            .modern-card-header {
                padding: 12px 16px;
                font-size: 14px;
            }
        }

        /* Modal Styling Override */
        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            background: white;
            border-bottom: none;
            padding: 0;
        }

        .modal-body {
            padding: 0;
            background: #f9fafb;
        }

        /* Section Divider */
        .section-divider {
            border-top: 2px solid #e5e7eb;
            margin: 24px 0;
            padding-top: 24px;
        }
    </style>
</head>
<body>

<div class="bg-gray-50" style="min-height: 400px;">
    <?php echo form_open('admin/pension_providers/update/' . $provider['provider_id'], array('id' => 'editProviderForm', 'class' => 'space-y-6')); ?>
        <input type="hidden" name="provider_id" value="<?php echo $provider['provider_id']; ?>">
        
        <!-- Section 1: Basic Information -->
        <div class="modern-card">
            <div class="modern-card-header gradient-gray">
                <i class="fas fa-info-circle mr-2"></i>Basic Information
            </div>
            <div class="modern-card-body">
                <div class="provider-grid">
                    <div class="provider-form-group">
                        <label class="provider-form-label">
                            <?php echo get_phrase('provider_name'); ?><span class="required">*</span>
                        </label>
                        <input type="text" class="provider-form-input" name="provider_name" value="<?php echo htmlspecialchars($provider['provider_name']); ?>" placeholder="e.g., GLICO Pensions" required>
                    </div>
                    
                    <div class="provider-form-group">
                        <label class="provider-form-label">
                            <?php echo get_phrase('provider_code'); ?><span class="required">*</span>
                        </label>
                        <input type="text" class="provider-form-input" name="provider_code" value="<?php echo htmlspecialchars($provider['provider_code']); ?>" placeholder="e.g., GLICO" required>
                        <small class="helper-text"><?php echo get_phrase('unique_identifier_for_provider'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Contact Details -->
        <div class="modern-card">
            <div class="modern-card-header gradient-blue">
                <i class="fas fa-address-book mr-2"></i>Contact Information
            </div>
            <div class="modern-card-body">
                <div class="provider-grid">
                    <div class="provider-form-group">
                        <label class="provider-form-label">
                            <i class="fas fa-envelope mr-1"></i><?php echo get_phrase('provider_email'); ?>
                        </label>
                        <input type="email" class="provider-form-input" name="provider_email" value="<?php echo htmlspecialchars($provider['provider_email']); ?>" placeholder="contact@provider.com">
                    </div>
                    
                    <div class="provider-form-group">
                        <label class="provider-form-label">
                            <i class="fas fa-phone mr-1"></i><?php echo get_phrase('provider_phone'); ?>
                        </label>
                        <input type="text" class="provider-form-input" name="provider_phone" value="<?php echo htmlspecialchars($provider['provider_phone']); ?>" placeholder="+233 XX XXX XXXX">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Additional Details -->
        <div class="modern-card">
            <div class="modern-card-header gradient-green">
                <i class="fas fa-file-alt mr-2"></i>Additional Details
            </div>
            <div class="modern-card-body">
                <div class="provider-grid-full">
                    <!-- Active Status -->
                    <div class="provider-form-group">
                        <label class="provider-form-label">
                            <?php echo get_phrase('status'); ?>
                        </label>
                        <label class="provider-checkbox-wrapper">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($provider['is_active'] == 1) ? 'checked' : ''; ?> class="provider-checkbox">
                            <span class="provider-checkbox-label">
                                <i class="fas fa-check-circle text-green-600 mr-1"></i><?php echo get_phrase('active_provider'); ?>
                            </span>
                        </label>
                        <small class="helper-text">Only active providers will appear in payroll dropdowns</small>
                    </div>
                    
                    <!-- Description -->
                    <div class="provider-form-group">
                        <label class="provider-form-label">
                            <?php echo get_phrase('description'); ?>
                        </label>
                        <textarea class="provider-form-textarea" name="description" rows="3" placeholder="Enter provider details, notes, or additional information..."><?php echo htmlspecialchars($provider['description']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="modern-card">
            <div class="modern-card-body">
                <div class="flex flex-col sm:flex-row gap-3 justify-end section-divider">
                    <button type="button" class="provider-button provider-button-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i>
                        <?php echo get_phrase('cancel'); ?>
                    </button>
                    <button type="submit" class="provider-button provider-button-primary">
                        <i class="fas fa-save"></i>
                        <?php echo get_phrase('update_provider'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php echo form_close(); ?>
</div>

</body>
</html>

<script type="text/javascript">
$(document).ready(function() {
    $('#editProviderForm').on('submit', function(e) {
        e.preventDefault();
        
        var providerId = $('input[name="provider_id"]').val();
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        $.ajax({
            url: '<?php echo site_url("admin/pension_providers/update/"); ?>' + providerId,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo get_phrase("updating"); ?>...');
            },
            success: function(response) {
                if (response.message == 'done') {
                    $('#modal_ajax').modal('hide');
                    showAjaxModal_alert('<?php echo get_phrase("provider_updated_successfully"); ?>', 'Success', false);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAjaxModal_alert(response.errors || '<?php echo get_phrase("error_updating_provider"); ?>', 'Error', false);
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showAjaxModal_alert('<?php echo get_phrase("error_updating_provider"); ?>', 'Error', false);
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>


<script type="text/javascript">
$(document).ready(function() {
    $('#editProviderForm').on('submit', function(e) {
        e.preventDefault();
        
        var providerId = $('input[name="provider_id"]').val();
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        $.ajax({
            url: '<?php echo site_url("admin/pension_providers/update/"); ?>' + providerId,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo get_phrase("updating"); ?>...');
            },
            success: function(response) {
                if (response.message == 'done') {
                    $('#modal_ajax').modal('hide');
                    showAjaxModal_alert('<?php echo get_phrase("provider_updated_successfully"); ?>', 'Success', false);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAjaxModal_alert(response.errors || '<?php echo get_phrase("error_updating_provider"); ?>', 'Error', false);
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showAjaxModal_alert('<?php echo get_phrase("error_updating_provider"); ?>', 'Error', false);
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
