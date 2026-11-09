<div class="row">
	<?php
		echo form_open(site_url('admin/multiples/solve'), array('id' => 'multiples_form'));?>

	<div class="col-md-2">
		<div class="form-group">
			<label class="form-control-label">Number 1</label>
			<input type="number" class="form-control" name="num1" id="num1" required>
	</div>
	</div>

	<div class="col-md-2">
		<div class="form-group">
			<label class="form-control-label">Number 2</label>
			<input type="number" class="form-control" name="num2" id="num2">
	</div>
	</div>

	<div class="col-md-2">
		<div class="form-group">
			<label class="form-control-label">Number 3</label>
			<input type="number" class="form-control" name="num3" id="num3">
	</div>
	</div>

	<div class="col-md-2">
		<div class="form-group">
			<label class="form-control-label">Number 4</label>
			<input type="number" class="form-control" name="num4" id="num4">
	</div>
	</div>

	<div class="col-md-4">
		<div class="form-group">
			<input type="submit" class="btn btn-success btn-lg" value="Solve" name="submit" id="submit">
	</div>
	</div>
</form>
</div>

<div class="row">
	<div id="answers"></div>
</div>

<script type="text/javascript">
	$('#multiples_form').submit(function(event) {
		/* Act on the event */
		event.preventDefault();

		let form_data = $(this).serialize();

		$.ajax({
			url: 'multiples/solve',
			type: 'POST',
			dataType: 'html',
			data: form_data,
		})
		.done(function(response) {
			$('#answers').html(response);
		})
		.fail(function() {
			alert("error");
		});
		
	});
</script>