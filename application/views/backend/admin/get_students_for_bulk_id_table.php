
<?php
	$class_ids = $this->db->get_where('enroll', array('class_id' => $class_id, 'mute' => '0', 'year' => $year))->result_array();

	$selected_year = explode('-', $year);
	$part1         = $selected_year[0];
	$part2         = $selected_year[1];

?>
<hr />
<div class="row" style="text-align: center; z-index: 99; background-color: #f3f3f3; position: sticky; position: -webkit-sticky; top: 0px;">
	<div class="col-sm-2"></div>
	<div class="col-sm-8">
		<div class="tile-stats tile-gray">
			<div class="icon"><i class="entypo-users"></i></div>
			
			<h3 style="color: #696969;"><?php echo get_phrase('students_of');?> <?php echo $this->db->get_where('class' , array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class' , array('class_id' => $class_id))->row()->name_numeric;?></h3>
			<h1 id="count_ch">Select Six(6) Students Only!</h1>
		</div>
	</div>
	<div class="col-sm-2"><h4 id="display_error" style="padding-top: 30px; text-align: left; font-weight: bold;"></h4></div>
</div>
<div class="row">
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
			$i = 1;
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
		$i++;
	endforeach;?>
			</tbody>
		</table>
	</div>
</div>
<br>
<div class="row">
	<center>
		<button type="submit" id="submit" class="btn btn-success">
			<i class="entypo-print"></i> <?php echo get_phrase('print_iDs_for_the_selected_students');?>
		</button>
	</center>
</div>

<script type="text/javascript">

	$(document).ready(function() {
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

    $('#id_table').dataTable();

    
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
				toastr.error('<?php echo get_phrase("no_student_was_selected!") ?>')
				return false;
			}
		});

		$checkboxes.change(function() {
			var $count_checked_checkboxes = $checkboxes.filter(':checked').length;


			$('#count_ch').css({
				'font-size': '20px', 'color': 'green'
			});
			$('#count_ch').text('You have selected '+$count_checked_checkboxes + ' students out of 6');

			if($count_checked_checkboxes == 6) {

				//disable the remaining checkboxes
					$('input:not(:checked)').attr('readonly', 'readonly');

				$('#count_ch').css({'color': 'red', 'font-size': '16px'});
				$('#count_ch').text('<?php echo get_phrase("you_have_reached_the_maximum_selection_of_six_(6)_students._please_print_these_before_you_proceed.") ?>');
				toastr.error('<?php echo get_phrase("you_have_reached_the_maximum_selection_of_six_(6)_students._please_print_these_before_you_proceed.") ?>')
				return false;

			}else if($count_checked_checkboxes > 6){

				//disable the remaining checkboxes
				$('input:not(:checked)').attr('readonly', 'readonly');
					
					$('#count_ch').css({'color': 'red', 'font-size': '16px'});
				$('#count_ch').text('<?php echo get_phrase("you_have_reached_the_maximum_selection_of_six_(6)_students._please_print_these_before_you_proceed.") ?>');
				toastr.error('<?php echo get_phrase("you_have_reached_the_maximum_selection_of_six_(6)_students._please_print_these_before_you_proceed.") ?>')
				return false;
			}else{
				$('#count_ch').text('You have selected '+$count_checked_checkboxes + ' students out of 6');
				$('input:not(:checked)').removeAttr('readonly');
			}
		});
	});

</script>