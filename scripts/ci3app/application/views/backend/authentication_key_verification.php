<div class="panel panel-danger" data-toggle="collapse" data-target="#auth_key_holder">
	<div class="panel-heading" >
		<h4 class="panel-title" align="center">Click and enter your Authentication Key</h4>
	</div>
	<div class="panel-body collapse" id="auth_key_holder">
		<div class="col-sm-6 col-sm-offset-3 col-xs-6 col-xs-offset-3" >
			<?php echo form_open(''); ?>
				<div class="form-group">
					<input type="text" class="form-control" name="auth_key" id="auth_key">
				</div>
				<div class="form-group">
					<input type="submit" name="submit_auth_key" id="submit_auth_key" value="Verify Authentication Key">
				</div>
			<?php echo form_close(); ?>
		</div>
	</div>
</div>