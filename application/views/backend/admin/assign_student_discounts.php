<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><i class="fa fa-user-plus"></i> <?php echo get_phrase('assign_students_to_discount_profiles'); ?></h3>
			</div>
			<div class="panel-body">
				<?php echo form_open('discount/do_assign', array('id' => 'assign_form')); ?>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label><?php echo get_phrase('year'); ?></label>
								<input type="number" name="year" class="form-control" value="<?php echo $running_year; ?>" required>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label><?php echo get_phrase('term'); ?></label>
								<select name="term" class="form-control" required>
									<option value="1" <?php echo $running_term == 1 ? 'selected' : ''; ?>>Term 1</option>
									<option value="2" <?php echo $running_term == 2 ? 'selected' : ''; ?>>Term 2</option>
									<option value="3" <?php echo $running_term == 3 ? 'selected' : ''; ?>>Term 3</option>
								</select>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label><?php echo get_phrase('class'); ?></label>
								<select name="class_id" id="class_id" class="form-control select2" onchange="load_students(this.value)" required>
									<option value=""><?php echo get_phrase('select_class'); ?></option>
									<?php
									$classes = $this->db->get('class')->result_array();
									foreach($classes as $class):
									?>
										<option value="<?php echo $class['class_id']; ?>"><?php echo $class['name']; ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label><?php echo get_phrase('select_students'); ?></label>
								<select name="student_ids[]" id="student_ids" class="form-control select2" multiple required>
									<option value=""><?php echo get_phrase('select_class_first'); ?></option>
								</select>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label><?php echo get_phrase('select_discount_profiles'); ?></label>
								<select name="profile_ids[]" class="form-control select2" multiple required>
									<?php foreach($profiles as $profile): ?>
										<option value="<?php echo $profile['profile_id']; ?>">
											<?php echo $profile['profile_name']; ?> (<?php echo ucfirst($profile['discount_type']); ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12">
							<button type="submit" class="btn btn-primary btn-lg">
								<i class="fa fa-check"></i> <?php echo get_phrase('assign_discounts'); ?>
							</button>
							<a href="<?php echo site_url('discount/profiles'); ?>" class="btn btn-default btn-lg">
								<i class="fa fa-arrow-left"></i> <?php echo get_phrase('back'); ?>
							</a>
						</div>
					</div>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	$('.select2').select2();
	
	window.load_students = function(class_id) {
		if(!class_id) return;
		$('#student_ids').html('<option value="">Loading...</option>');
		$.ajax({
			url: '<?php echo site_url('admin/select_student/'); ?>' + class_id,
			success: function(response) {
				$('#student_ids').html(response).trigger('change');
			}
		});
	};
	
	$('#assign_form').submit(function(e) {
		e.preventDefault();
		showAjaxModal_alert('<?php echo get_phrase("assigning_discounts"); ?>...', 'loading');
		
		$.ajax({
			url: $(this).attr('action'),
			type: 'POST',
			data: $(this).serialize(),
			dataType: 'json'
		}).done(function(response) {
			if(response.status === 'success') {
				showAjaxModal_alert(response.message, 'success');
				setTimeout(() => location.href = '<?php echo site_url('discount/student_list'); ?>', 2000);
			} else {
				showAjaxModal_alert(response.message, 'error');
			}
		}).fail(function() {
			showAjaxModal_alert('<?php echo get_phrase("an_error_occurred"); ?>', 'error');
		});
	});
});
</script>
