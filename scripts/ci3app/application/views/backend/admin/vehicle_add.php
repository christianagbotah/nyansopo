<?php 
if (!isset($transports)) {
	$transports = $this->db->get('transport')->result();
}
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('add_vehicle'); ?></h3>
			</div>
			<div class="panel-body">
				<form method="post" action="<?php echo site_url('admin/vehicles/create'); ?>" class="form-horizontal form-groups-bordered validate">
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('vehicle_number'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="vehicle_number" required>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('vehicle_model'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="vehicle_model">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('route'); ?></label>
						<div class="col-sm-9">
							<select class="form-control select2" name="transport_id">
								<option value="">Select Route</option>
								<?php foreach($transports as $transport): ?>
								<option value="<?php echo $transport->transport_id; ?>"><?php echo $transport->route_name; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('capacity'); ?></label>
						<div class="col-sm-9">
							<input type="number" class="form-control" name="capacity">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('purchase_date'); ?></label>
						<div class="col-sm-9">
							<input type="date" class="form-control" name="purchase_date">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-info"><?php echo get_phrase('add_vehicle'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
