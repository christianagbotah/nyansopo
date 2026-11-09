<style>
.settings-card{background:#fff;border-radius:16px;padding:2rem;margin-bottom:1.5rem;box-shadow:0 4px 16px rgba(0,0,0,0.06)}
.settings-header{background:linear-gradient(135deg,#6366f1,#4f46e5);padding:2rem;border-radius:16px;margin-bottom:2rem;color:#fff}
.form-group{margin-bottom:1.5rem}
.form-group label{font-weight:600;margin-bottom:0.5rem;display:block;color:#334155}
.form-control{width:100%;padding:0.75rem;border:2px solid #e2e8f0;border-radius:8px;font-size:1rem}
.form-control:focus{border-color:#6366f1;outline:none}
.btn-save{background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:0.75rem 2rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.3s}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(16,185,129,0.3)}
</style>

<div class="settings-header">
    <h2><i class="fa fa-cog"></i> Alert Configuration</h2>
    <p>Configure thresholds and notification settings for financial alerts</p>
</div>

<?php echo form_open('admin/alert_settings/update', ['id' => 'alert_settings_form']); ?>
<div class="settings-card">
    <h3>Outstanding Balance Alerts</h3>
    <div class="form-group">
        <label>Amount Threshold (<?php echo $this->db->get_where('settings',['type'=>'currency'])->row()->description; ?>)</label>
        <input type="number" name="outstanding_threshold" class="form-control" value="1000" required>
    </div>
    <div class="form-group">
        <label>Days Overdue Threshold</label>
        <input type="number" name="outstanding_days" class="form-control" value="90" required>
    </div>
</div>

<div class="settings-card">
    <h3>Collection Efficiency Alerts</h3>
    <div class="form-group">
        <label>Daily Collection Target (<?php echo $this->db->get_where('settings',['type'=>'currency'])->row()->description; ?>)</label>
        <input type="number" name="collection_target" class="form-control" value="15000" required>
    </div>
    <div class="form-group">
        <label>Efficiency Threshold (%)</label>
        <input type="number" name="collection_threshold" class="form-control" value="70" min="0" max="100" required>
    </div>
</div>

<div class="settings-card">
    <h3>Reconciliation Alerts</h3>
    <div class="form-group">
        <label>Days Before Alert</label>
        <input type="number" name="reconciliation_days" class="form-control" value="7" required>
    </div>
</div>

<div class="settings-card">
    <h3>Anomaly Detection</h3>
    <div class="form-group">
        <label>Rapid Transaction Count</label>
        <input type="number" name="rapid_transaction_count" class="form-control" value="10" required>
    </div>
    <div class="form-group">
        <label>Time Window (minutes)</label>
        <input type="number" name="rapid_transaction_minutes" class="form-control" value="10" required>
    </div>
</div>

<div class="settings-card">
    <h3>Email Notifications</h3>
    <div class="form-group">
        <label>
            <input type="checkbox" name="email_notifications" value="1"> Enable Email Notifications
        </label>
    </div>
    <div class="form-group">
        <label>Notification Email Addresses (comma-separated)</label>
        <input type="text" name="notification_emails" class="form-control" placeholder="admin@school.com, finance@school.com">
    </div>
</div>

<button type="submit" class="btn-save"><i class="fa fa-save"></i> Save Settings</button>
</form>

<script>
$('#alert_settings_form').submit(function(e){
    e.preventDefault();
    showAjaxModal_alert('Saving settings...','loading');
    $.ajax({
        url:$(this).attr('action'),
        type:'POST',
        data:new FormData(this),
        cache:false,
        contentType:false,
        processData:false,
        dataType:'json'
    }).done(function(response){
        if(response.status==='success'){
            showAjaxModal_alert(response.message,'success');
        }else{
            showAjaxModal_alert(response.message||'Failed to save settings','error');
        }
    }).fail(function(){
        showAjaxModal_alert('An error occurred','error');
    });
});
</script>
