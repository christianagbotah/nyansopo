<?php
$theme_color_row = $this->db->get_where('settings', array('type' => 'theme_color'))->row();
$theme_color = $theme_color_row ? $theme_color_row->description : '667eea';
if(strpos($theme_color, '#') !== 0) {
    $theme_color = '#' . $theme_color;
}

if(!function_exists('adjustBrightness_exam')) {
    function adjustBrightness_exam($hex, $steps) {
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
$gradient_light = adjustBrightness_exam($theme_color, 20);
$gradient_dark = adjustBrightness_exam($theme_color, -30);
?>
<style>
:root {
    --theme-primary: <?php echo $theme_color; ?>;
    --theme-light: <?php echo $gradient_light; ?>;
    --theme-dark: <?php echo $gradient_dark; ?>;
}

.exam-header {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.exam-stats {
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
    margin-top: 20px;
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

.exam-table {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.exam-table thead {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
}

.exam-table thead th {
    color: white;
    font-weight: 600;
    padding: 20px;
    border: none;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.exam-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f5f9;
}

.exam-table tbody tr:hover {
    background: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.exam-table tbody td {
    padding: 20px;
    vertical-align: middle;
    border: none;
}

.exam-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 16px;
}

.exam-date {
    color: #64748b;
    font-size: 14px;
}

.exam-category {
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

.exam-form {
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

select.form-control {
    cursor: pointer;
    appearance: none;
    background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23666" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 16px center;
    background-size: 12px;
    padding-right: 50px;
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
    .exam-form {
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
    .exam-stats {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .exam-table {
        font-size: 14px;
    }
    
    .exam-table thead th,
    .exam-table tbody td {
        padding: 12px 8px;
    }
}
</style>

<!-- Modern Tabs -->
<div class="modern-tabs">
    <a href="#list" class="modern-tab active" id="list-tab" data-toggle="tab">
        <i class="fa fa-list" style="margin-right: 8px;"></i>
        Exam List
    </a>
    <a href="#add" class="modern-tab" id="add-tab" data-toggle="tab">
        <i class="fa fa-plus" style="margin-right: 8px;"></i>
        Add New Exam
    </a>
</div>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Exam List Tab -->
    <div class="tab-pane active" id="list">
        <div class="exam-table">
            <?php if(empty($exams)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa fa-graduation-cap"></i>
                    </div>
                    <h3>No Exams Found</h3>
                    <p>Get started by creating your first exam</p>
                    <button class="btn btn-primary" onclick="$('#add-tab').click();">
                        <i class="fa fa-plus"></i> Create First Exam
                    </button>
                </div>
            <?php else: ?>
                <table class="table" id="table_export">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('exam_name'); ?></th>
                            <th><?php echo get_phrase('date'); ?></th>
                            <th><?php echo get_phrase('category'); ?></th>
                            <th style="text-align: center;"><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($exams as $row): ?>
                        <tr>
                            <td>
                                <div class="exam-name"><?php echo $row['name']; ?></div>
                            </td>
                            <td>
                                <div class="exam-date">
                                    <i class="fa fa-calendar" style="margin-right: 6px; color: #64748b;"></i>
                                    <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                </div>
                            </td>
                            <td>
                                <span class="exam-category">
                                    <?php echo $this->crud_model->get_exam_category($row['category_id']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-buttons">
                                    <button class="btn-action btn-edit" onclick="editExam(<?php echo $row['exam_id']; ?>)" title="Edit Exam">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteExam(<?php echo $row['exam_id']; ?>)" title="Delete Exam">
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
    
    <!-- Add Exam Tab -->
    <div class="tab-pane" id="add">
        <div class="exam-form">
            <div style="text-align: center; margin-bottom: 40px;">
                <h2 style="color: #1e293b; font-weight: 700; margin-bottom: 8px;">Create New Exam</h2>
                <p style="color: #64748b;">Fill in the details below to create a new examination</p>
            </div>
            
            <?php echo form_open(site_url('admin/exam/create'), array('id' => 'add_exam_form')); ?>
                <div class="form-container">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa fa-graduation-cap" style="margin-right: 8px;"></i>
                            <?php echo get_phrase('exam_name'); ?> *
                        </label>
                        <input type="text" class="form-control" name="name" placeholder="Enter exam name (e.g., Mid-Term Examination)" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa fa-calendar" style="margin-right: 8px;"></i>
                                <?php echo get_phrase('exam_date'); ?> *
                            </label>
                            <input type="date" class="form-control" name="date" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa fa-folder" style="margin-right: 8px;"></i>
                                <?php echo get_phrase('category'); ?> *
                            </label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select exam category</option>
                                <?php
                                $exam_cat = $this->db->get('exam_category')->result_array();
                                foreach($exam_cat as $rowc): 
                                ?>
                                    <option value="<?php echo $rowc['category_id']; ?>">
                                        <?php echo $this->crud_model->get_exam_category($rowc['category_id']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Create Exam
                        </button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Check URL hash and activate corresponding tab
    var hash = window.location.hash;
    if (hash === '#add') {
        // Activate the add tab
        $('.modern-tab').removeClass('active');
        $('#add-tab').addClass('active');
        $('.tab-pane').removeClass('active');
        $('#add').addClass('active');
    }
    
    // Initialize DataTable with modern styling
    $('#table_export').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'desc']], // Sort by date descending
        columnDefs: [
            { orderable: false, targets: [3] } // Disable sorting on actions column
        ],
        language: {
            search: "Search exams:",
            lengthMenu: "Show _MENU_ exams per page",
            info: "Showing _START_ to _END_ of _TOTAL_ exams",
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
        
        // Update URL hash without scrolling
        if (history.pushState) {
            history.pushState(null, null, target);
        } else {
            window.location.hash = target;
        }
    });
    
    // Form submission with modern modal
    $('#add_exam_form').submit(function(e) {
        e.preventDefault();
        
        // Show loading
        showAjaxModal_alert('Creating exam...', 'loading');
        
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
            showAjaxModal_alert('Exam created successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
        })
        .fail(function(xhr) {
            var errorMsg = 'Failed to create exam';
            if(xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showAjaxModal_alert(errorMsg, 'error');
        });
    });
});

// Edit exam function
function editExam(examId) {
    showAjaxModal('<?php echo site_url("modal/popup/modal_edit_exam/"); ?>' + examId);
}

// Delete exam function with modern confirmation
function deleteExam(examId) {
    showConfirmModal(
        'Delete Exam',
        'Are you sure you want to delete this exam? This action cannot be undone and will also remove all associated marks and aggregations.',
        function() {
            // Show loading
            showAjaxModal_alert('Deleting exam...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url("admin/exam/delete/"); ?>' + examId,
                type: 'GET',
                dataType: 'json'
            })
            .done(function(response) {
                if(response.message === 'done') {
                    showAjaxModal_alert('Exam deleted successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showAjaxModal_alert('Failed to delete exam', 'error');
                }
            })
            .fail(function() {
                showAjaxModal_alert('An error occurred while deleting the exam', 'error');
            });
        },
        'Delete',
        'danger'
    );
}
</script>