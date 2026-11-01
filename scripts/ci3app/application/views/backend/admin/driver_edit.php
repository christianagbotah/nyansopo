<?php 
if (!isset($driver)) {
	$driver = $this->db->get_where('drivers', array('driver_id' => $param2))->row();
}
if (!isset($transports)) {
	$transports = $this->db->get('transport')->result();
}
if (!isset($vehicles)) {
	$vehicles = $this->db->get('vehicle')->result();
}
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo get_phrase('edit_driver'); ?></h3>
			</div>
			<div class="panel-body">
				<form method="post" action="<?php echo site_url('admin/drivers/update/'.$param2); ?>" class="form-horizontal form-groups-bordered validate">
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('name'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="name" value="<?php echo $driver->name; ?>" required>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('phone'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="phone" value="<?php echo $driver->phone; ?>">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('license_number'); ?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="license_number" value="<?php echo $driver->license_number; ?>" required>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('license_expiry'); ?></label>
						<div class="col-sm-9">
							<input type="date" class="form-control" name="license_expiry" value="<?php echo $driver->license_expiry; ?>">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('address'); ?></label>
						<div class="col-sm-9">
							<textarea class="form-control" name="address" rows="3"><?php echo $driver->address; ?></textarea>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label"><?php echo get_phrase('status'); ?></label>
						<div class="col-sm-9">
							<select class="form-control" name="status">
								<option value="active" <?php echo $driver->status == 'active' ? 'selected' : ''; ?>>Active</option>
								<option value="inactive" <?php echo $driver->status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-info"><?php echo get_phrase('update_driver'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
