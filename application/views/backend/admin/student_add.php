<?php 
$parent_id = ''; 
$class_id = ''; 
$alert_type = '';
$alert_content = '';
$display_mode = $display = 'none';
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
?>


<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
html, body { margin: 0; padding: 0; overflow-x: hidden; }
body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
.admission-container { max-width: 1400px; margin: 20px auto; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; width: 100%; box-sizing: border-box; }
@media (max-width: 640px) { .admission-container { margin: 5px; border-radius: 10px; width: calc(100% - 10px); } }
.admission-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center; position: relative; overflow: hidden; }
.admission-header::before { content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%; }
.admission-header::after { content: ''; position: absolute; bottom: -30%; left: -5%; width: 200px; height: 200px; background: rgba(255,255,255,0.08); border-radius: 50%; }
@media (max-width: 640px) { .admission-header { padding: 60px 15px 30px; } }
.admission-header h2 { margin: 0; font-size: 36px; font-weight: 800; color: white; position: relative; z-index: 1; letter-spacing: -0.5px; }
@media (max-width: 640px) { .admission-header h2 { font-size: 26px; } }
.admission-header p { margin: 12px 0 0; opacity: 0.95; color: white; font-size: 16px; position: relative; z-index: 1; font-weight: 500; }
@media (max-width: 640px) { .admission-header p { font-size: 14px; } }
.form-content { padding: 0 40px; box-sizing: border-box; background: #e8e8e8; }
@media (max-width: 640px) { .form-content { padding: 0 10px; } }
.section-card { background: white; border-radius: 12px; padding: 25px; margin: 25px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
@media (max-width: 640px) { .section-card { padding: 15px; margin: 15px 0; border-radius: 10px; } }
.section-header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 18px 24px; margin: 0 0 25px 0; border-radius: 12px; font-weight: 700; font-size: 18px; box-shadow: 0 4px 20px rgba(79, 172, 254, 0.3); display: flex; align-items: center; gap: 12px; transition: all 0.3s; }
.section-header:hover { transform: translateX(5px); box-shadow: 0 6px 25px rgba(79, 172, 254, 0.4); }
.section-header i { font-size: 22px; }
@media (max-width: 640px) { .section-header { padding: 15px 18px; margin: 25px 0 20px; font-size: 16px; } .section-header i { font-size: 20px; } }
.form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; gap: 18px; } }
@media (min-width: 641px) and (max-width: 1024px) { .form-grid { grid-template-columns: repeat(2, 1fr); } }
.form-field { margin-bottom: 0; }
.form-field label { display: block; margin-bottom: 10px; font-weight: 600; color: #1f2937; font-size: 14px; letter-spacing: 0.2px; }
.form-field input, .form-field select, .form-field textarea { width: 100%; padding: 13px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; transition: all 0.3s; box-sizing: border-box; background: #ffffff; color: #1f2937; font-weight: 500; }
.form-field input:hover, .form-field select:hover, .form-field textarea:hover { border-color: #d1d5db; }
.form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); background: #fafbff; }
.form-field input::placeholder, .form-field textarea::placeholder { color: #9ca3af; font-weight: 400; }
.submit-btn { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff !important; padding: 16px 48px; border: none; border-radius: 12px; font-size: 18px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3); transition: all 0.3s; margin: 40px 0; letter-spacing: 0.3px; }
@media (max-width: 640px) { .submit-btn { padding: 14px 36px; font-size: 16px; margin: 30px 0; width: 100%; } }
.submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4); background: linear-gradient(135deg, #059669 0%, #047857 100%); }
.submit-btn:active { transform: translateY(-1px); }
.submit-btn i { color: #ffffff !important; margin-right: 8px; }
.alert-note { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 5px solid #f59e0b; padding: 24px; margin: 25px 0; border-radius: 12px; box-sizing: border-box; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.15); }
@media (max-width: 640px) { .alert-note { padding: 12px; margin: 15px 0; font-size: 13px; border-radius: 8px; } }
.alert-note strong { color: #92400e; font-weight: 700; }
.photo-upload { text-align: center; padding: 30px; border: 3px dashed #d1d5db; border-radius: 12px; background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); transition: all 0.3s; }
.photo-upload:hover { border-color: #9ca3af; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); }
@media (max-width: 640px) { .photo-upload { padding: 20px; } }

/* Bill Preview Section */
.bill-preview-container { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 16px; padding: 30px; margin: 30px 0; box-shadow: 0 8px 25px rgba(14, 165, 233, 0.15); display: block; }
@media (max-width: 640px) { .bill-preview-container { padding: 12px; margin: 15px 0; border-radius: 10px; } }
.residence-billing-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
@media (max-width: 768px) { .residence-billing-grid { grid-template-columns: 1fr; gap: 20px; padding: 0; } }
.bill-preview-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 3px solid #0ea5e9; }
.bill-preview-header h3 { margin: 0; font-size: 24px; font-weight: 800; color: #0c4a6e; display: flex; align-items: center; gap: 12px; }
.bill-preview-header h3 i { color: #0ea5e9; font-size: 28px; }
@media (max-width: 640px) { .bill-preview-header h3 { font-size: 20px; } .bill-preview-header h3 i { font-size: 24px; } }
.bill-items-list { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.bill-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid #e5e7eb; transition: all 0.2s; flex-wrap: wrap; }
@media (max-width: 640px) { .bill-item { padding: 12px 0; flex-direction: column; align-items: flex-start; gap: 8px; } }
.bill-item:last-child { border-bottom: none; }
.bill-item:hover { background: #f9fafb; margin: 0 -10px; padding: 16px 10px; border-radius: 8px; }
@media (max-width: 640px) { .bill-item:hover { margin: 0 -5px; padding: 12px 5px; } }
.bill-item-name { font-weight: 600; color: #374151; font-size: 15px; display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; word-break: break-word; }
@media (max-width: 640px) { .bill-item-name { font-size: 14px; } }
.bill-item-name i { color: #6b7280; font-size: 16px; flex-shrink: 0; }
.bill-item-amount { font-weight: 700; color: #0ea5e9; font-size: 16px; white-space: nowrap; margin-left: 10px; }
@media (max-width: 640px) { .bill-item-amount { font-size: 15px; margin-left: 0; width: 100%; text-align: right; } }

.remove-bill-item, .remove-admission-fee { transition: all 0.3s; }
.remove-bill-item:hover, .remove-admission-fee:hover { transform: scale(1.2); opacity: 0.7; }
.bill-total { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border-radius: 12px; margin-top: 20px; box-shadow: 0 6px 20px rgba(14, 165, 233, 0.3); }
@media (max-width: 640px) { .bill-total { flex-direction: column; align-items: flex-end; gap: 8px; padding: 16px; } }
.bill-total-label { font-size: 16px; font-weight: 700; color: white; letter-spacing: 0.3px; }
.bill-total-amount { font-size: 20px; font-weight: 800; color: white; }
@media (max-width: 640px) { .bill-total-label { font-size: 13px; } .bill-total-amount { font-size: 16px; } }
.bill-empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
.bill-empty-state i { font-size: 48px; color: #d1d5db; margin-bottom: 12px; display: block; }
.bill-empty-state p { font-size: 14px; font-weight: 500; margin: 0; }

/* Modern Select2 Styling */
.select2-container--default .select2-selection--single { height: 48px; border: 2px solid #e5e7eb; border-radius: 10px; padding: 8px 12px; transition: all 0.3s ease; }
.select2-container--default .select2-selection--single:hover { border-color: #3b82f6; }
.select2-container--default.select2-container--focus .select2-selection--single { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 30px; color: #111827; font-size: 15px; font-weight: 600; padding-left: 8px; }
.select2-container--default .select2-selection--single .select2-selection__placeholder { color: #9ca3af; font-weight: 500; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
.select2-dropdown { border: 2px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-top: 4px; }
.select2-container--default .select2-results__option { padding: 14px 18px; font-size: 15px; font-weight: 600; color: #111827; transition: all 0.2s ease; border-bottom: 1px solid #f3f4f6; }
.select2-container--default .select2-results__option:last-child { border-bottom: none; }
.select2-container--default .select2-results__option--highlighted { background-color: #eff6ff; color: #1e40af; }
.select2-container--default .select2-results__option[aria-selected=true] { background-color: #dbeafe; color: #1e40af; font-weight: 700; }
.select2-results__option[data-select2-id*="new"] { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: white !important; font-weight: 700 !important; border-bottom: 2px solid #047857 !important; }
.select2-results__option[data-select2-id*="new"]:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%) !important; }
.select2-search--dropdown { padding: 12px; background: #f9fafb; border-bottom: 2px solid #e5e7eb; }
.select2-search--dropdown .select2-search__field { border: 2px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; font-size: 14px; font-weight: 500; }
.select2-search--dropdown .select2-search__field:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
</style>

<div class="admission-container">
	<div class="admission-header">
		<h2><i class="entypo-graduation-cap"></i> Student Admission Form</h2>
		<p>Complete all required fields to admit a new student</p>
		<button type="button" onclick="openFormCustomizer()" class="customize-form-btn" style="position: absolute; top: 20px; right: 20px; background: white; color: #667eea; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 8px; z-index: 100; transition: all 0.3s;">
			<i class="fa fa-sliders" style="pointer-events: none;"></i>
			<span class="btn-text" style="pointer-events: none;">Customize Form</span>
		</button>
	</div>


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
	<div class="section-card">
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
				<input type="text" id="birthday" class="datepicker" name="birthday" placeholder="DD-MM-YYYY" required value="<?php echo set_value('birthday'); ?>" style="width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;" autocomplete="off">
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
			<label>Place of Birth</label>
			<input type="text" name="place_of_birth" placeholder="Enter place of birth" value="<?php echo set_value('place_of_birth'); ?>">
		</div>
		<div class="form-field">
			<label>Hometown</label>
			<input type="text" name="hometown" placeholder="Enter hometown" value="<?php echo set_value('hometown'); ?>">
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
	</div>

	<!-- PHOTO UPLOAD -->
	<div class="section-card">
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
	</div>

	<!-- ACADEMIC INFORMATION -->
	<div class="section-card">
	<div class="section-header"><i class="entypo-book"></i> Academic Information</div>
	<div class="form-grid">
		<div class="form-field">
			<label>Student ID <span style="color:red">*</span></label>
			<?php 
			//generate student id 
			$student_code_pref       = $this->db->get_where('settings', array('type'=>'student_code_prefix'))->row()->description;

			$student_code_f       = $this->db->get_where('settings', array('type'=>'student_code_format'))->row()->description;
			$id_length = strlen($student_code_pref.$student_code_f);

			$this->db->select('student_code');
			$this->db->order_by('student_code', 'desc');
			$this->db->limit(1);
			$st_query = $this->db->get('student');
			$st_id    = $st_query->row()->student_code;

			if($st_query->row()->student_code != '' || $st_query->row()->student_code != null) {
				if($st_query->num_rows() > 0) {
			

					//extract the numeric out and increase it by 1
					$first_num = ''; //the first occurence of a number in the string ....
					$position_of_first_num = ''; //the position of the first number

					$i = 0;
					for($i=0; $i<strlen($st_id); $i++) {
						if(is_numeric($st_id[$i])) {
							$first_num = $st_id[$i];
							break; 
						}
					}

					//find the position
					$position_of_first_num = strpos($st_id, $first_num);

					//now let's do the extraction
					$n_stid = substr($st_id, $position_of_first_num, strlen($st_id) - $i);

					$student_code       = $n_stid + 1;

					//checking if the first 4digits of the id equals the current year, if not, we create a new ID using the current year format
					
					if(intval(date('Y')) != intval(substr($student_code, 0, 4))) {
						//new year so the student ID format has to change
						//e.g in 2021, it will start with 2021001
						//while in 2022, it will start with 2022001 etc
						$student_code = $student_code_pref . $student_code_f;
					}	else {
						//we are in same year this student ID was generated
						if($first_num == 0) {
							$old_len = strlen($st_id);
							$new_len = strlen($student_code);
							$act_len = ($old_len - $new_len);
							$student_code =  substr($st_id, 0, $act_len).$student_code;
						}else {
							$student_code = $student_code_pref.$student_code;
						}	

					}		                        

				}	else{
					$student_code       = $student_code_pref . $student_code_f;
				}
		} else {
			$student_code       = $student_code_pref . $student_code_f;
		}
			?>
			<input type="text" id="id_field" name="student_code" readonly value="<?php echo $student_code; ?>" required>
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
			<select name="section_id" id="section_selector_holder" class="" required>
				<option value="">Select Class First</option>
			</select>
		</div>
		<div class="form-field">
			<label>Admission Date</label>
			<div style="position: relative;">
				<input type="text" id="admission_date" datepicker datepicker-format="dd-mm-yyyy" datepicker-autohide name="admission_date" placeholder="DD-MM-YYYY" value="<?php echo set_value('admission_date', date('d-m-Y')); ?>" style="width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
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
		</div>
	</div>

	<!-- PARENT/GUARDIAN INFORMATION -->
	<div class="section-card">
	<div class="section-header"><i class="entypo-users"></i> Parent / Guardian Information</div>
	
	<!-- Guardian Selection -->
	<div class="form-grid" style="margin-bottom: 30px;">
		<div class="form-field">
			<label>Primary Guardian <span style="color:red">*</span></label>
			<select name="parent_id" id="parent_id" class="select2" required data-placeholder="Search or select guardian...">
				<option value=""></option>
				<option value="new" data-icon="fa-plus-circle">➕ Register New Guardian</option>
				<?php
				$parents = $this->db->get('parent')->result_array();
				foreach($parents as $row):
					$phone = $row['phone'] ? ' • ' . $row['phone'] : '';
					$profession = $row['profession'] ? ' • ' . $row['profession'] : '';
				?>
				<option value="<?php echo $row['parent_id'];?>" 
					data-name="<?php echo htmlspecialchars($row['name']); ?>" 
					data-phone="<?php echo htmlspecialchars($row['phone']); ?>" 
					data-email="<?php echo htmlspecialchars($row['email']); ?>" 
					data-address="<?php echo htmlspecialchars($row['address']); ?>" 
					data-profession="<?php echo htmlspecialchars($row['profession']); ?>">
					<?php echo $row['name'] . $phone . $profession;?>
				</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-field" id="guardian_type_field" style="display: none;">
			<label>Guardian is the <span style="color:red">*</span></label>
			<select name="guardian_is_the" id="guardian_type" class="select2" data-placeholder="Select relationship...">
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
				<input type="text" id="father_name" name="father_name" placeholder="Enter father's name" value="<?php echo set_value('father_name'); ?>">
			</div>
			<div class="form-field">
				<label>Father's Phone</label>
				<input type="tel" id="father_phone" name="father_phone" placeholder="Enter father's phone" value="<?php echo set_value('father_phone'); ?>">
			</div>
			<div class="form-field">
				<label>Father's Occupation</label>
				<input type="text" id="father_occupation" name="father_occupation" placeholder="Enter father's occupation" value="<?php echo set_value('father_occupation'); ?>">
			</div>
		</div>
	</div>
	
	<!-- Mother's Information -->
	<div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
		<h4 style="margin: 0 0 20px 0; color: #92400e; font-size: 16px; font-weight: 700;"><i class="fa fa-female"></i> Mother's Information</h4>
		<div class="form-grid">
			<div class="form-field">
				<label>Mother's Name</label>
				<input type="text" id="mother_name" name="mother_name" placeholder="Enter mother's name" value="<?php echo set_value('mother_name'); ?>">
			</div>
			<div class="form-field">
				<label>Mother's Phone</label>
				<input type="tel" id="mother_phone" name="mother_phone" placeholder="Enter mother's phone" value="<?php echo set_value('mother_phone'); ?>">
			</div>
			<div class="form-field">
				<label>Mother's Occupation</label>
				<input type="text" id="mother_occupation" name="mother_occupation" placeholder="Enter mother's occupation" value="<?php echo set_value('mother_occupation'); ?>">
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
				<input type="tel" id="guardian_phone" name="phone" placeholder="Enter guardian phone" value="<?php echo set_value('phone'); ?>">
			</div>
			<div class="form-field">
				<label>Guardian Email</label>
				<input type="email" id="guardian_email" name="parent_email" placeholder="guardian@example.com" value="<?php echo set_value('parent_email'); ?>">
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
				<input type="tel" name="emergency_contact" placeholder="Enter emergency contact" value="<?php echo set_value('emergency_contact'); ?>">
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
			<textarea name="allergies" rows="3" placeholder="List any allergies..."><?php echo set_value('allergies'); ?></textarea>
		</div>
		<div class="form-field">
			<label>Medical Conditions</label>
			<textarea name="medical_conditions" rows="3" placeholder="List any medical conditions..."><?php echo set_value('medical_conditions'); ?></textarea>
		</div>
		<div class="form-field">
			<label>NHIS Number</label>
			<input type="text" name="nhis_number" placeholder="Enter NHIS number" value="<?php echo set_value('nhis_number'); ?>">
		</div>
		<div class="form-field">
			<label>NHIS Status</label>
			<select name="nhis_status" class="select2">
				<option value="pending">Pending</option>
				<option value="active">Active</option>
				<option value="inactive">Inactive</option>
			</select>
		</div>
		<div class="form-field">
			<label>Disability Status</label>
			<select name="disability_status" class="select2">
				<option value="0">No Disability</option>
				<option value="1">Has Disability</option>
			</select>
		</div>
		<div class="form-field">
			<label>Special Needs</label>
			<textarea name="special_needs" rows="3" placeholder="Describe any special needs..."><?php echo set_value('special_needs'); ?></textarea>
		</div>
		<div class="form-field">
			<label>Learning Support</label>
			<textarea name="learning_support" rows="3" placeholder="Describe learning support needs..."><?php echo set_value('learning_support'); ?></textarea>
		</div>
		<div class="form-field">
			<label>Digital Literacy Level</label>
			<select name="digital_literacy" class="select2">
				<option value="beginner">Beginner</option>
				<option value="intermediate">Intermediate</option>
				<option value="advanced">Advanced</option>
			</select>
		</div>
		<div class="form-field">
			<label>Home Technology Access</label>
			<select name="home_technology_access" class="select2">
				<option value="0">No Access</option>
				<option value="1">Has Access</option>
			</select>
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
	</div>

	<!-- RESIDENCE TYPE -->
	<div class="section-card">
	<div class="section-header"><i class="entypo-home"></i> Residence Type & Billing</div>
	<style>
		.residence-billing-grid {
			display: grid;
			grid-template-columns: 1fr;
			gap: 30px;
		}
		@media (min-width: 768px) {
			.residence-billing-grid {
				grid-template-columns: 1fr 2fr;
			}
		}
	</style>
	<div class="residence-billing-grid">
		<!-- Left Column: Selections -->
		<div>
			<div class="form-field" style="margin-bottom: 20px;">
				<label>Residence Type <span style="color:red">*</span></label>
				<select name="residence_type" id="residence_type" class="select2">
					<?php if($boarding_system == 'yes'): ?>
						<option value="Day">Day</option>
						<option value="Boarding">Boarding</option>
					<?php else: ?>
						<option value="Day">Day</option>
					<?php endif; ?>
				</select>
			</div>
			<?php if($boarding_system == 'yes'): ?>
			<div id="boarding_holder" style="display:none;">
				<div class="form-field" style="margin-bottom: 20px;">
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
				<div class="form-field" style="margin-bottom: 20px;">
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
		
		<!-- Right Column: Billing Preview -->
		<div>
			<div class="bill-preview-container" id="bill_preview_section" style="margin: 0; padding: 20px;">
				<div class="bill-preview-header" style="margin-bottom: 15px; padding-bottom: 15px;">
					<h3 style="font-size: 18px;"><i class="entypo-doc-text"></i> Admission Bill Preview</h3>
				</div>
				<div class="bill-items-list" id="bill_items_container" style="padding: 15px;">
					<div class="bill-empty-state">
						<i class="entypo-info"></i>
						<p>Select class and residence type to view admission bills</p>
					</div>
				</div>
				
				<!-- Discount Profile Selector -->
				<div id="discount_selector_section" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #0ea5e9;">
					<label style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-weight: 700; color: #0c4a6e; font-size: 15px;">
						<i class="fa fa-tag" style="color: #0ea5e9;"></i>
						Apply Discount Profiles (Optional)
					</label>
					
					<!-- Invoice Discount Profile -->
					<div style="margin-bottom: 15px;">
						<label style="font-size: 13px; color: #64748b; margin-bottom: 5px; display: block;">
							<i class="fa fa-file-invoice" style="color: #3b82f6;"></i> Invoice Discount
						</label>
						<select name="invoice_discount_profile" id="invoice_discount_profile" class="select2" style="width: 100%; height: 50px; border: 2px solid #3b82f6; border-radius: 10px; padding: 0 15px; font-size: 14px; font-weight: 600; color: #0c4a6e; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);">
							<option value="">No Invoice Discount</option>
							<?php
							$invoice_profiles = $this->db->where('is_active', 1)->where('discount_category', 'invoice')->get('discount_profiles')->result_array();
							foreach($invoice_profiles as $profile):
								$method_display = $profile['discount_method'] === 'percentage' ? $profile['discount_value'] . '%' : $currency . ' ' . number_format($profile['discount_value'], 2);
							?>
							<option value="<?php echo $profile['profile_id']; ?>" 
									data-method="<?php echo $profile['discount_method']; ?>" 
									data-value="<?php echo $profile['discount_value']; ?>">
								<?php echo $profile['profile_name'] . ' (' . $method_display . ')'; ?>
							</option>
							<?php endforeach; ?>
						</select>
						<div id="invoice_profile_preview" style="display: none; background: white; border-radius: 10px; padding: 15px; margin-top: 10px; border: 2px solid #3b82f6; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1);"></div>
					</div>
					
					<!-- Daily Fees Discount Profile -->
					<div>
						<label style="font-size: 13px; color: #64748b; margin-bottom: 5px; display: block;">
							<i class="fa fa-calendar-day" style="color: #10b981;"></i> Daily Fees Discount
						</label>
						<select name="daily_fees_discount_profile" id="daily_fees_discount_profile" class="select2" style="width: 100%; height: 50px; border: 2px solid #10b981; border-radius: 10px; padding: 0 15px; font-size: 14px; font-weight: 600; color: #0c4a6e; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
							<option value="">No Daily Fees Discount</option>
							<?php
							$daily_profiles = $this->db->where('is_active', 1)->where('discount_category', 'daily_fees')->get('discount_profiles')->result_array();
							foreach($daily_profiles as $profile):
								$method_display = $profile['discount_method'] === 'percentage' ? $profile['discount_value'] . '%' : $currency . ' ' . number_format($profile['discount_value'], 2);
							?>
							<option value="<?php echo $profile['profile_id']; ?>" 
									data-method="<?php echo $profile['discount_method']; ?>" 
									data-value="<?php echo $profile['discount_value']; ?>">
								<?php echo $profile['profile_name'] . ' (' . $method_display . ')'; ?>
							</option>
							<?php endforeach; ?>
						</select>
						<div id="daily_profile_preview" style="display: none; background: white; border-radius: 10px; padding: 15px; margin-top: 10px; border: 2px solid #10b981; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);"></div>
					</div>
				</div>
				</div>
			</div>
		</div>
	</div>
	
	<input type="hidden" name="my_admission_fee" id="my_admission_fee" value="<?=$this->boarding_model->getAdmissionFeeByResidentialStatus('Day');?>">
	<input type="hidden" name="item_id" id="item_id" value="9">

	<!-- LOGIN CREDENTIALS -->
	<div class="section-card">
	<div class="section-header"><i class="entypo-lock"></i> Login Credentials</div>
	<div class="form-grid">
		<div class="form-field">
			<label>Username</label>
			<input type="text" name="username" readonly value="<?php echo $student_code; ?>">
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

	<div style="position: fixed; bottom: 20px; right: 20px; z-index: 999; display: flex; gap: 15px;">
		<button type="button" onclick="resetForm()" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 15px 30px; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(239,68,68,0.3); transition: all 0.3s;">
			<i class="entypo-ccw"></i> Reset Form
		</button>
		<button type="submit" class="submit-btn bg-blue-600" style="box-shadow: 0 4px 12px rgba(0,0,0,0.3); padding: 15px 30px; font-size: 16px; margin: 0;">
			<i class="entypo-check"></i> Admit Student
		</button>
	</div>

	<?php echo form_close(); ?>
	</div>
</div>



<script>
var CURRENCY = '<?php echo $currency; ?>';
function resetForm() {
	showConfirmModal('Reset Form', 'Are you sure you want to reset the form? All unsaved data will be lost.', function() {
		localStorage.removeItem('student_add_form');
		location.reload();
	}, 'Reset', 'danger');
}
$(document).ready(function() {
	$('#others').css('display', 'none');
	barcode();
	
	// Initialize Select2 with search functionality for parent dropdown
	$('#parent_id').select2({
		allowClear: true,
		placeholder: 'Search or select guardian...',
		width: '100%'
	});
	
	// Ensure "Register New Guardian" always appears in search results
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
});

function updateImagePlaceholder(val) {
	if(!val) val = 'male';
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
	if(!class_id) {
		$('#section_selector_holder').html('<option value="">Select class first</option>');
		return;
	}
	$.ajax({
		url: '<?php echo site_url('admin/get_class_section/');?>' + class_id,
		success: function(response) {
			jQuery('#section_selector_holder').html(response);
			$('#section_selector_holder')[0].setCustomValidity('');
		},
		error: function() {
			$('#section_selector_holder').html('<option value="">Error loading sections</option>');
		}
	});
}

$('#parent_id').change(function(event) {
	var parent_id = $(this).val();
	
	if(parent_id == 'new') {
		showAjaxModal('<?php echo site_url('modal/popup/modal_parent_add_st/');?>');
		$('#guardian_type_field').hide();
		$('#other_guardian_section').hide();
		return;
	}
	
	if(parent_id) {
		// Show guardian type selector
		$('#guardian_type_field').slideDown();
		$('#guardian_type').prop('required', true);
		
		// If guardian type is already selected, trigger the change to update fields
		var currentGuardianType = $('#guardian_type').val();
		if(currentGuardianType) {
			$('#guardian_type').trigger('change');
		}
	} else {
		// Clear all fields
		$('#guardian_type_field').hide();
		$('#guardian_type').prop('required', false).val('');
		$('#other_guardian_section').hide();
		clearAllParentFields();
	}
});

$('#guardian_type').change(function() {
	var guardianType = $(this).val();
	var selectedOption = $('#parent_id').find('option:selected');
	var guardianData = {
		name: selectedOption.data('name'),
		phone: selectedOption.data('phone'),
		email: selectedOption.data('email'),
		address: selectedOption.data('address'),
		profession: selectedOption.data('profession')
	};
	
	if(!guardianType) {
		$('#other_guardian_section').hide();
		clearAllParentFields();
		return;
	}
	
	if(guardianType === 'father') {
		// Populate father's fields
		$('#father_name').val(guardianData.name || '');
		$('#father_phone').val(guardianData.phone || '');
		$('#father_occupation').val(guardianData.profession || '');
		// Clear mother and other
		$('#mother_name, #mother_phone, #mother_occupation').val('');
		$('#other_guardian_section').slideUp();
	} else if(guardianType === 'mother') {
		// Populate mother's fields
		$('#mother_name').val(guardianData.name || '');
		$('#mother_phone').val(guardianData.phone || '');
		$('#mother_occupation').val(guardianData.profession || '');
		// Clear father and other
		$('#father_name, #father_phone, #father_occupation').val('');
		$('#other_guardian_section').slideUp();
	} else if(guardianType === 'other') {
		// Show and populate other guardian section
		$('#guardian_name').val(guardianData.name || '');
		$('#guardian_phone').val(guardianData.phone || '');
		$('#guardian_email').val(guardianData.email || '');
		$('#guardian_address').val(guardianData.address || '');
		$('#guardian_occupation').val(guardianData.profession || '');
		// Clear father and mother
		$('#father_name, #father_phone, #father_occupation').val('');
		$('#mother_name, #mother_phone, #mother_occupation').val('');
		$('#other_guardian_section').slideDown();
	}
});

function clearAllParentFields() {
	$('#father_name, #father_phone, #father_occupation').val('');
	$('#mother_name, #mother_phone, #mother_occupation').val('');
	$('#guardian_name, #guardian_phone, #guardian_email, #guardian_address, #guardian_occupation').val('');
}

function reloadParentDropdown() {
	$.ajax({
		url: '<?php echo site_url('admin/get_parent_dropdown'); ?>',
		type: 'GET',
		dataType: 'json',
		success: function(response) {
			if(response.status === 'success') {
				let options = '<option value="">Select Guardian / Register New</option>';
				options += '<option value="new">Register New Guardian</option>';
				
				response.parents.forEach(function(parent) {
					let selected = parent.parent_id == response.last_added ? 'selected' : '';
					options += '<option value="' + parent.parent_id + '" ' + selected;
					options += ' data-name="' + (parent.name || '') + '"';
					options += ' data-phone="' + (parent.phone || '') + '"';
					options += ' data-email="' + (parent.email || '') + '"';
					options += ' data-address="' + (parent.address || '') + '"';
					options += ' data-profession="' + (parent.profession || '') + '"';
					options += '>' + parent.name + '</option>';
				});
				
				$('#parent_id').html(options);
				
				if(typeof $.fn.select2 !== 'undefined') {
					$('#parent_id').select2('destroy').select2();
				}
				
				if(response.last_added) {
					$('#guardian_type_field').show();
					$('#guardian_type').prop('required', true);
				}
			}
		}
	});
}

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
	showAjaxModal_alert('Processing admission...', 'loading');
	
	// Create FormData from form
	var formData = new FormData(this);
	
	// Ensure CSRF token is included
	var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
	var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
	formData.set(csrfName, csrfHash);
	
	$.ajax({
		url: '<?php echo site_url('admin/student/create'); ?>',
		type: 'POST',
		dataType: 'json',
		data: formData,
		cache: false,
		contentType: false,
		processData: false
	})
	.done(function(data) {
		if(data.done == 'success') {
			localStorage.removeItem('student_add_form');
			showAjaxModal_alert('Student admitted successfully', 'success');
			setTimeout(() => location.reload(), 2000);
		} else {
			$(function(){
				$.each(data, function(index, val) {
					$('#list_err_student_create').append('<li style="font-size: 16px">' + val + '</li>');
				}); 
			});
			showAjaxModal_alert('<ul id="list_err_student_create"></ul>', 'error');
		}
	})
	.fail(function(xhr, status, error) {
		console.error('AJAX Error:', xhr.responseText);
		let errorMsg = 'An error occurred. Please try again.';
		
		// Try to parse error response
		try {
			let response = JSON.parse(xhr.responseText);
			if(response.message) {
				errorMsg = response.message;
			} else if(response.error) {
				errorMsg = response.error;
			}
		} catch(e) {
			// If not JSON, show raw error
			if(xhr.responseText && xhr.responseText.length < 500) {
				errorMsg = xhr.responseText;
			} else {
				errorMsg = 'Server Error: ' + xhr.status + ' - ' + error;
			}
		}
		
		showAjaxModal_alert(errorMsg, 'error');
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
		data: {
			'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
		},
		cache: false
	})
	.done(function(response) {
		$('#my_admission_fee').val(response.amount);
		$('#item_id').val(response.item_id);
		loadAdmissionBills();
	});
});

$('#class_id').change(function() {
	var class_id = $(this).val();
	get_class_sections(class_id);
	loadAdmissionBills();
});

function loadAdmissionBills() {
	let class_id = $('#class_id').val();
	let residence_type = $('#residence_type').val() || 'Day';
	
	if(!class_id) {
		$('#bill_items_container').html('<div class="bill-empty-state"><i class="entypo-info"></i><p>Select class and residence type to view admission bills</p></div>');
		return;
	}
	
	// Clear the container first
	$('#bill_items_container').html('<div class="bill-empty-state"><i class="entypo-info"></i><p>Loading bills...</p></div>');
	
	$.ajax({
		url: '<?php echo site_url('admin/getAdmissionBillPreview'); ?>',
		type: 'POST',
		data: { 
			class_id: class_id, 
			residence_type: residence_type,
			'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
		},
		dataType: 'json',
		cache: false
	})
	.done(function(response) {
		if(response.status === 'success') {
			let html = '';
			let total = 0;
			let hasItems = false;
			
			// Add regular bills (excluding admission fees)
			if(response.bills && response.bills.length > 0) {
				$.each(response.bills, function(index, bill) {
					if(bill.title && bill.title !== 'null' && bill.title !== null) {
						let amount = parseFloat(bill.amount);
						html += '<div class="bill-item" data-bill-title="' + bill.title + '">';
						html += '<div class="bill-item-name"><i class="entypo-dot"></i>' + bill.title + '</div>';
						html += '<div style="display: flex; align-items: center; gap: 10px;">';
						html += '<div class="bill-item-amount"><input type="number" step="0.01" min="0" class="bill-amount-input" name="bill_amounts[]" data-title="' + bill.title + '" value="' + amount.toFixed(2) + '" style="width:140px;padding:8px;border:2px solid #0ea5e9;border-radius:5px;text-align:right;font-weight:700;color:#0ea5e9;font-size:16px;"></div>';
						html += '<input type="hidden" class="bill-title-input" name="bill_titles[]" value="' + bill.title + '">';
						html += '<i class="fa fa-times remove-bill-item" data-title="' + bill.title + '" title="Remove this item" style="color:#ef4444;font-size:18px;cursor:pointer;transition:all 0.3s;"></i>';
						html += '</div>';
						html += '</div>';
						hasItems = true;
					}
				});
			}
			
			// Add admission fee for current residence type only
			if(response.admission_fee && parseFloat(response.admission_fee) > 0) {
				let admFee = parseFloat(response.admission_fee);
				html += '<div class="bill-item admission-fee-item">';
				html += '<div class="bill-item-name"><i class="entypo-dot"></i>Admission Fee (' + residence_type + ')</div>';
				html += '<div style="display: flex; align-items: center; gap: 10px;">';
				html += '<div class="bill-item-amount"><input type="number" step="0.01" min="0" class="bill-amount-input admission-fee-input" data-title="Admission Fee" value="' + admFee.toFixed(2) + '" style="width:140px;padding:8px;border:2px solid #0ea5e9;border-radius:5px;text-align:right;font-weight:700;color:#0ea5e9;font-size:16px;"></div>';
				html += '<i class="fa fa-times remove-admission-fee" title="Remove admission fee" style="color:#ef4444;font-size:18px;cursor:pointer;transition:all 0.3s;"></i>';
				html += '</div>';
				html += '</div>';
				hasItems = true;
			}
			
			if(hasItems) {
				html += '<div style="border-top: 2px solid #0ea5e9; padding-top: 15px; margin-top: 15px;">';
				html += '<div style="display: flex; justify-content: space-between; padding: 10px 0; font-size: 15px;">';
				html += '<div style="font-weight: 600; color: #374151;">Subtotal</div>';
				html += '<div style="font-weight: 700; color: #374151;" id="subtotal_amount">' + CURRENCY + ' 0.00</div>';
				html += '</div>';
				html += '<div id="discount_details" style="display: none; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 10px; padding: 15px; margin: 10px 0; border: 2px solid #10b981;">';
				html += '<div style="font-weight: 700; color: #065f46; margin-bottom: 10px; font-size: 14px;"><i class="fa fa-tag"></i> Discount Applied</div>';
				html += '<div id="discount_profile_info" style="font-size: 12px; color: #065f46; margin-bottom: 10px; padding: 8px; background: rgba(255,255,255,0.5); border-radius: 6px;"></div>';
				html += '<div id="discount_breakdown" style="font-size: 13px; color: #047857;"></div>';
				html += '<div style="display: flex; justify-content: space-between; padding: 10px 0; margin-top: 10px; border-top: 2px solid #10b981; font-size: 15px;">';
				html += '<div style="font-weight: 700; color: #065f46;">Total Discount</div>';
				html += '<div style="font-weight: 800; color: #065f46; font-size: 16px;" id="discount_total_amount">- ' + CURRENCY + ' 0.00</div>';
				html += '</div>';
				html += '</div>';
				html += '</div>';
				html += '<div class="bill-total">';
				html += '<div class="bill-total-label self-start">TOTAL ADMISSION BILL</div>';
				html += '<div class="bill-total-amount" id="total_bill_amount">' + CURRENCY + ' 0.00</div>';
				html += '</div>';
				$('#discount_selector_section').show();
			} else {
				html = '<div class="bill-empty-state"><i class="entypo-info-circled"></i><p>No bills configured for this class</p></div>';
				$('#discount_selector_section').hide();
			}
			
			if(hasItems) {
				$('#apply_discount_btn').show();
			} else {
				$('#apply_discount_btn').hide();
			}
			
			$('#bill_items_container').html(html);
			
			// Calculate total and add event listeners
			if(hasItems) {
				calculateBillTotal();
				$('.bill-amount-input').on('input', calculateBillTotal);
				
				$('.admission-fee-input').on('input', function() {
					$('#my_admission_fee').val($(this).val());
					calculateBillTotal();
				});
			}
		}
	})
	.fail(function(xhr, status, error) {
		$('#bill_items_container').html('<div class="bill-empty-state"><i class="entypo-attention"></i><p>Error loading bills</p></div>');
	});
}

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

// Event delegation for dynamically created delete buttons
$(document).on('click', '.remove-bill-item', function() {
	let title = $(this).data('title');
	let $billItem = $(this).closest('.bill-item');
	
	showConfirmModal(
		'Remove Bill Item', 
		'Are you sure you want to remove <strong>"' + title + '"</strong> from this student\'s bill?',
		function() {
			$billItem.fadeOut(300, function() {
				$(this).remove();
				calculateBillTotal();
			});
		},
		'Remove',
		'warning'
	);
});

// Event delegation for admission fee delete
$(document).on('click', '.remove-admission-fee', function() {
	let $admissionFeeItem = $('.admission-fee-item');
	
	showConfirmModal(
		'Remove Admission Fee', 
		'Are you sure you want to remove the <strong>Admission Fee</strong> from this student\'s bill?',
		function() {
			$admissionFeeItem.fadeOut(300, function() {
				$(this).remove();
				$('#my_admission_fee').val(0); // Clear hidden field
				calculateBillTotal();
			});
		},
		'Remove',
		'warning'
	);
});

function calculateBillTotal() {
	let subtotal = 0;
	$('.bill-amount-input').each(function() {
		let amount = parseFloat($(this).val()) || 0;
		subtotal += amount;
	});
	
	$('#subtotal_amount').text(CURRENCY + ' ' + subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
	
	// Only apply preview for invoice discount profile
	let invoiceProfileId = $('#invoice_discount_profile').val();
	if(invoiceProfileId) {
		applyDiscountPreview(subtotal);
	} else {
		$('#discount_details').hide();
		$('#total_bill_amount').text(CURRENCY + ' ' + subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
	}
}

// Invoice discount affects the preview and shows profile details
$('#invoice_discount_profile').change(function() {
	var profileId = $(this).val();
	var selectedOption = $(this).find('option:selected');
	var method = selectedOption.data('method');
	var value = selectedOption.data('value');
	
	if(profileId) {
		// Fetch profile details to show applicable items
		$.ajax({
			url: '<?php echo site_url('admin/getDiscountProfileDetails'); ?>',
			type: 'POST',
			data: { profile_id: profileId },
			dataType: 'json'
		}).done(function(response) {
			if(response.status === 'success' && response.profile) {
				var profile = response.profile;
				var methodText = method === 'percentage' ? 'Percentage Discount' : 'Fixed Amount Discount';
				var valueText = method === 'percentage' 
					? '<span style="font-size: 24px; font-weight: 800; color: #2563eb;">' + value + '%</span>' 
					: '<span style="font-size: 24px; font-weight: 800; color: #2563eb;">' + CURRENCY + ' ' + parseFloat(value).toFixed(2) + '</span>';
				
				var itemsText = '';
				if(profile.is_all_items) {
					itemsText = '<div style="font-size: 12px; color: #1e40af; margin-top: 8px; font-weight: 600;"><i class="fa fa-check-circle"></i> Applies to: All Bill Items</div>';
				} else if(profile.item_names && profile.item_names.length > 0) {
					itemsText = '<div style="font-size: 11px; color: #1e40af; margin-top: 8px;"><strong>Applies to:</strong> ' + profile.item_names.join(', ') + '</div>';
				}
				
				var html = '<div style="text-align: center; padding: 15px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 8px;">';
				html += '<div style="font-size: 11px; color: #1e40af; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">' + methodText + '</div>';
				html += valueText;
				html += itemsText;
				html += '</div>';
				$('#invoice_profile_preview').html(html).slideDown(300);
			}
		});
	} else {
		$('#invoice_profile_preview').slideUp(300);
	}
	
	calculateBillTotal();
});

// Daily fees discount shows profile details (doesn't affect invoice preview)
$('#daily_fees_discount_profile').change(function() {
	var profileId = $(this).val();
	var selectedOption = $(this).find('option:selected');
	var method = selectedOption.data('method');
	var value = selectedOption.data('value');
	
	if(profileId) {
		// Fetch profile details to show applicable items
		$.ajax({
			url: '<?php echo site_url('admin/getDiscountProfileDetails'); ?>',
			type: 'POST',
			data: { profile_id: profileId },
			dataType: 'json'
		}).done(function(response) {
			if(response.status === 'success' && response.profile) {
				var profile = response.profile;
				var methodText = method === 'percentage' ? 'Percentage Discount' : 'Fixed Amount Discount';
				var valueText = method === 'percentage' 
					? '<span style="font-size: 24px; font-weight: 800; color: #059669;">' + value + '%</span>' 
					: '<span style="font-size: 24px; font-weight: 800; color: #059669;">' + CURRENCY + ' ' + parseFloat(value).toFixed(2) + '</span>';
				
				var itemsText = '';
				if(profile.is_all_items) {
					itemsText = '<div style="font-size: 12px; color: #065f46; margin-top: 8px; font-weight: 600;"><i class="fa fa-check-circle"></i> Applies to: All Daily Fees</div>';
				} else if(profile.item_names && profile.item_names.length > 0) {
					itemsText = '<div style="font-size: 11px; color: #065f46; margin-top: 8px;"><strong>Applies to:</strong> ' + profile.item_names.join(', ') + '</div>';
				}
				
				var html = '<div style="text-align: center; padding: 15px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 8px;">';
				html += '<div style="font-size: 11px; color: #065f46; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">' + methodText + '</div>';
				html += valueText;
				html += itemsText;
				html += '<div style="font-size: 11px; color: #047857; margin-top: 8px; font-style: italic;">Applied during daily fee collection</div>';
				html += '</div>';
				$('#daily_profile_preview').html(html).slideDown(300);
			}
		});
	} else {
		$('#daily_profile_preview').slideUp(300);
	}
});

function applyDiscountPreview(subtotal) {
	let invoiceProfileId = $('#invoice_discount_profile').val();
	if(!invoiceProfileId) {
		$('#discount_details').hide();
		$('#total_bill_amount').text(CURRENCY + ' ' + subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
		return;
	}
	
	$.ajax({
		url: '<?php echo site_url('admin/getDiscountProfileDetails'); ?>',
		type: 'POST',
		data: { profile_id: invoiceProfileId, class_id: $('#class_id').val() },
		dataType: 'json',
		cache: false
	})
	.done(function(response) {
		console.log('=== DISCOUNT PROFILE RESPONSE ===');
		console.log('Full response:', response);
		if(response.status === 'success' && response.profile) {
			let profile = response.profile;
			console.log('Profile:', profile);
			console.log('Discount method:', profile.discount_method);
			console.log('Discount value:', profile.discount_value);
			console.log('Bill item IDs:', profile.bill_item_ids);
			console.log('Items:', profile.items);
			let totalDiscount = 0;
			let breakdownHtml = '';
			
			if(profile.bill_item_ids === '*') {
				// Wildcard: apply to all items - show as single line
				if(profile.discount_method === 'percentage') {
					$('.bill-amount-input').each(function() {
						let itemAmount = parseFloat($(this).val()) || 0;
						let discount = (itemAmount * parseFloat(profile.discount_value)) / 100;
						totalDiscount += discount;
					});
					if(totalDiscount > 0) {
						breakdownHtml = '<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><span>All Bill Items</span><span style="color: #047857; font-weight: 600;">- ' + CURRENCY + ' ' + totalDiscount.toFixed(2) + '</span></div>';
					}
				} else {
					// Fixed amount: apply to all items
					let fixedAmount = parseFloat(profile.discount_value);
					let remaining = fixedAmount;
					$('.bill-amount-input').each(function() {
						if(remaining <= 0) return;
						let itemAmount = parseFloat($(this).val()) || 0;
						let itemDiscount = Math.min(remaining, itemAmount);
						if(itemDiscount > 0) {
							totalDiscount += itemDiscount;
							remaining -= itemDiscount;
						}
					});
					if(totalDiscount > 0) {
						breakdownHtml = '<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;"><span>All Bill Items</span><span style="color: #047857; font-weight: 600;">- ' + CURRENCY + ' ' + totalDiscount.toFixed(2) + '</span></div>';
					}
				}
			} else if(profile.items && profile.items.length > 0) {
				// Specific items
				$('.bill-amount-input').each(function() {
					let itemTitle = $(this).data('title');
					let itemAmount = parseFloat($(this).val()) || 0;
					let itemTitleLower = itemTitle.toLowerCase().trim();
					let discountApplied = false;
					
					$.each(profile.items, function(index, item) {
						if(discountApplied) return;
						let discountTypeLower = item.discount_type.toLowerCase().trim();
						
						if(discountTypeLower === itemTitleLower || 
						   (discountTypeLower.includes('school') && itemTitleLower.includes('school')) ||
						   (discountTypeLower.includes('admission') && itemTitleLower.includes('admission')) ||
						   (discountTypeLower.includes('uniform') && itemTitleLower.includes('uniform'))) {
							let discount = 0;
							if(item.discount_method === 'percentage') {
								discount = (itemAmount * parseFloat(item.discount_value)) / 100;
							} else {
								// Fixed amount
								discount = Math.min(parseFloat(item.discount_value), itemAmount);
							}
							if(discount > 0) {
								totalDiscount += discount;
								breakdownHtml += '<div style="display: flex; justify-content: space-between; padding: 5px 0;">';
								breakdownHtml += '<div>' + itemTitle + ' (' + (item.discount_method === 'percentage' ? item.discount_value + '%' : CURRENCY + ' ' + parseFloat(item.discount_value).toFixed(2)) + ')</div>';
								breakdownHtml += '<div style="font-weight: 600;">- ' + CURRENCY + ' ' + discount.toFixed(2) + '</div>';
								breakdownHtml += '</div>';
								discountApplied = true;
							}
						}
					});
				});
			}
			
			console.log('=== DISCOUNT CALCULATION COMPLETE ===');
			console.log('Total discount:', totalDiscount);
			console.log('Breakdown HTML:', breakdownHtml);
			
			if(totalDiscount > 0 || profile.bill_item_ids === '*') {
				if(totalDiscount > 0) {
					// Show profile info
					var profileInfo = '<strong>' + profile.profile_name + '</strong><br>';
					if(profile.is_all_items) {
						profileInfo += '<i class="fa fa-check-circle"></i> Applies to: All Bill Items';
					} else if(profile.item_names && profile.item_names.length > 0) {
						profileInfo += 'Applies to: ' + profile.item_names.join(', ');
					}
					$('#discount_profile_info').html(profileInfo);
					
					$('#discount_breakdown').html(breakdownHtml);
					$('#discount_total_amount').text('- ' + CURRENCY + ' ' + totalDiscount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
					$('#discount_details').show();
					let finalTotal = subtotal - totalDiscount;
					$('#total_bill_amount').text(CURRENCY + ' ' + finalTotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
				} else {
					// Show profile info even when no matching items
					var profileInfo = '<strong>' + profile.profile_name + '</strong><br>';
					if(profile.is_all_items) {
						profileInfo += '<i class="fa fa-check-circle"></i> Applies to: All Bill Items';
					} else if(profile.item_names && profile.item_names.length > 0) {
						profileInfo += 'Applies to: ' + profile.item_names.join(', ');
					}
					$('#discount_profile_info').html(profileInfo);
					
					$('#discount_breakdown').html('<div style="color: #f59e0b; font-style: italic;">No matching bill items for this discount profile</div>');
					$('#discount_total_amount').text('- ' + CURRENCY + ' 0.00');
					$('#discount_details').show();
					$('#total_bill_amount').text(CURRENCY + ' ' + subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
				}
			} else {
				$('#discount_details').hide();
				$('#total_bill_amount').text(CURRENCY + ' ' + subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
			}
		}
	})
	.fail(function() {
		$('#discount_details').hide();
		$('#total_bill_amount').text(CURRENCY + ' ' + subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
	});
}


</script>

<script>
function initDatepicker() {
	$('#birthday').datepicker({
		format: 'dd-mm-yyyy',
		autohide: true,
		endDate: new Date(),
		maxDate: new Date(),
		orientation: 'bottom auto'
	});
}

$(document).ready(function() {
	initDatepicker();
	
	$('input[required], select[required], textarea[required]').each(function() {
		var $field = $(this);
		var label = $field.closest('.form-field').find('label').first().text().replace('*', '').trim();
		if(label) {
			$field.on('invalid', function() {
				this.setCustomValidity('Please fill out the ' + label + ' field');
			});
			$field.on('input change', function() {
				this.setCustomValidity('');
			});
		}
	});
});
</script>

<!-- Form Customizer Modal -->
<div id="formCustomizerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; overflow-y: auto;">
	<div style="max-width: 800px; margin: 50px auto; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
		<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px 30px; border-radius: 20px 20px 0 0; position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
				<h3 style="margin: 0; font-size: 24px; font-weight: 700; color: white;"><i class="fa fa-sliders"></i> Customize Admission Form</h3>
				<button onclick="closeFormCustomizer()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; font-size: 20px;">&times;</button>
			</div>
			<div style="display: flex; gap: 10px; align-items: center;">
				<input type="text" id="fieldSearchInput" placeholder="Search fields..." onkeyup="filterFields()" style="flex: 1; padding: 10px 15px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: white; border-radius: 8px; font-size: 14px;">
				<button onclick="resetFormCustomization()" style="padding: 10px 20px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: white; border-radius: 8px; font-weight: 600; cursor: pointer; white-space: nowrap;">
					<i class="fa fa-undo"></i> Reset
				</button>
			</div>
		</div>
		<div style="padding: 30px;">
			<p style="color: #6b7280; margin-bottom: 25px; font-size: 14px;"><i class="fa fa-info-circle"></i> Toggle fields to show or hide them. Required fields cannot be hidden.</p>
			<div id="fieldToggles" style="display: grid; gap: 15px;"></div>
		</div>
	</div>
</div>

<script>
const formFields = [
	{name: 'first_name', label: 'First Name', required: true, section: 'Personal'},
	{name: 'middle_name', label: 'Middle Name', required: false, section: 'Personal'},
	{name: 'last_name', label: 'Last Name', required: true, section: 'Personal'},
	{name: 'sex', label: 'Gender', required: true, section: 'Personal'},
	{name: 'birthday', label: 'Date of Birth', required: true, section: 'Personal'},
	{name: 'blood_group', label: 'Blood Group', required: false, section: 'Personal'},
	{name: 'nationality', label: 'Nationality', required: false, section: 'Personal'},
	{name: 'ghana_card_id', label: 'Ghana Card ID', required: false, section: 'Personal'},
	{name: 'place_of_birth', label: 'Place of Birth', required: false, section: 'Personal'},
	{name: 'hometown', label: 'Hometown', required: false, section: 'Personal'},
	{name: 'tribe', label: 'Tribe', required: false, section: 'Personal'},
	{name: 'religion', label: 'Religion', required: false, section: 'Personal'},
	{name: 'student_phone', label: 'Student Phone', required: false, section: 'Personal'},
	{name: 'email', label: 'Email Address', required: false, section: 'Personal'},
	{name: 'address', label: 'Residential Address', required: false, section: 'Personal'},
	{name: 'student_code', label: 'Student ID', required: true, section: 'Academic'},
	{name: 'class_id', label: 'Class to Enroll', required: true, section: 'Academic'},
	{name: 'section_id', label: 'Section', required: true, section: 'Academic'},
	{name: 'admission_date', label: 'Admission Date', required: false, section: 'Academic'},
	{name: 'former_school', label: 'Former School', required: false, section: 'Academic'},
	{name: 'class_reached', label: 'Class Reached', required: false, section: 'Academic'},
	{name: 'father_name', label: "Father's Name", required: false, section: 'Guardian'},
	{name: 'father_phone', label: "Father's Phone", required: false, section: 'Guardian'},
	{name: 'father_occupation', label: "Father's Occupation", required: false, section: 'Guardian'},
	{name: 'mother_name', label: "Mother's Name", required: false, section: 'Guardian'},
	{name: 'mother_phone', label: "Mother's Phone", required: false, section: 'Guardian'},
	{name: 'mother_occupation', label: "Mother's Occupation", required: false, section: 'Guardian'},
	{name: 'allergies', label: 'Allergies', required: false, section: 'Medical'},
	{name: 'medical_conditions', label: 'Medical Conditions', required: false, section: 'Medical'},
	{name: 'nhis_number', label: 'NHIS Number', required: false, section: 'Medical'},
	{name: 'nhis_status', label: 'NHIS Status', required: false, section: 'Medical'},
	{name: 'disability_status', label: 'Disability Status', required: false, section: 'Medical'},
	{name: 'special_needs', label: 'Special Needs', required: false, section: 'Medical'},
	{name: 'learning_support', label: 'Learning Support', required: false, section: 'Medical'},
	{name: 'digital_literacy', label: 'Digital Literacy Level', required: false, section: 'Medical'},
	{name: 'home_technology_access', label: 'Home Technology Access', required: false, section: 'Medical'}
];

function openFormCustomizer() {
	const modal = document.getElementById('formCustomizerModal');
	const container = document.getElementById('fieldToggles');
	const prefs = JSON.parse(localStorage.getItem('studentFormPrefs') || '{}');
	
	let sections = {};
	formFields.forEach(field => {
		if(!sections[field.section]) sections[field.section] = [];
		sections[field.section].push(field);
	});
	
	let html = '';
	Object.keys(sections).forEach(section => {
		html += `<div style="background: #f9fafb; padding: 20px; border-radius: 12px; border: 2px solid #e5e7eb;">`;
		html += `<h4 style="margin: 0 0 15px 0; color: #374151; font-size: 16px; font-weight: 700;"><i class="fa fa-folder-open"></i> ${section} Information</h4>`;
		sections[section].forEach(field => {
			const isVisible = prefs[field.name] !== false;
			const disabled = field.required ? 'disabled' : '';
			const opacity = field.required ? '0.5' : '1';
			html += `<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; border-radius: 8px; margin-bottom: 10px; opacity: ${opacity};">`;
			html += `<div style="flex: 1;"><span style="font-weight: 600; color: #1f2937;">${field.label}</span>${field.required ? ' <span style="color: #ef4444; font-size: 12px;">(Required)</span>' : ''}</div>`;
			html += `<label class="toggle-switch"><input type="checkbox" ${isVisible ? 'checked' : ''} ${disabled} onchange="toggleField('${field.name}', this.checked)"><span class="toggle-slider"></span></label>`;
			html += `</div>`;
		});
		html += `</div>`;
	});
	
	container.innerHTML = html;
	modal.style.display = 'block';
}

function closeFormCustomizer() {
	document.getElementById('formCustomizerModal').style.display = 'none';
}

function filterFields() {
	const searchTerm = document.getElementById('fieldSearchInput').value.toLowerCase();
	const sections = document.querySelectorAll('#fieldToggles > div');
	
	sections.forEach(section => {
		const fields = section.querySelectorAll('div[style*="display: flex"]');
		let visibleCount = 0;
		
		fields.forEach(field => {
			const text = field.textContent.toLowerCase();
			if(text.includes(searchTerm)) {
				field.style.display = 'flex';
				visibleCount++;
			} else {
				field.style.display = 'none';
			}
		});
		
		section.style.display = visibleCount > 0 ? '' : 'none';
	});
}

function toggleField(fieldName, isVisible) {
	const prefs = JSON.parse(localStorage.getItem('studentFormPrefs') || '{}');
	prefs[fieldName] = isVisible;
	localStorage.setItem('studentFormPrefs', JSON.stringify(prefs));
	
	$.post('<?php echo site_url('admin/save_form_preference'); ?>', {field_name: fieldName, is_visible: isVisible ? 1 : 0});
	
	const fieldEl = document.querySelector(`[name="${fieldName}"]`);
	if(fieldEl) {
		const formField = fieldEl.closest('.form-field');
		if(formField) {
			if(isVisible) {
				formField.style.display = '';
				fieldEl.removeAttribute('disabled');
			} else {
				formField.style.display = 'none';
				fieldEl.setAttribute('disabled', 'disabled');
			}
		}
	}
}


function resetFormCustomization() {
	localStorage.removeItem('studentFormPrefs');
	formFields.forEach(field => {
		const fieldEl = document.querySelector(`[name="${field.name}"]`);
		if(fieldEl) {
			const formField = fieldEl.closest('.form-field');
			if(formField) formField.style.display = '';
			fieldEl.removeAttribute('disabled');
		}
	});
	closeFormCustomizer();
	showAjaxModal_alert('Form reset to default', 'success', false);
}

$(document).ready(function() {
	$.get('<?php echo site_url('admin/get_form_preferences'); ?>', function(data) {
		if(data.status === 'success') {
			const prefs = {};
			data.preferences.forEach(p => prefs[p.field_name] = p.is_visible == 1);
			localStorage.setItem('studentFormPrefs', JSON.stringify(prefs));
			formFields.forEach(field => {
				if(prefs[field.name] === false && !field.required) {
					const fieldEl = document.querySelector(`[name="${field.name}"]`);
					if(fieldEl) {
						const formField = fieldEl.closest('.form-field');
						if(formField) formField.style.display = 'none';
						fieldEl.setAttribute('disabled', 'disabled');
					}
				}
			});
		}
	}, 'json');
	const prefs = JSON.parse(localStorage.getItem('studentFormPrefs') || '{}');
	formFields.forEach(field => {
		if(prefs[field.name] === false && !field.required) {
			const fieldEl = document.querySelector(`[name="${field.name}"]`);
			if(fieldEl) {
				const formField = fieldEl.closest('.form-field');
				if(formField) formField.style.display = 'none';
				fieldEl.setAttribute('disabled', 'disabled');
			}
		}
	});
	
	// Auto-save form data
	const formId = 'student_add_form';
	const savedData = localStorage.getItem(formId);
	if(savedData) {
		const data = JSON.parse(savedData);
		Object.keys(data).forEach(name => {
			const field = $('[name="'+name+'"]');
			if(field.length) {
				if(field.is(':checkbox')) field.prop('checked', data[name]);
				else if(field.is('select')) field.val(data[name]).trigger('change');
				else field.val(data[name]);
			}
		});
	}
	
	let saveTimeout;
	$('#student_single_add_form :input').on('input change', function() {
		clearTimeout(saveTimeout);
		saveTimeout = setTimeout(() => {
			const formData = {};
			$('#student_single_add_form :input:not([type="file"]):not([type="password"]):not([readonly])').each(function() {
				const name = $(this).attr('name');
				if(name) {
					if($(this).is(':checkbox')) formData[name] = $(this).is(':checked');
					else formData[name] = $(this).val();
				}
			});
			localStorage.setItem(formId, JSON.stringify(formData));
		}, 1000);
	});
	
	$('#student_single_add_form').on('submit', function() {
		localStorage.removeItem(formId);
	});
});
</script>

<style>
.toggle-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; border-radius: 26px; transition: 0.3s; }
.toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: 0.3s; }
input:checked + .toggle-slider { background-color: #10b981; }
input:checked + .toggle-slider:before { transform: translateX(24px); }
input:disabled + .toggle-slider { opacity: 0.5; cursor: not-allowed; }
#fieldSearchInput::placeholder { color: rgba(255,255,255,0.7); }

/* Customize Form Button Responsive */
.customize-form-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.3) !important; }
@media (max-width: 640px) {
	.customize-form-btn { top: 10px !important; right: 10px !important; padding: 8px 12px !important; font-size: 13px !important; }
	.customize-form-btn .btn-text { display: none; }
	.customize-form-btn i { margin: 0 !important; font-size: 16px; }
}

/* Fixed Submit Button Responsive */
@media (max-width: 640px) {
	.submit-btn { position: fixed !important; bottom: 10px !important; right: 10px !important; left: 10px !important; width: calc(100% - 20px) !important; margin: 0 !important; }
}
</style>









