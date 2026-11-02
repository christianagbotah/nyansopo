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
<style type="text/css">
	table.table-bordered.dataTable th:last-child, table.table-bordered.dataTable th:last-child {
		border-right-width: 1px !important;
	}

	th {
		padding-bottom: 20px !important;
	}

	.form-inline .form-control {
		width: 50px !important;
	}

	.transf {
		transform: rotate(-90deg);
	}

	@media 
only screen and (min-width: 1530px),
  {
  	.lgt {
  		margin-bottom: 10px;
  	}
  }

</style>
<hr />
<?php echo form_open(site_url('teacher/marks_selector_creche'));?>
<div class="row">

	<div class="col-md-3">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('exam');?></label>
			<select name="exam_id" class="form-control select2" required>
				<?php
					$exams = $this->db->get_where('exam' , array('year' => $running_year, 'term' => $running_term))->result_array();
					foreach($exams as $row):
				?>
				<option value="<?php echo $row['exam_id'];?>"
					<?php if($exam_id == $row['exam_id']) echo 'selected';?>><?php echo $row['name'];?></option>
				<?php endforeach;?>
			</select>
		</div>
	</div>

	<div class="col-md-2">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('class');?></label>
			<select name="class_id" class="form-control select2" onchange="get_class_subject(this.value)">
				<option value=""><?php echo get_phrase('select_class');?></option>
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
                            $sec_name = ' '.$section_name;
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

	<div id="subject_holder">
		<div class="col-md-2">
			<div class="form-group">
			<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section');?></label>
				<select name="section_id" id="section_id" class="form-control select2">
					<?php 
						$sections = $this->db->get_where('section' , array(
							'class_id' => $class_id 
						))->result_array();
						foreach($sections as $row):
					?>
					<option value="<?php echo $row['section_id'];?>" 
						<?php if($section_id == $row['section_id']) echo 'selected';?>>
							<?php echo $row['name'];?>
					</option>
					<?php endforeach;?>
				</select>
			</div>
		</div>
		
		<div class="col-md-4">
    	<div class="form-group">
    	<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('subject_category');?></label>
    		<select name="category_id" id="category_id" class="form-control select2">
    			<option value="">SELECT CATEGORY</option>
				<option value="0" selected="selected">SUBJECTS WITH NO CATEGORY</option>
    			<?php 
    			    $category_id2 = '';
    				$categories = $this->db->get('subject_category_creche')->result_array();
    				foreach($categories as $row):
    				$category_id2 = $row['category_id'];
    			?>
    			<option value="<?php echo $row['category_id'];?>"><?php echo $row['name'];?></option>
    			<?php endforeach;?>
    		</select>
    	</div>
    	<span style="color:red; display: none;" id="error_notec"></span>
    </div>
    <div class="row">
        <div class="col-md-8">
			<div class="form-group">
			<label class="control-label col-md-2" style="margin-bottom: 5px;"><?php echo get_phrase('subject');?></label>
				<select name="subject_id" id="subject_id" class="form-control col-md-6">
					<?php 
						$subjects = $this->db->get_where('subject_creche' , array(
							'class_id' => $class_id, 'category_id' => $category_id, 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
						))->result_array();
						foreach($subjects as $row):
					?>
					<option value="<?php echo $row['subject_id'];?>"
						<?php if($subject_id == $row['subject_id']) echo 'selected';?>>
							<?php echo $row['name'];?>
					</option>
					<?php endforeach;?>
				</select>
			</div>
			<span style="color:red; display: none;" id="error_note"></span>
		</div>
		<div class="col-md-4" style="margin-top: 20px;">
			<center>
				<button type="submit" class="btn btn-info" id="btn_marks"><?php echo get_phrase('manage_marks');?></button>
			</center>
		</div>
		
		<!-- View Marksheet Button - Only for class/form masters -->
		<div class="col-md-4" style="margin-top: 20px;">
			<?php
				// Check if current teacher is the class/form master
				$class_info = $this->db->get_where('class', array('class_id' => $class_id))->row();
				$is_class_master = ($class_info && $class_info->teacher_id == $this->session->userdata('teacher_id'));
				
				if($is_class_master):
					// Get first enrolled student in this class for current academic session
					$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
					$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
					
					$first_student = $this->db->select('student_id')
						->from('enroll')
						->where('class_id', $class_id)
						->where('year', $running_year)
						->where('term', $running_term)
						->where('mute', '0')
						->order_by('student_id', 'ASC')
						->limit(1)
						->get()
						->row();
					
					if($first_student):
						$marksheet_url = site_url('teacher/student_marksheet_creche/'.$first_student->student_id);
			?>
			<center>
				<a href="<?php echo $marksheet_url; ?>" class="btn btn-primary">
					<i class="entypo-doc-text"></i> <?php echo get_phrase('view_marksheet');?>
				</a>
			</center>
			<?php 
					endif;
				endif; 
			?>
		</div>
    </div>
	</div>

</div>
<?php echo form_close();?>

<hr />
<div class="row" style="text-align: center;">
    <div class="col-sm-2"></div>
    <div class="col-sm-2">
       <h3><?php echo get_phrase('subject');?>:</h3>
    </div>
    <div class="col-sm-6">
        <h4 style="color: #696969; text-align: left;">
		<?php echo $this->db->get_where('subject_creche' , array('subject_id' => $subject_id, 'category_id' => $category_id, 'year' => $running_year, 'term' => $running_term))->row()->name;?>
	</h4>
    </div>
    <div class="col-sm-2"></div>
</div>
<div class="row" style="text-align: center;">
	<div class="col-sm-4"></div>
	<div class="col-sm-4">
	    
		<div class="tile-stats tile-gray">
			<div class="icon"><i class="entypo-chart-bar" style="color: #8082ec"></i></div>
			
			<h4><?php echo get_phrase('marks_for');?> <?php echo $this->db->get_where('exam' , array('exam_id' => $exam_id, 'year' => $running_year, 'term' => $running_term))->row()->name;?></h4>
			<h4>
				<?php echo get_phrase('term:').' '.$running_term.' | '. get_phrase('sessional_year:').' '.$running_year;?>
			</h4>
			<h4 style="color: #696969;">
				<?php echo $this->db->get_where('class' , array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;?> : 
				<?php echo get_phrase('section');?> <?php echo $this->db->get_where('section' , array('section_id' => $section_id))->row()->name;?> 
			</h4>
		</div>
	</div>
	<div class="col-sm-4"></div>
</div>
<div class="row">
	<div class="col-md-12">

		<?php echo form_open(site_url('teacher/marks_update/'.$exam_id.'/'.$class_id.'/'.$section_id.'/'.$subject_id. '/'. $category_id));?>
			<table class="table table-bordered" id="mark_sheet">
				<thead>
					<tr>
						<th rowspan="2">#</th>
						<th rowspan="2"><?php echo get_phrase('id');?></th>
						<th rowspan="2"><?php echo get_phrase('name');?></th>
						<th colspan="4" style="text-align: center; padding-top: 20px;">TASKS</th>
						
						<th rowspan="2" style="text-align: center; height: 60px; width: 40px;"><div class="transf lgt">Sub Total</div></th>
						<th rowspan="2" style="text-align: center;">50%<br>(A)</th>
						<th rowspan="2" style="text-align: center; height: 60px; width: 40px;"><div class="transf lgt">Term Exam</div></th>
						<th rowspan="2" style="text-align: center;">50%<br>(B)</th>
						<th rowspan="2" style="text-align: center;">Total<br>(A+B)</th>
					</tr>
					<tr>						
						<th style="text-align: center; height: 60px; width: 40px;"><div class="transf">Test 1</div></th>
						<th style="text-align: center; height: 60px; width: 40px;"><div class="transf lgt">Group Work</div></th>
						<th style="text-align: center; height: 60px; width: 40px;"><div class="transf">Test 2</div></th>
						<th style="text-align: center; height: 60px; width: 40px;"><div class="transf">Project</div></th>
					</tr>
				</thead>
				<tbody>
				<?php
					$count = 1;
					$marks_of_students = $this->db->get_where('mark' , array(
						'class_id' => $class_id, 
							'section_id' => $section_id ,
								'year' => $running_year,
									 'term' => $running_term,
										'subject_id' => $subject_id,
											'exam_id' => $exam_id
					))->result_array();

					$students_counter = 0;
					
					foreach($marks_of_students as $row):
				?>
					<tr>
						<td><?php echo $count++;?></td>

                        <td><?php echo $this->db->get_where('student',array('student_id'=>$row['student_id']))->row()->student_code;?></td>

						<td>
							<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
						</td>

						
							
							<td style="text-align: center">
								<input type="text" id="test1_<?php echo $row['student_id']; ?>" class="form-control" name="test1_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['test1'];?>" max="10" onkeyup="update_sub_total()" onchange="update_sub_total()">
							</td>
							<td style="text-align: center">
								<input type="text" id="group_work_<?php echo $row['student_id']; ?>" class="form-control" name="group_work_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['group_work'];?>" max="10" onkeyup="update_sub_total()" onchange="update_sub_total()">
							</td>
							<td style="text-align: center">
								<input type="text" id="test2_<?php echo $row['student_id']; ?>" class="form-control" name="test2_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['test2'];?>" max="10" onkeyup="update_sub_total()" onchange="update_sub_total()">
							</td>
							<td style="text-align: center">
								<input type="text" id="project_<?php echo $row['student_id']; ?>" class="form-control" name="project_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['project'];?>" max="20" onkeyup="update_sub_total()" onchange="update_sub_total()">
							</td>


						

						<td style="text-align: center">
							<input type="text" id="sub_total_<?php echo $row['student_id']; ?>" class="form-control" name="sub_total_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['sub_total'];?>" onkeyup="update_total_score()" readonly>
						</td>

						<td style="text-align: center">
							<input type="text" id="class_score_<?php echo $row['student_id']; ?>" class="form-control class_score" name="class_score_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['class_score'];?>" max="50" readonly>
						</td>

						<td style="text-align: center">
							<input type="text" id="term_exam_<?php echo $row['student_id']; ?>" class="form-control" name="term_exam_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['term_exam'];?>" onkeyup="update_exam_score()" onchange="update_exam_score()">
						</td>

						<td style="text-align: center">
							<input type="text" id="exam_score_<?php echo $row['student_id']; ?>"  class="form-control exam_score" name="exam_score_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['exam_score'];?>" max="50" readonly>
						</td>
						<td style="text-align: center">
							<span style="font-weight: bold";><input type="text" id="total_score_<?php echo $row['student_id']; ?>" class="form-control" name="marks_obtained_<?php echo $row['mark_id'];?>"
								value="<?php echo $row['mark_obtained'];?>" readonly></span>	
						</td>

					</tr>
				<?php
				$students_counter++;
				 endforeach;?>
				</tbody>
			</table>

		<center>
			<button type="submit" class="btn btn-success" id="submit_button">
				<i class="entypo-check"></i> <?php echo get_phrase('save_changes');?>
			</button>
		</center>
		<?php echo form_close();?>
		
	</div>
</div>





<script type="text/javascript">

	$(document).ready(function() {
		$('#mark_sheet').DataTable({
			pageLength: 100,
		});
		
		set_val();
		
	});

	function set_val() {
		let students_counter = <?php echo $students_counter; ?>;

		let val1 = students_counter;
		$('#mark_sheet_length select').attr('id', 'show_val');
		$('#mark_sheet_length select').append('<option value="' + val1 + '"></option>');
		$('#mark_sheet_length select option[value=' + val1 + ']').attr('selected', 'selected').change();

		$('#mark_sheet_length select').attr('disabled', 'disabled');
		$('#mark_sheet_length select').change(function(event) {
			return false;
		});
	}


	function get_class_subject(class_id) {
	if (class_id !== '') {
	$.ajax({
            url: '<?php echo site_url('teacher/marks_get_subject_creche/');?>' + class_id ,
            success: function(response)
            {
                jQuery('#subject_holder').html(response);
            }
        });
	  }
	}


	//update subtotal area on onkeydown event
	function update_sub_total(){
		
		var max_student_id = <?php echo $this->crud_model->get_max_student_id($class_id, $subject_id); ?>;
		

		for(var i = 1; i <= max_student_id; i++){
			var exam_score =  Number($("#exam_score_"+i).val());

			var test1       =  Number($("#test1_"+i).val());
			var group_work  =  Number($("#group_work_"+i).val());
			var test2       =  Number($("#test2_"+i).val());
			var project     =  Number($("#project_"+i).val());

			$("#sub_total_"+i).val(test1 + group_work + test2 + project);

			var sub_total = Number($("#sub_total_"+i).val());
			$("#class_score_"+i).val(sub_total);

			var class_score =  Number($("#class_score_"+i).val());
			$("#total_score_" + i).val(class_score + exam_score);
		}
		
	}


	//update exam score area on onkeydown event
	function update_exam_score(){
		
		var max_student_id = <?php echo $this->crud_model->get_max_student_id($class_id, $subject_id); ?>;
		

		for(var i = 1; i <= max_student_id; i++){
			var class_score =  $("#class_score_"+i).val();
			class_score = Number(class_score);

			var term_exam = $("#term_exam_"+i).val();
				
				term_exam = Number(term_exam);
			
			$("#exam_score_" + i).val(term_exam / 2);

			var exam_score =  $("#exam_score_"+i).val();
			exam_score = Number(exam_score);

			$("#total_score_" + i).val(class_score + exam_score);
		}
		
	}

	//update total score area on onkeydown event
	function update_total_score(){
		
		var max_student_id = <?php echo $this->crud_model->get_max_student_id($class_id, $subject_id); ?>;

		for(var i = 1; i <= max_student_id; i++){
			var cs = $("#class_score_"+i).val();
			var es = $("#exam_score_"+i).val();
				cs = Number(cs);
				es = Number(es);
			
			$("#total_score_" + i).val(cs + es);
		}
		
	}

	$('#submit_button').click(function() {
		var max_student_id = <?php echo $this->crud_model->get_max_student_id($class_id, $subject_id); ?>;

		for (var i = 1; i <= max_student_id; i++) {

			//for column A
			if($('#class_score_'+i).val() > 50) {
				toastr.error('Each mark in column (A) must not be greater than 50.');
				showAjaxModal_alert('Each mark in column (A) must not be greater than 50.', 'Warning', false, true);
				return false;
			}

			//for column test 1, group work and test 2
			if($('#test1_'+i).val() > 10 || $('#test2_'+i).val() > 10 || $('#group_work_'+i).val() > 10) {
				toastr.error('Each mark in columns (Test 1, Test 2 and Group Work) must not be greater than 10.');
				showAjaxModal_alert('Each mark in columns (Test 1, Test 2 and Group Work) must not be greater than 10.', 'Warning', false, true);
				return false;
			}

			//for column prodject
			if($('#project_'+i).val() > 20) {
				toastr.error('Each mark in column (Project) must not be greater than 20.');
				showAjaxModal_alert('Each mark in column (Project) must not be greater than 20.', 'Warning', false, true);
				return false;
			}

			//for column B
			if($('#exam_score_'+i).val() > 50) {
				toastr.error('Each mark in column (B) must not be greater than 50.');
				showAjaxModal_alert('Each mark in column (B) must not be greater than 50.', 'Warning', false, true);
				return false;
			}
		}
	})
	
		//update subjects if category field changes
		$('#category_id').on("change", function() {
		    $('#error_note').css('display', 'none');//clear error if there was any
		    $('#error_notec').css('display', 'none');//clear error if there was any
		   let cat_id = $('#category_id').val();
		   const class_id = '<?php echo $class_id; ?>';
		   
		   //send via ajax
		   $.ajax({
		      url: '<?php echo site_url('teacher/update_subjects_creche/'); ?>' + cat_id + '/' + class_id,
		      success: function(response) {
		          $('#subject_id').html(response);
		      }
		   });
		});
		
		//check if subject is selected
		$('#btn_marks').on("click", function() {
		   if($('#subject_id').val() == '' || $('#subject_id').val() == null) {
		       $('#error_note').css('display', 'block');
		       $('#error_note').text('No subject was selected for the category. Change the category to update the subject field!');
		       return false;
		   } if($('#category_id').val() == '' || $('#category_id').val() == null) {
		       $('#error_notec').css('display', 'block');
		       $('#error_notec').text('Select a category!');
		       return false;
		   } else {
		       $('#error_note').css('display', 'none');
		       $('#error_notec').css('display', 'none');
		   }
		});
	
</script>