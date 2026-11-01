<style type="text/css">
	<?php
	// Get theme colors from database
	$theme_primary = $this->db->get_where('settings', array('type' => 'theme_primary'))->row()->description ?? '#667eea';
	$theme_secondary = $this->db->get_where('settings', array('type' => 'theme_secondary'))->row()->description ?? '#764ba2';
	?>
	
	/* Modern Portfolio Assessment Styles */
	.portfolio-marks-container {
		background: #f8f9fa;
		padding: 20px;
		border-radius: 10px;
		margin-bottom: 0; /* Remove excess margin */
	}
	
	.marks-info-panel {
		background: linear-gradient(135deg, <?= $theme_primary ?> 0%, <?= $theme_secondary ?> 100%);
		color: white;
		padding: 20px;
		border-radius: 8px;
		margin-bottom: 25px;
		box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
	}
	
	.marks-info-panel h4 {
		color: white;
		font-weight: 600;
		margin-bottom: 10px;
	}
	
	.marks-info-panel .info-row {
		display: flex;
		gap: 30px;
		flex-wrap: wrap;
		margin-top: 15px;
	}
	
	.marks-info-panel .info-item {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	
	.marks-info-panel .info-item i {
		font-size: 16px;
	}

	table.table-bordered.dataTable th:last-child, 
	table.table-bordered.dataTable th:last-child {
		border-right-width: 1px !important;
	}

	#mark_sheet {
		background: white;
		border-radius: 8px;
		overflow: hidden;
		box-shadow: 0 2px 8px rgba(0,0,0,0.1);
	}

	#mark_sheet th {
		background: linear-gradient(135deg, <?= $theme_primary ?> 0%, <?= $theme_secondary ?> 100%);
		color: white;
		padding: 15px 10px !important;
		font-weight: 500;
		text-align: center;
		border-color: rgba(255,255,255,0.2) !important;
		position: sticky;
		top: 0;
		z-index: 10;
	}

	#mark_sheet td {
		padding: 10px;
		vertical-align: middle;
	}

	#mark_sheet tbody tr:hover {
		background-color: #f8f9fa;
		transition: background-color 0.2s;
	}

	.form-inline .form-control {
		width: 80px !important;
		border-radius: 6px;
		border: 1px solid #d1d5db;
		text-align: center;
		font-weight: 500;
		transition: all 0.3s;
		padding: 8px 12px;
		font-size: 14px;
	}
	
	.form-inline .form-control:focus {
		border-color: <?= $theme_primary ?>;
		box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
		transform: scale(1.05);
		outline: none;
	}
	
	.form-inline .form-control:hover:not(:disabled):not([readonly]) {
		border-color: <?= $theme_primary ?>;
		background-color: #f8f9ff;
	}

	.transf {
		transform: rotate(-90deg);
	}

	.today_selected {
		background-color: #10b981 !important;
		color: #fff !important;
		font-size: 14px !important;
		font-weight: 600 !important;
		border-color: #059669 !important;
	}
	
	.today_selected:focus {
		background-color: #059669 !important;
		border-color: #047857 !important;
	}
	
	.student-name {
		font-weight: 500;
		color: #2c3e50;
	}
	
	.student-id-badge {
		background: #e3f2fd;
		color: #1976d2;
		padding: 4px 10px;
		border-radius: 4px;
		font-size: 12px;
		font-weight: 500;
	}
	
	.action-buttons {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		display: flex;
		gap: 10px;
		padding: 15px 20px;
		background: white;
		border-top: 2px solid <?= $theme_primary ?>;
		box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
		justify-content: space-between;
		align-items: center;
		z-index: 9999;
		margin: 0 !important;
		width: 100%;
	}
	
	/* Wrapper to add padding for sticky footer */
	.marks-table-wrapper {
		padding-bottom: 100px; /* Space for fixed buttons */
	}
	
	.action-buttons .left-section {
		display: flex;
		align-items: center;
		gap: 15px;
	}
	
	.action-buttons .right-section {
		display: flex;
		gap: 10px;
	}
	
	.admin-override-switch {
		display: flex;
		align-items: center;
		gap: 10px;
		padding: 8px 15px;
		background: #f3f4f6;
		border-radius: 8px;
	}
	
	.admin-override-switch label {
		margin: 0;
		font-weight: 500;
		color: #4b5563;
		cursor: pointer;
		user-select: none;
	}
	
	.admin-override-switch input[type="checkbox"] {
		width: 20px;
		height: 20px;
		cursor: pointer;
	}
	
	.btn-save-marks {
		background: linear-gradient(135deg, #10b981 0%, #059669 100%);
		border: none;
		padding: 12px 35px;
		border-radius: 6px;
		color: white;
		font-weight: 500;
		transition: all 0.3s;
		font-size: 15px;
	}
	
	.btn-save-marks:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
		color: white;
	}
	
	.btn-save-marks:active {
		transform: translateY(0);
	}
	
	.btn-print-marks {
		background: linear-gradient(135deg, <?= $theme_primary ?> 0%, <?= $theme_secondary ?> 100%);
		border: none;
		padding: 12px 35px;
		border-radius: 6px;
		color: white;
		font-weight: 500;
		transition: all 0.3s;
		font-size: 15px;
	}
	
	.btn-print-marks:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
		color: white;
	}
	
	.btn-print-marks:active {
		transform: translateY(0);
	}

	@media only screen and (min-width: 1530px) {
		.lgt {
			margin-bottom: 10px;
		}
	}
	
	@media print {
		.action-buttons, .marks-info-panel .info-row, .form-control, .select2, .admin-override-switch {
			display: none !important;
		}
		.portfolio-marks-container {
			margin-bottom: 0 !important;
		}
		.marks-info-panel {
			background: #333 !important;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
	}
</style>
<hr />
<?php echo form_open(site_url('admin/portfolio_assessment_selector'), array('id' => 'subject_loder_form'));?>
<div class="row">

	<div class="col-md-3">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('exam');?></label>
			<select name="exam_id" class="form-control select2" required>
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
						<select name="class_id" class="form-control select2" onchange="get_class_subject(this.value)">
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
				<select name="subject_id" onchange="load_subjects()" id="subject_id" class="form-control select2">
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

<div class="portfolio-marks-container">
	<div class="marks-info-panel">
		<h4><i class="fa fa-chart-line"></i> <?php echo get_phrase('portfolio_assessment_marks_entry');?></h4>
		<div class="info-row">
			<div class="info-item">
				<i class="fa fa-file-alt"></i>
				<span>
					<?php
					// Get class name to determine term/sem
					$class_row = $this->db->get_where('class', array('class_id' => $class_id))->row();
					$class_name = $class_row->name;
					
					if($class_name == 'JHSS') {
						echo get_phrase('exam') . ': ' . $this->db->get_where('exam', array('exam_id' => $exam_id, 'year' => $running_year, 'sem' => $running_sem))->row()->name;
					} else {
						echo get_phrase('exam') . ': ' . $this->db->get_where('exam', array('exam_id' => $exam_id, 'year' => $running_year, 'term' => $running_term))->row()->name;
					}
					?>
				</span>
			</div>
			<div class="info-item">
				<i class="fa fa-calendar"></i>
				<span>
					<?php
					if($class_name == 'JHSS') {
						echo get_phrase('semester:') . ' ' . $running_sem . ' | ' . get_phrase('year:') . ' ' . $running_year;
					} else {
						echo get_phrase('term:') . ' ' . $running_term . ' | ' . get_phrase('year:') . ' ' . $running_year;
					}
					?>
				</span>
			</div>
			<div class="info-item">
				<i class="fa fa-school"></i>
				<span>
					<?php echo $class_row->name . ' ' . $class_row->name_numeric;?> 
					<?php echo get_phrase('section') . ' ' . $this->db->get_where('section', array('section_id' => $section_id))->row()->name;?>
				</span>
			</div>
			<div class="info-item">
				<i class="fa fa-book"></i>
				<span>
					<?php
					if($class_name == 'JHSS') {
						echo get_phrase('subject') . ': ' . $this->db->get_where('subject', array('subject_id' => $subject_id, 'year' => $running_year, 'sem' => $running_sem))->row()->name;
					} else {
						echo get_phrase('subject') . ': ' . $this->db->get_where('subject', array('subject_id' => $subject_id, 'year' => $running_year, 'term' => $running_term))->row()->name;
					}
					?>
				</span>
			</div>
			<div class="info-item">
				<i class="fa fa-calendar-week"></i>
				<span><?php echo get_phrase('week') . ': ' . $week;?></span>
			</div>
		</div>
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

		<div id="edit_holder" class="marks-table-wrapper">
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
							for($d = 0; $d < sizeof($dates_array); $d++) { 
								// Check if portfolio assessment exists for this date and subject
								$portfolio_row = $this->db->get_where('portfolio_assessment', array(
									'timestamp' => $dates_array[$d], 
									'subject_id' => $subject_id
								))->row();
								$portfolio_code = $portfolio_row ? $portfolio_row->code : '';
								$is_past = $dates_array[$d] < strtotime(date('d-m-Y'));
								$is_today = $dates_array[$d] == strtotime(date('d-m-Y'));
								$is_future = $dates_array[$d] > strtotime(date('d-m-Y'));
								?>
								<th style="text-align: center;">
									<div>
										<input type="text" 
											id="code_<?php echo $dates_array[$d]; ?>" 
											class="form-control <?= $is_today ? 'today_selected' : '' ?> <?= $is_past ? 'past-date-field' : '' ?>" 
											name="code_<?php echo $dates_array[$d]; ?>" 
											value="<?= $portfolio_code ?>"
											data-past-date="<?= $is_past ? '1' : '0' ?>"
											placeholder="Code"
											<?= $is_today ? 'required="required"' : '' ?> 
											<?= $is_future ? 'disabled="disabled"' : '' ?> 
											<?= $is_past ? 'readonly="readonly"' : '' ?>>
									</div>
								</th>
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

                        <td width="80">
							<span class="student-id-badge">
								<?php echo $this->db->get_where('student',array('student_id'=>$row['student_id']))->row()->student_code;?>
							</span>
						</td>
          
						<td width="300" colspan="2">
							<span class="student-name">
								<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
							</span>
						</td>


						
							<?php
							for($d = 0; $d < sizeof($dates_array); $d++) { 

								//retrieve the student's score for each strand -topic
								$strand_score_query = $this->db->get_where('portfolio_assessment', array(
									'student_id' => $row['student_id'], 
									'timestamp' => $dates_array[$d], 
									'week' => $week, 
									'class_id' => $class_id, 
									'subject_id' => $subject_id
								))->row();

								// Check if record exists, otherwise use defaults
								if($strand_score_query) {
									$strand_score = $strand_score_query->strand_score;
									$assessment_id = $strand_score_query->assessment_id;
									$date_timestamp = $strand_score_query->timestamp;
									array_push($assessment_ids, $assessment_id); //push assessment ids here
								} else {
									$strand_score = '';
									$assessment_id = 0;
									$date_timestamp = $dates_array[$d];
								}

								
								?>


								<td style="text-align: center" width="120">
									<input type="text" 
										id="strand_<?php echo $row['student_id']; ?>_<?php echo $date_timestamp; ?>" 
										class="form-control <?=$date_timestamp == strtotime(date('d-m-Y')) ? 'today_selected' : '' ?> <?=$date_timestamp < strtotime(date('d-m-Y')) ? 'past-date-field' : '' ?>" 
										name="strand_<?php echo $assessment_id;?>"
										value="<?php echo $strand_score;?>" 
										max="100"
										data-past-date="<?=$date_timestamp < strtotime(date('d-m-Y')) ? '1' : '0' ?>"
										<?=$date_timestamp > strtotime(date('d-m-Y')) ? 'disabled="disabled"' : '' ?> 
										<?=$date_timestamp < strtotime(date('d-m-Y')) ? 'readonly="readonly"' : '' ?>
										placeholder="-">
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
		</div><!-- End marks-table-wrapper -->

			<!--
			<input type="hidden" name="dates_array[]" value="<?=implode(',', $dates_array)?>" >
			<input type="hidden" name="assessment_ids" value="<?=implode(',', $assessment_ids)?>" >
			<input type="hidden" name="students_ids[]" value="<?=implode(',', $students_ids)?>" > -->

			<div class="action-buttons">
				<div class="left-section">
					<?php 
					// Get the system-wide portfolio past edit setting
					$allow_past_edit = $this->db->get_where('settings', array('type' => 'allow_portfolio_past_edit'))->row()->description ?? '0';
					
					if($allow_past_edit == '1'): ?>
					<div class="admin-override-switch" style="background: #dcfce7; border: 1px solid #86efac;">
						<i class="fa fa-check-circle" style="color: #16a34a;"></i>
						<small style="color: #16a34a; font-weight: 500;">
							Past date editing enabled by admin
							<?php if($account_type == 'admin'): ?>
							<a href="<?= site_url('admin/system_settings') ?>" style="color: #16a34a; text-decoration: underline; margin-left: 5px;">
								(Change in Settings)
							</a>
							<?php endif; ?>
						</small>
					</div>
					<?php endif; ?>
					
					<div>
						<i class="fa fa-info-circle text-primary"></i>
						<small class="text-muted">Current date column is highlighted in green. Save after entering marks.</small>
					</div>
				</div>
				<div class="right-section">
					<button type="button" class="btn btn-print-marks" onclick="window.print()">
						<i class="fa fa-print"></i> <?php echo get_phrase('print');?>
					</button>
					<button type="submit" class="btn btn-save-marks" id="submit_button">
						<i class="fa fa-save"></i> <?php echo get_phrase('save_marks');?>
					</button>
				</div>
			</div>
		
		<?php echo form_close();

		?>
		</div><!--Edit holder ends-->
</div><!-- portfolio-marks-container ends -->

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
									// Check if portfolio assessment exists for this date and subject
									$portfolio_row = $this->db->get_where('portfolio_assessment', array(
										'timestamp' => $dates_array[$d], 
										'subject_id' => $sb['subject_id']
									))->row();
									$portfolio_code = $portfolio_row ? $portfolio_row->code : '-';
									$subject_name = $this->db->get_where('subject', array('subject_id' => $sb['subject_id']))->row()->name;
								?>
								<th style="text-align: center;">
									<div><?= $portfolio_code ?> <br>
									<small><?= substr($subject_name, 0, 6); ?></small>
								</th>
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
								$strand_score_query = $this->db->get_where('portfolio_assessment', array(
									'student_id' => $row['student_id'], 
									'timestamp' => $dates_array[$d], 
									'week' => $week, 
									'class_id' => $class_id, 
									'subject_id' => $sub['subject_id']
								))->row();

								// Check if record exists, otherwise use defaults
								if($strand_score_query) {
									$strand_score = $strand_score_query->strand_score;
									$assessment_id = $strand_score_query->assessment_id;
									$date_timestamp = $strand_score_query->timestamp;
									array_push($assessment_ids, $assessment_id); //push assessment ids here
								} else {
									$strand_score = 0;
									$assessment_id = 0;
									$date_timestamp = $dates_array[$d];
								}

								
								?>


								<td style="text-align: center" width="120">
									<?php
										$grade = $this->crud_model->get_grade($strand_score);
										// Check if grade exists and has grade_point key
										if($grade && isset($grade['grade_point'])) {
											echo $grade['grade_point'];
										} else {
											echo '-'; // Show dash if no grade found
										}
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
		$('#mark_sheet').dataTable({
			"paging": false,        // Disable pagination - show all records
			"searching": true,      // Keep search functionality
			"ordering": false,      // Disable sorting to keep original order
			"info": true,          // Show info like "Showing 1 to X of Y entries"
			"lengthChange": false,  // Hide "Show X entries" dropdown
			"dom": 'frtip'         // Filter, table, info, pagination (without length)
		});
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

// Auto-enable past fields if setting is enabled
$(document).ready(function() {
	<?php if($allow_past_edit == '1'): ?>
	const pastDateFields = document.querySelectorAll('.past-date-field');
	pastDateFields.forEach(function(field) {
		field.removeAttribute('readonly');
		field.style.backgroundColor = '#fff3cd';
		field.style.borderColor = '#ffc107';
	});
	<?php endif; ?>
});

</script>