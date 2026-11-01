<style type="text/css">
	.validate-has-error { color: red; }
</style>
<div class="max-w-7xl mx-auto">
	<div class="bg-white rounded-lg shadow-lg overflow-hidden">
		<div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-5">
			<h2 class="text-2xl font-bold text-white flex items-center gap-2">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
				</svg>
				<?php echo get_phrase('add_non_teaching_staff');?>
			</h2>
		</div>

		<div class="p-6">
			<?php echo form_open(site_url('admin/non_teaching_staff/create/') , array('class' => 'validate', 'enctype' => 'multipart/form-data', 'id' => 'non_teaching_staff_add_form'));?>

			<!-- Profile Image Section -->
			<div class="mb-8 flex justify-center">
				<div class="fileinput fileinput-new" data-provides="fileinput">
					<div class="fileinput-new thumbnail border-4 border-gray-200 rounded-full overflow-hidden" style="width: 150px; height: 150px;" data-trigger="fileinput">
						<img src="<?= base_url();?>uploads/non_teaching_staff_image/staff.jpg" alt="Staff" class="object-cover w-full h-full">
					</div>
					<div class="fileinput-preview fileinput-exists thumbnail border-4 border-purple-500 rounded-full overflow-hidden" style="width: 150px; height: 150px;"></div>
					<div class="mt-4 flex gap-2 justify-center">
						<span class="btn bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg btn-file transition">
							<span class="fileinput-new">Select Image</span>
							<span class="fileinput-exists">Change</span>
							<input type="file" name="userfile" accept="image/*">
						</span>
						<a href="#" class="btn bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg fileinput-exists transition" data-dismiss="fileinput">Remove</a>
					</div>
				</div>
			</div>

			<!-- Personal Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-purple-500">Personal Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('first_name');?> <span class="text-red-600">*</span>
						</label>
						<input type="text" name="first_name" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('first_name_required');?>" autofocus>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('other_name');?>
						</label>
						<input type="text" name="other_name" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('last_name');?> <span class="text-red-600">*</span>
						</label>
						<input type="text" name="last_name" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('last_name_required');?>">
					</div>
				</div>

				<div class="mb-4">
					<label class="block mb-2.5 text-xl font-bold text-gray-700" id="verify">
						Staff ID <span class="text-red-600">*</span>
					</label>
					<div class="flex gap-2">
						<?php 
							$staff_code_pref = $this->db->get_where('settings', array('type'=>'non_teaching_staff_code_prefix'))->row();
							$staff_code_f = $this->db->get_where('settings', array('type'=>'non_teaching_staff_code_format'))->row();
							
							// Default prefix and format if not set
							if (!$staff_code_pref) {
								$staff_code_pref = (object)['description' => 'NTS-'];
							}
							if (!$staff_code_f) {
								$staff_code_f = (object)['description' => '00001'];
							}
							
							$staff_code_prefix = $staff_code_pref->description;
							$staff_code_format = $staff_code_f->description;
							$id_length = strlen($staff_code_prefix.$staff_code_format);

							$this->db->select('staff_code');
							$this->db->order_by('staff_code', 'desc');
							$this->db->limit(1);
							$s_query = $this->db->get('non_teaching_staff');
							
							if($s_query->num_rows() > 0) {
								$s_id = $s_query->row()->staff_code;
								$first_num = '';
								$position_of_first_num = '';
								$i = 0;
								for($i=0; $i<strlen($s_id); $i++) {
									if(is_numeric($s_id[$i])) {
										$first_num = $s_id[$i];
										break; 
									}
								}
								$position_of_first_num = strpos($s_id, $first_num);
								$n_sid = substr($s_id, $position_of_first_num, strlen($s_id) - $i);
								$staff_code = $n_sid + 1;
								
								if($first_num == 0) {
									$old_len = strlen($s_id);
									$new_len = strlen($staff_code);
									$act_len = ($old_len - $new_len);
									$staff_code = substr($s_id, 0, $act_len).$staff_code;
								} else {
									$staff_code = $staff_code;
								}
							} else {
								$staff_code = $staff_code_prefix . $staff_code_format;
							}
						?>
						<input type="text" id="id_field" onkeyup="verify_sid()" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" name="staff_code" minlength="<?php echo $id_length; ?>" maxlength="<?php echo $id_length; ?>" value="<?php 
							if($s_query->num_rows() > 0) {
								if(isset($first_num) && $first_num == '0') { 
									echo $staff_code;
								} else {
									echo $staff_code_prefix.$staff_code;
								} 
							} else {
								echo $staff_code;
							}
						?>" data-validate="required" required data-message-required="<?php echo get_phrase('value_required');?>">
						<button type="button" id="id_generator" class="bg-green-600 hover:bg-green-700 text-white px-6 py-5 rounded-lg transition font-semibold text-xl" onclick="generate_sid()">Validate</button>
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('position');?> <span class="text-red-500">*</span>
						</label>
						<select name="position" id="staff_position" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('position_required');?>" onchange="checkDriverRole()">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="Driver">Driver</option>
							<option value="Cleaner">Cleaner</option>
							<option value="Cook">Cook</option>
							<option value="Security Guard">Security Guard</option>
							<option value="Librarian">Librarian</option>
							<option value="IT Technician">IT Technician</option>
							<option value="Janitor">Janitor</option>
							<option value="Gardener">Gardener</option>
							<option value="Maintenance Worker">Maintenance Worker</option>
							<option value="Kitchen Assistant">Kitchen Assistant</option>
							<option value="Office Assistant">Office Assistant</option>
							<option value="Lab Technician">Lab Technician</option>
							<option value="Receptionist">Receptionist</option>
							<option value="Nurse">Nurse</option>
							<option value="Messenger">Messenger</option>
							<option value="Other">Other</option>
						</select>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('qualification');?>
						</label>
						<input type="text" name="qualification" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
					</div>
				</div>

				<!-- Vehicle Assignment (Only for Drivers) -->
				<div class="grid grid-cols-1 gap-5 mb-4" id="vehicle_assignment_section" style="display:none;">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<i class="fa fa-bus"></i> <?php echo get_phrase('assign_vehicle');?>
						</label>
						<select name="assigned_vehicle_id" id="assigned_vehicle_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
							<option value="">Select Vehicle (Optional)</option>
							<?php
							// Query unique vehicles only - using GROUP BY to ensure distinct vehicle numbers
							// Note: driver filtering removed as driver_id column doesn't exist in transport table
							$this->db->select('number_of_vehicle, route_name');
							$this->db->from('transport');
							$this->db->group_by('number_of_vehicle');
							$this->db->order_by('number_of_vehicle', 'ASC');
							$unassigned_vehicles = $this->db->get()->result();
							foreach ($unassigned_vehicles as $vehicle):
							?>
							<option value="<?php echo htmlspecialchars($vehicle->number_of_vehicle); ?>">
								<?php echo htmlspecialchars($vehicle->number_of_vehicle); ?>
								<?php if (!empty($vehicle->route_name)): ?>
								- Route: <?php echo htmlspecialchars($vehicle->route_name); ?>
								<?php endif; ?>
							</option>
							<?php endforeach; ?>
						</select>
						<small class="text-gray-600 text-base font-medium">
							<i class="fa fa-info-circle"></i> Only unassigned vehicles are shown. Once assigned, the vehicle won't appear for other staff.
						</small>
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('birthday');?> <span class="text-red-500">*</span>
						</label>
						<input type="text" name="birthday" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition datepicker" data-format="dd-mm-yyyy" data-start-view="2" data-validate="required" required data-message-required="<?php echo get_phrase('date_of_birth_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('gender');?> <span class="text-red-500">*</span>
						</label>
						<select name="sex" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('gender_required');?>">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="male"><?php echo get_phrase('male');?></option>
							<option value="female"><?php echo get_phrase('female');?></option>
						</select>
					</div>
				</div>

				<div class="mb-4">
					<label class="block mb-2.5 text-xl font-bold text-gray-700">
						<?php echo get_phrase('address');?>
					</label>
					<input type="text" name="address" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
				</div>
			</div>

			<!-- Contact Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-purple-500">Contact Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('phone');?> <span class="text-red-500">*</span>
						</label>
						<input type="tel" name="phone[]" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('phone_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('email');?> <span class="text-red-500">*</span>
						</label>
						<input type="email" name="email" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('email_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('password');?> <span class="text-red-500">*</span>
						</label>
						<input type="password" name="password" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('password_required');?>">
					</div>
				</div>
			</div>

			<!-- Identification -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-purple-500">Identification</h3>
				
				<div class="grid grid-cols-1 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							SSNIT Tier 2 Provider
						</label>
						<select name="tier2_provider_id" id="tier2_provider_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
							<option value="">Select Tier 2 Provider</option>
							<?php
							$this->db->where('is_active', 1);
							$this->db->order_by('provider_name', 'ASC');
							$providers = $this->db->get('pension_tier2_providers')->result();
							$first_provider_selected = false;
							foreach ($providers as $provider):
								$is_first = !$first_provider_selected;
								$first_provider_selected = true;
							?>
							<option value="<?php echo $provider->provider_id; ?>" <?php if($is_first) echo 'selected'; ?>>
								<?php echo $provider->provider_name; ?> (<?php echo $provider->provider_code; ?>)
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							Tier 2 Member ID / Code
						</label>
						<input type="text" name="tier2_member_id" id="tier2_member_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" placeholder="Enter Tier 2 provider member ID">
						<small class="text-gray-600 text-base font-medium">Employee's ID with their Tier 2 provider</small>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">SSNIT ID</label>
						<input type="text" name="ssnit_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('Ghana Card');?> ID <span class="text-red-500">*</span>
						</label>
						<input type="text" name="ghana_card_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('ghana_card_required');?>">
					</div>
				</div>
			</div>

			<!-- Account Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-purple-500">Account Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_number');?> <span class="text-red-500">*</span>
						</label>
						<input type="number" name="account_number" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('account_number');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_details');?> <span class="text-red-500">*</span>
						</label>
						<textarea rows="4" name="account_details" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('enter_account_details');?>" placeholder="Enter Account Name, Branch etc..."></textarea>
					</div>
				</div>
			</div>

			<!-- Submit Button -->
			<div class="flex justify-end gap-3 pt-6 border-t">
				<button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-2xl font-bold px-10 py-4 rounded-lg transition shadow-lg hover:shadow-xl">
					<?php echo get_phrase('add_non_teaching_staff');?>
				</button>
			</div>

			<?php echo form_close();?>
		</div>
	</div>
</div>

<script type="text/javascript">
	// Initialize datepicker for birthday field
	$(document).ready(function() {
		$('.datepicker').datepicker({
			format: 'dd-mm-yyyy',
			startView: 2,
			autoclose: true,
			todayHighlight: true,
			endDate: new Date() // Can't select future dates for birthday
		});
	});

	function checkDriverRole() {
		var position = $('#staff_position').val().toLowerCase();
		if (position.indexOf('driver') !== -1) {
			$('#vehicle_assignment_section').slideDown();
		} else {
			$('#vehicle_assignment_section').slideUp();
			$('#assigned_vehicle_id').val(''); // Clear selection when not a driver
		}
	}

	function verify_sid() {
		var id = $('#id_field').val();
		$.ajax({
			url: '<?php echo site_url('admin/get_generated_sid_verification/'); ?>' + id,
			success: function(response) {
				jQuery('#verify').html(response);
			}
		});
	}

	function generate_sid() {
		$.ajax({
			url: '<?php echo site_url('admin/get_generated_sid'); ?>',
			success: function(response) {
				jQuery('#id_field').val(response);
			}
		});
		verify_sid();
	}

	$('#non_teaching_staff_add_form').submit(function(event) {
		event.preventDefault();
		
		showConfirmModal(
			'Confirm Add Staff',
			'Are you sure you want to add this non-teaching staff member?',
			function() {
				// Close main modal first
				$('#modal_ajax').modal('hide');
				
				// Show loading modal (cleaner pattern matching teacher forms)
				showAjaxModal_alert('Creating non-teaching staff...', 'loading');

				let formUrl = $('#non_teaching_staff_add_form').attr('action');

				$.ajax({
					url: formUrl,
					type: 'POST',
					dataType: 'html',
					data: new FormData($('#non_teaching_staff_add_form')[0]),
					cache: false,
					contentType: false,
					processData: false
				})
				.done(function(data) {
					if(data == 1) {
						showAjaxModal_alert('<?=get_phrase('Invalid Email Address'); ?>', 'error');
						setTimeout(() => {
							$('#modal_alert .close').click();
						}, 5000);
					} else if(data == 2) {
						showAjaxModal_alert('<?=get_phrase('Non-Teaching Staff Added Successfully!'); ?>', 'success');
						navigation('<?php echo site_url('admin/non_teaching_staff'); ?>');
						setTimeout(() => {
							$('.close').click();
						}, 5000);
					} else if(data == 3) {
						showAjaxModal_alert('<?=get_phrase('this_email_address_is_not_available'); ?>', 'error');
						setTimeout(() => {
							$('#modal_alert .close').click();
						}, 5000);
					} else if(data == 4) {
						showAjaxModal_alert('NON-TEACHING STAFF ADDED SUCCESSFULLY, BUT THERE WAS ERROR WHILE SENDING AN SMS ' + data, 'error');
						setTimeout(() => {
							$('.close').click();
						}, 1000 * 30);
					} else {
						showAjaxModal_alert('<div class="">' + data + '</div>', 'error');
					}
				});
			},
			'<?php echo get_phrase('add_staff'); ?>',
			'info'
		);
	});
</script>




