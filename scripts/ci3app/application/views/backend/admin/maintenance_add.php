<?php 
$vehicles = $this->db->get('vehicles')->result();
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('add_maintenance'); ?></h3>
			</div>
			<div class="panel-body">
				<form method="post" action="<?php echo site_url('admin/maintenance/create'); ?>" class="form-horizontal form-groups-bordered validate">
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('vehicle'); ?></label>
						<div class="col-sm-9">
							<select class="form-control select2" name="vehicle_id" required>
								<option value="">Select Vehicle</option>
								<?php foreach($vehicles as $vehicle): ?>
								<option value="<?php echo $vehicle->vehicle_id; ?>"><?php echo $vehicle->vehicle_number; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('maintenance_type'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="maintenance_type" placeholder="e.g., Oil Change, Tire Replacement">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('description'); ?></label>
						<div class="col-sm-9">
							<textarea class="form-control" name="description" rows="3"></textarea>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('cost'); ?></label>
						<div class="col-sm-9">
							<input type="number" step="0.01" class="form-control" name="cost">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('maintenance_date'); ?></label>
						<div class="col-sm-9">
							<input type="date" class="form-control" name="maintenance_date" required>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('next_maintenance'); ?></label>
						<div class="col-sm-9">
							<input type="date" class="form-control" name="next_maintenance_date">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-info"><?php echo get_phrase('add_maintenance'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
