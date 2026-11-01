<?php
$edit_data = $this->db->get_where('admin', array('admin_id' => $param2))->result_array();
foreach ($edit_data as $row):
?>
<style type="text/css">
	.validate-has-error {
		color: red;
	}
</style>
<div class="max-w-7xl mx-auto">
	<div class="bg-white rounded-lg shadow-lg overflow-hidden">
		<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-5">
			<h2 class="text-2xl font-bold text-white flex items-center gap-2">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
				</svg>
				<?php echo get_phrase('edit_administrator');?>
			</h2>
		</div>

		<div class="p-6 max-h-[70vh] overflow-y-auto">
			<?php echo form_open(site_url('admin/admins/do_update/'.$row['admin_id']), array('id' => 'admin_edit_form', 'class' => 'validate', 'target'=>'_top', 'enctype' => 'multipart/form-data'));?>

			<!-- Personal Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-green-500">Personal Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('first_name');?> <span class="text-red-600">*</span>
						</label>
						<?php 
						$nameParts = explode(' ', $row['name']);
						$firstName = $nameParts[0];
						$otherName = isset($nameParts[1]) && count($nameParts) > 2 ? $nameParts[1] : '';
						$lastName = isset($nameParts[1]) ? (count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 2)) : $nameParts[1]) : '';
						?>
						<input type="text" name="first_name" value="<?= $firstName ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('first_name_required');?>" autofocus>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('other_name');?>
						</label>
						<input type="text" name="other_name" value="<?= $otherName ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('last_name');?> <span class="text-red-600">*</span>
						</label>
						<input type="text" name="last_name" value="<?= $lastName ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('last_name_required');?>">
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('designation');?> <span class="text-red-600">*</span>
						</label>
						<select name="designation" id="designation" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required onchange="update_btn()">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="1" <?php if($row['level'] == 1) echo 'selected'; ?>><?php echo get_phrase('super_administrator');?></option>
							<option value="2" <?php if($row['level'] == 2) echo 'selected'; ?>><?php echo get_phrase('administrator');?></option>
							<option value="3" <?php if($row['level'] == 3) echo 'selected'; ?>><?php echo get_phrase('Accountant');?></option>
							<option value="4" <?php if($row['level'] == 4) echo 'selected'; ?>><?php echo get_phrase('cashier');?></option>
							<option value="5" <?php if($row['level'] == 5) echo 'selected'; ?>><?php echo get_phrase('conductor');?></option>
							<option value="6" <?php if($row['level'] == 6) echo 'selected'; ?>><?php echo get_phrase('shop_attendant');?></option>
						</select>
					</div>
				</div>
			</div>

			<!-- Contact Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-green-500">Contact Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('phone');?> <span class="text-red-600">*</span>
						</label>
						<input type="tel" name="phone[]" value="<?= $row['phone'] ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('phone_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('email');?> <span class="text-red-600">*</span>
						</label>
						<input type="email" name="email" value="<?= $row['email'] ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('email_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('gender');?> <span class="text-red-600">*</span>
						</label>
						<select name="gender" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('gender_required');?>">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="male" <?php if($row['gender'] == 'male') echo 'selected'; ?>><?php echo get_phrase('male');?></option>
							<option value="female" <?php if($row['gender'] == 'female') echo 'selected'; ?>><?php echo get_phrase('female');?></option>
						</select>
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-1 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('password');?>
						</label>
						<input type="password" name="password" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" placeholder="<?php echo get_phrase('leave_blank_to_keep_current');?>">
					</div>
				</div>
			</div>

			<!-- Identification -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-green-500">Identification</h3>
				
				<div class="grid grid-cols-1 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							SSNIT Tier 2 Provider
						</label>
						<select name="tier2_provider_id" id="tier2_provider_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition">
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
							   class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" 
							   placeholder="Enter Tier 2 provider member ID">
						<small class="text-gray-500 text-sm">Employee's ID with their Tier 2 provider</small>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">SSNIT ID</label>
						<input type="text" name="ssnit_id" value="<?= $row['ssnit_id'] ?? '' ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('Ghana Card');?> ID <span class="text-red-600">*</span>
						</label>
						<input type="text" name="ghana_card_id" value="<?= $row['ghana_card_id'] ?? '' ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('ghana_card_required');?>">
					</div>
				</div>
			</div>

			<!-- Account Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-green-500">Account Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_number');?> <span class="text-red-600">*</span>
						</label>
						<input type="number" name="account_number" value="<?= $row['account_number'] ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('account_number');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_details');?> <span class="text-red-600">*</span>
						</label>
						<textarea rows="4" name="account_details" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('enter_account_details');?>"><?= trim($row['account_details']) ?></textarea>
					</div>
				</div>
			</div>

			<?php echo form_close();?>
		</div>
		
		<!-- Sticky Footer -->
		<div class="bg-gray-50 px-6 py-5 border-t border-gray-200 flex justify-end gap-3">
			<button type="button" class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition" data-dismiss="modal">
				<?php echo get_phrase('cancel');?>
			</button>
			<button type="submit" form="admin_edit_form" id="add_btn" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-8 py-2.5 rounded-lg transition shadow-md hover:shadow-lg">
				<?php echo get_phrase('update_administrator');?>
			</button>
		</div>
	</div>
</div>

<?php
endforeach;
?>

<script type="text/javascript">
	$('#admin_edit_form').submit(function(e) {
		e.preventDefault();
		$('#modal_ajax').modal('hide');
		showAjaxModal_alert('Updating administrator...', 'loading');
		
		$.ajax({
			url: $(this).attr('action'),
			type: 'POST',
			data: new FormData(this),
			cache: false,
			contentType: false,
			processData: false,
			dataType: 'json'
		}).done(function(response) {
			if(response.status === 'success') {
				showAjaxModal_alert(response.message || 'Administrator updated successfully', 'success');
				setTimeout(() => location.reload(), 2000);
			} else {
				showAjaxModal_alert(response.message || 'Operation failed', 'error');
			}
		}).fail(function() {
			showAjaxModal_alert('An error occurred', 'error');
		});
	});
	
	function update_btn() {
		var designation = $('#designation').val();
		if(designation == 1) {
			designation = 'Super Administrator';
		} else if(designation == 2) {
			designation = 'Administrator';
		} else if(designation == 3) {
			designation = 'Accountant';
		} else if(designation == 4) {
			designation = 'Cashier';
		} else if(designation == 5) {
			designation = 'Conductor';
		} else if(designation == 6) {
			designation = 'Shop Attendant';
		}
		$('#add_btn').text('Edit ' + designation);
	}
</script>
