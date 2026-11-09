<?php
/**
 * Sync Locations Management View - Modern UI
 * 
 * Lists all registered sync locations with management options
 */
?>

<style>
/* Modern Sync Locations Styles */
.sync-locations-container {
    padding: 24px 20px;
    margin-top: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

.sync-locations-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
    color: white;
    position: relative;
    overflow: hidden;
}

.sync-locations-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    opacity: 0.5;
}

.sync-locations-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.sync-locations-title {
    display: flex;
    align-items: center;
    gap: 16px;
}

.sync-locations-title-icon {
    width: 56px;
    height: 56px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.sync-locations-title h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: white;
}

.sync-locations-title p {
    margin: 4px 0 0 0;
    font-size: 14px;
    opacity: 0.9;
}

.sync-locations-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.sync-btn {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.sync-btn-primary {
    background: white;
    color: #667eea;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.sync-btn-primary:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    color: #667eea;
    text-decoration: none;
}

.sync-btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.sync-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

/* Stats Cards */
.sync-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.sync-stat-card {
    background: white;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: all 0.3s;
    border-left: 3px solid;
}

.sync-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.sync-stat-card.total { border-left-color: #667eea; }
.sync-stat-card.active { border-left-color: #10b981; }
.sync-stat-card.stale { border-left-color: #f59e0b; }
.sync-stat-card.inactive { border-left-color: #ef4444; }

.sync-stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.sync-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.sync-stat-card.total .sync-stat-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.sync-stat-card.active .sync-stat-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.sync-stat-card.stale .sync-stat-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.sync-stat-card.inactive .sync-stat-icon { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

.sync-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 4px;
}

.sync-stat-label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

/* Locations Table */
.sync-locations-table-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.sync-locations-table-header {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sync-locations-table-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sync-locations-table-body {
    padding: 24px;
}

.sync-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.sync-table thead th {
    background: #f9fafb;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
}

.sync-table tbody tr {
    transition: all 0.2s;
}

.sync-table tbody tr:hover {
    background: #f9fafb;
}

.sync-table tbody td {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}

.sync-table tbody tr:last-child td {
    border-bottom: none;
}

.location-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 15px;
}

.location-description {
    font-size: 13px;
    color: #6b7280;
    margin-top: 4px;
}

.device-id-badge {
    background: #f3f4f6;
    padding: 6px 12px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: #374151;
    font-weight: 500;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 6px;
    margin-bottom: 4px;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #f3f4f6;
    color: #6b7280;
}

.status-badge.suspended {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.sync-on {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.sync-off {
    background: #f3f4f6;
    color: #6b7280;
}

.status-badge.success {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.failed {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.partial {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.pending {
    background: #f3f4f6;
    color: #6b7280;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.status-indicator.active { background: #10b981; }
.status-indicator.inactive { background: #9ca3af; }
.status-indicator.suspended { background: #f59e0b; }

.priority-badge {
    background: #667eea;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* Action Popup Menu Styles */
.sync-action-popup-wrapper {
    position: relative;
    display: inline-block;
}

.sync-action-popup-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.sync-action-popup-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.sync-action-popup-btn i.fa-chevron-down {
    font-size: 10px;
    transition: transform 0.2s;
}

.sync-action-popup-btn.active i.fa-chevron-down {
    transform: rotate(180deg);
}

.sync-action-popup-menu {
    position: fixed;
    top: 0;
    left: 0;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    min-width: 180px;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s, visibility 0.2s;
    border: 1px solid #e5e7eb;
}

.sync-action-popup-menu.show {
    opacity: 1;
    visibility: visible;
}

.sync-action-popup-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 14px;
    font-weight: 500;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.sync-action-popup-item:hover {
    background: #f3f4f6;
}

.sync-action-popup-item i {
    width: 18px;
    text-align: center;
    font-size: 14px;
}

.sync-action-popup-item.view i { color: #2563eb; }
.sync-action-popup-item.view:hover { background: #dbeafe; color: #1d4ed8; }

.sync-action-popup-item.edit i { color: #7c3aed; }
.sync-action-popup-item.edit:hover { background: #ede9fe; color: #6d28d9; }

.sync-action-popup-item.pause i { color: #d97706; }
.sync-action-popup-item.pause:hover { background: #fef3c7; color: #b45309; }

.sync-action-popup-item.play i { color: #059669; }
.sync-action-popup-item.play:hover { background: #d1fae5; color: #047857; }

.sync-action-popup-item.danger i { color: #dc2626; }
.sync-action-popup-item.danger:hover { background: #fee2e2; color: #b91c1c; }

.sync-action-popup-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 4px 0;
}

/* Empty State */
.sync-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.sync-empty-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    color: white;
    opacity: 0.9;
}

.sync-empty-state h3 {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
}

.sync-empty-state p {
    font-size: 16px;
    color: #6b7280;
    margin-bottom: 24px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

/* Responsive */
@media (max-width: 768px) {
    .sync-locations-container {
        padding: 16px 12px;
        margin-top: 16px;
    }
    
    .sync-locations-header {
        padding: 24px 20px;
    }
    
    .sync-locations-header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .sync-locations-actions {
        width: 100%;
    }
    
    .sync-btn {
        flex: 1;
        justify-content: center;
    }
    
    .sync-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .sync-locations-table-body {
        padding: 16px;
        overflow-x: auto;
    }
    
    .sync-table {
        min-width: 800px;
    }
}
</style>

<div class="sync-locations-container">
    <!-- Page Header -->
    <div class="sync-locations-header">
        <div class="sync-locations-header-content">
            <div class="sync-locations-title">
                <div class="sync-locations-title-icon">
                    <i class="fa fa-building"></i>
                </div>
                <div>
                    <h1>Sync Locations</h1>
                    <p>Manage multi-location synchronization across your school branches</p>
                </div>
            </div>
            <div class="sync-locations-actions">
                <button onclick="showAjaxModal('<?php echo site_url('admin/sync_locations/modal_add'); ?>')" class="sync-btn sync-btn-primary">
                    <i class="fa fa-plus"></i>
                    <span>Add Location</span>
                </button>
                <a href="<?php echo site_url('sync_server/dashboard'); ?>" class="sync-btn sync-btn-secondary">
                    <i class="fa fa-tachometer-alt"></i>
                    <span>Sync Dashboard</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="sync-stats-grid">
        <div class="sync-stat-card total">
            <div class="sync-stat-header">
                <div class="sync-stat-icon">
                    <i class="fa fa-building"></i>
                </div>
            </div>
            <div class="sync-stat-value"><?php echo $stats['total']; ?></div>
            <div class="sync-stat-label">Total Locations</div>
        </div>
        
        <div class="sync-stat-card active">
            <div class="sync-stat-header">
                <div class="sync-stat-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
            </div>
            <div class="sync-stat-value"><?php echo $stats['active']; ?></div>
            <div class="sync-stat-label">Active & Syncing</div>
        </div>
        
        <div class="sync-stat-card stale">
            <div class="sync-stat-header">
                <div class="sync-stat-icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="sync-stat-value"><?php echo $stats['stale']; ?></div>
            <div class="sync-stat-label">Stale (24h+)</div>
        </div>
        
        <div class="sync-stat-card inactive">
            <div class="sync-stat-header">
                <div class="sync-stat-icon">
                    <i class="fa fa-pause-circle"></i>
                </div>
            </div>
            <div class="sync-stat-value"><?php echo $stats['inactive'] + $stats['suspended']; ?></div>
            <div class="sync-stat-label">Inactive/Suspended</div>
        </div>
    </div>
    
    <!-- Locations Table -->
    <div class="sync-locations-table-container">
        <div class="sync-locations-table-header">
            <h2>
                <i class="fa fa-list"></i>
                Registered Locations
            </h2>
        </div>
        
        <div class="sync-locations-table-body">
            <?php if (empty($locations)): ?>
                <div class="sync-empty-state">
                    <div class="sync-empty-icon">
                        <i class="fa fa-building"></i>
                    </div>
                    <h3>No Locations Registered</h3>
                    <p>Add your first sync location to get started with multi-location synchronization across your school branches.</p>
                    <button onclick="showAjaxModal('<?php echo site_url('admin/sync_locations/modal_add'); ?>')" class="sync-btn sync-btn-primary">
                        <i class="fa fa-plus"></i>
                        <span>Add Your First Location</span>
                    </button>
                </div>
            <?php else: ?>
                <table class="sync-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Location</th>
                            <th>Device ID</th>
                            <th>Status</th>
                            <th>Last Sync</th>
                            <th>Sync Status</th>
                            <th style="width: 80px; text-align: center;">Priority</th>
                            <th style="width: 80px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $index => $location): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="location-name"><?php echo htmlspecialchars($location['location_name']); ?></div>
                                    <?php if ($location['description']): ?>
                                        <div class="location-description"><?php echo htmlspecialchars($location['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="device-id-badge"><?php echo htmlspecialchars($location['device_id']); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $location['status']; ?>">
                                        <span class="status-indicator <?php echo $location['status']; ?>"></span>
                                        <?php echo ucfirst($location['status']); ?>
                                    </span>
                                    <br>
                                    <span class="status-badge <?php echo $location['sync_enabled'] ? 'sync-on' : 'sync-off'; ?>">
                                        <i class="fa fa-<?php echo $location['sync_enabled'] ? 'check' : 'times'; ?>"></i>
                                        <?php echo $location['sync_enabled'] ? 'ON' : 'OFF'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($location['last_sync_at']): ?>
                                        <div style="font-weight: 500; color: #374151;"><?php echo date('M d, Y', strtotime($location['last_sync_at'])); ?></div>
                                        <div style="font-size: 12px; color: #9ca3af;"><?php echo date('H:i:s', strtotime($location['last_sync_at'])); ?></div>
                                    <?php else: ?>
                                        <span style="color: #9ca3af; font-style: italic;">Never synced</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($location['last_sync_status'] == 'success'): ?>
                                        <span class="status-badge success">
                                            <i class="fa fa-check-circle"></i>
                                            Success
                                        </span>
                                    <?php elseif ($location['last_sync_status'] == 'failed'): ?>
                                        <span class="status-badge failed">
                                            <i class="fa fa-times-circle"></i>
                                            Failed
                                        </span>
                                    <?php elseif ($location['last_sync_status'] == 'partial'): ?>
                                        <span class="status-badge partial">
                                            <i class="fa fa-exclamation-circle"></i>
                                            Partial
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge pending">
                                            <i class="fa fa-clock"></i>
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="priority-badge"><?php echo $location['priority']; ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <div class="sync-action-popup-wrapper">
                                        <button type="button" class="sync-action-popup-btn" onclick="toggleSyncActionMenu(this, event, <?php echo $location['id']; ?>)">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </button>
                                    </div>
                                    <!-- Hidden data attributes for actions -->
                                    <span class="hidden sync-action-data" 
                                          data-location-id="<?php echo $location['id']; ?>"
                                          data-view-url="<?php echo site_url('admin/sync_locations/view/' . $location['id']); ?>"
                                          data-edit-url="<?php echo site_url('admin/sync_locations/edit/' . $location['id']); ?>"
                                          data-activate-url="<?php echo site_url('admin/sync_locations/activate/' . $location['id']); ?>"
                                          data-deactivate-url="<?php echo site_url('admin/sync_locations/deactivate/' . $location['id']); ?>"
                                          data-delete-url="<?php echo site_url('admin/sync_locations/delete/' . $location['id']); ?>"
                                          data-status="<?php echo $location['status']; ?>"></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Floating Action Popup Menu -->
<div id="syncActionPopupMenu" class="sync-action-popup-menu">
    <button id="syncActionViewBtn" class="sync-action-popup-item view">
        <i class="fa fa-eye"></i> View Details
    </button>
    <div class="sync-action-popup-divider"></div>
    <button id="syncActionEditBtn" class="sync-action-popup-item edit">
        <i class="fa fa-edit"></i> Edit Location
    </button>
    <div class="sync-action-popup-divider"></div>
    <button id="syncActionActivateBtn" class="sync-action-popup-item play">
        <i class="fa fa-play"></i> Activate
    </button>
    <button id="syncActionDeactivateBtn" class="sync-action-popup-item pause">
        <i class="fa fa-pause"></i> Deactivate
    </button>
    <div class="sync-action-popup-divider"></div>
    <button id="syncActionDeleteBtn" class="sync-action-popup-item danger">
        <i class="fa fa-trash"></i> Delete
    </button>
</div>

<script type="text/javascript">
// ============================================
// SYNC LOCATION POPUP MENU FUNCTIONALITY
// ============================================

var currentLocationId = null;
var currentActionData = null;
var $syncActionPopupMenu = null;

$(function() {
    $syncActionPopupMenu = $('#syncActionPopupMenu');
    initSyncPopupMenus();
});

function initSyncPopupMenus() {
    // Close popup when clicking outside
    $(document).off('click.syncPopup').on('click.syncPopup', function(e) {
        if (!$(e.target).closest('.sync-action-popup-btn, .sync-action-popup-menu').length) {
            closeSyncPopupMenus();
        }
    });
}

function closeSyncPopupMenus() {
    if ($syncActionPopupMenu) $syncActionPopupMenu.removeClass('show');
    $('.sync-action-popup-btn').removeClass('active');
}

// ============================================
// SYNC ACTION POPUP MENU
// ============================================

function toggleSyncActionMenu(btn, event, locationId) {
    event.stopPropagation();
    event.preventDefault();
    
    // Ensure we have a valid DOM element
    var btnElement = btn instanceof jQuery ? btn[0] : btn;
    var $btn = $(btnElement);
    var $row = $btn.closest('tr');
    var $dataSpan = $row.find('.sync-action-data');
    
    currentLocationId = $dataSpan.data('location-id');
    currentActionData = {
        viewUrl: $dataSpan.data('view-url'),
        editUrl: $dataSpan.data('edit-url'),
        activateUrl: $dataSpan.data('activate-url'),
        deactivateUrl: $dataSpan.data('deactivate-url'),
        deleteUrl: $dataSpan.data('delete-url'),
        status: $dataSpan.data('status')
    };
    
    // Show/hide activate/deactivate buttons based on status
    if (currentActionData.status == 'active') {
        $('#syncActionActivateBtn').hide();
        $('#syncActionDeactivateBtn').show();
    } else {
        $('#syncActionActivateBtn').show();
        $('#syncActionDeactivateBtn').hide();
    }
    
    // If menu is already open, close it
    if ($syncActionPopupMenu && $syncActionPopupMenu.hasClass('show')) {
        closeSyncPopupMenus();
        return;
    }
    
    closeSyncPopupMenus();
    
    // Force menu to be rendered but hidden to get proper dimensions
    $syncActionPopupMenu.css({
        left: '-9999px',
        top: '-9999px'
    }).addClass('show');
    
    // Get actual menu dimensions
    var menuWidth = $syncActionPopupMenu.outerWidth();
    var menuHeight = $syncActionPopupMenu.outerHeight();
    
    // Hide again to reposition
    $syncActionPopupMenu.removeClass('show');
    
    // Position the menu - getBoundingClientRect gives viewport-relative coordinates
    // which is correct for position: fixed elements
    var btnRect = btnElement.getBoundingClientRect();
    
    var left = btnRect.left;
    var top = btnRect.bottom + 5;
    
    // Adjust if menu would go off right edge
    if (left + menuWidth > window.innerWidth) {
        left = window.innerWidth - menuWidth - 10;
    }
    
    // Adjust if menu would go off bottom edge
    if (top + menuHeight > window.innerHeight) {
        top = btnRect.top - menuHeight - 5;
    }
    
    // Ensure coordinates are within viewport
    left = Math.max(10, left);
    top = Math.max(10, top);
    
    $syncActionPopupMenu.css({
        left: left + 'px',
        top: top + 'px'
    });
    
    // Show menu
    $syncActionPopupMenu.addClass('show');
    $(btnElement).addClass('active');
}

// Action menu handlers
$(document).ready(function() {
    $('#syncActionViewBtn').on('click', function() {
        if (currentActionData && currentActionData.viewUrl) {
            window.location.href = currentActionData.viewUrl;
        }
        closeSyncPopupMenus();
    });
    
    $('#syncActionEditBtn').on('click', function() {
        if (currentActionData && currentActionData.editUrl) {
            // Extract location ID from URL
            var locationId = currentActionData.editUrl.split('/').pop();
            // Open edit modal
            showAjaxModal('<?php echo site_url('admin/sync_locations/modal_edit/'); ?>' + locationId);
        }
        closeSyncPopupMenus();
    });
    
    $('#syncActionActivateBtn').on('click', function() {
        if (currentActionData && currentActionData.activateUrl) {
            window.location.href = currentActionData.activateUrl;
        }
        closeSyncPopupMenus();
    });
    
    $('#syncActionDeactivateBtn').on('click', function() {
        if (currentActionData && currentActionData.deactivateUrl) {
            if (confirm('Deactivate this location?')) {
                window.location.href = currentActionData.deactivateUrl;
            }
        }
        closeSyncPopupMenus();
    });
    
    $('#syncActionDeleteBtn').on('click', function() {
        if (currentActionData && currentActionData.deleteUrl) {
            if (confirm('Are you sure you want to delete this location? This action cannot be undone.')) {
                window.location.href = currentActionData.deleteUrl;
            }
        }
        closeSyncPopupMenus();
    });
});
</script>
