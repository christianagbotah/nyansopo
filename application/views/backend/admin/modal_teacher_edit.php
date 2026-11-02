<style type="text/css">
	.validate-has-error { color: red; }
</style>

<?php
$edit_data = $this->db->get_where('teacher' , array('teacher_id' => $param2) )->result_array();
foreach ( $edit_data as $row):
	$links_json = $row['social_links'];
	$links = json_decode($links_json);

	$file_path = 'uploads/teacher_image/'.$row['teacher_id'].'.jpg';

	if(!file_exists($file_path)) {
		$file_path = 'uploads/teacher_image/teacher.jpg';
	}

	// Split name into first, other, and last name
	$name_parts = explode(' ', $row['name']);
	$first_name = $name_parts[0];
	$last_name = isset($name_parts[count($name_parts)-1]) && count($name_parts) > 1 ? $name_parts[count($name_parts)-1] : '';
?>
<div class="max-w-7xl mx-auto">
	<div class="bg-white rounded-lg shadow-lg overflow-hidden">
		<div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
			<h2 class="text-2xl font-bold text-white flex items-center gap-2">
				<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
				</svg>
				<?php echo get_phrase('edit_teacher');?>
			</h2>
		</div>

		<div class="p-6">
			<?php echo form_open(site_url('admin/teacher/do_update/'.$row['teacher_id']) , array('class' => 'validate','target'=>'_top', 'enctype' => 'multipart/form-data', 'id' => 'teacher_edit_form'));?>

			<!-- Profile Image Section -->
			<div class="mb-8 flex justify-center">
				<div class="fileinput fileinput-new" data-provides="fileinput">
					<div class="fileinput-new thumbnail border-4 border-gray-200 rounded-full overflow-hidden" style="width: 150px; height: 150px;" data-trigger="fileinput">
						<img src="<?= base_url().$file_path;?>" alt="Teacher" class="object-cover w-full h-full">
					</div>
					<div class="fileinput-preview fileinput-exists thumbnail border-4 border-green-500 rounded-full overflow-hidden" style="width: 150px; height: 150px;"></div>
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
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('first_name');?> <span class="text-red-500">*</span>
						</label>
						<input type="text" name="first_name" value="<?= $first_name; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('first_name_required');?>" autofocus>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('other_name');?>
						</label>
						<input type="text" name="other_name" value="<?= isset($name_parts[2]) ? $name_parts[2] : ''; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('last_name');?> <span class="text-red-500">*</span>
						</label>
						<input type="text" name="last_name" value="<?= $last_name; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('last_name_required');?>">
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700" id="verify">
							Staff ID <span class="text-red-500">*</span>
						</label>
						<input type="text" id="id_field" disabled class="bg-gray-100 border-2 border-gray-300 text-gray-500 text-2xl rounded-lg block w-full px-4 py-5 cursor-not-allowed" name="teacher_code" value="<?= $row['teacher_code'];?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('qualification');?>
						</label>
						<input type="text" name="designation" value="<?= $row['designation']; ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
					</div>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('birthday');?> <span class="text-red-500">*</span>
						</label>
						<input type="text" name="birthday" value="<?= date('d-m-Y', strtotime($row['birthday'])); ?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition datepicker" data-format="dd-mm-yyyy" data-start-view="2" data-validate="required" required data-message-required="<?php echo get_phrase('date_of_birth_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('gender');?> <span class="text-red-500">*</span>
						</label>
						<select name="sex" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('gender_required');?>">
							<option value=""><?php echo get_phrase('select');?></option>
							<option value="male" <?= $row['sex'] == 'Male' ? 'selected' : ''; ?>><?php echo get_phrase('male');?></option>
							<option value="female" <?= $row['sex'] == 'Female' ? 'selected' : ''; ?>><?php echo get_phrase('female');?></option>
						</select>
					</div>
				</div>

				<div class="mb-4">
					<label class="block mb-2.5 text-xl font-bold text-gray-700">
						<?php echo get_phrase('address');?>
					</label>
					<input type="text" name="address" value="<?= $row['address'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
				</div>
			</div>

			<!-- Contact Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Contact Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('phone');?> <span class="text-red-500">*</span>
						</label>
						<input type="tel" name="phone[]" value="<?= $row['phone'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('phone_required');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('email');?> <span class="text-red-500">*</span>
						</label>
						<input type="email" name="email" value="<?= $row['email'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('email_required');?>">
					</div>
				</div>
			</div>

			<!-- Identification -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Identification</h3>
				
				<div class="grid grid-cols-1 gap-4 mb-4">
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

				<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							Tier 2 Member ID / Code
						</label>
						<input type="text" name="tier2_member_id" id="tier2_member_id" 
							   value="<?php echo isset($row['tier2_member_id']) ? $row['tier2_member_id'] : ''; ?>" 
							   class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" 
							   placeholder="Enter Tier 2 provider member ID">
						<small class="text-gray-500 text-xs">Employee's ID with their Tier 2 provider</small>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">SSNIT ID</label>
						<input type="text" name="ssnit_id" value="<?= $row['ssnit_id'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('Ghana Card');?> ID <span class="text-red-500">*</span>
						</label>
						<input type="text" name="ghana_card_id" value="<?= $row['ghana_card_id'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('ghana_card_required');?>">
					</div>
				</div>
			</div>

			<!-- Social Links -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500"><?php echo get_phrase('social_links');?></h3>
				
				<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">Facebook</label>
						<div class="relative">
							<input type="text" name="facebook" value="<?= $links[0]->facebook;?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 pl-10 transition">
							<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
								<i class="entypo-facebook text-gray-400"></i>
							</div>
						</div>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">Twitter</label>
						<div class="relative">
							<input type="text" name="twitter" value="<?= $links[0]->twitter;?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 pl-10 transition">
							<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
								<i class="entypo-twitter text-gray-400"></i>
							</div>
						</div>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">LinkedIn</label>
						<div class="relative">
							<input type="text" name="linkedin" value="<?= $links[0]->linkedin;?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 pl-10 transition">
							<div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
								<i class="entypo-linkedin text-gray-400"></i>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Account Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Account Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_number');?> <span class="text-red-500">*</span>
						</label>
						<input type="number" name="account_number" value="<?= $row['account_number'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('enter_account_number');?>">
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('account_details');?> <span class="text-red-500">*</span>
						</label>
						<textarea rows="4" name="account_details" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" data-validate="required" required data-message-required="<?php echo get_phrase('enter_account_details');?>"><?= trim($row['account_details']);?></textarea>
					</div>
				</div>
			</div>

			<!-- Submit Button -->
			<div class="flex justify-end gap-3 pt-6 border-t">
				<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-10 py-4 rounded-lg transition shadow-lg hover:shadow-xl text-2xl">
					<?php echo get_phrase('update_teacher');?>
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
	$('#teacher_edit_form').submit(function(event) {
		event.preventDefault();
		showAjaxModal_alert('Updating teacher...', 'loading');

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
			if(data == 5) {
				showAjaxModal_alert('<?=get_phrase('Could Not Update Your Data. Invalid Email Found!'); ?>', 'error');
				setTimeout(() => {
					$('#modal_alert .close').click();
				}, 5000);
			} else if(data == 'image size') {
				showAjaxModal_alert('<?=get_phrase('image_size_must_not_be_more_than_1_mB.'); ?>', 'success');
				setTimeout(() => {
					$('.close').click();
				}, 5000);
			} else if(data == 4) {
				showAjaxModal_alert('<?=get_phrase('selected_teacher_updated_successfully'); ?>', 'success');
				navigation('<?php echo site_url('admin/teacher'); ?>');
				setTimeout(() => {
					$('.close').click();
				}, 5000);
			} else if(data == 3) {
				showAjaxModal_alert('<?=get_phrase('this_email_id_is_not_available'); ?>', 'error');
				setTimeout(() => {
					$('#modal_alert .close').click();
				}, 5000);
			} else {
				showAjaxModal_alert(data, 'error');
			}
		});
	});
</script>

