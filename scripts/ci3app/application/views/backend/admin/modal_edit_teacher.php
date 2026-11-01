<?php foreach($edit_data as $row):?>
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
			<?php echo form_open(site_url('admin/teacher/do_update/'.$row['teacher_id']) , array('class' => 'validate', 'enctype' => 'multipart/form-data', 'id' => 'teacher_edit_form'));?>

			<!-- Profile Image Section -->
			<div class="mb-8 flex justify-center">
				<div>
					<div class="border-4 border-gray-200 rounded-full overflow-hidden mb-4" style="width: 150px; height: 150px;">
						<img id="blah" src="<?php echo $this->crud_model->get_image_url('teacher',$row['teacher_id'], $row['sex']);?>" alt="Teacher Image" class="object-cover w-full h-full">
					</div>
					<div class="flex gap-2 justify-center">
						<label class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg cursor-pointer transition">
							Change Photo
							<input type="file" name="userfile" id="imgInp" accept="image/*" class="hidden">
						</label>
					</div>
				</div>
			</div>

			<!-- Personal Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Personal Information</h3>
				
				<div class="mb-4">
					<label class="block mb-2.5 text-xl font-bold text-gray-700">
						<?php echo get_phrase('name');?> <span class="text-red-600">*</span>
					</label>
					<input type="text" name="name" value="<?php echo $row['name'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" required>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('birthday');?> <span class="text-red-600">*</span>
						</label>
						<input type="text" name="birthday" value="<?php echo $row['birthday'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition datepicker" data-format="dd-mm-yyyy" data-start-view="2" required>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('sex');?> <span class="text-red-600">*</span>
						</label>
						<select name="sex" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" required>
							<option value="male" <?php if($row['sex'] == 'male')echo 'selected';?>><?php echo get_phrase('male');?></option>
							<option value="female" <?php if($row['sex'] == 'female')echo 'selected';?>><?php echo get_phrase('female');?></option>
						</select>
					</div>
				</div>

				<div class="mb-4">
					<label class="block mb-2.5 text-xl font-bold text-gray-700">
						<?php echo get_phrase('address');?>
					</label>
					<input type="text" name="address" value="<?php echo $row['address'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition">
				</div>
			</div>

			<!-- Contact Information -->
			<div class="mb-6">
				<h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Contact Information</h3>
				
				<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('phone');?> <span class="text-red-600">*</span>
						</label>
						<input type="tel" name="phone[]" value="<?php echo $row['phone'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" required>
					</div>

					<div>
						<label class="block mb-2.5 text-xl font-bold text-gray-700">
							<?php echo get_phrase('email');?> <span class="text-red-600">*</span>
						</label>
						<input type="email" name="email" value="<?php echo $row['email'];?>" class="bg-white border-2 border-gray-300 text-gray-900 text-2xl rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-5 transition" required>
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
<?php endforeach;?>

<script type="text/javascript">
$(document).ready(function() {
	$('#teacher_edit_form').submit(function(event) {
		event.preventDefault();
		
		// Close main modal and show loading with reduced gap
		$('#modal_ajax').modal('hide');
		showAjaxModal_alert('Updating teacher...', 'loading');

		let formUrl = $('#teacher_edit_form').attr('action');

		$.ajax({
			url: formUrl,
			type: 'POST',
			dataType: 'text',
			data: new FormData($('#teacher_edit_form')[0]),
			cache: false,
			contentType: false,
			processData: false
		})
		.done(function(response) {
			// Handle backend's numeric response codes
			let message = '';
			let success = false;
			
			if(response == '4') {
				// Code 4 = success (from line 5003 and 5007 in controller)
				message = 'Teacher updated successfully';
				success = true;
			} else if(response == '5') {
				// Code 5 = invalid email (from line 5000 in controller)
				message = 'Invalid email address';
			} else if(response.includes('image size')) {
				// Image size error (from line 4996)
				message = 'Image size too large';
			} else {
				// Validation errors (HTML list)
				message = response;
			}
			
			showAjaxModal_alert(message, success ? 'success' : 'error');
			if(success) {
				setTimeout(() => location.reload(), 2000);
			}
		})
		.fail(function(xhr, status, error) {
			showAjaxModal_alert('Error updating teacher: ' + error, 'error');
		});
	});
});
</script>