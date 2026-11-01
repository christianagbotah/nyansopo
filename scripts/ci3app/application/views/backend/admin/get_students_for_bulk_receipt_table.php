
<?php
/*
	$date_week = date('W', $date);
  $date_year =  date('Y', $date);

  $result_array = array();
  $result_array = getStartAndEndDate($date_week, $date_year);

  $start_date = $result_array['week_starts'];

  //echo 'week '.$start_date;*/


	// Get students who have any fee payment on this date
	$student_ids = [];
	
	// Check feeding_fee_payment
	$feeding = $this->db->select('student_id')->distinct()->where(['class_id' => $class_id, 'day_timestamp' => $date, 'can_delete !=' => 'trash'])->get('feeding_fee_payment')->result_array();
	foreach($feeding as $f) $student_ids[$f['student_id']] = true;
	
	// Check classes_fee_payment
	$classes = $this->db->select('student_id')->distinct()->where(['class_id' => $class_id, 'day_timestamp' => $date, 'can_delete !=' => 'trash'])->get('classes_fee_payment')->result_array();
	foreach($classes as $c) $student_ids[$c['student_id']] = true;
	
	// Check transport_fare_payment
	$transport = $this->db->select('student_id')->distinct()->where(['class_id' => $class_id, 'day_timestamp' => $date, 'can_delete !=' => 'trash'])->get('transport_fare_payment')->result_array();
	foreach($transport as $t) $student_ids[$t['student_id']] = true;
	
	$class_ids = [];
	foreach(array_keys($student_ids) as $sid) {
		$class_ids[] = ['student_id' => $sid];
	}
	$class_ids_data = (object)['num_rows' => count($class_ids)];

	$class_ids = $class_ids_data->result_array();

	$data_ids = array();

	 //add section A or B if the class has more than one section
    $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
    $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class' , array('class_id' => $class_id))->row()->name, 'name_numeric' => $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric))->num_rows();
    $sec_name = '';
    if($class_has_more_sections > 1) {
        $sec_name = $section_name;
    }

    $class = '';
    if($row['name'] == 'CRECHE') {
        $class = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name;
    } else {
        $class = $this->db->get_where('class' , array('class_id' => $class_id))->row()->name. ' '. $this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric.$sec_name;
    }

?>
<style type="text/css">
	#div_sticky {
		position: sticky;
		top: 0px;
	}
</style>
<hr />
<div class="row" style="text-align: center; z-index: 99; background-color: #f3f3f3;" id="div_sticky">
	<div class="col-sm-2"></div>
	<div class="col-sm-8">
		<div class="tile-stats tile-gray">
			<div class="icon"><i class="entypo-users"></i></div>
			
			<h3 style="color: #696969;"><?php echo get_phrase('students_of');?> <?php echo $class; ?></h3>
			<h1 id="count_ch"></h1>
		</div>
	</div>
	<div class="col-sm-2"><h4 id="display_error" style="padding-top: 30px; text-align: left; font-weight: bold;"></h4></div>
</div><br>
<div class="row">
	<center>
    <a class="btn btn-success" id="select_toggle" onclick="select_all()">Select All</a>
</center><hr>

	<div class="col-md-12">
		<table class="table table-bordered" id="id_table">
			<thead align="center">
				<tr>
					<td align="center">#</td >
					<td align="center"><?php echo "ID No.";?></td >
					<td align="center"><?php echo get_phrase('student_name');?></td >
					<td align="center"><?php echo get_phrase('selection');?></td >
				</tr>
			</thead>
			<tbody>
			<?php 

			if($class_ids_data->num_rows() < 1) { ?>
				<tr>
					<td colspan="4"><h4 style="color: red; text-align: center">No Data Found For This Date: <u><?=date('l d M, Y', $date); ?></u></h4></td>
				</tr>

			<?php
			}
			$i = 1;
			$array_pos = 0;
					foreach($class_ids as $cl_id):
			?>
			
				<tr>
					<td><?php echo $i; ?></td>
					<td><?php echo $this->db->get_where('student', array('student_id' => $cl_id['student_id']))->row()->student_code; ?></td>
					<td><label class="form-control-label" onclick="checkbox_over_alert()" for="bulk_ids_<?php echo $cl_id['student_id']; ?>"><?php echo $this->db->get_where('student', array('student_id' => $cl_id['student_id']))->row()->name; ?></label></td>
					<td style="text-align: center;">
						<input type="checkbox" name="bulk_ids[]" value="<?php echo $cl_id['student_id'];?>" id="bulk_ids_<?php echo $cl_id['student_id']; ?>" class="form-control">
					</td>
				</tr>
			
			
		<?php 
		//push ids into the array
		$data_ids[$array_pos] = $cl_id['student_id'];
		$array_pos++;

		$i++;
	endforeach;?>
			</tbody>
		</table>
	</div>
</div>
<br>
<div class="row">
	<center>
		<p id="error_above"></p>
		<button type="submit" id="submit" class="btn btn-success">
			<i class="entypo-print"></i> <?php echo get_phrase('print_receipt(s)_for_the_selected_student(s)');?>
		</button>
	</center>
</div>

<script type="text/javascript">

	$(document).ready(function() {

		//disable the select all and print button if no row is returned
		let class_row = <?php echo $class_ids_data->num_rows();  ?>;
		if(class_row < 1) {
			$('#select_toggle').attr('disabled', 'disabled');
			$('#submit').attr('disabled', 'disabled');
		}

        if (typeof $.fn.select2 !== "undefined")
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
				$this.select2({ placeholder: "Select an option", allowClear: true, width: "100%" });
			});
		}
    });

    //$('#id_table').dataTable();

    
</script>

<script type="text/javascript">
	//throw error message if user selects more than 6 checkboxes

	$(document).ready(function() {

		var $checkboxes = $('#checkboxes_form td input[type="checkbox"]');

		$('#submit').click(function() {
			var $count_checked_buttons = $checkboxes.filter(':checked').length;
			if($count_checked_buttons <= 0) {

				$('#count_ch').css({'color': 'red', 'font-size': '22px'});
				$('#count_ch').text('<?php echo get_phrase("no_student_was_selected!") ?>');
				$('#error_above').css({'color': 'red', 'display': 'block'});
				$('#error_above').text('<?php echo get_phrase("no_student_was_selected!") ?>');
				showAjaxModal_alert('<?php echo get_phrase("no_student_was_selected!") ?>', 'error');
				return false;
			} else {
				$('#error_above').css({'color': 'green', 'display': 'block'});
				$('#error_above').text('<?php echo get_phrase("Loading... Please Wait.") ?>');
			}
		});

		$checkboxes.change(function() {
			var $count_checked_checkboxes = $checkboxes.filter(':checked').length;
			let nStudents = 'student';


			$('#count_ch').css({
				'font-size': '20px', 'color': 'green'
			});

			if($count_checked_checkboxes > 1) {
				nStudents = 'students';
			}
			$('#count_ch').text('You have selected '+$count_checked_checkboxes + ' ' + nStudents);
			$('#error_above').css('display', 'none');

			/**if($count_checked_checkboxes == 6) {

				//disable the remaining checkboxes
					$('input:not(:checked)').attr('disabled', 'disabled');

				$('#count_ch').css({'color': 'red', 'font-size': '16px'});
				$('#count_ch').text('<?php echo get_phrase("you_have_reached_the_maximum_selection_of_six_(6)_students._please_print_these_before_you_proceed.") ?>');
				toastr.error('<?php echo get_phrase("you_have_reached_the_maximum_selection_of_six_(6)_students._please_print_these_before_you_proceed.") ?>')
				return false;

			}else if($count_checked_checkboxes > 6){

				//disable the remaining checkboxes
				$('input:not(:checked)').attr('disabled', 'disabled');
					
					$('#count_ch').css({'color': 'red', 'font-size': '16px'});
				$('#count_ch').text('<?php echo get_phrase("you_have_reached_the_maximum_selection_of_six_(6)_students._please_print_these_before_you_proceed.") ?>');
				toastr.error('<?php echo get_phrase("you_have_reached_the_maximum_selection_of_six_(6)_students._please_print_these_before_you_proceed.") ?>')
				return false;
			}else{
				$('#count_ch').text('You have selected '+$count_checked_checkboxes + ' students out of 6');
				$('input:not(:checked)').removeAttr('disabled');
			} **/
		});

		
		
	});

	function select_all() {
        var st_id_array = <?php echo json_encode($data_ids); ?>;
        var button_text = $('#select_toggle').text();

        if(button_text == 'Select All') {
        	$('#select_toggle').text('Deselect All');
        	$('#select_toggle').removeClass('btn-success');
        	$('#select_toggle').addClass('btn-danger');

        	for(var i = 0; i < st_id_array.length; i++) {
            	$('#bulk_ids_' + st_id_array[i]).click();
        	}	

        } else if(button_text == 'Deselect All') {
        	$('#select_toggle').text('Select All');
        	$('#select_toggle').removeClass('btn-danger');
        	$('#select_toggle').addClass('btn-success');
        	
        	for(var i = 0; i < st_id_array.length; i++) {
            	$('#bulk_ids_' + st_id_array[i]).click();
        	}
        }
        
    }

</script>