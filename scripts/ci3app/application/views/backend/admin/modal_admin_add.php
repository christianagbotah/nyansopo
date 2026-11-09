<style type="text/css">
	.validate-has-error {
		color: red;
	}
</style>
<div class="max-w-7xl mx-auto">
	<div class="bg-white rounded-lg shadow-lg overflow-hidden">
		<div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
			<h2 class="text-2xl font-bold text-white flex items-center gap-2">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
				</svg>
				<?php echo get_phrase('add_administrator');?>
			</h2>
		</div>

		<div class="p-6 max-h-[70vh] overflow-y-auto">
			<?php echo form_open(site_url('admin/admins/create/') , array('id' => 'admin_add_form', 'class' => 'validate', 'enctype' => 'multipart/form-data'));?>

			<!-- Personal Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Personal Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('first_name');?> <span class="text-red-600">*</span>
						</label>
						<input type="text" name="first_name" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('first_name_required');?>" autofocus>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('other_name');?>
						</label>
						<input type="text" name="other_name" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('last_name');?> <span class="text-red-600">*</span>
						</label>
						<input type="text" name="last_name" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('last_name_required');?>">
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('designation');?> <span class="text-red-600">*</span>
						</label>
						<select name="designation" id="designation" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required onchange="update_btn()">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="1"><?php echo get_phrase('super_administrator');?></option>
							<option value="2"><?php echo get_phrase('administrator');?></option>
							<option value="3"><?php echo get_phrase('Accountant');?></option>
							<option value="4"><?php echo get_phrase('cashier');?></option>
							<option value="5"><?php echo get_phrase('conductor');?></option>
							<option value="6"><?php echo get_phrase('shop_attendant');?></option>
						</select>
					</div>
				</div>
			</div>

			<!-- Contact Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Contact Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('phone');?> <span class="text-red-600">*</span>
						</label>
						<input type="tel" name="phone[]" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('phone_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('email');?> <span class="text-red-600">*</span>
						</label>
						<input type="email" name="email" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('email_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('gender');?> <span class="text-red-600">*</span>
						</label>
						<select name="gender" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('gender_required');?>">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="male"><?php echo get_phrase('male');?></option>
							<option value="female"><?php echo get_phrase('female');?></option>
						</select>
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-1 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('password');?> <span class="text-red-600">*</span>
						</label>
						<input type="password" name="password" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('password_required');?>">
					</div>
				</div>
			</div>

			<!-- Identification -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Identification</h3>
				
				<div class="grid grid-cols-1 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							SSNIT Tier 2 Provider
						</label>
						<select name="tier2_provider_id" id="tier2_provider_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
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
						<input type="text" name="tier2_member_id" id="tier2_member_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" placeholder="Enter Tier 2 provider member ID">
						<small class="text-gray-500 text-sm">Employee's ID with their Tier 2 provider</small>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">SSNIT ID</label>
						<input type="text" name="ssnit_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('Ghana Card');?> ID <span class="text-red-600">*</span>
						</label>
						<input type="text" name="ghana_card_id" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('ghana_card_required');?>">
					</div>
				</div>
			</div>

			<!-- Account Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Account Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_number');?> <span class="text-red-600">*</span>
						</label>
						<input type="number" name="account_number" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('account_number');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_details');?> <span class="text-red-600">*</span>
						</label>
						<textarea rows="4" name="account_details" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('enter_account_details');?>" placeholder="Enter Account Name, Branch etc..."></textarea>
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
			<button type="submit" form="admin_add_form" id="add_btn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-2.5 rounded-lg transition shadow-md hover:shadow-lg">
				<?php echo get_phrase('add_administrator');?>
			</button>
		</div>
	</div>
</div>

<script type="text/javascript">
	$('#admin_add_form').submit(function(e) {
		e.preventDefault();
		
		showConfirmModal(
			'Confirm Add Administrator',
			'Are you sure you want to add this administrator?',
			function() {
				$('#modal_ajax').modal('hide');
				showAjaxModal_alert('Creating administrator...', 'loading');
				
				$.ajax({
					url: $('#admin_add_form').attr('action'),
					type: 'POST',
					data: new FormData($('#admin_add_form')[0]),
					cache: false,
					contentType: false,
					processData: false,
					dataType: 'json'
				}).done(function(response) {
					if(response.status === 'success') {
						showAjaxModal_alert(response.message || 'Administrator added successfully', 'success');
						setTimeout(() => location.reload(), 2000);
					} else {
						showAjaxModal_alert(response.message || 'Operation failed', 'error');
					}
				}).fail(function() {
					showAjaxModal_alert('An error occurred', 'error');
				});
			},
			'<?php echo get_phrase('add_administrator'); ?>',
			'info'
		);
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
		$('#add_btn').text('Add ' + designation);
	}
</script>
