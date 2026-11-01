<?php
$sync_status = $this->sync_status ?? [];
$internet_online = $sync_status['internet_online'] ?? false;
$selected_table = $this->selected_table ?? null;
$tables_without_sync = $this->tables_without_sync ?? [];

// Handle table parameters from URL (supports both single and bulk selection)
$url_tables = [];

// Single table: ?table=tablename
if (isset($_GET['table'])) {
    $url_tables[] = htmlspecialchars($_GET['table'], ENT_QUOTES, 'UTF-8');
    if ($selected_table === null) {
        $selected_table = $url_tables[0];
    }
}

// Bulk tables: ?tables[]=table1&tables[]=table2
if (isset($_GET['tables']) && is_array($_GET['tables'])) {
    foreach ($_GET['tables'] as $tbl) {
        $url_tables[] = htmlspecialchars($tbl, ENT_QUOTES, 'UTF-8');
    }
}

// If we have URL tables but empty tables_without_sync, fetch the data
if (!empty($url_tables) && empty($tables_without_sync)) {
    $CI =& get_instance();
    $all_tables = $CI->db->list_tables();
    
    foreach ($all_tables as $tbl) {
        // Skip sync metadata tables
        if (strpos($tbl, 'sync_') === 0) continue;
        
        // Check if table has sync columns
        $fields = $CI->db->list_fields($tbl);
        if (!in_array('sync_status', $fields)) {
            // This table doesn't have sync columns
            if (in_array($tbl, $url_tables)) {
                // This is one of the tables we're looking for
                $tables_without_sync[] = $tbl;
            }
        }
    }
}
?>

<style>
.add-sync-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.add-sync-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; position: relative; }
.add-sync-header h1 { margin: 0 0 10px 0; font-size: 28px; color: white; }
.add-sync-header p { margin: 0; opacity: 0.9; color: white; }
.offline-status-badge { position: absolute; top: 20px; right: 20px; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.offline-status-badge.online { background: rgba(16, 185, 129, 0.2); border: 2px solid rgba(16, 185, 129, 0.5); color: #10b981; }
.offline-status-badge.offline { background: rgba(239, 68, 68, 0.2); border: 2px solid rgba(239, 68, 68, 0.5); color: #ef4444; }

.info-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
.info-card h3 { margin: 0 0 15px 0; color: #1f2937; font-size: 18px; font-weight: 700; }
.info-card p { color: #6b7280; margin-bottom: 15px; line-height: 1.6; }

.alert-modern { padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px; border-left: 4px solid; }
.alert-info-modern { background: #dbeafe; border-color: #3b82f6; color: #1e40af; }
.alert-warning-modern { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
.alert-success-modern { background: #d1fae5; border-color: #10b981; color: #065f46; }

.table-selector { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
.table-selector h3 { margin: 0 0 20px 0; color: #1f2937; font-size: 18px; font-weight: 700; }
.table-list { max-height: 400px; overflow-y: auto; border: 2px solid #e5e7eb; border-radius: 8px; }
.table-item { padding: 15px 20px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: background 0.2s; }
.table-item:hover { background: #f9fafb; }
.table-item:last-child { border-bottom: none; }
.table-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
.table-item label { flex: 1; cursor: pointer; margin: 0; font-weight: 500; color: #374151; }

.action-buttons { display: flex; gap: 12px; padding-top: 20px; }
.btn-modern { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.btn-primary-modern { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); color: white; }
.btn-secondary-modern { background: #f3f4f6; color: #374151; }
.btn-secondary-modern:hover { background: #e5e7eb; color: #374151; }
.btn-modern:disabled { opacity: 0.5; cursor: not-allowed; }

.columns-list { background: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 15px; }
.columns-list h4 { margin: 0 0 15px 0; color: #374151; font-size: 16px; font-weight: 600; }
.columns-list ul { margin: 0; padding-left: 20px; }
.columns-list li { color: #6b7280; margin-bottom: 8px; }
.columns-list code { background: white; padding: 2px 6px; border-radius: 4px; color: #667eea; font-size: 13px; }

.progress-container { display: none; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
.progress-container.active { display: block; }
.progress-bar-container { background: #e5e7eb; height: 30px; border-radius: 15px; overflow: hidden; margin: 20px 0; }
.progress-bar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px; }
.progress-log { background: #f9fafb; padding: 15px; border-radius: 8px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 13px; }
.progress-log-item { padding: 5px 0; color: #6b7280; }
.progress-log-item.success { color: #059669; }
.progress-log-item.error { color: #dc2626; }

/* Progress Modal */
.progress-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
.progress-modal.active { display: flex; }
.progress-modal-content { background: white; border-radius: 12px; padding: 30px; max-width: 600px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.progress-modal-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.progress-modal-header h3 { margin: 0; color: #1f2937; font-size: 20px; font-weight: 700; }
.progress-modal-header i { color: #667eea; }
</style>

<div class="add-sync-container">
    <!-- Header -->
    <div class="add-sync-header">
        <h1><i class="fa fa-plus-circle"></i> Add Sync Columns</h1>
        <p>Add sync tracking columns to database tables</p>
        
        <!-- Offline Status Badge -->
        <div class="offline-status-badge <?php echo $internet_online ? 'online' : 'offline'; ?>" id="offline-status-badge">
            <span class="status-dot"></span>
            <span><?php echo $internet_online ? 'Online' : 'Offline'; ?></span>
        </div>
    </div>

    <?php if (empty($tables_without_sync)): ?>
    <!-- No Tables Available -->
    <?php if ($selected_table): ?>
    <!-- Specific table requested but not available -->
    <div class="alert-modern alert-warning-modern">
        <i class="fa fa-exclamation-triangle" style="font-size: 20px;"></i>
        <div>
            <strong>Table "<?php echo htmlspecialchars($selected_table); ?>" not available</strong><br>
            This table either already has sync columns or doesn't exist in the database.
        </div>
    </div>
    
    <div class="info-card">
        <h3><i class="fa fa-info-circle"></i> Troubleshooting</h3>
        <p>If you believe this table should be available:</p>
        <ul>
            <li>Check if the table exists in your database</li>
            <li>Verify the table doesn't already have a <code>sync_status</code> column</li>
            <li>Try accessing the page without the <code>?table=</code> parameter to see all available tables</li>
        </ul>
    </div>
    
    <?php else: ?>
    <!-- No tables without sync columns -->
    <div class="alert-modern alert-success-modern">
        <i class="fa fa-check-circle" style="font-size: 20px;"></i>
        <div>
            <strong>All tables have sync columns!</strong><br>
            Every table in your database already has sync tracking columns. No action needed.
        </div>
    </div>
    <?php endif; ?>
    
    <div class="action-buttons">
        <a href="<?php echo site_url('sync_server/table_management'); ?>" class="btn-modern btn-primary-modern">
            <i class="fa fa-arrow-left"></i> Back to Table Management
        </a>
        <?php if ($selected_table): ?>
        <a href="<?php echo site_url('sync_server/add_sync_columns_ui'); ?>" class="btn-modern btn-secondary-modern">
            <i class="fa fa-list"></i> View All Tables
        </a>
        <?php endif; ?>
    </div>
    
    <?php else: ?>
    
    <!-- Info Card -->
    <div class="info-card">
        <h3><i class="fa fa-info-circle"></i> What This Does</h3>
        <p>This tool adds sync tracking columns to selected tables, allowing them to automatically sync to the cloud server.</p>
        
        <div class="columns-list">
            <h4>Columns that will be added (8 sync columns):</h4>
            <ul>
                <li><code>sync_status</code> - Track sync state (PENDING, SYNCED, FAILED, MANUAL_REVIEW)</li>
                <li><code>sync_operation_type</code> - Type of operation (insert, update, both)</li>
                <li><code>last_modified_at</code> - Timestamp of last modification</li>
                <li><code>last_modified_by</code> - User who made the last modification</li>
                <li><code>device_id</code> - Identifier of the device that created/modified the record</li>
                <li><code>version</code> - Version number for conflict resolution</li>
                <li><code>retry_count</code> - Number of sync retry attempts</li>
                <li><code>sync_error</code> - Error message if sync fails</li>
            </ul>
        </div>
    </div>

    <div class="alert-modern alert-warning-modern">
        <i class="fa fa-exclamation-triangle" style="font-size: 20px;"></i>
        <div>
            <strong>Important:</strong> This operation will modify your database schema. Make sure you have a backup before proceeding.
        </div>
    </div>

    <!-- Table Selector -->
    <div class="table-selector">
        <h3><i class="fa fa-table"></i> Select Tables (<?php echo count($tables_without_sync); ?> tables without sync columns)</h3>
        
        <div style="margin-bottom: 15px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" id="select-all" onchange="toggleSelectAll(this.checked)" style="width: 18px; height: 18px;">
                <span style="font-weight: 600; color: #374151;">Select All Tables</span>
            </label>
        </div>
        
        <div class="table-list" id="table-list">
            <?php foreach ($tables_without_sync as $table): ?>
            <div class="table-item">
                <input type="checkbox" id="table-<?php echo $table; ?>" value="<?php echo $table; ?>" class="table-checkbox" 
                       data-table="<?php echo $table; ?>">
                <label for="table-<?php echo $table; ?>"><?php echo $table; ?></label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Progress Container -->
    <div class="progress-container" id="progress-container">
        <h3><i class="fa fa-spinner fa-spin"></i> Adding Sync Columns...</h3>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progress-bar">0%</div>
        </div>
        <div class="progress-log" id="progress-log"></div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <button class="btn-modern btn-primary-modern" id="add-columns-btn" onclick="addSyncColumns()">
            <i class="fa fa-plus-circle"></i> Add Sync Columns
        </button>
        <a href="<?php echo site_url('sync_server/table_management'); ?>" class="btn-modern btn-secondary-modern">
            <i class="fa fa-times"></i> Cancel
        </a>
    </div>
    
    <?php endif; ?>
</div>

<!-- Progress Modal -->
<div class="progress-modal" id="progress-modal">
    <div class="progress-modal-content">
        <div class="progress-modal-header">
            <i class="fa fa-spinner fa-spin"></i>
            <h3>Adding Sync Columns...</h3>
        </div>
        <div class="progress-bar-container">
            <div class="progress-bar" id="modal-progress-bar">0%</div>
        </div>
        <div class="progress-log" id="modal-progress-log"></div>
    </div>
</div>

<script>
function toggleSelectAll(checked) {
    $('.table-checkbox').prop('checked', checked);
}

function addSyncColumns() {
    const selectedTables = [];
    $('.table-checkbox:checked').each(function() {
        selectedTables.push($(this).val());
    });
    
    if (selectedTables.length === 0) {
        showAjaxModal_alert('Please select at least one table', 'warning', false);
        return;
    }
    
    // Use custom modal for confirmation
    const message = `Add sync columns to <strong>${selectedTables.length} table(s)</strong>?<br><span style="color:#6b7280;font-size:13px;">This will modify the database schema.</span>`;
    showAjaxModal_prompt(message);
    
    // Wire the Yes button
    $('#prompt_yes').off('click').on('click', function() {
        $('#modal_prompt').modal('hide');
        
        // Show progress modal
        $('#progress-modal').addClass('active');
        $('#add-columns-btn').prop('disabled', true);
        $('#modal-progress-log').html('');
        
        // Process tables
        processTables(selectedTables, 0);
    });
}

function processTables(tables, index) {
    if (index >= tables.length) {
        // All done
        $('#modal-progress-bar').css('width', '100%').text('100%');
        addLog('✓ All tables processed successfully!', 'success');
        
        setTimeout(function() {
            window.location.href = '<?php echo site_url('sync_server/table_management'); ?>';
        }, 2000);
        return;
    }
    
    const table = tables[index];
    const progress = Math.round(((index + 1) / tables.length) * 100);
    
    $('#modal-progress-bar').css('width', progress + '%').text(progress + '%');
    addLog(`Processing table: ${table}...`);
    
    $.ajax({
        url: '<?php echo site_url('sync_server/add_sync_columns_to_table'); ?>',
        type: 'POST',
        data: { table_name: table },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                addLog(`✓ ${table} - Success`, 'success');
            } else {
                addLog(`✗ ${table} - ${response.message}`, 'error');
            }
            // Process next table
            processTables(tables, index + 1);
        },
        error: function() {
            addLog(`✗ ${table} - Error`, 'error');
            // Continue with next table
            processTables(tables, index + 1);
        }
    });
}

function addLog(message, type = '') {
    const logItem = $('<div class="progress-log-item"></div>')
        .addClass(type)
        .text(message);
    $('#modal-progress-log').append(logItem);
    $('#modal-progress-log').scrollTop($('#modal-progress-log')[0].scrollHeight);
}

// Auto-select tables from URL parameters
$(document).ready(function() {
    <?php if (!empty($url_tables)): ?>
    // Tables specified in URL - preselect them
    const urlTables = <?php echo json_encode($url_tables); ?>;
    urlTables.forEach(function(tableName) {
        $('#table-' + tableName).prop('checked', true);
    });
    <?php endif; ?>
});
</script>
