<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('drivers'); ?></h3>
			</div>
			<div class="panel-body">
				<button class="btn btn-primary" onclick="showAjaxModal('<?php echo site_url('modal/popup/driver_add'); ?>');">
					<i class="entypo-plus"></i> <?php echo get_phrase('add_driver'); ?>
				</button>
				<br><br>
				<table class="table table-bordered datatable">
					<thead>
						<tr>
							<th><?php echo get_phrase('name'); ?></th>
							<th><?php echo get_phrase('phone'); ?></th>
							<th><?php echo get_phrase('license_number'); ?></th>
							<th><?php echo get_phrase('license_expiry'); ?></th>
							<th><?php echo get_phrase('status'); ?></th>
							<th><?php echo get_phrase('options'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($drivers as $driver): ?>
						<tr>
							<td><?php echo $driver->name; ?></td>
							<td><?php echo $driver->phone; ?></td>
							<td><?php echo $driver->license_number; ?></td>
							<td><?php echo date('d M, Y', strtotime($driver->license_expiry)); ?></td>
							<td><span class="label label-<?php echo $driver->status == 'active' ? 'success' : 'danger'; ?>"><?php echo $driver->status; ?></span></td>
							<td>
								<a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/driver_edit/'.$driver->driver_id); ?>');" class="btn btn-sm btn-info">
									<i class="entypo-pencil"></i> <?php echo get_phrase('edit'); ?>
								</a>
								<a href="#" onclick="confirm_modal('<?php echo site_url('admin/drivers/delete/'.$driver->driver_id); ?>');" class="btn btn-sm btn-danger">
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
	showCustomConfirm(
		'<?php echo get_phrase('confirm_delete'); ?>',
		function() {
			// User clicked Yes - proceed with deletion
			showAjaxModal_alert('Processing...', 'Loading', false, false);
			$.ajax({
				url: url,
				success: function(response) {
					showAjaxModal_alert('Deleted successfully.', 'Success', true, true);
				},
				error: function() {
					showAjaxModal_alert('An error occurred while deleting.', 'Error', false, true);
				}
			});
		}
		// User clicked No - do nothing
	);
}
</script>
