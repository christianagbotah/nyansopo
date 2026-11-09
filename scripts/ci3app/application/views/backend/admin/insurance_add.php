<?php 
$vehicles = $this->db->get('vehicles')->result();
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('add_insurance'); ?></h3>
			</div>
			<div class="panel-body">
				<form method="post" action="<?php echo site_url('admin/insurance/create'); ?>" class="form-horizontal form-groups-bordered validate">
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
						<label class="col-sm-3 control-label"><?php echo get_phrase('policy_number'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="policy_number" required>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('provider'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="provider">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('start_date'); ?></label>
						<div class="col-sm-9">
							<input type="date" class="form-control" name="start_date" required>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('expiry_date'); ?></label>
						<div class="col-sm-9">
							<input type="date" class="form-control" name="expiry_date" required>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('amount'); ?></label>
						<div class="col-sm-9">
							<input type="number" step="0.01" class="form-control" name="amount">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-info"><?php echo get_phrase('add_insurance'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
