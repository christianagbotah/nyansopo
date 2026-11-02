<style type="text/css">
	.validate-has-error { color: red; }
</style>

<?php
$edit_data = $this->db->get_where('non_teaching_staff' , array('staff_id' => $param2) )->result_array();
foreach ( $edit_data as $row):
	$file_path = 'uploads/non_teaching_staff_image/'.$row['staff_id'].'.jpg';

	if(!file_exists($file_path)) {
		$file_path = 'uploads/non_teaching_staff_image/staff.jpg';
	}

	// Split name into first, other, and last name
	$name_parts = explode(' ', $row['name']);
	$first_name = $name_parts[0];
	$last_name = isset($name_parts[count($name_parts)-1]) && count($name_parts) > 1 ? $name_parts[count($name_parts)-1] : '';
	$other_name = '';
	if(count($name_parts) > 2) {
		$other_name_parts = array_slice($name_parts, 1, -1);
		$other_name = implode(' ', $other_name_parts);
	}
?>
<div class="max-w-7xl mx-auto">
	<div class="bg-white rounded-lg shadow-lg overflow-hidden">
		<div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-5">
			<h2 class="text-2xl font-bold text-white flex items-center gap-2">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
				</svg>
				<?php echo get_phrase('edit_non_teaching_staff');?>
			</h2>
		</div>

		<div class="p-6">
			<?php echo form_open(site_url('admin/non_teaching_staff/do_update/'.$row['staff_id']) , array('class' => 'validate','target'=>'_top', 'enctype' => 'multipart/form-data', 'id' => 'non_teaching_staff_edit_form'));?>

			<!-- Profile Image Section -->
			<div class="mb-8 flex justify-center">
				<div class="fileinput fileinput-new" data-provides="fileinput">
					<div class="fileinput-new thumbnail border-4 border-gray-200 rounded-full overflow-hidden" style="width: 150px; height: 150px;" data-trigger="fileinput">
						<img src="<?= base_url().$file_path;?>" alt="Staff" class="object-cover w-full h-full">
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
						<input type="text" name="first_name" value="<?= $first_name; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('first_name_required');?>" autofocus>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('other_name');?>
						</label>
						<input type="text" name="other_name" value="<?= $other_name; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('last_name');?> <span class="text-red-600">*</span>
						</label>
						<input type="text" name="last_name" value="<?= $last_name; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('last_name_required');?>">
					</div>
				</div>

				<div class="mb-4">
					<label class="block mb-2.5 text-xl font-bold text-gray-700" id="verify">
						Staff ID <span class="text-red-600">*</span>
					</label>
					<input type="text" id="id_field" disabled class="bg-gray-100 border-2 border-gray-300 text-gray-500 text-2xl rounded-lg block w-full px-4 py-5 cursor-not-allowed" name="staff_code" value="<?= $row['staff_code'];?>">
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('position');?> <span class="text-red-500">*</span>
						</label>
						<select name="position" id="staff_position" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('position_required');?>" onchange="checkDriverRole()">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="Driver" <?= strtolower($row['position']) == strtolower('Driver') ? 'selected' : ''; ?>>Driver</option>
							<option value="Cleaner" <?= strtolower($row['position']) == strtolower('Cleaner') ? 'selected' : ''; ?>>Cleaner</option>
							<option value="Cook" <?= strtolower($row['position']) == strtolower('Cook') ? 'selected' : ''; ?>>Cook</option>
							<option value="Security Guard" <?= strtolower($row['position']) == strtolower('Security Guard') ? 'selected' : ''; ?>>Security Guard</option>
							<option value="Librarian" <?= strtolower($row['position']) == strtolower('Librarian') ? 'selected' : ''; ?>>Librarian</option>
							<option value="IT Technician" <?= strtolower($row['position']) == strtolower('IT Technician') ? 'selected' : ''; ?>>IT Technician</option>
							<option value="Janitor" <?= strtolower($row['position']) == strtolower('Janitor') ? 'selected' : ''; ?>>Janitor</option>
							<option value="Gardener" <?= strtolower($row['position']) == strtolower('Gardener') ? 'selected' : ''; ?>>Gardener</option>
							<option value="Maintenance Worker" <?= strtolower($row['position']) == strtolower('Maintenance Worker') ? 'selected' : ''; ?>>Maintenance Worker</option>
							<option value="Kitchen Assistant" <?= strtolower($row['position']) == strtolower('Kitchen Assistant') ? 'selected' : ''; ?>>Kitchen Assistant</option>
							<option value="Office Assistant" <?= strtolower($row['position']) == strtolower('Office Assistant') ? 'selected' : ''; ?>>Office Assistant</option>
			<option value="Lab Technician" <?= strtolower($row['position']) == strtolower('Lab Technician') ? 'selected' : ''; ?>>Lab Technician</option>
							<option value="Receptionist" <?= strtolower($row['position']) == strtolower('Receptionist') ? 'selected' : ''; ?>>Receptionist</option>
							<option value="Nurse" <?= strtolower($row['position']) == strtolower('Nurse') ? 'selected' : ''; ?>>Nurse</option>
							<option value="Messenger" <?= strtolower($row['position']) == strtolower('Messenger') ? 'selected' : ''; ?>>Messenger</option>
							<option value="Other" <?= strtolower($row['position']) == strtolower('Other') ? 'selected' : ''; ?>>Other</option>
						</select>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('qualification');?>
						</label>
						<input type="text" name="qualification" value="<?= isset($row['qualification']) ? $row['qualification'] : ''; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
					</div>
				</div>

				<!-- Vehicle Assignment (Only for Drivers) -->
				<div class="grid grid-cols-1 gap-5 mb-4" id="vehicle_assignment_section" style="display:<?= strpos(strtolower($row['position']), 'driver') !== false ? 'block' : 'none'; ?>;">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<i class="fa fa-bus"></i> <?php echo get_phrase('assign_vehicle');?>
						</label>
						<select name="assigned_vehicle_id" id="assigned_vehicle_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
							<option value="">Select Vehicle (Optional)</option>
							<?php
							// Query unique vehicles only - using GROUP BY to ensure distinct vehicle numbers
							$this->db->select('number_of_vehicle, route_name');
							$this->db->from('transport');
							$this->db->group_by('number_of_vehicle');
							$this->db->order_by('number_of_vehicle', 'ASC');
							$unassigned_vehicles = $this->db->get()->result();
							$current_vehicle = isset($row['assigned_vehicle_id']) ? $row['assigned_vehicle_id'] : '';
							foreach ($unassigned_vehicles as $vehicle):
							?>
							<option value="<?php echo htmlspecialchars($vehicle->number_of_vehicle); ?>" <?= $current_vehicle == $vehicle->number_of_vehicle ? 'selected' : ''; ?>>
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
						<input type="text" name="birthday" value="<?= date('d-m-Y', strtotime($row['birthday'])); ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition datepicker" data-format="dd-mm-yyyy" data-start-view="2" data-validate="required" required data-message-required="<?php echo get_phrase('date_of_birth_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('gender');?> <span class="text-red-500">*</span>
						</label>
						<select name="sex" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('gender_required');?>">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="male" <?= strtolower($row['sex']) == 'male' ? 'selected' : ''; ?>><?php echo get_phrase('male');?></option>
							<option value="female" <?= strtolower($row['sex']) == 'female' ? 'selected' : ''; ?>><?php echo get_phrase('female');?></option>
						</select>
					</div>
				</div>

				<div class="mb-4">
					<label class="block mb-2.5 text-xl font-bold text-gray-700">
						<?php echo get_phrase('address');?>
					</label>
					<input type="text" name="address" value="<?= isset($row['address']) ? $row['address'] : ''; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
				</div>
			</div>

			<!-- Contact Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-purple-500">Contact Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('phone');?> <span class="text-red-500">*</span>
						</label>
						<input type="tel" name="phone[]" value="<?= $row['phone'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('phone_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('email');?> <span class="text-red-500">*</span>
						</label>
						<input type="email" name="email" value="<?= $row['email'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('email_required');?>">
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
							foreach ($providers as $provider):
							?>
							<option value="<?php echo $provider->provider_id; ?>" 
									<?php if (isset($row['tier2_provider_id']) && $row['tier2_provider_id'] == $provider->provider_id) echo 'selected'; ?>>
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
						<input type="text" name="tier2_member_id" id="tier2_member_id" 
							   value="<?php echo isset($row['tier2_member_id']) ? $row['tier2_member_id'] : ''; ?>" 
							   class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" 
							   placeholder="Enter Tier 2 provider member ID">
						<small class="text-gray-600 text-base font-medium">Employee's ID with their Tier 2 provider</small>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">SSNIT ID</label>
						<input type="text" name="ssnit_id" value="<?= isset($row['ssnit_id']) ? $row['ssnit_id'] : ''; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('Ghana Card');?> ID <span class="text-red-500">*</span>
						</label>
						<input type="text" name="ghana_card_id" value="<?= isset($row['ghana_card_id']) ? $row['ghana_card_id'] : ''; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('ghana_card_required');?>">
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
						<input type="number" name="account_number" value="<?= isset($row['account_number']) ? $row['account_number'] : ''; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('account_number');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_details');?> <span class="text-red-500">*</span>
						</label>
						<textarea rows="4" name="account_details" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('enter_account_details');?>" placeholder="Enter Account Name, Branch etc..."><?= isset($row['account_details']) ? trim($row['account_details']) : ''; ?></textarea>
					</div>
				</div>
			</div>

			<!-- Submit Button -->
			<div class="flex justify-end gap-3 pt-6 border-t">
				<button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-2xl font-bold px-10 py-4 rounded-lg transition shadow-lg hover:shadow-xl">
					<?php echo get_phrase('update_non_teaching_staff');?>
				</button>
			</div>

			<?php echo form_close();?>
		</div>
	</div>
</div>

<?php
endforeach;
?>

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

		// Check on page load if position is driver
		checkDriverRole();
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

	$('#non_teaching_staff_edit_form').submit(function(event) {
		event.preventDefault();
		
		// Close main modal and show loading with reduced gap
		$('#modal_ajax').modal('hide');
		showAjaxModal_alert('Updating non-teaching staff...', 'loading');

		let formUrl = $(this).attr('action');

		$.ajax({
			url: formUrl,
			type: 'POST',
			dataType: 'html',
			data: new FormData(this),
			cache: false,
			contentType: false,
			processData: false
		})
		.done(function(data) {
			// Handle backend's numeric response codes
			let message = '';
			let success = false;
			
			if(data == '5') {
				message = 'Could Not Update Your Data. Invalid Email Found!';
			} else if(data == 'image size') {
				message = 'Image size must not be more than 1 MB.';
			} else if(data == '4') {
				message = 'Non-Teaching Staff Updated Successfully!';
				success = true;
			} else if(data == '3') {
				message = 'This email address is not available';
			} else {
				// Validation errors (HTML list)
				message = data;
			}
			
			showAjaxModal_alert(message, success ? 'success' : 'error');
			if(success) {
				setTimeout(() => location.reload(), 2000);
			}
		})
		.fail(function(xhr, status, error) {
			showAjaxModal_alert('Error updating non-teaching staff: ' + error, 'error');
		});
	});
</script>
