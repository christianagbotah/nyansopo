<!-- Modern Professional Bulk Student Admission -->
<style type="text/css">
    .modern-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 30px;
        margin-bottom: 24px;
        transition: all 0.3s ease;
    }
    
    .modern-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    }
    
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
    }
    
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
    }
    
    .step-item {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }
    
    .step-item.active .step-circle {
        background: #3b82f6;
        color: white;
    }
    
    .step-item.completed .step-circle {
        background: #10b981;
        color: white;
    }
    
    .step-label {
        font-size: 13px;
        color: #6b7280;
        font-weight: 500;
    }
    
    .step-item.active .step-label {
        color: #3b82f6;
    }
    
    .modern-btn {
        padding: 12px 28px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .modern-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .modern-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
    }
    
    .modern-btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .modern-btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(16, 185, 129, 0.4);
    }
    
    .modern-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .modern-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.3s ease;
        background: white;
    }
    
    .modern-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .modern-file-upload {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    
    .modern-file-input {
        width: 100%;
        padding: 40px 20px;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8fafc;
    }
    
    .modern-file-input:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    
    .modern-file-input.has-file {
        border-color: #10b981;
        background: #ecfdf5;
    }
    
    .alert-modern {
        padding: 16px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideDown 0.3s ease;
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
    
    .alert-success {
        background: #ecfdf5;
        border-left: 4px solid #10b981;
        color: #065f46;
    }
    
    .alert-danger {
        background: #fef2f2;
        border-left: 4px solid #ef4444;
        color: #991b1b;
    }
    
    .alert-info {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        color: #1e40af;
    }
    
    .instruction-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
    }
    
    .instruction-list {
        list-style: none;
        padding: 0;
        margin: 20px 0 0 0;
    }
    
    .instruction-list li {
        padding: 12px 0;
        padding-left: 35px;
        position: relative;
        line-height: 1.6;
    }
    
    .instruction-list li::before {
        content: '✓';
        position: absolute;
        left: 0;
        top: 12px;
        width: 24px;
        height: 24px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .form-group-modern {
        margin-bottom: 24px;
    }
    
    .form-label-modern {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 16px;
    }
    
    .icon-box-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .icon-box-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    #preloader2 {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.98);
        z-index: 99999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .loader-content {
        text-align: center;
    }
    
    .spinner {
        width: 60px;
        height: 60px;
        border: 4px solid #e5e7eb;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<div id="preloader2" style="display: none">
    <div class="loader-content">
        <img src="<?php echo base_url();?>assets/images/lightworldtech.png" width="120px" style="margin-bottom: 20px;">
        <div class="spinner"></div>
        <p style="font-size: 18px; font-weight: 600; color: #374151; margin-top: 20px;">
            Admitting your students, please wait<span id="dot1">.</span><span id="dot2">.</span><span id="dot3">.</span>
        </p>
        <p style="font-size: 14px; color: #6b7280; margin-top: 8px;">Please do not close or refresh this page</p>
    </div>
</div>

<?php 
    $loader = '<div class="spinner" style="width: 20px; height: 20px; border-width: 2px; display: inline-block; vertical-align: middle; margin-right: 8px;"></div>';
    $checked_icon = '<i class="glyphicon glyphicon-check" style="color: #10b981; font-size: 18px;"></i>';

    // Show success message
    if(isset($_GET['success']) && $_GET['success'] == 1) {
        echo '
            <div class="alert-modern alert-success">
                <i class="glyphicon glyphicon-ok-circle" style="font-size: 24px;"></i>
                <div>
                    <strong>Success!</strong> Uploaded students were admitted successfully!
                </div>
                <button type="button" class="close" style="margin-left: auto;" data-dismiss="alert">&times;</button>
            </div>
        ';
    }
    
    // Show error messages
    if(isset($_GET['error']) && $_GET['error'] == 1) {
        echo '
            <div class="alert-modern alert-danger">
                <i class="glyphicon glyphicon-exclamation-sign" style="font-size: 24px;"></i>
                <div>
                    <strong>Invalid Email!</strong> The email address <u>'.$_GET['email'].'</u> is invalid. Admission process terminated!
                </div>
                <button type="button" class="close" style="margin-left: auto;" data-dismiss="alert">&times;</button>
            </div>
        ';
    } else if(isset($_GET['error']) && ($_GET['error'] == 2 || $_GET['error'] == 3)) {
        echo '
            <div class="alert-modern alert-danger">
                <i class="glyphicon glyphicon-exclamation-sign" style="font-size: 24px;"></i>
                <div>
                    <strong>Duplicate Email!</strong> Email <u>'.$_GET['email'].'</u> already exists. Please change this email and try again.
                </div>
                <button type="button" class="close" style="margin-left: auto;" data-dismiss="alert">&times;</button>
            </div>
        ';
    }
?>

<!-- Page Header -->
<div class="modern-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <div style="display: flex; align-items: center; gap: 20px;">
        <div class="icon-box" style="background: rgba(255,255,255,0.2);">
            <i class="glyphicon glyphicon-upload"></i>
        </div>
        <div>
            <h2 style="margin: 0; font-size: 28px; font-weight: 700;">Bulk Student Admission</h2>
            <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 15px;">Import multiple students at once using CSV file</p>
        </div>
    </div>
</div>

<!-- Step Indicator -->
<div class="modern-card">
    <div class="step-indicator">
        <div class="step-item active" id="step1">
            <div class="step-circle">1</div>
            <div class="step-label">Select Class</div>
        </div>
        <div class="step-item" id="step2">
            <div class="step-circle">2</div>
            <div class="step-label">Generate Template</div>
        </div>
        <div class="step-item" id="step3">
            <div class="step-circle">3</div>
            <div class="step-label">Fill Data</div>
        </div>
        <div class="step-item" id="step4">
            <div class="step-circle">4</div>
            <div class="step-label">Upload File</div>
        </div>
    </div>
</div>

<!-- Instructions -->
<div class="instruction-box">
    <h3 style="margin: 0 0 10px 0; font-size: 20px; font-weight: 700;">
        <i class="glyphicon glyphicon-info-sign"></i> How It Works
    </h3>
    <p style="opacity: 0.95; margin-bottom: 20px;">Follow these simple steps to admit multiple students:</p>
    <ul class="instruction-list">
        <li>Select the class and section for the students you want to admit</li>
        <li>Click "Generate CSV Template" to download the template file</li>
        <li>Open the downloaded file and fill in student details under each column</li>
        <li>Ensure dates of birth are in format: <strong>YYYY-MM-DD</strong> (e.g., 2005-05-25)</li>
        <li>Save the file in CSV format (keep the same filename)</li>
        <li>Upload the completed CSV file using the upload button below</li>
        <li>Relax while we process the admissions - you'll be notified when complete</li>
    </ul>
    <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 8px; text-align: center;">
        <strong>⚠️ Important:</strong> Each student and parent must have a unique email address
    </div>
</div>

<div id="err_alert" class="alert-modern alert-danger" style="display: none;">
    <i class="glyphicon glyphicon-exclamation-sign" style="font-size: 24px;"></i>
    <div>
        <strong>Error!</strong> <span id="err_message">Please make sure class and section are selected!</span>
    </div>
</div>

<?php echo form_open(site_url('admin/bulk_student_add_using_csv/import'), 
    array('class' => 'validate', 'id' => 'upload_form', 'name' => 'upload_form', 'enctype' => 'multipart/form-data'));?>

<!-- Main Form -->
<div class="modern-card">
    <h3 style="margin: 0 0 24px 0; font-size: 20px; font-weight: 700; color: #111827;">
        <i class="glyphicon glyphicon-education"></i> Step 1: Select Class & Section
    </h3>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="glyphicon glyphicon-blackboard"></i> Class <span style="color: #ef4444;">*</span>
                </label>
                <select name="class_id" id="class_id" class="modern-select" required
                    onchange="get_sections(this.value)">
                    <option value="">Select a class...</option>
                    <?php getFullClassList(); ?>
                </select>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-group-modern" id="section_holder">
                <label class="form-label-modern">
                    <i class="glyphicon glyphicon-list-alt"></i> Section <span style="color: #ef4444;">*</span>
                </label>
                <select name="section_id" id="section_id" class="modern-select">
                    <option value="">Select class first...</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Generate Template -->
<div class="modern-card">
    <h3 style="margin: 0 0 24px 0; font-size: 20px; font-weight: 700; color: #111827;">
        <i class="glyphicon glyphicon-download-alt"></i> Step 2: Generate CSV Template
    </h3>
    
    <div style="text-align: center; padding: 20px;">
        <div class="icon-box icon-box-primary" style="margin: 0 auto 20px;">
            <i class="glyphicon glyphicon-file"></i>
        </div>
        <p style="color: #6b7280; margin-bottom: 20px; font-size: 15px;">
            Generate a CSV template file customized for your selected class
        </p>
        <button type="button" class="modern-btn modern-btn-primary" id="generate_csv">
            <i class="glyphicon glyphicon-download-alt"></i> Generate CSV Template
        </button>
        <p style="margin-top: 16px; font-size: 13px; color: #6b7280;">
            The file will be downloaded to your Downloads folder
        </p>
    </div>
</div>

<!-- Upload File -->
<div class="modern-card">
    <h3 style="margin: 0 0 24px 0; font-size: 20px; font-weight: 700; color: #111827;">
        <i class="glyphicon glyphicon-upload"></i> Step 3: Upload Completed CSV File
    </h3>
    
    <div class="alert-modern alert-info" style="margin-bottom: 24px;">
        <i class="glyphicon glyphicon-info-sign" style="font-size: 20px;"></i>
        <div style="font-size: 14px;">
            <strong>Date Format:</strong> Birthday must be in format <strong>YYYY-MM-DD</strong> (e.g., 2005-05-25)
        </div>
    </div>
    
    <div class="modern-file-upload">
        <label for="userfile" class="modern-file-input" id="file_label">
            <div>
                <i class="glyphicon glyphicon-cloud-upload" style="font-size: 48px; color: #94a3b8; margin-bottom: 16px;"></i>
                <p style="font-size: 16px; font-weight: 600; color: #374151; margin: 0;">
                    Click to select CSV file or drag and drop
                </p>
                <p style="font-size: 13px; color: #6b7280; margin: 8px 0 0 0;">
                    Only CSV files are accepted
                </p>
            </div>
        </label>
        <input type="file" name="userfile" id="userfile" style="display: none;" 
            onchange="check_loaded_csvfile()" accept="text/csv, .csv" required>
    </div>
    
    <div id="file_info" style="display: none; margin-top: 16px; padding: 16px; background: #f0fdf4; border-radius: 8px; border: 1px solid #86efac;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <i class="glyphicon glyphicon-file" style="font-size: 24px; color: #10b981;"></i>
            <div style="flex: 1;">
                <p style="margin: 0; font-weight: 600; color: #065f46;" id="file_name"></p>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #059669;" id="file_size"></p>
            </div>
            <button type="button" onclick="clearFile()" style="background: none; border: none; color: #dc2626; cursor: pointer;">
                <i class="glyphicon glyphicon-remove" style="font-size: 20px;"></i>
            </button>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <button type="submit" class="modern-btn modern-btn-success" id="import_csv" disabled>
            <i class="glyphicon glyphicon-ok"></i> Import Students
        </button>
    </div>
</div>

<?php echo form_close();?>

<a href="" style="display: none;" id="bulk">Download</a>

<script type="text/javascript">
$(document).ready(function() {
    $('#import_csv').attr('disabled', 'disabled');
    
    // File input trigger
    $('#file_label').click(function() {
        $('#userfile').click();
    });
    
    // Update step indicators
    function updateStep(stepNumber) {
        $('.step-item').removeClass('active completed');
        for(let i = 1; i < stepNumber; i++) {
            $('#step' + i).addClass('completed');
        }
        $('#step' + stepNumber).addClass('active');
    }
    
    // Class selection
    $('#class_id').change(function() {
        if($(this).val() !== '') {
            updateStep(2);
        }
    });
});

function get_sections(class_id) {
    if (class_id != "") {
        $('#err_alert').hide();
        
        $.ajax({
            url: '<?php echo site_url('admin/get_sections/');?>' + class_id,
            success: function(response) {
                jQuery('#section_holder').html(response);
            }
        });
    }
}

$("#generate_csv").click(function() {
    var class_id = $('#class_id').val();
    var section_id = $('#section_id').val();
    
    if(class_id == '' || section_id == '') {
        toastr.error("<?php echo get_phrase('please_make_sure_class_and_section_are_selected'); ?>");
        $('#err_alert').show();
    } else {
        $.ajax({
            url: '<?php echo site_url('admin/generate_bulk_student_excel_template/');?>' + class_id + '/' + section_id,
            dataType: 'json',
            success: function(response) {
                toastr.success("Template generated successfully! Check your Downloads folder.");
                $("#bulk").attr('href', response.file_path);
                $("#bulk").attr('download', response.file_name);
                jQuery('#bulk')[0].click();
                
                // Update step
                $('.step-item').removeClass('active');
                $('#step1, #step2').addClass('completed');
                $('#step3').addClass('active');
            }
        });
    }
});

function check_loaded_csvfile() {
    var file = $('#userfile').prop('files')[0];
    
    if(file) {
        // Update UI
        $('#file_label').addClass('has-file');
        $('#file_info').show();
        $('#file_name').text(file.name);
        $('#file_size').text((file.size / 1024).toFixed(2) + ' KB');
        
        // Update step
        $('.step-item').removeClass('active');
        $('#step1, #step2, #step3').addClass('completed');
        $('#step4').addClass('active');
        
        var class_id = $('#class_id').val();
        var section_id = $('#section_id').val();
        var form_data = new FormData();
        form_data.append('userfile', file);
        form_data.append('<?php echo $this->security->get_csrf_token_name(); ?>', '<?php echo $this->security->get_csrf_hash(); ?>');
        
        if(class_id == '' || section_id == '') {
            toastr.error('Make sure Class and Section fields are selected!');
            clearFile();
            return false;
        }
        
        var import_btn = $('#import_csv');
        import_btn.attr('disabled', 'disabled');
        import_btn.html('<?php echo $loader; ?> Validating file...');
        
        $.ajax({
            url: '<?php echo site_url('admin/uploaded_csvfile_parent_validate/');?>',
            type: 'post',
            data: form_data,
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response == 'email_error') {
                    toastr.error('Parents\' email validation failed. Please check emails and try again.');
                    clearFile();
                    return false;
                } else if(response.length > 0) {
                    showAjaxModal_confirm('<?php echo site_url('modal/popup_2/upload_validate/');?>' + response);
                    $('#modal_confirm .modal-content').css('margin-top', '450px');
                    $('#modal_confirm').modal({backdrop: 'static', keyboard: false});
                } else {
                    import_btn.removeAttr('disabled');
                    import_btn.html('<?php echo $checked_icon; ?> File validated - Ready to import');
                    
                    setTimeout(function() {
                        $('#preloader2').show();
                        $('#import_csv').click();
                    }, 2000);
                }
            }
        });
    }
}

function clearFile() {
    $('#userfile').val('');
    $('#file_label').removeClass('has-file');
    $('#file_info').hide();
    $('#import_csv').attr('disabled', 'disabled').html('<i class="glyphicon glyphicon-ok"></i> Import Students');
    
    $('.step-item').removeClass('active completed');
    $('#step1').addClass('completed');
    $('#step2').addClass('active');
}

$(document).ready(function() {
    $('#modal_no').click(function() {
        toastr.error('Cancelled!');
        navigation('<?php echo site_url('admin/student_bulk_add'); ?>');
    });
    
    $(document).on('hidden.bs.modal', '#modal_confirm', function() {
        toastr.error('Cancelled!');
        navigation('<?php echo site_url('admin/student_bulk_add'); ?>');
    });
    
    $('#modal_yes').click(function() {
        $('#preloader2').show();
        $('#import_csv').removeAttr('disabled').click();
    });
});
</script>
