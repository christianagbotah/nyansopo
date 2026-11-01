<?php
$running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
$running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;

// Get assignment ID if editing
$assignment_id = isset($param2) ? $param2 : '';
$assignment = null;

if (!empty($assignment_id)) {
    $assignment = $this->db->get_where('fee_collection_assignments', ['id' => $assignment_id])->row_array();
}

// Get teachers for dropdown
$teachers = $this->db->select('teacher_id, name')
    ->from('teacher')
    ->order_by('name', 'ASC')
    ->get()->result_array();

// Selected class for editing
$selected_class_id = !empty($assignment) ? $assignment['class_id'] : '';
?>

<style>
.modal-content {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}
.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px 32px;
    border-bottom: none;
}
.modal-header .close {
    color: white;
    opacity: 0.9;
    font-size: 28px;
    font-weight: 300;
}
.modal-header h4 {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
    color: white;
}
.modal-body {
    padding: 32px;
    background: #f8fafc;
}
.modal-footer {
    padding: 20px 32px;
    background: white;
    border-top: 1px solid #e2e8f0;
}

.form-group {
    margin-bottom: 24px;
}
.form-group label {
    font-size: 15px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 10px;
    display: block;
}
.form-control {
    font-size: 15px;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.2s ease;
    background: white;
    height: auto;
    min-height: 48px;
}
.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}
select.form-control {
    height: 48px;
    line-height: 1.4;
}

.fee-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-top: 10px;
}
.fee-checkbox {
    display: flex;
    align-items: center;
    padding: 14px 16px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.fee-checkbox:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.fee-checkbox.checked {
    border-color: #10b981;
    background: #f0fdf4;
}
.fee-checkbox input {
    width: 18px;
    height: 18px;
    margin-right: 10px;
    accent-color: #10b981;
}
.fee-checkbox span {
    font-size: 14px;
    font-weight: 500;
    color: #334155;
}
.fee-checkbox .fee-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    color: white;
    font-size: 14px;
}
.icon-feeding { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.icon-classes { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.icon-transport { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.icon-breakfast { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.icon-water { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }

.btn-modal {
    padding: 14px 32px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}
.btn-modal-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}
.btn-modal-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}
.btn-modal-default {
    background: white;
    color: #64748b;
    border: 2px solid #e2e8f0;
}
.btn-modal-default:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
</style>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h4 class="modal-title">
        <i class="fa fa-user-plus"></i> 
        <span id="modal_fee_assignment_title"><?php echo !empty($assignment) ? 'Edit Teacher Assignment' : 'Add Teacher Assignment'; ?></span>
    </h4>
</div>

<div class="modal-body">
    <form id="fee_assignment_form">
        <input type="hidden" name="assignment_id" id="fee_assignment_id" value="<?php echo !empty($assignment) ? $assignment['id'] : ''; ?>">
        <input type="hidden" name="year" value="<?php echo $running_year; ?>">
        <input type="hidden" name="term" value="<?php echo $running_term; ?>">
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="fee_assignment_teacher_id">
                        <i class="fa fa-user" style="color: #667eea;"></i> Select Teacher
                    </label>
                    <select name="teacher_id" id="fee_assignment_teacher_id" class="form-control" required style="width: 100%;">
                        <option value="">-- Choose a Teacher --</option>
                        <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['teacher_id']; ?>" <?php echo (!empty($assignment) && $assignment['teacher_id'] == $teacher['teacher_id']) ? 'selected' : ''; ?>>
                            <?php echo $teacher['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="fee_assignment_class_id">
                        <i class="fa fa-chalkboard" style="color: #667eea;"></i> Select Class
                    </label>
                    <select name="class_id" id="fee_assignment_class_id" class="form-control" required style="width: 100%;">
                        <option value="">-- Choose a Class --</option>
                        <?php echo getFullClassList('', $selected_class_id); ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>
                <i class="fa fa-money-bill-wave" style="color: #667eea;"></i> Fee Collection Permissions
            </label>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 12px;">Select which fee types this teacher can collect for the selected class.</p>
            
            <div class="fee-checkboxes">
                <label class="fee-checkbox <?php echo (!empty($assignment) && $assignment['can_collect_feeding']) ? 'checked' : ''; ?>">
                    <input type="checkbox" name="can_collect_feeding" value="1" <?php echo (!empty($assignment) && $assignment['can_collect_feeding']) ? 'checked' : ''; ?> onchange="updateCheckboxStyle(this)">
                    <div class="fee-icon icon-feeding"><i class="fa fa-utensils"></i></div>
                    <span>Feeding</span>
                </label>
                
                <label class="fee-checkbox <?php echo (!empty($assignment) && $assignment['can_collect_breakfast']) ? 'checked' : ''; ?>">
                    <input type="checkbox" name="can_collect_breakfast" value="1" <?php echo (!empty($assignment) && $assignment['can_collect_breakfast']) ? 'checked' : ''; ?> onchange="updateCheckboxStyle(this)">
                    <div class="fee-icon icon-breakfast"><i class="fa fa-coffee"></i></div>
                    <span>Breakfast</span>
                </label>
                
                <label class="fee-checkbox <?php echo (!empty($assignment) && $assignment['can_collect_classes']) ? 'checked' : ''; ?>">
                    <input type="checkbox" name="can_collect_classes" value="1" <?php echo (!empty($assignment) && $assignment['can_collect_classes']) ? 'checked' : ''; ?> onchange="updateCheckboxStyle(this)">
                    <div class="fee-icon icon-classes"><i class="fa fa-book"></i></div>
                    <span>Classes</span>
                </label>
                
                <label class="fee-checkbox <?php echo (!empty($assignment) && $assignment['can_collect_water']) ? 'checked' : ''; ?>">
                    <input type="checkbox" name="can_collect_water" value="1" <?php echo (!empty($assignment) && $assignment['can_collect_water']) ? 'checked' : ''; ?> onchange="updateCheckboxStyle(this)">
                    <div class="fee-icon icon-water"><i class="fa fa-tint"></i></div>
                    <span>Water</span>
                </label>
                
                <label class="fee-checkbox <?php echo (!empty($assignment) && $assignment['can_collect_transport']) ? 'checked' : ''; ?>">
                    <input type="checkbox" name="can_collect_transport" value="1" <?php echo (!empty($assignment) && $assignment['can_collect_transport']) ? 'checked' : ''; ?> onchange="updateCheckboxStyle(this)">
                    <div class="fee-icon icon-transport"><i class="fa fa-bus"></i></div>
                    <span>Transport</span>
                </label>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn-modal btn-modal-default" data-dismiss="modal">
        <i class="fa fa-times"></i> Cancel
    </button>
    <button type="button" class="btn-modal btn-modal-primary" onclick="saveFeeAssignment()">
        <i class="fa fa-save"></i> Save Assignment
    </button>
</div>

<script>
function updateCheckboxStyle(checkbox) {
    $(checkbox).closest('.fee-checkbox').toggleClass('checked', checkbox.checked);
}

function saveFeeAssignment() {
    var teacherId = $('#fee_assignment_teacher_id').val();
    var classId = $('#fee_assignment_class_id').val();
    
    if (!teacherId) {
        showAjaxModal_alert('Please select a teacher', 'error');
        return;
    }
    if (!classId) {
        showAjaxModal_alert('Please select a class', 'error');
        return;
    }
    
    showAjaxModal_alert('Saving assignment...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/save_fee_assignment"); ?>',
        type: 'POST',
        data: $('#fee_assignment_form').serialize(),
        dataType: 'json'
    }).done(function(response) {
        if (response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(function() {
                $('#modal_alert').modal('hide');
                location.reload();
            }, 1500);
        } else {
            showAjaxModal_alert(response.message || 'Failed to save assignment', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred while saving', 'error');
    });
}

// Initialize Select2 on the dropdowns after modal loads
// Using setTimeout to ensure Modal controller's Select2 init completes first
setTimeout(function() {
    // Destroy any existing Select2 instances first
    if ($('#fee_assignment_teacher_id').data('select2')) {
        $('#fee_assignment_teacher_id').select2('destroy');
    }
    if ($('#fee_assignment_class_id').data('select2')) {
        $('#fee_assignment_class_id').select2('destroy');
    }
    
    // Reinitialize with proper settings
    $('#fee_assignment_teacher_id').select2({
        theme: 'classic',
        width: '100%',
        dropdownParent: $('#modal_alert')
    });
    $('#fee_assignment_class_id').select2({
        theme: 'classic',
        width: '100%',
        dropdownParent: $('#modal_alert')
    });
}, 100);
</script>
