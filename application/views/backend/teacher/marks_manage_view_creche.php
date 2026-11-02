<?php
	//creche
	$this->db->select('class_id');
	$this->db->distinct();
	$find_teacher_creche = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')));
	$class_ids_creche = $find_teacher_creche->result_array();

	//general
    $this->db->select('class_id');
    $this->db->distinct();
    $find_teacher = $this->db->get_where('subject', array('teacher_id' => $this->session->userdata('teacher_id'), 'year' => $running_year, 'term' => $running_term));
    $class_ids = $find_teacher->result_array();
    
    //general for class teacher
    $this->db->select('class_id');
    $this->db->distinct();
    $find_teacher_c = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')));
    $class_ids_c = $find_teacher_c->result_array();


?>

<style type="text/css">
	table.table-bordered.dataTable th:last-child, table.table-bordered.dataTable th:last-child {
		border-right-width: 1px !important;
	}

	th {
		padding-bottom: 20<?php
	//creche
	$this->db->select('class_id');
	$this->db->distinct();
	$find_teacher_creche = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')));
	$class_ids_creche = $find_teacher_creche->result_array();

	//general
    $this->db->select('class_id');
    $this->db->distinct();
    $find_teacher = $this->db->get_where('subject', array('teacher_id' => $this->session->userdata('teacher_id'), 'year' => $running_year, 'term' => $running_term));
    $class_ids = $find_teacher->result_array();
    
    //general for class teacher
    $this->db->select('class_id');
    $this->db->distinct();
    $find_teacher_c = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')));
    $class_ids_c = $find_teacher_c->result_array();


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
<?php echo form_open(site_url('admin/marks_selector_creche'), array('id' => 'subject_loader_form'));?>
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
				// Track added class IDs to prevent duplicates
				$added_class_ids = array();
				
				// First, add classes where teacher is the class teacher (creche)
				foreach($class_ids_creche as $subj):
					// Skip if already added
					if(in_array($subj['class_id'], $added_class_ids)) continue;
					
					$classes = $this->db->get_where('class', array('class_id' => $subj['class_id']))->result_array();
					foreach($classes as $row):
					 
					 //add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                    $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                    $sec_name = '';
                    if($class_has_more_sections > 1) {
                        $sec_name = ' '.$section_name;
                    }
				?>
				<option value="<?php echo $row['class_id'];?>" <?php if($class_id == $row['class_id']) echo 'selected';?>><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
				<?php 
					// Mark this class as added
					$added_class_ids[] = $subj['class_id'];
					endforeach; 
				endforeach; //FOR CRECHE ?> 
                
                <?php
				// Second, add classes where teacher teaches subjects
				foreach($class_ids as $subj):
					// Skip if already added
					if(in_array($subj['class_id'], $added_class_ids)) continue;
					
					$classes = $this->db->get_where('class', array('class_id' => $subj['class_id']))->result_array();
					foreach($classes as $row):
					
					//add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                    $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                    $sec_name = '';
                    if($class_has_more_sections > 1) {
                        $sec_name = ' '.$section_name;
                    }
				?>
				<option value="<?php echo $row['class_id'];?>" <?php if($class_id == $row['class_id']) echo 'selected';?>><?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?></option>
				<?php 
					// Mark this class as added
					$added_class_ids[] = $subj['class_id'];
					endforeach; 
				endforeach; ?>
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
    	</div>
    	<span style="color:red; display: none;" id="error_notec"></span>
    </div>
    <div class="row">
        <div class="col-md-8">
			<div class="form-group">
			<label class="control-label col-md-2" style="margin-bottom: 5px;"><?php echo get_phrase('subject');?></label>
				<select name="subject_id" id="subject_id" onchange="load_subject()" class="form-control select2 col-md-6">
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
						$marksheet_url = site_url('admin/student_marksheet_creche/'.$first_student->student_id);
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
		
		echo form_open(site_url('admin/marks_update_creche/'.$exam_id.'/'.$class_id.'/'.$section_id.'/'.$subject_id.'/'.$category_id), array('id' => 'mark_sheet_form'));?>
			<table class="table table-bordered" id="mark_sheet">
				<thead>

					<tr>
						<td colspan="3"></td>
						<td style="text-align: center">
							<select name="assess_" id="assess_" onchange="bulkAssessment()" class="form-control select2">
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
							<select name="assess_<?php echo $row['mark_id'];?>" id="assess_<?php echo $row['student_id']; ?>" class="form-control select2">
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

	//mark all assessment in bulk
	function bulkAssessment() {
		let stIds = <?=json_encode($students_ids); ?>;
		let gradeId = $('#assess_').val();
		 for(var st in stIds) {
		 	$('#assess_' + stIds[st]).val(gradeId).change();
		 }
	}

	$('#mark_sheet_form').submit(function(event) {
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
	
	function load_subject() {
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
            url: '<?php echo site_url('teacher/marks_get_subject_creche/');?>' + class_id ,
            success: function(response)
            {
                jQuery('#subject_holder').html(response);
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
		    $('#error_note').css('display', 'none');//clear error if there was any
		    $('#error_notec').css('display', 'none');//clear error if there was any
		   let cat_id = $('#category_id').val();
		   const class_id = '<?php echo $class_id; ?>';
		   
		   //send via ajax
		   $.ajax({
		      url: '<?php echo site_url('admin/update_subjects_creche/'); ?>' + cat_id + '/' + class_id,
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
		   }if($('#category_id').val() == '' || $('#category_id').val() == null) {
		       $('#error_notec').css('display', 'block');
		       $('#error_notec').text('Select a category!');
		       return false;
		   } else {
		       $('#error_note').css('display', 'none');
		       $('#error_notec').css('display', 'none');
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