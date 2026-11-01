<style>
.exam-reports-container {
    margin-top: 25px;
}
.modern-panel {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.modern-panel-title {
    color: white;
    font-size: 18px;
    font-weight: 600;
    padding: 20px;
}
.modern-panel-body {
    background: white;
    padding: 30px;
    border-radius: 0 0 12px 12px;
}
.modern-label {
    font-size: 14px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
    display: block;
}
.modern-select {
    height: 45px;
    font-size: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0 15px;
    transition: all 0.3s;
}
.modern-select:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    outline: none;
}
.modern-select:disabled {
    background-color: #f7fafc;
    cursor: not-allowed;
}
.modern-btn {
    height: 45px;
    padding: 0 30px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.modern-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    color: white;
}
.modern-btn:disabled {
    background: linear-gradient(135deg, #cbd5e0 0%, #a0aec0 100%);
    cursor: not-allowed;
    box-shadow: none;
}
.modern-btn i {
    margin-right: 8px;
}
.select2-container--default .select2-selection--single {
    height: 45px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 41px;
    padding-left: 15px;
    font-size: 15px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 43px;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}
.info-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.info-badge i {
    font-size: 20px;
}
</style>

<div class="row exam-reports-container">
    <div class="col-md-12">
        <div class="modern-panel">
            <div class="modern-panel-title">
                <i class="fa fa-archive"></i> <?php echo get_phrase('exam_reports_archives'); ?>
            </div>
            <div class="modern-panel-body">
                <div class="info-badge">
                    <i class="fa fa-info-circle"></i>
                    <div>
                        <strong><?php echo get_phrase('archive_reports'); ?>:</strong>
                        <?php echo get_phrase('generate_historical_marksheet_reports_from_any_past_exam'); ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="modern-label">
                                <i class="fa fa-school" style="margin-right: 5px;"></i>
                                <?php echo get_phrase('select_class'); ?>
                            </label>
                            <select class="form-control modern-select" id="class_filter" onchange="loadStudentsAndExams()">
                                <option value=""><?php echo get_phrase('select_class'); ?></option>
                                <?php getFullClassList(); ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="modern-label">
                                <i class="fa fa-users" style="margin-right: 5px;"></i>
                                <?php echo get_phrase('select_students'); ?>
                            </label>
                            <select class="form-control modern-select" id="student_filter" disabled>
                                <option value=""><?php echo get_phrase('select_class_first'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="modern-label">
                                <i class="fa fa-file-alt" style="margin-right: 5px;"></i>
                                <?php echo get_phrase('exam_type'); ?>
                            </label>
                            <select class="form-control modern-select" id="exam_filter" disabled>
                                <option value=""><?php echo get_phrase('select_class_first'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="modern-label">&nbsp;</label>
                            <button class="btn modern-btn" onclick="generateReport()" disabled id="generate_btn" style="width: 100%;">
                                <i class="fa fa-print"></i> <?php echo get_phrase('generate_report'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2 for all filters
    $('#class_filter').select2({
        placeholder: '<?php echo get_phrase('select_class'); ?>',
        allowClear: true,
        width: '100%'
    });
    
    $('#student_filter').select2({
        placeholder: '<?php echo get_phrase('select_students'); ?>',
        allowClear: true,
        width: '100%'
    });
    
    $('#exam_filter').select2({
        placeholder: '<?php echo get_phrase('select_exam'); ?>',
        allowClear: true,
        width: '100%'
    });
});

function loadStudentsAndExams() {
    var class_id = $('#class_filter').val();
    
    if(!class_id) {
        $('#student_filter').html('<option value=""><?php echo get_phrase('select_class_first'); ?></option>').prop('disabled', true).trigger('change');
        $('#exam_filter').html('<option value=""><?php echo get_phrase('select_class_first'); ?></option>').prop('disabled', true).trigger('change');
        $('#generate_btn').prop('disabled', true);
        return;
    }
    
    // Load students with "All Students" option
    $('#student_filter').html('<option value=""><?php echo get_phrase('loading'); ?>...</option>').prop('disabled', true).trigger('change');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_students_by_class'); ?>',
        type: 'POST',
        data: {class_id: class_id},
        success: function(response) {
            // Add "All Students" option at the top
            var allOption = '<option value="all"><?php echo get_phrase('all_students'); ?></option>';
            $('#student_filter').html(allOption + response).prop('disabled', false).trigger('change');
        },
        error: function() {
            $('#student_filter').html('<option value=""><?php echo get_phrase('error_loading_students'); ?></option>').trigger('change');
        }
    });
    
    // Load exams for this class
    $('#exam_filter').html('<option value=""><?php echo get_phrase('loading'); ?>...</option>').prop('disabled', true).trigger('change');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_exams_by_class'); ?>',
        type: 'POST',
        data: {class_id: class_id},
        success: function(response) {
            $('#exam_filter').html(response).prop('disabled', false).trigger('change');
        },
        error: function() {
            $('#exam_filter').html('<option value=""><?php echo get_phrase('error_loading_exams'); ?></option>').trigger('change');
        }
    });
    
    $('#generate_btn').prop('disabled', true);
}

// Enable button when both student and exam are selected
$('#student_filter, #exam_filter').on('change', function() {
    var student = $('#student_filter').val();
    var exam = $('#exam_filter').val();
    
    if(student && exam) {
        $('#generate_btn').prop('disabled', false);
    } else {
        $('#generate_btn').prop('disabled', true);
    }
});

function generateReport() {
    var student_id = $('#student_filter').val();
    var exam_id = $('#exam_filter').val();
    var class_id = $('#class_filter').val();
    
    if(!student_id || !exam_id) {
        alert('<?php echo get_phrase('please_select_student_and_exam'); ?>');
        return;
    }
    
    // Parse class_id to get section_id (format: classId_sectionId)
    var class_parts = class_id.split('_');
    var actual_class_id = class_parts[0];
    var section_id = class_parts[1] || actual_class_id;
    
    // Check if class is CRECHE by making AJAX call
    $.ajax({
        url: '<?php echo site_url('admin/get_class_name_ajax'); ?>',
        type: 'POST',
        data: {class_id: actual_class_id},
        async: false,
        success: function(class_name) {
            var isCreche = (class_name.trim() === 'CRECHE');
            
            // Determine if generating for all students or single student
            if(student_id === 'all') {
                // Generate bulk marksheet for all students
                if(isCreche) {
                    // URL: admin/student_marksheet_bulk_print_view_creche/class_id/section_id
                    window.open('<?php echo site_url('admin/student_marksheet_bulk_print_view_creche'); ?>/' + actual_class_id + '/' + section_id, '_blank');
                } else {
                    // URL: admin/student_marksheet_bulk_print_view/class_id/section_id/exam_id
                    window.open('<?php echo site_url('admin/student_marksheet_bulk_print_view'); ?>/' + actual_class_id + '/' + section_id + '/' + exam_id, '_blank');
                }
            } else {
                // Generate single student marksheet print view
                if(isCreche) {
                    // URL: admin/student_marksheet_print_view_creche/student_id/exam_id
                    window.open('<?php echo site_url('admin/student_marksheet_print_view_creche'); ?>/' + student_id + '/' + exam_id, '_blank');
                } else {
                    // URL: admin/student_marksheet_print_view/student_id/exam_id
                    window.open('<?php echo site_url('admin/student_marksheet_print_view'); ?>/' + student_id + '/' + exam_id, '_blank');
                }
            }
        },
        error: function() {
            alert('<?php echo get_phrase('error_determining_class_type'); ?>');
        }
    });
}
</script>
