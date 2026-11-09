<?php
/**
 * Curriculum Import View
 * Bulk import curriculum data from CSV/Excel files
 * 
 * Requirements: 8.9, 8.10
 */
?>

<div class="row">
    <div class="col-md-12">
        
        <!-- Import Instructions -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-upload"></i> <?php echo get_phrase('import_curriculum_data'); ?>
                </div>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong><?php echo get_phrase('instructions'); ?>:</strong>
                    <ul style="margin-top: 10px; margin-bottom: 0;">
                        <li><?php echo get_phrase('download_sample_template_below'); ?></li>
                        <li><?php echo get_phrase('fill_template_with_your_data'); ?></li>
                        <li><?php echo get_phrase('upload_completed_file'); ?></li>
                        <li><?php echo get_phrase('review_preview_before_importing'); ?></li>
                    </ul>
                </div>
                
                <!-- Download Templates -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-12">
                        <h4><?php echo get_phrase('download_templates'); ?></h4>
                        <div class="btn-group">
                            <a href="<?php echo base_url('assets/templates/curriculum_strands_template.csv'); ?>" 
                               class="btn btn-info" download>
                                <i class="fa fa-download"></i> <?php echo get_phrase('strands_template'); ?>
                            </a>
                            <a href="<?php echo base_url('assets/templates/curriculum_sub_strands_template.csv'); ?>" 
                               class="btn btn-info" download>
                                <i class="fa fa-download"></i> <?php echo get_phrase('sub_strands_template'); ?>
                            </a>
                            <a href="<?php echo base_url('assets/templates/curriculum_content_standards_template.csv'); ?>" 
                               class="btn btn-info" download>
                                <i class="fa fa-download"></i> <?php echo get_phrase('content_standards_template'); ?>
                            </a>
                            <a href="<?php echo base_url('assets/templates/curriculum_learning_indicators_template.csv'); ?>" 
                               class="btn btn-info" download>
                                <i class="fa fa-download"></i> <?php echo get_phrase('learning_indicators_template'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Form -->
                <div class="row">
                    <div class="col-md-12">
                        <h4><?php echo get_phrase('upload_file'); ?></h4>
                        <?php echo form_open_multipart(site_url('admin/curriculum_import_process'), array('id' => 'curriculum-import-form')); ?>
                            
                            <div class="form-group">
                                <label><?php echo get_phrase('import_type'); ?> <span class="required">*</span></label>
                                <select name="import_type" id="import_type" class="form-control" required>
                                    <option value=""><?php echo get_phrase('select_import_type'); ?></option>
                                    <option value="strands"><?php echo get_phrase('strands'); ?></option>
                                    <option value="sub_strands"><?php echo get_phrase('sub_strands'); ?></option>
                                    <option value="content_standards"><?php echo get_phrase('content_standards'); ?></option>
                                    <option value="learning_indicators"><?php echo get_phrase('learning_indicators'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="subject-selector" style="display:none;">
                                <label><?php echo get_phrase('subject'); ?> <span class="required">*</span></label>
                                <select name="subject_id" id="subject_id" class="form-control">
                                    <option value=""><?php echo get_phrase('select_subject'); ?></option>
                                    <?php
                                    $subjects = $this->db->get('subject')->result();
                                    foreach ($subjects as $subject):
                                    ?>
                                        <option value="<?php echo $subject->subject_id; ?>">
                                            <?php echo $subject->name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" id="class-selector" style="display:none;">
                                <label><?php echo get_phrase('class_level'); ?></label>
                                <select name="class_level" id="class_level" class="form-control">
                                    <option value=""><?php echo get_phrase('select_class_level'); ?></option>
                                    <?php
                                    $classes = $this->db->get('class')->result();
                                    foreach ($classes as $class):
                                    ?>
                                        <option value="<?php echo $class->name; ?>">
                                            <?php echo $class->name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><?php echo get_phrase('upload_file'); ?> <span class="required">*</span></label>
                                <input type="file" name="import_file" id="import_file" class="form-control" 
                                       accept=".csv,.xlsx,.xls" required>
                                <small class="text-muted">
                                    <?php echo get_phrase('accepted_formats'); ?>: CSV, Excel (.xlsx, .xls)
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa fa-upload"></i> <?php echo get_phrase('upload_and_preview'); ?>
                                </button>
                            </div>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Import Preview (shown after upload) -->
        <div id="import-preview" style="display:none;">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="fa fa-eye"></i> <?php echo get_phrase('import_preview'); ?>
                    </div>
                </div>
                <div class="panel-body">
                    <div id="preview-content"></div>
                    
                    <div style="margin-top: 20px;">
                        <button type="button" id="confirm-import" class="btn btn-success btn-lg">
                            <i class="fa fa-check"></i> <?php echo get_phrase('confirm_import'); ?>
                        </button>
                        <button type="button" id="cancel-import" class="btn btn-default">
                            <i class="fa fa-times"></i> <?php echo get_phrase('cancel'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Import Results (shown after import) -->
        <div id="import-results" style="display:none;">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="fa fa-check-circle"></i> <?php echo get_phrase('import_results'); ?>
                    </div>
                </div>
                <div class="panel-body">
                    <div id="results-content"></div>
                    
                    <div style="margin-top: 20px;">
                        <button type="button" id="import-another" class="btn btn-primary">
                            <i class="fa fa-upload"></i> <?php echo get_phrase('import_another_file'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
$(document).ready(function() {
    
    // Show/hide subject and class selectors based on import type
    $('#import_type').change(function() {
        var type = $(this).val();
        
        if (type === 'strands') {
            $('#subject-selector').show();
            $('#class-selector').show();
            $('#subject_id').attr('required', true);
        } else if (type === 'sub_strands') {
            $('#subject-selector').hide();
            $('#class-selector').hide();
            $('#subject_id').attr('required', false);
        } else if (type === 'content_standards' || type === 'learning_indicators') {
            $('#subject-selector').hide();
            $('#class-selector').hide();
            $('#subject_id').attr('required', false);
        } else {
            $('#subject-selector').hide();
            $('#class-selector').hide();
            $('#subject_id').attr('required', false);
        }
    });
    
    // Handle form submission
    $('#curriculum-import-form').submit(function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        // Show loading
        $.notify('<?php echo get_phrase('uploading_file'); ?>...', 'info');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Show preview
                    displayPreview(response.data);
                } else {
                    $.notify(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                $.notify('<?php echo get_phrase('upload_failed'); ?>: ' + error, 'error');
            }
        });
    });
    
    // Display preview
    function displayPreview(data) {
        var html = '<div class="alert alert-warning">';
        html += '<i class="fa fa-exclamation-triangle"></i> ';
        html += '<strong><?php echo get_phrase('review_before_importing'); ?></strong>';
        html += '</div>';
        
        html += '<div class="row">';
        html += '<div class="col-md-4"><div class="alert alert-info text-center">';
        html += '<h3>' + data.total_records + '</h3>';
        html += '<p><?php echo get_phrase('total_records'); ?></p>';
        html += '</div></div>';
        
        html += '<div class="col-md-4"><div class="alert alert-success text-center">';
        html += '<h3>' + data.valid_records + '</h3>';
        html += '<p><?php echo get_phrase('valid_records'); ?></p>';
        html += '</div></div>';
        
        html += '<div class="col-md-4"><div class="alert alert-danger text-center">';
        html += '<h3>' + data.invalid_records + '</h3>';
        html += '<p><?php echo get_phrase('invalid_records'); ?></p>';
        html += '</div></div>';
        html += '</div>';
        
        if (data.errors && data.errors.length > 0) {
            html += '<div class="alert alert-danger">';
            html += '<strong><?php echo get_phrase('errors_found'); ?>:</strong>';
            html += '<ul>';
            $.each(data.errors, function(index, error) {
                html += '<li>' + error + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        if (data.preview && data.preview.length > 0) {
            html += '<h4><?php echo get_phrase('preview_first_10_records'); ?></h4>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-bordered table-striped">';
            html += '<thead><tr>';
            
            // Table headers
            var firstRow = data.preview[0];
            $.each(firstRow, function(key, value) {
                html += '<th>' + key + '</th>';
            });
            html += '</tr></thead><tbody>';
            
            // Table rows
            $.each(data.preview, function(index, row) {
                html += '<tr>';
                $.each(row, function(key, value) {
                    html += '<td>' + value + '</td>';
                });
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '</div>';
        }
        
        $('#preview-content').html(html);
        $('#import-preview').show();
        
        // Store data for confirmation
        $('#confirm-import').data('import-data', data);
    }
    
    // Confirm import
    $('#confirm-import').click(function() {
        var data = $(this).data('import-data');
        
        $.notify('<?php echo get_phrase('importing_data'); ?>...', 'info');
        
        $.ajax({
            url: '<?php echo site_url('admin/curriculum_import_confirm'); ?>',
            type: 'POST',
            data: {
                import_data: JSON.stringify(data)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    displayResults(response.data);
                    $.notify(response.message, 'success');
                } else {
                    $.notify(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                $.notify('<?php echo get_phrase('import_failed'); ?>: ' + error, 'error');
            }
        });
    });
    
    // Display results
    function displayResults(data) {
        var html = '<div class="row">';
        html += '<div class="col-md-4"><div class="alert alert-success text-center">';
        html += '<h3>' + data.imported + '</h3>';
        html += '<p><?php echo get_phrase('records_imported'); ?></p>';
        html += '</div></div>';
        
        html += '<div class="col-md-4"><div class="alert alert-warning text-center">';
        html += '<h3>' + data.duplicates + '</h3>';
        html += '<p><?php echo get_phrase('duplicate_records'); ?></p>';
        html += '</div></div>';
        
        html += '<div class="col-md-4"><div class="alert alert-danger text-center">';
        html += '<h3>' + data.failed + '</h3>';
        html += '<p><?php echo get_phrase('failed_records'); ?></p>';
        html += '</div></div>';
        html += '</div>';
        
        if (data.errors && data.errors.length > 0) {
            html += '<div class="alert alert-danger">';
            html += '<strong><?php echo get_phrase('errors'); ?>:</strong>';
            html += '<ul>';
            $.each(data.errors, function(index, error) {
                html += '<li>' + error + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        $('#results-content').html(html);
        $('#import-preview').hide();
        $('#import-results').show();
    }
    
    // Cancel import
    $('#cancel-import').click(function() {
        $('#import-preview').hide();
        $('#curriculum-import-form')[0].reset();
    });
    
    // Import another
    $('#import-another').click(function() {
        $('#import-results').hide();
        $('#curriculum-import-form')[0].reset();
        $('#import_type').trigger('change');
    });
    
});
</script>

<style>
.required {
    color: #dc3545;
}

#import-preview, #import-results {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert h3 {
    margin: 0;
    font-size: 36px;
    font-weight: bold;
}

.alert p {
    margin: 5px 0 0 0;
    font-size: 14px;
}
</style>
