<hr />

<?php echo form_open(site_url('admin/attendance_selector/'), array('id' => 'att_selector_form'));?>
<div class="row">

	<div class="col-md-3 col-sm-offset-2">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('date');?></label>
			<input type="text" class="form-control datepicker" data-end-date="<?= date('d-m-Y');?>" name="timestamp" data-format="dd-mm-yyyy"
				value="<?php echo date("d-m-Y");?>"/>
		</div>
	</div>

	<?php
		$query = $this->db->get_where('section' , array('class_id' => $class_id,'teacher_id'=>$this->session->userdata('teacher_id')));
		if($query->num_rows() > 0):
			$sections = $query->result_array();
	?>

	<div class="col-md-3">
		<div class="form-group">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section');?></label>
			<select class="form-control select2" name="section_id">
				<?php foreach($sections as $row):?>
					<option value="<?php echo $row['section_id'];?>"><?php echo $row['name'];?></option>
				<?php endforeach;?>
			</select>
		</div>
	</div>
	<?php endif;?>
	<input type="hidden" name="class_id" value="<?php echo $class_id;?>">
	<input type="hidden" name="year" value="<?php echo $running_year;?>">
	<input type="hidden" name="term" value="<?php echo $running_term;?>">
	<input type="hidden" name="sem" value="<?php echo $running_sem;?>">

	<div class="col-md-3" style="margin-top: 20px;">
		<button type="submit" class="btn btn-info"><?php echo get_phrase('manage_attendance');?></button>
	</div>

<hr>
<hr>
<br>
<br>
	<div id="students_holder" style="display: none" class="row col-md-12"></div>

</div>
<?php echo form_close();?>



<script type="text/javascript">

jQuery(document).ready(function($) {
	select_students(<?=$class_id ?>);
});


function select_students(class_id) {
	if(class_id !== ''){

		$.ajax({
			url: '<?php echo site_url('admin/get_multi_select_students/'); ?>' + class_id,
			success:function (response)
			{

			jQuery('#students_holder').slideDown('slow');
			jQuery('#students_holder').html(response);
			}
		});

	}	else {
		jQuery('#students_holder').slideUp('slow');
	}
}


//ajax
$('#att_selector_form').submit(function(event) {
	/* Act on the event */

	event.preventDefault();

	let item_checked = $('.check').filter(':checked').length;
	if(item_checked < 1) {
		showAjaxModal_alert('No student was selected!', 'Error');
		return false;
	}

	$('#main_page').empty();

	//SHOW LOADER
  $('#main_page').html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px; ">Fetching Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>');


	$.ajax({
      url: '<?php echo site_url('admin/attendance_selector/'); ?>',
      type: 'POST',
      dataType: 'html',
      data: new FormData(this),
      cache: false,
      contentType: false,
      processData: false
  })
  .done(function(data) {
  	if(data == 'promotion error term') {
  		showAjaxModal_confirm('Make sure students were promoted during the previous term. For further assistance, kindly contact the system administrator.', 'Error');

  		setTimeout(() => {
  			navigation('<?php echo site_url('admin/manage_attendance'); ?>')
  		}, 5000);
      
  	} else if(data == 'promotion error sem') {
  		showAjaxModal_confirm('Make sure students were promoted during the previous semester. For further assistance, kindly contact the system administrator.', 'Error');

  		

  		setTimeout(() => {
  			navigation('<?php echo site_url('admin/manage_attendance'); ?>')
  		}, 5000);
      
  	} else {
  		$('#main_page').empty();
  		
  		navigation(data);

  		$('#pre_notice').fadeOut('400', function() {
            $('#pre_notice').remove();
            $('#pre_notice').css('display', 'none');
        }); 

  	}
  });
});
</script>
