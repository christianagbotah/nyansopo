
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-success" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title" >
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('upload_academic_syllabus'); ?>
                </div>
            </div>
            <div class="panel-body">

                <?php
                echo form_open(site_url('teacher/upload_academic_syllabus'), array(
                    'id' => 'syllabus_upload_form',
                    'class' => 'form-horizontal form-groups-bordered validate', 'target' => '_top', 'enctype' => 'multipart/form-data'
                ));
                ?>

                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('title'); ?></label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="title"
                               data-validate="required" data-message-required="<?php echo get_phrase('value_required'); ?>"/>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('description'); ?></label>
                    <div class="col-sm-6">
                        <textarea class="form-control" name="description" required="required"></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('class'); ?></label>
                    <div class="col-sm-6">
                        <select class="form-control select2" name="class_id" id="class_id" onchange="return get_class_subject(this.value)">
                            <option value=""><?php echo get_phrase('select'); ?></option>
                            <?php
                            $class = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')))->result_array();
                            foreach($class as $subj):
                            $classes = $this->db->get_where('class', array('class_id' => $subj['class_id']))->result_array();
                            foreach ($classes as $row):
                                //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }
                                ?>

                                <option value="<?php echo $row['class_id']; ?>"><?php echo $row['name'].' '.$row['name_numeric'].$sec_name; ?></option>

                            <?php endforeach; endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('subject'); ?></label>
                    <div class="col-sm-5">
                        <select name="subject_id" class="form-control" id="subject_selector_holder" required="required">
                            <option value=""><?php echo get_phrase('select_class_first'); ?></option>

                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('file'); ?></label>
                    <div class="col-sm-5">
                        <input type="file" name="file_name" class="form-control file2 inline btn btn-primary" data-label="<i class='glyphicon glyphicon-file'></i> Browse" 
                               data-validate="required" data-message-required="<?php echo get_phrase('required'); ?>"/>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-5">
                        <button type="submit" id = 'submit' class="btn btn-info">
                            <i class="entypo-upload"></i> <?php echo get_phrase('upload_syllabus'); ?>
                        </button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    function get_class_subject(class_id) {
        if(class_id !== ''){
        $.ajax({
            url: '<?php echo site_url('admin/get_class_subject/'); ?>' + class_id,
            success: function (response)
            {
                jQuery('#subject_selector_holder').html(response);
            }
        });
      }
    }

</script>
<script type = 'text/javascript'>
                var class_id = '';
                jQuery(document).ready(function($) {
                    $("#submit").attr('disabled', 'disabled');
                });

                function check_validation(){
                    if(class_id !== ''){
                        $('#submit').removeAttr('disabled');
                    }
                    else{
                        $("#submit").attr('disabled', 'disabled');
                    }
                }
                $('#class_id').change(function(){
                    class_id = $('#class_id').val();
                    check_validation();
                });

                $('#syllabus_upload_form').submit(function(e) {
                    e.preventDefault();
                    showAjaxModal_alert('Uploading...', 'loading');
                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: new FormData(this),
                        cache: false,
                        contentType: false,
                        processData: false
                    }).done(function(response) {
                        var data = typeof response === 'string' ? JSON.parse(response) : response;
                        if(data.status === 'success') {
                            showAjaxModal_alert(data.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showAjaxModal_alert(data.message || 'Upload failed', 'error');
                        }
                    }).fail(function() {
                        showAjaxModal_alert('An error occurred', 'error');
                    });
                });
            </script>