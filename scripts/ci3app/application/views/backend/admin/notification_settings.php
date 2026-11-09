<style>
.settings-card {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.settings-header {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    color: #fff;
}

.settings-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 600;
}

.settings-header p {
    margin: 0;
    opacity: 0.9;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
    color: #334155;
}

.phone-display {
    padding: 0.75rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-size: 1rem;
    display: inline-block;
    min-width: 250px;
}

.phone-display i {
    margin-right: 0.5rem;
    color: #6366f1;
}

.warning-message {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
    color: #92400e;
}

.warning-message i {
    margin-right: 0.5rem;
    color: #f59e0b;
}

.switch-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: 0.4s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #10b981;
}

input:focus + .slider {
    box-shadow: 0 0 1px #10b981;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.switch-label {
    font-weight: 500;
    color: #475569;
    font-size: 1rem;
}

.btn-save {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1rem;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-save i {
    margin-right: 0.5rem;
}

.info-text {
    color: #64748b;
    font-size: 0.875rem;
    margin-top: 0.5rem;
    line-height: 1.5;
}

.phone-edit-link {
    margin-left: 1rem;
    color: #6366f1;
    text-decoration: none;
    font-size: 0.875rem;
}

.phone-edit-link:hover {
    text-decoration: underline;
}
</style>

<div class="settings-header">
    <h2><i class="fa fa-bell"></i> Notification Settings</h2>
    <p>Manage your notification preferences for payroll approval notifications</p>
</div>

<?php 
// Get current user's phone number and SMS preference
$user_id = $this->session->userdata('admin_id');

// Load the User_notification_preferences model
$this->load->model('User_notification_preferences');

// Get phone number from admin table
$this->db->select('phone');
$this->db->where('admin_id', $user_id);
$user = $this->db->get('admin')->row();
$phone_number = ($user && !empty($user->phone)) ? $user->phone : null;

// Get SMS preference
$sms_enabled = $this->User_notification_preferences->get_user_sms_preference($user_id);
?>

<?php echo form_open('admin/update_notification_preferences', ['id' => 'notification_settings_form']); ?>

<div class="settings-card">
    <h3><i class="fa fa-mobile" style="color: #6366f1;"></i> SMS Notifications</h3>
    <p class="info-text">
        Receive SMS notifications for payroll approval events including submissions, approvals, rejections, and payment confirmations.
    </p>
    
    <div class="form-group" style="margin-top: 1.5rem;">
        <label>SMS Notification Status</label>
        <div class="switch-container">
            <label class="switch">
                <input type="checkbox" 
                       name="sms_enabled" 
                       id="sms_enabled_toggle" 
                       value="1" 
                       <?php echo $sms_enabled ? 'checked' : ''; ?>
                       <?php echo empty($phone_number) ? 'disabled' : ''; ?>>
                <span class="slider"></span>
            </label>
            <span class="switch-label" id="switch_label">
                <?php echo $sms_enabled ? 'Enabled' : 'Disabled'; ?>
            </span>
        </div>
    </div>
    
    <div class="form-group">
        <label>Your Phone Number</label>
        <div>
            <?php if (!empty($phone_number)): ?>
                <div class="phone-display">
                    <i class="fa fa-phone"></i>
                    <strong><?php echo htmlspecialchars($phone_number); ?></strong>
                </div>
                <a href="<?php echo site_url('admin/manage_profile'); ?>" class="phone-edit-link">
                    <i class="fa fa-edit"></i> Edit Phone Number
                </a>
            <?php else: ?>
                <div class="warning-message">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Add a phone number to your profile to enable SMS notifications.</strong>
                    <br>
                    <a href="<?php echo site_url('admin/manage_profile'); ?>" style="color: #92400e; text-decoration: underline;">
                        Click here to update your profile
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <p class="info-text">
            SMS notifications will be sent to this number. Make sure it's a valid mobile number.
        </p>
    </div>
    
    <?php if (!empty($phone_number)): ?>
    <div class="form-group" style="margin-top: 2rem;">
        <button type="submit" class="btn-save">
            <i class="fa fa-save"></i> Save Preferences
        </button>
    </div>
    <?php endif; ?>
</div>

<?php echo form_close(); ?>

<script>
$(document).ready(function() {
    // Toggle label text when switch changes
    $('#sms_enabled_toggle').change(function() {
        if ($(this).is(':checked')) {
            $('#switch_label').text('Enabled');
        } else {
            $('#switch_label').text('Disabled');
        }
    });
    
    // Form submission
    $('#notification_settings_form').submit(function(e) {
        e.preventDefault();
        
        // Show loading message
        showAjaxModal_alert('Saving preferences...', 'loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json'
        }).done(function(response) {
            if (response.success === true) {
                showAjaxModal_alert(response.message, 'success');
            } else {
                showAjaxModal_alert(response.message || 'Failed to save preferences', 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('An error occurred while saving preferences', 'error');
        });
    });
});
</script>
