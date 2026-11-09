<?php
$sync_status = isset($sync_status) ? $sync_status : ['internet_online' => false];
$internet_online = $sync_status['internet_online'] ?? false;
?>

<!-- Modern UI Enhancements -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-modern-ui.css?v=<?php echo time(); ?>">

<style>
.sync-settings { padding: 20px; max-width: 1200px; margin: 0 auto; }
.settings-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; position: relative; }
.settings-header h1 { margin: 0 0 10px 0; font-size: 28px; color: white; }
.settings-header p { margin: 0; opacity: 0.9; color: white; }
.offline-status-badge { position: absolute; top: 20px; right: 20px; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.offline-status-badge.online { background: rgba(16, 185, 129, 0.2); border: 2px solid rgba(16, 185, 129, 0.5); color: #10b981; }
.offline-status-badge.offline { background: rgba(239, 68, 68, 0.2); border: 2px solid rgba(239, 68, 68, 0.5); color: #ef4444; }
.offline-status-badge .status-dot { width: 8px; height: 8px; border-radius: 50%; }
.offline-status-badge.online .status-dot { background: #10b981; animation: pulse 2s infinite; }
.offline-status-badge.offline .status-dot { background: #ef4444; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.settings-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
.settings-section-title { font-size: 18px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0; display: flex; align-items: center; gap: 10px; }
.settings-section-title i { color: #667eea; }
.settings-section-desc { color: #6b7280; font-size: 14px; margin-bottom: 25px; }

.form-group-modern { margin-bottom: 25px; }
.form-label-modern { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; }
.form-label-modern i { color: #667eea; margin-right: 8px; }
.form-control-modern { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
.form-control-modern:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.form-control-modern::placeholder { color: #9ca3af; }
.form-help-text { display: block; margin-top: 6px; font-size: 13px; color: #6b7280; }

.toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 28px; }
.toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
.toggle-switch input:checked + .toggle-slider { background-color: #667eea; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }
.toggle-label { display: flex; align-items: center; gap: 12px; cursor: pointer; }

.select-modern { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E"); background-position: right 12px center; background-repeat: no-repeat; background-size: 20px; padding-right: 40px; }

.btn-modern { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.btn-primary-modern { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); color: white; }
.btn-secondary-modern { background: #f3f4f6; color: #374151; }
.btn-secondary-modern:hover { background: #e5e7eb; color: #374151; }

.alert-modern { padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px; border-left: 4px solid; }
.alert-success-modern { background: #d1fae5; border-color: #10b981; color: #065f46; }
.alert-info-modern { background: #dbeafe; border-color: #3b82f6; color: #1e40af; }
.alert-modern i { font-size: 20px; margin-top: 2px; }

.quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }
.quick-link-card { background: white; padding: 16px; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.3s; text-decoration: none; display: block; }
.quick-link-card:hover { border-color: #667eea; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); text-decoration: none; }
.quick-link-title { font-weight: 600; color: #1f2937; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
.quick-link-title i { color: #667eea; }
.quick-link-desc { font-size: 13px; color: #6b7280; margin: 0; }

.form-actions { display: flex; gap: 12px; padding-top: 20px; border-top: 2px solid #f3f4f6; margin-top: 30px; }

.input-group-modern { position: relative; }
.input-icon { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer; }
.input-icon:hover { color: #667eea; }

.form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
.form-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }

@media (max-width: 768px) {
    .sync-settings { padding: 15px; }
    .settings-header { padding: 20px; }
    .settings-card { padding: 20px; }
    .quick-links { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .form-row-3 { grid-template-columns: 1fr; }
}
</style>

<div class="sync-settings">
    <!-- Header -->
    <div class="settings-header">
        <h1><i class="fa fa-cog"></i> Sync Settings</h1>
        <p>Configure synchronization between local and cloud servers</p>
        
        <!-- Offline Status Badge -->
        <div class="offline-status-badge <?php echo $internet_online ? 'online' : 'offline'; ?>" id="offline-status-badge">
            <span class="status-dot <?php echo $internet_online ? 'online' : 'offline'; ?>"></span>
            <span><?php echo $internet_online ? 'Online' : 'Offline'; ?></span>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($this->session->flashdata('flash_message')): ?>
    <div class="alert-modern alert-success-modern">
        <i class="fa fa-check-circle"></i>
        <div>
            <strong>Success!</strong><br>
            <?php echo $this->session->flashdata('flash_message'); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Settings Form -->
    <?php echo form_open(site_url('sync_server/save_settings')); ?>
    
    <!-- General Settings Card -->
    <div class="settings-card">
        <h2 class="settings-section-title">
            <i class="fa fa-sliders-h"></i>
            General Settings
        </h2>
        <p class="settings-section-desc">Configure basic synchronization behavior and preferences</p>
        
        <!-- Enable Sync Toggle (DISABLED - Always set to NO) -->
        <div class="form-group-modern">
            <label class="toggle-label" style="opacity: 0.6; cursor: not-allowed;">
                <div class="toggle-switch">
                    <input type="checkbox" name="sync_enabled" value="1" id="sync-enabled-toggle" disabled style="cursor: not-allowed;">
                    <span class="toggle-slider" style="cursor: not-allowed;"></span>
                </div>
                <div>
                    <div class="form-label-modern" style="margin-bottom: 2px;">Enable Automatic Sync</div>
                    <span class="form-help-text" style="margin-top: 0;">Automatic sync is disabled. Use manual sync from the dashboard instead.</span>
                </div>
            </label>
        </div>
        
        <!-- Sync Frequency and Device ID Row -->
        <div class="form-row">
            <!-- Sync Frequency -->
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="fa fa-clock"></i>
                    Sync Frequency
                </label>
                <select name="sync_frequency" class="form-control-modern select-modern">
                    <option value="1" <?php echo ($sync_frequency == '1') ? 'selected' : ''; ?>>Every 1 hour</option>
                    <option value="2" <?php echo ($sync_frequency == '2') ? 'selected' : ''; ?>>Every 2 hours (Recommended)</option>
                    <option value="4" <?php echo ($sync_frequency == '4') ? 'selected' : ''; ?>>Every 4 hours</option>
                    <option value="6" <?php echo ($sync_frequency == '6') ? 'selected' : ''; ?>>Every 6 hours</option>
                    <option value="12" <?php echo ($sync_frequency == '12') ? 'selected' : ''; ?>>Every 12 hours</option>
                    <option value="24" <?php echo ($sync_frequency == '24') ? 'selected' : ''; ?>>Once daily</option>
                </select>
                <span class="form-help-text">How often to automatically sync with the cloud server</span>
            </div>
        </div>
        
        <!-- Device IDs Row -->
        <div class="form-row">
            <!-- Local Device ID -->
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="fa fa-server"></i>
                    Local Device ID
                </label>
                <input type="text" name="device_id" class="form-control-modern" value="<?php echo $device_id; ?>" required placeholder="e.g., local-wamp-main" id="local-device-id">
                <span class="form-help-text">Unique identifier for this local server instance</span>
            </div>
            
            <!-- Remote Device ID -->
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="fa fa-cloud-upload-alt"></i>
                    Remote Device ID
                </label>
                <input type="text" name="remote_device_id" class="form-control-modern" value="<?php echo isset($remote_device_id) ? $remote_device_id : ''; ?>" placeholder="e.g., remote-cpanel-server" id="remote-device-id">
                <span class="form-help-text">Unique identifier for the remote cloud server (optional, used for audit logging)</span>
            </div>
        </div>
    </div>

    <!-- Remote Server Configuration Card -->
    <div class="settings-card">
        <h2 class="settings-section-title">
            <i class="fa fa-cloud"></i>
            Remote Server Configuration
        </h2>
        <p class="settings-section-desc">Configure connection details for your cloud database server</p>
        
        <!-- Host and Port Row -->
        <div class="form-row">
            <!-- Database Host -->
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="fa fa-globe"></i>
                    Database Host
                </label>
                <input type="text" name="remote_host" class="form-control-modern" value="<?php echo $remote_host; ?>" placeholder="e.g., db.example.com or 123.45.67.89" required>
                <span class="form-help-text">Hostname or IP address of your cloud database server</span>
            </div>
            
            <!-- Database Port -->
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="fa fa-plug"></i>
                    Database Port
                </label>
                <input type="number" name="remote_port" class="form-control-modern" value="<?php echo $remote_port; ?>" placeholder="3306" required>
                <span class="form-help-text">Usually 3306 for MySQL/MariaDB databases</span>
            </div>
        </div>
        
        <!-- Database Credentials Row (3 columns) -->
        <div class="form-row-3">
            <!-- Database Name -->
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="fa fa-database"></i>
                    Database Name
                </label>
                <input type="text" name="remote_database" class="form-control-modern" value="<?php echo $remote_database; ?>" placeholder="school_db" required>
                <span class="form-help-text">Name of the database on the cloud server</span>
            </div>
            
            <!-- Database Username -->
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="fa fa-user"></i>
                    Database Username
                </label>
                <input type="text" name="remote_user" class="form-control-modern" value="<?php echo $remote_user; ?>" placeholder="sync_user" required autocomplete="off">
                <span class="form-help-text">Username for database authentication</span>
            </div>
            
            <!-- Database Password -->
            <div class="form-group-modern">
                <label class="form-label-modern">
                    <i class="fa fa-lock"></i>
                    Database Password
                </label>
                <div class="input-group-modern">
                    <input type="password" name="remote_pass" class="form-control-modern" id="remote-pass" placeholder="Enter password to change" autocomplete="new-password">
                    <i class="fa fa-eye input-icon" id="toggle-password" onclick="togglePassword()"></i>
                </div>
                <span class="form-help-text">Leave blank to keep current password unchanged</span>
            </div>
        </div>
    </div>

    <!-- Quick Links Card -->
    <div class="settings-card">
        <h2 class="settings-section-title">
            <i class="fa fa-link"></i>
            Quick Links
        </h2>
        <p class="settings-section-desc">Access related sync management tools</p>
        
        <div class="quick-links">
            <a href="<?php echo site_url('sync_server/dashboard'); ?>" class="quick-link-card">
                <div class="quick-link-title">
                    <i class="fa fa-tachometer-alt"></i>
                    Sync Dashboard
                </div>
                <p class="quick-link-desc">Monitor sync operations and view status</p>
            </a>
            
            <a href="<?php echo site_url('setup_wizard'); ?>" class="quick-link-card">
                <div class="quick-link-title">
                    <i class="fa fa-magic"></i>
                    Setup Wizard
                </div>
                <p class="quick-link-desc">Guided setup process for first-time configuration</p>
            </a>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
        <button type="submit" class="btn-modern btn-primary-modern">
            <i class="fa fa-save"></i>
            <span>Save Settings</span>
        </button>
        <a href="<?php echo site_url('sync_server/dashboard'); ?>" class="btn-modern btn-secondary-modern">
            <i class="fa fa-times"></i>
            <span>Cancel</span>
        </a>
    </div>
    
    <?php echo form_close(); ?>
</div>

<!-- Modern UI Enhancements -->
<script src="<?php echo base_url(); ?>assets/js/sync-modern-ui.js?v=<?php echo time(); ?>"></script>

<script>
// Toggle password visibility
function togglePassword() {
    const input = document.getElementById('remote-pass');
    const icon = document.getElementById('toggle-password');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Handle sync enabled toggle change - DISABLED
// The toggle is now permanently disabled and set to NO
// Users should use manual sync from the dashboard instead
/*
document.getElementById('sync-enabled-toggle').addEventListener('change', function() {
    const isEnabled = this.checked;
    const toggle = this;
    
    // Disable toggle while saving
    toggle.disabled = true;
    
    // Get CSRF token
    const csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
    const csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
    // Send AJAX request
    fetch('<?php echo site_url("sync_server/toggle_sync_enabled"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'enabled=' + (isEnabled ? '1' : '0') + '&' + csrfName + '=' + csrfHash
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success notification
            showNotification(data.message, 'success');
        } else {
            // Revert toggle on error
            toggle.checked = !isEnabled;
            showNotification(data.message || 'Failed to update setting', 'error');
        }
    })
    .catch(error => {
        // Revert toggle on error
        toggle.checked = !isEnabled;
        showNotification('Network error: Could not update setting', 'error');
        console.error('Error:', error);
    })
    .finally(() => {
        // Re-enable toggle
        toggle.disabled = false;
    });
});
*/

// Show notification function
function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '320px';
    notification.style.maxWidth = '400px';
    notification.style.padding = '16px 20px';
    notification.style.borderRadius = '10px';
    notification.style.display = 'flex';
    notification.style.alignItems = 'flex-start';
    notification.style.gap = '12px';
    notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    notification.style.animation = 'slideInRight 0.3s ease-out';
    notification.style.borderLeft = '4px solid';
    
    // Set colors based on type with !important to override any CSS
    if (type === 'success') {
        notification.style.setProperty('background', '#d1fae5', 'important'); // Light green background
        notification.style.setProperty('border-left-color', '#10b981', 'important'); // Green border
        notification.style.setProperty('color', '#065f46', 'important'); // Dark green text
    } else {
        notification.style.setProperty('background', '#fee2e2', 'important'); // Light red background
        notification.style.setProperty('border-left-color', '#ef4444', 'important'); // Red border
        notification.style.setProperty('color', '#991b1b', 'important'); // Dark red text
    }
    
    notification.innerHTML = 
        '<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '" style="font-size: 20px; margin-top: 2px;"></i>' +
        '<div style="flex: 1;">' +
            '<strong style="display: block; margin-bottom: 4px;">' + (type === 'success' ? 'Success!' : 'Error') + '</strong>' +
            '<span style="font-size: 14px;">' + message + '</span>' +
        '</div>';
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Form validation feedback
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    const deviceIdPattern = /^[a-zA-Z0-9-_]+$/;
    let isValid = true;
    
    // Validate required fields
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#ef4444';
        } else {
            field.style.borderColor = '#e5e7eb';
        }
    });
    
    // Validate Local Device ID format
    const localDeviceId = document.getElementById('local-device-id');
    if (localDeviceId && localDeviceId.value.trim()) {
        if (!deviceIdPattern.test(localDeviceId.value)) {
            isValid = false;
            localDeviceId.style.borderColor = '#ef4444';
            alert('Local Device ID can only contain letters, numbers, hyphens, and underscores');
            e.preventDefault();
            return false;
        }
    }
    
    // Validate Remote Device ID format (optional, but must match pattern if provided)
    const remoteDeviceId = document.getElementById('remote-device-id');
    if (remoteDeviceId && remoteDeviceId.value.trim() && !deviceIdPattern.test(remoteDeviceId.value)) {
        isValid = false;
        remoteDeviceId.style.borderColor = '#ef4444';
        alert('Remote Device ID can only contain letters, numbers, hyphens, and underscores');
        e.preventDefault();
        return false;
    }
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields correctly');
        return false;
    }
});
</script>
