<?php
    $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
    $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

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

<?php 
$edit_data = $this->db->get_where('question_paper', array('question_paper_id' => $param2))->result_array();
foreach ($edit_data as $row) { ?>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary" data-collapsed="0">
                <div class="panel-heading">
                    <div class="panel-title" >
                        <i class="entypo-plus-circled"></i>
                        <?php echo get_phrase('edit_question_paper');?>
                    </div>
                </div>

                <div class="panel-body">
                    
                    <?php echo form_open(site_url('teacher/question_paper/update/'. $param2 ), array('class' => 'form-horizontal form-groups-bordered validate',
                        'enctype' => 'multipart/form-data')); ?>
        
                        <div class="form-group">
                            <label for="field-1" class="col-sm-3 control-label"><?php echo get_phrase('title');?></label>
                            
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" 
                                    value="<?php echo $row['title']; ?>" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('class'); ?></label>
                            <div class="col-sm-6">
                                <select name="class_id" class="form-control select2" required>
                                    <option value=""><?php echo get_phrase('select_a_class'); ?></option>
                                    <?php
                                        foreach($class_ids_creche as $subj):
                                            $name_array = array('CRECHE', 'NURSERY');
                                            $this->db->where_in('name', $name_array);
                                            $classes = $this->db->get_where('class', array('class_id' =>  $subj['class_id']))->result_array();
                                            foreach($classes as $row2):
                                                //add section A or B if the class has more than one section
                                            $section_name = $this->db->get_where('section', array('class_id' => $row2['class_id']))->row()->name;
                                            $class_has_more_sections = $this->db->get_where('class', array('name' => $row2['name'], 'name_numeric' => $row2['name_numeric']))->num_rows();
                                            $sec_name = '';
                                            if($class_has_more_sections > 1) {
                                                $sec_name = $section_name;
                                            }
                                        ?>
                                        <option value="<?php echo $row2['class_id'];?>"
                                            <?php if($row['class_id'] == $row2['class_id']) echo 'selected';?>><?php echo $row2['name'].' '.$row2['name_numeric'].$sec_name;?></option>
                                        <?php endforeach; endforeach; //FOR CRECHE ?>

                                        <?php
                                        foreach($class_ids as $subj):
                                            $classes = $this->db->get_where('class', array('class_id' =>  $subj['class_id']))->result_array();
                                            foreach($classes as $row2):
                                                //add section A or B if the class has more than one section
                                                $section_name = $this->db->get_where('section', array('class_id' => $row2['class_id']))->row()->name;
                                                $class_has_more_sections = $this->db->get_where('class', array('name' => $row2['name'], 'name_numeric' => $row2['name_numeric']))->num_rows();
                                                $sec_name = '';
                                                if($class_has_more_sections > 1) {
                                                    $sec_name = $section_name;
                                                }
                                        ?>
                                        <option value="<?php echo $row2['class_id'];?>"
                                            <?php if($row['class_id'] == $row2['class_id']) echo 'selected';?>><?php echo $row2['name'].' '.$row2['name_numeric'].$sec_name;?></option>
                                        <?php endforeach; endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo get_phrase('exam'); ?></label>
                            <div class="col-sm-6">
                                <select name="exam_id" class="form-control select2" required>
                                    <option value=""><?php echo get_phrase('select_an_exam'); ?></option>
                                    <?php 
                                    $exams = $this->db->get('exam')->result_array();
                                    foreach ($exams as $row2) { ?>
                                        <option value="<?php echo $row2['exam_id']; ?>" <?php if($row2['exam_id'] == $row['exam_id']) echo 'selected'; ?>>
                                            <?php echo $row2['name'];?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    
                        <div class="form-group">
                            <label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('question_paper');?></label>
                            
                            <div class="col-sm-9">
                                <textarea class="form-control wysihtml5" data-stylesheet-url="<?php echo base_url('assets/css/wysihtml5-color.css');?>" name="question_paper"><?php echo $row['question_paper']; ?></textarea>
                            </div> 
                        </div>
                        
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-5">
                                <button type="submit" class="btn btn-info"><?php echo get_phrase('update');?></button>
                            </div>
                        </div>

                    <?php echo form_close(); ?>

                </div>
            </div>
        </div>
    </div>

<?php } ?>





















