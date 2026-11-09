<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><i class="fa fa-list"></i> <?php echo get_phrase('students_with_discount_profiles'); ?></h3>
			</div>
			<div class="panel-body">
				<div class="row mb-3">
					<div class="col-md-6">
						<a href="<?php echo site_url('discount/assign_students'); ?>" class="btn btn-success">
							<i class="fa fa-user-plus"></i> <?php echo get_phrase('assign_more_students'); ?>
						</a>
						<a href="<?php echo site_url('discount/profiles'); ?>" class="btn btn-default">
							<i class="fa fa-arrow-left"></i> <?php echo get_phrase('back_to_profiles'); ?>
						</a>
					</div>
					<div class="col-md-6 text-right">
						<form method="get" class="form-inline">
							<select name="year" class="form-control">
								<?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
									<option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
								<?php endfor; ?>
							</select>
							<select name="term" class="form-control">
								<option value="1" <?php echo $term == 1 ? 'selected' : ''; ?>>Term 1</option>
								<option value="2" <?php echo $term == 2 ? 'selected' : ''; ?>>Term 2</option>
								<option value="3" <?php echo $term == 3 ? 'selected' : ''; ?>>Term 3</option>
							</select>
							<button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
						</form>
					</div>
				</div>

				<table class="table table-bordered datatable">
					<thead>
						<tr>
							<th><?php echo get_phrase('student_code'); ?></th>
							<th><?php echo get_phrase('student_name'); ?></th>
							<th><?php echo get_phrase('class'); ?></th>
							<th><?php echo get_phrase('discount_profiles'); ?></th>
							<th><?php echo get_phrase('actions'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$grouped = [];
						foreach($students as $student) {
							$key = $student['student_id'];
							if(!isset($grouped[$key])) {
								$grouped[$key] = [
									'student_id' => $student['student_id'],
									'name' => $student['name'],
									'student_code' => $student['student_code'],
									'class_id' => $student['class_id'],
									'profiles' => []
								];
							}
							$grouped[$key]['profiles'][] = [
								'profile_id' => $student['profile_id'],
								'profile_name' => $student['profile_name']
							];
						}

						foreach($grouped as $student):
							$class_name = $this->crud_model->get_type_name_by_id('class', $student['class_id']);
						?>
						<tr>
							<td><?php echo $student['student_code']; ?></td>
							<td><?php echo $student['name']; ?></td>
							<td><?php echo $class_name; ?></td>
							<td>
								<?php foreach($student['profiles'] as $profile): ?>
									<span class="label label-info"><?php echo $profile['profile_name']; ?></span>
								<?php endforeach; ?>
							</td>
							<td>
								<button class="btn btn-sm btn-danger" onclick="remove_discount(<?php echo $student['student_id']; ?>)">
									<i class="fa fa-trash"></i> <?php echo get_phrase('remove_all'); ?>
								</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
function remove_discount(student_id) {
	showConfirmModal(
		'<?php echo get_phrase("confirm_remove"); ?>',
		'<?php echo get_phrase("are_you_sure_remove_all_discounts"); ?>',
		function() {
			showAjaxModal_alert('<?php echo get_phrase("removing"); ?>...', 'loading');
			$.ajax({
				url: '<?php echo site_url('discount/remove_assignment/'); ?>' + student_id,
				type: 'GET',
				dataType: 'json'
			}).done(function(response) {
				if(response.status === 'success') {
					showAjaxModal_alert(response.message, 'success');
					setTimeout(() => location.reload(), 2000);
				} else {
					showAjaxModal_alert(response.message, 'error');
				}
			});
		},
		'<?php echo get_phrase("remove"); ?>',
		'danger'
	);
}

$(document).ready(function() {
	$('.datatable').DataTable();
});
</script>
