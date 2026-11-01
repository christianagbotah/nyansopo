<?php
$sync_status = $this->sync_status ?? [];
$internet_online = $sync_status['internet_online'] ?? false;
?>

<!-- Modern UI Enhancements -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-modern-ui.css?v=<?php echo time(); ?>">

<style>
.sync-table-mgmt { padding: 20px; max-width: 1400px; margin: 0 auto; }
.mgmt-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; position: relative; }
.mgmt-header h1 { margin: 0 0 10px 0; font-size: 28px; color: white; }
.mgmt-header p { margin: 0; opacity: 0.9; color: white; }
.offline-status-badge { position: absolute; top: 20px; right: 20px; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.offline-status-badge.online { background: rgba(16, 185, 129, 0.2); border: 2px solid rgba(16, 185, 129, 0.5); color: #10b981; }
.offline-status-badge.offline { background: rgba(239, 68, 68, 0.2); border: 2px solid rgba(239, 68, 68, 0.5); color: #ef4444; }
.offline-status-badge .status-dot { width: 8px; height: 8px; border-radius: 50%; }
.offline-status-badge.online .status-dot { background: #10b981; animation: pulse 2s infinite; }
.offline-status-badge.offline .status-dot { background: #ef4444; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.stat-value { font-size: 32px; font-weight: 700; color: #667eea; margin: 0; }
.stat-label { font-size: 14px; color: #6b7280; margin: 5px 0 0 0; }

.filter-bar { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; position: sticky; top: 0; z-index: 100; }
.filter-bar input { padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; flex: 1; min-width: 250px; }
.filter-bar input:focus { outline: none; border-color: #667eea; }
.filter-bar select { padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: white; }
.filter-bar button { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }

.tables-container { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; }
.table-header { background: #f9fafb; padding: 15px 20px; border-bottom: 2px solid #e5e7eb; display: grid; grid-template-columns: 40px 40px 1fr 120px 120px 100px 80px; gap: 15px; font-weight: 600; color: #374151; font-size: 13px; text-transform: uppercase; }
.table-row { padding: 15px 20px; border-bottom: 1px solid #f3f4f6; display: grid; grid-template-columns: 40px 40px 1fr 120px 120px 100px 80px; gap: 15px; align-items: center; transition: background 0.2s; }
.table-row:hover { background: #f9fafb; }
.table-row.disabled { opacity: 0.5; }

.bulk-checkbox { width: 18px; height: 18px; cursor: pointer; }
.bulk-checkbox:disabled { cursor: not-allowed; opacity: 0.5; }

.table-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.table-icon.has-sync { background: #d1fae5; color: #059669; }
.table-icon.no-sync { background: #fee2e2; color: #dc2626; }

.table-name { font-weight: 600; color: #1f2937; }
.table-description { font-size: 13px; color: #6b7280; margin-top: 2px; }

.badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-info { background: #dbeafe; color: #1e40af; }

.record-count { font-weight: 600; color: #374151; }

.toggle-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 26px; }
.toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; }
.toggle-switch input:checked + .toggle-slider { background-color: #667eea; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }
.toggle-switch input:disabled + .toggle-slider { opacity: 0.5; cursor: not-allowed; }

.bulk-actions { padding: 15px 20px; background: #f9fafb; border-top: 2px solid #e5e7eb; display: flex; gap: 10px; align-items: center; }
.bulk-actions button { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }

.empty-state { padding: 60px 20px; text-align: center; color: #9ca3af; }
.empty-state i { font-size: 48px; margin-bottom: 15px; }

.loading { text-align: center; padding: 40px; color: #9ca3af; }

/* Floating Bulk Actions Bar */
.floating-bulk-actions {
    position: fixed;
    bottom: -100px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    padding: 20px 30px;
    border-radius: 12px 12px 0 0;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    display: none; /* Hidden by default */
    gap: 15px;
    align-items: center;
    z-index: 1000;
    transition: bottom 0.3s ease-in-out;
    min-width: 600px;
}

.floating-bulk-actions.show {
    display: flex; /* Show as flex when active */
    bottom: 20px;
}

.floating-bulk-actions .selection-info {
    font-weight: 600;
    color: #374151;
    margin-right: 10px;
}

.floating-bulk-actions button {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

@media (max-width: 1024px) {
    .table-header, .table-row { grid-template-columns: 1fr; gap: 10px; }
    .table-header { display: none; }
    .table-row { padding: 20px; }
    .table-row > div { display: flex; justify-content: space-between; align-items: center; }
    .table-row > div:before { content: attr(data-label); font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; }
    
    .floating-bulk-actions {
        min-width: 90%;
        flex-wrap: wrap;
        justify-content: center;
    }
}

/* Excluded Tables Styles - Task 4.1 */
.table-row.excluded {
    background: #fef3c7 !important;
    opacity: 0.8;
}

.table-row.excluded .table-name {
    color: #92400e;
}

.table-row.excluded .bulk-checkbox {
    cursor: not-allowed;
}

.badge-excluded {
    background: #fbbf24;
    color: #78350f;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-excluded i {
    font-size: 14px;
}

.exclusion-tooltip {
    position: relative;
    cursor: help;
}

.exclusion-tooltip:hover::after {
    content: attr(data-reason);
    position: absolute;
    bottom: 120%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    margin-bottom: 5px;
    max-width: 300px;
    white-space: normal;
    text-align: center;
}

.exclusion-tooltip:hover::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: #1f2937;
    z-index: 1001;
}
</style>

<div class="sync-table-mgmt">
    <!-- Header -->
    <div class="mgmt-header">
        <h1><i class="fa fa-table"></i> Sync Table Management</h1>
        <p>Enable or disable sync for individual tables</p>
        
        <!-- Offline Status Badge -->
        <div class="offline-status-badge <?php echo $internet_online ? 'online' : 'offline'; ?>" id="offline-status-badge">
            <span class="status-dot"></span>
            <span><?php echo $internet_online ? 'Online' : 'Offline'; ?></span>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" id="total-tables">0</div>
            <div class="stat-label">Total Tables</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="enabled-tables">0</div>
            <div class="stat-label">Sync Enabled</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="disabled-tables">0</div>
            <div class="stat-label">Sync Disabled</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="no-sync-columns">0</div>
            <div class="stat-label">No Sync Columns</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <input type="text" id="search-tables" placeholder="Search tables..." />
        <select id="filter-status">
            <option value="all">All Tables</option>
            <option value="enabled">Sync Enabled</option>
            <option value="disabled">Sync Disabled</option>
            <option value="no-columns">No Sync Columns</option>
            <option value="excluded">Excluded Tables</option>
        </select>
        <select id="filter-category">
            <option value="all">All Categories</option>
            <option value="financial">Financial</option>
            <option value="student">Student</option>
            <option value="academic">Academic</option>
            <option value="system">System</option>
            <option value="other">Other</option>
        </select>
        <button class="btn-primary" onclick="applyFilters()"><i class="fa fa-filter"></i> Filter</button>
        <button class="btn-secondary" onclick="resetFilters()"><i class="fa fa-redo"></i> Reset</button>
        <button class="btn-primary" onclick="markAllAsSynced()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); margin-left: auto;">
            <i class="fa fa-check-double"></i> Mark All as Synced
        </button>
    </div>

    <!-- Tables Container -->
    <div class="tables-container">
        <div class="table-header">
            <div>
                <input type="checkbox" id="select-all-tables" class="bulk-checkbox" onchange="toggleSelectAllTables(this.checked)" title="Select All">
            </div>
            <div></div>
            <div>Table Name</div>
            <div>Status</div>
            <div>Records</div>
            <div title="Indicates whether this table has the sync tracking columns (sync_status, last_modified_at, etc.) installed">
                Sync Columns <i class="fa fa-info-circle" style="color: #9ca3af; font-size: 12px;"></i>
            </div>
            <div>Enable</div>
        </div>
        
        <div id="tables-list">
            <div class="loading">
                <i class="fa fa-spinner fa-spin"></i>
                <p>Loading tables...</p>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions">
        <button class="btn-primary" onclick="showEnableAllModal()">
            <i class="fa fa-check-circle"></i> Enable All Filtered
        </button>
        <button class="btn-secondary" onclick="showDisableAllModal()">
            <i class="fa fa-times-circle"></i> Disable All Filtered
        </button>
    </div>
</div>

<!-- Floating Bulk Actions Bar -->
<div class="floating-bulk-actions" id="floating-bulk-actions">
    <span class="selection-info" id="floating-selected-count">0 selected</span>
    <button class="btn-primary" onclick="bulkEnableSelected()" id="floating-bulk-enable-btn" disabled>
        <i class="fa fa-check-circle"></i> Enable Selected
    </button>
    <button class="btn-secondary" onclick="bulkDisableSelected()" id="floating-bulk-disable-btn" disabled>
        <i class="fa fa-times-circle"></i> Disable Selected
    </button>
    <button class="btn-secondary" onclick="bulkAddSyncColumns()" id="floating-bulk-add-sync-btn" disabled>
        <i class="fa fa-plus-circle"></i> Add Sync Columns
    </button>
    <button class="btn-secondary" onclick="bulkRemoveSyncColumns()" id="floating-bulk-remove-sync-btn" disabled style="background: #fee2e2; color: #991b1b;">
        <i class="fa fa-trash"></i> Remove Sync Columns
    </button>
    <button class="btn-secondary" onclick="clearSelection()" style="background: #fee2e2; color: #991b1b;">
        <i class="fa fa-times"></i> Clear
    </button>
</div>

<!-- Modern UI Enhancements -->
<script src="<?php echo base_url(); ?>assets/js/sync-modern-ui.js?v=<?php echo time(); ?>"></script>

<script>
let allTables = [];
let filteredTables = [];

// Load tables on page load
$(document).ready(function() {
    // Ensure sidebar toggle functionality is initialized
    if (typeof public_vars !== 'undefined' && public_vars.$sidebarMenu) {
        // Reinitialize sidebar collapse icon click handler
        public_vars.$sidebarMenu.find(".sidebar-collapse-icon").off('click').on('click', function(ev) {
            ev.preventDefault();
            var with_animation = $(this).hasClass('with-animation');
            if (typeof toggle_sidebar_menu === 'function') {
                toggle_sidebar_menu(with_animation);
            }
        });
        
        // Reinitialize mobile sidebar menu toggle
        public_vars.$sidebarMenu.find(".sidebar-mobile-menu a").off('click').on('click', function(ev) {
            ev.preventDefault();
            var with_animation = $(this).hasClass('with-animation');
            if (public_vars.$mainMenu) {
                if (with_animation) {
                    public_vars.$mainMenu.stop().slideToggle('normal', function() {
                        public_vars.$mainMenu.css('height', 'auto');
                    });
                } else {
                    public_vars.$mainMenu.toggle();
                }
            }
        });
    }
    
    loadTables();
});

function loadTables() {
    // Show skeleton loaders
    SkeletonLoader.show('#tables-list', 'table', 8);
    
    $.ajax({
        url: '<?php echo site_url('sync_server/get_all_tables_status'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                allTables = response.tables;
                filteredTables = allTables;
                updateStats();
                renderTables();
            } else {
                // Check if session expired
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    showError(response.message || 'Failed to load tables');
                    // Show error state
                    $('#tables-list').html(createEmptyState({
                        icon: 'fa-exclamation-triangle',
                        title: 'Failed to load tables',
                        description: response.message || 'An error occurred while loading tables.',
                        actions: [{
                            label: 'Try Again',
                            icon: 'fa-redo',
                            onclick: 'loadTables()'
                        }]
                    }));
                }
            }
        },
        error: function(xhr) {
            // Check if it's a session expiration (401 or 403)
            if (xhr.status === 401 || xhr.status === 403) {
                SyncToast.error('Session expired. Redirecting to login...');
                setTimeout(() => {
                    window.location.href = '<?php echo site_url('login'); ?>';
                }, 2000);
            } else {
                showError('Error loading tables');
                // Show error state
                $('#tables-list').html(createEmptyState({
                    icon: 'fa-exclamation-triangle',
                    title: 'Error loading tables',
                    description: 'An unexpected error occurred. Please try again.',
                    actions: [{
                        label: 'Try Again',
                        icon: 'fa-redo',
                        onclick: 'loadTables()'
                    }]
                }));
            }
        }
    });
}

function updateStats() {
    // Task 4.4: Filter out excluded tables from statistics
    const syncableTables = allTables.filter(t => !t.is_excluded);
    
    const total = syncableTables.length;
    const enabled = syncableTables.filter(t => t.sync_enabled && t.has_sync_columns).length;
    const disabled = syncableTables.filter(t => !t.sync_enabled && t.has_sync_columns).length;
    const noColumns = syncableTables.filter(t => !t.has_sync_columns).length;
    
    $('#total-tables').text(total);
    $('#enabled-tables').text(enabled);
    $('#disabled-tables').text(disabled);
    $('#no-sync-columns').text(noColumns);
}

function renderTables() {
    const container = $('#tables-list');
    
    if (filteredTables.length === 0) {
        container.html(createEmptyState({
            icon: 'fa-database',
            title: 'No tables found',
            description: 'No tables match your current filters. Try adjusting your search criteria.',
            actions: [{
                label: 'Reset Filters',
                icon: 'fa-redo',
                onclick: 'resetFilters()'
            }]
        }));
        return;
    }
    
    let html = '';
    filteredTables.forEach(table => {
        // Task 4.2: Add exclusion detection
        const isExcluded = table.is_excluded || false;
        const excludedClass = isExcluded ? 'excluded' : '';
        
        const iconClass = table.has_sync_columns ? 'has-sync' : 'no-sync';
        const icon = table.has_sync_columns ? 'fa-check' : 'fa-times';
        
        // Task 4.2: Show exclusion badge instead of status badge for excluded tables
        const statusBadge = isExcluded 
            ? `<span class="badge badge-excluded exclusion-tooltip" 
                     data-reason="${table.exclusion_reason || 'Excluded from sync'}">
                   <i class="fa fa-ban"></i> Excluded
               </span>`
            : getStatusBadge(table);
        
        const recordCount = table.record_count || 0;
        const syncColumnsBadge = table.has_sync_columns 
            ? '<span class="badge badge-success">Yes</span>' 
            : '<span class="badge badge-danger">No</span>';
        
        html += `
            <div class="table-row ${!table.has_sync_columns ? 'disabled' : ''} ${excludedClass}" data-table="${table.table_name}">
                <div>
                    <input type="checkbox" class="bulk-checkbox table-checkbox" 
                           data-table="${table.table_name}" 
                           data-has-sync="${table.has_sync_columns ? '1' : '0'}"
                           ${isExcluded ? 'disabled title="This table is excluded from sync"' : ''}
                           onchange="updateBulkActions()">
                </div>
                <div class="table-icon ${iconClass}">
                    <i class="fa ${icon}"></i>
                </div>
                <div>
                    <div class="table-name">${table.table_name}</div>
                    <div class="table-description">${table.description || 'No description'}</div>
                </div>
                <div data-label="Status">${statusBadge}</div>
                <div data-label="Records" class="record-count">${recordCount.toLocaleString()}</div>
                <div data-label="Sync Columns">${syncColumnsBadge}</div>
                <div data-label="Enable">
                    ${renderActions(table, isExcluded)}
                </div>
            </div>
        `;
    });
    
    container.html(html);
    updateBulkActions();
}

// Task 4.3: Create renderActions helper function
function renderActions(table, isExcluded) {
    if (isExcluded) {
        return `
            <div class="exclusion-tooltip" 
                 data-reason="${table.exclusion_reason || 'Excluded from sync'}">
                <button class="btn-secondary" disabled 
                        style="opacity: 0.5; cursor: not-allowed;">
                    <i class="fa fa-ban"></i> Excluded
                </button>
            </div>
        `;
    }
    
    if (table.has_sync_columns) {
        return `
            <div style="display: flex; gap: 8px; align-items: center;">
                <label class="toggle-switch">
                    <input type="checkbox" 
                           ${table.sync_enabled ? 'checked' : ''} 
                           onchange="toggleSync('${table.table_name}', this.checked)">
                    <span class="toggle-slider"></span>
                </label>
                <button class="btn-secondary" 
                        style="padding: 6px 12px; font-size: 12px; background: #fee2e2; color: #991b1b;" 
                        onclick="removeSyncColumns('${table.table_name}')" 
                        title="Remove sync columns from this table">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        `;
    }
    
    return `
        <button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;" 
                onclick="addSyncColumns('${table.table_name}')">
            <i class="fa fa-plus"></i> Add
        </button>
    `;
}

function getStatusBadge(table) {
    if (!table.has_sync_columns) {
        return '<span class="badge badge-danger">No Sync Columns</span>';
    }
    if (table.sync_enabled) {
        return '<span class="badge badge-success">Enabled</span>';
    }
    return '<span class="badge badge-warning">Disabled</span>';
}

function toggleSync(tableName, enabled) {
    $.ajax({
        url: '<?php echo site_url('sync_server/toggle_table_sync'); ?>',
        type: 'POST',
        data: { table_name: tableName, enabled: enabled ? 1 : 0 },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Update local data
                const table = allTables.find(t => t.table_name === tableName);
                if (table) {
                    table.sync_enabled = enabled;
                }
                
                // Update the specific row in the UI without full reload
                updateTableRow(tableName, enabled);
                updateStats();
                
                showSuccess(`Sync ${enabled ? 'enabled' : 'disabled'} for ${tableName}`);
            } else {
                // Check if session expired
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    showError(response.message || 'Failed to toggle sync');
                    // Revert toggle
                    $(`input[onchange*="${tableName}"]`).prop('checked', !enabled);
                }
            }
        },
        error: function(xhr) {
            // Check if it's a session expiration (401 or 403)
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = '<?php echo site_url('login'); ?>';
            } else {
                showError('Error toggling sync');
                $(`input[onchange*="${tableName}"]`).prop('checked', !enabled);
            }
        }
    });
}

function updateTableRow(tableName, enabled) {
    // Find the table row
    const $row = $(`.table-row[data-table="${tableName}"]`);
    if ($row.length === 0) return;
    
    // Update the status badge
    const statusBadge = enabled 
        ? '<span class="badge badge-success">Enabled</span>'
        : '<span class="badge badge-warning">Disabled</span>';
    
    $row.find('[data-label="Status"]').html(statusBadge);
    
    // Update the toggle switch state
    $row.find('.toggle-switch input').prop('checked', enabled);
}

function applyFilters() {
    const search = $('#search-tables').val().toLowerCase();
    const status = $('#filter-status').val();
    const category = $('#filter-category').val();
    
    filteredTables = allTables.filter(table => {
        // Search filter
        if (search && !table.table_name.toLowerCase().includes(search)) {
            return false;
        }
        
        // Task 4.5: Status filter with excluded tables handling
        if (status === 'excluded' && !table.is_excluded) return false;
        if (status === 'enabled' && (!table.sync_enabled || !table.has_sync_columns)) return false;
        if (status === 'disabled' && (table.sync_enabled || !table.has_sync_columns)) return false;
        if (status === 'no-columns' && table.has_sync_columns) return false;
        
        // Category filter
        if (category !== 'all') {
            const tableName = table.table_name.toLowerCase();
            if (category === 'financial' && !/(payment|invoice|receipt|fee|bill|account)/.test(tableName)) return false;
            if (category === 'student' && !/(student|enroll|parent)/.test(tableName)) return false;
            if (category === 'academic' && !/(exam|grade|mark|subject|class)/.test(tableName)) return false;
            if (category === 'system' && !/(setting|log|audit|sync)/.test(tableName)) return false;
        }
        
        return true;
    });
    
    renderTables();
}

function resetFilters() {
    $('#search-tables').val('');
    $('#filter-status').val('all');
    $('#filter-category').val('all');
    filteredTables = allTables;
    renderTables();
}

function showEnableAllModal() {
    const count = filteredTables.filter(t => t.has_sync_columns).length;
    if (count === 0) {
        showError('No tables with sync columns in current filter');
        return;
    }
    
    showAjaxModal_prompt(`Are you sure you want to enable sync for <strong>${count} tables</strong>?<br><span style="color:#6b7280;font-size:13px;">This will allow these tables to sync to the cloud server.</span>`);
    
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        enableAllFiltered();
    });
}

function showDisableAllModal() {
    const count = filteredTables.filter(t => t.has_sync_columns).length;
    if (count === 0) {
        showError('No tables with sync columns in current filter');
        return;
    }
    
    showAjaxModal_prompt(`Are you sure you want to disable sync for <strong>${count} tables</strong>?<br><span style="color:#6b7280;font-size:13px;">These tables will no longer sync to the cloud server.</span>`);
    
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        disableAllFiltered();
    });
}

function enableAllFiltered() {
    const tables = filteredTables.filter(t => t.has_sync_columns).map(t => t.table_name);
    bulkToggleSync(tables, true);
}

function disableAllFiltered() {
    const tables = filteredTables.filter(t => t.has_sync_columns).map(t => t.table_name);
    bulkToggleSync(tables, false);
}

function bulkToggleSync(tables, enabled) {
    $.ajax({
        url: '<?php echo site_url('sync_server/bulk_toggle_sync'); ?>',
        type: 'POST',
        data: { tables: tables, enabled: enabled ? 1 : 0 },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Clear selections
                $('.table-checkbox').prop('checked', false);
                $('#select-all-tables').prop('checked', false);
                updateBulkActions();
                
                loadTables();
                showSuccess(`Sync ${enabled ? 'enabled' : 'disabled'} for ${tables.length} tables`);
            } else {
                // Check if session expired
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    showError(response.message || 'Failed to bulk toggle sync');
                }
            }
        },
        error: function(xhr) {
            // Check if it's a session expiration (401 or 403)
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = '<?php echo site_url('login'); ?>';
            } else {
                showError('Error performing bulk operation');
            }
        }
    });
}

function addSyncColumns(tableName) {
    showAjaxModal_prompt(`Add sync columns to table: <strong>${tableName}</strong>?<br><span style="color:#6b7280;font-size:13px;">This will add 7 columns (sync_status, last_modified_at, last_modified_by, device_id, version, retry_count, sync_error) and may take a moment.</span><br><div style="margin-top:10px;padding:10px;background:#dbeafe;border-radius:6px;"><i class="fa fa-info-circle" style="color:#1e40af;"></i> <strong style="color:#1e40af;">Note:</strong> <span style="color:#1e40af;font-size:12px;">This operation will also run on the remote database to keep schemas in sync.</span></div>`);
    
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        proceedAddSyncColumns(tableName);
    });
}

function proceedAddSyncColumns(tableName) {
    window.location.href = '<?php echo site_url('sync_server/add_sync_columns_ui'); ?>?table=' + tableName;
}

function addSyncToSelected() {
    const noSyncTables = filteredTables.filter(t => !t.has_sync_columns);
    if (noSyncTables.length === 0) {
        showError('No tables without sync columns in current filter');
        return;
    }
    
    window.location.href = '<?php echo site_url('sync_server/add_sync_columns_ui'); ?>';
}

function toggleSelectAllTables(checked) {
    $('.table-checkbox').prop('checked', checked);
    updateBulkActions();
}

function updateBulkActions() {
    const selectedCheckboxes = $('.table-checkbox:checked');
    const count = selectedCheckboxes.length;
    
    // Update both counters
    $('#selected-count').text(count + ' selected');
    $('#floating-selected-count').text(count + ' selected');
    
    // Count tables with and without sync columns
    let withSync = 0;
    let withoutSync = 0;
    
    selectedCheckboxes.each(function() {
        if ($(this).data('has-sync') == '1') {
            withSync++;
        } else {
            withoutSync++;
        }
    });
    
    // Enable/disable bulk action buttons (both sets)
    $('#bulk-enable-btn, #floating-bulk-enable-btn').prop('disabled', withSync === 0);
    $('#bulk-disable-btn, #floating-bulk-disable-btn').prop('disabled', withSync === 0);
    $('#bulk-add-sync-btn, #floating-bulk-add-sync-btn').prop('disabled', withoutSync === 0);
    $('#floating-bulk-remove-sync-btn').prop('disabled', withSync === 0);
    
    // Update select all checkbox state
    const totalCheckboxes = $('.table-checkbox').length;
    $('#select-all-tables').prop('checked', count === totalCheckboxes && count > 0);
    
    // Show/hide floating bulk actions bar
    if (count > 0) {
        $('#floating-bulk-actions').addClass('show');
    } else {
        $('#floating-bulk-actions').removeClass('show');
    }
}

function clearSelection() {
    $('.table-checkbox').prop('checked', false);
    $('#select-all-tables').prop('checked', false);
    updateBulkActions();
}

function getSelectedTables() {
    const selected = [];
    $('.table-checkbox:checked').each(function() {
        selected.push($(this).data('table'));
    });
    return selected;
}

function bulkEnableSelected() {
    const selected = getSelectedTables();
    const tablesWithSync = selected.filter(tableName => {
        const table = allTables.find(t => t.table_name === tableName);
        return table && table.has_sync_columns;
    });
    
    if (tablesWithSync.length === 0) {
        showError('No tables with sync columns selected');
        return;
    }
    
    showAjaxModal_prompt(`Enable sync for <strong>${tablesWithSync.length} selected table(s)</strong>?<br><span style="color:#6b7280;font-size:13px;">These tables will sync to the cloud server.</span>`);
    
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        bulkToggleSync(tablesWithSync, true);
    });
}

function bulkDisableSelected() {
    const selected = getSelectedTables();
    const tablesWithSync = selected.filter(tableName => {
        const table = allTables.find(t => t.table_name === tableName);
        return table && table.has_sync_columns;
    });
    
    if (tablesWithSync.length === 0) {
        showError('No tables with sync columns selected');
        return;
    }
    
    showAjaxModal_prompt(`Disable sync for <strong>${tablesWithSync.length} selected table(s)</strong>?<br><span style="color:#6b7280;font-size:13px;">These tables will no longer sync to the cloud server.</span>`);
    
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        bulkToggleSync(tablesWithSync, false);
    });
}

function bulkAddSyncColumns() {
    const selected = getSelectedTables();
    const tablesWithoutSync = selected.filter(tableName => {
        const table = allTables.find(t => t.table_name === tableName);
        return table && !table.has_sync_columns;
    });
    
    if (tablesWithoutSync.length === 0) {
        showError('No tables without sync columns selected');
        return;
    }
    
    // Redirect to add sync columns page with selected tables
    const params = tablesWithoutSync.map(t => 'tables[]=' + encodeURIComponent(t)).join('&');
    window.location.href = '<?php echo site_url('sync_server/add_sync_columns_ui'); ?>?' + params;
}

function removeSyncColumns(tableName) {
    showAjaxModal_prompt(`<div style="color:#991b1b;"><i class="fa fa-exclamation-triangle"></i> <strong>Warning: Remove sync columns from table: ${tableName}?</strong></div><br><span style="color:#6b7280;font-size:13px;">This will permanently remove all sync tracking columns (sync_status, last_modified_at, last_modified_by, device_id, version, retry_count, sync_error) from this table.</span><br><div style="margin-top:10px;padding:10px;background:#fee2e2;border-radius:6px;"><i class="fa fa-exclamation-circle" style="color:#991b1b;"></i> <strong style="color:#991b1b;">Important:</strong> <span style="color:#991b1b;font-size:12px;">This action cannot be undone. The table will no longer be able to sync.</span></div>`);
    
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        proceedRemoveSyncColumns(tableName);
    });
}

function proceedRemoveSyncColumns(tableName) {
    $.ajax({
        url: '<?php echo site_url('sync_server/remove_sync_columns_from_table'); ?>',
        type: 'POST',
        data: { table_name: tableName },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                showSuccess(response.message);
                loadTables();
            } else {
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    showError(response.message || 'Failed to remove sync columns');
                }
            }
        },
        error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = '<?php echo site_url('login'); ?>';
            } else {
                showError('Error removing sync columns');
            }
        }
    });
}

function bulkRemoveSyncColumns() {
    const selected = getSelectedTables();
    const tablesWithSync = selected.filter(tableName => {
        const table = allTables.find(t => t.table_name === tableName);
        return table && table.has_sync_columns;
    });
    
    if (tablesWithSync.length === 0) {
        showError('No tables with sync columns selected');
        return;
    }
    
    showAjaxModal_prompt(`<div style="color:#991b1b;"><i class="fa fa-exclamation-triangle"></i> <strong>Warning: Remove sync columns from ${tablesWithSync.length} selected table(s)?</strong></div><br><span style="color:#6b7280;font-size:13px;">This will permanently remove all sync tracking columns from these tables.</span><br><div style="margin-top:10px;padding:10px;background:#fee2e2;border-radius:6px;"><i class="fa fa-exclamation-circle" style="color:#991b1b;"></i> <strong style="color:#991b1b;">Important:</strong> <span style="color:#991b1b;font-size:12px;">This action cannot be undone. These tables will no longer be able to sync.</span></div>`);
    
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        proceedBulkRemoveSyncColumns(tablesWithSync);
    });
}

function proceedBulkRemoveSyncColumns(tables) {
    $.ajax({
        url: '<?php echo site_url('sync_server/bulk_remove_sync_columns'); ?>',
        type: 'POST',
        data: { tables: tables },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Clear selections
                $('.table-checkbox').prop('checked', false);
                $('#select-all-tables').prop('checked', false);
                updateBulkActions();
                
                loadTables();
                showSuccess(response.message);
            } else {
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    showError(response.message || 'Failed to remove sync columns');
                }
            }
        },
        error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = '<?php echo site_url('login'); ?>';
            } else {
                showError('Error performing bulk remove operation');
            }
        }
    });
}

// Search on keyup
$('#search-tables').on('keyup', function() {
    applyFilters();
});

// Mark All as Synced function
function markAllAsSynced() {
    showAjaxModal_prompt(`<div style="color:#059669;"><i class="fa fa-check-double"></i> <strong>Mark all records as synced?</strong></div><br><span style="color:#6b7280;font-size:13px;">This will update all records in all tables with sync columns to indicate they are fully synced with no pending changes.</span><br><div style="margin-top:10px;padding:10px;background:#d1fae5;border-radius:6px;"><i class="fa fa-info-circle" style="color:#059669;"></i> <strong style="color:#059669;">What this does:</strong><ul style="margin:5px 0 0 20px;color:#065f46;font-size:12px;"><li>Sets sync_status = 'SYNCED' for all records</li><li>Clears all pending sync flags</li><li>Affects 243 tables with sync columns</li></ul></div><br><div style="padding:10px;background:#fef3c7;border-radius:6px;"><i class="fa fa-exclamation-triangle" style="color:#92400e;"></i> <strong style="color:#92400e;">Note:</strong> <span style="color:#92400e;font-size:12px;">This is useful when you want to reset sync status after a fresh deployment or when starting fresh sync operations.</span></div>`);
    
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        proceedMarkAllAsSynced();
    });
}

function proceedMarkAllAsSynced() {
    // Show loading indicator
    showSuccess('Processing... This may take up to 5 minutes for large databases.');
    
    $.ajax({
        url: '<?php echo site_url('sync_server/mark_all_as_synced'); ?>',
        type: 'POST',
        dataType: 'json',
        timeout: 300000, // 5 minutes timeout
        success: function(response) {
            if (response.status === 'success') {
                showSuccess(response.message);
                // Reload tables to show updated status
                loadTables();
            } else {
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    showError(response.message || 'Failed to mark all as synced');
                }
            }
        },
        error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 403) {
                window.location.href = '<?php echo site_url('login'); ?>';
            } else if (xhr.status === 504 || xhr.statusText === 'timeout') {
                showError('Operation timed out. The process may still be running in the background. Please check the sync dashboard in a few minutes.');
            } else {
                showError('Error marking all as synced. The operation may have completed - please refresh the page to check.');
            }
        }
    });
}

function showSuccess(message) {
    if (typeof SyncToast !== 'undefined') {
        SyncToast.success(message);
    } else {
        alert(message);
    }
}

function showError(message) {
    if (typeof SyncToast !== 'undefined') {
        SyncToast.error(message);
    } else {
        alert(message);
    }
}

function showAjaxModal_prompt(message) {
    if (typeof $('#modal_prompt').modal === 'function') {
        $('#modal_prompt .modal-body').html(message);
        $('#modal_prompt').modal('show');
    } else {
        // Fallback to confirm dialog
        if (confirm(message.replace(/<[^>]*>/g, ''))) {
            $('#prompt_yes').trigger('click');
        }
    }
}

function createEmptyState(options) {
    return `
        <div class="empty-state">
            <i class="fa ${options.icon}"></i>
            <h3>${options.title}</h3>
            <p>${options.description}</p>
            ${options.actions ? options.actions.map(action => `
                <button class="btn-primary" onclick="${action.onclick}">
                    <i class="fa ${action.icon}"></i> ${action.label}
                </button>
            `).join('') : ''}
        </div>
    `;
}

// Debounce helper function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Real-time search with debounce
const debouncedSearch = debounce(function() {
    applyFilters();
}, 300);

$('#search-tables').on('keyup', debouncedSearch);
</script>
