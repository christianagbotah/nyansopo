<?php

    $online_exam = $this->db->get_where('online_exam', array('online_exam_id' => $online_exam_id))->row_array();
    $sections    = $this->db->get_where('section', array('class_id' => $online_exam['class_id']))->result_array();
    $class_name = $this->crud_model->get_class_name($online_exam['class_id']);

    //class teacher id
    $class_teacher = $this->db->get_where('class', array('class_id' => $online_exam['class_id']))->row()->teacher_id;

   
?>
<div class="row">
    <?php echo form_open(site_url('teacher/manage_online_exam/edit') , array('id' => 'edit_online_exam_form', 'class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));?>
    <input type="hidden" name="online_exam_id" value="<?php echo $online_exam['online_exam_id']; ?>">
        <div class="col-md-6">
            <div class="panel panel-info" data-collapsed="0">
                <div class="panel-heading">
                    <div class="panel-title" >
                        <i class="entypo-plus-circled"></i>
                        <?php echo get_phrase('online_exam');?>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('exam_title');?></label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="exam_title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" value="<?php echo $online_exam['title']; ?>"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('class');?></label>
                        <div class="col-sm-9">
                            <select name="class_id" class="form-control select2" data-validate="required" id="class_id"
                            data-message-required="<?php echo get_phrase('value_required');?>"
                            onchange="return get_class_sections(this.value)" required>
                                <option value=""><?php echo get_phrase('select_class');?></option>
                                    <?php
                foreach($class_ids_creche as $subj):
                    $classes = $this->db->get_where('class', array('class_id' => $subj['class_id']))->result_array();
                    foreach($classes as $row):
                     
                     //add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                    $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                    $sec_name = '';
                    if($class_has_more_sections > 1) {
                        $sec_name = $section_name;
                    }
                ?>
                <option value="<?php echo $row['class_id'];?>" <?php if($online_exam['class_id'] == $row['class_id']) echo 'selected';?>><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
                <?php endforeach; endforeach; //FOR CRECHE ?> 
                
                <?php
                foreach($class_ids_c as $cs):
                    $classes_c = $this->db->get_where('class', array('class_id' => $cs['class_id']))->result_array();
                    foreach($classes_ as $rowc):
                    
                    //add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('class_id' => $rowc['class_id']))->row()->name;
                    $class_has_more_sections = $this->db->get_where('class', array('name' => $rowc['name'], 'name_numeric' => $rowc['name_numeric']))->num_rows();
                    $sec_name = '';
                    if($class_has_more_sections > 1) {
                        $sec_name = $section_name;
                    }
                ?>
                <option value="<?php echo $rowc['class_id'];?>" <?php if($online_exam['class_id'] == $rowc['class_id']) echo 'selected';?>><?php echo $rowc['name'].' '.$rowc['name_numeric'].$sec_name;?></option>
                <?php endforeach; endforeach; ?>
                
                <?php
                foreach($class_ids as $subj):
                    $classes = $this->db->get_where('class', array('class_id' => $subj['class_id']))->result_array();
                    foreach($classes as $row):
                    
                    //add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                    $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                    $sec_name = '';
                    if($class_has_more_sections > 1) {
                        $sec_name = $section_name;
                    }
                ?>
                <option value="<?php echo $row['class_id'];?>" <?php if($online_exam['class_id'] == $row['class_id']) echo 'selected';?>><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
                <?php endforeach; endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('section');?></label>
                        <div class="col-sm-9" id="section_selector_holder">
                            <select name="section_id" class="form-control select2" id = "section_id">
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo $section['section_id']; ?>" <?php if($section['section_id'] == $online_exam['section_id']) echo 'selected'; ?>><?php echo $section['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('subject');?></label>
                        <div class="col-sm-9" id="subject_selector_holder">
                            <select name="subject_id" class="form-control select2" id = "subject_id">

                                <?php
                            if($class_name == 'JHSS') {
                                    //if this is a class master or class teacher, grant full access to all the subjects
                                if($class_teacher == $this->session->userdata('teacher_id')) {
                                    $subjects = $this->db->get_where('subject' , array(
                                    'class_id' => $online_exam['class_id'] , 'year' => $online_exam['running_year'], 'sem' => $online_exam['sem']))->result_array();
                                } else {
                                    $subjects = $this->db->get_where('subject' , array(
                                    'class_id' => $online_exam['class_id'] , 'year' => $online_exam['running_year'], 'sem' => $online_exam['sem'], 'teacher_id' => $this->session->userdata('teacher_id')
                                    ))->result_array();
                                }

                            } else {
                                //if this is a class master or class teacher, grant full access to all the subjects
                                if($class_teacher == $this->session->userdata('teacher_id')) {
                                    $subjects = $this->db->get_where('subject' , array(
                                    'class_id' => $online_exam['class_id'] , 'year' => $online_exam['running_year'], 'term' => $online_exam['term']))->result_array();
                                } else {
                                    $subjects = $this->db->get_where('subject' , array(
                                    'class_id' => $online_exam['class_id'] , 'year' => $online_exam['running_year'], 'term' => $online_exam['term'], 'teacher_id' => $this->session->userdata('teacher_id')
                                    ))->result_array();
                                }

                            }
                            ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['subject_id']; ?>" <?php if($subject['subject_id'] == $online_exam['subject_id']) echo 'selected'; ?>><?php echo $subject['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-info" data-collapsed="0">
                <div class="panel-heading">
                    <div class="panel-title" >
                        <i class="entypo-plus-circled"></i>
                        <?php echo get_phrase('online_exam');?>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('exam_date');?></label>
                        <div class="col-sm-9">
                            <input type="text" id="exam_date" class="datepicker form-control" name="exam_date" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" value="<?php echo date('M d, Y', $online_exam['exam_date']); ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('exam_time');?></label>
                        <div class="col-sm-4">
                            <div class="input-group">
                                <input type="text" class="form-control timepicker" name="time_start" id="time_start" data-template="dropdown" data-show-seconds="true" data-default-time="<?php echo $online_exam['time_start'];?>" data-show-meridian="false" data-minute-step="5" data-second-step="5" value="" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" />
                                
                                <div class="input-group-addon">
                                    <a href="#"><i class="entypo-clock"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-1"><h5><strong><?php echo get_phrase('to');?></strong></h5></div>
                        <div class="col-sm-4">
                            <div class="input-group">
                                <input type="text" class="form-control timepicker" name="time_end" id="time_end" data-template="dropdown" data-show-seconds="true" data-default-time="<?php echo $online_exam['time_end'];?>" data-show-meridian="false" data-minute-step="5" data-second-step="5" value="" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" />
                                
                                <div class="input-group-addon">
                                    <a href="#"><i class="entypo-clock"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('minimum_percentage');?></label>
                        <div class="col-sm-9">
                            <label class="sr-only" for="exampleInputAmount"><?php echo get_phrase('minimum_percentage_for_passing'); ?></label>
                            <div class="input-group">
                              <input type="text" class="form-control" name = "minimum_percentage" id="exampleInputAmount" placeholder="<?php echo get_phrase('minimum_percentage_for_passing'); ?>" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" value="<?php echo $online_exam['minimum_percentage']; ?>" required>
                              <div class="input-group-addon">%</div>
                            </div>
                        </div>
                   </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('instruction');?></label>
                        <div class="col-sm-9">
                            <textarea name="instruction" class = "form-control" rows="8" cols="80" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" required><?php echo $online_exam['instruction']; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-12" style="text-align: center;">
                <button type="submit" class="btn btn-info"><?php echo get_phrase('update_exam');?></button>
            </div>
        </div>
    </form>
</div>

<script type="text/javascript">
$('#edit_online_exam_form').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('Updating online exam...', 'loading');
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
            setTimeout(() => window.location.href = '<?php echo site_url('teacher/manage_online_exam'); ?>', 2000);
        } else {
            showAjaxModal_alert(data.message || 'Operation failed', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});

function get_class_sections(class_id) {
    $.ajax({
        url: '<?php echo site_url('teacher/get_class_section_selector/');?>' + class_id ,
        success: function(response)
        {
            jQuery('#section_selector_holder').html(response);
        }
    });
    get_class_subject(class_id);
}

function get_class_subject(class_id) {
    $.ajax({
        url: '<?php echo site_url('teacher/get_class_subject_selector/');?>' + class_id ,
        success: function(response)
        {
            jQuery('#subject_selector_holder').html(response);
        }
    });
}
</script>
