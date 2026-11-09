<?php
$theme_color_row = $this->db->get_where('settings', array('type' => 'theme_color'))->row();
$theme_color = $theme_color_row ? $theme_color_row->description : '667eea';
if(strpos($theme_color, '#') !== 0) {
    $theme_color = '#' . $theme_color;
}

if(!function_exists('adjustBrightness_grade_creche')) {
    function adjustBrightness_grade_creche($hex, $steps) {
        $hex = str_replace('#', '', $hex);
        if(strlen($hex) == 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}
$gradient_light = adjustBrightness_grade_creche($theme_color, 20);
$gradient_dark = adjustBrightness_grade_creche($theme_color, -30);
?>
<style>
:root {
    --theme-primary: <?php echo $theme_color; ?>;
    --theme-light: <?php echo $gradient_light; ?>;
    --theme-dark: <?php echo $gradient_dark; ?>;
}

.grade-header {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.grade-stats {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.15);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    flex: 1;
    backdrop-filter: blur(10px);
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

.modern-tabs {
    display: flex;
    background: white;
    border-radius: 12px;
    padding: 8px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.modern-tab {
    flex: 1;
    padding: 16px 24px;
    text-align: center;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
}

.modern-tab.active {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.modern-tab:hover {
    background: #f1f5f9;
    color: #334155;
    text-decoration: none;
}

.modern-tab.active:hover {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
    color: white;
}

.grade-table {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.grade-table thead {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
}

.grade-table thead th {
    color: white;
    font-weight: 600;
    padding: 20px;
    border: none;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.grade-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f5f9;
}

.grade-table tbody tr:hover {
    background: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.grade-table tbody td {
    padding: 20px;
    vertical-align: middle;
    border: none;
}

.grade-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 16px;
}

.grade-abbrev {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-action {
    padding: 8px 12px;
    border-radius: 8px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-edit {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
    color: white;
}

.grade-form {
    background: white;
    border-radius: 16px;
    padding: 50px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    max-width: 800px;
    margin: 0 auto;
}

.form-container {
    max-width: 600px;
    margin: 0 auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.form-group {
    margin-bottom: 30px;
}

.form-label {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 12px;
    display: block;
    font-size: 15px;
}

.form-control {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    font-size: 16px;
    transition: all 0.3s ease;
    width: 100%;
    min-height: 54px;
    background: #fafbfc;
}

.form-control:focus {
    border-color: var(--theme-primary);
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
    background: white;
}

.form-control::placeholder {
    color: #94a3b8;
    font-size: 15px;
}

.form-actions {
    text-align: center;
    margin-top: 50px;
    padding-top: 30px;
    border-top: 1px solid #f1f5f9;
}

.btn-primary {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
    border: none;
    padding: 16px 40px;
    border-radius: 12px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    min-width: 180px;
    justify-content: center;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    color: white;
}

@media (max-width: 768px) {
    .grade-form {
        padding: 30px 20px;
        margin: 0 15px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .form-container {
        max-width: 100%;
    }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-icon {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .grade-stats {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .grade-table {
        font-size: 14px;
    }
    
    .grade-table thead th,
    .grade-table tbody td {
        padding: 12px 8px;
    }
}
</style>

<!-- Modern Tabs -->
<div class="modern-tabs">
    <a href="#list" class="modern-tab active" id="list-tab" data-toggle="tab">
        <i class="fa fa-list" style="margin-right: 8px;"></i>
        Grade List
    </a>
    <a href="#add" class="modern-tab" id="add-tab" data-toggle="tab">
        <i class="fa fa-plus" style="margin-right: 8px;"></i>
        Add New Grade
    </a>
</div>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Grade List Tab -->
    <div class="tab-pane active" id="list">
        <div class="grade-table">
            <?php if(empty($grades)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa fa-star"></i>
                    </div>
                    <h3>No Grades Found</h3>
                    <p>Get started by creating your first grade</p>
                    <button class="btn btn-primary" onclick="$('#add-tab').click();">
                        <i class="fa fa-plus"></i> Create First Grade
                    </button>
                </div>
            <?php else: ?>
                <table class="table" id="table_export">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo get_phrase('grade_full_name'); ?></th>
                            <th><?php echo get_phrase('grade_abbreviation'); ?></th>
                            <th style="text-align: center;"><?php echo get_phrase('options'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach($grades as $row): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <div class="grade-name"><?php echo $row['full_name']; ?></div>
                            </td>
                            <td>
                                <span class="grade-abbrev"><?php echo $row['abbrev']; ?></span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-buttons">
                                    <button class="btn-action btn-edit" onclick="editGrade(<?php echo $row['grade_id']; ?>)" title="Edit Grade">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteGrade(<?php echo $row['grade_id']; ?>)" title="Delete Grade">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Grade Tab -->
    <div class="tab-pane" id="add">
        <div class="grade-form">
            <div style="text-align: center; margin-bottom: 40px;">
                <h2 style="color: #1e293b; font-weight: 700; margin-bottom: 8px;">Create New Grade</h2>
                <p style="color: #64748b;">Add a new grade for creche and nursery assessment</p>
            </div>
            
            <?php echo form_open(site_url('admin/grade_creche/create'), array('id' => 'add_grade_form')); ?>
                <div class="form-container">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa fa-star" style="margin-right: 8px;"></i>
                            <?php echo get_phrase('full_name'); ?> *
                        </label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., Needs Attention, Excellent" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa fa-tag" style="margin-right: 8px;"></i>
                            <?php echo get_phrase('grade_abbreviation'); ?> *
                        </label>
                        <input type="text" class="form-control" name="grade_point" placeholder="e.g., NA, EX" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Create Grade
                        </button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Initialize DataTable with modern styling
    $('#table_export').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'asc']], // Sort by name
        columnDefs: [
            { orderable: false, targets: [3] } // Disable sorting on actions column
        ],
        language: {
            search: "Search grades:",
            lengthMenu: "Show _MENU_ grades per page",
            info: "Showing _START_ to _END_ of _TOTAL_ grades",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
    
    // Tab switching functionality
    $('.modern-tab').click(function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.modern-tab').removeClass('active');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        
        // Hide all tab panes
        $('.tab-pane').removeClass('active');
        
        // Show target tab pane
        var target = $(this).attr('href');
        $(target).addClass('active');
    });
    
    // Form submission with modern modal
    $('#add_grade_form').submit(function(e) {
        e.preventDefault();
        
        // Show loading
        showAjaxModal_alert('Creating grade...', 'loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json'
        })
        .done(function(response) {
            showAjaxModal_alert('Grade created successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
        })
        .fail(function(xhr) {
            var errorMsg = 'Failed to create grade';
            if(xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showAjaxModal_alert(errorMsg, 'error');
        });
    });
});

// Edit grade function
function editGrade(gradeId) {
    showAjaxModal('<?php echo site_url("modal/popup/modal_edit_grade_creche/"); ?>' + gradeId);
}

// Delete grade function with modern confirmation
function deleteGrade(gradeId) {
    showConfirmModal(
        'Delete Grade',
        'Are you sure you want to delete this grade? This action cannot be undone.',
        function() {
            // Show loading
            showAjaxModal_alert('Deleting grade...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url("admin/grade_creche/delete/"); ?>' + gradeId,
                type: 'GET',
                dataType: 'json'
            })
            .done(function(response) {
                showAjaxModal_alert('Grade deleted successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            })
            .fail(function() {
                showAjaxModal_alert('An error occurred while deleting the grade', 'error');
            });
        },
        'Delete',
        'danger'
    );
}
</script>