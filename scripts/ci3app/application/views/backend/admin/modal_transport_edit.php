<?php 
$transport = $this->db->get_where('transport', array('transport_id' => $param2))->row();
?>
<style>
.transport-edit-modal label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    display: block;
}
.transport-edit-modal input,
.transport-edit-modal textarea {
    font-size: 15px;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    width: 100%;
    transition: all 0.3s;
}
.transport-edit-modal input:focus,
.transport-edit-modal textarea:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.transport-edit-modal button[type="submit"] {
    font-size: 14px;
    font-weight: 600;
    padding: 12px 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    color: white;
    border: none;
    margin-top: 20px;
}
</style>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-primary">
			<div class="panel-heading" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
				<h3 class="panel-title" style="color: white; font-weight: 700;"><?php echo get_phrase('edit_transport_route'); ?></h3>
			</div>
			<div class="panel-body transport-edit-modal">
				<?php echo form_open('', array('id' => 'form_transport_edit')); ?>
					<div class="row">
						<div class="col-md-6">
							<label><?php echo get_phrase('route_name'); ?> *</label>
							<input type="text" name="route_name" value="<?php echo $transport->route_name; ?>" required>
						</div>
						<div class="col-md-6">
							<label><?php echo get_phrase('number_of_vehicle'); ?> *</label>
							<input type="text" name="number_of_vehicle" value="<?php echo $transport->number_of_vehicle; ?>" required placeholder="e.g. GH-1234-20">
						</div>
						<div class="col-md-6" style="margin-top: 20px;">
							<label><?php echo get_phrase('route_fare'); ?> *</label>
							<input type="number" step="0.01" name="route_fare" value="<?php echo $transport->route_fare; ?>" required min="0">
						</div>
						<div class="col-md-6" style="margin-top: 20px;">
							<label><?php echo get_phrase('description'); ?></label>
							<textarea name="description" rows="1" style="resize: vertical; min-height: 46px;"><?php echo $transport->description; ?></textarea>
						</div>
					</div>
					<button type="submit"><?php echo get_phrase('update_route'); ?></button>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</div>

<script>
	$('#form_transport_edit').submit(function(e) {
		e.preventDefault();
		showAjaxModal_alert('Updating...', 'loading');
		$.ajax({
			url: '<?php echo site_url('admin/transport/do_update/'.$param2); ?>',
			type: 'POST',
			data: $(this).serialize(),
			dataType: 'json'
		}).done(function(response) {
			if(response.status === 'success') {
				$('#modal_ajax').modal('hide');
				$('#transport_table').DataTable().ajax.reload(null, false);
				showAjaxModal_alert(response.message, 'success', false);
			} else {
				showAjaxModal_alert(response.message || 'Operation failed', 'error');
			}
		}).fail(function() {
			showAjaxModal_alert('An error occurred', 'error');
		});
	});
</script>
