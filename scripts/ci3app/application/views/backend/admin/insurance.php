<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('vehicle_insurance'); ?></h3>
			</div>
			<div class="panel-body">
				<button class="btn btn-primary" onclick="showAjaxModal('<?php echo site_url('modal/popup/insurance_add'); ?>');">
					<i class="entypo-plus"></i> <?php echo get_phrase('add_insurance'); ?>
				</button>
				<br><br>
				<table class="table table-bordered datatable">
					<thead>
						<tr>
							<th><?php echo get_phrase('vehicle'); ?></th>
							<th><?php echo get_phrase('policy_number'); ?></th>
							<th><?php echo get_phrase('provider'); ?></th>
							<th><?php echo get_phrase('start_date'); ?></th>
							<th><?php echo get_phrase('expiry_date'); ?></th>
							<th><?php echo get_phrase('amount'); ?></th>
							<th><?php echo get_phrase('status'); ?></th>
							<th><?php echo get_phrase('options'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($insurance as $ins): ?>
						<tr>
							<td><?php echo $ins->vehicle_number; ?></td>
							<td><?php echo $ins->policy_number; ?></td>
							<td><?php echo $ins->provider; ?></td>
							<td><?php echo date('d M, Y', strtotime($ins->start_date)); ?></td>
							<td><?php echo date('d M, Y', strtotime($ins->expiry_date)); ?></td>
							<td>GHS <?php echo number_format($ins->amount, 2); ?></td>
							<td><span class="label label-<?php echo $ins->status == 'active' ? 'success' : 'danger'; ?>"><?php echo $ins->status; ?></span></td>
							<td>
								<a href="#" onclick="confirm_modal('<?php echo site_url('admin/insurance/delete/'.$ins->insurance_id); ?>');" class="btn btn-sm btn-danger">
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
