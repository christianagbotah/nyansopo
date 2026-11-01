<hr />
<div class="row">
	<div class="col-md-12">
		<?php echo form_open(site_url('admin/tabulation_sheet'));?>
			<div class="col-md-2">
				<div class="form-group">
					<label class="control-label"><?php echo get_phrase('class');?></label>
					<select name="class_id" class="form-control select2" id="class_id">
                        <option value=""><?php echo get_phrase('select_a_class');?></option>
                        <?php 
                        $classes = $this->db->get('class')->result_array();
                        foreach($classes as $row):
                        ?>
                            <option value="<?php echo $row['class_id'];?>"
                            	<?php if ($class_id == $row['class_id']) echo 'selected';?>>
                            		<?php echo $row['name'].' '.$row['name_numeric'];?>
                            </option>
                        <?php
                        endforeach;
                        ?>
                    </select>
				</div>
			</div>

			<div class="col-md-2">
            	<div class="form-group">
                    <label  class="col-sm-3 control-label"><?php echo get_phrase('term');?></label>
                      <select name="term" class="form-control select2" onchange="get_exam_type()" id="term">
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
            </div>

            <div class="col-md-2">
				<div class="form-group">
	              <label  class="control-label"><?php echo get_phrase('sessional_year');?></label>
	                  <select name="year" class="form-control select2" onchange="get_exam_type()" id="year">
	                  <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
	                  <option value="" disabled="true"><?php echo get_phrase('sessional_year');?></option>
	                  <?php
                          echo populate_academic_year();
                        ?>
	                  </select>
	            </div>
            </div>

			<div class="col-md-3">
				<div class="form-group" id="exam_id_holder">
				<label class="control-label"><?php echo get_phrase('exam');?></label>
					<select name="exam_id" class="form-control " id="exam_id">
                        <option value=""><?php echo get_phrase('select_an_exam');?></option>
                        
                    </select>
				</div>
			</div>
			<input type="hidden" name="operation" value="selection">
			<div class="col-md-3" style="margin-top: 20px;">
				<button type="submit" id = 'submit' class="btn btn-info"><?php echo get_phrase('view_tabulation_sheet');?></button>
			</div>
		<?php echo form_close();?>
	</div>
</div>
<?php 
	
?>

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

			<?php
                //add section A or B if the class has more than one section
                $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                $sec_name = '';
                if($class_has_more_sections > 1) {
                    $sec_name = $section_name;
                }
            ?>

			<h4 style="color: #696969;">
				<?php echo $class_name.' '.$class_name_numeric.$sec_name;?> | <?php echo get_phrase('term:').' '.$term; ?>
			</h4>
			<h4><?php echo get_phrase('sessional_year:').' '.explode('-', $year)[1];; ?></h4>
		</div>
	</div>
	<div class="col-md-4"></div>
</div>


<hr />

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
				<td style="text-align: center;"><?php echo get_phrase('raw_score');?></td>
				<td style="text-align: center;"><?php echo get_phrase('aggregate');?></td>
				<td style="text-align: center;"><?php echo get_phrase('position_in_class');?></td>
				</tr>
			</thead>
			<tbody>
			<?php
				
				$students = $this->db->get_where('enroll' , array('class_id' => $class_id , 'year' => $year, 'mute' => '0', 'term' => $term));
				if($students->num_rows() < 1) {$this->session->set_flashdata('error_message' , get_phrase('no_record_was_found.'));
			    }

				$array_students = $students->result_array();
				foreach($array_students as $row):

					//AGGREGATION OF MARKS
				     //aggregation of core subjects 4
				    $total_aggregate = 0;
				    $sum_core = 0;
				       $this->db->where('exam_id', $exam_id);
				       $this->db->where('class_id', $class_id);
				       $this->db->where('section_id', $section_id);
				       $this->db->where('student_id', $row['student_id']);
				       $this->db->where('year', $year);
				       $this->db->where('term', $term);
				       $this->db->where('status', '1');
				       $this->db->limit(4);
				       //$this->db->order_by("mark_obtained", "desc");
				       
				       $marks = $this->db->get('mark')->result_array();

				        foreach ($marks as $row) {
				            $sum_core += $row['mark_obtained'];
				        }
				     

				     //aggregation of best 2
				    $sum_best2 = 0;
				       $this->db->where('exam_id', $exam_id);
				       $this->db->where('class_id', $class_id);
				       $this->db->where('section_id', $section_id);
				       $this->db->where('student_id', $row['student_id']);
				       $this->db->where('year', $year);
				       $this->db->where('term', $term);
				       $this->db->where('status', '0');
				       $this->db->limit(2);
				       $this->db->order_by("mark_obtained", "desc");
				       
				       $marks = $this->db->get('mark')->result_array();

				        foreach ($marks as $row) {
				            $sum_best2 += $row['mark_obtained'];

				            //finding the aggregate for the best 2 subjects
				            $core_grade = $this->crud_model->get_raw_score_grade($row['mark_obtained']);
				            $total_aggregate += $core_grade['grade_point'];  
				        }

				        	
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

									//finding the aggregate for the 4 core subjects
		                           if($row2['status'] == 1) {
	                                    $core_grade = $this->crud_model->get_raw_score_grade($obtained_marks);
										$total_aggregate += $core_grade['grade_point'];
	                                }
								}
								
							}

							

						?>
					</td>
				<?php endforeach;?>
				<td style="text-align: center;"><b><?php echo $sum_core + $sum_best2;?></b></td>
				<td style="text-align: center;"><b><?php echo $total_aggregate;?></b></td>
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
			<a href="<?php echo site_url('admin/tabulation_sheet_print_view/'.$class_id.'/'.$exam_id.'/'.$year.'/'.$term.'/'.$section_id);?>" 
				class="btn btn-primary" target="_blank">
				<?php echo get_phrase('print_tabulation_sheet');?>
			</a>
		</center>
	</div>
</div>
<?php
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
		get_exam_type()
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
	
	
</script>