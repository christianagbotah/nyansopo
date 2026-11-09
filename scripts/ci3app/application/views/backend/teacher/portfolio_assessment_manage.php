<hr />
<?php
//echo 'checking '.date('d-m-Y', strtotime('2021W03 + 2 days'));
	if(isset($_GET['error']) && $_GET['error'] == 1) { 
		if(isset($_GET['term'])) {
		?>
		<div class="alert alert-danger alert-dismissable" role="alert" aria-label="alert" style="text-align: center;">
			<button class="close" data-dismiss="alert">&times;</button>
			<strong><?= get_phrase('seems_students_attendance_for_term_'.$_GET['term'].'_has_not_been_marked_yet._please_mark_students_attendance_first_to_enroll_students_for_this_term_before_you_proceed.'); ?></strong>
		</div>
<?php
		} else {
			if(isset($_GET['sem'])) {
		?>
		<div class="alert alert-danger alert-dismissable" role="alert" aria-label="alert" style="text-align: center;">
			<button class="close" data-dismiss="alert">&times;</button>
			<strong><?= get_phrase('seems_students_attendance_for_semester_'.$_GET['sem'].'_has_not_been_marked_yet._please_mark_students_attendance_first_to_enroll_students_for_this_semester_before_you_proceed.'); ?></strong>
		</div>
<?php
		}
		}
	}
 ?>

 <?php
 //No subject selected error
	if(isset($_GET['subjr']) && $_GET['subjr'] == 1) { ?>
		<div class="alert alert-danger alert-dismissable" role="alert" aria-label="alert" style="text-align: center;">
			<button class="close" data-dismiss="alert">&times;</button>
			<strong><?= get_phrase('no_subject_was_found_for_this_class._please_add_subjects_for_this_class_and_try_again!'); ?></strong>
		</div>
<?php
	}
 ?>
<?php echo form_open(site_url('admin/portfolio_assessment_selector'), array('id' => 'portfolio_form'));?>
<div class="row">

	<div class="col-md-3">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('exam');?></label>
			<select name="exam_id" class="form-control select2">
				<option value=""><?php echo get_phrase('select_exam_type');?></option>
				<?php
					$this->db->where('year', $running_year);
					$this->db->where('term', $running_term);
					$this->db->or_where('sem', $running_sem);
					$exams = $this->db->get_where('exam')->result_array();
					foreach($exams as $row):
				?>
				<option value="<?php echo $row['exam_id'];?>"><?php echo $row['name'];?></option>
				<?php endforeach;?>
			</select>
		</div>
	</div>

	<?php
		if($account_type == 'teacher') {
			?>
				<div class="col-md-2">
					<div class="form-group">
					<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('class');?></label>
						<select name="class_id" class="form-control select2" onchange="get_class_subject(this.value)">
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
							<option value="<?php echo $row['class_id'];?>"><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
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
							<option value="<?php echo $rowc['class_id'];?>"><?php echo $rowc['name'].' '.$rowc['name_numeric'].$sec_name;?></option>
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
							<option value="<?php echo $row['class_id'];?>"><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
							<?php endforeach; endforeach; ?>
						</select>
					</div>
				</div>
			<?php
		} else {
			?>
				<div class="col-md-2">
					<div class="form-group">
					<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('class');?></label>
						<select name="class_id" class="form-control select2" onchange="get_class_subject(this.value)">
							<option value=""><?php echo get_phrase('select_class');?></option>
							<?php

                    getFullClassList();
                ?>
						</select>
					</div>
				</div>
			<?php
		}
	?>
	

	
		<div class="col-md-2">
			<div class="form-group">
			<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('week');?></label>
				<input type="week" class="form-control" name="week" id="week" required="required">
			</div>
		</div>

		<div id="subject_holder">
		<div class="col-md-3">
			<div class="form-group">
			<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('subject');?></label>
				<select name="" id="" class="form-control select2" disabled="disabled">
					<option value=""><?php echo get_phrase('select_class_first');?></option>		
				</select>
			</div>
		</div>
		<div class="col-md-2" style="margin-top: 20px;">
			<center>
				<button type="submit" class="btn btn-info" id = "submit"><?php echo get_phrase('manage_assessment');?></button>
			</center>
		</div>
	</div>

</div>
<?php echo form_close();?>





<script type="text/javascript">
jQuery(document).ready(function($) {
	$("#submit").attr('disabled', 'disabled');
});
	function get_class_subject(class_id) {
		if (class_id !== '') {
		$.ajax({
            url: '<?php echo site_url('admin/marks_get_subject/');?>' + class_id + '/portfolio',
            success: function(response)
            {
                jQuery('#subject_holder').html(response);
            }
        });
        $('#submit').removeAttr('disabled');
	  }
	  else{
	  	$('#submit').attr('disabled', 'disabled');
	  }
	}


</script>