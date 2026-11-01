<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('vehicle_maintenance'); ?></h3>
			</div>
			<div class="panel-body">
				<button class="btn btn-primary" onclick="showAjaxModal('<?php echo site_url('modal/popup/maintenance_add'); ?>');">
					<i class="entypo-plus"></i> <?php echo get_phrase('add_maintenance'); ?>
				</button>
				<br><br>
				<table class="table table-bordered datatable">
					<thead>
						<tr>
							<th><?php echo get_phrase('vehicle'); ?></th>
							<th><?php echo get_phrase('type'); ?></th>
							<th><?php echo get_phrase('description'); ?></th>
							<th><?php echo get_phrase('cost'); ?></th>
							<th><?php echo get_phrase('date'); ?></th>
							<th><?php echo get_phrase('next_maintenance'); ?></th>
							<th><?php echo get_phrase('options'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($maintenance as $maint): ?>
						<tr>
							<td><?php echo $maint->vehicle_number; ?></td>
							<td><?php echo $maint->maintenance_type; ?></td>
							<td><?php echo $maint->description; ?></td>
							<td>GHS <?php echo number_format($maint->cost, 2); ?></td>
							<td><?php echo date('d M, Y', strtotime($maint->maintenance_date)); ?></td>
							<td><?php echo $maint->next_maintenance_date ? date('d M, Y', strtotime($maint->next_maintenance_date)) : 'N/A'; ?></td>
							<td>
								<a href="#" onclick="confirm_modal('<?php echo site_url('admin/maintenance/delete/'.$maint->maintenance_id); ?>');" class="btn btn-sm btn-danger">
									<i class="entypo-trash"></i> <?php echo get_phrase('delete'); ?>
								</a>
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
function confirm_modal(url) {
	showCustomConfirm('<?php echo get_phrase('confirm_delete'); ?>', function() {
		showAjaxModal_alert('Processing...', 'Loading', false, false);
		$.ajax({
			url: url,
			success: function(response) {
				showAjaxModal_alert('Deleted successfully', 'Success', true, true);
			},
			error: function() {
				showAjaxModal_alert('Error deleting record', 'Error', false, true);
			}
		});
	});
}
</script>
