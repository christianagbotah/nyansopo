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

	.today_selected {
		background-color: #16c313 !important;
		color: #fff !important;
		font-size: 16px !important;
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
<?php echo form_open(site_url('admin/portfolio_assessment_selector'), array('id' => 'subject_loder_form'));?>
<div class="row">

	<div class="col-md-3">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('exam');?></label>
			<select name="exam_id" class="form-control selectboxit" required>
				<?php

					$class_name = $this->crud_model->get_class_name($class_id);

					if($class_name == 'JHSS') {
						$this->db->where('year', $running_year);
						$this->db->where('category_id', '1');
						$this->db->where('sem', $running_sem);
					} else {
						$this->db->where('year', $running_year);
						$this->db->where('category_id', '1');
						$this->db->where('term', $running_term);
					}
					$exams = $this->db->get_where('exam')->result_array();
					foreach($exams as $row):
				?>
				<option value="<?php echo $row['exam_id'];?>"
					<?php if($exam_id == $row['exam_id']) echo 'selected';?>><?php echo $row['name'];?></option>
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
						<select name="class_id" class="form-control selectboxit" onchange="get_class_subject(this.value)">
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
							<option value="<?php echo $row['class_id'];?>" <?php if($class_id == $row['class_id']) echo 'selected';?>><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
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
							<option value="<?php echo $rowc['class_id'];?>" <?php if($class_id == $row['class_id']) echo 'selected';?>><?php echo $rowc['name'].' '.$rowc['name_numeric'].$sec_name;?></option>
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
							<option value="<?php echo $row['class_id'];?>" <?php if($class_id == $row['class_id']) echo 'selected';?>><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
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
						<select name="class_id" class="form-control selectboxit" onchange="get_class_subject(this.value)">
							<option value=""><?php echo get_phrase('select_class');?></option>
							<?php
										$teacher_id = '';
                    getFullClassList($teacher_id, $class_id);
                ?>
						</select>
					</div>
				</div>
			<?php
		}
	?>

	
		<?php
				$section = $this->db->get_where('section' , array(
					'class_id' => $class_id 
				))->row()->section_id;
				?>
			<input type="hidden" name="section_id" id="section_id" value="<?php echo $section;?>">

			<div class="col-md-2">
			<div class="form-group">
			<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('week');?></label>
				<input type="week" class="form-control" name="week" id="week" value="<?=$week;?>" required="required">
			</div>
		</div>

		<div id="subject_holder">
		<div class="col-md-3">
			<div class="form-group">
			<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('subject');?></label>
				<select name="subject_id" onchange="load_subjects()" id="subject_id" class="form-control selectboxit">
					<?php 
						if($account_type == 'teacher') {

							if($class_name == 'JHSS') { 

								//if this is a class master or class teacher, grant full access to all the subjects
								if($class_teacher == $this->session->userdata('teacher_id')) {
									$subjects = $this->db->get_where('subject' , array(
									'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'sem' => $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description))->result_array();
								} else {
									$subjects = $this->db->get_where('subject' , array(
									'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'sem' => $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description, 'teacher_id' => $this->session->userdata('teacher_id')
									))->result_array();
								}

							} else {
								//if this is a class master or class teacher, grant full access to all the subjects
								if($class_teacher == $this->session->userdata('teacher_id')) {
									$subjects = $this->db->get_where('subject' , array(
									'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description))->result_array();
								} else {
									$subjects = $this->db->get_where('subject' , array(
									'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description, 'teacher_id' => $this->session->userdata('teacher_id')
									))->result_array();
								}

							}

						} else {

							if($class_name == 'JHSS') { 

								$subjects = $this->db->get_where('subject' , array(
								'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'sem' => $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description
								))->result_array();

							} else {
								$subjects = $this->db->get_where('subject' , array(
								'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description
							))->result_array();

							}
						}
						
						foreach($subjects as $row):
					?>
					<option value="<?php echo $row['subject_id'];?>"
						<?php if($subject_id == $row['subject_id']) echo 'selected';?>>
							<?php echo $row['name'];?>
					</option>
					<?php endforeach;?>
				</select>
			</div>
		</div>
		<div class="col-md-2" style="margin-top: 20px;">
			<center>
				<button type="submit" class="btn btn-info" id="btn_marks"><?php echo get_phrase('manage_marks');?></button>
			</center>
		</div>
	</div>

</div>
<?php echo form_close();?>

<hr />
<div class="row" style="text-align: center;">
	<div class="col-sm-4"></div>
	<div class="col-sm-4">
		<div class="tile-stats tile-gray">
			<div class="icon"><i class="entypo-chart-bar" style="color: #8082ec"></i></div>

			
			
			<?php
				if($class_name == 'JHSS') {
					?>
						<h4><?php echo get_phrase('marks_for');?> <?php echo $this->db->get_where('exam' , array('exam_id' => $exam_id, 'year' => $running_year, 'sem' => $running_sem))->row()->name;?></h4>
						<h4>
							<?php echo get_phrase('semester:').' '.$running_sem.' | '. get_phrase('sessional_year:').' '.explode('-', $running_year)[1];?>
						</h4>
					<?php
				} else {
					?>

						<h4><?php echo get_phrase('marks_for');?> <?php echo $this->db->get_where('exam' , array('exam_id' => $exam_id, 'year' => $running_year, 'term' => $running_term))->row()->name;?></h4>
						<h4>
							<?php echo get_phrase('term:').' '.$running_term.' | '. get_phrase('sessional_year:').' '.explode('-', $running_year)[1];?>
						</h4>
					<?php
				}
			?>
			<h4 style="color: #696969;">
				<?php echo $this->db->get_where('class' , array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;?> : 
				<?php echo get_phrase('section');?> <?php echo $this->db->get_where('section' , array('section_id' => $section_id))->row()->name;?> 
			</h4>

			<?php
				if($class_name == 'JHSS') {

					//get all the subjects for this class this semester
					//$subjects_query = $this->db->get_where('subject' , array('class_id' => $class_id, 'year' => $running_year, 'sem' => $running_sem));
					?>
					<h4 style="color: #696969;">
						<?php echo get_phrase('subject');?> : <?php echo $this->db->get_where('subject' , array('subject_id' => $subject_id, 'year' => $running_year, 'sem' => $running_sem))->row()->name;?>
					</h4>
					<?php
				} else {

					//get all the subjects for this class this term
					//$subjects_query = $this->db->get_where('subject' , array('class_id' => $class_id, 'year' => $running_year, 'term' => $running_term));
					?>
					<h4 style="color: #696969;">
						<?php echo get_phrase('subject');?> : <?php echo $this->db->get_where('subject' , array('subject_id' => $subject_id, 'year' => $running_year, 'term' => $running_term))->row()->name;?>
					</h4>
					<?php
				} ?>
			
		</div>
	</div>
	<div class="col-sm-4">
		<button class="btn btn-info btn-lg preview_mode" id="view_toggle"><i class="fa fa-search"></i> Preview</button>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		

		<?php

			$explode_week = explode('-', $week)[0].explode('-', $week)[1];


      $dates_array = array(
          strtotime($explode_week),
          strtotime($explode_week.' + 1 day'),
          strtotime($explode_week.' + 2 days'),
          strtotime($explode_week.' + 3 days'),
          strtotime($explode_week.' + 4 days')
      );


		?>

		<div id="edit_holder">
		<?php echo form_open(site_url('admin/portfolio_assessment_update/'), array('id' => 'portfolio_update_form'));?>

			<input type="hidden" name="exam_id" value="<?=$exam_id?>" >
			<input type="hidden" name="class_id" value="<?=$class_id?>" >
			<input type="hidden" name="section_id" value="<?=$section_id?>" >
			<input type="hidden" name="subject_id" value="<?=$subject_id?>" >
			<input type="hidden" name="week" value="<?=$week?>" >

			<table class="table table-bordered" id="mark_sheet">
				<thead>
					<tr>
						<!--<th rowspan="2">#</th>-->
						<th rowspan="2"><?php echo get_phrase('ID');?></th>
						<th rowspan="2"><?php echo get_phrase('name');?></th>
						<th colspan="1"><?php echo get_phrase('date');?></th>
						
						<?php
							for($d = 0; $d < sizeof($dates_array); $d++) { ?>
								<th colspan="1" style="text-align: center; padding-top: 20px;"><input type="text" id="date_<?php echo $dates_array[$d]; ?>" class="form-control <?=$dates_array[$d] == strtotime(date('d-m-Y')) ? 'today_selected' : '' ?>" value="<?=date('d-m-Y', $dates_array[$d]) ?>" name="date_<?php echo $dates_array[$d]; ?>" readonly="readonly" data-format="dd-mm-yyyy"></th>
								<?php
							}
						?>

					</tr>
					<tr>	
						<th ><?php echo get_phrase('code');?></th>	

						<?php
							for($d = 0; $d < sizeof($dates_array); $d++) { ?>
								<th style="text-align: center;"><div><input type="text" id="code_<?php echo $dates_array[$d]; ?>" class="form-control <?=$dates_array[$d] == strtotime(date('d-m-Y')) ? 'today_selected' : '' ?>" name="code_<?php echo $dates_array[$d]; ?>" value="<?=$this->db->get_where('portfolio_assessment', array('timestamp' => $dates_array[$d], 'subject_id' => $subject_id))->row()->code; ?>" <?=$dates_array[$d] == strtotime(date('d-m-Y')) ? 'required="required"' : '' ?> <?=$dates_array[$d] > strtotime(date('d-m-Y')) ? 'disabled="disabled"' : '' ?> <?=$dates_array[$d] < strtotime(date('d-m-Y')) ? 'readonly="readonly"' : '' ?> ></th>
								<?php
							}
						?>
						
					</tr>
				</thead>
				<tbody>
				<?php
					$count = 1;

					if($class_name == 'JHSS') { 

						$this->db->select('student_id');
						$this->db->distinct();
						$list_of_students = $this->db->get_where('portfolio_assessment' , array(
							'class_id' => $class_id, 
									'year' => $running_year,
										 'sem' => $running_sem,
											'subject_id' => $subject_id,
												'week' => $week,
													'exam_id' => $exam_id
						))->result_array();
					} else {

						$this->db->select('student_id');
						$this->db->distinct();
						$list_of_students = $this->db->get_where('portfolio_assessment' , array(
							'class_id' => $class_id, 
									'year' => $running_year,
										 'term' => $running_term,
											'subject_id' => $subject_id,
												'week' => $week,
													'exam_id' => $exam_id
						))->result_array();
					}
					

					$students_counter = 0;
					$students_ids = array();
					$assessment_ids = array();

					


					foreach($list_of_students as $row):
				?>
					<tr>
						<!--<td><?php echo $count++;?></td>-->

                        <td width="80"><?php echo $this->db->get_where('student',array('student_id'=>$row['student_id']))->row()->student_code;?></td>
          
						<td width="300" colspan="2">
							<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
						</td>


						
							<?php
							for($d = 0; $d < sizeof($dates_array); $d++) { 

								//retrieve the student's score for each strand -topic
									$strand_score_query = $this->db->get_where('portfolio_assessment', array('student_id' => $row['student_id'], 'timestamp' => $dates_array[$d], 'week' => $week, 'class_id' => $class_id, 'subject_id' => $subject_id))->row();

									$strand_score = $strand_score_query->strand_score;
									$assessment_id = $strand_score_query->assessment_id;
									$date_timestamp = $strand_score_query->timestamp;

									array_push($assessment_ids, $assessment_id); //push assessment ids here

								
								?>


								<td style="text-align: center" width="120">
									<input type="text" id="strand_<?php echo $row['student_id']; ?>" class="form-control <?=$date_timestamp == strtotime(date('d-m-Y')) ? 'today_selected' : '' ?>" name="strand_<?php echo $assessment_id;?>"
									value="<?php echo $strand_score;?>" max="100" <?=$date_timestamp > strtotime(date('d-m-Y')) ? 'disabled="disabled"' : '' ?> <?=$date_timestamp < strtotime(date('d-m-Y')) ? 'readonly="readonly"' : '' ?> >
								</td>
								<?php
							}
						?>

					</tr>


				<?php
				$students_counter++;
				array_push($students_ids, $row['student_id']); //push students ids here
				 endforeach;?>
				</tbody>
			</table>

		<center>
			<!--
			<input type="hidden" name="dates_array[]" value="<?=implode(',', $dates_array)?>" >
			<input type="hidden" name="assessment_ids" value="<?=implode(',', $assessment_ids)?>" >
			<input type="hidden" name="students_ids[]" value="<?=implode(',', $students_ids)?>" > -->

			<button type="submit" class="btn btn-success" id="submit_button">
				<i class="entypo-check"></i> <?php echo get_phrase('save_changes');?>
			</button>
		</center>
		<?php echo form_close();

		?>
		</div><!--Edit holder ends-->

		<div id="preview_holder" style="display: none;">
			<?php
				//subjects selections
				if($class_name == 'JHSS') {
					$subjects_query = $this->db->get_where('subject', array('class_id' => $class_id, 'year' => $running_year, 'sem' => $running_sem));
				} else {
					$subjects_query = $this->db->get_where('subject', array('class_id' => $class_id, 'year' => $running_year, 'term' => $running_term));
				}
			?>

			<div id="print_preview">
			<table class="table table-bordered" id="mark_sheet_preview" style="width:100%; border-collapse:collapse;border: 1px solid #000; margin-top: 10px;" border="1">
				<thead>
					<tr>
						<!--<th rowspan="2">#</th>-->
						<!--<th rowspan="2"><?php echo get_phrase('ID');?></th>-->
						<th rowspan="2"><?php echo get_phrase('name');?></th>
						<th><?php echo get_phrase('date');?></th>
						
						<?php
							for($d = 0; $d < sizeof($dates_array); $d++) { ?>
								<th colspan="<?=$subjects_query->num_rows();?>" style="text-align: center; padding-top: 20px;"><?=date('d-m-Y', $dates_array[$d]) ?></th>
								<?php
							}
						?>

					</tr>
					<tr>	
						<th ><?php echo get_phrase('code');?></th>	

						<?php
							for($d = 0; $d < sizeof($dates_array); $d++) { //each day
								foreach($subjects_query->result_array() as $sb):
								?>
								<th style="text-align: center;"><div><?=$this->db->get_where('portfolio_assessment', array('timestamp' => $dates_array[$d], 'subject_id' => $sb['subject_id']))->row()->code;  ?> <br><small><?=substr($this->db->get_where('subject', array('subject_id' => $sb['subject_id']))->row()->name, 0, 6); ?></small></th>
								<?php
							endforeach;
							}
						?>
						
					</tr>
				</thead>
				<tbody>
				<?php
					$count = 1;

					if($class_name == 'JHSS') { 

						$this->db->select('student_id');
						$this->db->distinct();
						$list_of_students = $this->db->get_where('portfolio_assessment' , array(
							'class_id' => $class_id, 
									'year' => $running_year,
										 'sem' => $running_sem,
											'subject_id' => $subject_id,
												'week' => $week,
													'exam_id' => $exam_id
						))->result_array();
					} else {

						$this->db->select('student_id');
						$this->db->distinct();
						$list_of_students = $this->db->get_where('portfolio_assessment' , array(
							'class_id' => $class_id, 
									'year' => $running_year,
										 'term' => $running_term,
											'subject_id' => $subject_id,
												'week' => $week,
													'exam_id' => $exam_id
						))->result_array();
					}
					

					$students_counter = 0;
					$students_ids = array();
					$assessment_ids = array();

					


					foreach($list_of_students as $row):
				?>
					<tr>
						<!--<td><?php echo $count++;?></td>

                        <td width="80"><?php echo $this->db->get_where('student',array('student_id'=>$row['student_id']))->row()->student_code;?></td> -->
          
						<td width="300" colspan="2">
							<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
						</td>


						
							<?php
							for($d = 0; $d < sizeof($dates_array); $d++) { //each date

								foreach($subjects_query->result_array() as $sub):

								//retrieve the student's score for each strand -topic
									$strand_score_query = $this->db->get_where('portfolio_assessment', array('student_id' => $row['student_id'], 'timestamp' => $dates_array[$d], 'week' => $week, 'class_id' => $class_id, 'subject_id' => $sub['subject_id']))->row();

									$strand_score = $strand_score_query->strand_score;
									$assessment_id = $strand_score_query->assessment_id;
									$date_timestamp = $strand_score_query->timestamp;

									array_push($assessment_ids, $assessment_id); //push assessment ids here

								
								?>


								<td style="text-align: center" width="120">
									<?php
										$grade = $this->crud_model->get_grade($strand_score);
                    echo $grade['grade_point'];
								 ?>
								 	
								 </td>
								<?php
							endforeach;
							}
						?>

					</tr>


				<?php
				$students_counter++;
				array_push($students_ids, $row['student_id']); //push students ids here
				 endforeach;?>
				</tbody>
			</table>
		</div>
			<div class="row pull-right">
				<button class="btn btn-primary btn-lg" onclick="PrintElem('#print_preview');" style="text-align: right"><i class="fa fa-print"></i> Print</button>
			</div>



		</div> <!--Preview holder ends-->
	</div>
</div>





<script type="text/javascript">

	$(document).ready(function() {
		$('#mark_sheet').dataTable();
		set_val();

	});

	//toggle preview button
	$('#view_toggle').click(function(event) {
		/* Act on the event */
		//currently set to preview mode
		if($(this).hasClass('preview_mode')) {
			$('#edit_holder').slideUp('slow');
			$('#preview_holder').slideDown('slow');
			$('#main_div').addClass('sidebar-collapsed');//collapse the side bar
			$('#main_div').css({
				'transition': 'padding 600ms ease 0s',
				'padding-left': '65px'
			});

			$(this).removeClass('preview_mode');
			$(this).removeClass('btn-info');
			$(this).addClass('btn-success');
			$(this).addClass('edit_mode');
			$(this).html('<i class="fa fa-pencil"></i> Edit');

		} else if($(this).hasClass('edit_mode')) {
			$('#preview_holder').slideUp('slow');
			$('#edit_holder').slideDown('slow');
			$('#main_div').removeClass('sidebar-collapsed');
			$('#main_div').css({
				'transition': 'padding 600ms ease 0s',
				'padding-left': '280px'
			});
			$(this).removeClass('edit_mode');
			$(this).removeClass('btn-success');
			$(this).addClass('btn-info');
			$(this).addClass('preview_mode');
			$(this).html('<i class="fa fa-search"></i> Preview');
		}
	});

	function load_subjects() {
		$('#subject_loder_form').submit();
		$('#btn_marks').text('Loading...');
	}

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
            url: '<?php echo site_url('admin/marks_get_subject/');?>' + class_id + '/portfolio',
            success: function(response)
            {
                jQuery('#subject_holder').html(response);
            }
        });
	  }
	}

$('#portfolio_update_form').submit(function(e) {
	$('html, body').animate({
          scrollTop: ($('#top').offset().top )
    }, 1000); 

	e.preventDefault();

	let form_data = $('#portfolio_update_form').serialize();
	let dates = [];
	let st_ids = [];
	let ass_ids = [];

	dates = "<?=implode('-', $dates_array)?>";
	st_ids = "<?=implode('-', $students_ids)?>";
	ass_ids = "<?=implode('-', $assessment_ids)?>";

	showAjaxModal_alert('Please wait, we are working on it <i class="fa fa-spinner fa-pulse"></i>', 'Loading');

	$.ajax({
		url: '<?php echo site_url('admin/portfolio_assessment_update/') ?>' + dates + '/' + st_ids + '/' + ass_ids,
		type: 'POST',
		dataType: 'json',
		data: form_data,
	})
	.done(function(response) {

	//	showAjaxModal_alert(response.success, 'Success');
		if(response.success == 1) {
			showAjaxModal_alert('Portfolio Assessment Updated Successfully.', 'Success');

			setTimeout(() => {
			  window.location.reload();
			}, 600);

		} else if(response.success == 0) {
			showAjaxModal_alert('Update Failed. Make you selected Class and Exam types. Please try again.', 'Error');
		} 
	})
	.fail(function(err) {
		showAjaxModal_alert('Error: ' + err.responseText, 'Error');
	});
	
})

//print
 function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        //mywindow.document.write('<link rel="stylesheet" href="assets/css/chartjs/dist/Chart.min.css" type="text\/css" \/>');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.css');?>" />');
        mywindow.document.write('<script src="<?php echo base_url('assets/css/chartjs/dist/Chart.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script type="text\/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');

        mywindow.document.write('<\/head><body style="font-size: 13px">');
        mywindow.document.write(data);

        mywindow.document.write('<\/body><\/html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }


    function print_page() {
        //window.location.reload();
        print();
       }

       function page_reload() {
        window.location.reload();
       }


        $(function() {
            setTimeout(() => {
             // print_page();
            }, 1000);
            
        });

</script>