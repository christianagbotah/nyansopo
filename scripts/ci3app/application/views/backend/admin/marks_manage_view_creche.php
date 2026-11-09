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

	.filters-card {
		background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
		border-radius: 12px;
		padding: 30px;
		box-shadow: 0 4px 15px rgba(0,0,0,0.15);
		margin-bottom: 30px;
		border: 1px solid #e1e8ed;
	}

	.filters-card h4 {
		color: #2c3e50;
		font-weight: 700;
		margin-bottom: 25px;
		padding-bottom: 15px;
		border-bottom: 3px solid #3498db;
		display: inline-block;
	}

	.filters-card .form-group {
		margin-bottom: 20px;
	}

	.filters-card label {
		font-weight: 600;
		color: #2c3e50;
		margin-bottom: 8px;
		display: block;
		font-size: 14px;
	}

	.filters-card .form-control {
		height: 42px;
		border-radius: 6px;
		border: 2px solid #d1d8e0;
		padding: 8px 12px;
		transition: all 0.3s ease;
		background-color: #fff;
	}

	.filters-card .form-control:focus {
		border-color: #3498db;
		box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
	}

	.filters-card .select2-container {
		width: 100% !important;
	}

	.filters-card .select2-container .select2-selection--single {
		height: 42px !important;
		border-radius: 6px !important;
		border: 2px solid #d1d8e0 !important;
		padding: 4px 12px !important;
		background-color: #fff !important;
	}

	.filters-card .select2-container--default .select2-selection--single .select2-selection__rendered {
		line-height: 32px !important;
		color: #2c3e50 !important;
	}

	.filters-card .select2-container--default .select2-selection--single .select2-selection__arrow {
		height: 40px !important;
	}

	.btn-manage-marks {
		padding: 6px 20px;
		font-size: 16px;
		font-weight: 600;
		border-radius: 8px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border: none;
		box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
		transition: all 0.3s ease;
		margin-top: 30px;
	}

	.btn-manage-marks:hover {
		transform: translateY(-2px);
		box-shadow: 0 6px 15px rgba(102, 126, 234, 0.6);
	}

	.error-text {
		color: #e74c3c;
		font-size: 12px;
		margin-top: 5px;
		display: none;
		font-weight: 500;
	}

	/* Exam Info Card */
	.exam-info-card {
		background: white;
		border-radius: 12px;
		padding: 30px;
		box-shadow: 0 2px 12px rgba(0,0,0,0.08);
		margin-bottom: 30px;
		border-left: 4px solid #667eea;
	}

	.exam-info-card .icon-wrapper {
		width: 60px;
		height: 60px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border-radius: 12px;
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 0 auto 20px;
	}

	.exam-info-card .icon-wrapper i {
		font-size: 28px;
		color: white;
	}

	.exam-info-card h4 {
		color: #2c3e50;
		font-weight: 600;
		margin-bottom: 10px;
		font-size: 18px;
	}

	.exam-info-card .info-text {
		color: #7f8c8d;
		font-size: 14px;
		margin-bottom: 5px;
	}

	.exam-info-card .class-section-text {
		color: #34495e;
		font-size: 16px;
		font-weight: 500;
		margin-top: 10px;
	}

	/* Modern Table Styles */
	.table-card {
		background: white;
		border-radius: 15px;
		padding: 30px;
		box-shadow: 0 4px 20px rgba(0,0,0,0.08);
		border: 1px solid #e8eef5;
	}

	.table-card h5 {
		color: #2c3e50;
		font-weight: 700;
		margin-bottom: 20px;
		font-size: 18px;
	}

	#mark_sheet_wrapper {
		border-radius: 10px;
		overflow: hidden;
	}

	#mark_sheet {
		border-radius: 10px;
		overflow: hidden;
		border: none;
		width: 100%;
		margin-bottom: 0 !important;
	}

	#mark_sheet thead {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
	}

	#mark_sheet thead th {
		color: white !important;
		font-weight: 600 !important;
		border: none !important;
		padding: 18px 15px !important;
		vertical-align: middle !important;
		text-align: center !important;
		font-size: 14px;
		letter-spacing: 0.5px;
		text-transform: uppercase;
	}

	#mark_sheet thead td {
		background: rgba(255,255,255,0.1);
		border: none !important;
		padding: 15px !important;
	}

	#mark_sheet tbody tr {
		transition: all 0.3s ease;
		border-bottom: 1px solid #f0f4f8;
	}

	#mark_sheet tbody tr:hover {
		background: linear-gradient(to right, #f8f9ff 0%, #f0f4ff 100%);
		transform: translateX(3px);
		box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
	}

	#mark_sheet tbody tr:last-child {
		border-bottom: none;
	}

	#mark_sheet tbody td {
		padding: 16px 15px;
		vertical-align: middle;
		border: none;
		color: #2c3e50;
		font-size: 14px;
	}

	#mark_sheet tbody td:first-child {
		font-weight: 600;
		color: #667eea;
	}

	/* DataTables Modern Styling */
	.dataTables_wrapper .dataTables_length,
	.dataTables_wrapper .dataTables_filter {
		padding: 15px 0;
		margin-bottom: 15px;
	}

	.dataTables_wrapper .dataTables_info,
	.dataTables_wrapper .dataTables_paginate {
		padding: 15px 0;
		margin-top: 15px;
	}

	.dataTables_wrapper .dataTables_length label,
	.dataTables_wrapper .dataTables_filter label {
		color: #5a6c7d;
		font-weight: 500;
		font-size: 14px;
	}

	.dataTables_wrapper .dataTables_length select {
		border: 2px solid #e1e8ed;
		border-radius: 6px;
		padding: 5px 10px;
		margin: 0 8px;
		color: #2c3e50;
		font-weight: 500;
	}

	.dataTables_wrapper .dataTables_filter input {
		border: 2px solid #e1e8ed;
		border-radius: 6px;
		padding: 8px 15px;
		margin-left: 8px;
		color: #2c3e50;
		transition: all 0.3s ease;
	}

	.dataTables_wrapper .dataTables_filter input:focus {
		border-color: #667eea;
		outline: none;
		box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
	}

	.dataTables_wrapper .dataTables_info {
		color: #5a6c7d;
		font-weight: 500;
		font-size: 14px;
	}

	.dataTables_wrapper .dataTables_paginate .paginate_button {
		border-radius: 6px;
		padding: 8px 14px;
		margin: 0 3px;
		border: 1px solid #e1e8ed;
		background: white;
		color: #667eea !important;
		font-weight: 500;
		transition: all 0.3s ease;
	}

	.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
		background: #667eea;
		color: white !important;
		border-color: #667eea;
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
	}

	.dataTables_wrapper .dataTables_paginate .paginate_button.current {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: white !important;
		border-color: #667eea;
		box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
	}

	.dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.save-button-wrapper {
		background: white;
		padding: 25px;
		border-radius: 12px;
		box-shadow: 0 2px 12px rgba(0,0,0,0.08);
		margin-top: 20px;
		text-align: center;
	}

	.btn-save-marks {
		padding: 14px 50px;
		font-size: 16px;
		font-weight: 600;
		border-radius: 8px;
		background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
		border: none;
		color: white;
		box-shadow: 0 4px 10px rgba(46, 204, 113, 0.4);
		transition: all 0.3s ease;
	}

	.btn-save-marks:hover {
		transform: translateY(-2px);
		box-shadow: 0 6px 15px rgba(46, 204, 113, 0.6);
		color: white;
	}

	@media (max-width: 768px) {
		.filters-card {
			padding: 20px 15px;
		}
		.filters-card h4 {
			font-size: 18px;
		}
		.exam-info-card,
		.table-card,
		.save-button-wrapper {
			padding: 20px 15px;
		}
	}
</style>

<div class="filters-card">
	<h4><i class="entypo-filter"></i> <?php echo get_phrase('filter_marks');?></h4>
	<?php echo form_open(site_url('admin/marks_selector_creche'), array('id' => 'subject_loader_form'));?>
	<div class="row">
		<div class="col-md-3 col-sm-6">
			<div class="form-group">
				<label class="control-label"><?php echo get_phrase('exam');?></label>
				<select name="exam_id" id="exam_id" class="form-control select2-filter" required>
					<?php
						$this->db->where('year', $running_year);
						$this->db->where('term', $running_term);
						$this->db->where('category_id', 2); // Terminal exams only
						$exams = $this->db->get('exam')->result_array();
						foreach($exams as $row):
					?>
					<option value="<?php echo $row['exam_id'];?>"
						<?php if($exam_id == $row['exam_id']) echo 'selected';?>><?php echo $row['name'];?></option>
					<?php endforeach;?>
				</select>
			</div>
		</div>

		<div class="col-md-2 col-sm-6">
			<div class="form-group">
				<label class="control-label"><?php echo get_phrase('class');?></label>
				<select name="class_id" id="class_id" class="form-control select2-filter" onchange="get_section_id(this.value)">
					<option value=""><?php echo get_phrase('select_class');?></option>
					<?php getFullClassList('', $class_id); ?>
				</select>
			</div>
		</div>
		
		<div class="col-md-3 col-sm-6">
			<div class="form-group">
				<label class="control-label"><?php echo get_phrase('subject_category');?></label>
				<select name="category_id" id="category_id" class="form-control select2-filter">
					<option value="">SELECT CATEGORY</option>
					<option value="0">SUBJECTS WITH NO CATEGORY</option>
					<?php 
					    $category_id2 = '';
						$categories = $this->db->get('subject_category_creche')->result_array();
						foreach($categories as $row):
						$category_id2 = $row['category_id'];
					?>
					<option value="<?php echo $row['category_id'];?>" <?php if($category_id == $category_id2) echo 'selected';?>><?php echo $row['name'];?></option>
					<?php endforeach;?>
				</select>
				<span class="error-text" id="error_notec"></span>
			</div>
		</div>

		<div class="col-md-2 col-sm-6">
			<div class="form-group">
				<label class="control-label"><?php echo get_phrase('subject');?></label>
				<select name="subject_id" onchange="load_subjects()" id="subject_id" class="form-control select2-filter">
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
				<span class="error-text" id="error_note"></span>
			</div>
		</div>

		<div class="col-md-2 col-sm-6">
			
			<button type="submit" class="btn btn-primary btn-manage-marks md:mt-3" id="btn_marks">
				<i class="entypo-check"></i> <?php echo get_phrase('manage_marks');?>
			</button>
		</div>
		
		<!-- View Marksheet Button -->
		<div class="col-md-2 col-sm-6">
			<?php
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
					$marksheet_url = site_url('admin/student_marksheet_creche/'.$first_student->student_id);
			?>
			<a href="<?php echo $marksheet_url; ?>" class="btn btn-info btn-manage-marks md:mt-3">
				<i class="entypo-doc-text"></i> <?php echo get_phrase('view_marksheet');?>
			</a>
			<?php endif; ?>
		</div>
		
	</div>

	<!-- Hidden field to store section_id -->
	<input type="hidden" name="section_id" id="section_id" value="<?php echo $section_id;?>">
	
	<?php echo form_close();?>
</div>

<!-- Exam Info Card -->
<div class="row">
	<div class="col-md-12">
		<div class="exam-info-card">
			<div class="icon-wrapper">
				<i class="entypo-chart-bar"></i>
			</div>
			<h4 style="text-align: center;">
				<?php echo get_phrase('marks_for');?> <?php echo $this->db->get_where('exam' , array('exam_id' => $exam_id, 'year' => $running_year, 'term' => $running_term))->row()->name;?>
			</h4>
			<p class="info-text" style="text-align: center;">
				<?php echo get_phrase('term:').' '.$running_term.' | '. get_phrase('sessional_year:').' '.explode('-', $running_year)[1];?>
			</p>
			<p class="info-text" style="text-align: center; font-weight: 600; color: #2c3e50; font-size: 15px;">
				<?php echo get_phrase('subject');?>: <?php echo $this->db->get_where('subject_creche' , array('subject_id' => $subject_id, 'category_id' => $category_id, 'year' => $running_year, 'term' => $running_term))->row()->name;?>
			</p>
			<p class="class-section-text" style="text-align: center;">
				<?php echo $this->db->get_where('class' , array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;?> : 
				<?php echo get_phrase('section');?> <?php echo $this->db->get_where('section' , array('section_id' => $section_id))->row()->name;?>
			</p>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="table-card">

		<?php 

		$students_ids = [];
		$this->db->select('student_id');
		$this->db->distinct();
		$students_ids_array = $this->db->get_where('mark' , array(
						'class_id' => $class_id, 
							'section_id' => $section_id ,
								'year' => $running_year,
									 'term' => $running_term,
										'subject_id' => $subject_id,
											'exam_id' => $exam_id
					))->result_array();

		foreach($students_ids_array as $stid) {
			array_push($students_ids, $stid['student_id']);
		}

		echo form_open(site_url('admin/marks_update_creche/'.$exam_id.'/'.$class_id.'/'.$section_id.'/'.$subject_id.'/'.$category_id), array('id' => 'mark_sheet_form_creche'));?>
			<table class="table table-bordered" id="mark_sheet">
				<thead>
					<tr>
						<td colspan="3"></td>
						<td style="text-align: center">
							<select name="assess_" id="assess_" onchange="bulkAssessment()" class="form-control select2" style="width: 100%;">
								<option value=""><?php echo get_phrase('select_assessment');?></option>
								<?php
									$assessmentsAll = $this->db->get('grade_creche')->result_array();
									foreach($assessmentsAll as $ass):
								?>
								<option value="<?php echo $ass['grade_id'];?>"><?php echo $ass['full_name'];?></option>
								<?php endforeach;?>
							</select>
						</td>
					</tr>


					<tr>
						<th>S/N</th>
						<th><?php echo get_phrase('student_iD');?></th>
						<th><?php echo get_phrase('student_name');?></th>
						<th>Assessment</th>
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

					$all_students_id = array();
					$id_counter = 0;
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
							<select name="assess_<?php echo $row['mark_id'];?>" id="assess_<?php echo $row['student_id']; ?>" class="form-control select2" style="width: 100%;">
								<option value=""><?php echo get_phrase('select_assessment');?></option>
								<?php
									$assessments = $this->db->get('grade_creche')->result_array();
									foreach($assessments as $row2):
								?>
								<option value="<?php echo $row2['grade_id'];?>" <?php if($row['test1'] == $row2['grade_id']) echo 'selected'; ?>><?php echo $row2['full_name'];?></option>
								<?php endforeach;?>
							</select>
						</td>

					</tr>
					<?php

						$all_students_id[$id_counter] = $row['student_id']; 
						$id_counter++;
						$students_counter++;
					?>
				<?php endforeach;?>

				<?php 
					//converting it into json before sending it to javascript
					$array_js = json_encode($all_students_id);
				?>
				</tbody>
			</table>

			<div class="save-button-wrapper">
				<button type="submit" class="btn btn-save-marks" id="submit_button">
					<i class="entypo-check"></i> <?php echo get_phrase('save_changes');?>
				</button>
			</div>
		<?php echo form_close();?>
		
		</div>
	</div>
</div>





<script type="text/javascript">

	$(document).ready(function() {

		// Initialize Select2 on filter dropdowns with enhanced settings
		initializeSelect2Filters();

		// Initialize DataTable
		$('#mark_sheet').DataTable({
			pageLength: 100,
		});

		set_val();
		
	});

	// Function to initialize Select2 on filter dropdowns
	function initializeSelect2Filters() {
		$('.select2-filter').each(function() {
			$(this).select2({
				placeholder: 'Select an option',
				allowClear: true,
				width: '100%',
				theme: 'default'
			});
		});

		// Also initialize regular select2 elements (assessment dropdowns in table)
		$('.select2').each(function() {
			if (!$(this).hasClass('select2-filter')) {
				$(this).select2({
					placeholder: 'Select an option',
					allowClear: true,
					width: '100%'
				});
			}
		});
	}

	// Get section_id from selected class
	function get_section_id(class_id) {
		if(class_id !== '') {
			$.ajax({
				url: '<?php echo site_url('admin/get_section_id_by_class/');?>' + class_id,
				success: function(section_id) {
					$('#section_id').val(section_id);
				}
			});
		}
	}

	//mark all assessment in bulk
	function bulkAssessment() {
		let stIds = <?=json_encode($students_ids); ?>;
		let gradeId = $('#assess_').val();
		 for(var st in stIds) {
		 	$('#assess_' + stIds[st]).val(gradeId).trigger('change');
		 }
	}

	$('#mark_sheet_form_creche').submit(function(event) {
			/* Act on the event */
			event.preventDefault();

			//Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);

      showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Please wait... Data is being processed<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

		let formUrl = $(this).attr('action');

		$.ajax({
	      url: formUrl,
          type: 'POST',
	      dataType: 'html',
	      data: new FormData(this),
	      cache: false,
	      contentType: false,
	      processData: false
      })
      .done(function() {

          showAjaxModal_alert('Assessment updated successfully.', 'Success');
          

          setTimeout(() => {
              $('.close').click();  
              $('#subject_loader_form').submit();           
              //window.location.reload();
          }, 3000);
      })
      .fail(function(err) {
          showAjaxModal_alert(err.responseText, 'Error');
      });
			
	});

	function load_subjects() {
		$('#subject_loader_form').submit();
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
            url: '<?php echo site_url('admin/marks_get_subject_creche/');?>' + class_id ,
            success: function(response)
            {
                jQuery('#subject_holder').html(response);
                // Re-initialize Select2 on assessment dropdowns after AJAX load
                initializeAssessmentSelect2();
            }
        });
	  }
	}


	$('#submit_button').click(function() {
		//var max_student_id = <?php echo $this->crud_model->get_max_student_id($class_id, $subject_id); ?>;
		let student_ids = <?php echo $array_js; ?>;
		let counter = 0;


		for (var i = 0; i < student_ids.length; i++) {

			if($('#assess_'+ student_ids[i]).val() == null || $('#assess_'+ student_ids[i]).val() == '') {

				counter++;
			} 

		}

		if(counter == student_ids.length) {
			//none has been filled--throw and error
			//Scroll to the top
			  $('html, body').animate({
			      scrollTop: ($('#top').offset().top )
			  }, 1000);


			showAjaxModal_alert('Assessment cannot be empty. Fill at least one before submitting your form.', 'Error');
			return false;
		}
		
	});
	
		//update subjects if category field changes
		$('#category_id').on("change", function() {
		    $('#error_note').hide();//clear error if there was any
		    $('#error_notec').hide();//clear error if there was any
		   let cat_id = $('#category_id').val();
		   const class_id = $('#class_id').val();
		   
		   //send via ajax
		   $.ajax({
		      url: '<?php echo site_url('admin/update_subjects_creche/'); ?>' + cat_id + '/' + class_id,
		      success: function(response) {
		          $('#subject_id').html(response);
		          // Re-initialize Select2 after updating subject dropdown
		          $('#subject_id').select2('destroy').select2({
						placeholder: 'Select a subject',
						allowClear: true,
						width: '100%'
					});
		      }
		   });
		});
		
		//check if subject is selected
		$('#btn_marks').on("click", function() {
		   if($('#subject_id').val() == '' || $('#subject_id').val() == null) {
		       $('#error_note').show();
		       $('#error_note').text('No subject was selected for the category. Change the category to update the subject field!');
		       return false;
		   }if($('#category_id').val() == '' || $('#category_id').val() == null) {
		       $('#error_notec').show();
		       $('#error_notec').text('Select a category!');
		       return false;
		   } else {
		       $('#error_note').hide();
		       $('#error_notec').hide();
		   }
		});


//ajax
$('#subject_loader_form').submit(function(event) {
	/* Act on the event */

	event.preventDefault();

	//Scroll to the top
  $('html, body').animate({
      scrollTop: ($('#top').offset().top )
  }, 1000);

	$('#main_page').empty();

	//SHOW LOADER
  $('#main_page').html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px; ">Fetching Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

  let formUrl = $(this).attr('action');

	$.ajax({
      url: formUrl,
      type: 'POST',
      dataType: 'html',
      data: new FormData(this),
      cache: false,
      contentType: false,
      processData: false
  })
  .done(function(data) {
  	if(data == 'no subject') {
  		showAjaxModal_alert('Please select subject for this class.', 'Error');

  		
  		setTimeout(() => {
  			navigation(formUrl);
  		}, 5000);
      
  	} else if(data == 'no enrollment') {
  		showAjaxModal_alert('Make sure students were enrolled before proceeding. Try marking attendance first and try again.', 'Error');

  		setTimeout(() => {
  			navigation(formUrl);
  		}, 5000);

      
  	} else {
  		$('#main_page').empty();
  		
  		navigation(data);
  	}
  });
});
	
</script>
