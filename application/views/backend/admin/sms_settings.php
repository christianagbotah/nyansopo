<style>
.modern-card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
.modern-header { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
.modern-header i { color: #3b82f6; font-size: 28px; }
.settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; }
.setting-card { background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; padding: 24px; transition: all 0.3s; }
.setting-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); }
.setting-card.active { border-color: #10b981; background: #f0fdf4; }
.setting-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
.setting-desc { font-size: 13px; color: #6b7280; margin-bottom: 16px; line-height: 1.6; }
.badge-active { background: #10b981; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.form-modern { margin-top: 20px; }
.form-modern label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 8px; display: block; }
.form-modern input, .form-modern select { border: 2px solid #e5e7eb; border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.3s; width: 100%; height: 46px; }
.form-modern input:focus, .form-modern select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
.btn-modern { padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; }
.btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary-modern:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); }
.toggle-switch { position: relative; display: inline-block; width: 60px; height: 30px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 30px; }
.toggle-slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .toggle-slider { background-color: #10b981; }
input:checked + .toggle-slider:before { transform: translateX(30px); }
.divider { height: 1px; background: #e5e7eb; margin: 30px 0; }
</style>

<?php
$active_sms_service = $this->db->get_where('settings', ['type' => 'active_sms_service'])->row()->description;
$send_attendance_sms = $this->db->get_where('settings', ['type' => 'send_attendance_sms'])->row()->description ?? '0';
?>

<div class="modern-card">
	<div class="modern-header">
		<i class="fa fa-comment"></i>
		SMS Configuration
	</div>
	
	<div class="settings-grid">
		<!-- SMS Service Selection -->
		<div class="setting-card <?php echo $active_sms_service != 'disabled' && $active_sms_service != '' ? 'active' : ''; ?>">
			<div class="setting-title">
				<i class="fa fa-cog" style="color: #3b82f6;"></i>
				SMS Service Provider
				<?php if($active_sms_service == 'hubtel'): ?>
					<span class="badge-active">ACTIVE</span>
				<?php endif; ?>
			</div>
			<div class="setting-desc">Select and configure your SMS service provider for sending messages</div>
			
			<div class="form-modern">
				<label>Active Service</label>
				<select id="active_service_select" class="form-control">
					<option value="hubtel" <?php echo $active_sms_service == 'hubtel' ? 'selected' : ''; ?>>Hubtel SMS</option>
					<option value="disabled" <?php echo $active_sms_service == 'disabled' || $active_sms_service == '' ? 'selected' : ''; ?>>Disabled</option>
				</select>
			</div>
		</div>
		
		<!-- Attendance SMS Toggle -->
		<div class="setting-card <?php echo $send_attendance_sms == '1' ? 'active' : ''; ?>">
			<div class="setting-title">
				<i class="fa fa-bell" style="color: #10b981;"></i>
				Attendance Notifications
				<?php if($send_attendance_sms == '1'): ?>
					<span class="badge-active">ENABLED</span>
				<?php endif; ?>
			</div>
			<div class="setting-desc">Automatically send SMS to guardians when attendance is taken with payment details</div>
			
			<div style="margin-top: 20px;">
				<label class="toggle-switch">
					<input type="checkbox" id="attendance_sms_toggle" <?php echo $send_attendance_sms == '1' ? 'checked' : ''; ?>>
					<span class="toggle-slider"></span>
				</label>
				<span style="margin-left: 12px; font-size: 14px; color: #6b7280;">
					<?php echo $send_attendance_sms == '1' ? 'Enabled' : 'Disabled'; ?>
				</span>
			</div>
		</div>
	</div>
	
	<div class="divider"></div>
	
	<!-- Hubtel Configuration -->
	<div id="hubtel_config" style="<?php echo $active_sms_service != 'hubtel' ? 'display:none;' : ''; ?>">
		<div class="modern-header" style="font-size: 20px;">
			<i class="fa fa-wrench"></i>
			Hubtel Configuration
		</div>
		
		<?php echo form_open(site_url('admin/sms_settings/hubtel-sms'), ['class' => 'form-modern', 'id' => 'hubtel_form']); ?>
			<div class="row">
				<div class="col-md-4">
					<label>Sender Name (Max 11 characters)</label>
					<input type="text" name="hubtel_sender" maxlength="11" value="<?php echo $this->db->get_where('settings', ['type' => 'hubtel_sender'])->row()->description; ?>" required>
				</div>
				<div class="col-md-4">
					<label>Client ID</label>
					<input type="text" name="hubtel_client_id" value="<?php echo $this->db->get_where('settings', ['type' => 'hubtel_client_id'])->row()->description; ?>" required>
				</div>
				<div class="col-md-4">
					<label>Client Secret</label>
					<input type="password" name="hubtel_client_secret" value="<?php echo $this->db->get_where('settings', ['type' => 'hubtel_client_secret'])->row()->description; ?>" required>
				</div>
			</div>
			<div style="margin-top: 20px;">
				<button type="submit" class="btn-modern btn-primary-modern">
					<i class="fa fa-save"></i> Save Configuration
				</button>
			</div>
		<?php echo form_close(); ?>
	</div>
</div>

<script>
$('#active_service_select').change(function() {
	var service = $(this).val();
	showAjaxModal_alert('Updating...', 'loading');
	$.ajax({
		url: '<?php echo site_url('admin/sms_settings/active_service'); ?>',
		type: 'POST',
		data: {active_sms_service: service}
	}).done(function() {
		showAjaxModal_alert('Service updated successfully', 'success');
		setTimeout(() => location.reload(), 1500);
	}).fail(function() {
		showAjaxModal_alert('Update failed', 'error');
	});
});

$('#attendance_sms_toggle').change(function() {
	var value = $(this).prop('checked') ? '1' : '0';
	showAjaxModal_alert('Updating...', 'loading');
	$.ajax({
		url: '<?php echo site_url('admin/sms_settings/attendance_sms'); ?>',
		type: 'POST',
		data: {send_attendance_sms: value},
		dataType: 'json'
	}).done(function(response) {
		if(response.status === 'success') {
			showAjaxModal_alert('Setting updated successfully', 'success');
			setTimeout(() => location.reload(), 1500);
		} else {
			showAjaxModal_alert('Update failed', 'error');
		}
	}).fail(function() {
		showAjaxModal_alert('An error occurred', 'error');
	});
});

$('#hubtel_form').submit(function(e) {
	e.preventDefault();
	showAjaxModal_alert('Saving configuration...', 'loading');
	$.ajax({
		url: $(this).attr('action'),
		type: 'POST',
		data: new FormData(this),
		cache: false,
		contentType: false,
		processData: false,
		dataType: 'json'
	}).done(function(data) {
		if(data.message == 1) {
			showAjaxModal_alert('Configuration saved successfully', 'success');
			setTimeout(() => location.reload(), 2000);
		} else {
			showAjaxModal_alert(data.message || 'Save failed', 'error');
		}
	}).fail(function() {
		showAjaxModal_alert('An error occurred', 'error');
	});
});
</script>
