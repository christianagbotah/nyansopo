<?php
/**
 * Enhanced Sync Dashboard for Multi-Location Bidirectional Sync
 * 
 * Provides comprehensive monitoring of sync operations across multiple locations
 * with real-time status, metrics charts, and conflict management.
 * 
 * @package    School Manager
 * @subpackage Views
 * @category   Sync
 * 
 * Requirements: 1.1, 1.3, 1.4, 1.9, 6.1, 6.3
 * Task 2.1: Dashboard layout structure with gradient header and responsive grid
 */

$sync_status = $sync_status ?? [];
$internet_online = $sync_status['internet_online'] ?? false;
$last_sync = $sync_status['last_sync'] ?? 'Never';
$last_sync_status = $sync_status['last_sync_status'] ?? 'unknown';
$pending_total = $sync_status['pending_total'] ?? 0;
$pending_by_table = $sync_status['pending_by_table'] ?? [];
$pending_deletions = $sync_status['pending_deletions'] ?? 0;
$sync_enabled = $sync_status['sync_enabled'] ?? true;

// Task 4.5: Operation type breakdown
$pending_by_operation_type = $sync_status['pending_by_operation_type'] ?? [
    'insert' => 0,
    'update' => 0,
    'both' => 0
];

// Multi-location data
$locations = $sync_status['locations'] ?? [];
$active_locations = $sync_status['active_locations'] ?? 0;
$total_locations = $sync_status['total_locations'] ?? 0;
$conflicts_count = $sync_status['conflicts_count'] ?? 0;
$realtime_enabled = $sync_status['realtime_enabled'] ?? false;
$metrics = $sync_status['metrics'] ?? [];
?>

<!-- Load Design System CSS -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-design-system.css?v=<?php echo time(); ?>">

<style>
/* Dashboard-specific styles - extends design system */

/* Dashboard Container */
.sync-dashboard-container {
    padding: var(--spacing-xl);
    max-width: 1400px;
    margin: 0 auto;
}

/* Gradient Header Section - Requirements: 1.1 */
.sync-dashboard-header {
    background: linear-gradient(135deg, var(--theme-primary, #667eea) 0%, var(--theme-secondary, #764ba2) 100%);
    color: white;
    padding: var(--spacing-3xl);
    border-radius: var(--radius-xl);
    margin-bottom: var(--spacing-3xl);
    position: relative;
    box-shadow: var(--shadow-lg);
}

.sync-dashboard-header h1 {
    margin: 0 0 var(--spacing-md) 0;
    font-size: var(--font-size-2xl);
    color: white;
    font-weight: var(--font-weight-bold);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.sync-dashboard-header p {
    margin: 0;
    opacity: 0.9;
    color: white;
    font-size: var(--font-size-sm);
}

/* Header Stats Summary - Requirements: 1.1 */
.sync-header-stats {
    display: flex;
    gap: var(--spacing-3xl);
    margin-top: var(--spacing-xl);
    flex-wrap: wrap;
}

.sync-header-stat {
    text-align: center;
    flex: 1;
    min-width: 120px;
}

.sync-header-stat-value {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    color: white;
    line-height: 1;
}

.sync-header-stat-label {
    font-size: var(--font-size-xs);
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: var(--spacing-sm);
    color: white;
}

/* Real-time Sync Indicator - Requirements: 1.6 */
.sync-realtime-indicator {
    position: absolute;
    top: var(--spacing-xl);
    right: 180px;
    padding: var(--spacing-sm) var(--spacing-lg);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(4px);
}

.sync-realtime-indicator.active {
    color: var(--color-success);
}

.sync-realtime-indicator.inactive {
    color: var(--color-gray-300);
}

.sync-realtime-pulse {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--color-success);
    position: relative;
}

.sync-realtime-indicator.active .sync-realtime-pulse::before {
    content: '';
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    border-radius: 50%;
    border: 2px solid var(--color-success);
    animation: sync-pulse-ring 1.5s infinite;
}

@keyframes sync-pulse-ring {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

/* Connection Status Badge */
.sync-connection-badge {
    position: absolute;
    top: var(--spacing-xl);
    right: var(--spacing-xl);
    padding: var(--spacing-sm) var(--spacing-lg);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.sync-connection-badge.online {
    background: rgba(16, 185, 129, 0.2);
    border: 2px solid rgba(16, 185, 129, 0.5);
    color: var(--color-success);
}

.sync-connection-badge.offline {
    background: rgba(239, 68, 68, 0.2);
    border: 2px solid rgba(239, 68, 68, 0.5);
    color: var(--color-error);
}

.sync-connection-badge .sync-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.sync-connection-badge.online .sync-status-dot {
    background: var(--color-success);
    animation: sync-pulse 2s infinite;
}

.sync-connection-badge.offline .sync-status-dot {
    background: var(--color-error);
}

/* Status Cards Grid - Requirements: 1.3, 1.4 */
.sync-status-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-3xl);
}

/* Responsive breakpoints - Requirements: 6.1, 6.3 */
@media (max-width: 1200px) {
    .sync-status-cards-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .sync-status-cards-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .sync-dashboard-header {
        padding: var(--spacing-xl);
    }
    
    .sync-header-stats {
        gap: var(--spacing-xl);
    }
    
    .sync-realtime-indicator {
        position: static;
        margin-top: var(--spacing-lg);
        width: fit-content;
    }
    
    .sync-connection-badge {
        position: static;
        margin-top: var(--spacing-lg);
        width: fit-content;
    }
}

@media (max-width: 480px) {
    .sync-status-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .sync-header-stats {
        flex-direction: column;
        gap: var(--spacing-lg);
    }
    
    .sync-dashboard-container {
        padding: var(--spacing-lg);
    }
}

/* Section Styles */
.sync-alerts-section {
    margin-bottom: var(--spacing-3xl);
}

.sync-quick-actions-section {
    margin-bottom: var(--spacing-3xl);
}

.sync-location-health-section {
    margin-bottom: var(--spacing-3xl);
}

.sync-charts-section {
    margin-bottom: var(--spacing-3xl);
}

.sync-pending-records-section {
    margin-bottom: var(--spacing-3xl);
}

.sync-manual-controls-section {
    margin-bottom: var(--spacing-3xl);
}

/* Quick Action Cards - Requirements: 16.1, 16.2, 16.3 */
.sync-quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-xl);
}

.sync-quick-action-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-2xl);
    text-align: center;
    cursor: pointer;
    transition: all var(--transition-base);
    box-shadow: var(--shadow-md);
    text-decoration: none;
    color: inherit;
    display: block;
}

.sync-quick-action-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
    text-decoration: none;
}

.sync-quick-action-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto var(--spacing-lg);
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    background: linear-gradient(135deg, var(--theme-primary, #667eea) 0%, var(--theme-secondary, #764ba2) 100%);
    color: white;
}

.sync-quick-action-title {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-sm) 0;
}

.sync-quick-action-description {
    font-size: var(--font-size-xs);
    color: var(--color-gray-600);
    margin: 0;
}

/* Location Health Grid - Requirements: 1.4, 1.5, 1.7, 7.2, 7.3, 7.8, 11.1, 11.3 */
.sync-location-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-xl);
}

.sync-location-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    border-left: 4px solid var(--color-gray-300);
}

.sync-location-card.online {
    border-left-color: var(--color-success);
}

.sync-location-card.offline {
    border-left-color: var(--color-error);
}

.sync-location-card.warning {
    border-left-color: var(--color-warning);
}

.sync-location-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.sync-location-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-md);
}

.sync-location-name {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-xs) 0;
}

.sync-location-device {
    font-size: var(--font-size-xs);
    color: var(--color-gray-500);
    margin: 0;
}

.sync-location-status-badge {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-md);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
}

.sync-location-status-badge.online {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}

.sync-location-status-badge.offline {
    background: var(--color-error-light);
    color: var(--color-error-dark);
}

.sync-location-info {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-gray-200);
}

.sync-location-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-sm);
    font-size: var(--font-size-xs);
}

.sync-location-info-label {
    color: var(--color-gray-600);
}

.sync-location-info-value {
    color: var(--color-gray-900);
    font-weight: var(--font-weight-medium);
}

.sync-location-actions {
    margin-top: var(--spacing-md);
    display: flex;
    gap: var(--spacing-sm);
}

/* Charts Section - Requirements: 1.8, 15.1-15.10 */
.sync-charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-xl);
}

.sync-chart-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-2xl);
    box-shadow: var(--shadow-md);
}

.sync-chart-header {
    margin-bottom: var(--spacing-xl);
}

.sync-chart-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-xs) 0;
}

.sync-chart-subtitle {
    font-size: var(--font-size-xs);
    color: var(--color-gray-600);
    margin: 0;
}

.sync-chart-container {
    position: relative;
    height: 300px;
}

/* Pending Records List - Requirements: 1.10 */
.sync-pending-list {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-2xl);
    box-shadow: var(--shadow-md);
}

.sync-pending-item {
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--color-gray-200);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.sync-pending-item:last-child {
    border-bottom: none;
}

.sync-pending-item:hover {
    background: var(--color-gray-50);
}

.sync-pending-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sync-pending-item-title {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.sync-pending-item-count {
    background: var(--color-warning-light);
    color: var(--color-warning-dark);
    padding: var(--spacing-xs) var(--spacing-md);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-bold);
}

.sync-pending-item-icon {
    transition: transform var(--transition-base);
}

.sync-pending-item.expanded .sync-pending-item-icon {
    transform: rotate(90deg);
}

.sync-pending-item-details {
    max-height: 0;
    overflow: hidden;
    transition: max-height var(--transition-base);
}

.sync-pending-item.expanded .sync-pending-item-details {
    max-height: 500px;
    margin-top: var(--spacing-md);
}

/* Manual Sync Controls - Requirements: 13.4, 13.5, 16.4, 16.5, 16.6, 16.7 */
.sync-manual-controls {
    background: white;
    border-radius: var(--radius-xl);
    padding: var(--spacing-2xl);
    box-shadow: var(--shadow-md);
}

.sync-manual-controls-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-lg);
    margin-top: var(--spacing-xl);
}

.sync-manual-btn {
    padding: var(--spacing-lg) var(--spacing-xl);
    font-size: var(--font-size-base);
    min-height: 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
}

.sync-manual-btn i {
    font-size: 24px;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
    .sync-quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .sync-location-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .sync-charts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .sync-quick-actions-grid,
    .sync-location-grid,
    .sync-manual-controls-grid {
        grid-template-columns: 1fr;
    }
    
    .sync-chart-container {
        height: 250px;
    }
}
</style>

<div class="sync-dashboard-container">
    <!-- Gradient Header Section - Task 2.1 Complete -->
    <div class="sync-dashboard-header">
        <h1>
            <i class="fa fa-sync-alt"></i>
            Multi-Location Sync Dashboard
        </h1>
        <p>Monitor and control bidirectional synchronization across all locations</p>
        
        <!-- Real-time Sync Indicator -->
        <div class="sync-realtime-indicator <?php echo $realtime_enabled ? 'active' : 'inactive'; ?>" id="realtime-indicator">
            <div class="sync-realtime-pulse"></div>
            <span><?php echo $realtime_enabled ? 'Real-time Sync Active' : 'Real-time Sync Off'; ?></span>
        </div>
        
        <!-- Connection Status Badge -->
        <div class="sync-connection-badge <?php echo $internet_online ? 'online' : 'offline'; ?>" id="connection-status-badge">
            <span class="sync-status-dot"></span>
            <span><?php echo $internet_online ? 'Online' : 'Offline'; ?></span>
        </div>
        
        <!-- Header Stats Summary -->
        <div class="sync-header-stats">
            <div class="sync-header-stat">
                <div class="sync-header-stat-value"><?php echo $active_locations; ?>/<?php echo $total_locations; ?></div>
                <div class="sync-header-stat-label">Active Locations</div>
            </div>
            <div class="sync-header-stat">
                <div class="sync-header-stat-value" id="header-pending"><?php echo number_format($pending_total); ?></div>
                <div class="sync-header-stat-label">Pending Records</div>
            </div>
            <div class="sync-header-stat">
                <div class="sync-header-stat-value" id="header-pending-deletions"><?php echo number_format($pending_deletions); ?></div>
                <div class="sync-header-stat-label">Pending Deletions</div>
            </div>
            <div class="sync-header-stat">
                <div class="sync-header-stat-value" id="header-conflicts"><?php echo number_format($conflicts_count); ?></div>
                <div class="sync-header-stat-label">Conflicts</div>
            </div>
        </div>
    </div>

    <!-- Alerts Section - Task 3.8: Error Display + Task 3.9: Status Indicators -->
    <div class="sync-alerts-section">
        <?php 
        // Task 3.8: Check for sync errors
        $error_states = ['failed_connection', 'failed', 'failed_disk_space', 'failed_no_internet', 'failed_system'];
        $has_error = in_array($last_sync_status, $error_states);
        $error_data = $sync_status['last_sync_error'] ?? null;
        
        // Display error alert if present
        if ($has_error && $error_data): 
            $error_message = $error_data['error_message'] ?? 'Sync operation failed';
            $error_timestamp = $error_data['timestamp'] ?? date('Y-m-d H:i:s');
            $error_type = $error_data['error_type'] ?? 'unknown';
            $corrective_action = $error_data['corrective_action'] ?? '';
        ?>
        <div class="sync-card sync-card-error" id="sync-error-alert" style="margin-bottom: var(--spacing-lg);">
            <div class="sync-card-body">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1; display: flex; align-items: flex-start; gap: var(--spacing-lg);">
                        <i class="fa fa-exclamation-circle" style="font-size: 32px; color: var(--color-error);"></i>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 var(--spacing-xs) 0; color: var(--color-error);">
                                <i class="fa fa-times-circle"></i> Sync Failure Detected
                            </h4>
                            <p style="margin: 0 0 var(--spacing-sm) 0; font-weight: var(--font-weight-semibold);">
                                <?php echo htmlspecialchars($error_message); ?>
                            </p>
                            <div style="display: flex; gap: var(--spacing-2xl); margin-bottom: var(--spacing-md); font-size: var(--font-size-sm); color: var(--color-gray-600);">
                                <div>
                                    <strong>Time:</strong> <?php echo date('M d, Y H:i:s', strtotime($error_timestamp)); ?>
                                </div>
                                <div>
                                    <strong>Error Type:</strong> <span style="text-transform: capitalize;"><?php echo str_replace('_', ' ', $error_type); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($corrective_action): ?>
                            <div style="background: rgba(255, 255, 255, 0.1); padding: var(--spacing-md); border-radius: var(--radius-md); margin-top: var(--spacing-sm);">
                                <strong style="color: var(--color-error);"><i class="fa fa-lightbulb"></i> Corrective Action:</strong><br>
                                <?php echo htmlspecialchars($corrective_action); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
                                <a href="#" class="sync-btn sync-btn-secondary sync-btn-sm" id="view-error-log-btn">
                                    <i class="fa fa-list"></i> View Full Error Log
                                </a>
                                <a href="#" class="sync-btn sync-btn-secondary sync-btn-sm" id="view-failed-records-btn">
                                    <i class="fa fa-exclamation-triangle"></i> View Failed Records
                                </a>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="sync-btn sync-btn-text" id="dismiss-error-alert" style="padding: var(--spacing-xs);">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($conflicts_count > 0): ?>
        <div class="sync-card sync-card-warning">
            <div class="sync-card-body">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: var(--spacing-lg);">
                        <i class="fa fa-exclamation-triangle" style="font-size: 32px; color: var(--color-warning);"></i>
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-xs) 0;"><?php echo $conflicts_count; ?> Sync Conflicts Detected</h4>
                            <p style="margin: 0; color: var(--color-gray-600);">Some records require manual resolution before sync can complete.</p>
                        </div>
                    </div>
                    <a href="<?php echo site_url('sync_conflicts'); ?>" class="sync-btn sync-btn-danger">
                        <i class="fa fa-wrench"></i> Resolve Conflicts
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$sync_enabled): ?>
        <div class="sync-card sync-card-warning" style="margin-top: var(--spacing-lg);">
            <div class="sync-card-body">
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <i class="fa fa-exclamation-triangle" style="font-size: 20px; color: var(--color-warning);"></i>
                    <div>
                        <strong>Sync Disabled</strong> - Automatic synchronization is currently disabled. Enable it in settings to resume sync operations.
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($pending_total > 100): ?>
        <div class="sync-card sync-card-info" style="margin-top: var(--spacing-lg);">
            <div class="sync-card-body">
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <i class="fa fa-info-circle" style="font-size: 20px; color: var(--color-info);"></i>
                    <div>
                        <strong>High Pending Count</strong> - You have <?php echo number_format($pending_total); ?> pending records. Consider running a manual sync.
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Status Cards Grid - Task 2.1 Complete -->
    <div class="sync-status-cards-grid">
        <!-- Internet Connection Card -->
        <div class="sync-card sync-card-<?php echo $internet_online ? 'success' : 'error'; ?>" id="connection-status-card">
            <div class="sync-card-body">
                <div style="font-size: 24px; margin-bottom: var(--spacing-md); color: <?php echo $internet_online ? 'var(--color-success)' : 'var(--color-error)'; ?>;">
                    <i class="fa fa-<?php echo $internet_online ? 'signal' : 'unlink'; ?>" id="connection-status-icon"></i>
                </div>
                <div style="font-size: var(--font-size-xs); color: var(--color-gray-600); font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--spacing-sm);">
                    Internet Connection
                </div>
                <div style="font-size: var(--font-size-xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900);" id="connection-status-text">
                    <?php echo $internet_online ? 'Connected' : 'Offline'; ?>
                </div>
            </div>
        </div>

        <!-- Pending Records Card -->
        <div class="sync-card sync-card-warning">
            <div class="sync-card-body">
                <div style="font-size: 24px; margin-bottom: var(--spacing-md); color: var(--color-warning);">
                    <i class="fa fa-clock"></i>
                </div>
                <div style="font-size: var(--font-size-xs); color: var(--color-gray-600); font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--spacing-sm);">
                    Pending Records
                </div>
                <div style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900);" id="pending-count">
                    <?php echo number_format($pending_total); ?>
                </div>
            </div>
        </div>

        <!-- Last Sync Card -->
        <div class="sync-card sync-card-info">
            <div class="sync-card-body">
                <div style="font-size: 24px; margin-bottom: var(--spacing-md); color: var(--color-info);">
                    <i class="fa fa-history"></i>
                </div>
                <div style="font-size: var(--font-size-xs); color: var(--color-gray-600); font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--spacing-sm);">
                    Last Sync
                </div>
                <div style="font-size: var(--font-size-base); font-weight: var(--font-weight-bold); color: var(--color-gray-900);" id="last-sync-time">
                    <?php echo $last_sync != 'Never' ? date('M d, H:i', strtotime($last_sync)) : 'Never'; ?>
                </div>
                <div style="font-size: var(--font-size-xs); color: var(--color-gray-500); margin-top: var(--spacing-xs);">
                    <?php echo str_replace('_', ' ', $last_sync_status); ?>
                </div>
            </div>
        </div>

        <!-- Conflicts Card -->
        <div class="sync-card sync-card-<?php echo $conflicts_count > 0 ? 'error' : 'success'; ?>">
            <div class="sync-card-body">
                <div style="font-size: 24px; margin-bottom: var(--spacing-md); color: <?php echo $conflicts_count > 0 ? 'var(--color-error)' : 'var(--color-success)'; ?>;">
                    <i class="fa fa-exclamation-circle"></i>
                </div>
                <div style="font-size: var(--font-size-xs); color: var(--color-gray-600); font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--spacing-sm);">
                    Conflicts
                </div>
                <div style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900);" id="conflicts-count">
                    <?php echo number_format($conflicts_count); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Task 4.5: Operation Type Breakdown Section -->
    <?php if ($pending_total > 0): ?>
    <div class="sync-operation-breakdown-section" style="margin-bottom: var(--spacing-3xl);">
        <div class="sync-section-header">
            <h2 class="sync-section-title">
                <i class="fa fa-tasks"></i> Pending Operations Breakdown
            </h2>
            <p class="sync-section-description">Breakdown of pending sync operations by type</p>
        </div>
        
        <div class="sync-status-cards-grid" style="grid-template-columns: repeat(3, 1fr);">
            <!-- INSERT Operations Card -->
            <div class="sync-card sync-card-success">
                <div class="sync-card-body">
                    <div style="font-size: 24px; margin-bottom: var(--spacing-md); color: var(--color-success);">
                        <i class="fa fa-plus-circle"></i>
                    </div>
                    <div style="font-size: var(--font-size-xs); color: var(--color-gray-600); font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--spacing-sm);">
                        INSERT Operations
                    </div>
                    <div style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900);">
                        <?php echo number_format($pending_by_operation_type['insert']); ?>
                    </div>
                    <div style="font-size: var(--font-size-xs); color: var(--color-gray-500); margin-top: var(--spacing-xs);">
                        New records to create
                    </div>
                </div>
            </div>

            <!-- UPDATE Operations Card -->
            <div class="sync-card sync-card-info">
                <div class="sync-card-body">
                    <div style="font-size: 24px; margin-bottom: var(--spacing-md); color: var(--color-info);">
                        <i class="fa fa-edit"></i>
                    </div>
                    <div style="font-size: var(--font-size-xs); color: var(--color-gray-600); font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--spacing-sm);">
                        UPDATE Operations
                    </div>
                    <div style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900);">
                        <?php echo number_format($pending_by_operation_type['update']); ?>
                    </div>
                    <div style="font-size: var(--font-size-xs); color: var(--color-gray-500); margin-top: var(--spacing-xs);">
                        Modified records to update
                    </div>
                </div>
            </div>

            <!-- BOTH Operations Card -->
            <div class="sync-card sync-card-warning">
                <div class="sync-card-body">
                    <div style="font-size: 24px; margin-bottom: var(--spacing-md); color: var(--color-warning);">
                        <i class="fa fa-exchange-alt"></i>
                    </div>
                    <div style="font-size: var(--font-size-xs); color: var(--color-gray-600); font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: var(--spacing-sm);">
                        BOTH Operations
                    </div>
                    <div style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900);">
                        <?php echo number_format($pending_by_operation_type['both']); ?>
                    </div>
                    <div style="font-size: var(--font-size-xs); color: var(--color-gray-500); margin-top: var(--spacing-xs);">
                        Records created and modified
                    </div>
                </div>
            </div>
        </div>
        
        <div class="sync-card" style="margin-top: var(--spacing-lg); background: var(--color-gray-50); border: none;">
            <div class="sync-card-body">
                <p style="margin: 0; font-size: var(--font-size-sm); color: var(--color-gray-700);">
                    <i class="fa fa-info-circle" style="color: var(--color-info);"></i>
                    <strong>Operation Type Guide:</strong> 
                    <strong class="text-success">INSERT</strong> creates new records on remote, 
                    <strong class="text-info">UPDATE</strong> modifies previously-synced records, 
                    <strong class="text-warning">BOTH</strong> indicates records that were created and modified before first sync.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Action Cards Section - Task 2.5 -->
    <div class="sync-quick-actions-section">
        <div class="sync-section-header">
            <h2 class="sync-section-title">Quick Actions</h2>
        </div>
        
        <div class="sync-quick-actions-grid">
            <a href="<?php echo site_url('admin/sync_locations'); ?>" class="sync-quick-action-card">
                <div class="sync-quick-action-icon">
                    <i class="fa fa-building"></i>
                </div>
                <h3 class="sync-quick-action-title">Manage Locations</h3>
                <p class="sync-quick-action-description">Add, edit, or remove sync locations</p>
            </a>
            
            <a href="<?php echo site_url('admin/sync_conflicts'); ?>" class="sync-quick-action-card">
                <div class="sync-quick-action-icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <h3 class="sync-quick-action-title">Resolve Conflicts</h3>
                <p class="sync-quick-action-description">Review and resolve sync conflicts</p>
            </a>
            
            <a href="<?php echo site_url('admin/sync_audit'); ?>" class="sync-quick-action-card">
                <div class="sync-quick-action-icon">
                    <i class="fa fa-history"></i>
                </div>
                <h3 class="sync-quick-action-title">View Audit Trail</h3>
                <p class="sync-quick-action-description">Track all sync operations and changes</p>
            </a>
            
            <a href="<?php echo site_url('admin/sync_settings'); ?>" class="sync-quick-action-card">
                <div class="sync-quick-action-icon">
                    <i class="fa fa-cog"></i>
                </div>
                <h3 class="sync-quick-action-title">Sync Settings</h3>
                <p class="sync-quick-action-description">Configure sync behavior and strategies</p>
            </a>
        </div>
    </div>

    <!-- Manual Sync Controls Section - Task 2.7 (Moved to top) -->
    <div class="sync-manual-controls-section">
        <div class="sync-manual-controls">
            <div class="sync-section-header" style="margin-bottom: 0;">
                <div>
                    <h2 class="sync-section-title">Manual Sync Controls</h2>
                    <p class="sync-section-description">Trigger sync operations manually</p>
                </div>
                <button class="sync-btn sync-btn-secondary sync-btn-sm" id="refresh-dashboard-btn">
                    <i class="fa fa-sync-alt"></i> Refresh Dashboard
                </button>
            </div>
            
            <div class="sync-manual-controls-grid">
                <!-- <button class="sync-btn sync-btn-primary disabled sync-manual-btn" id="run-manual-sync-btn-disabled" disabled="disabled">
                    <i class="fa fa-sync-alt"></i>
                    <span>Run Full Sync</span>
                </button>
                
                <button class="sync-btn sync-btn-success disabled sync-manual-btn" id="pull-from-remote-btn-disabled" disabled="disabled">
                    <i class="fa fa-download"></i>
                    <span>Pull from Remote</span>
                </button> -->
                
                <button class="sync-btn sync-btn-success sync-manual-btn" id="push-to-remote-btn">
                    <i class="fa fa-upload"></i>
                    <span>Push to Remote</span>
                </button>
            </div>
            
            <!-- Progress Indicator (hidden by default) -->
            <div id="sync-progress-container" style="display: none; margin-top: var(--spacing-xl);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-sm);">
                    <span id="sync-progress-text" style="font-size: var(--font-size-sm); color: var(--color-gray-700); font-weight: var(--font-weight-medium);">Syncing...</span>
                    <span id="sync-progress-percentage" style="font-size: var(--font-size-sm); color: var(--color-gray-700); font-weight: var(--font-weight-bold);">0%</span>
                </div>
                <div class="sync-progress">
                    <div class="sync-progress-bar" id="sync-progress-bar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Task 3.10: Error Log Section (Collapsible) -->
    <div class="sync-error-log-section" id="error-log-section" style="display: none; margin-bottom: var(--spacing-3xl);">
        <div class="sync-card">
            <div class="sync-card-body">
                <div class="sync-section-header" style="margin-bottom: var(--spacing-xl);">
                    <div>
                        <h2 class="sync-section-title">
                            <i class="fa fa-list"></i> Sync Error Log
                        </h2>
                        <p class="sync-section-description">Recent sync errors and failures</p>
                    </div>
                    <div style="display: flex; gap: var(--spacing-md);">
                        <button class="sync-btn sync-btn-secondary sync-btn-sm" id="download-error-log-btn">
                            <i class="fa fa-download"></i> Download Full Log
                        </button>
                        <button class="sync-btn sync-btn-text sync-btn-sm" id="close-error-log-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Error Filter -->
                <div style="margin-bottom: var(--spacing-lg);">
                    <label style="font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); margin-bottom: var(--spacing-sm); display: block;">Filter by Error Type:</label>
                    <select class="form-control" id="error-type-filter" style="max-width: 300px;">
                        <option value="all">All Errors</option>
                        <option value="connection">Connection Errors</option>
                        <option value="foreign_key">Foreign Key Errors</option>
                        <option value="duplicate">Duplicate Entry Errors</option>
                        <option value="constraint">Constraint Errors</option>
                        <option value="timeout">Timeout Errors</option>
                        <option value="authentication">Authentication Errors</option>
                        <option value="no_internet">No Internet Errors</option>
                    </select>
                </div>
                
                <!-- Error Log Container -->
                <div id="error-log-container">
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--color-gray-500);">
                        <i class="fa fa-spinner fa-spin" style="font-size: 24px;"></i>
                        <p>Loading error log...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Task 3.10: Failed Records Section (Collapsible) -->
    <div class="sync-failed-records-section" id="failed-records-section" style="display: none; margin-bottom: var(--spacing-3xl);">
        <div class="sync-card">
            <div class="sync-card-body">
                <div class="sync-section-header" style="margin-bottom: var(--spacing-xl);">
                    <div>
                        <h2 class="sync-section-title">
                            <i class="fa fa-exclamation-triangle"></i> Failed Records
                        </h2>
                        <p class="sync-section-description">Records that failed to sync and require attention</p>
                    </div>
                    <div style="display: flex; gap: var(--spacing-md);">
                        <button class="sync-btn sync-btn-danger sync-btn-sm" id="retry-all-failed-records-btn">
                            <i class="fa fa-redo"></i> Retry All Failed Records
                        </button>
                        <button class="sync-btn sync-btn-primary sync-btn-sm" id="retry-all-failed-btn" disabled>
                            <i class="fa fa-redo"></i> Retry Selected
                        </button>
                        <button class="sync-btn sync-btn-text sync-btn-sm" id="close-failed-records-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Failed Records Summary -->
                <div id="failed-records-summary" style="display: flex; gap: var(--spacing-xl); margin-bottom: var(--spacing-xl); padding: var(--spacing-lg); background: var(--color-gray-50); border-radius: var(--radius-md);">
                    <div>
                        <span style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); color: var(--color-error);" id="total-failed-count">0</span>
                        <span style="font-size: var(--font-size-sm); color: var(--color-gray-600); display: block;">Total Failed</span>
                    </div>
                    <div>
                        <span style="font-size: var(--font-size-xl); font-weight: var(--font-weight-semibold);" id="foreign-key-count">0</span>
                        <span style="font-size: var(--font-size-sm); color: var(--color-gray-600); display: block;">Foreign Key</span>
                    </div>
                    <div>
                        <span style="font-size: var(--font-size-xl); font-weight: var(--font-weight-semibold);" id="duplicate-count">0</span>
                        <span style="font-size: var(--font-size-sm); color: var(--color-gray-600); display: block;">Duplicates</span>
                    </div>
                    <div>
                        <span style="font-size: var(--font-size-xl); font-weight: var(--font-weight-semibold);" id="constraint-count">0</span>
                        <span style="font-size: var(--font-size-sm); color: var(--color-gray-600); display: block;">Constraints</span>
                    </div>
                </div>
                
                <!-- Failed Records Filter -->
                <div style="margin-bottom: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
                    <div style="flex: 1;">
                        <label style="font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); margin-bottom: var(--spacing-sm); display: block;">Filter by Table:</label>
                        <select class="form-control" id="failed-table-filter">
                            <option value="all">All Tables</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); margin-bottom: var(--spacing-sm); display: block;">Filter by Error Type:</label>
                        <select class="form-control" id="failed-error-type-filter">
                            <option value="all">All Error Types</option>
                            <option value="foreign_key">Foreign Key</option>
                            <option value="duplicate">Duplicate</option>
                            <option value="constraint">Constraint</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <!-- Failed Records Table -->
                <div id="failed-records-container">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="failed-records-table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Table Name</th>
                                    <th style="width: 100px;">Record ID</th>
                                    <th style="width: 120px;">Error Type</th>
                                    <th>Error Message</th>
                                    <th style="width: 140px;">Verification Status</th>
                                    <th style="width: 80px;">Retry Count</th>
                                    <th style="width: 150px;">Failed At</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="failed-records-tbody">
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: var(--spacing-2xl); color: var(--color-gray-500);">
                                        <i class="fa fa-spinner fa-spin" style="font-size: 24px;"></i>
                                        <p>Loading failed records...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div id="failed-records-pagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--spacing-lg); padding-top: var(--spacing-lg); border-top: 1px solid var(--color-gray-200);">
                        <div style="font-size: var(--font-size-sm); color: var(--color-gray-600);">
                            Showing <span id="failed-showing-from">0</span> to <span id="failed-showing-to">0</span> of <span id="failed-total-records">0</span> records
                        </div>
                        <div style="display: flex; gap: var(--spacing-sm);">
                            <button class="sync-btn sync-btn-secondary sync-btn-sm" id="failed-prev-page" disabled>
                                <i class="fa fa-chevron-left"></i> Previous
                            </button>
                            <span style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm); color: var(--color-gray-600);">
                                Page <span id="failed-current-page">1</span> of <span id="failed-total-pages">1</span>
                            </span>
                            <button class="sync-btn sync-btn-secondary sync-btn-sm" id="failed-next-page" disabled>
                                Next <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Health Grid Section - Task 2.4 -->
    <div class="sync-location-health-section">
        <div class="sync-section-header">
            <h2 class="sync-section-title">Location Health</h2>
            <a href="<?php echo site_url('admin/sync_locations'); ?>" class="sync-btn sync-btn-secondary sync-btn-sm">
                <i class="fa fa-plus"></i> Add Location
            </a>
        </div>
        
        <div class="sync-location-grid" id="location-health-grid">
            <?php if (!empty($locations)): ?>
                <?php foreach ($locations as $location): ?>
                    <?php 
                    $is_online = $location['is_online'] ?? false;
                    $status_class = $is_online ? 'online' : 'offline';
                    $last_sync_time = $location['last_sync'] ?? 'Never';
                    ?>
                    <div class="sync-location-card <?php echo $status_class; ?>">
                        <div class="sync-location-header">
                            <div>
                                <h3 class="sync-location-name"><?php echo htmlspecialchars($location['location_name']); ?></h3>
                                <p class="sync-location-device">
                                    <i class="fa fa-desktop"></i> <?php echo htmlspecialchars($location['device_id']); ?>
                                </p>
                            </div>
                            <span class="sync-location-status-badge <?php echo $status_class; ?>">
                                <?php if ($is_online): ?>
                                    <span class="sync-status-dot online"></span>
                                <?php endif; ?>
                                <?php echo $is_online ? 'Online' : 'Offline'; ?>
                            </span>
                        </div>
                        
                        <div class="sync-location-info">
                            <div class="sync-location-info-item">
                                <span class="sync-location-info-label">Last Sync:</span>
                                <span class="sync-location-info-value">
                                    <?php echo $last_sync_time != 'Never' ? date('M d, H:i', strtotime($last_sync_time)) : 'Never'; ?>
                                </span>
                            </div>
                            <div class="sync-location-info-item">
                                <span class="sync-location-info-label">Status:</span>
                                <span class="sync-location-info-value"><?php echo ucfirst($location['status'] ?? 'unknown'); ?></span>
                            </div>
                        </div>
                        
                        <div class="sync-location-actions">
                            <a href="<?php echo site_url('admin/sync_locations/edit/' . $location['id']); ?>" class="sync-btn sync-btn-secondary sync-btn-sm" style="flex: 1;">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="<?php echo site_url('admin/sync_locations/view/' . $location['id']); ?>" class="sync-btn sync-btn-ghost sync-btn-sm" style="flex: 1;">
                                <i class="fa fa-eye"></i> Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="sync-card" style="grid-column: 1 / -1; text-align: center; padding: var(--spacing-3xl);">
                    <i class="fa fa-building" style="font-size: 48px; color: var(--color-gray-400); margin-bottom: var(--spacing-lg);"></i>
                    <h3 style="color: var(--color-gray-600); margin-bottom: var(--spacing-md);">No Locations Configured</h3>
                    <p style="color: var(--color-gray-500); margin-bottom: var(--spacing-xl);">Add your first sync location to get started</p>
                    <a href="<?php echo site_url('admin/sync_locations/add'); ?>" class="sync-btn sync-btn-primary">
                        <i class="fa fa-plus"></i> Add Location
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Charts Section - Task 2.3 -->
    <div class="sync-charts-section">
        <div class="sync-section-header">
            <h2 class="sync-section-title">Performance Metrics</h2>
        </div>
        
        <div class="sync-charts-grid">
            <!-- Line Chart: Sync Performance Over Time -->
            <div class="sync-chart-card">
                <div class="sync-chart-header">
                    <h3 class="sync-chart-title">Sync Operations (Last 7 Days)</h3>
                    <p class="sync-chart-subtitle">Track push, pull, and conflict trends</p>
                </div>
                <div class="sync-chart-container">
                    <canvas id="sync-performance-chart"></canvas>
                </div>
            </div>
            
            <!-- Doughnut Chart: Sync Status Distribution -->
            <div class="sync-chart-card">
                <div class="sync-chart-header">
                    <h3 class="sync-chart-title">Sync Status Distribution</h3>
                    <p class="sync-chart-subtitle">Success vs failures</p>
                </div>
                <div class="sync-chart-container">
                    <canvas id="sync-status-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Records List Section - Task 2.6 -->
    <div class="sync-pending-records-section">
        <div class="sync-section-header">
            <h2 class="sync-section-title">Pending Records by Table</h2>
        </div>
        
        <div class="sync-pending-list">
            <?php if (!empty($pending_by_table)): ?>
                <?php foreach ($pending_by_table as $table => $count): ?>
                    <div class="sync-pending-item" data-table="<?php echo htmlspecialchars($table); ?>">
                        <div class="sync-pending-item-header">
                            <h4 class="sync-pending-item-title">
                                <i class="fa fa-chevron-right sync-pending-item-icon"></i>
                                <?php echo htmlspecialchars($table); ?>
                            </h4>
                            <span class="sync-pending-item-count"><?php echo number_format($count); ?> pending</span>
                        </div>
                        <div class="sync-pending-item-details">
                            <p style="font-size: var(--font-size-xs); color: var(--color-gray-600); margin: 0;">
                                These records are waiting to be synchronized with remote locations.
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: var(--spacing-3xl); color: var(--color-gray-500);">
                    <i class="fa fa-check-circle" style="font-size: 48px; color: var(--color-success); margin-bottom: var(--spacing-lg);"></i>
                    <h3 style="color: var(--color-gray-600);">All Caught Up!</h3>
                    <p>No pending records to sync</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>

<!-- Define base_url for JavaScript -->
<script>
    var base_url = '<?php echo base_url(); ?>';
    console.log('base_url defined:', base_url);
</script>

<!-- Dashboard JavaScript - Task 2.8 -->
<script src="<?php echo base_url(); ?>assets/backend/js/sync_dashboard_ui.js?v=<?php echo time(); ?>"></script>

<!-- Sync Dashboard Core - Task 3.11, 3.12, 6.1, 6.2 -->
<script src="<?php echo base_url(); ?>assets/backend/js/sync_dashboard.js?v=<?php echo time(); ?>"></script>
