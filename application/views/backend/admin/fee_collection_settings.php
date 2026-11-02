<?php
$running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
$running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;

// Get current settings
$daily_mode = $this->db->get_where('settings', ['type' => 'daily_fee_collection_mode'])->row();
$school_mode = $daily_mode ? $daily_mode->description : 'classroom';

$teacher_mode_setting = $this->db->get_where('settings', ['type' => 'teacher_fee_collection_mode'])->row();
$teacher_mode = $teacher_mode_setting ? $teacher_mode_setting->description : 'restricted';
?>

<style>
/* ============================================
   FEE COLLECTION SETTINGS - PROFESSIONAL DESIGN
   ============================================ */

.settings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 32px;
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.25);
    position: relative;
    overflow: hidden;
}
.settings-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.settings-header h2 {
    color: white;
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
    position: relative;
    z-index: 1;
}
.settings-header p {
    color: rgba(255,255,255,0.9);
    margin: 0;
    font-size: 16px;
    position: relative;
    z-index: 1;
}

/* Settings Card */
.settings-card {
    background: white;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    margin-bottom: 24px;
    border: 1px solid #f1f5f9;
}
.settings-card-title {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.settings-card-title i {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}
.settings-card-description {
    color: #64748b;
    font-size: 15px;
    margin-bottom: 24px;
    line-height: 1.6;
}

/* Mode Selection Cards */
.mode-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 768px) {
    .mode-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (min-width: 1200px) {
    .mode-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
.mode-option {
    display: flex;
    flex-direction: column;
    padding: 24px;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 160px;
    background: #fafbfc;
}
.mode-option:hover {
    background: white;
    border-color: #cbd5e1;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.08);
}
.mode-option.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
}
.mode-option input[type="radio"] {
    margin-bottom: 12px;
    width: 20px;
    height: 20px;
    accent-color: #667eea;
}
.mode-option-content h4 {
    margin: 0 0 8px 0;
    font-weight: 700;
    color: #1e293b;
    font-size: 17px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.mode-option-content p {
    margin: 0;
    font-size: 14px;
    color: #64748b;
    line-height: 1.6;
}

/* Module Toggle Cards */
.module-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 576px) {
    .module-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (min-width: 992px) {
    .module-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (min-width: 1400px) {
    .module-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}
.module-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-radius: 16px;
    background: #f8fafc;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}
.module-card:hover {
    background: white;
    border-color: #e2e8f0;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.06);
}
.module-card.active {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(5, 150, 105, 0.08) 100%);
    border-color: #10b981;
}
.module-info {
    display: flex;
    align-items: center;
    gap: 16px;
}
.module-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: white;
    flex-shrink: 0;
}
.icon-feeding { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.icon-classes { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.icon-transport { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.icon-breakfast { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.icon-water { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
.module-text h5 {
    margin: 0;
    font-weight: 700;
    color: #1e293b;
    font-size: 16px;
}
.module-text p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: #64748b;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    width: 56px;
    height: 30px;
    flex-shrink: 0;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.3s;
    border-radius: 30px;
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
input:checked + .toggle-slider {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

/* Assignments Table */
.assignments-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e2e8f0;
}
.assignments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.assignments-header h4 {
    margin: 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.assignments-header h4 i {
    color: #667eea;
}
.assignments-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.assignments-table th {
    background: #f1f5f9;
    padding: 16px 20px;
    text-align: left;
    font-weight: 700;
    color: #475569;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.assignments-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 15px;
    color: #334155;
}
.assignments-table tr:last-child td {
    border-bottom: none;
}
.assignments-table tr:hover td {
    background: #f8fafc;
}
.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 6px;
    margin-bottom: 4px;
}
.badge-feeding { background: #fce7f3; color: #9d174d; }
.badge-classes { background: #dbeafe; color: #1e40af; }
.badge-transport { background: #d1fae5; color: #065f46; }
.badge-breakfast { background: #fef3c7; color: #92400e; }
.badge-water { background: #cffafe; color: #0e7490; }

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    text-decoration: none;
}
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.35);
}
.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.45);
}
.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.35);
}
.btn-success:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.45);
}
.btn-outline {
    background: white;
    border: 2px solid #e2e8f0;
    color: #475569;
    padding: 10px 16px;
}
.btn-outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
.btn-danger {
    background: white;
    border: 2px solid #fecaca;
    color: #ef4444;
    padding: 10px 16px;
}
.btn-danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
}

/* Save Button Container */
.save-container {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 20px 32px;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -8px 32px rgba(0,0,0,0.1);
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 24px;
    border-top: 1px solid #f1f5f9;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #64748b;
}
.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    color: #cbd5e1;
}
.empty-state p {
    font-size: 16px;
    margin: 0;
}

/* Responsive Table */
.table-responsive {
    overflow-x: auto;
    border-radius: 12px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}
</style>

<div class="settings-header">
    <h2><i class="fa fa-cog"></i> Fee Collection Settings</h2>
    <p>Configure how and where fees are collected, which modules are active, and teacher permissions</p>
</div>

<?php echo form_open('admin/fee_collection_settings/update', ['id' => 'fee_settings_form']); ?>

<!-- Section 1: School-Wide Collection Mode -->
<div class="settings-card">
    <div class="settings-card-title">
        <i class="fa fa-school"></i>
        School-Wide Collection Mode
    </div>
    <p class="settings-card-description">Define where daily fees can be collected in your school. This setting controls who has permission to collect fees from students.</p>
    
    <div class="mode-grid">
        <div class="mode-option <?php echo $school_mode == 'classroom' ? 'selected' : ''; ?>" onclick="selectMode(this, 'school_mode', 'classroom')">
            <input type="radio" name="daily_fee_collection_mode" value="classroom" <?php echo $school_mode == 'classroom' ? 'checked' : ''; ?>>
            <div class="mode-option-content">
                <h4><i class="fa fa-chalkboard-teacher" style="color: #667eea;"></i> Classroom Only</h4>
                <p>Teachers collect fees while marking attendance. Best for schools where teachers handle daily collections.</p>
            </div>
        </div>
        
        <div class="mode-option <?php echo $school_mode == 'cashier' ? 'selected' : ''; ?>" onclick="selectMode(this, 'school_mode', 'cashier')">
            <input type="radio" name="daily_fee_collection_mode" value="cashier" <?php echo $school_mode == 'cashier' ? 'checked' : ''; ?>>
            <div class="mode-option-content">
                <h4><i class="fa fa-calculator" style="color: #f59e0b;"></i> Cashier Only</h4>
                <p>Only designated cashiers can collect fees. Best for schools with a dedicated finance office.</p>
            </div>
        </div>
        
        <div class="mode-option <?php echo $school_mode == 'hybrid' ? 'selected' : ''; ?>" onclick="selectMode(this, 'school_mode', 'hybrid')">
            <input type="radio" name="daily_fee_collection_mode" value="hybrid" <?php echo $school_mode == 'hybrid' ? 'checked' : ''; ?>>
            <div class="mode-option-content">
                <h4><i class="fa fa-exchange-alt" style="color: #10b981;"></i> Hybrid Mode</h4>
                <p>Both teachers and cashiers can collect fees. Provides maximum flexibility for your school.</p>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Fee Modules -->
<div class="settings-card">
    <div class="settings-card-title">
        <i class="fa fa-toggle-on"></i>
        Fee Modules
    </div>
    <p class="settings-card-description">Enable or disable fee collection modules for your school. Only enabled modules will be available for collection.</p>
    
    <div class="module-grid">
        <div class="module-card <?php echo is_fee_module_enabled('feeding') ? 'active' : ''; ?>">
            <div class="module-info">
                <div class="module-icon icon-feeding">
                    <i class="fa fa-utensils"></i>
                </div>
                <div class="module-text">
                    <h5>Feeding Fee</h5>
                    <p>Daily feeding charges</p>
                </div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="fee_module_feeding" value="1" <?php echo is_fee_module_enabled('feeding') ? 'checked' : ''; ?> onchange="toggleModule(this, 'feeding')">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="module-card <?php echo is_fee_module_enabled('classes') ? 'active' : ''; ?>">
            <div class="module-info">
                <div class="module-icon icon-classes">
                    <i class="fa fa-book"></i>
                </div>
                <div class="module-text">
                    <h5>Classes Fee</h5>
                    <p>Daily tuition charges</p>
                </div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="fee_module_classes" value="1" <?php echo is_fee_module_enabled('classes') ? 'checked' : ''; ?> onchange="toggleModule(this, 'classes')">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="module-card <?php echo is_fee_module_enabled('transport') ? 'active' : ''; ?>">
            <div class="module-info">
                <div class="module-icon icon-transport">
                    <i class="fa fa-bus"></i>
                </div>
                <div class="module-text">
                    <h5>Transport Fare</h5>
                    <p>School bus charges</p>
                </div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="fee_module_transport" value="1" <?php echo is_fee_module_enabled('transport') ? 'checked' : ''; ?> onchange="toggleModule(this, 'transport')">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="module-card <?php echo is_fee_module_enabled('breakfast') ? 'active' : ''; ?>">
            <div class="module-info">
                <div class="module-icon icon-breakfast">
                    <i class="fa fa-coffee"></i>
                </div>
                <div class="module-text">
                    <h5>Breakfast Fee</h5>
                    <p>Morning meal charges</p>
                </div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="fee_module_breakfast" value="1" <?php echo is_fee_module_enabled('breakfast') ? 'checked' : ''; ?> onchange="toggleModule(this, 'breakfast')">
                <span class="toggle-slider"></span>
            </label>
        </div>
        
        <div class="module-card <?php echo is_fee_module_enabled('water') ? 'active' : ''; ?>">
            <div class="module-info">
                <div class="module-icon icon-water">
                    <i class="fa fa-tint"></i>
                </div>
                <div class="module-text">
                    <h5>Water Fee</h5>
                    <p>Drinking water charges</p>
                </div>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" name="fee_module_water" value="1" <?php echo is_fee_module_enabled('water') ? 'checked' : ''; ?> onchange="toggleModule(this, 'water')">
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>
</div>

<!-- Section 3: Teacher Permissions -->
<div class="settings-card">
    <div class="settings-card-title">
        <i class="fa fa-user-shield"></i>
        Teacher Fee Collection Permissions
    </div>
    <p class="settings-card-description">Control which teachers can collect fees during attendance. This setting works in conjunction with the school-wide collection mode.</p>
    
    <div class="mode-grid">
        <div class="mode-option <?php echo $teacher_mode == 'all_allowed' ? 'selected' : ''; ?>" onclick="selectMode(this, 'teacher_mode', 'all_allowed')">
            <input type="radio" name="teacher_fee_collection_mode" value="all_allowed" <?php echo $teacher_mode == 'all_allowed' ? 'checked' : ''; ?>>
            <div class="mode-option-content">
                <h4><i class="fa fa-check-circle" style="color: #10b981;"></i> Allow All Teachers</h4>
                <p>All teachers can collect fees when marking attendance in any class they teach.</p>
            </div>
        </div>
        
        <div class="mode-option <?php echo $teacher_mode == 'restricted' ? 'selected' : ''; ?>" onclick="selectMode(this, 'teacher_mode', 'restricted')">
            <input type="radio" name="teacher_fee_collection_mode" value="restricted" <?php echo $teacher_mode == 'restricted' ? 'checked' : ''; ?>>
            <div class="mode-option-content">
                <h4><i class="fa fa-ban" style="color: #ef4444;"></i> Restrict All Teachers</h4>
                <p>No teacher can collect fees. Only admins and cashiers can collect fees.</p>
            </div>
        </div>
        
        <div class="mode-option <?php echo $teacher_mode == 'selective' ? 'selected' : ''; ?>" onclick="selectMode(this, 'teacher_mode', 'selective')">
            <input type="radio" name="teacher_fee_collection_mode" value="selective" <?php echo $teacher_mode == 'selective' ? 'checked' : ''; ?>>
            <div class="mode-option-content">
                <h4><i class="fa fa-user-check" style="color: #3b82f6;"></i> Selective Assignment</h4>
                <p>Only specifically assigned teachers can collect fees in their assigned classes.</p>
            </div>
        </div>
    </div>
    
    <!-- Selective Assignments Section -->
    <div id="selective_assignments" class="assignments-section" style="display: <?php echo $teacher_mode == 'selective' ? 'block' : 'none'; ?>;">
        <div class="assignments-header">
            <h4><i class="fa fa-users"></i> Teacher Assignments</h4>
            <button type="button" onclick="showFeeAssignmentModal()" class="btn btn-success">
                <i class="fa fa-plus"></i> Add Assignment
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="assignments-table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Class</th>
                        <th>Can Collect</th>
                        <th>Assigned</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="assignments_table">
                    <?php
                    $assignments = $this->db->select('fc.*, t.name as teacher_name, c.name as class_name, c.name_numeric')
                        ->from('fee_collection_assignments fc')
                        ->join('teacher t', 't.teacher_id = fc.teacher_id')
                        ->join('class c', 'c.class_id = fc.class_id')
                        ->where('fc.year', $running_year)
                        ->where('fc.term', $running_term)
                        ->order_by('t.name', 'ASC')
                        ->get()->result_array();

                    if (empty($assignments)):
                    ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fa fa-users-slash"></i>
                                <p>No assignments yet. Click "Add Assignment" to create one.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($assignments as $assignment): ?>
                        <tr>
                            <td><strong><?php echo $assignment['teacher_name']; ?></strong></td>
                            <td><?php echo $assignment['class_name'] . ' ' . $assignment['name_numeric']; ?></td>
                            <td>
                                <?php if ($assignment['can_collect_feeding']): ?>
                                <span class="badge badge-feeding">Feeding</span>
                                <?php endif; ?>
                                <?php if ($assignment['can_collect_breakfast']): ?>
                                <span class="badge badge-breakfast">Breakfast</span>
                                <?php endif; ?>
                                <?php if ($assignment['can_collect_classes']): ?>
                                <span class="badge badge-classes">Classes</span>
                                <?php endif; ?>
                                <?php if ($assignment['can_collect_water']): ?>
                                <span class="badge badge-water">Water</span>
                                <?php endif; ?>
                                <?php if ($assignment['can_collect_transport']): ?>
                                <span class="badge badge-transport">Transport</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #64748b;">
                                <?php echo date('d M Y', strtotime($assignment['created_at'])); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" onclick="editFeeAssignment(<?php echo $assignment['id']; ?>)" class="btn btn-outline" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button type="button" onclick="deleteFeeAssignment(<?php echo $assignment['id']; ?>)" class="btn btn-danger" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Save Button -->
<div class="save-container">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save"></i> Save All Settings
    </button>
</div>

<?php echo form_close(); ?>

<script>
// Mode selection highlighting
function selectMode(element, group, value) {
    // Update radio
    $(element).find('input[type="radio"]').prop('checked', true);
    
    // Update visual selection
    $(element).siblings('.mode-option').removeClass('selected');
    $(element).addClass('selected');
    
    // Show/hide selective assignments
    if (group === 'teacher_mode') {
        if (value === 'selective') {
            $('#selective_assignments').slideDown(300);
        } else {
            $('#selective_assignments').slideUp(300);
        }
    }
}

// Module toggle visual update
function toggleModule(checkbox, module) {
    $(checkbox).closest('.module-card').toggleClass('active', checkbox.checked);
}

// Form submission
$('#fee_settings_form').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('Saving settings...', 'loading');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAjaxModal_alert(response.message || 'Operation failed', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});

// Show fee assignment modal using Modal controller
function showFeeAssignmentModal() {
    showAjaxModal_alert('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>', 'loading');
    $.ajax({
        url: '<?php echo site_url("modal/popup/modal_fee_assignment"); ?>',
        success: function(response) {
            $('#modal_alert .modal-body').html(response);
            $('#modal_alert .modal-dialog').css('width', '700px');
        },
        error: function() {
            $('#modal_alert .modal-body').html('<div class="alert alert-danger">Failed to load form. Please try again.</div>');
        }
    });
}

// Edit fee assignment
function editFeeAssignment(id) {
    showAjaxModal_alert('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>', 'loading');
    $.ajax({
        url: '<?php echo site_url("modal/popup/modal_fee_assignment/"); ?>' + id,
        success: function(response) {
            $('#modal_alert .modal-body').html(response);
            $('#modal_alert .modal-dialog').css('width', '700px');
        },
        error: function() {
            $('#modal_alert .modal-body').html('<div class="alert alert-danger">Failed to load form. Please try again.</div>');
        }
    });
}

// Delete fee assignment
function deleteFeeAssignment(id) {
    showConfirmModal(
        'Delete Assignment',
        'Are you sure you want to remove this fee collection assignment?',
        function() {
            showAjaxModal_alert('Deleting...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/delete_fee_assignment/'); ?>' + id,
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                showAjaxModal_alert(response.message, response.status);
                if (response.status === 'success') {
                    setTimeout(() => location.reload(), 1500);
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Delete',
        'danger'
    );
}
</script>
