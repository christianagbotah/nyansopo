<?php
/**
 * Manual Merge Conflict Modal
 * 
 * Modal for manually merging conflicting data between local and remote versions.
 * Displays editable fields with current values from both versions and highlights
 * conflicting fields.
 * 
 * Requirements: 8.7, 4.4, 4.5, 4.6
 * Task 5.4: Manual merge modal
 */

// Get conflict data from controller
$conflict = $conflict ?? [];
$conflict_id = $conflict['id'] ?? 0;
$table_name = $conflict['table_name'] ?? '';
$record_id = $conflict['record_id'] ?? 0;
$local_data = json_decode($conflict['local_data'] ?? '{}', true);
$remote_data = json_decode($conflict['remote_data'] ?? '{}', true);

// Merge all fields from both versions
$all_fields = array_unique(array_merge(array_keys($local_data), array_keys($remote_data)));
?>

<div class="sync-modal-content modern-modal-content">
    <div class="sync-modal-header modern-modal-header">
        <h4 class="modal-title">
            <i class="fa fa-code-branch"></i>
            Manual Merge Conflict
        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    
    <div class="sync-modal-body">
        <div class="sync-merge-info">
            <div class="sync-merge-info-item">
                <strong>Table:</strong> <?php echo htmlspecialchars($table_name); ?>
            </div>
            <div class="sync-merge-info-item">
                <strong>Record ID:</strong> #<?php echo $record_id; ?>
            </div>
            <div class="sync-merge-info-item">
                <strong>Conflicting Fields:</strong> 
                <?php 
                $diff_count = 0;
                foreach ($all_fields as $field) {
                    $local_val = $local_data[$field] ?? null;
                    $remote_val = $remote_data[$field] ?? null;
                    if ($local_val != $remote_val) {
                        $diff_count++;
                    }
                }
                echo $diff_count;
                ?>
            </div>
        </div>
        
        <div class="sync-merge-instructions">
            <i class="fa fa-info-circle"></i>
            <p>Review each field below and select the value you want to keep. Fields with conflicts are highlighted in yellow.</p>
        </div>
        
        <form id="merge-conflict-form" data-conflict-id="<?php echo $conflict_id; ?>">
            <div class="sync-merge-fields">
                <?php foreach ($all_fields as $field): ?>
                    <?php 
                    $local_val = $local_data[$field] ?? null;
                    $remote_val = $remote_data[$field] ?? null;
                    $has_conflict = $local_val != $remote_val;
                    $field_id = 'field_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $field);
                    ?>
                    
                    <div class="sync-merge-field <?php echo $has_conflict ? 'sync-merge-field-conflict' : ''; ?>">
                        <div class="sync-merge-field-header">
                            <label for="<?php echo $field_id; ?>">
                                <?php echo htmlspecialchars($field); ?>
                                <?php if ($has_conflict): ?>
                                    <span class="sync-conflict-indicator">
                                        <i class="fa fa-exclamation-triangle"></i> Conflict
                                    </span>
                                <?php endif; ?>
                            </label>
                        </div>
                        
                        <div class="sync-merge-field-options">
                            <!-- Local Version Option -->
                            <div class="sync-merge-option">
                                <div class="sync-merge-option-header">
                                    <input type="radio" 
                                           name="<?php echo $field; ?>" 
                                           id="<?php echo $field_id; ?>_local" 
                                           value="local"
                                           <?php echo !$has_conflict || $local_val !== null ? 'checked' : ''; ?>>
                                    <label for="<?php echo $field_id; ?>_local">
                                        <i class="fa fa-laptop"></i> Local Version
                                    </label>
                                </div>
                                <div class="sync-merge-option-value">
                                    <input type="text" 
                                           class="form-control" 
                                           id="<?php echo $field_id; ?>_local_value"
                                           value="<?php echo htmlspecialchars($local_val ?? ''); ?>"
                                           data-original="<?php echo htmlspecialchars($local_val ?? ''); ?>">
                                </div>
                            </div>
                            
                            <!-- Remote Version Option -->
                            <div class="sync-merge-option">
                                <div class="sync-merge-option-header">
                                    <input type="radio" 
                                           name="<?php echo $field; ?>" 
                                           id="<?php echo $field_id; ?>_remote" 
                                           value="remote"
                                           <?php echo $has_conflict && $remote_val !== null && $local_val === null ? 'checked' : ''; ?>>
                                    <label for="<?php echo $field_id; ?>_remote">
                                        <i class="fa fa-cloud"></i> Remote Version
                                    </label>
                                </div>
                                <div class="sync-merge-option-value">
                                    <input type="text" 
                                           class="form-control" 
                                           id="<?php echo $field_id; ?>_remote_value"
                                           value="<?php echo htmlspecialchars($remote_val ?? ''); ?>"
                                           data-original="<?php echo htmlspecialchars($remote_val ?? ''); ?>">
                                </div>
                            </div>
                            
                            <!-- Custom Value Option -->
                            <div class="sync-merge-option">
                                <div class="sync-merge-option-header">
                                    <input type="radio" 
                                           name="<?php echo $field; ?>" 
                                           id="<?php echo $field_id; ?>_custom" 
                                           value="custom">
                                    <label for="<?php echo $field_id; ?>_custom">
                                        <i class="fa fa-edit"></i> Custom Value
                                    </label>
                                </div>
                                <div class="sync-merge-option-value">
                                    <input type="text" 
                                           class="form-control" 
                                           id="<?php echo $field_id; ?>_custom_value"
                                           placeholder="Enter custom value...">
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="merged_<?php echo $field; ?>" id="merged_<?php echo $field; ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
    
    <div class="sync-modal-footer">
        <button type="button" class="sync-btn sync-btn-secondary" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancel
        </button>
        <button type="button" class="sync-btn sync-btn-primary" id="save-merge-btn">
            <i class="fa fa-save"></i> Save Merged Data
        </button>
    </div>
</div>

<style>
/* Modal-specific styles */
.sync-merge-info {
    display: flex;
    gap: var(--spacing-xl);
    padding: var(--spacing-lg);
    background: var(--color-gray-50);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-xl);
    flex-wrap: wrap;
}

.sync-merge-info-item {
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
}

.sync-merge-info-item strong {
    color: var(--color-gray-900);
    margin-right: var(--spacing-xs);
}

.sync-merge-instructions {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
    background: var(--color-info-light);
    border-left: 4px solid var(--color-info);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-xl);
}

.sync-merge-instructions i {
    color: var(--color-info-dark);
    font-size: 20px;
    flex-shrink: 0;
}

.sync-merge-instructions p {
    margin: 0;
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
}

.sync-merge-fields {
    max-height: 500px;
    overflow-y: auto;
    padding-right: var(--spacing-sm);
}

.sync-merge-field {
    margin-bottom: var(--spacing-xl);
    padding: var(--spacing-lg);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-md);
    background: white;
}

.sync-merge-field-conflict {
    background: #fef3c7;
    border-color: #f59e0b;
}

.sync-merge-field-header {
    margin-bottom: var(--spacing-md);
}

.sync-merge-field-header label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0;
}

.sync-conflict-indicator {
    font-size: var(--font-size-xs);
    color: var(--color-warning-dark);
    font-weight: var(--font-weight-normal);
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.sync-merge-field-options {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.sync-merge-option {
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    background: white;
    transition: all var(--transition-fast);
}

.sync-merge-option:has(input[type="radio"]:checked) {
    border-color: var(--theme-primary, #667eea);
    background: rgba(102, 126, 234, 0.05);
}

.sync-merge-option-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
}

.sync-merge-option-header input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.sync-merge-option-header label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-gray-700);
    cursor: pointer;
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.sync-merge-option-value {
    margin-left: 26px;
}

.sync-merge-option-value .form-control {
    font-size: var(--font-size-sm);
    padding: var(--spacing-sm) var(--spacing-md);
}

.sync-merge-option-value .form-control:disabled {
    background: var(--color-gray-100);
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
    .sync-merge-info {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .sync-modal-footer {
        flex-direction: column;
    }
    
    .sync-modal-footer .sync-btn {
        width: 100%;
    }
}
</style>

<script>
(function($) {
    'use strict';
    
    // Enable/disable input fields based on radio selection
    $('input[type="radio"]').on('change', function() {
        const fieldName = $(this).attr('name');
        const selectedValue = $(this).val();
        
        // Disable all inputs for this field
        $(`input[name="${fieldName}"]`).each(function() {
            const optionValue = $(this).val();
            const inputField = $(`#${$(this).attr('id')}_value`);
            
            if (optionValue === selectedValue) {
                inputField.prop('disabled', false);
            } else {
                inputField.prop('disabled', true);
            }
        });
    });
    
    // Initialize: disable non-selected inputs
    $('input[type="radio"]:checked').each(function() {
        $(this).trigger('change');
    });
    
    // Save merged data
    $('#save-merge-btn').on('click', function() {
        const conflictId = $('#merge-conflict-form').data('conflict-id');
        const mergedData = {};
        
        // Collect merged values
        $('.sync-merge-field').each(function() {
            const fieldName = $(this).find('input[type="radio"]').first().attr('name');
            const selectedOption = $(this).find('input[type="radio"]:checked').val();
            
            let value = '';
            if (selectedOption === 'local') {
                value = $(this).find(`#field_${fieldName.replace(/[^a-zA-Z0-9_]/g, '_')}_local_value`).val();
            } else if (selectedOption === 'remote') {
                value = $(this).find(`#field_${fieldName.replace(/[^a-zA-Z0-9_]/g, '_')}_remote_value`).val();
            } else if (selectedOption === 'custom') {
                value = $(this).find(`#field_${fieldName.replace(/[^a-zA-Z0-9_]/g, '_')}_custom_value`).val();
            }
            
            mergedData[fieldName] = value;
        });
        
        // Disable button and show loading
        $(this).prop('disabled', true).addClass('sync-btn-loading');
        
        // Send AJAX request
        $.ajax({
            url: base_url + 'admin/sync_conflicts/merge_manual',
            method: 'POST',
            data: {
                conflict_id: conflictId,
                merged_data: JSON.stringify(mergedData)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAjaxModal_alert('Conflict resolved successfully with merged data!', 'success');
                    $('#modal').modal('hide');
                    
                    // Refresh conflicts list
                    if (typeof refreshConflictsList === 'function') {
                        refreshConflictsList();
                    }
                } else {
                    showAjaxModal_alert(response.message || 'Failed to merge conflict. Please try again.', 'danger');
                    $('#save-merge-btn').prop('disabled', false).removeClass('sync-btn-loading');
                }
            },
            error: function() {
                showAjaxModal_alert('Network error. Please check your connection and try again.', 'danger');
                $('#save-merge-btn').prop('disabled', false).removeClass('sync-btn-loading');
            }
        });
    });
    
})(jQuery);
</script>
