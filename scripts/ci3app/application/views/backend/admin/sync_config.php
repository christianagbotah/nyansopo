<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Configuration View - Modern UI/UX Redesign
 * 
 * Modern interface for managing table-level sync configuration including:
 * - Enable/disable sync per table
 * - Conflict resolution strategies
 * - Real-time sync toggles
 * - Data scope settings
 * - Search and filter capabilities
 * - Reset to defaults functionality
 * 
 * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.9, 10.10
 * Task 8.1: Sync configuration layout structure
 * 
 * @package    School Manager
 * @subpackage Views
 * @category   Sync
 */

// Get data from controller
$tables = $tables ?? [];
$global_settings = $global_settings ?? [];
$conflict_strategies = $conflict_strategies ?? [];
$data_scopes = $data_scopes ?? [];
$locations = $locations ?? [];
$total_tables = count($tables);
$enabled_tables = count(array_filter($tables, function($t) { return $t['sync_enabled']; }));
?>


<!-- Load Design System CSS -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-design-system.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-configuration-ui.css?v=<?php echo time(); ?>">

<div class="sync-config-container">
    <!-- Page Header with Gradient -->
    <div class="sync-config-header">
        <div class="sync-config-header-content">
            <h1>
                <i class="fa fa-cog"></i>
                Table Sync Configuration
            </h1>
            <p>Configure synchronization settings for each table including conflict resolution and data scope</p>
        </div>
        
        <!-- Header Actions -->
        <div class="sync-config-header-actions">
            <button class="sync-btn sync-btn-danger" id="reset-all-defaults-btn" onclick="resetAllToDefaults()">
                <i class="fa fa-undo"></i> Reset All to Defaults
            </button>
            <a href="<?php echo site_url('admin/sync_dashboard'); ?>" class="sync-btn sync-btn-ghost">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="sync-config-summary">
        <div class="sync-summary-card sync-card-info">
            <div class="sync-summary-icon">
                <i class="fa fa-table"></i>
            </div>
            <div class="sync-summary-content">
                <div class="sync-summary-label">Total Tables</div>
                <div class="sync-summary-value"><?php echo number_format($total_tables); ?></div>
            </div>
        </div>
        
        <div class="sync-summary-card sync-card-success">
            <div class="sync-summary-icon">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="sync-summary-content">
                <div class="sync-summary-label">Sync Enabled</div>
                <div class="sync-summary-value"><?php echo number_format($enabled_tables); ?></div>
            </div>
        </div>
        
        <div class="sync-summary-card sync-card-warning">
            <div class="sync-summary-icon">
                <i class="fa fa-clock"></i>
            </div>
            <div class="sync-summary-content">
                <div class="sync-summary-label">Sync Disabled</div>
                <div class="sync-summary-value"><?php echo number_format($total_tables - $enabled_tables); ?></div>
            </div>
        </div>
        
        <div class="sync-summary-card sync-card-info">
            <div class="sync-summary-icon">
                <i class="fa fa-bolt"></i>
            </div>
            <div class="sync-summary-content">
                <div class="sync-summary-label">Real-time Enabled</div>
                <div class="sync-summary-value"><?php echo number_format(count(array_filter($tables, function($t) { return $t['real_time_sync']; }))); ?></div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Controls -->
    <div class="sync-config-controls">
        <div class="sync-config-search">
            <i class="fa fa-search"></i>
            <input type="text" id="table-search" placeholder="Search tables..." class="sync-search-input">
        </div>
        
        <div class="sync-config-filters">
            <select id="filter-status" class="sync-filter-select">
                <option value="">All Status</option>
                <option value="enabled">Sync Enabled</option>
                <option value="disabled">Sync Disabled</option>
            </select>
            
            <select id="filter-realtime" class="sync-filter-select">
                <option value="">All Real-time</option>
                <option value="on">Real-time On</option>
                <option value="off">Real-time Off</option>
            </select>
            
            <select id="filter-strategy" class="sync-filter-select">
                <option value="">All Strategies</option>
                <?php foreach ($conflict_strategies as $value => $label): ?>
                <option value="<?php echo $value; ?>"><?php echo explode('(', $label)[0]; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

<style>
.sync-config-container { padding: 20px; max-width: 1400px; margin: 0 auto; }

/* Header */
.config-header { 
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); 
    color: white; 
    padding: 25px 30px; 
    border-radius: 12px; 
    margin-bottom: 25px; 
}
.config-header h2 { margin: 0 0 8px 0; font-size: 24px; }
.config-header p { margin: 0; opacity: 0.85; font-size: 14px; }

/* Global Settings Section */
.global-section { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 25px; overflow: hidden; }
.section-header { 
    padding: 15px 25px; 
    border-bottom: 1px solid #e5e7eb; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    background: #f9fafb;
}
.section-header h3 { margin: 0; font-size: 16px; color: #374151; }
.section-content { padding: 25px; }

/* Settings Grid */
.settings-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
@media (max-width: 992px) { .settings-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .settings-grid { grid-template-columns: 1fr; } }
.setting-group { margin-bottom: 15px; }
.setting-group label { 
    display: block; 
    font-size: 13px; 
    font-weight: 600; 
    color: #374151; 
    margin-bottom: 8px; 
}
.setting-group input, .setting-group select { 
    width: 100%; 
    padding: 10px 12px; 
    border: 1px solid #d1d5db; 
    border-radius: 8px; 
    font-size: 14px; 
    transition: all 0.2s;
}
.setting-group input:focus, .setting-group select:focus { 
    outline: none; 
    border-color: #3b82f6; 
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); 
}
.setting-group .hint { font-size: 12px; color: #6b7280; margin-top: 5px; }

/* Toggle Switch */
.toggle-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { 
    position: absolute; 
    cursor: pointer; 
    top: 0; 
    left: 0; 
    right: 0; 
    bottom: 0; 
    background-color: #d1d5db; 
    transition: 0.3s; 
    border-radius: 26px; 
}
.toggle-slider:before { 
    position: absolute; 
    content: ""; 
    height: 20px; 
    width: 20px; 
    left: 3px; 
    bottom: 3px; 
    background-color: white; 
    transition: 0.3s; 
    border-radius: 50%; 
}
.toggle-switch input:checked + .toggle-slider { background-color: #10b981; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }

/* Bulk Actions */
.bulk-actions { 
    padding: 15px 25px; 
    border-bottom: 1px solid #e5e7eb; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    background: #f0f9ff;
}
.bulk-actions h4 { margin: 0; font-size: 14px; color: #1e40af; }
.bulk-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.bulk-form select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }

/* Tables Section */
.tables-section { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; }
.config-table { width: 100%; border-collapse: collapse; }
.config-table th { 
    background: #f9fafb; 
    padding: 12px 15px; 
    text-align: left; 
    font-size: 12px; 
    font-weight: 600; 
    color: #6b7280; 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
    border-bottom: 1px solid #e5e7eb; 
}
.config-table td { 
    padding: 12px 15px; 
    border-bottom: 1px solid #f3f4f6; 
    font-size: 14px; 
    color: #374151; 
}
.config-table tr:hover { background: #f9fafb; }
.config-table tr:last-child td { border-bottom: none; }

/* Table Name */
.table-name { font-weight: 600; color: #1f2937; }
.table-stats { font-size: 12px; color: #6b7280; margin-top: 4px; }

/* Status Badges */
.badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-info { background: #dbeafe; color: #1e40af; }
.badge-secondary { background: #e5e7eb; color: #374151; }

/* Real-time Badge */
.realtime-badge { 
    display: inline-flex; 
    align-items: center; 
    gap: 4px; 
    padding: 4px 10px; 
    border-radius: 12px; 
    font-size: 11px; 
    font-weight: 600; 
}
.realtime-badge.active { background: #d1fae5; color: #065f46; }
.realtime-badge.inactive { background: #e5e7eb; color: #6b7280; }
.realtime-badge .dot { width: 6px; height: 6px; border-radius: 50%; }
.realtime-badge.active .dot { background: #10b981; }
.realtime-badge.inactive .dot { background: #9ca3af; }

/* Select Dropdown in Table */
.table-select { 
    padding: 6px 10px; 
    border: 1px solid #e5e7eb; 
    border-radius: 6px; 
    font-size: 12px; 
    background: white; 
    min-width: 120px;
}
.table-select:focus { outline: none; border-color: #3b82f6; }

/* Number Input in Table */
.table-input { 
    padding: 6px 10px; 
    border: 1px solid #e5e7eb; 
    border-radius: 6px; 
    font-size: 12px; 
    width: 70px; 
    text-align: center;
}
.table-input:focus { outline: none; border-color: #3b82f6; }

/* Checkbox */
.checkbox-wrapper { display: flex; align-items: center; justify-content: center; }
.checkbox-wrapper input[type="checkbox"] { 
    width: 18px; 
    height: 18px; 
    cursor: pointer; 
}

/* Buttons */
.btn { 
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 8px; 
    font-size: 14px; 
    font-weight: 500; 
    cursor: pointer; 
    transition: all 0.2s; 
    text-decoration: none;
}
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-secondary { background: #6b7280; color: white; }
.btn-secondary:hover { background: #4b5563; }
.btn-outline { background: white; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f9fafb; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn i { font-size: 14px; }

/* Action Buttons */
.action-buttons { display: flex; gap: 8px; }
.btn-save { background: #10b981; color: white; }
.btn-save:hover { background: #059669; }
.btn-reset { background: #f3f4f6; color: #6b7280; }
.btn-reset:hover { background: #e5e7eb; }

/* Alert */
.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
.alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
.alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
.alert-info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }

/* Modal */
.modal-overlay { 
    display: none; 
    position: fixed; 
    top: 0; 
    left: 0; 
    right: 0; 
    bottom: 0; 
    background: rgba(0,0,0,0.5); 
    z-index: 1000; 
    align-items: center; 
    justify-content: center; 
}
.modal-overlay.show { display: flex; }
.modal-content { background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; }
.modal-header { padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 18px; color: #1f2937; }
.modal-body { padding: 20px; }
.modal-footer { padding: 15px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px; }

/* Priority Indicator */
.priority-indicator { 
    display: inline-flex; 
    align-items: center; 
    gap: 4px; 
    padding: 4px 8px; 
    border-radius: 4px; 
    font-size: 12px; 
    font-weight: 600; 
}
.priority-high { background: #fee2e2; color: #991b1b; }
.priority-medium { background: #fef3c7; color: #92400e; }
.priority-low { background: #d1fae5; color: #065f46; }
</style>

<div class="config-container">
    <!-- Header -->
    <div class="config-header">
        <h2><i class="fa fa-cog"></i> Sync Configuration</h2>
        <p>Configure synchronization settings for each table including conflict resolution strategies and sync options</p>
    </div>

    <!-- Global Settings -->
    <div class="global-section">
        <div class="section-header">
            <h3><i class="fa fa-globe"></i> Global Sync Settings</h3>
            <button class="btn btn-primary btn-sm" onclick="saveGlobalSettings()">
                <i class="fa fa-save"></i> Save Settings
            </button>
        </div>
        <div class="section-content">
            <div class="settings-grid">
                <div class="setting-group">
                    <label>Auto Sync</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="auto_sync_enabled" <?php echo $global_settings['auto_sync_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="hint">Enable automatic synchronization</div>
                </div>
                
                <div class="setting-group">
                    <label>Sync Interval (minutes)</label>
                    <input type="number" id="sync_interval_minutes" value="<?php echo $global_settings['sync_interval_minutes']; ?>" min="1" max="1440">
                    <div class="hint">How often to sync (1-1440 minutes)</div>
                </div>
                
                <div class="setting-group">
                    <label>Max Retry Attempts</label>
                    <input type="number" id="max_retry_attempts" value="<?php echo $global_settings['max_retry_attempts']; ?>" min="1" max="10">
                    <div class="hint">Retry attempts on failure (1-10)</div>
                </div>
                
                <div class="setting-group">
                    <label>Offline Mode</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="offline_mode_enabled" <?php echo $global_settings['offline_mode_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="hint">Allow offline operations</div>
                </div>
                
                <div class="setting-group">
                    <label>Real-time Sync</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="realtime_sync_enabled" <?php echo $global_settings['realtime_sync_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="hint">Enable immediate sync for critical tables</div>
                </div>
                
                <div class="setting-group">
                    <label>Conflict Notification Email</label>
                    <input type="email" id="conflict_notification_email" value="<?php echo htmlspecialchars($global_settings['conflict_notification_email']); ?>" placeholder="admin@example.com">
                    <div class="hint">Email for conflict alerts</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="tables-section">
        <div class="bulk-actions">
            <h4><i class="fa fa-tasks"></i> Bulk Update Selected Tables</h4>
            <div class="bulk-form">
                <select id="bulk-conflict-strategy">
                    <option value="">Conflict Strategy...</option>
                    <?php foreach ($conflict_strategies as $value => $label): ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="bulk-data-scope">
                    <option value="">Data Scope...</option>
                    <?php foreach ($data_scopes as $value => $label): ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="bulk-batch-size" placeholder="Batch Size" min="1" max="1000" style="width: 100px; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                <button class="btn btn-primary btn-sm" onclick="applyBulkUpdate()">
                    <i class="fa fa-check"></i> Apply to Selected
                </button>
            </div>
        </div>

        <!-- Tables List -->
        <table class="config-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                        </div>
                    </th>
                    <th>Table Name</th>
                    <th>Conflict Strategy</th>
                    <th>Real-time</th>
                    <th>Data Scope</th>
                    <th>Batch Size</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $table): ?>
                <tr data-table="<?php echo $table['table_name']; ?>">
                    <td>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" class="table-checkbox" value="<?php echo $table['table_name']; ?>">
                        </div>
                    </td>
                    <td>
                        <div class="table-name"><?php echo ucwords(str_replace('_', ' ', $table['table_name'])); ?></div>
                        <div class="table-stats">
                            <span class="badge badge-warning"><?php echo number_format($table['pending_count']); ?> pending</span>
                            <span class="badge badge-success"><?php echo number_format($table['synced_count']); ?> synced</span>
                        </div>
                    </td>
                    <td>
                        <select class="table-select conflict-strategy" data-table="<?php echo $table['table_name']; ?>">
                            <?php foreach ($conflict_strategies as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $table['conflict_strategy'] == $value ? 'selected' : ''; ?>>
                                <?php echo explode('(', $label)[0]; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <span class="realtime-badge <?php echo $table['real_time_sync'] ? 'active' : 'inactive'; ?>" id="realtime-<?php echo $table['table_name']; ?>">
                            <span class="dot"></span>
                            <?php echo $table['real_time_sync'] ? 'On' : 'Off'; ?>
                        </span>
                    </td>
                    <td>
                        <select class="table-select data-scope" data-table="<?php echo $table['table_name']; ?>">
                            <?php foreach ($data_scopes as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $table['data_scope'] == $value ? 'selected' : ''; ?>>
                                <?php echo explode('(', $label)[0]; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" class="table-input batch-size" data-table="<?php echo $table['table_name']; ?>" value="<?php echo $table['batch_size']; ?>" min="1" max="1000">
                    </td>
                    <td>
                        <input type="number" class="table-input priority" data-table="<?php echo $table['table_name']; ?>" value="<?php echo $table['priority']; ?>" min="1" max="10">
                    </td>
                    <td>
                        <?php if ($table['sync_enabled']): ?>
                        <span class="badge badge-success">Enabled</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-save" onclick="saveTableConfig('<?php echo $table['table_name']; ?>')" title="Save">
                                <i class="fa fa-save"></i>
                            </button>
                            <button class="btn btn-sm btn-reset" onclick="resetTableConfig('<?php echo $table['table_name']; ?>')" title="Reset to Defaults">
                                <i class="fa fa-undo"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
var csrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

function toggleSelectAll() {
    var checked = document.getElementById('select-all').checked;
    document.querySelectorAll('.table-checkbox').forEach(function(cb) {
        cb.checked = checked;
    });
}

function saveTableConfig(tableName) {
    var row = document.querySelector('tr[data-table="' + tableName + '"]');
    var data = {
        table_name: tableName,
        conflict_strategy: row.querySelector('.conflict-strategy').value,
        real_time_sync: row.querySelector('.realtime-badge').classList.contains('active') ? 1 : 0,
        data_scope: row.querySelector('.data-scope').value,
        batch_size: parseInt(row.querySelector('.batch-size').value),
        priority: parseInt(row.querySelector('.priority').value),
        csrf_token: csrfToken
    };
    
    fetch('<?php echo site_url("sync_config/update"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('success', 'Configuration saved for ' + tableName);
        } else {
            showNotification('error', result.message || 'Failed to save configuration');
        }
    })
    .catch(error => {
        showNotification('error', 'An error occurred');
    });
}

function resetTableConfig(tableName) {
    if (!confirm('Reset configuration for ' + tableName + ' to defaults?')) return;
    
    fetch('<?php echo site_url("sync_config/reset_defaults"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            table_name: tableName,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            showNotification('error', result.message || 'Failed to reset configuration');
        }
    });
}

function saveGlobalSettings() {
    var data = {
        auto_sync_enabled: document.getElementById('auto_sync_enabled').checked ? 1 : 0,
        sync_interval_minutes: parseInt(document.getElementById('sync_interval_minutes').value),
        max_retry_attempts: parseInt(document.getElementById('max_retry_attempts').value),
        offline_mode_enabled: document.getElementById('offline_mode_enabled').checked ? 1 : 0,
        realtime_sync_enabled: document.getElementById('realtime_sync_enabled').checked ? 1 : 0,
        conflict_notification_email: document.getElementById('conflict_notification_email').value,
        csrf_token: csrfToken
    };
    
    fetch('<?php echo site_url("sync_config/update_global"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('success', 'Global settings saved successfully');
        } else {
            showNotification('error', result.message || 'Failed to save settings');
        }
    });
}

function applyBulkUpdate() {
    var selectedTables = [];
    document.querySelectorAll('.table-checkbox:checked').forEach(function(cb) {
        selectedTables.push(cb.value);
    });
    
    if (selectedTables.length === 0) {
        showNotification('error', 'Please select at least one table');
        return;
    }
    
    var data = {
        tables: selectedTables,
        conflict_strategy: document.getElementById('bulk-conflict-strategy').value,
        data_scope: document.getElementById('bulk-data-scope').value,
        batch_size: document.getElementById('bulk-batch-size').value,
        csrf_token: csrfToken
    };
    
    // Remove empty values
    Object.keys(data).forEach(function(key) {
        if (data[key] === '' || data[key] === null) {
            delete data[key];
        }
    });
    
    fetch('<?php echo site_url("sync_config/bulk_update"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('success', result.message);
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showNotification('error', result.message || 'Failed to update tables');
        }
    });
}

function showNotification(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    var notification = document.createElement('div');
    notification.className = 'alert ' + alertClass;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = '<i class="fa ' + icon + '"></i> ' + message;
    
    document.body.appendChild(notification);
    
    setTimeout(function() {
        notification.remove();
    }, 3000);
}

// Toggle real-time sync on click
document.querySelectorAll('.realtime-badge').forEach(function(badge) {
    badge.style.cursor = 'pointer';
    badge.addEventListener('click', function() {
        var isActive = this.classList.contains('active');
        if (isActive) {
            this.classList.remove('active');
            this.classList.add('inactive');
            this.innerHTML = '<span class="dot"></span> Off';
        } else {
            this.classList.remove('inactive');
            this.classList.add('active');
            this.innerHTML = '<span class="dot"></span> On';
        }
    });
});
</script>
