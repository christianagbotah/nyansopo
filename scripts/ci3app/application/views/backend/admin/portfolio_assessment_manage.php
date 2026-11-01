<style>
.portfolio-card {
	background: #fff;
	border-radius: 8px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.1);
	padding: 25px;
	margin-bottom: 20px;
}
.portfolio-header {
	border-bottom: 3px solid #5867dd;
	padding-bottom: 15px;
	margin-bottom: 25px;
}
.portfolio-header h4 {
	color: #2c3e50;
	font-weight: 600;
	margin: 0;
	display: flex;
	align-items: center;
	gap: 10px;
}
.portfolio-header h4 i {
	color: #5867dd;
}
.form-group label {
	font-weight: 500;
	color: #495057;
	margin-bottom: 8px;
}
.form-control {
	border-radius: 6px;
	border: 1px solid #d1d5db;
	padding: 10px 15px;
	font-size: 14px;
	transition: all 0.3s;
}
.form-control:focus {
	border-color: #5867dd;
	box-shadow: 0 0 0 0.2rem rgba(88, 103, 221, 0.15);
}
/* Week picker styling - make it clear the whole field is clickable */
.week-picker {
	cursor: pointer !important;
	background-color: #fff;
}
.week-picker:hover {
	border-color: #5867dd;
	background-color: #f8f9ff;
}
.week-picker::-webkit-calendar-picker-indicator {
	cursor: pointer;
	padding: 5px;
	margin-left: -25px;
}
.select2-container--default .select2-selection--single {
	border-radius: 6px;
	border: 1px solid #d1d5db;
	height: 42px;
	padding: 6px 12px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
	line-height: 28px;
	color: #495057;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
	height: 40px;
}
.select2-container--default.select2-container--focus .select2-selection--single {
	border-color: #5867dd;
	box-shadow: 0 0 0 0.2rem rgba(88, 103, 221, 0.15);
}
.btn-portfolio {
	background: linear-gradient(135deg, #5867dd 0%, #4956c7 100%);
	border: none;
	border-radius: 6px;
	padding: 12px 30px;
	font-weight: 500;
	color: #fff;
	transition: all 0.3s;
}
.btn-portfolio:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(88, 103, 221, 0.4);
	color: #fff;
}
.btn-portfolio:disabled {
	background: #d1d5db;
	cursor: not-allowed;
	transform: none;
}
.info-badge {
	background: #e3f2fd;
	color: #1976d2;
	padding: 8px 15px;
	border-radius: 6px;
	font-size: 13px;
	display: inline-block;
	margin-bottom: 20px;
}
</style>

<?php
// Display error messages with modern styling
if(isset($_GET['error']) && $_GET['error'] == 1) { 
	if(isset($_GET['term'])) { ?>
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			<i class="fa fa-exclamation-triangle"></i>
			<strong>Attendance Required:</strong> <?= get_phrase('seems_students_attendance_for_term_'.$_GET['term'].'_has_not_been_marked_yet._please_mark_students_attendance_first_to_enroll_students_for_this_term_before_you_proceed.'); ?>
			<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
	<?php } elseif(isset($_GET['sem'])) { ?>
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			<i class="fa fa-exclamation-triangle"></i>
			<strong>Attendance Required:</strong> <?= get_phrase('seems_students_attendance_for_semester_'.$_GET['sem'].'_has_not_been_marked_yet._please_mark_students_attendance_first_to_enroll_students_for_this_semester_before_you_proceed.'); ?>
			<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
	<?php }
}

if(isset($_GET['subjr']) && $_GET['subjr'] == 1) { ?>
	<div class="alert alert-warning alert-dismissible fade show" role="alert">
		<i class="fa fa-info-circle"></i>
		<strong>No Subjects Found:</strong> <?= get_phrase('no_subject_was_found_for_this_class._please_add_subjects_for_this_class_and_try_again!'); ?>
		<button type="button" class="close" data-dismiss="alert">&times;</button>
	</div>
<?php } ?>

<div class="portfolio-card">
	<div class="portfolio-header">
		<h4><i class="fa fa-chart-line"></i> <?php echo get_phrase('portfolio_assessment_selection'); ?></h4>
	</div>
	
	<div class="info-badge">
		<i class="fa fa-lightbulb"></i> Select exam, class, week, and subject to manage portfolio assessment marks
	</div>

	<?php echo form_open(site_url('admin/portfolio_assessment_selector'), array('id' => 'portfolio_form'));?>
	<div class="row">

		<div class="col-md-3">
			<div class="form-group">
				<label class="control-label">
					<i class="fa fa-file-alt"></i> <?php echo get_phrase('exam');?>
				</label>
				<select name="exam_id" class="form-control select2" id="exam_select" required>
					<option value=""><?php echo get_phrase('select_exam_type');?></option>
					<?php
						// Show all portfolio assessment exams for current year
						$this->db->where('year', $running_year);
						$this->db->where('category_id', '1');
						$exams = $this->db->get('exam')->result_array();
						foreach($exams as $row):
					?>
					<option value="<?php echo $row['exam_id'];?>"><?php echo $row['name'];?></option>
					<?php endforeach;?>
				</select>
			</div>
		</div>

		<?php if($account_type == 'teacher') { ?>
			<div class="col-md-3">
				<div class="form-group">
					<label class="control-label">
						<i class="fa fa-school"></i> <?php echo get_phrase('class');?>
					</label>
					<select name="class_id" class="form-control select2" id="class_select" onchange="get_class_subject(this.value)" required>
						<option value=""><?php echo get_phrase('select_class');?></option>
						<?php
						foreach($class_ids_creche as $subj):
							$classes = $this->db->get_where('class', array('class_id' => $subj['class_id']))->result_array();
							foreach($classes as $row):
								$section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
								$class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
								$sec_name = '';
								if($class_has_more_sections > 1) {
									$sec_name = $section_name;
								}
						?>
						<option value="<?php echo $row['class_id'];?>"><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
						<?php endforeach; endforeach; ?> 
						
						<?php
						foreach($class_ids_c as $cs):
							$classes_c = $this->db->get_where('class', array('class_id' => $cs['class_id']))->result_array();
							foreach($classes_c as $rowc):
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
		<?php } else { ?>
			<div class="col-md-3">
				<div class="form-group">
					<label class="control-label">
						<i class="fa fa-school"></i> <?php echo get_phrase('class');?>
					</label>
					<select name="class_id" class="form-control select2" id="class_select" onchange="get_class_subject(this.value)" required>
						<option value=""><?php echo get_phrase('select_class');?></option>
						<?php getFullClassList(); ?>
					</select>
				</div>
			</div>
		<?php } ?>
		
		<div class="col-md-2">
			<div class="form-group">
				<label class="control-label">
					<i class="fa fa-calendar-week"></i> <?php echo get_phrase('week');?>
				</label>
				<input type="week" class="form-control week-picker" name="week" id="week" required style="cursor: pointer;">
			</div>
		</div>

		<div id="subject_holder">
			<div class="col-md-3">
				<div class="form-group">
					<label class="control-label">
						<i class="fa fa-book"></i> <?php echo get_phrase('subject');?>
					</label>
					<select name="" id="subject_select" class="form-control select2" disabled>
						<option value=""><?php echo get_phrase('select_class_first');?></option>		
					</select>
				</div>
			</div>
			<div class="col-md-1" style="margin-top: 32px;">
				<button type="submit" class="btn btn-portfolio btn-block" id="submit" disabled>
					<i class="fa fa-arrow-right"></i> <?php echo get_phrase('proceed');?>
				</button>
			</div>
		</div>

	</div>
	<?php echo form_close();?>
</div>


<script type="text/javascript">
jQuery(document).ready(function($) {
	// Initialize Select2
	$('.select2').select2({
		placeholder: 'Please select',
		allowClear: true,
		width: '100%'
	});
	
	$("#submit").prop('disabled', true);
	
	// Make week input fully clickable - open picker on any click
	$('.week-picker').on('click', function() {
		// Focus the input to trigger the native week picker
		this.focus();
		// Try to show the picker programmatically (works in some browsers)
		if (this.showPicker) {
			try {
				this.showPicker();
			} catch(e) {
				// Fallback for browsers that don't support showPicker
				console.log('showPicker not supported');
			}
		}
	});
	
	// Also trigger on focus for better UX
	$('.week-picker').on('focus', function() {
		if (this.showPicker) {
			try {
				this.showPicker();
			} catch(e) {
				// Silent fallback
			}
		}
	});
	
	// Add loading state on form submission
	$('#portfolio_form').on('submit', function() {
		$('#submit').html('<i class="fa fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
	});
});

function get_class_subject(class_id) {
	if (class_id !== '') {
		$('#subject_holder').html('<div class="col-md-4"><div class="text-center"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p>Loading subjects...</p></div></div>');
		
		$.ajax({
			url: '<?php echo site_url('admin/marks_get_subject/');?>' + class_id + '/portfolio',
			success: function(response) {
				jQuery('#subject_holder').html(response);
				
				// Reinitialize Select2 for dynamically loaded select
				$('#subject_holder .select2').select2({
					placeholder: 'Select subject',
					allowClear: true,
					width: '100%'
				});
				
				$('#submit').prop('disabled', false);
			},
			error: function() {
				$('#subject_holder').html('<div class="col-md-4"><div class="alert alert-danger">Failed to load subjects</div></div>');
			}
		});
	} else {
		$('#submit').prop('disabled', true);
	}
}
</script>