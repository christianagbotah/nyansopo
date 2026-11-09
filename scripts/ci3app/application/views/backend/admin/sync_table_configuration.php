<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Table Configuration View - Modern UI/UX Redesign
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
$conflict_strategies = $conflict_strategies ?? [];
$data_scopes = $data_scopes ?? [];
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

    <!-- Table Configuration List -->
    <div class="sync-config-table-container">
        <table class="sync-config-table">
            <thead>
                <tr>
                    <th class="col-table-name">
                        <span>Table Name</span>
                        <i class="fa fa-sort sort-icon"></i>
                    </th>
                    <th class="col-sync-enabled">
                        <span>Sync Enabled</span>
                    </th>
                    <th class="col-conflict-strategy">
                        <span>Conflict Strategy</span>
                    </th>
                    <th class="col-realtime">
                        <span>Real-time Sync</span>
                    </th>
                    <th class="col-data-scope">
                        <span>Data Scope</span>
                    </th>
                    <th class="col-records">
                        <span>Records</span>
                    </th>
                    <th class="col-actions">
                        <span>Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody id="config-table-body">
                <?php foreach ($tables as $table): ?>
                <tr class="config-row" 
                    data-table="<?php echo $table['table_name']; ?>"
                    data-status="<?php echo $table['sync_enabled'] ? 'enabled' : 'disabled'; ?>"
                    data-realtime="<?php echo $table['real_time_sync'] ? 'on' : 'off'; ?>"
                    data-strategy="<?php echo $table['conflict_strategy']; ?>">
                    
                    <!-- Table Name -->
                    <td class="table-name-cell">
                        <div class="table-name-display">
                            <i class="fa fa-table table-icon"></i>
                            <span class="table-name-text"><?php echo ucwords(str_replace('_', ' ', $table['table_name'])); ?></span>
                        </div>
                        <div class="table-name-code"><?php echo $table['table_name']; ?></div>
                    </td>
                    
                    <!-- Sync Enabled Toggle -->
                    <td class="sync-toggle-cell">
                        <label class="sync-toggle-switch">
                            <input type="checkbox" 
                                   class="sync-enabled-toggle" 
                                   data-table="<?php echo $table['table_name']; ?>"
                                   <?php echo $table['sync_enabled'] ? 'checked' : ''; ?>>
                            <span class="sync-toggle-slider"></span>
                        </label>
                    </td>
                    
                    <!-- Conflict Strategy -->
                    <td class="strategy-cell">
                        <select class="sync-config-select conflict-strategy-select" 
                                data-table="<?php echo $table['table_name']; ?>">
                            <?php foreach ($conflict_strategies as $value => $label): ?>
                            <option value="<?php echo $value; ?>" 
                                    <?php echo $table['conflict_strategy'] == $value ? 'selected' : ''; ?>>
                                <?php echo explode('(', $label)[0]; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="strategy-tooltip">
                            <i class="fa fa-info-circle"></i>
                        </div>
                    </td>
                    
                    <!-- Real-time Sync Toggle -->
                    <td class="realtime-cell">
                        <label class="sync-toggle-switch sync-toggle-small">
                            <input type="checkbox" 
                                   class="realtime-toggle" 
                                   data-table="<?php echo $table['table_name']; ?>"
                                   <?php echo $table['real_time_sync'] ? 'checked' : ''; ?>>
                            <span class="sync-toggle-slider"></span>
                        </label>
                        <span class="realtime-label <?php echo $table['real_time_sync'] ? 'active' : ''; ?>">
                            <?php echo $table['real_time_sync'] ? 'On' : 'Off'; ?>
                        </span>
                    </td>
                    
                    <!-- Data Scope -->
                    <td class="scope-cell">
                        <select class="sync-config-select data-scope-select" 
                                data-table="<?php echo $table['table_name']; ?>">
                            <?php foreach ($data_scopes as $value => $label): ?>
                            <option value="<?php echo $value; ?>" 
                                    <?php echo $table['data_scope'] == $value ? 'selected' : ''; ?>>
                                <?php echo explode('(', $label)[0]; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    
                    <!-- Record Counts -->
                    <td class="records-cell">
                        <div class="record-stats">
                            <span class="record-badge record-badge-pending">
                                <?php echo number_format($table['pending_count'] ?? 0); ?> pending
                            </span>
                            <span class="record-badge record-badge-synced">
                                <?php echo number_format($table['synced_count'] ?? 0); ?> synced
                            </span>
                        </div>
                    </td>
                    
                    <!-- Actions -->
                    <td class="actions-cell">
                        <div class="config-actions">
                            <button class="sync-btn-icon sync-btn-icon-save" 
                                    onclick="saveTableConfig('<?php echo $table['table_name']; ?>')"
                                    title="Save Configuration">
                                <i class="fa fa-save"></i>
                            </button>
                            <button class="sync-btn-icon sync-btn-icon-reset" 
                                    onclick="resetTableConfig('<?php echo $table['table_name']; ?>')"
                                    title="Reset to Defaults">
                                <i class="fa fa-undo"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Empty State -->
        <div class="sync-config-empty" id="empty-state" style="display: none;">
            <i class="fa fa-search"></i>
            <h3>No tables found</h3>
            <p>Try adjusting your search or filter criteria</p>
        </div>
    </div>
</div>

<!-- Configuration JavaScript -->
<script src="<?php echo base_url(); ?>assets/backend/js/sync_configuration_ui.js?v=<?php echo time(); ?>"></script>

<script>
// Initialize configuration data
var csrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
var conflictStrategies = <?php echo json_encode($conflict_strategies); ?>;
var dataScopes = <?php echo json_encode($data_scopes); ?>;

// Save table configuration
function saveTableConfig(tableName) {
    var row = document.querySelector('tr[data-table="' + tableName + '"]');
    if (!row) return;
    
    var data = {
        table_name: tableName,
        sync_enabled: row.querySelector('.sync-enabled-toggle').checked ? 1 : 0,
        conflict_strategy: row.querySelector('.conflict-strategy-select').value,
        real_time_sync: row.querySelector('.realtime-toggle').checked ? 1 : 0,
        data_scope: row.querySelector('.data-scope-select').value,
        csrf_token: csrfToken
    };
    
    // Show loading state
    var saveBtn = row.querySelector('.sync-btn-icon-save');
    var originalHTML = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    saveBtn.disabled = true;
    
    fetch('<?php echo site_url("sync_config/update"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        saveBtn.innerHTML = originalHTML;
        saveBtn.disabled = false;
        
        if (result.success) {
            showNotification('success', 'Configuration saved for ' + tableName);
            // Update row data attributes
            row.setAttribute('data-status', data.sync_enabled ? 'enabled' : 'disabled');
            row.setAttribute('data-realtime', data.real_time_sync ? 'on' : 'off');
            row.setAttribute('data-strategy', data.conflict_strategy);
        } else {
            showNotification('error', result.message || 'Failed to save configuration');
        }
    })
    .catch(error => {
        saveBtn.innerHTML = originalHTML;
        saveBtn.disabled = false;
        showNotification('error', 'An error occurred while saving');
    });
}

// Reset table configuration to defaults
function resetTableConfig(tableName) {
    if (!confirm('Reset configuration for ' + tableName + ' to defaults?\n\nThis will restore:\n- Default conflict strategy\n- Disable real-time sync\n- Reset data scope\n- Enable sync')) {
        return;
    }
    
    var row = document.querySelector('tr[data-table="' + tableName + '"]');
    var resetBtn = row.querySelector('.sync-btn-icon-reset');
    var originalHTML = resetBtn.innerHTML;
    resetBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    resetBtn.disabled = true;
    
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
        resetBtn.innerHTML = originalHTML;
        resetBtn.disabled = false;
        
        if (result.success) {
            showNotification('success', 'Configuration reset for ' + tableName);
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showNotification('error', result.message || 'Failed to reset configuration');
        }
    })
    .catch(error => {
        resetBtn.innerHTML = originalHTML;
        resetBtn.disabled = false;
        showNotification('error', 'An error occurred');
    });
}

// Reset all tables to defaults
function resetAllToDefaults() {
    if (!confirm('Reset ALL tables to default configuration?\n\nThis will:\n- Reset all conflict strategies to default\n- Disable real-time sync for all tables\n- Reset all data scopes\n- Enable sync for all tables\n\nThis action cannot be undone.')) {
        return;
    }
    
    var btn = document.getElementById('reset-all-defaults-btn');
    var originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Resetting...';
    btn.disabled = true;
    
    fetch('<?php echo site_url("sync_config/reset_all_defaults"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(result => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        
        if (result.success) {
            showNotification('success', 'All tables reset to defaults');
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            showNotification('error', result.message || 'Failed to reset all tables');
        }
    })
    .catch(error => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        showNotification('error', 'An error occurred');
    });
}

// Show notification
function showNotification(type, message) {
    var notification = document.createElement('div');
    notification.className = 'sync-notification sync-notification-' + type;
    notification.innerHTML = '<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(function() {
        notification.classList.add('show');
    }, 10);
    
    // Auto-dismiss
    setTimeout(function() {
        notification.classList.remove('show');
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 3000);
}

// Search functionality
document.getElementById('table-search').addEventListener('input', function(e) {
    filterTables();
});

// Filter functionality
document.getElementById('filter-status').addEventListener('change', filterTables);
document.getElementById('filter-realtime').addEventListener('change', filterTables);
document.getElementById('filter-strategy').addEventListener('change', filterTables);

function filterTables() {
    var searchTerm = document.getElementById('table-search').value.toLowerCase();
    var statusFilter = document.getElementById('filter-status').value;
    var realtimeFilter = document.getElementById('filter-realtime').value;
    var strategyFilter = document.getElementById('filter-strategy').value;
    
    var rows = document.querySelectorAll('.config-row');
    var visibleCount = 0;
    
    rows.forEach(function(row) {
        var tableName = row.querySelector('.table-name-text').textContent.toLowerCase();
        var tableCode = row.querySelector('.table-name-code').textContent.toLowerCase();
        var status = row.getAttribute('data-status');
        var realtime = row.getAttribute('data-realtime');
        var strategy = row.getAttribute('data-strategy');
        
        var matchesSearch = tableName.includes(searchTerm) || tableCode.includes(searchTerm);
        var matchesStatus = !statusFilter || status === statusFilter;
        var matchesRealtime = !realtimeFilter || realtime === realtimeFilter;
        var matchesStrategy = !strategyFilter || strategy === strategyFilter;
        
        if (matchesSearch && matchesStatus && matchesRealtime && matchesStrategy) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide empty state
    document.getElementById('empty-state').style.display = visibleCount === 0 ? 'flex' : 'none';
}

// Update realtime label when toggle changes
document.querySelectorAll('.realtime-toggle').forEach(function(toggle) {
    toggle.addEventListener('change', function() {
        var label = this.closest('.realtime-cell').querySelector('.realtime-label');
        if (this.checked) {
            label.textContent = 'On';
            label.classList.add('active');
        } else {
            label.textContent = 'Off';
            label.classList.remove('active');
        }
    });
});
</script>
