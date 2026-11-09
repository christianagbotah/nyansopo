<?php
$edit_data_query = $this->db->get_where('enroll', array(
	'student_id' => $param2, 'mute' => '0', 'year' => $this->db->get_where('settings', array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings', array('type' => 'running_term'))->row()->description
));

if($edit_data_query->num_rows() < 1) {
	$edit_data_query = $this->db->get_where('enroll', array(
		'student_id' => $param2, 'mute' => '0', 'year' => $this->db->get_where('settings', array('type' => 'running_year'))->row()->description, 'sem' => $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description
	));
	$edit_data = $edit_data_query->result_array();
} else {
	$edit_data = $edit_data_query->result_array();
}

foreach ($edit_data as $row):
	$student = $this->db->get_where('student', array('student_id' => $row['student_id'], 'mute' => '0'))->row();
	$gender = $student->sex;
	$benefit_status = $student->benefit_status ?? 0;
	$special_diet = $student->special_diet ?? 0;
	$parent_id = $student->parent_id;
	$parent = $this->db->get_where('parent', array('parent_id' => $parent_id))->row();
?>

<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.admission-container {
	max-width: 1800px;
	margin: 20px auto;
	background: white;
	border-radius: 20px;
	box-shadow: 0 20px 60px rgba(0,0,0,0.3);
	overflow: hidden;
}
@media (max-width: 640px) { .admission-container { margin: 10px; border-radius: 15px; width: calc(100% - 20px); } }
.admission-header {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
	color: white;
	padding: 20px 30px;
	text-align: center;
	position: relative;
	overflow: hidden;
}
.admission-header::before { content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%; }
.admission-header::after { content: ''; position: absolute; bottom: -30%; left: -5%; width: 200px; height: 200px; background: rgba(255,255,255,0.08); border-radius: 50%; }
@media (max-width: 640px) { .admission-header { padding: 30px 20px; } }
.admission-header h2 { margin: 0; font-size: 28px; font-weight: 800; color: white; position: relative; z-index: 1; letter-spacing: -0.5px; }
@media (max-width: 640px) { .admission-header h2 { font-size: 22px; } }
.admission-header p { margin: 12px 0 0; opacity: 0.95; color: white; font-size: 16px; position: relative; z-index: 1; font-weight: 500; }
@media (max-width: 640px) { .admission-header p { font-size: 14px; } }
.form-content { padding: 30px 40px; box-sizing: border-box; background: #f8f9fa; }
@media (max-width: 640px) { .form-content { padding: 0 20px; } }
.section-card {
	background: white;
	border-radius: 12px;
	padding: 25px;
	margin: 25px 0;
	box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
@media (max-width: 640px) {
	.section-card {
		padding: 15px;
		margin: 15px 0;
		border-radius: 10px;
	}
}
.section-header {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
	color: white;
	padding: 18px 24px;
	margin: 35px 0 25px;
	border-radius: 12px;
	font-weight: 700;
	font-size: 18px;
	box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
	display: flex;
	align-items: center;
	gap: 12px;
	transition: all 0.3s;
}
.section-header:hover { transform: translateX(5px); box-shadow: 0 6px 25px rgba(245, 158, 11, 0.4); }
.section-header i { font-size: 22px; }
@media (max-width: 640px) { .section-header { padding: 15px 18px; margin: 25px 0 20px; font-size: 16px; } .section-header i { font-size: 20px; } }
.form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
@media (max-width: 1200px) { .form-grid { grid-template-columns: repeat(2, 1fr); gap: 18px; } }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; gap: 18px; } }
.form-field { margin-bottom: 0; }
.form-field label { display: block; margin-bottom: 10px; font-weight: 600; color: #1f2937; font-size: 14px; letter-spacing: 0.2px; }
.form-field input, .form-field select, .form-field textarea {
	width: 100%;
	padding: 13px 16px;
	border: 2px solid #e5e7eb;
	border-radius: 10px;
	font-size: 14px;
	transition: all 0.3s;
	box-sizing: border-box;
	background: #ffffff;
	color: #1f2937;
	font-weight: 500;
}
.form-field input:hover, .form-field select:hover, .form-field textarea:hover { border-color: #d1d5db; }
.form-field input:focus, .form-field select:focus, .form-field textarea:focus {
	border-color: #f59e0b;
	outline: none;
	box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
	background: #fffbf5;
}
.form-field input::placeholder, .form-field textarea::placeholder { color: #9ca3af; font-weight: 400; }
.submit-btn {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
	color: #ffffff !important;
	padding: 16px 48px;
	border: none;
	border-radius: 12px;
	font-size: 18px;
	font-weight: 700;
	cursor: pointer;
	box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
	transition: all 0.3s;
	margin: 40px 0;
	letter-spacing: 0.3px;
}
@media (max-width: 640px) { .submit-btn { padding: 14px 36px; font-size: 16px; margin: 30px 0; width: 100%; } }
.submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 40px rgba(245, 158, 11, 0.4); background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
.submit-btn:active { transform: translateY(-1px); }
.submit-btn i { color: #ffffff !important; margin-right: 8px; }
.photo-upload {
	text-align: center;
	padding: 30px;
	border: 3px dashed #d1d5db;
	border-radius: 12px;
	background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
	transition: all 0.3s;
}
/* Modern Select2 Styling for Guardian Selection */
.select2-container--default .select2-selection--single {
	height: 52px;
	border: 2px solid #e5e7eb;
	border-radius: 12px;
	padding: 8px 16px;
	transition: all 0.3s ease;
	background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
}
.select2-container--default .select2-selection--single:hover {
	border-color: #f59e0b;
	box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}
.select2-container--default.select2-container--focus .select2-selection--single {
	border-color: #f59e0b;
	box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15);
	background: #fffbf5;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
	line-height: 34px;
	color: #1f2937;
	font-size: 15px;
	font-weight: 600;
	padding-left: 0;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder {
	color: #9ca3af;
	font-weight: 500;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
	height: 50px;
	right: 12px;
}
.select2-dropdown {
	border: 2px solid #f59e0b;
	border-radius: 12px;
	box-shadow: 0 20px 60px rgba(0,0,0,0.15);
	margin-top: 8px;
	background: white;
}
.select2-container--default .select2-results__option {
	padding: 0;
	font-size: 14px;
	color: #1f2937;
	transition: all 0.2s ease;
	border: none;
	margin: 4px 8px;
	border-radius: 8px;
}
.select2-container--default .select2-results__option--highlighted {
	background-color: transparent !important;
	color: inherit !important;
	transform: scale(1.02);
}
.select2-container--default .select2-results__option[aria-selected=true] {
	background-color: rgba(245, 158, 11, 0.1) !important;
	color: #92400e !important;
	font-weight: 700;
}
.select2-search--dropdown {
	padding: 16px;
	background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
	border-bottom: 2px solid #f59e0b;
}
.select2-search--dropdown .select2-search__field {
	border: 2px solid #e5e7eb;
	border-radius: 10px;
	padding: 12px 16px;
	font-size: 14px;
	font-weight: 500;
	background: white;
	transition: all 0.3s;
}
.select2-search--dropdown .select2-search__field:focus {
	border-color: #f59e0b;
	outline: none;
	box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}
.select2-results__message {
	padding: 20px;
	text-align: center;
	color: #6b7280;
	font-style: italic;
}
</style>

<div class="admission-container">
	<div class="admission-header">
		<h2><i class="entypo-pencil"></i> Edit Student Information</h2>
		<p>Update student details and enrollment information</p>
	</div>


	<div class="form-content">
		<?php echo form_open(site_url('admin/student/do_update/'.$row['student_id'].'/'.$row['class_id']), array('class' => 'validate', 'enctype' => 'multipart/form-data', 'id' => 'student_single_update_form', 'novalidate' => 'novalidate')); ?>

		<!-- PERSONAL INFORMATION -->
		<div class="section-card">
			<div class="section-header"><i class="entypo-user"></i> Personal Information</div>
			<div class="form-grid">
			<div class="form-field">
				<label>First Name <span style="color:red">*</span></label>
				<input type="text" name="first_name" placeholder="Enter first name" required value="<?php echo $student->first_name; ?>">
			</div>
			<div class="form-field">
				<label>Middle Name</label>
				<input type="text" name="middle_name" placeholder="Enter middle name" value="<?php echo $student->middle_name; ?>">
			</div>
			<div class="form-field">
				<label>Last Name <span style="color:red">*</span></label>
				<input type="text" name="last_name" placeholder="Enter last name" required value="<?php echo $student->last_name; ?>">
			</div>
			<div class="form-field">
				<label>Gender <span style="color:red">*</span></label>
				<select name="sex" class="select2" required>
					<option value="">Select Gender</option>
					<option value="male" <?php if(strtolower($gender) == 'male') echo 'selected'; ?>>Male</option>
					<option value="female" <?php if(strtolower($gender) == 'female') echo 'selected'; ?>>Female</option>
				</select>
			</div>
			<div class="form-field">
				<label>Date of Birth <span style="color:red">*</span></label>
				<div style="position: relative;">
					<input type="text" id="birthday" datepicker datepicker-autohide name="birthday" placeholder="DD-MM-YYYY" required value="<?php echo $student->birthday; ?>" style="width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
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
					<option value="<?php echo $blood['blood_group']; ?>" <?php if($student->blood_group == $blood['blood_group']) echo 'selected'; ?>><?php echo $blood['blood_group']; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-field">
				<label>Nationality</label>
				<input type="text" name="nationality" placeholder="Enter nationality" value="<?php echo $student->nationality; ?>">
			</div>
			<div class="form-field">
				<label>Ghana Card ID</label>
				<input type="text" name="ghana_card_id" placeholder="GHA-XXXXXXXXX-X" value="<?php echo $student->ghana_card_id; ?>">
			</div>
			<div class="form-field">
				<label>Place of Birth</label>
				<input type="text" name="place_of_birth" placeholder="Enter place of birth" value="<?php echo $student->place_of_birth; ?>">
			</div>
			<div class="form-field">
				<label>Hometown</label>
				<input type="text" name="hometown" placeholder="Enter hometown" value="<?php echo $student->hometown; ?>">
			</div>
			<div class="form-field">
				<label>Tribe</label>
				<input type="text" name="tribe" placeholder="Enter tribe" value="<?php echo $student->tribe; ?>">
			</div>
			<div class="form-field">
				<label>Religion</label>
				<select name="religion" id="religion" class="select2">
					<?php
					$religions = $this->db->get('religion')->result_array();
					foreach($religions as $religion):
					?>
					<option value="<?php echo $religion['religion']; ?>" <?php if($student->religion == $religion['religion']) echo 'selected'; ?>><?php echo $religion['religion']; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-field" id="others" style="display:none;">
				<label>Specify Religion</label>
				<input type="text" name="others" placeholder="Please specify...">
			</div>
			<div class="form-field">
				<label>Student Phone</label>
				<input type="tel" name="student_phone" id="phone" placeholder="Enter student phone" value="<?php echo $student->phone; ?>">
			</div>
			<div class="form-field">
				<label>Email Address</label>
				<input type="email" name="email" placeholder="student@example.com" value="<?php echo $student->email; ?>">
			</div>
			<div class="form-field">
				<label>Residential Address</label>
				<input type="text" name="address" id="address" placeholder="Enter address" value="<?php echo $student->address; ?>">
			</div>
			</div>
		</div>

		<!-- PHOTO UPLOAD -->
		<div class="section-card">
			<div class="section-header"><i class="entypo-camera"></i> Student Photo</div>
			<div style="padding: 0 30px;">
			<div class="photo-upload">
				<div class="fileinput fileinput-new" data-provides="fileinput">
					<div class="fileinput-new thumbnail" style="width: 150px; height: 150px; margin: 0 auto;" data-trigger="fileinput">
						<img src="<?php echo $this->crud_model->get_image_url('student', $row['student_id'], $gender); ?>" alt="Student">
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
		</div>

		<!-- ACADEMIC INFORMATION -->
		<div class="section-card">
			<div class="section-header"><i class="entypo-book"></i> Academic Information</div>
			<div class="form-grid">
			<div class="form-field">
				<label>Student ID <span style="color:red">*</span></label>
				<input type="text" name="student_code" value="<?php echo $student->student_code; ?>" readonly>
			</div>
			<div class="form-field">
				<label>Class <span style="color:red">*</span></label>
				<input type="text" value="<?php echo $this->db->get_where('class', array('class_id' => $row['class_id']))->row()->name.' '.$this->db->get_where('class', array('class_id' => $row['class_id']))->row()->name_numeric; ?>" disabled style="background-color: #f3f4f6; cursor: not-allowed;">
				<input type="hidden" name="class_id" value="<?php echo $row['class_id']; ?>">
			</div>
			<div class="form-field">
				<label>Section</label>
				<input type="text" value="<?php echo $this->db->get_where('section', array('section_id' => $row['section_id']))->row()->name; ?>" disabled style="background-color: #f3f4f6; cursor: not-allowed;">
				<input type="hidden" name="section_id" value="<?php echo $row['section_id']; ?>">
			</div>
			<div class="form-field">
				<label>Admission Date</label>
				<div style="position: relative;">
					<input type="text" id="admission_date" datepicker datepicker-format="dd-mm-yyyy" datepicker-autohide name="admission_date" placeholder="DD-MM-YYYY" value="<?php echo $student->admission_date; ?>" style="width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
					<div style="position: absolute; top: 50%; transform: translateY(-50%); left: 10px; pointer-events: none;">
						<svg style="width: 16px; height: 16px; color: #6b7280;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
					</div>
				</div>
			</div>
			<div class="form-field">
				<label>Former School</label>
				<input type="text" name="former_school" placeholder="Enter former school name" value="<?php echo $student->former_school; ?>">
			</div>
			<div class="form-field">
				<label>Class Reached</label>
				<input type="text" name="class_reached" placeholder="Enter class reached" value="<?php echo $student->class_reached; ?>">
			</div>
			<div class="form-field">
				<label>NHIS Number</label>
				<input type="text" name="nhis_number" placeholder="Enter NHIS number" value="<?php echo $student->nhis_number; ?>">
			</div>
			<div class="form-field">
				<label>NHIS Status</label>
				<select name="nhis_status" class="select2">
					<option value="pending" <?php if($student->nhis_status == 'pending') echo 'selected'; ?>>Pending</option>
					<option value="active" <?php if($student->nhis_status == 'active') echo 'selected'; ?>>Active</option>
					<option value="inactive" <?php if($student->nhis_status == 'inactive') echo 'selected'; ?>>Inactive</option>
				</select>
			</div>
			<div class="form-field">
				<label>Disability Status</label>
				<select name="disability_status" class="select2">
					<option value="0" <?php if($student->disability_status == 0) echo 'selected'; ?>>No Disability</option>
					<option value="1" <?php if($student->disability_status == 1) echo 'selected'; ?>>Has Disability</option>
				</select>
			</div>
			<div class="form-field">
				<label>Special Needs</label>
				<textarea name="special_needs" rows="3" placeholder="Describe any special needs..."><?php echo $student->special_needs; ?></textarea>
			</div>
			<div class="form-field">
				<label>Learning Support</label>
				<textarea name="learning_support" rows="3" placeholder="Describe learning support needs..."><?php echo $student->learning_support; ?></textarea>
			</div>
			<div class="form-field">
				<label>Digital Literacy Level</label>
				<select name="digital_literacy" class="select2">
					<option value="beginner" <?php if($student->digital_literacy == 'beginner') echo 'selected'; ?>>Beginner</option>
					<option value="intermediate" <?php if($student->digital_literacy == 'intermediate') echo 'selected'; ?>>Intermediate</option>
					<option value="advanced" <?php if($student->digital_literacy == 'advanced') echo 'selected'; ?>>Advanced</option>
				</select>
			</div>
			<div class="form-field">
				<label>Home Technology Access</label>
				<select name="home_technology_access" class="select2">
					<option value="0" <?php if($student->home_technology_access == 0) echo 'selected'; ?>>No Access</option>
					<option value="1" <?php if($student->home_technology_access == 1) echo 'selected'; ?>>Has Access</option>
				</select>
			</div>
			<div class="form-field">
				<label style="display: flex; align-items: center; gap: 10px;">
					<input type="checkbox" id="special_diet" name="special_diet" value="<?php echo $special_diet; ?>" <?php if($special_diet == 1) echo 'checked'; ?> onchange="special_diet_value_change()" style="width: auto;">
					On Special Diet
				</label>
			</div>
			<div class="form-field" id="special_diet_details_holder" style="display:<?php echo $special_diet == 1 ? 'block' : 'none'; ?>; grid-column: 1/-1;">
				<label>Special Diet Details</label>
				<textarea name="student_special_diet_details" rows="3" placeholder="Provide special diet details..."><?php echo $student->student_special_diet_details; ?></textarea>
			</div>
			</div>
		</div>
		</div>

		<!-- MEDICAL & SPECIAL NEEDS -->
		<div class="section-card">
			<div class="section-header"><i class="entypo-heart"></i> Medical & Special Needs</div>
			<div class="form-grid">
			<div class="form-field">
				<label>Allergies</label>
				<textarea name="allergies" rows="3" placeholder="List any allergies..."><?php echo $student->allergies ?? ''; ?></textarea>
			</div>
			<div class="form-field">
				<label>Medical Conditions</label>
				<textarea name="medical_conditions" rows="3" placeholder="List any medical conditions..."><?php echo $student->medical_conditions ?? ''; ?></textarea>
			</div>
			</div>
		</div>

		<!-- RESIDENCE TYPE & BOARDING -->
		<?php
		$boarding_system = $this->db->get_where('settings', array('type' => 'boarding_system'))->row()->description;
		$residence_type = $student->residence_type ?? 'Day';
		?>
		<div class="section-card">
			<div class="section-header"><i class="entypo-home"></i> Residence Type</div>
			<div class="form-grid">
			<div class="form-field">
				<label>Residence Type <span style="color:red">*</span></label>
				<select name="residence_type" id="residence_type" class="select2">
					<?php if($boarding_system == 'yes'): ?>
						<option value="Day" <?php if($residence_type == 'Day') echo 'selected'; ?>>Day</option>
						<option value="Boarding" <?php if($residence_type == 'Boarding') echo 'selected'; ?>>Boarding</option>
					<?php else: ?>
						<option value="Day">Day</option>
					<?php endif; ?>
				</select>
			</div>
			<?php if($boarding_system == 'yes'): ?>
			<div id="boarding_holder" style="display:<?php echo $residence_type == 'Boarding' ? 'block' : 'none'; ?>;">
				<div class="form-field">
					<label>House</label>
					<select name="house_id" id="house_id" class="select2">
						<option value="">Select House</option>
						<?php
						$allAvailableHouses = $this->boarding_model->getAllAvailableHouses();
						foreach($allAvailableHouses as $house):
						?>
						<option value="<?=$house['house_id'];?>" <?php if(isset($student->house_id) && $student->house_id == $house['house_id']) echo 'selected'; ?>><?=$house['house_name'];?></option>
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
			<?php endif; ?>
			</div>
		</div>
		</div>

		<!-- PARENT/GUARDIAN INFORMATION -->
		<div class="section-card">
			<div class="section-header"><i class="entypo-users"></i> Parent / Guardian Information</div>
		
		<!-- Guardian Selection -->
		<div class="form-grid" style="margin-bottom: 30px;">
			<div class="form-field">
				<label>Primary Guardian <span style="color:red">*</span></label>
				<select name="parent_id" id="parent_id" class="select2" required data-placeholder="Search or select guardian..." style="width: 100%;">
					<option value=""></option>
					<option value="new" data-icon="fa-plus-circle">➕ Register New Guardian</option>
					<?php
					$parents = $this->db->get('parent')->result_array();
					foreach($parents as $p):
						$phone = $p['phone'] ? ' • ' . $p['phone'] : '';
						$profession = $p['profession'] ? ' • ' . $p['profession'] : '';
					?>
					<option value="<?php echo $p['parent_id'];?>" 
						data-name="<?php echo htmlspecialchars($p['name']); ?>" 
						data-phone="<?php echo htmlspecialchars($p['phone']); ?>" 
						data-email="<?php echo htmlspecialchars($p['email']); ?>" 
						data-address="<?php echo htmlspecialchars($p['address']); ?>" 
						data-profession="<?php echo htmlspecialchars($p['profession']); ?>"
						<?php if($p['parent_id'] == $parent_id) echo 'selected'; ?>>
						<?php echo $p['name'] . $phone . $profession;?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-field" id="guardian_type_field" style="display: none;">
				<label>Guardian is the <span style="color:red">*</span></label>
				<select id="guardian_type" class="select2" data-placeholder="Select relationship...">
					<option value=""></option>
					<option value="father" data-icon="fa-male">👨 Father</option>
					<option value="mother" data-icon="fa-female">👩 Mother</option>
					<option value="other" data-icon="fa-user">👤 Other Person</option>
				</select>
			</div>
		</div>
		
		<!-- Father's Information -->
		<div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
			<h4 style="margin: 0 0 20px 0; color: #1e40af; font-size: 16px; font-weight: 700;"><i class="fa fa-male"></i> Father's Information</h4>
			<div class="form-grid">
				<div class="form-field">
					<label>Father's Name</label>
					<input type="text" id="father_name" name="father_name" placeholder="Enter father's name" value="<?php echo $parent->father_name ?? ''; ?>">
				</div>
				<div class="form-field">
					<label>Father's Phone</label>
					<input type="tel" id="father_phone" name="father_phone" placeholder="Enter father's phone" value="<?php echo $parent->father_phone ?? ''; ?>">
				</div>
				<div class="form-field">
					<label>Father's Occupation</label>
					<input type="text" id="father_occupation" name="father_occupation" placeholder="Enter father's occupation" value="<?php echo $parent->father_occupation ?? ''; ?>">
				</div>
			</div>
		</div>
		
		<!-- Mother's Information -->
		<div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
			<h4 style="margin: 0 0 20px 0; color: #92400e; font-size: 16px; font-weight: 700;"><i class="fa fa-female"></i> Mother's Information</h4>
			<div class="form-grid">
				<div class="form-field">
					<label>Mother's Name</label>
					<input type="text" id="mother_name" name="mother_name" placeholder="Enter mother's name" value="<?php echo $parent->mother_name ?? ''; ?>">
				</div>
				<div class="form-field">
					<label>Mother's Phone</label>
					<input type="tel" id="mother_phone" name="mother_phone" placeholder="Enter mother's phone" value="<?php echo $parent->mother_phone ?? ''; ?>">
				</div>
				<div class="form-field">
					<label>Mother's Occupation</label>
					<input type="text" id="mother_occupation" name="mother_occupation" placeholder="Enter mother's occupation" value="<?php echo $parent->mother_occupation ?? ''; ?>">
				</div>
			</div>
		</div>
		
		<!-- Other Guardian Information -->
		<div id="other_guardian_section" style="background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); border-left: 4px solid #a855f7; padding: 20px; border-radius: 12px; margin-bottom: 25px; display: none;">
			<h4 style="margin: 0 0 20px 0; color: #6b21a8; font-size: 16px; font-weight: 700;"><i class="fa fa-user"></i> Guardian Information</h4>
			<div class="form-grid">
				<div class="form-field">
					<label>Guardian Name</label>
					<input type="text" id="guardian_name" readonly placeholder="Select guardian above" style="background: #f9fafb;">
				</div>
				<div class="form-field">
					<label>Guardian Phone</label>
					<input type="tel" id="guardian_phone" name="phone" placeholder="Enter guardian phone" value="<?php echo $parent->phone ?? ''; ?>">
				</div>
				<div class="form-field">
					<label>Guardian Email</label>
					<input type="text" id="guardian_email" name="parent_email" placeholder="guardian@example.com" value="<?php echo $parent->email ?? ''; ?>">
				</div>
				<div class="form-field">
					<label>Guardian Occupation</label>
					<input type="text" id="guardian_occupation" placeholder="Enter occupation">
				</div>
				<div class="form-field">
					<label>Guardian Address</label>
					<input type="text" id="guardian_address" placeholder="Enter address">
				</div>
				<div class="form-field">
					<label>Emergency Contact</label>
					<input type="tel" name="emergency_contact" placeholder="Enter emergency contact" value="<?php echo $student->emergency_contact; ?>">
				</div>
			</div>
			</div>
			</div>
		</div>

		<!-- LOGIN CREDENTIALS -->
		<div class="section-card">
			<div class="section-header"><i class="entypo-lock"></i> Login Credentials</div>
			<div class="form-grid">
			<div class="form-field">
				<label>Username</label>
				<input type="text" name="username" readonly value="<?php echo $student->student_code; ?>">
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
		</div>

		<div style="text-align: center;">
			<button type="submit" class="submit-btn bg-blue-700">
				<i class="entypo-check"></i> Update Student
			</button>
		</div>

		<?php echo form_close(); ?>
	</div>
</div>

<?php endforeach; ?>

<script src="<?php echo base_url(); ?>assets/cdn/js/flowbite.min.js"></script>
<script type="text/javascript">
const formPrefs=JSON.parse(localStorage.getItem('studentFormPrefs')||'{}');['middle_name','blood_group','nationality','ghana_card_id','place_of_birth','hometown','tribe','religion','student_phone','email','address','admission_date','former_school','class_reached','father_name','father_phone','father_occupation','mother_name','mother_phone','mother_occupation','allergies','medical_conditions','nhis_number','nhis_status','disability_status','special_needs','learning_support','digital_literacy','home_technology_access'].forEach(n=>{if(formPrefs[n]===false)$('[name="'+n+'"]').closest('.form-field').hide();});
$.get('<?php echo site_url('admin/get_form_preferences'); ?>',function(d){if(d.status==='success'){const p={};d.preferences.forEach(x=>p[x.field_name]=x.is_visible==1);localStorage.setItem('studentFormPrefs',JSON.stringify(p));Object.keys(p).forEach(n=>{if(p[n]===false)$('[name="'+n+'"]').closest('.form-field').hide();});}}, 'json');
$(document).ready(function() {
	console.log('Document ready fired');
	console.log('jQuery version:', $.fn.jquery);
	console.log('Form exists:', $('#student_single_update_form').length);
	
	$('#others').css('display', 'none');
	let parent_id_onload = $('select[name="parent_id"]').val();
	updateStudent(parent_id_onload);
	

	special_diet_value_change();
	
	$('#parent_id').select2({
		allowClear: true,
		placeholder: 'Search or select guardian...',
		width: '100%'
	});
	
	$('#parent_id').on('select2:open', function() {
		setTimeout(function() {
			var $search = $('.select2-search__field');
			$search.on('input', function() {
				setTimeout(function() {
					var $newOption = $('.select2-results__option[data-select2-id="select2-parent_id-result-new"]');
					if ($newOption.length === 0) {
						$('.select2-results__options').prepend('<li class="select2-results__option" data-select2-id="select2-parent_id-result-new" role="option" aria-selected="false">➕ Register New Guardian</li>');
					}
				}, 10);
			});
		}, 10);
	});
	
	initializeGuardianSelection();
	
	// Auto-save form data
	const formId = 'student_edit_<?php echo $row['student_id']; ?>';
	const savedData = localStorage.getItem(formId);
	if(savedData) {
		const data = JSON.parse(savedData);
		Object.keys(data).forEach(name => {
			const field = $('[name="'+name+'"]');
			if(field.length && !field.is('[readonly]')) {
				if(field.is(':checkbox')) field.prop('checked', data[name]);
				else if(field.is('select')) field.val(data[name]).trigger('change');
				else field.val(data[name]);
			}
		});
	}
	
	let saveTimeout;
	$('#student_single_update_form :input').on('input change', function() {
		clearTimeout(saveTimeout);
		saveTimeout = setTimeout(() => {
			const formData = {};
			$('#student_single_update_form :input:not([type="file"]):not([type="password"]):not([readonly])').each(function() {
				const name = $(this).attr('name');
				if(name) {
					if($(this).is(':checkbox')) formData[name] = $(this).is(':checked');
					else formData[name] = $(this).val();
				}
			});
			localStorage.setItem(formId, JSON.stringify(formData));
		}, 1000);
	});
	
	$('#student_single_update_form').on('submit', function() {
		localStorage.removeItem(formId);
	});
});

$('#religion').change(function() {
	var religion = $('#religion').val();
	if(religion == 'OTHERS') {
		$('#others').css('display', 'block');
	} else {
		$('#others').css('display', 'none');
	}
});

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

$('#residence_type').change(function(e) {
	let selectedVal = $(this).val();
	if(selectedVal == 'Day') {
		$('#boarding_holder').slideUp('slow');
	} else {
		$('#boarding_holder').slideDown('slow');
	}
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

function get_class_sections(class_id) {
	$.ajax({
		url: '<?php echo site_url('admin/get_class_section/'); ?>' + class_id,
		success: function(response) {
			jQuery('#section_selector_holder').html(response);
			// Auto-select first section
			setTimeout(function() {
				if($('#section_selector_holder option').length > 1) {
					$('#section_selector_holder').val($('#section_selector_holder option:eq(1)').val()).trigger('change');
				}
			}, 100);
		}
	});
}

// Form submission handler - Multiple approaches for reliability
$(document).ready(function() {
	// Method 1: Direct form submit handler
	$('#student_single_update_form').on('submit', function(e) {
		console.log('Form submit handler triggered');
		e.preventDefault();
		submitStudentForm(this);
	});
	
});

function submitStudentForm(form) {
	console.log('submitStudentForm called');
	
	// Check if modal functions exist
	if(typeof showAjaxModal_alert !== 'function') {
		console.error('showAjaxModal_alert not found');
		alert('Required functions not loaded. Please refresh the page.');
		return;
	}
	
	// Check for hidden required fields
	let hiddenRequiredFields = [];
	$(form).find('[required]').each(function() {
		let $field = $(this);
		let $container = $field.closest('.form-field');
		if($container.is(':hidden') || $field.is(':hidden')) {
			let label = $container.find('label').text().replace('*', '').trim();
			hiddenRequiredFields.push(label || $field.attr('name'));
		}
	});
	
	if(hiddenRequiredFields.length > 0) {
		let errorMsg = '<div style="text-align: left;"><h4 style="color: #dc3545; margin-bottom: 15px;"><i class="fa fa-exclamation-triangle"></i> Hidden Required Fields</h4>';
		errorMsg += '<p style="color: #721c24; margin: 10px 0;">The following required fields are hidden and need to be completed:</p>';
		errorMsg += '<ul style="margin: 10px 0; padding-left: 20px;">';
		hiddenRequiredFields.forEach(function(field) {
			errorMsg += '<li style="margin: 8px 0; color: #721c24;"><strong>' + field + '</strong></li>';
		});
		errorMsg += '</ul>';
		errorMsg += '<p style="margin-top: 15px; font-size: 12px; color: #6c757d;"><i class="fa fa-info-circle"></i> Please ensure all required information is provided.</p></div>';
		showAjaxModal_alert(errorMsg, 'error');
		return;
	}
	
	// Close any open modals
	try {
		$('.close')[0].click();
	} catch(e) {
		console.log('No close button found');
	}
	
	showAjaxModal_alert('Updating student...', 'loading');

	$.ajax({
		url: '<?php echo site_url('admin/student/do_update/'.$row['student_id'].'/'.$row['class_id']); ?>',
		type: 'POST',
		dataType: 'json',
		data: new FormData(form),
		cache: false,
		contentType: false,
		processData: false
	})
	.done(function(response) {
		console.log('AJAX response:', response);
		if(response.status === 'success' || response.done === 'success') {
			showAjaxModal_alert(response.message || 'Student updated successfully', 'success');
			setTimeout(() => location.reload(), 2000);
		} else {
			// Handle validation errors with detailed modal
			let errorMsg = '<div style="text-align: left;"><h4 style="color: #dc3545; margin-bottom: 15px;"><i class="fa fa-exclamation-triangle"></i> Validation Errors</h4>';
			
			if(response.errors) {
				errorMsg += '<ul style="margin: 10px 0; padding-left: 20px;">';
				$.each(response.errors, function(field, error) {
					errorMsg += '<li style="margin: 8px 0; color: #721c24;"><strong>' + field.replace('_', ' ').toUpperCase() + ':</strong> ' + error + '</li>';
				});
				errorMsg += '</ul>';
			} else if(response.message) {
				errorMsg += '<p style="color: #721c24; margin: 10px 0;">' + response.message + '</p>';
			} else {
				// Legacy format - check for validation errors
				let hasErrors = false;
				errorMsg += '<ul style="margin: 10px 0; padding-left: 20px;">';
				$.each(response, function(field, error) {
					if(field !== 'done' && field !== 'status' && error && error.trim() !== '') {
						hasErrors = true;
						let fieldName = field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
						errorMsg += '<li style="margin: 8px 0; color: #721c24;"><strong>' + fieldName + ':</strong> ' + error + '</li>';
					}
				});
				errorMsg += '</ul>';
				
				if(!hasErrors) {
					errorMsg += '<p style="color: #721c24; margin: 10px 0;">Please check all required fields and try again.</p>';
				}
			}
			
			errorMsg += '<p style="margin-top: 15px; font-size: 12px; color: #6c757d;"><i class="fa fa-info-circle"></i> Please correct the above errors and submit again.</p></div>';
			showAjaxModal_alert(errorMsg, 'error');
		}
	})
	.fail(function(xhr, status, error) {
		console.error('AJAX error:', xhr, status, error);
		let errorMsg = 'An error occurred while updating the student.';
		if(xhr.responseText) {
			try {
				let response = JSON.parse(xhr.responseText);
				errorMsg = response.message || errorMsg;
			} catch(e) {
				errorMsg += ' Please check your connection and try again.';
			}
		}
		showAjaxModal_alert(errorMsg, 'error');
	});
}



function updateStudent(parent_id) {
	console.log('Hello what '+ typeof(parent_id) + ' ' + parent_id);
	if(parent_id == 'new' || parent_id == null || parent_id == undefined || parent_id == '' || parent_id == false || isEmpty(parent_id)) {
		return;
	}

	let ajaxUrl = '<?php echo site_url('admin/updateStudentEdit/'); ?>' + parent_id;
	$.ajax({
		url: ajaxUrl,
		type: 'post',
		dataType: 'json',
		cache: false
	})
	.done(function(response) {
		$('#phone').val(response.phone);
		$('#address').val(response.address);
	})
	.fail(function(err) {
		showAjaxModal_alert('Unable to update student: ' + err.responseText, 'error');
	});
}

// Store original data on page load
var originalData = {
	father: {
		name: $('#father_name').val(),
		phone: $('#father_phone').val(),
		occupation: $('#father_occupation').val()
	},
	mother: {
		name: $('#mother_name').val(),
		phone: $('#mother_phone').val(),
		occupation: $('#mother_occupation').val()
	}
};

// Initialize guardian selection based on existing data
function initializeGuardianSelection() {
	var selectedParentId = $('#parent_id').val();
	if(selectedParentId) {
		$('#guardian_type_field').show();
		
		// Compare guardian data with father and mother data to determine default selection
		var selectedOption = $('#parent_id').find('option:selected');
		var guardianData = {
			name: selectedOption.data('name'),
			phone: selectedOption.data('phone'),
			profession: selectedOption.data('profession')
		};
		
		var fatherName = $('#father_name').val();
		var fatherPhone = $('#father_phone').val();
		var motherName = $('#mother_name').val();
		var motherPhone = $('#mother_phone').val();
		
		// Check if guardian matches father
		if(guardianData.name === fatherName || guardianData.phone === fatherPhone) {
			$('#guardian_type').val('father').trigger('change');
		}
		// Check if guardian matches mother
		else if(guardianData.name === motherName || guardianData.phone === motherPhone) {
			$('#guardian_type').val('mother').trigger('change');
		}
		// Default to other
		else {
			$('#guardian_type').val('other').trigger('change');
		}
	}
}

// Handle parent selection change
$('#parent_id').change(function() {
	var parent_id = $(this).val();
	
	if(parent_id == 'new') {
		showAjaxModal('<?php echo site_url('modal/popup/modal_parent_add_st/');?>');
		$('#guardian_type_field').hide();
		$('#other_guardian_section').hide();
		return;
	}
	
	if(parent_id) {
		$('#guardian_type_field').slideDown();
		$('#guardian_type').prop('required', true);
	} else {
		$('#guardian_type_field').hide();
		$('#guardian_type').prop('required', false).val('');
		$('#other_guardian_section').hide();
		clearAllParentFields();
	}
});

// Handle guardian type change
$('#guardian_type').change(function() {
	var guardianType = $(this).val();
	var previousType = $(this).data('previous-type');
	var selectedOption = $('#parent_id').find('option:selected');
	var guardianData = {
		name: selectedOption.data('name'),
		phone: selectedOption.data('phone'),
		email: selectedOption.data('email'),
		address: selectedOption.data('address'),
		profession: selectedOption.data('profession')
	};
	
	// Reset previous type to original data
	if(previousType === 'father') {
		$('#father_name').val(originalData.father.name);
		$('#father_phone').val(originalData.father.phone);
		$('#father_occupation').val(originalData.father.occupation);
	} else if(previousType === 'mother') {
		$('#mother_name').val(originalData.mother.name);
		$('#mother_phone').val(originalData.mother.phone);
		$('#mother_occupation').val(originalData.mother.occupation);
	}
	
	if(!guardianType) {
		$('#other_guardian_section').hide();
		$(this).data('previous-type', '');
		return;
	}
	
	if(guardianType === 'father') {
		// Copy guardian data to father's fields
		if(guardianData.name) {
			$('#father_name').val(guardianData.name || '');
			$('#father_phone').val(guardianData.phone || '');
			$('#father_occupation').val(guardianData.profession || '');
		} else {
			// New guardian - populate father fields with guardian name
			var selectedOption = $('#parent_id').find('option:selected');
			$('#father_name').val(selectedOption.data('name') || selectedOption.text());
		}
		$('#other_guardian_section').slideUp();
	} else if(guardianType === 'mother') {
		// Copy guardian data to mother's fields
		if(guardianData.name) {
			$('#mother_name').val(guardianData.name || '');
			$('#mother_phone').val(guardianData.phone || '');
			$('#mother_occupation').val(guardianData.profession || '');
		} else {
			// New guardian - populate mother fields with guardian name
			var selectedOption = $('#parent_id').find('option:selected');
			$('#mother_name').val(selectedOption.data('name') || selectedOption.text());
		}
		$('#other_guardian_section').slideUp();
	} else if(guardianType === 'other') {
		// Show and populate other guardian section
		$('#guardian_name').val(guardianData.name || '');
		$('#guardian_phone').val(guardianData.phone || '');
		$('#guardian_email').val(guardianData.email || '');
		$('#guardian_address').val(guardianData.address || '');
		$('#guardian_occupation').val(guardianData.profession || '');
		$('#other_guardian_section').slideDown();
	}
	
	// Store current type as previous for next change
	$(this).data('previous-type', guardianType);
});

// Handle keyup events to update guardian data when user types
$('#father_name, #father_phone, #father_occupation').on('keyup change', function() {
	if($('#guardian_type').val() === 'father') {
		updateGuardianFromFields('father');
	}
});

$('#mother_name, #mother_phone, #mother_occupation').on('keyup change', function() {
	if($('#guardian_type').val() === 'mother') {
		updateGuardianFromFields('mother');
	}
});

$('#guardian_phone, #guardian_email, #guardian_address, #guardian_occupation').on('keyup change', function() {
	if($('#guardian_type').val() === 'other') {
		updateGuardianFromFields('other');
	}
});

function updateGuardianFromFields(type) {
	var selectedOption = $('#parent_id').find('option:selected');
	
	if(type === 'father') {
		// Update guardian data from father's fields
		selectedOption.data('name', $('#father_name').val());
		selectedOption.data('phone', $('#father_phone').val());
		selectedOption.data('profession', $('#father_occupation').val());
	} else if(type === 'mother') {
		// Update guardian data from mother's fields
		selectedOption.data('name', $('#mother_name').val());
		selectedOption.data('phone', $('#mother_phone').val());
		selectedOption.data('profession', $('#mother_occupation').val());
	} else if(type === 'other') {
		// Update guardian data from other guardian fields
		selectedOption.data('name', $('#guardian_name').val());
		selectedOption.data('phone', $('#guardian_phone').val());
		selectedOption.data('email', $('#guardian_email').val());
		selectedOption.data('address', $('#guardian_address').val());
		selectedOption.data('profession', $('#guardian_occupation').val());
	}
}

function clearAllParentFields() {
	$('#father_name, #father_phone, #father_occupation').val('');
	$('#mother_name, #mother_phone, #mother_occupation').val('');
	$('#guardian_name, #guardian_phone, #guardian_email, #guardian_address, #guardian_occupation').val('');
}
</script>
