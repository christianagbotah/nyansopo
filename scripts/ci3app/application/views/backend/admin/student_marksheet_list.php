<style>
.marksheet-container {
    margin-top: 25px;
}
.modern-panel {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}
.modern-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
#marksheet_content {
    margin-top: 30px;
}
.loading-spinner {
    text-align: center;
    padding: 40px;
    font-size: 16px;
    color: #667eea;
}
.loading-spinner i {
    font-size: 32px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="row marksheet-container">
    <div class="col-md-12">
        <div class="modern-panel">
            <div class="modern-panel-title">
                <i class="fa fa-chart-bar"></i> <?php echo get_phrase('student_marksheet'); ?>
            </div>
            <div class="modern-panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="modern-label">
                                <i class="fa fa-school" style="margin-right: 5px;"></i>
                                <?php echo get_phrase('select_class'); ?>
                            </label>
                            <select class="form-control modern-select" id="class_filter" onchange="loadStudents()">
                                <option value=""><?php echo get_phrase('select_class'); ?></option>
                                <?php 
                                if($account_type == 'admin'):
                                    getFullClassList();
                                else:
                                    $teacher_id = $this->session->userdata('teacher_id');
                                    getFullClassList($teacher_id);
                                endif;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="modern-label">
                                <i class="fa fa-user-graduate" style="margin-right: 5px;"></i>
                                <?php echo get_phrase('select_student'); ?>
                            </label>
                            <select class="form-control modern-select" id="student_filter" disabled>
                                <option value=""><?php echo get_phrase('select_class_first'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="modern-label">&nbsp;</label>
                            <button class="btn modern-btn" onclick="viewMarksheet()" disabled id="view_btn" style="width: 100%;">
                                <i class="fa fa-eye"></i> <?php echo get_phrase('view_marksheet'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Marksheet Content Area -->
<div class="row" id="marksheet_content" style="display: none;">
    <div class="col-md-12">
        <div id="marksheet_display"></div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2 for class filter
    $('#class_filter').select2({
        placeholder: '<?php echo get_phrase('select_class'); ?>',
        allowClear: true,
        width: '100%'
    });
    
    // Initialize Select2 for student filter
    $('#student_filter').select2({
        placeholder: '<?php echo get_phrase('select_student'); ?>',
        allowClear: true,
        width: '100%'
    });
});

function loadStudents() {
    var class_id = $('#class_filter').val();
    
    if(!class_id) {
        $('#student_filter').html('<option value=""><?php echo get_phrase('select_class_first'); ?></option>').prop('disabled', true).trigger('change');
        $('#view_btn').prop('disabled', true);
        $('#marksheet_content').hide();
        return;
    }
    
    $('#student_filter').html('<option value=""><?php echo get_phrase('loading'); ?>...</option>').prop('disabled', true).trigger('change');
    $('#view_btn').prop('disabled', true);
    $('#marksheet_content').hide();
    
    $.ajax({
        url: '<?php echo site_url($account_type.'/get_students_by_class'); ?>',
        type: 'POST',
        data: {class_id: class_id},
        success: function(response) {
            $('#student_filter').html(response).prop('disabled', false).trigger('change');
        },
        error: function() {
            $('#student_filter').html('<option value=""><?php echo get_phrase('error_loading_students'); ?></option>').trigger('change');
        }
    });
}

$('#student_filter').on('change', function() {
    if($(this).val()) {
        $('#view_btn').prop('disabled', false);
    } else {
        $('#view_btn').prop('disabled', true);
        $('#marksheet_content').hide();
    }
});

function viewMarksheet() {
    var student_id = $('#student_filter').val();
    
    if(!student_id) {
        alert('<?php echo get_phrase('please_select_student'); ?>');
        return;
    }
    
    // Show loading spinner
    $('#marksheet_content').show();
    $('#marksheet_display').html('<div class="loading-spinner"><i class="fa fa-spinner"></i><br><?php echo get_phrase('loading'); ?>...</div>');
    
    // Scroll to content area
    $('html, body').animate({
        scrollTop: $('#marksheet_content').offset().top - 20
    }, 500);
    
    // Load marksheet content via AJAX
    $.ajax({
        url: '<?php echo site_url($account_type.'/load_marksheet_content'); ?>',
        type: 'POST',
        data: {student_id: student_id},
        success: function(response) {
            $('#marksheet_display').html(response);
        },
        error: function() {
            $('#marksheet_display').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <?php echo get_phrase('error_loading_marksheet'); ?></div>');
        }
    });
}
</script>
