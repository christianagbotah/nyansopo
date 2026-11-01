<?php
/**
 * Sync Conflict Merge Interface
 * 
 * Provides a dedicated interface for field-by-field conflict resolution
 * with preview and validation capabilities.
 * 
 * @package    School Manager
 * @subpackage Views
 * @category   Sync
 */
?>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-success">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-code-fork"></i> <?php echo get_phrase('merge_conflict'); ?>
                    <a href="<?php echo site_url('sync_conflicts/view/' . $conflict->id); ?>" class="btn btn-default btn-sm pull-right">
                        <i class="fa fa-arrow-left"></i> <?php echo get_phrase('back_to_conflict'); ?>
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <!-- Conflict Info Summary -->
                <div class="alert alert-info">
                    <h4><i class="fa fa-info-circle"></i> <?php echo get_phrase('merge_instructions'); ?></h4>
                    <p><?php echo get_phrase('select_value_for_each_field_or_enter_custom'); ?></p>
                    <ul>
                        <li><?php echo get_phrase('click_left_arrow_to_use_local_value'); ?></li>
                        <li><?php echo get_phrase('click_right_arrow_to_use_remote_value'); ?></li>
                        <li><?php echo get_phrase('manually_edit_field_for_custom_value'); ?></li>
                    </ul>
                </div>

                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-6">
                        <div class="well">
                            <h4><i class="fa fa-server"></i> <?php echo get_phrase('local_version'); ?></h4>
                            <p>
                                <strong><?php echo get_phrase('location'); ?>:</strong> 
                                <?php echo $local_location ? $local_location->location_name : $conflict->local_device_id; ?>
                            </p>
                            <p>
                                <strong><?php echo get_phrase('modified'); ?>:</strong> 
                                <?php echo date('M d, Y H:i:s', strtotime($conflict->local_modified_at)); ?>
                            </p>
                            <p>
                                <strong><?php echo get_phrase('version'); ?>:</strong> 
                                <?php echo $conflict->local_version; ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="well">
                            <h4><i class="fa fa-cloud"></i> <?php echo get_phrase('remote_version'); ?></h4>
                            <p>
                                <strong><?php echo get_phrase('location'); ?>:</strong> 
                                <?php echo $remote_location ? $remote_location->location_name : $conflict->remote_device_id; ?>
                            </p>
                            <p>
                                <strong><?php echo get_phrase('modified'); ?>:</strong> 
                                <?php echo date('M d, Y H:i:s', strtotime($conflict->remote_modified_at)); ?>
                            </p>
                            <p>
                                <strong><?php echo get_phrase('version'); ?>:</strong> 
                                <?php echo $conflict->remote_version; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Merge Form -->
                <form id="mergeForm" onsubmit="return false;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr class="bg-primary">
                                    <th width="200"><?php echo get_phrase('field_name'); ?></th>
                                    <th width="35%"><?php echo get_phrase('local_value'); ?></th>
                                    <th width="35%"><?php echo get_phrase('remote_value'); ?></th>
                                    <th><?php echo get_phrase('merged_value'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $primary_key = $this->get_primary_key($conflict->table_name);
                                foreach ($diff as $field => $values): 
                                    // Skip primary key
                                    if ($field === $primary_key) continue;
                                ?>
                                <tr class="<?php echo $values['is_different'] ? 'warning' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($field); ?></strong>
                                        <?php if ($values['is_different']): ?>
                                            <br><span class="label label-warning">
                                                <i class="fa fa-exclamation-triangle"></i> <?php echo get_phrase('different'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="bg-warning">
                                        <div class="merge-value-display">
                                            <?php echo $this->format_merge_value($values['local']); ?>
                                        </div>
                                        <?php if ($values['is_different']): ?>
                                        <button type="button" class="btn btn-warning btn-xs btn-block" 
                                                onclick="selectValue('<?php echo htmlspecialchars($field, ENT_QUOTES); ?>', 'local')"
                                                style="margin-top: 5px;">
                                            <i class="fa fa-arrow-right"></i> <?php echo get_phrase('use_this'); ?>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="bg-info">
                                        <div class="merge-value-display">
                                            <?php echo $this->format_merge_value($values['remote']); ?>
                                        </div>
                                        <?php if ($values['is_different']): ?>
                                        <button type="button" class="btn btn-info btn-xs btn-block" 
                                                onclick="selectValue('<?php echo htmlspecialchars($field, ENT_QUOTES); ?>', 'remote')"
                                                style="margin-top: 5px;">
                                            <i class="fa fa-arrow-left"></i> <?php echo get_phrase('use_this'); ?>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($this->is_text_field($field, $values['local'])): ?>
                                            <textarea class="form-control merge-field" 
                                                      id="merge_<?php echo htmlspecialchars($field); ?>" 
                                                      name="<?php echo htmlspecialchars($field); ?>"
                                                      rows="2"
                                                      data-local="<?php echo htmlspecialchars($values['local'], ENT_QUOTES); ?>"
                                                      data-remote="<?php echo htmlspecialchars($values['remote'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($values['local']); ?></textarea>
                                        <?php else: ?>
                                            <input type="text" class="form-control merge-field" 
                                                   id="merge_<?php echo htmlspecialchars($field); ?>" 
                                                   name="<?php echo htmlspecialchars($field); ?>"
                                                   value="<?php echo htmlspecialchars($values['local']); ?>"
                                                   data-local="<?php echo htmlspecialchars($values['local'], ENT_QUOTES); ?>"
                                                   data-remote="<?php echo htmlspecialchars($values['remote'], ENT_QUOTES); ?>">
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <i class="fa fa-info-circle"></i> <?php echo get_phrase('edit_manually_or_select_from_above'); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Preview Section -->
                    <div class="panel panel-default" style="margin-top: 20px;">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <i class="fa fa-eye"></i> <?php echo get_phrase('merged_record_preview'); ?>
                                <button type="button" class="btn btn-primary btn-xs pull-right" onclick="updatePreview()">
                                    <i class="fa fa-refresh"></i> <?php echo get_phrase('refresh_preview'); ?>
                                </button>
                            </h4>
                        </div>
                        <div class="panel-body">
                            <div id="mergePreview" class="well">
                                <p class="text-muted">
                                    <i class="fa fa-info-circle"></i> <?php echo get_phrase('click_refresh_to_see_preview'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Validation Messages -->
                    <div id="validationMessages" style="display: none;">
                        <div class="alert alert-danger">
                            <h4><i class="fa fa-exclamation-triangle"></i> <?php echo get_phrase('validation_errors'); ?></h4>
                            <ul id="validationList"></ul>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="button" class="btn btn-success btn-lg" onclick="validateAndSubmit()">
                            <i class="fa fa-check"></i> <?php echo get_phrase('apply_merged_version'); ?>
                        </button>
                        <button type="button" class="btn btn-default btn-lg" onclick="resetForm()">
                            <i class="fa fa-undo"></i> <?php echo get_phrase('reset_to_local'); ?>
                        </button>
                        <button type="button" class="btn btn-warning btn-lg" onclick="useAllLocal()">
                            <i class="fa fa-arrow-left"></i> <?php echo get_phrase('use_all_local'); ?>
                        </button>
                        <button type="button" class="btn btn-info btn-lg" onclick="useAllRemote()">
                            <i class="fa fa-arrow-right"></i> <?php echo get_phrase('use_all_remote'); ?>
                        </button>
                        <a href="<?php echo site_url('sync_conflicts/view/' . $conflict->id); ?>" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> <?php echo get_phrase('cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.merge-value-display {
    padding: 8px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-height: 40px;
    word-wrap: break-word;
}
.merge-field {
    border: 2px solid #5cb85c;
}
.merge-field:focus {
    border-color: #4cae4c;
    box-shadow: 0 0 8px rgba(92, 184, 92, 0.6);
}
.merge-field.modified {
    border-color: #f0ad4e;
    background-color: #fcf8e3;
}
</style>

<script>
var conflictId = <?php echo $conflict->id; ?>;
var localData = <?php echo json_encode($conflict->local_data); ?>;
var remoteData = <?php echo json_encode($conflict->remote_data); ?>;

// Select a value from local or remote
function selectValue(field, source) {
    var $field = $('#merge_' + field);
    var value = source === 'local' ? $field.data('local') : $field.data('remote');
    $field.val(value);
    $field.addClass('modified');
    
    // Show feedback
    showToast(source === 'local' ? 'Local value selected' : 'Remote value selected', 'success');
}

// Use all local values
function useAllLocal() {
    $('.merge-field').each(function() {
        var $field = $(this);
        $field.val($field.data('local'));
        $field.removeClass('modified');
    });
    showToast('All fields set to local values', 'success');
}

// Use all remote values
function useAllRemote() {
    $('.merge-field').each(function() {
        var $field = $(this);
        $field.val($field.data('remote'));
        $field.addClass('modified');
    });
    showToast('All fields set to remote values', 'success');
}

// Reset form to local values
function resetForm() {
    if (!confirm('Reset all fields to local values?')) {
        return;
    }
    useAllLocal();
}

// Update preview
function updatePreview() {
    var mergedData = getFormData();
    var html = '<table class="table table-condensed table-bordered">';
    
    for (var field in mergedData) {
        html += '<tr>';
        html += '<th width="200">' + field + '</th>';
        html += '<td>' + (mergedData[field] || '<span class="text-muted">NULL</span>') + '</td>';
        html += '</tr>';
    }
    
    html += '</table>';
    $('#mergePreview').html(html);
}

// Get form data as object
function getFormData() {
    var data = {};
    $('.merge-field').each(function() {
        var $field = $(this);
        data[$field.attr('name')] = $field.val();
    });
    return data;
}

// Validate form
function validateForm() {
    var errors = [];
    var mergedData = getFormData();
    
    // Check for required fields (basic validation)
    $('.merge-field[required]').each(function() {
        var $field = $(this);
        if (!$field.val().trim()) {
            errors.push('Field "' + $field.attr('name') + '" is required');
        }
    });
    
    return errors;
}

// Validate and submit
function validateAndSubmit() {
    var errors = validateForm();
    
    if (errors.length > 0) {
        $('#validationList').html('');
        errors.forEach(function(error) {
            $('#validationList').append('<li>' + error + '</li>');
        });
        $('#validationMessages').show();
        $('html, body').animate({ scrollTop: $('#validationMessages').offset().top - 100 }, 500);
        return;
    }
    
    $('#validationMessages').hide();
    
    if (!confirm('Apply this merged version? This will resolve the conflict.')) {
        return;
    }
    
    var mergedData = getFormData();
    
    // Show loading
    var $btn = $(event.target);
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Applying...');
    
    $.ajax({
        url: '<?php echo site_url("sync_conflicts/merge/"); ?>' + conflictId,
        type: 'POST',
        data: { merged_data: JSON.stringify(mergedData) },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                setTimeout(function() {
                    window.location.href = '<?php echo site_url("sync_conflicts"); ?>';
                }, 1500);
            } else {
                showToast(response.message, 'error');
                $btn.prop('disabled', false).html('<i class="fa fa-check"></i> <?php echo get_phrase("apply_merged_version"); ?>');
            }
        },
        error: function() {
            showToast('Error submitting merge', 'error');
            $btn.prop('disabled', false).html('<i class="fa fa-check"></i> <?php echo get_phrase("apply_merged_version"); ?>');
        }
    });
}

// Show toast notification
function showToast(message, type) {
    var bgColor = type === 'success' ? '#5cb85c' : (type === 'error' ? '#d9534f' : '#5bc0de');
    
    $('body').append(
        '<div class="toast-notification" style="position: fixed; top: 20px; right: 20px; z-index: 9999; ' +
        'background: ' + bgColor + '; color: white; padding: 15px 20px; border-radius: 4px; ' +
        'box-shadow: 0 2px 8px rgba(0,0,0,0.2); animation: slideIn 0.3s;">' +
        '<i class="fa fa-' + (type === 'success' ? 'check' : 'exclamation') + '-circle"></i> ' + message +
        '</div>'
    );
    
    setTimeout(function() {
        $('.toast-notification').fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}

// Track field modifications
$(document).ready(function() {
    $('.merge-field').on('input', function() {
        var $field = $(this);
        var currentVal = $field.val();
        var localVal = $field.data('local');
        var remoteVal = $field.data('remote');
        
        if (currentVal !== localVal && currentVal !== remoteVal) {
            $field.addClass('modified');
        } else {
            $field.removeClass('modified');
        }
    });
});
</script>

<?php
// Helper functions
function format_merge_value($value) {
    if (is_null($value)) {
        return '<span class="text-muted">NULL</span>';
    }
    if (is_array($value) || is_object($value)) {
        return '<code>' . htmlspecialchars(json_encode($value)) . '</code>';
    }
    if (strlen($value) > 100) {
        return htmlspecialchars(substr($value, 0, 100)) . '...';
    }
    return htmlspecialchars($value);
}

function is_text_field($field, $value) {
    // Determine if field should be textarea based on length or field name
    if (strlen($value) > 100) return true;
    if (stripos($field, 'description') !== false) return true;
    if (stripos($field, 'comment') !== false) return true;
    if (stripos($field, 'note') !== false) return true;
    if (stripos($field, 'address') !== false) return true;
    return false;
}

function get_primary_key($table) {
    $CI =& get_instance();
    $query = $CI->db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    $result = $query->row_array();
    return $result ? $result['Column_name'] : null;
}

$this->format_merge_value = 'format_merge_value';
$this->is_text_field = 'is_text_field';
$this->get_primary_key = 'get_primary_key';
?>
