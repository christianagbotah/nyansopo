<style type="text/css">
	#modal_ajax {
		margin-top: 300px;
	}
	
	/* Override for xlarge modals - position at top */
	#modal_ajax .modal-dialog.modal-xl {
		margin-top: 10px !important;
		margin-bottom: 10px !important;
	}
</style>
<hr />
<div class="row" style="text-align: center;">
	<div class="col-sm-4"></div>
	<div class="col-sm-4">
		<div class="tile-stats tile-gray">
			<div class="icon"><i class="entypo-users"></i></div>
			
			<h3 style="color: #696969;"><?php echo get_phrase('students_of');?> <?php echo $this->db->get_where('class' , array('class_id' => $class_id_from))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id_from))->row()->name_numeric.' '.$this->crud_model->get_class_section($class_id_to);?></h3>
		</div>
	</div>
	<div class="col-sm-4"></div>
</div>
<div class="row" style="margin-bottom: 15px;">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-body" style="background-color: #f5f5f5; padding: 15px;">
				<div class="row">
					<div class="col-md-8">
						<label style="font-weight: bold; margin-right: 10px; display: inline-block; vertical-align: middle;">
							<i class="entypo-flash"></i> Bulk Action:
						</label>
						<select id="bulk_promotion_action" class="form-control select2" style="width: 500px; display: inline-block; vertical-align: middle;">
							<option value="">-- Select Action for All Students --</option>
							<option value="promote">Promote All to <?php echo $this->crud_model->get_class_name($class_id_to).' '.$this->crud_model->get_class_name_numeric($class_id_to).' '.$this->crud_model->get_class_section($class_id_to);?></option>
							<option value="repeat">Repeat All in <?php echo $this->crud_model->get_class_name($class_id_from).' '.$this->crud_model->get_class_name_numeric($class_id_from).' '.$this->crud_model->get_class_section($class_id_to);?></option>
						</select>
					</div>
					<div class="col-md-4 text-right">
						<button type="button" id="apply_bulk_action" class="btn btn-primary btn-lg">
							<i class="entypo-check"></i> Apply to All
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<table class="table table-bordered">
			<thead align="center">
				<tr>
					<td align="center" style="width: 5%;"><?php echo get_phrase('SN');?></td >
					<td align="center" style="width: 25%;"><?php echo get_phrase('name');?></td >
					<td align="center" style="width: 15%;"><?php echo "ID No.";?></td >
					<td align="center" style="width: 15%;"><?php echo get_phrase('info');?></td >
					<td align="center" style="width: 40%;"><?php echo get_phrase('options');?></td >
				</tr>
			</thead>
			<tbody>
			<?php
				if($class_name == 'JHSS') {
					$students = $this->db->get_where('enroll' , array(
						'class_id' => $class_id_from , 'mute' => '0','year' => $running_year, 'sem' => $running_sem
					));

				} else {
					$students = $this->db->get_where('enroll' , array(
						'class_id' => $class_id_from , 'mute' => '0','year' => $running_year, 'term' => $running_term
					));
				}

				if($students->num_rows() < 1) {
					?>
					<tr>
						<td colspan="5">
							<h4 style="color: red; text-align: center;">No Record Was Found.</h4>
						</td>
					</tr>
					<?php
				}

				$students_array = $students->result_array();
				$sn = 0;
				foreach($students_array as $row):
					$query = $this->db->get_where('enroll' , array(
						'student_id' => $row['student_id'],
							'year' => $promotion_year
						));

					$sn++;//for counting the sn
			?>
				<tr>
					<td>
						<?php echo $sn;?>
					</td>
					<td>
						<?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;?>
					</td>
                    <td align="center"><?php echo $this->db->get_where('student' , array(
                            'student_id' => $row['student_id']
                        ))->row()->student_code;?></td>
					<td align="center">
					<button type="button" class="btn btn-info"
						onclick="showAjaxModal('<?php echo site_url('modal/popup/student_promotion_performance/'.$row['student_id'].'/'.$class_id_from);?>', 'xlarge');">
						<i class="entypo-eye"></i> <?php echo get_phrase('view_academic_performance');?>
					</button>	
					</td>
					<td>
						<?php 
						// Check if student is already enrolled in next year
						if($query->num_rows() > 0) {
							$existing_enrollment = $query->row();
							$current_enrolled_class = $existing_enrollment->class_id;
							
							// Check if editing is allowed (only if promotion_year is next year after running_year)
							$running_year_parts = explode('-', $running_year);
							$promotion_year_parts = explode('-', $promotion_year);
							$can_edit = ($promotion_year_parts[0] == $running_year_parts[1]); // Next year = second part of running year
							
							if($can_edit) {
						?>
							<!-- Show dropdown with current enrollment (editable) -->
							<select class="form-control select2 student-promotion-select" 
									name="promotion_status_<?php echo $row['student_id'];?>" 
									style="width: 100%;" 
									data-student-id="<?php echo $row['student_id'];?>">
								<option value="<?php echo $class_id_to;?>" <?php echo ($current_enrolled_class == $class_id_to) ? 'selected' : ''; ?>>
									<?php echo get_phrase('promote_to_class') .": ". $this->crud_model->get_class_name($class_id_to).' '.$this->crud_model->get_class_name_numeric($class_id_to).' '.$this->crud_model->get_class_section($class_id_to);?>
								</option>
								<option value="<?php echo $class_id_from;?>" <?php echo ($current_enrolled_class == $class_id_from) ? 'selected' : ''; ?>>
									<?php echo get_phrase('repeat').": ". $this->crud_model->get_class_name($class_id_from).' '.$this->crud_model->get_class_name_numeric($class_id_from).' '.$this->crud_model->get_class_section($class_id_to);?>
								</option>
							</select>
							<small class="text-info" style="display: block; margin-top: 5px;">
								<i class="fa fa-info-circle"></i> Currently enrolled in: 
								<strong><?php echo $this->crud_model->get_class_name($current_enrolled_class).' '.$this->crud_model->get_class_name_numeric($current_enrolled_class); ?></strong>
							</small>
						<?php } else { ?>
							<!-- Editing disabled - session has changed -->
							<button class="btn btn-success" disabled="disabled">
								<i class="entypo-check"></i> <?php echo get_phrase('student_already_enrolled');?>
							</button>
							<small class="text-warning" style="display: block; margin-top: 5px;">
								<i class="fa fa-lock"></i> Editing disabled - session has changed
							</small>
						<?php } ?>
						<?php } else { ?>
							<!-- Show dropdown for new enrollment -->
							<select class="form-control select2 student-promotion-select" 
									name="promotion_status_<?php echo $row['student_id'];?>" 
									style="width: 100%;" 
									data-student-id="<?php echo $row['student_id'];?>">
								<option value="<?php echo $class_id_to;?>">
									<?php echo get_phrase('promote_to_class') .": ". $this->crud_model->get_class_name($class_id_to).' '.$this->crud_model->get_class_name_numeric($class_id_to).' '.$this->crud_model->get_class_section($class_id_to);?>
								</option>
								<option value="<?php echo $class_id_from;?>">
									<?php echo get_phrase('repeat').": ". $this->crud_model->get_class_name($class_id_from).' '.$this->crud_model->get_class_name_numeric($class_id_from).' '.$this->crud_model->get_class_section($class_id_to);?>
								</option>
							</select>
						<?php } ?>
					</td>
				</tr>
			<?php endforeach;?>
			</tbody>
		</table>
	</div>
</div>
<br>
<div class="row">
	<center>
		<button type="submit" id="submit" class="btn btn-success btn-lg">
			<i class="entypo-check"></i> <?php echo get_phrase('save_promotion_changes');?>
		</button>
	</center>
</div>

<script type="text/javascript">

	$(document).ready(function() {
        if($.isFunction($.fn.select2))
		{
			$("select.select2").each(function(i, el)
			{
				var $this = $(el),
					opts = {
						showFirstOption: attrDefault($this, 'first-option', true),
						'native': attrDefault($this, 'native', false),
						defaultText: attrDefault($this, 'text', ''),
					};
					
				$this.addClass('visible');
				$this.select2(opts);
			});
		}
    });

    $(document).ready(function() {
    	var nrows = <?php echo $students->num_rows(); ?>;

    	if(nrows < 1) {
    		$('#submit').attr('disabled', 'disabled');
    	}
    });

    // Bulk action handler
    $('#apply_bulk_action').click(function() {
    	var action = $('#bulk_promotion_action').val();
    	
    	if(action === '') {
    		showAjaxModal_alert('Please select an action first', 'Warning');
    		return;
    	}
    	
    	var targetClassId = '';
    	if(action === 'promote') {
    		targetClassId = '<?php echo $class_id_to; ?>';
    	} else if(action === 'repeat') {
    		targetClassId = '<?php echo $class_id_from; ?>';
    	}
    	
    	// Apply to all student promotion selects
    	$('.student-promotion-select').each(function() {
    		$(this).val(targetClassId).trigger('change');
    	});
    	
    	// Show success message
    	var actionText = action === 'promote' ? 'Promotion' : 'Repeat';
    	showAjaxModal_alert(actionText + ' option applied to all students successfully!', 'Success', false, false);
    });

    $('#submit').click(function(e) {
    	showAjaxModal_alert('Please Wait... <i class="fa fa-spinner fa-pulse"></i>', 'Loading');

    	$('html, body').animate({
    		scrollTop: ($('#top').offset().top)
    	}, 1000);
    })
</script>