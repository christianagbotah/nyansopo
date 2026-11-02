<?php
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

//creche
$this->db->select('class_id');
$this->db->distinct();
$find_teacher_creche = $this->db->get_where('subject_creche', array('teacher_id' => $this->session->userdata('teacher_id'), 'year' => $running_year, 'term' => $running_term));
$class_ids_creche = $find_teacher_creche->result_array();

//general
$this->db->select('class_id');
$this->db->distinct();
$find_teacher = $this->db->get_where('subject', array('teacher_id' => $this->session->userdata('teacher_id'), 'year' => $running_year, 'term' => $running_term));
$class_ids = $find_teacher->result_array();

?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title" >
                    <i class="entypo-plus-circled<?php
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

//creche
$this->db->select('class_id');
$this->db->distinct();
$find_teacher_creche = $this->db->get_where('subject_creche', array('teacher_id' => $this->session->userdata('teacher_id'), 'year' => $running_year, 'term' => $running_term));
$class_ids_creche = $find_teacher_creche->result_array();

//general
$this->db->select('class_id');
$this->db->distinct();
$find_teacher = $this->db->get_where('subject', array('teacher_id' => $this->session->userdata('teacher_id'), 'year' => $running_year, 'term' => $running_term));
$class_ids = $find_teacher->result_array();

?>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title" >
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('add_question_paper');?>
                </div>
            </div>

            <div class="panel-body">
                
                <?php echo form_open(site_url('teacher/question_paper/create') , array('class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data'));?>
    
                    <div class="form-group">
                        <label for="field-1" class="col-sm-3 control-label"><?php echo get_phrase('title');?></label>
                        
                        <div class="col-sm-6">
                            <input type="text" class="form-control" name="title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" value="" autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('class'); ?></label>
                        <div class="col-sm-6">
                            <select name="class_id" id = 'class_id' class="form-control select2" required>
                                <option value=""><?php echo get_phrase('select_a_class'); ?></option>
                                <?php
                    foreach($class_ids_creche as $subj):
                        $name_array = array('CRECHE', 'NURSERY');
                        $this->db->where_in('name', $name_array);
                        $classes = $this->db->get_where('class', array('class_id' =>  $subj['class_id']))->result_array();
                        foreach($classes as $row):
                            //add section A or B if the class has more than one section
                        $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                        $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                        $sec_name = '';
                        if($class_has_more_sections > 1) {
                            $sec_name = $section_name;
                        }
                    ?>
                    <option value="<?php echo $row['class_id'];?>"
                        <?php if($class_id == $row['class_id']) echo 'selected';?>><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
                    <?php endforeach; endforeach; //FOR CRECHE ?>

                    <?php
                    foreach($class_ids as $subj):
                        $classes = $this->db->get_where('class', array('class_id' =>  $subj['class_id']))->result_array();
                        foreach($classes as $row):
                            //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                            $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }
                    ?>
                    <option value="<?php echo $row['class_id'];?>"
                        <?php if($class_id == $row['class_id']) echo 'selected';?>><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
                    <?php endforeach; endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('exam'); ?></label>
                        <div class="col-sm-6">
                            <select name="exam_id" class="form-control" required>
                                <option value=""><?php echo get_phrase('select_an_exam'); ?></option>
                                <?php 
                                $exams = $this->db->get('exam')->result_array();
                                foreach ($exams as $row) { ?>
                                    <option value="<?php echo $row['exam_id']; ?>">
                                        <?php echo $row['name'];?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('question_paper');?></label>
                        
                        <div class="col-sm-9">
                            <textarea class="form-control wysihtml5" data-stylesheet-url="assets/css/wysihtml5-color.css" name="question_paper" required></textarea>
                        </div> 
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-5">
                            <button type="submit" id = "submit" class="btn btn-info"><?php echo get_phrase('submit');?></button>
                        </div>
                    </div>

                <?php echo form_close();?>

            </div>
        </div>
    </div>
</div>
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
            </script>
















