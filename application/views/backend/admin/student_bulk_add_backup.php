<style type="text/css">
	#preloader2 {
		width: 100%; 
		min-height: 1920px; 
		background-color: #fff; 
		text-align: center; 
		z-index: 99999; 
		position: absolute;
	}
</style>
<div id="preloader2" style="display: none">
		<img id="loader_logo" src="<?php echo base_url();?>assets/images/lightworldtech.png" width="100px">
		<img id="loader" src="<?php echo base_url();?>assets/images/validate.gif" width="64px">
		<p id="loading_txt" style="padding-top: 15px; font-weight: bold;">Admitting your students, please wait<span id="dot1">.</span><span id="dot2">.</span><span id="dot3">.</span></p>
	</div>
<hr />
<?php 
	$loader = '<img src="'.base_url('assets/images/validate.gif').'" width="16px;">';
	$checked_icon = '<i class="glyphicon glyphicon-check" style="color: green;"></i>';

	$popover_content = 'Select Class and Section and then click on this button. This button helps you to automatically generate a CSV file which is just like an Excel file. The generated file will be downloaded automatically into your local disk Download Folder. Open it with Microsoft Excel and fill the columns provided. When you are done, save the file in csv format (NB: please click "Yes" button whenever you see a popup window in excel while saving) and choose a class and section and upload the file. Make sure the date of birth column is formatted as this: mm-dd-yy';

	//show success message if admission was successful
	if(isset($_GET['success']) && $_GET['success'] == 1) {
		echo '
			<div class="row">
				<div class="alert alert-success alert-dismissable" role="alert" id="admission" align="center">
					<button type="button" class="close" aria-label="Close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>
					<strong>Uploaded students were admitted successfully!</strong>
				</div>
			</div>
		';
	}
?>


<div class="row" id="instruction">
	<div class="col-md-12 col-sm-12 col-xs-12">
		<blockquote class="blockquote-blue">
			<p style="font-weight: 700; font-size: 15px;">
			<?php echo get_phrase('please_follow_the_instructions_for_adding_bulk_student:'); ?>
		</p>
			<ol>
				<li style="padding: 5px;"><?php echo get_phrase('at_first_select_the_class_and_section').'.'; ?></li>
				<li style="padding: 5px;"><?php echo get_phrase('click_').'"Generate CSV File".'; ?></li>
				<li style="padding: 5px;"><?php echo get_phrase('open_the_downloaded_').'"class name_bulk_student.csv" File. '.get_phrase('fill_it_with_students\'_details_under_each_heading').'.';?></li>
				<li style="padding: 5px;">Please make sure the dates of birth are in the right format <strong style="color: #fff; ">YYYY-MM-DD, eg: 2005-05-25</strong></li>
				<li style="padding: 5px;"><?php echo get_phrase('save_the_edited_').'"class name_bulk_student.csv" File.';?></li>
				<li style="padding: 5px;"><?php echo get_phrase('make_sure_you_save_the_file_in_cSV_format_as_this: ').'"class name_bulk_student.csv"';?></li>
				<li style="padding: 5px;"><?php echo get_phrase('click_the_').'"Select CSV File" Button '.get_phrase('and_choose_the_file_you_just_edited_from_your_local_disk').'.';?></li>
				<li style="padding: 5px;">Congratulations!! That is all there is for you to do. Please relax as we take care of the remaining processes. We will notify you as soon as we are done with the admissions. Thank you.</li>
			</ol>
			<p style="color: #ffffff; font-weight: 500; text-align: center">
				***<?php echo get_phrase('this_system_keeps_track_of_duplication_in_email_ID.').' '.get_phrase('so_please_enter_unique_email_ID_for_every_student_and_parent').'***'; ?>
			</p>
		</blockquote>
	</div>
</div><hr>
<div class="row" id="err_alert" style="display: none;">
	<div class="col-sm-2"></div>
	<div class="col-sm-8">
		<div class="alert alert-danger alert-dismissable" role="alert" style="text-align: center">
			<button type="button" class="close" data-dismiss="alert" aria-label="close"><span aria-hidden="true">&times;</span></s></button>
			<strong>Please make sure class is selected!</strong>
		</div>
	</div>
	<div class="col-sm-2"></div>
</div>

<?php 
	$error_msg = '';
	if(isset($_GET['success']) && $_GET['success'] == 1) { ?>

		<div class="row">
		<div class="col-sm-2"></div>
		<div class="col-sm-8">
			<div class="alert alert-success alert-dismissable" role="alert" style="text-align: center">
				<button type="button" class="close" data-dismiss="alert" aria-label="close"><span aria-hidden="true">&times;</span></s></button>
				<strong>Students Admitted Successfully!</strong>
			</div>
		</div>
		<div class="col-sm-2"></div>
		</div>

		<?php
	} else if(isset($_GET['error']) && $_GET['error'] == 1) { ?>
		<div class="row">
		<div class="col-sm-2"></div>
		<div class="col-sm-8">
			<div class="alert alert-danger alert-dismissable" role="alert" style="text-align: center">
				<button type="button" class="close" data-dismiss="alert" aria-label="close"><span aria-hidden="true">&times;</span></s></button>
				<strong>The Email Address <u><?= $_GET['email']; ?></u> is Invalid. Admission process terminated!</strong>
			</div>
		</div>
		<div class="col-sm-2"></div>
		</div>
		
		<?php
	} else if(isset($_GET['error']) && $_GET['error'] == 2 || isset($_GET['error']) && $_GET['error'] == 3) { ?>
		<div class="row">
		<div class="col-sm-2"></div>
		<div class="col-sm-8">
			<div class="alert alert-danger alert-dismissable" role="alert" style="text-align: center">
				<button type="button" class="close" data-dismiss="alert" aria-label="close"><span aria-hidden="true">&times;</span></s></button>
				<strong>Email <u><?= $_GET['email']; ?></u> already exists. Admission process terminated! Change this email and try again.</strong>
			</div>
		</div>
		<div class="col-sm-2"></div>
		</div>
		
		<?php
	}
?>
<hr>

<?php echo form_open(site_url('admin/bulk_student_add_using_csv/import') ,
			array('class' => 'form-inline validate', 'id' => 'upload_form', 'name' => 'upload_form', 'style' => 'text-align:center;',  'enctype' => 'multipart/form-data'));?>
<div class="row">
	<div class="col-md-3 col-sm-3 col-xs-2"></div>
	<div class="col-md-3 col-sm-3 col-xs-5">
		<div class="form_group">
			<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('class');?></label>
			<select name="class_id" id="class_id" class="form-control select2" required
				onchange="get_sections(this.value)"  data-validate="required"  data-message-required="<?php echo get_phrase('value_required');?>">
				<option value=""><?php echo get_phrase('select_class');?></option>
				<?php
            getFullClassList();
        ?>
			</select>
		</div>
	</div>
	<div id="section_holder" class="col-md-3 col-sm-3 col-xs-3">
		<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section');?></label>
		<select name="section_id" id="section_id" class="form-control select2">
			<option value=""><?php echo get_phrase('select_class_first');?></option>
		</select>
	</div>
	<div class="col-md-3 col-sm-3 col-xs-2"></div>
</div>
<div class="row">
	<div class="col-md-offset-4 col-md-4 col-sm-6 col-sm-offset-3  col-xs-6 col-xs-offset-3 " style="padding: 15px;">
		<button type="button" class="btn btn-primary pop-over" data-content="<?php echo $popover_content; ?>" data-placement="left" data-title="How to download and upload your csv file" name="generate_csv" id="generate_csv"><?php echo get_phrase('generate_').'CSV '.get_phrase('file'); ?></button>
	</div>
	<div class="col-md-offset-2 col-md-8 col-sm-8 col-sm-offset-2  col-xs-12">
        <span style="color: red;">*Birthday format must be <strong style="color: blue;">YYYY-MM-DD e.g: 2005-05-25</strong> before you upload the file*</span>
    </div>
	<div class="col-md-offset-4 col-md-4 col-sm-6 col-sm-offset-3  col-xs-6 col-xs-offset-3" style="padding-bottom:15px;">
	<input type="file" name="userfile" id="userfile" onchange="check_loaded_csvfile()" class="form-control inline btn btn-info" data-validate="required" data-message-required="<?php echo get_phrase('required'); ?>"
                                                accept="text/csv, .csv">
	</div>
	<div class="col-md-offset-4 col-md-4 col-sm-6 col-sm-offset-3  col-xs-6 col-xs-offset-3">
		<button type="submit" class="btn btn-success" name="import_csv" id="import_csv"><?php echo get_phrase('import_CSV'); ?></button>
	</div>
</div>
<br><br>
<?php echo form_close();?>

<hr>

<a href="" on style="display: none;" id = "bulk">Download</a>


<script type="text/javascript">

var class_selection = '';
$(document).ready(function($) {

$('#submit_button').attr('disabled', 'disabled');
$('#import_csv').attr('disabled', 'disabled');

$('#generate_csv').mouseover(function() {
	$('#generate_csv').popover('show');
});

$('#generate_csv').mouseout(function() {
	$('#generate_csv').popover('hide');
});

	
});



	function get_sections(class_id) {
		if (class_id != "") {
			$('#err_alert').css('display', 'none');

			$.ajax({
	            url: '<?php echo site_url('admin/get_sections/');?>' + class_id ,
	            success: function(response)
	            {
	                jQuery('#section_holder').html(response);
	                jQuery('#bulk_add_form').show();
	            }
	        });
		}
	}
	$("#generate_csv").click(function(){
		var class_id 	= $('#class_id').val();
		var section_id 	= $('#section_id').val();

		if(class_id == '' || section_id == '') {
			toastr.error("<?php echo get_phrase('please_make_sure_class_and_section_are_selected'); ?>");
			$('#err_alert').css('display', 'block');
		}

		else {
			$.ajax({
			  	url: '<?php echo site_url('admin/generate_bulk_student_csv/');?>' + class_id + '/' + section_id,
			  	dataType: 'json',
			  	success: function(response) {
			    	toastr.success("<?php echo get_phrase('file_generated_and_downloaded_successfully._please_check_your_download_folder.'); ?>");
						$("#bulk").attr('href', response.file_path);
						$("#bulk").attr('download', response.file_name);
						jQuery('#bulk')[0].click();
			    	//document.location = response;
			  	}
			});
		}
	});

	//check loaded file
	function check_loaded_csvfile() {
		//send file path to php to upload the file and validate
		var loader = '<?php echo $loader;?>';
		var checked_icon = '<?php echo $checked_icon; ?>';
		var class_id = $('#class_id').val();
		var section_id = $('#section_id').val();

		//get the file
		var upload_file = $('#userfile').prop('files')[0];
		var form_data = new FormData();
		form_data.append('userfile', upload_file);
		form_data.append('<?php echo $this->security->get_csrf_token_name(); ?>', '<?php echo $this->security->get_csrf_hash(); ?>');

		if(class_id == '' && section_id == '' || class_id == null && section_id == null) {
			toastr.error('Make sure Class and Section fields are selected before you proceed!');

			navigation('<?php echo site_url('admin/student_bulk_add'); ?>');
			//location.reload();
			return false;
		} else{
			//first disable the upload button
			var import_btn = $('#import_csv').attr('disabled', 'disabled');
			//change the btn text to 'please wait. Validating file...'
			import_btn.html(loader + ' Please wait. Validating uploaded file...');

			//let's send the file path to php now for upload and validation
			$.ajax({
				url: '<?php echo site_url('admin/uploaded_csvfile_parent_validate/');?>',
				type: 'post',
				data: form_data,
				dataType: 'text',
				cache: false,
				contentType: false,
				processData: false,
				success: function(response) {
					if(response == 'email_error') {
						alert('Parents\' email validation failed. Please check parents\' emails carefully and try again.');
						$('#err_alert').text('Parents\' email validation failed. Please check parents\' emails carefully and try again.');
						$('#err_alert').css({'display': 'block', 'color' : 'red', 'text-align': 'center'});

						setTimeout(() => {
						  //window.location.href = '<?php echo site_url('admin/student_bulk_add'); ?>';
						  navigation('<?php echo site_url('admin/student_bulk_add'); ?>');
						}, 3000);
						return false;

					} else if(response.length > 0) {
						showAjaxModal_confirm('<?php echo site_url('modal/popup_2/upload_validate/');?>' + response);
						$('#modal_confirm .modal-content').css('margin-top', '450px');

						$('#modal_confirm').modal({
							backdrop: 'static',
							keyboard: 'false'
						});
					} else{
						//enable the upload button
						var import_btn = $('#import_csv').removeAttr('disabled');
						//change the btn text to 'parent validation successful
						import_btn.html(checked_icon + ' File validation successful. Please wait.');

						setTimeout(() => {
						  _click();
						}, 3000);

						function _click() {
							//load the pre-loader again
							$('#instruction').css('display', 'none');
							$('#import_csv').click();

							$('#modal_confirm').modal('show');
							$('#modal_confirm .modal-content').css('margin-top', '450px');
							$('#modal_confirm .modal-header').html('<strong style="color: #fff;">Please Wait... NB: Please DO NOT CLICK anywhere on the page now. Process might be terminated if you click or refresh the page.</strong>');
							$('#modal_confirm .modal-body').html('<center><div><img style="position: relative;" src="<?php echo base_url();?>assets/images/lightworldtech.png" width="100px"><img style="position: relative;" src="<?php echo base_url();?>assets/images/validate.gif" width="64px"><p style="position: relative;" style="padding-top: 15px; font-weight: bold;">Admitting your students, please wait<span id="dot1">.</span><span id="dot2">.</span><span id="dot3">.</span></p></div></center>');
							$('#modal_confirm .modal-footer').html('');
							$('body').css('cursor', 'wait');

							$('#main_div').click(function(event) {
								event.preventDefault();
								return false;
							});
							$('#main_div').css('cursor', 'wait');
						}
					}
					
				}

			});
		}
	}

	//call this when user is done confirming the modal

		$(document).ready(function() { //no button clicked
			$('#modal_no').click(function() {
			//reload the page
			toastr.error('Cancelled!');
			navigation('<?php echo site_url('admin/student_bulk_add'); ?>');
			//location.reload();

			});
		});

		$(document).ready(function() { //no button clicked
			$(document).on('hidden.bs.modal', '#modal_confirm', function() {
				//reload the page
				toastr.error('Cancelled!');
				navigation('<?php echo site_url('admin/student_bulk_add'); ?>');
				//location.reload();
			});
		});


		$(document).ready(function() { // yes button clicked
			$('#modal_yes').click(function() {
			//remove the disabled attr
			$('#instruction').css('display', 'none');
			$('#import_csv').removeAttr('disabled');
			$('#import_csv').click();
			$('#modal_confirm .modal-header').html('<strong style="color: #fff;">Please Wait... NB: Please DO NOT CLICK anywhere on the page now. Process might be terminated if you click or refresh the page.</strong>');
			$('#modal_confirm .modal-body').html('<center><div><img style="position: relative;" src="<?php echo base_url();?>assets/images/lightworldtech.png" width="100px"><img style="position: relative;" src="<?php echo base_url();?>assets/images/validate.gif" width="64px"><p style="position: relative;" style="padding-top: 15px; font-weight: bold;">Admitting your students, please wait<span id="dot1">.</span><span id="dot2">.</span><span id="dot3">.</span></p></div></center>');
			$('#main_div').css('cursor', 'wait');
			$('#modal_confirm .modal-footer').html('');
			$('body').css('cursor', 'wait');

			});

		});

</script>
