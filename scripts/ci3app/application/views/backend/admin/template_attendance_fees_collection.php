<?php echo form_open(site_url('admin/portfolio_assessment_selector'), array('id' => 'portfolio_form'));?>
<div class="grid grid-cols-3 gap-2 items-center">

	<?php
		if($account_type == 'teacher') {
			?>
				<div class="max-w-md">
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
				<div class="max-w-md">
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
	

	
		<div class="max-w-md">
			<div class="form-group">
			<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('week');?></label>
				<input type="week" class="form-control" name="week" id="week" required="required">
			</div>
		</div>

		
		<div class="">
				<button type="submit" class="btn btn-info" id="submit"><?php echo get_phrase('manage_assessment');?></button>
		</div>
	</div>

</div>
<?php echo form_close();?>

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
