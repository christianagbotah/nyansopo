<?php 
$parent_id = ''; 
$class_id = ''; 
$alert_type = '';
$alert_content = '';
$display_mode = $display = 'none';
?>

<style>
body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.admission-container { 
	max-width: 1400px; 
	margin: 20px auto; 
	background: white; 
	border-radius: 20px; 
	box-shadow: 0 20px 60px rgba(0,0,0,0.3);
	overflow: hidden;
}
.admission-header {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 30px;
	text-align: center;
}
.admission-header h2 { margin: 0; font-size: 32px; font-weight: 700; color: white; }
.admission-header p { margin: 10px 0 0; opacity: 0.9; color: white; }
.form-content { padding: 0 30px; }
.section-header {
	background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
	color: white;
	padding: 15px 20px;
	margin: 30px 0 20px;
	border-radius: 10px;
	font-weight: 700;
	font-size: 18px;
	box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
}
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.form-field { margin-bottom: 20px; }
.form-field label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
.form-field input, .form-field select, .form-field textarea {
	width: 100%;
	padding: 12px 15px;
	border: 2px solid #e0e0e0;
	border-radius: 8px;
	font-size: 14px;
	transition: all 0.3s;
}
.form-field input:focus, .form-field select:focus, .form-field textarea:focus {
	border-color: #667eea;
	outline: none;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.submit-btn {
	background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
	color: #ffffff !important;
	padding: 15px 40px;
	border: none;
	border-radius: 50px;
	font-size: 18px;
	font-weight: 700;
	cursor: pointer;
	box-shadow: 0 10px 30px rgba(79, 172, 254, 0.4);
	transition: all 0.3s;
	margin: 30px;
}
.submit-btn:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(79, 172, 254, 0.6); }
.submit-btn i { color: #ffffff !important; }
.alert-note {
	background: #fff3cd;
	border-left: 4px solid #ffc107;
	padding: 20px;
	margin: 20px 30px;
	border-radius: 8px;
}
.photo-upload {
	text-align: center;
	padding: 20px;
	border: 2px dashed #e0e0e0;
	border-radius: 10px;
	background: #f9f9f9;
}
</style>

<div class="admission-container">
	<div class="admission-header">
		<h2><i class="entypo-graduation-cap"></i> Student Admission Form</h2>
		<p>Complete all required fields to admit a new student</p>
	</div>

	<link href="<?php echo base_url(); ?>assets/cdn/css/flowbite.min.css" rel="stylesheet" />

	<div class="form-content">
	<div class="alert-note">
		<strong><i class="entypo-info"></i> Important:</strong> Admitting new students will automatically create an enrollment to the selected class in the running session. Please verify all information before submission.
	</div>

	<?php if(validation_errors()): ?>
	<div style="margin: 20px 30px;">
		<div class="alert alert-danger alert-dismissible" role="alert">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			<strong><?php echo validation_errors(); ?></strong>
		</div>
	</div>
	<?php endif; ?>

	<?php echo form_open(site_url('admin/student/create/'), array('class' => 'validate', 'enctype' => 'multipart/form-data', 'id' => 'student_single_add_form')); ?>

	<!-- PERSONAL INFORMATION -->
	<div class="section-header"><i class="entypo-user"></i> Personal Information</div>
	<div class="form-grid">
		<div class="form-field">
			<label>First Name <span style="color:red">*</span></label>
			<input type="text" name="first_name" placeholder="Enter first name" required value="<?php echo set_value('first_name'); ?>">
		</div>
		<div class="form-field">
			<label>Middle Name</label>
			<input type="text" name="middle_name" placeholder="Enter middle name" value="<?php echo set_value('middle_name'); ?>">
		</div>
		<div class="form-field">
			<label>Last Name <span style="color:red">*</span></label>
			<input type="text" name="last_name" placeholder="Enter last name" required value="<?php echo set_value('last_name'); ?>">
		</div>
		<div class="form-field">
			<label>Gender <span style="color:red">*</span></label>
			<select name="sex" class="select2" onchange="updateImagePlaceholder($(this).val())" required>
				<option value="">Select Gender</option>
				<option value="male">Male</option>
				<option value="female">Female</option>
			</select>
		</div>
		<div class="form-field">
			<label>Date of Birth <span style="color:red">*</span></label>
			<div style="position: relative;">
				<input type="text" id="birthday" class="datepicker" datepicker datepicker-autohide name="birthday" placeholder="DD-MM-YYYY" required value="<?php echo set_value('birthday'); ?>" style="width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
				<div style="position: absolute; top: 50%; transform: translateY(-50%); left: 10px; pointer-events: none;">
					<svg style="width: 16px; height: 16px; color: #6b7280;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
				</div>
			</div>
		</div>
		<div class="form-field">
			<label>Blood Group</label>
			<select name="blood_group" class="select2">
				<option value="">Select Blood Group</option>
				<?php
				$blood_groups = $this->db->get('blood_group')->result_array();
				foreach($blood_groups as $blood):
				?>
				<option value="<?php echo $blood['blood_group']; ?>"><?php echo $blood['blood_group']; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-field">
			<label>Nationality</label>
			<input type="text" name="nationality" placeholder="Enter nationality" value="<?php echo set_value('nationality', 'Ghanaian'); ?>">
		</div>
		<div class="form-field">
			<label>Ghana Card ID</label>
			<input type="text" name="ghana_card_id" placeholder="GHA-XXXXXXXXX-X" value="<?php echo set_value('ghana_card_id'); ?>">
		</div>
		<div class="form-field">
			<label>Tribe</label>
			<input type="text" name="tribe" placeholder="Enter tribe" value="<?php echo set_value('tribe'); ?>">
		</div>
		<div class="form-field">
			<label>Religion</label>
			<select name="religion" id="religion" class="select2">
				<?php
				$religions = $this->db->get('religion')->result_array();
				foreach($religions as $religion):
				?>
				<option value="<?php echo $religion['religion']; ?>"><?php echo $religion['religion']; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-field" id="others" style="display:none;">
			<label>Specify Religion</label>
			<input type="text" name="others" placeholder="Please specify...">
		</div>
		<div class="form-field">
			<label>Student Phone</label>
			<input type="tel" name="student_phone" placeholder="Enter student phone" value="<?php echo set_value('student_phone'); ?>">
		</div>
		<div class="form-field">
			<label>Email Address</label>
			<input type="email" name="email" placeholder="student@example.com" value="<?php echo set_value('email'); ?>">
		</div>
		<div class="form-field">
			<label>Residential Address</label>
			<input type="text" name="address" placeholder="Enter address" value="<?php echo set_value('address'); ?>">
		</div>
	</div>

	<!-- PHOTO UPLOAD -->
	<div class="section-header"><i class="entypo-camera"></i> Student Photo</div>
	<div style="padding: 0 30px;">
		<div class="photo-upload">
			<div class="fileinput fileinput-new" data-provides="fileinput">
				<div class="fileinput-new thumbnail" style="width: 150px; height: 150px; margin: 0 auto;" data-trigger="fileinput">
					<img src="<?=base_url('uploads/user_male.jpg');?>" alt="Student">
				</div>
				<div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 200px; margin: 0 auto;"></div>
				<div style="margin-top: 15px;">
					<span class="btn btn-primary btn-file">
						<span class="fileinput-new">Select Photo</span>
						<span class="fileinput-exists">Change Photo</span>
						<input type="file" name="userfile" accept="image/*">
					</span>
				</div>
			</div>
		</div>
	</div>

	<!-- ACADEMIC INFORMATION -->
	<div class="section-header"><i class="entypo-book"></i> Academic Information</div>
	<div class="form-grid">
		<div class="form-field">
			<label>Student ID <span style="color:red">*</span></label>
			<?php 
			$student_code_pref = $this->db->get_where('settings', array('type'=>'student_code_prefix'))->row()->description;
			$student_code_f = $this->db->get_where('settings', array('type'=>'student_code_format'))->row()->description;
			$id_length = strlen($student_code_pref.$student_code_f);
			
			$this->db->select('student_code');
			$this->db->order_by('student_code', 'desc');
			$this->db->limit(1);
			$st_query = $this->db->get('student');
			$st_id = $st_query->row()->student_code;
			
			if($st_query->row()->student_code != '' || $st_query->row()->student_code != null) {
				if($st_query->num_rows() > 0) {
					$first_num = '';
					$position_of_first_num = '';
					$i = 0;
					for($i=0; $i<strlen($st_id); $i++) {
						if(is_numeric($st_id[$i])) {
							$first_num = $st_id[$i];
							break; 
						}
					}
					$position_of_first_num = strpos($st_id, $first_num);
					$n_stid = substr($st_id, $position_of_first_num, strlen($st_id) - $i);
					$student_code = $n_stid + 1;
					
					if($first_num == 0) {
						$old_len = strlen($st_id);
						$new_len = strlen($student_code);
						$act_len = ($old_len - $new_len);
						$student_code = substr($st_id, 0, $act_len).$student_code;
					} else {
						$student_code = $student_code;
					}
				} else {
					$student_code = $student_code_pref . $student_code_f;
				}
			} else {
				$student_code = $student_code_pref . $student_code_f;
			}
			?>
			<input type="text" id="id_field" name="student_code" readonly value="<?php if($first_num == 0) { echo $student_code;} else {echo $student_code_pref.$student_code;} ?>" required>
			<input type="hidden" name="barcode" id="barcode">
		</div>
		<div class="form-field">
			<label>Class to Enroll <span style="color:red">*</span></label>
			<select name="class_id" id="class_id" class="select2" onchange="return get_class_sections(this.value)" required>
				<option value="">Select Class</option>
				<?php getFullClassList(); ?>
			</select>
		</div>
		<div class="form-field">
			<label>Section <span style="color:red">*</span></label>
			<select name="section_id" id="section_selector_holder" class="select2" required>
				<option value="">Select Class First</option>
			</select>
		</div>
		<div class="form-field">
			<label>Admission Date</label>
			<div style="position: relative;">
				<input type="text" id="admission_date" datepicker datepicker-autohide name="admission_date" placeholder="DD-MM-YYYY" value="<?php echo set_value('admission_date', date('d-m-Y')); ?>" style="width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
				<div style="position: absolute; top: 50%; transform: translateY(-50%); left: 10px; pointer-events: none;">
					<svg style="width: 16px; height: 16px; color: #6b7280;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
				</div>
			</div>
		</div>
		<div class="form-field">
			<label>Former School</label>
			<input type="text" name="former_school" placeholder="Enter former school name" value="<?php echo set_value('former_school'); ?>">
		</div>
		<div class="form-field">
			<label>Class Reached</label>
			<input type="text" name="class_reached" placeholder="Enter class reached" value="<?php echo set_value('class_reached'); ?>">
		</div>
		<div class="form-field">
			<label>Benefit Category</label>
			<select name="category_id" class="select2">
				<option value="">Select Category</option>
				<option value="0">None</option>
				<?php
				$categories = $this->db->get('benefit_category')->result_array();
				foreach($categories as $row):
				?>
				<option value="<?php echo $row['category_id'];?>"><?php echo $row['name'];?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-field">
			<label>Transport Route</label>
			<select name="transport_id" class="select2">
				<option value="">Select Route</option>
				<?php
				$transports = $this->db->get('transport')->result_array();
				foreach($transports as $row):
				?>
				<option value="<?php echo $row['transport_id'];?>"><?php echo $row['route_name'];?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<!-- PARENT/GUARDIAN INFORMATION -->
	<div class="section-header"><i class="entypo-users"></i> Parent / Guardian Information</div>
	<div class="form-grid">
		<div class="form-field">
			<label>Select Parent/Guardian <span style="color:red">*</span></label>
			<select name="parent_id" id="parent_id" class="select2" required>
				<option value="">Select Guardian / Register New</option>
				<option value="new">Register New Guardian</option>
				<?php
				$parents = $this->db->get('parent')->result_array();
				foreach($parents as $row):
				?>
				<option value="<?php echo $row['parent_id'];?>"><?php echo $row['name'];?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-field">
			<label>Guardian Phone</label>
			<input type="tel" name="phone" placeholder="Enter guardian phone" value="<?php echo set_value('phone'); ?>">
		</div>
		<div class="form-field">
			<label>Parent Email</label>
			<input type="email" name="parent_email" placeholder="parent@example.com" value="<?php echo set_value('parent_email'); ?>">
		</div>
		<div class="form-field">
			<label>Emergency Contact</label>
			<input type="tel" name="emergency_contact" placeholder="Enter emergency contact" value="<?php echo set_value('emergency_contact'); ?>">
		</div>
		<div class="form-field">
			<label>Father's Name</label>
			<input type="text" name="father_name" placeholder="Enter father's name" value="<?php echo set_value('father_name'); ?>">
		</div>
		<div class="form-field">
			<label>Father's Phone</label>
			<input type="tel" name="father_phone" placeholder="Enter father's phone" value="<?php echo set_value('father_phone'); ?>">
		</div>
		<div class="form-field">
			<label>Father's Occupation</label>
			<input type="text" name="father_occupation" placeholder="Enter father's occupation" value="<?php echo set_value('father_occupation'); ?>">
		</div>
		<div class="form-field">
			<label>Mother's Name</label>
			<input type="text" name="mother_name" placeholder="Enter mother's name" value="<?php echo set_value('mother_name'); ?>">
		</div>
		<div class="form-field">
			<label>Mother's Phone</label>
			<input type="tel" name="mother_phone" placeholder="Enter mother's phone" value="<?php echo set_value('mother_phone'); ?>">
		</div>
		<div class="form-field">
			<label>Mother's Occupation</label>
			<input type="text" name="mother_occupation" placeholder="Enter mother's occupation" value="<?php echo set_value('mother_occupation'); ?>">
		</div>
	</div>

	<!-- MEDICAL & SPECIAL NEEDS -->
	<div class="section-header"><i class="entypo-heart"></i> Medical & Special Needs</div>
	<div class="form-grid">
		<div class="form-field">
			<label>Allergies</label>
			<textarea name="allergies" rows="3" placeholder="List any allergies..."><?php echo set_value('allergies'); ?></textarea>
		</div>
		<div class="form-field">
			<label>Medical Conditions</label>
			<textarea name="medical_conditions" rows="3" placeholder="List any medical conditions..."><?php echo set_value('medical_conditions'); ?></textarea>
		</div>
		<div class="form-field">
			<label>Health Details</label>
			<textarea name="student_health" rows="3" placeholder="Additional health information..."><?php echo set_value('student_health'); ?></textarea>
		</div>
		<div class="form-field">
			<label style="display: flex; align-items: center; gap: 10px;">
				<input type="checkbox" id="special_diet" name="special_diet" value="0" onchange="special_diet_value_change()" style="width: auto;">
				On Special Diet
			</label>
		</div>
		<div class="form-field" id="special_diet_details_holder" style="display:none; grid-column: 1/-1;">
			<label>Special Diet Details</label>
			<textarea name="student_special_diet_details" rows="3" placeholder="Provide special diet details..."><?php echo set_value('student_special_diet_details'); ?></textarea>
		</div>
	</div>

	<!-- BOARDING INFORMATION -->
	<?php if($boarding_system == 'yes'): ?>
	<div class="section-header"><i class="entypo-home"></i> Boarding Information</div>
	<div class="form-grid items-center">
		<div class="form-field">
			<label>Residence Type <span style="color:red">*</span></label>
			<select name="residence_type" id="residence_type" class="select2">
				<option value="Day">Day</option>
				<option value="Boarding">Boarding</option>
			</select>
		</div>
		<div class="form-field">
			<label>.<span style="color:red"></span></label>
			<label style="background: #ffc107; padding: 10px; border-radius: 5px; font-weight: 700;" id="admission_fee">
				ADMISSION FEE: <?php echo number_format($this->boarding_model->getAdmissionFeeByResidentialStatus('Day'), 2, '.', ','); ?>
			</label>
		</div>
	</div>
	<div id="boarding_holder" style="display:none; padding: 0 30px;">
		<div class="form-grid">
			<div class="form-field">
				<label>House</label>
				<select name="house_id" id="house_id" class="select2">
					<option value="">Select House</option>
					<?php
					$allAvailableHouses = $this->boarding_model->getAllAvailableHouses();
					foreach($allAvailableHouses as $house):
					?>
					<option value="<?=$house['house_id'];?>"><?=$house['house_name'];?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-field">
				<label>Dormitory</label>
				<select name="dormitory_id" id="dormitory_id" class="select2">
					<option value="">Select House First</option>
				</select>
			</div>
			<div class="form-field">
				<label>Bed Number</label>
				<select name="bed_id" id="bed_id" class="select2">
					<option value="">Select Dormitory First</option>
				</select>
			</div>
		</div>
	</div>
	<?php else: ?>
	<input type="hidden" name="residence_type" value="Day">
	<?php endif; ?>
	
	<input type="hidden" name="my_admission_fee" id="my_admission_fee" value="<?=$this->boarding_model->getAdmissionFeeByResidentialStatus('Day');?>">
	<input type="hidden" name="item_id" id="item_id" value="9">

	<!-- LOGIN CREDENTIALS -->
	<div class="section-header"><i class="entypo-lock"></i> Login Credentials</div>
	<div class="form-grid">
		<div class="form-field">
			<label>Username</label>
			<input type="text" name="username" readonly value="<?php if($first_num == 0) { echo $student_code;} else {echo $student_code_pref.$student_code;} ?>">
		</div>
		<div class="form-field">
			<label>Password <span style="color:red">*</span></label>
			<div style="display: flex; gap: 10px;">
				<input type="password" id="pass" name="password" value="123456" required style="flex: 1;">
				<button type="button" onclick="toggle_password_view()" style="padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; background: white; cursor: pointer;">
					<i class="fa fa-eye-slash" id="eye_pass"></i>
				</button>
			</div>
		</div>
	</div>

	<div style="text-align: center; padding: 20px 0 40px;">
		<button type="submit" class="submit-btn bg-blue-900">
			<i class="entypo-check"></i> Admit Student
		</button>
	</div>

	<?php echo form_close(); ?>
	</div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/flowbite.min.js"></script>

<script>
$(document).ready(function() {
	$('#others').css('display', 'none');
	barcode();
	
	// Initialize Flowbite datepickers
	const birthdayEl = document.getElementById('birthday');
	const admissionEl = document.getElementById('admission_date');
	
	if (birthdayEl) {
		new Datepicker(birthdayEl, {
			format: 'dd-mm-yyyy',
			autohide: true,
			todayHighlight: true,
			maxDate: new Date()
		});
	}
	
	if (admissionEl) {
		new Datepicker(admissionEl, {
			format: 'dd-mm-yyyy',
			autohide: true,
			todayHighlight: true
		});
	}
});

function updateImagePlaceholder(val) {
	let base_url = '<?=base_url();?>';
	let fileInputExists = $('.fileinput-exists img').attr('src');
	if(fileInputExists == '' || fileInputExists == undefined) {
		$('.fileinput img').attr('src', base_url + 'uploads/user_' + val + '.jpg');
	}
}

function special_diet_value_change() {
	let special_diet = $('#special_diet').filter(':checked').length;
	if(special_diet > 0) {
		$('#special_diet').val(1);
		$('#special_diet_details_holder').slideDown('slow');
	} else {
		$('#special_diet').val(0);
		$('#special_diet_details_holder').slideUp('slow');
	}
}

function get_class_sections(class_id) {
	$.ajax({
		url: '<?php echo site_url('admin/get_class_section/');?>' + class_id,
		success: function(response) {
			jQuery('#section_selector_holder').html(response);
		}
	});
}

$('#parent_id').change(function(event) {
	var new_parent = $('#parent_id').val();
	if(new_parent == 'new') {
		showAjaxModal('<?php echo site_url('modal/popup/modal_parent_add_st/');?>');
	}
});

function barcode() {
	var id = $('#id_field').val();
	var barcode = '<?php echo site_url('admin/create_barcode/'); ?>' + id;
	$('#barcode').attr('value', barcode);
}

$('#religion').change(function() {
	var religion = $('#religion').val();
	if(religion == 'OTHERS') {
		$('#others').css('display', 'block');
	} else {
		$('#others').css('display', 'none');
	}
});

function toggle_password_view() {
	if($('#pass').attr('type') == 'password') {
		$('#pass').removeAttr('type');
		$('#eye_pass').removeClass('fa-eye-slash');
		$('#pass').attr('type', 'text');
		$('#eye_pass').addClass('fa-eye');
	} else if($('#pass').attr('type') == 'text') {
		$('#pass').removeAttr('type');
		$('#eye_pass').removeClass('fa-eye');
		$('#pass').attr('type', 'password');
		$('#eye_pass').addClass('fa-eye-slash');
	}
}

$('#student_single_add_form').submit(function(event) {
	event.preventDefault();
	$('html, body').animate({ scrollTop: 0 }, 1000);
	showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br></div></center>', 'Loading');
	
	$.ajax({
		url: '<?php echo site_url('admin/student/create'); ?>',
		type: 'POST',
		dataType: 'json',
		data: new FormData(this),
		cache: false,
		contentType: false,
		processData: false
	})
	.done(function(data) {
		if(data.done == 'success') {
			showAjaxModal_alert('Student admitted successfully.', 'Success');
			setTimeout(() => {
				$('.close').click();
				navigation('<?php echo site_url('admin/student_add'); ?>');
			}, 3000);
		} else {
			$(function(){
				$.each(data, function(index, val) {
					$('#list_err_student_create').append('<li style="font-size: 16px">' + val + '</li>');
				}); 
			});
			showAjaxModal_alert('<ul id="list_err_student_create"></ul>', 'Error');
		}
	})
	.fail(function(err) {
		showAjaxModal_alert('Error:' + err.responseText, 'Error');
	});
});

$('#residence_type').change(function(e) {
	let selectedVal = $(this).val();
	if(selectedVal == 'Day') {
		$('#boarding_holder').slideUp('slow');
	} else {
		$('#boarding_holder').slideDown('slow');
	}
	
	$.ajax({
		url: '<?php echo site_url('admin/getAdmissionFee/') ?>' + selectedVal,
		type: 'post',
		dataType: 'json',
		cache: false
	})
	.done(function(response) {
		$('#admission_fee').text(response.text);
		$('#my_admission_fee').val(response.amount);
		$('#item_id').val(response.item_id);
	});
});

$('#house_id').change(function(e) {
	let selectedVal = $(this).val();
	$.ajax({
		url: '<?php echo site_url('admin/getAllAvailableDormitoriesByHouseId/') ?>' + selectedVal,
		type: 'post',
		dataType: 'html',
		cache: false
	})
	.done(function(response) {
		$('#dormitory_id').html(response);
	});
});

$('#dormitory_id').change(function(e) {
	let selectedVal = $(this).val();
	$.ajax({
		url: '<?php echo site_url('admin/getAllAvailableBedsByDormitoryId/') ?>' + selectedVal,
		type: 'post',
		dataType: 'html',
		cache: false
	})
	.done(function(response) {
		$('#bed_id').html(response);
	});
});
</script>
