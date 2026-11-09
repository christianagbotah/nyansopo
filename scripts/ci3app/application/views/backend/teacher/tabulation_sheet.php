<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="bg-white rounded-lg shadow-sm p-6">
	<div class="mb-6">
		<h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo get_phrase('tabulation_sheet');?></h2>
		<p class="text-gray-600"><?php echo get_phrase('view_class_exam_tabulation');?></p>
	</div>

	<?php echo form_open(site_url('admin/tabulation_sheet'), ['class' => 'space-y-4']);?>
	<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
			<div>
				<label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('class');?></label>
					<select name="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" id="class_id" onchange="showTermSem($(this).val())">
                        <option value=""><?php echo get_phrase('select_a_class');?></option>
                        <?php 

                        $classes = $this->db->get('class')->result_array();
                        foreach($classes as $row):
                        ?>

                        <?php


                            //add section A or B if the class has more than one section
                            $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                            $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                            $sec_name = '';
                            if($class_has_more_sections > 1) {
                                $sec_name = $section_name;
                            }
                        ?>
                            <option value="<?php echo $row['class_id'];?>"
                            	<?php if ($class_id == $row['class_id']) echo 'selected';?>>
                            		<?php echo $row['name'].' '.$row['name_numeric'].$sec_name;?>
                            </option>
                        <?php
                        endforeach;
                        ?>
                    </select>
			</div>

			<div id="term_holder">
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('term');?></label>
                      <select name="term" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="get_exam_type();" id="term">
                      <?php $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;?>
                      <option value="" disabled="true"><?php echo get_phrase('sessional_term');?></option>
                      <?php for($i = 1; $i <= 3; $i++):?>
                          <option value="<?php echo $i;?>"
                            <?php if($term == $i) echo 'selected';?>>
                              <?php echo $i;?>
                          </option>
                      <?php endfor;?>
                      </select>
            </div>

            <div id="sem_holder" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('semester');?></label>
                      <select name="sem" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="get_exam_type_sem()" id="sem">
                      <?php $running_sem = $this->db->get_where('settings' , array('type'=>'running_sem'))->row()->description;?>
                      <option value="" disabled="true"><?php echo get_phrase('selected_semester');?></option>
                      <?php for($i = 1; $i <= 2; $i++):?>
                          <option value="<?php echo $i;?>"
                            <?php if($sem == $i) echo 'selected';?>>
                              <?php echo $i;?>
                          </option>
                      <?php endfor;?>
                      </select>
            </div>

            <div>
	              <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('sessional_year');?></label>
	                  <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="get_exam_type()" id="year">
	                  <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
	                  <option value="" disabled="true"><?php echo get_phrase('sessional_year');?></option>
	                  <?php
                          echo populate_academic_year();
                        ?>
	                  </select>
            </div>

			<div>
				<label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('exam');?></label>
					<select name="exam_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" id="exam_id">
                        <option value=""><?php echo get_phrase('select_an_exam');?></option>
                        
                    </select>
			</div>
	</div>

	<input type="hidden" name="operation" value="selection">
	<div class="mt-6">
		<button type="submit" id="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition flex items-center gap-2">
			<i class="entypo-docs"></i> <?php echo get_phrase('view_tabulation_sheet');?>
		</button>
	</div>
	<?php echo form_close();?>
</div>

<?php if ($class_id != '' && $exam_id != '') {?>
<br>
<div class="row">
	<div class="col-md-4"></div>
	<div class="col-md-4" style="text-align: center;">
		<div class="tile-stats tile-gray">
		<div class="icon"><i class="entypo-docs" style="color: ;"></i></div>
			<h3 style="color: #696969;">
				<?php
					$exam_name  = $this->db->get_where('exam' , array('exam_id' => $exam_id))->row()->name; 
					$class_name = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
					$class_name_numeric = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric; 
					echo get_phrase('exam_tabulation_sheet');
				?>
			</h3>
			<h4 style="color: #696969;">
				<?php echo $class_name.' '.$class_name_numeric;?> | <?php echo $class_name == 'JHSS' ? get_phrase('Semester:').' '.$sem : get_phrase('term:').' '.$term; ?>
			</h4>
			<h4><?php echo get_phrase('academic_year:').' '.explode('-', $year)[1];; ?></h4>
		</div>
	</div>
	<div class="col-md-4"></div>
</div>


<hr />


<?php

if($class_name == 'JHSS') {
		?>
			<div class="row">
	<div class="col-md-12 col-lg-12 col-sm-12">
		<table class="table table-bordered">
			<thead>
				<tr>
				<td style="text-align: center;">
					<?php echo get_phrase('students');?> <i class="entypo-down-thin"></i> | <?php echo get_phrase('subjects');?> <i class="entypo-right-thin"></i>
				</td>
				<?php 
					$subjects = $this->db->get_where('subject' , array('class_id' => $class_id , 'year' => $year, 'sem' => $sem))->result_array();
					foreach($subjects as $row):
				?>
					<td style="text-align: center;">
						<?php if(strlen($row['name']) <= 4) {
                                    echo strtoupper($row['name']);
                                }else{ 
                                    echo $row['name'];
                                };?></td>
				<?php endforeach;?>
				<td style="text-align: center;"><?php echo get_phrase('total');?></td>
				<td style="text-align: center;"><?php echo get_phrase('position_in_class');?></td>
				</tr>
			</thead>
			<tbody>
			<?php
				
				$students = $this->db->get_where('enroll' , array('class_id' => $class_id , 'year' => $year, 'mute' => '0', 'sem' => $sem));
				if($students->num_rows() < 1) {$this->session->set_flashdata('error_message' , get_phrase('no_record_was_found.'));
			    }

				$array_students = $students->result_array();
				foreach($array_students as $row):
			?>
				<tr>
					<td>
						<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
					</td>
				<?php
					$total_marks = 0;
					$total_grade_point = 0;  
					foreach($subjects as $row2):
				?>
					<td style="text-align: center;">
						<?php 
							$obtained_mark_query = 	$this->db->get_where('mark' , array(
													'class_id' => $class_id , 
														'exam_id' => $exam_id , 
															'subject_id' => $row2['subject_id'] , 
																'student_id' => $row['student_id'],
																	'year' => $year,
																		'term' => $sem
												));
							if ( $obtained_mark_query->num_rows() > 0) {
								$obtained_marks = round($obtained_mark_query->row()->mark_obtained, 2);
								echo $obtained_marks;
								if ($obtained_marks >= 0 && $obtained_marks != '') {
									$grade = $this->crud_model->get_grade($obtained_marks);
									$total_grade_point += $grade['grade_point'];
								}
								$total_marks += $obtained_marks;
							}
							

						?>
					</td>
				<?php endforeach;?>
				<td style="text-align: center;"><b><?php echo $total_marks;?></b></td>
				<td style="text-align: center;">
					<?php
					  $section_id = $this->db->get_where('section' , array('class_id' => $class_id))->row()->section_id;
          			  $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $row['student_id'], $year, $sem);

            		?>
				</td>
				</tr>

			<?php endforeach;?>

			</tbody>
		</table>
		<center>
			<a href="<?php echo site_url('admin/tabulation_sheet_print_view/'.$class_id.'/'.$exam_id.'/'.$year.'/'.$sem);?>" 
				class="btn btn-primary" target="_blank">
				<?php echo get_phrase('print_tabulation_sheet');?>
			</a>
		</center>
	</div>
</div>
		<?php //jhs ends

} else {
	?>
	<div class="row">
	<div class="col-md-12 col-lg-12 col-sm-12">
		<table class="table table-bordered">
			<thead>
				<tr>
				<td style="text-align: center;">
					<?php echo get_phrase('students');?> <i class="entypo-down-thin"></i> | <?php echo get_phrase('subjects');?> <i class="entypo-right-thin"></i>
				</td>
				<?php 
					$subjects = $this->db->get_where('subject' , array('class_id' => $class_id , 'year' => $year, 'term' => $term))->result_array();
					foreach($subjects as $row):
				?>
					<td style="text-align: center;">
						<?php if(strlen($row['name']) <= 4) {
                                    echo strtoupper($row['name']);
                                }else{ 
                                    echo $row['name'];
                                };?></td>
				<?php endforeach;?>
				<td style="text-align: center;"><?php echo get_phrase('total');?></td>
				<td style="text-align: center;"><?php echo get_phrase('position_in_class');?></td>
				</tr>
			</thead>
			<tbody>
			<?php
				
				$students = $this->db->get_where('enroll' , array('class_id' => $class_id , 'year' => $year, 'term' => $term));
				if($students->num_rows() < 1) {$this->session->set_flashdata('error_message' , get_phrase('no_record_was_found.'));
			    }

				$array_students = $students->result_array();
				foreach($array_students as $row):
			?>
				<tr>
					<td>
						<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
					</td>
				<?php
					$total_marks = 0;
					$total_grade_point = 0;  
					foreach($subjects as $row2):
				?>
					<td style="text-align: center;">
						<?php 
							$obtained_mark_query = 	$this->db->get_where('mark' , array(
													'class_id' => $class_id , 
														'exam_id' => $exam_id , 
															'subject_id' => $row2['subject_id'] , 
																'student_id' => $row['student_id'],
																	'year' => $year,
																		'term' => $term
												));
							if ( $obtained_mark_query->num_rows() > 0) {
								$obtained_marks = round($obtained_mark_query->row()->mark_obtained, 2);
								echo $obtained_marks;
								if ($obtained_marks >= 0 && $obtained_marks != '') {
									$grade = $this->crud_model->get_grade($obtained_marks);
									$total_grade_point += $grade['grade_point'];
								}
								$total_marks += $obtained_marks;
							}
							

						?>
					</td>
				<?php endforeach;?>
				<td style="text-align: center;"><b><?php echo $total_marks;?></b></td>
				<td style="text-align: center;">
					<?php
					  $section_id = $this->db->get_where('section' , array('class_id' => $class_id))->row()->section_id;
          			  $this->crud_model->get_aggregate_marks($exam_id, $class_id, $section_id, $row['student_id'], $year, $term);

            		?>
				</td>
				</tr>

			<?php endforeach;?>

			</tbody>
		</table>
		<center>
			<a href="<?php echo site_url('admin/tabulation_sheet_print_view/'.$class_id.'/'.$exam_id.'/'.$year.'/'.$term);?>" 
				class="btn btn-primary" target="_blank">
				<?php echo get_phrase('print_tabulation_sheet');?>
			</a>
		</center>
	</div>
</div>
	<?php
}
}else{
		$this->session->set_flashdata('error_message' , get_phrase('class_name_or_exam_name_was_not_selected!.'));
	}
?>
<script type="text/javascript">

/**	var class_id = '';
	var exam_id  = '';
	jQuery(document).ready(function($) {
		$('#submit').attr('disabled', 'disabled');
	});
	function check_validation(){
		if(class_id !== '' && exam_id !== ''){
			$('#submit').removeAttr('disabled');
		}
		else{
			$('#submit').attr('disabled', 'disabled');	
		}
	}
	$('#class_id').change(function() {
		class_id = $('#class_id').val();
		check_validation();
	});
	$('#exam_id').change(function() {
		exam_id = $('#exam_id').val();
		check_validation();
	});

	**/

	//load exam types	
	$(function() {
		let class_id = '<?=$class_id ?>';
		get_exam_type();
		get_exam_type_sem();
		showTermSem(class_id);
	});

		function get_exam_type() {
			var $term = $('#term').val();
			var $year = $('#year').val();

			$.ajax({
				url: '<?php echo site_url('admin/get_exam_type/');?>'+ $term +'/'+ $year,
				success: function(response) {
					$('#exam_id').html(response);
				}
			});
		}

		function showTermSem(class_id) {
        
        $.ajax({
            url: '<?php echo site_url('admin/get_class_name/') ?>' + class_id,
            type: 'POST',
            dataType: 'text',
            //data: {param1: 'value1'},
        })
        .done(function(class_name) {
            if(class_name == 'JHSS') {
                $('#term_holder').addClass('hidden');
                $('#sem_holder').removeClass('hidden');

                get_exam_type_sem();
            } else {
                 $('#sem_holder').addClass('hidden');
                 $('#term_holder').removeClass('hidden');
                 get_exam_type()
            }
        })
        .fail(function() {
            console.log("error");
        });
    }


    function get_exam_type_sem() {
        var $sem = $('#sem').val();
        var $year = $('#year').val();


        $.ajax({
            url: '<?php echo site_url('admin/get_exam_type_sem/');?>'+ $sem +'/'+ $year,
            success: function(response) {
                $('#exam_id').html(response);

            }
        });
    }
	
	
</script>