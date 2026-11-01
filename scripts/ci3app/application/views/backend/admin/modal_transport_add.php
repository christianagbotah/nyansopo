<style>
.transport-modal label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    display: block;
}
.transport-modal input,
.transport-modal textarea {
    font-size: 15px;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    width: 100%;
    transition: all 0.3s;
}
.transport-modal input:focus,
.transport-modal textarea:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.transport-modal .modal-footer button {
    font-size: 14px;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 8px;
}
</style>
<?php echo form_open('', array('id' => 'form_transport_add', 'class' => 'transport-modal')); ?>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6">
                <label><?php echo get_phrase('route_name'); ?> *</label>
                <input type="text" name="route_name" required>
            </div>
            <div class="col-md-6">
                <label><?php echo get_phrase('vehicle_registration_number'); ?> *</label>
                <input type="text" name="number_of_vehicle" required placeholder="e.g. GH-1234-20">
            </div>
            <div class="col-md-6" style="margin-top: 20px;">
                <label><?php echo get_phrase('route_fare'); ?> (<?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?>) *</label>
                <input type="number" name="route_fare" required step="0.01" min="0">
            </div>
            <div class="col-md-6" style="margin-top: 20px;">
                <label><?php echo get_phrase('description'); ?></label>
                <textarea name="description" rows="1" style="resize: vertical; min-height: 46px;"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
        <button type="submit" class="btn btn-primary"><?php echo get_phrase('save'); ?></button>
    </div>
<?php echo form_close(); ?>
<script>
$('#form_transport_add').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('Creating...', 'loading');
    $.ajax({
        url: '<?php echo site_url('admin/transport/create'); ?>',
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
