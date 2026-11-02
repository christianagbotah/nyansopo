<?php 
if (!isset($vehicle)) {
	$vehicle = $this->db->get_where('vehicles', array('vehicle_id' => $param2))->row();
}
if (!isset($transports)) {
	$transports = $this->db->get('transport')->result();
}
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('edit_vehicle'); ?></h3>
			</div>
			<div class="panel-body">
				<form method="post" action="<?php echo site_url('admin/vehicles/update/'.$param2); ?>" class="form-horizontal form-groups-bordered validate">
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('vehicle_number'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="vehicle_number" value="<?php echo $vehicle->vehicle_number; ?>" required>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('vehicle_model'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="vehicle_model" value="<?php echo $vehicle->vehicle_model; ?>">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('route'); ?></label>
						<div class="col-sm-9">
							<select class="form-control select2" name="transport_id">
								<option value="">Select Route</option>
								<?php foreach($transports as $transport): ?>
								<option value="<?php echo $transport->transport_id; ?>" <?php echo $vehicle->transport_id == $transport->transport_id ? 'selected' : ''; ?>><?php echo $transport->route_name; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('capacity'); ?></label>
						<div class="col-sm-9">
							<input type="number" class="form-control" name="capacity" value="<?php echo $vehicle->capacity; ?>">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('status'); ?></label>
						<div class="col-sm-9">
							<select class="form-control" name="status">
								<option value="active" <?php echo $vehicle->status == 'active' ? 'selected' : ''; ?>>Active</option>
								<option value="maintenance" <?php echo $vehicle->status == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
								<option value="inactive" <?php echo $vehicle->status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-info"><?php echo get_phrase('update_vehicle'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
