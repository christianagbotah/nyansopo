<style type="text/css">
	.validate-has-error { color: red; }
</style>
<div class="max-w-7xl mx-auto">
	<div class="bg-white rounded-lg shadow-lg overflow-hidden">
		<div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
			<h2 class="text-2xl font-bold text-white flex items-center gap-2">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
				</svg>
				<?php echo get_phrase('add_teacher');?>
			</h2>
		</div>

		<div class="p-6">
			<?php echo form_open(site_url('admin/teacher/create/') , array('class' => 'validate', 'enctype' => 'multipart/form-data', 'id' => 'teacher_add_form'));?>

			<!-- Profile Image Section -->
			<div class="mb-8 flex justify-center">
				<div class="fileinput fileinput-new" data-provides="fileinput">
					<div class="fileinput-new thumbnail border-4 border-gray-200 rounded-full overflow-hidden" style="width: 150px; height: 150px;" data-trigger="fileinput">
						<img src="<?= base_url();?>uploads/teacher_image/teacher.jpg" alt="Teacher" class="object-cover w-full h-full">
					</div>
					<div class="fileinput-preview fileinput-exists thumbnail border-4 border-blue-500 rounded-full overflow-hidden" style="width: 150px; height: 150px;"></div>
					<div class="mt-4 flex gap-2 justify-center">
						<span class="btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg btn-file transition">
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
						<label class="block mb-2.5 text-xl font-bold text-gray-700" id="verify">
							Staff ID <span class="text-red-600">*</span>
						</label>
						<div class="flex gap-2">
							<?php 
								$teacher_code_pref = $this->db->get_where('settings', array('type'=>'teacher_code_prefix'))->row()->description;
								$teacher_code_f = $this->db->get_where('settings', array('type'=>'teacher_code_format'))->row()->description;
								$id_length = strlen($teacher_code_pref.$teacher_code_f);

								$this->db->select('teacher_code');
								$this->db->order_by('teacher_code', 'desc');
								$this->db->limit(1);
								$t_query = $this->db->get('teacher');
								$t_id = $t_query->row()->teacher_code;

								if($t_query->row()->teacher_code != '' || $t_query->row()->teacher_code != null) {
									if($t_query->num_rows() > 0) {
										$first_num = '';
										$position_of_first_num = '';
										$i = 0;
										for($i=0; $i<strlen($t_id); $i++) {
											if(is_numeric($t_id[$i])) {
												$first_num = $t_id[$i];
												break; 
											}
										}
										$position_of_first_num = strpos($t_id, $first_num);
										$n_tid = substr($t_id, $position_of_first_num, strlen($t_id) - $i);
										$teacher_code = $n_tid + 1;
										
										if($first_num == 0) {
											$old_len = strlen($t_id);
											$new_len = strlen($teacher_code);
											$act_len = ($old_len - $new_len);
											$teacher_code = substr($t_id, 0, $act_len).$teacher_code;
										}else {
											$teacher_code = $teacher_code;
										}
									}else{
										$teacher_code = $teacher_code_pref . $teacher_code_f;
									}
								} else {
									$teacher_code = $teacher_code_pref . $teacher_code_f;
								}
							?>
							<input type="text" id="id_field" onkeyup="verify_tid()" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" name="teacher_code" minlength="<?php echo $id_length; ?>" maxlength="<?php echo $id_length; ?>" value="<?php if($first_num == 0) { echo $teacher_code;} else {echo $teacher_code_pref.$teacher_code;} ?>" data-validate="required" required data-message-required="<?php echo get_phrase('value_required');?>">
							<button type="button" id="id_generator" class="bg-green-600 hover:bg-green-700 text-white px-6 py-5 rounded-lg transition font-semibold text-xl" onclick="generate_tid()">Validate</button>
						</div>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('qualification');?>
						</label>
						<input type="text" name="designation" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('birthday');?> <span class="text-red-600">*</span>
						</label>
						<input type="text" name="birthday" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition datepicker" data-format="dd-mm-yyyy" data-start-view="2" data-validate="required" required data-message-required="<?php echo get_phrase('date_of_birth_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('gender');?> <span class="text-red-600">*</span>
						</label>
						<select name="sex" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('gender_required');?>">
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
					<input type="text" name="address" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
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
						<small class="text-gray-600 text-base font-medium">Employee's ID with their Tier 2 provider</small>
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

			<!-- Social Links -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500"><?php echo get_phrase('social_links');?></h3>
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">Facebook</label>
						<div class="relative">
							<input type="text" name="facebook" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 pl-12 transition">
							<div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
								<i class="entypo-facebook text-gray-400 text-xl"></i>
							</div>
						</div>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">Twitter</label>
						<div class="relative">
							<input type="text" name="twitter" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 pl-12 transition">
							<div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
								<i class="entypo-twitter text-gray-400 text-xl"></i>
							</div>
						</div>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">LinkedIn</label>
						<div class="relative">
							<input type="text" name="linkedin" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 pl-12 transition">
							<div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
								<i class="entypo-linkedin text-gray-400 text-xl"></i>
							</div>
						</div>
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

			<!-- Submit Button -->
			<div class="flex justify-end gap-3 pt-6 border-t">
				<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-10 py-4 rounded-lg transition shadow-lg hover:shadow-xl text-2xl">
					<?php echo get_phrase('add_teacher');?>
				</button>
			</div>

			<?php echo form_close();?>
		</div>
	</div>
</div>

<script type="text/javascript">
	function verify_tid() {
		var id = $('#id_field').val();
		$.ajax({
			url: '<?php echo site_url('admin/get_generated_tid_verification/'); ?>' + id,
			success: function(response) {
				jQuery('#verify').html(response);
			}
		});
	}

	function generate_tid() {
		$.ajax({
			url: '<?php echo site_url('admin/get_generated_tid'); ?>',
			success: function(response) {
				jQuery('#id_field').val(response);
			}
		});
		verify_tid();
	}

	$('#teacher_add_form').submit(function(e) {
		e.preventDefault();
		
		showConfirmModal(
			'Confirm Add Teacher',
			'Are you sure you want to add this teacher?',
			function() {
				$('#modal_ajax').modal('hide');
				showAjaxModal_alert('Creating teacher...', 'loading');
				
				$.ajax({
					url: $('#teacher_add_form').attr('action'),
					type: 'POST',
					data: new FormData($('#teacher_add_form')[0]),
					cache: false,
					contentType: false,
					processData: false,
					dataType: 'text'
				}).done(function(response) {
					// Handle backend's numeric response codes
					let message = '';
					let success = false;
					
					if(response == '1') {
						message = 'Invalid email format';
					} else if(response == '2') {
						message = 'Teacher added successfully';
						success = true;
					} else if(response == '3') {
						message = 'Email already exists';
					} else if(response == '4') {
						message = 'Teacher added but SMS notification failed';
						success = true;
					} else {
						// Handle validation errors (HTML list)
						message = response;
					}
					
					showAjaxModal_alert(message, success ? 'success' : 'error');
					if(success) {
						setTimeout(() => location.reload(), 2000);
					}
				}).fail(function() {
					showAjaxModal_alert('An error occurred while adding teacher', 'error');
				});
			},
			'<?php echo get_phrase('add_teacher'); ?>',
			'info'
		);
	});
</script>
