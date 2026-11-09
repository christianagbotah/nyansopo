<?php
$setup_status = $this->setup_status ?? [];
$config = $this->config ?? [];
$tables_status = $this->tables_status ?? [];
?>

<style>
.sync-setup { padding: 20px; max-width: 1200px; margin: 0 auto; }
.setup-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
.setup-header h1 { margin: 0 0 10px 0; font-size: 28px; }
.setup-header p { margin: 0; opacity: 0.9; }

.setup-progress { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
.progress-step { flex: 1; min-width: 180px; background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.progress-step.completed { border-left: 4px solid #10b981; }
.progress-step.pending { border-left: 4px solid #f59e0b; }
.progress-step.error { border-left: 4px solid #ef4444; }
.step-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 18px; }
.progress-step.completed .step-icon { background: #d1fae5; color: #10b981; }
.progress-step.pending .step-icon { background: #fef3c7; color: #f59e0b; }
.progress-step.error .step-icon { background: #fee2e2; color: #ef4444; }
.step-title { font-weight: 600; color: #1f2937; margin-bottom: 5px; }
.step-status { font-size: 13px; color: #6b7280; }

.setup-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px; }
.card-title { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.card-title i { color: #667eea; }

.form-group { margin-bottom: 20px; }
.form-label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; }
.form-control { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
.form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }

.btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); color: white; }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; color: #374151; }
.btn-success { background: #10b981; color: white; }
.btn-warning { background: #f59e0b; color: white; }

.api-key-display { background: #f3f4f6; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px; word-break: break-all; position: relative; }
.copy-btn { position: absolute; top: 10px; right: 10px; background: #667eea; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }

.tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
.table-item { background: #f9fafb; padding: 15px; border-radius: 8px; border: 1px solid #e5e7eb; }
.table-name { font-weight: 600; color: #1f2937; margin-bottom: 8px; }
.table-meta { font-size: 12px; color: #6b7280; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-info { background: #dbeafe; color: #1e40af; }

.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
.alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
.alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
.alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
    .setup-progress { flex-direction: column; }
}
</style>

<div class="sync-setup">
    <!-- Header -->
    <div class="setup-header">
        <h1><i class="fa fa-cogs"></i> Sync Setup Wizard</h1>
        <p>Configure your multi-location bidirectional sync system</p>
    </div>

    <!-- Progress Steps -->
    <div class="setup-progress">
        <div class="progress-step <?php echo $setup_status['database_tables'] ? 'completed' : 'error'; ?>">
            <div class="step-icon"><i class="fa fa-database"></i></div>
            <div class="step-title">Database Tables</div>
            <div class="step-status"><?php echo $setup_status['database_tables'] ? 'Ready' : 'Missing tables'; ?></div>
        </div>
        <div class="progress-step <?php echo $setup_status['api_key'] ? 'completed' : 'pending'; ?>">
            <div class="step-icon"><i class="fa fa-key"></i></div>
            <div class="step-title">API Key</div>
            <div class="step-status"><?php echo $setup_status['api_key'] ? 'Configured' : 'Not set'; ?></div>
        </div>
        <div class="progress-step <?php echo $setup_status['remote_config'] ? 'completed' : 'pending'; ?>">
            <div class="step-icon"><i class="fa fa-server"></i></div>
            <div class="step-title">Remote Server</div>
            <div class="step-status"><?php echo $setup_status['remote_config'] ? 'Configured' : 'Not set'; ?></div>
        </div>
        <div class="progress-step <?php echo $setup_status['device_id'] ? 'completed' : 'pending'; ?>">
            <div class="step-icon"><i class="fa fa-desktop"></i></div>
            <div class="step-title">Device ID</div>
            <div class="step-status"><?php echo $setup_status['device_id'] ? 'Set' : 'Not set'; ?></div>
        </div>
        <div class="progress-step <?php echo $setup_status['sync_metadata'] ? 'completed' : 'pending'; ?>">
            <div class="step-icon"><i class="fa fa-list-alt"></i></div>
            <div class="step-title">Sync Tables</div>
            <div class="step-status"><?php echo $setup_status['sync_metadata'] ? 'Initialized' : 'Empty'; ?></div>
        </div>
    </div>

    <?php if ($setup_status['complete']): ?>
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i>
        <span><strong>All set!</strong> Your sync system is fully configured. <a href="<?php echo site_url('sync_server/dashboard'); ?>">Go to Sync Dashboard</a></span>
    </div>
    <?php endif; ?>

    <!-- API Key Configuration -->
    <div class="setup-card">
        <div class="card-title"><i class="fa fa-key"></i> API Key Configuration</div>
        
        <?php if (!empty($config['sync_api_key'])): ?>
        <div class="form-group">
            <label class="form-label">Current API Key</label>
            <div class="api-key-display">
                <span id="api-key-value"><?php echo htmlspecialchars($config['sync_api_key']); ?></span>
                <button class="copy-btn" onclick="copyApiKey()"><i class="fa fa-copy"></i> Copy</button>
            </div>
            <small style="color: #6b7280;">Use this key in the X-Sync-API-Key header for API requests</small>
        </div>
        <?php endif; ?>
        
        <button class="btn btn-warning" onclick="generateApiKey()">
            <i class="fa fa-refresh"></i> <?php echo !empty($config['sync_api_key']) ? 'Regenerate API Key' : 'Generate API Key'; ?>
        </button>
    </div>

    <!-- Remote Server Configuration -->
    <div class="setup-card">
        <div class="card-title"><i class="fa fa-server"></i> Remote Server Configuration</div>
        
        <form id="remote-config-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Remote Host</label>
                    <input type="text" class="form-control" name="host" value="<?php echo htmlspecialchars($config['remote_db_host'] ?? ''); ?>" placeholder="remote-server.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Port</label>
                    <input type="text" class="form-control" name="port" value="<?php echo htmlspecialchars($config['remote_db_port'] ?? '3306'); ?>" placeholder="3306">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Database Name</label>
                    <input type="text" class="form-control" name="database" value="<?php echo htmlspecialchars($config['remote_db_name'] ?? ''); ?>" placeholder="school_db">
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="user" value="<?php echo htmlspecialchars($config['remote_db_user'] ?? ''); ?>" placeholder="sync_user">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" value="<?php echo htmlspecialchars($config['remote_db_pass'] ?? ''); ?>" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label class="form-label">Sync Frequency (hours)</label>
                    <input type="number" class="form-control" name="sync_frequency" value="<?php echo htmlspecialchars($config['sync_frequency_hours'] ?? '2'); ?>" min="1" max="24">
                </div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="button" class="btn btn-secondary" onclick="testConnection()">
                    <i class="fa fa-plug"></i> Test Connection
                </button>
                <button type="button" class="btn btn-primary" onclick="saveConfig()">
                    <i class="fa fa-save"></i> Save Configuration
                </button>
            </div>
        </form>
        
        <div id="connection-result" style="margin-top: 20px;"></div>
    </div>

    <!-- Device Configuration -->
    <div class="setup-card">
        <div class="card-title"><i class="fa fa-desktop"></i> Device Configuration</div>
        
        <form id="device-config-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Device ID</label>
                    <input type="text" class="form-control" name="device_id" value="<?php echo htmlspecialchars($config['device_id'] ?? 'loc-' . substr(md5(uniqid()), 0, 12)); ?>" placeholder="loc-branch-001">
                    <small style="color: #6b7280;">Unique identifier for this location</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Device Name</label>
                    <input type="text" class="form-control" name="device_name" value="<?php echo htmlspecialchars($config['device_name'] ?? 'Main School Server'); ?>" placeholder="Main School Server">
                </div>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="sync_enabled" <?php echo ($config['sync_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                    <span>Enable automatic sync</span>
                </label>
            </div>
            
            <button type="button" class="btn btn-primary" onclick="saveDeviceConfig()">
                <i class="fa fa-save"></i> Save Device Settings
            </button>
        </form>
    </div>

    <!-- Initialize Sync Metadata -->
    <?php if (!$setup_status['sync_metadata']): ?>
    <div class="setup-card">
        <div class="card-title"><i class="fa fa-list-alt"></i> Initialize Sync Tables</div>
        <p style="color: #6b7280; margin-bottom: 20px;">The sync_metadata table is empty. Initialize it with all sync-enabled tables.</p>
        <button class="btn btn-success" onclick="initializeMetadata()">
            <i class="fa fa-magic"></i> Initialize Sync Metadata
        </button>
    </div>
    <?php endif; ?>

    <!-- Tables Status -->
    <?php if (!empty($tables_status)): ?>
    <div class="setup-card">
        <div class="card-title"><i class="fa fa-table"></i> Sync Tables Status (<?php echo count($tables_status); ?> tables)</div>
        <div class="tables-grid">
            <?php foreach ($tables_status as $table): ?>
            <div class="table-item">
                <div class="table-name"><?php echo htmlspecialchars($table['table_name']); ?></div>
                <div class="table-meta">
                    <span class="badge <?php echo $table['sync_enabled'] ? 'badge-success' : 'badge-warning'; ?>">
                        <?php echo $table['sync_enabled'] ? 'Enabled' : 'Disabled'; ?>
                    </span>
                    <span class="badge badge-info"><?php echo $table['conflict_strategy']; ?></span>
                    <?php if ($table['real_time_sync']): ?>
                    <span class="badge badge-success">Real-time</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Maintenance -->
    <div class="setup-card">
        <div class="card-title"><i class="fa fa-wrench"></i> Maintenance</div>
        <p style="color: #6b7280; margin-bottom: 20px;">Run cleanup procedures to remove old sync data.</p>
        <button class="btn btn-secondary" onclick="runMaintenance()">
            <i class="fa fa-broom"></i> Run Cleanup
        </button>
    </div>
</div>

<script>
function copyApiKey() {
    const key = document.getElementById('api-key-value').textContent;
    navigator.clipboard.writeText(key).then(() => {
        alert('API key copied to clipboard!');
    });
}

function generateApiKey() {
    if (!confirm('This will generate a new API key. The old key will stop working. Continue?')) {
        return;
    }
    
    fetch('<?php echo site_url("sync_setup/generate_api_key"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message + '\n\nNew API Key: ' + data.api_key);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Request failed: ' + err));
}

function testConnection() {
    const form = document.getElementById('remote-config-form');
    const formData = new FormData(form);
    const resultDiv = document.getElementById('connection-result');
    
    resultDiv.innerHTML = '<div class="alert alert-warning"><i class="fa fa-spinner fa-spin"></i> Testing connection...</div>';
    
    fetch('<?php echo site_url("sync_setup/test_connection"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        const alertClass = data.status === 'success' ? 'alert-success' : 
                          data.status === 'warning' ? 'alert-warning' : 'alert-error';
        resultDiv.innerHTML = '<div class="alert ' + alertClass + '"><i class="fa fa-' + 
            (data.status === 'success' ? 'check-circle' : 'exclamation-triangle') + 
            '"></i> ' + data.message + '</div>';
    })
    .catch(err => {
        resultDiv.innerHTML = '<div class="alert alert-error"><i class="fa fa-times"></i> Request failed: ' + err + '</div>';
    });
}

function saveConfig() {
    const form = document.getElementById('remote-config-form');
    const formData = new FormData(form);
    
    fetch('<?php echo site_url("sync_setup/save_config"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.status === 'success') {
            location.reload();
        }
    })
    .catch(err => alert('Request failed: ' + err));
}

function saveDeviceConfig() {
    const form = document.getElementById('device-config-form');
    const formData = new FormData(form);
    
    fetch('<?php echo site_url("sync_setup/save_config"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.status === 'success') {
            location.reload();
        }
    })
    .catch(err => alert('Request failed: ' + err));
}

function initializeMetadata() {
    if (!confirm('This will initialize the sync_metadata table with all sync-enabled tables. Continue?')) {
        return;
    }
    
    fetch('<?php echo site_url("sync_setup/initialize_metadata"); ?>', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message + (data.errors ? '\nErrors: ' + data.errors.join(', ') : ''));
        if (data.status === 'success' || data.status === 'partial') {
            location.reload();
        }
    })
    .catch(err => alert('Request failed: ' + err));
}

function runMaintenance() {
    if (!confirm('This will clean up old sync data (synced items older than 7 days, logs older than 90 days). Continue?')) {
        return;
    }
    
    fetch('<?php echo site_url("sync_setup/run_maintenance"); ?>', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        let msg = data.message + '\n\n';
        for (const [key, value] of Object.entries(data.results)) {
            msg += key + ': ' + value + ' rows deleted\n';
        }
        alert(msg);
    })
    .catch(err => alert('Request failed: ' + err));
}
</script>
