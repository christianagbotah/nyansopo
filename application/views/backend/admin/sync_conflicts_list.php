<style>
/* Modern Sync Conflicts Styling */
.sync-conflicts-modern {
    padding: 24px;
    background: #f8f9fa;
    min-height: calc(100vh - 100px);
}

.sync-conflicts-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.sync-conflicts-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
}

.sync-conflicts-title i {
    color: white;
}

.sync-conflicts-subtitle {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
}

/* Stats Cards */
.sync-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.sync-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border-left: 4px solid;
}

.sync-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.sync-stat-card.pending { border-left-color: #ef4444; }
.sync-stat-card.resolved { border-left-color: #10b981; }
.sync-stat-card.ignored { border-left-color: #6b7280; }
.sync-stat-card.tables { border-left-color: #3b82f6; }

.sync-stat-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.sync-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.sync-stat-card.pending .sync-stat-icon { background: #fee2e2; color: #ef4444; }
.sync-stat-card.resolved .sync-stat-icon { background: #d1fae5; color: #10b981; }
.sync-stat-card.ignored .sync-stat-icon { background: #f3f4f6; color: #6b7280; }
.sync-stat-card.tables .sync-stat-icon { background: #dbeafe; color: #3b82f6; }

.sync-stat-value {
    font-size: 40px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
    text-align: right;
}

.sync-stat-label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filters and Actions Bar */
.sync-controls-bar {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.sync-filters-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 16px;
}

.sync-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sync-filter-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.sync-filter-select {
    min-width: 200px;
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    color: #1f2937;
    transition: all 0.2s;
    background: white;
}

.sync-filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.sync-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.sync-btn-primary {
    background: #667eea;
    color: white;
}

.sync-btn-primary:hover {
    background: #5568d3;
    transform: translateY(-1px);
}

.sync-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.sync-btn-secondary:hover {
    background: #e5e7eb;
}

.sync-bulk-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.sync-btn-warning {
    background: #f59e0b;
    color: white;
}

.sync-btn-warning:hover {
    background: #d97706;
}

.sync-btn-info {
    background: #3b82f6;
    color: white;
}

.sync-btn-info:hover {
    background: #2563eb;
}

.sync-btn-default {
    background: #6b7280;
    color: white;
}

.sync-btn-default:hover {
    background: #4b5563;
}

/* Conflicts Table */
.sync-conflicts-table-container {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.sync-conflicts-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.sync-conflicts-table thead th {
    background: #f9fafb;
    padding: 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
}

.sync-conflicts-table tbody td {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
    color: #1f2937;
}

.sync-conflicts-table tbody tr:hover {
    background: #f9fafb;
}

.sync-conflicts-table tbody tr:last-child td {
    border-bottom: none;
}

.sync-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.5;
}

.sync-badge-primary {
    background: #dbeafe;
    color: #1e40af;
}

.sync-badge-info {
    background: #e0e7ff;
    color: #4338ca;
}

.sync-badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.sync-badge-default {
    background: #f3f4f6;
    color: #374151;
}

.sync-action-buttons {
    display: flex;
    gap: 8px;
}

.sync-action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 14px;
}

.sync-action-btn-view {
    background: #f3f4f6;
    color: #374151;
}

.sync-action-btn-view:hover {
    background: #e5e7eb;
}

.sync-action-btn-local {
    background: #fef3c7;
    color: #92400e;
}

.sync-action-btn-local:hover {
    background: #fde68a;
}

.sync-action-btn-remote {
    background: #dbeafe;
    color: #1e40af;
}

.sync-action-btn-remote:hover {
    background: #bfdbfe;
}

.sync-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.sync-empty-icon {
    font-size: 64px;
    color: #10b981;
    margin-bottom: 16px;
}

.sync-empty-title {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.sync-empty-text {
    font-size: 14px;
    color: #6b7280;
}

.sync-checkbox {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 2px solid #d1d5db;
    cursor: pointer;
}

.sync-checkbox:checked {
    background: #667eea;
    border-color: #667eea;
}

@media (max-width: 768px) {
    .sync-conflicts-modern {
        padding: 16px;
    }
    
    .sync-conflicts-header {
        padding: 24px;
    }
    
    .sync-conflicts-title {
        font-size: 24px;
    }
    
    .sync-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .sync-filters-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .sync-filter-select {
        width: 100%;
    }
    
    .sync-bulk-actions {
        flex-direction: column;
    }
    
    .sync-btn {
        width: 100%;
        justify-content: center;
    }
    
    .sync-conflicts-table-container {
        overflow-x: auto;
    }
}
</style>

<div class="sync-conflicts-modern">
    <!-- Header -->
    <div class="sync-conflicts-header">
        <h1 class="sync-conflicts-title">
            <i class="fa fa-exclamation-triangle"></i>
            Sync Conflicts
        </h1>
        <p class="sync-conflicts-subtitle">
            Manage and resolve data synchronization conflicts between locations
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="sync-stats-grid">
        <div class="sync-stat-card pending">
            <div class="sync-stat-content">
                <div class="sync-stat-icon">
                    <i class="fa fa-exclamation-circle"></i>
                </div>
                <div class="sync-stat-value"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="sync-stat-label">Pending</div>
        </div>

        <div class="sync-stat-card resolved">
            <div class="sync-stat-content">
                <div class="sync-stat-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="sync-stat-value"><?php echo $stats['resolved']; ?></div>
            </div>
            <div class="sync-stat-label">Resolved</div>
        </div>

        <div class="sync-stat-card ignored">
            <div class="sync-stat-content">
                <div class="sync-stat-icon">
                    <i class="fa fa-ban"></i>
                </div>
                <div class="sync-stat-value"><?php echo $stats['ignored']; ?></div>
            </div>
            <div class="sync-stat-label">Ignored</div>
        </div>

        <div class="sync-stat-card tables">
            <div class="sync-stat-content">
                <div class="sync-stat-icon">
                    <i class="fa fa-table"></i>
                </div>
                <div class="sync-stat-value"><?php echo count($stats['by_table']); ?></div>
            </div>
            <div class="sync-stat-label">Tables Affected</div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="sync-controls-bar">
        <!-- Filters -->
        <form method="get" class="sync-filters-row">
            <div class="sync-filter-group">
                <label class="sync-filter-label">Table:</label>
                <select name="table" class="sync-filter-select">
                    <option value="">All Tables</option>
                    <?php foreach ($counts_by_table as $table_name => $count): ?>
                        <option value="<?php echo $table_name; ?>" <?php echo ($filters['table_name'] == $table_name) ? 'selected' : ''; ?>>
                            <?php echo $table_name; ?> (<?php echo $count; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="sync-btn sync-btn-primary">
                <i class="fa fa-filter"></i> Filter
            </button>
            
            <a href="<?php echo site_url('admin/sync_conflicts'); ?>" class="sync-btn sync-btn-secondary">
                <i class="fa fa-refresh"></i> Reset
            </a>
        </form>

        <!-- Bulk Actions -->
        <div class="sync-bulk-actions">
            <button type="button" class="sync-btn sync-btn-warning" onclick="bulkResolve('local_wins')">
                <i class="fa fa-arrow-left"></i> Keep Local (Selected)
            </button>
            <button type="button" class="sync-btn sync-btn-info" onclick="bulkResolve('remote_wins')">
                <i class="fa fa-arrow-right"></i> Keep Remote (Selected)
            </button>
            <button type="button" class="sync-btn sync-btn-default" onclick="bulkResolve('ignore')">
                <i class="fa fa-ban"></i> Ignore (Selected)
            </button>
        </div>
    </div>

    <!-- Conflicts Table -->
    <div class="sync-conflicts-table-container">
        <?php if (empty($conflicts)): ?>
            <div class="sync-empty-state">
                <div class="sync-empty-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <div class="sync-empty-title">No Pending Conflicts</div>
                <div class="sync-empty-text">All synchronization conflicts have been resolved. Great job!</div>
            </div>
        <?php else: ?>
            <table class="sync-conflicts-table">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="select_all" class="sync-checkbox" onclick="toggleSelectAll(this)">
                        </th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>Local Location</th>
                        <th>Remote Location</th>
                        <th>Local Modified</th>
                        <th>Remote Modified</th>
                        <th>Strategy</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conflicts as $conflict): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="conflict-checkbox sync-checkbox" value="<?php echo $conflict['id']; ?>">
                            </td>
                            <td>
                                <span class="sync-badge sync-badge-primary"><?php echo $conflict['table_name']; ?></span>
                            </td>
                            <td><?php echo $conflict['record_id']; ?></td>
                            <td>
                                <span class="sync-badge sync-badge-info"><?php echo $conflict['local_device_id']; ?></span>
                            </td>
                            <td>
                                <span class="sync-badge sync-badge-warning"><?php echo $conflict['remote_device_id']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($conflict['local_modified_at'])); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($conflict['remote_modified_at'])); ?></td>
                            <td>
                                <span class="sync-badge sync-badge-default"><?php echo $conflict['conflict_strategy']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($conflict['created_at'])); ?></td>
                            <td>
                                <div class="sync-action-buttons">
                                    <a href="<?php echo site_url('admin/sync_conflicts/view/' . $conflict['id']); ?>" 
                                       class="sync-action-btn sync-action-btn-view" 
                                       title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <button type="button" 
                                            class="sync-action-btn sync-action-btn-local" 
                                            onclick="quickResolve(<?php echo $conflict['id']; ?>, 'local')" 
                                            title="Keep Local">
                                        <i class="fa fa-arrow-left"></i>
                                    </button>
                                    <button type="button" 
                                            class="sync-action-btn sync-action-btn-remote" 
                                            onclick="quickResolve(<?php echo $conflict['id']; ?>, 'remote')" 
                                            title="Keep Remote">
                                        <i class="fa fa-arrow-right"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSelectAll(checkbox) {
    $('.conflict-checkbox').prop('checked', checkbox.checked);
}

function getSelectedIds() {
    var ids = [];
    $('.conflict-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    return ids;
}

function bulkResolve(resolution) {
    var ids = getSelectedIds();
    if (ids.length === 0) {
        showAjaxModal_alert('Please select at least one conflict', 'warning');
        return;
    }
    
    var actionText = resolution === 'local_wins' ? 'keep local version' : 
                     resolution === 'remote_wins' ? 'keep remote version' : 'ignore';
    
    var iconType = resolution === 'local_wins' ? 'warning' : 
                   resolution === 'remote_wins' ? 'info' : 'warning';
    
    showConfirmModal(
        'Confirm Bulk Action',
        'Are you sure you want to ' + actionText + ' for ' + ids.length + ' conflict(s)?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url("admin/sync_conflicts/bulk_resolve"); ?>',
                type: 'POST',
                data: { ids: ids, resolution: resolution },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAjaxModal_alert('Conflicts resolved successfully', 'success');
                    } else {
                        showAjaxModal_alert(response.message || 'Failed to resolve conflicts', 'error');
                    }
                },
                error: function() {
                    showAjaxModal_alert('An error occurred while resolving conflicts', 'error');
                }
            });
        },
        'Confirm',
        iconType
    );
}

function quickResolve(id, version) {
    var url = version === 'local' 
        ? '<?php echo site_url("admin/sync_conflicts/keep_local/"); ?>' + id
        : '<?php echo site_url("admin/sync_conflicts/keep_remote/"); ?>' + id;
    
    var versionText = version === 'local' ? 'local' : 'remote';
    var iconType = version === 'local' ? 'warning' : 'info';
    
    showConfirmModal(
        'Confirm Resolution',
        'Are you sure you want to keep the ' + versionText + ' version?',
        function() {
            showAjaxModal_alert('Processing...', 'loading');
            
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAjaxModal_alert('Conflict resolved successfully', 'success');
                    } else {
                        showAjaxModal_alert(response.message || 'Failed to resolve conflict', 'error');
                    }
                },
                error: function() {
                    showAjaxModal_alert('An error occurred while resolving the conflict', 'error');
                }
            });
        },
        'Confirm',
        iconType
    );
}
</script>
