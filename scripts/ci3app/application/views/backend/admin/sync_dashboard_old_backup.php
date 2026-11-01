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
 */
$sync_status = $this->sync_status ?? [];
$internet_online = $sync_status['internet_online'] ?? false;
$last_sync = $sync_status['last_sync'] ?? 'Never';
$last_sync_status = $sync_status['last_sync_status'] ?? 'unknown';
$pending_total = $sync_status['pending_total'] ?? 0;
$pending_by_table = $sync_status['pending_by_table'] ?? [];
$sync_enabled = $sync_status['sync_enabled'] ?? true;

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

/* Gradient Header Section */
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

/* Header Stats Summary */
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

/* Real-time Sync Indicator */
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

/* Status Cards Grid */
.sync-status-cards-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-3xl);
}

/* Responsive breakpoints for status cards */
@media (max-width: 1200px) {
    .sync-status-cards-grid {
        grid-template-columns: repeat(3, 1fr);
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
</style>

<div class="sync-dashboard-container">
    <!-- Gradient Header Section -->
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
                <div class="sync-header-stat-value" id="header-conflicts"><?php echo number_format($conflicts_count); ?></div>
                <div class="sync-header-stat-label">Conflicts</div>
            </div>
        </div>
    </div>

/* Location Status Grid */
.location-section { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.location-section h3 { margin: 0 0 20px 0; font-size: 18px; color: #1f2937; display: flex; align-items: center; gap: 10px; }
.location-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
.location-card { 
    border: 1px solid #e5e7eb; 
    border-radius: 10px; 
    padding: 15px; 
    transition: all 0.2s;
}
.location-card:hover { border-color: #3b82f6; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1); }
.location-card.offline { border-color: #fecaca; background: #fef2f2; }
.location-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.location-name { font-weight: 600; color: #1f2937; font-size: 15px; }
.location-status { display: flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 500; }
.location-status.online { color: #10b981; }
.location-status.offline { color: #ef4444; }
.location-status-dot { width: 8px; height: 8px; border-radius: 50%; }
.location-status.online .location-status-dot { background: #10b981; }
.location-status.offline .location-status-dot { background: #ef4444; }
.location-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
.location-stat { text-align: center; padding: 8px; background: #f9fafb; border-radius: 6px; }
.location-stat-value { font-size: 16px; font-weight: 600; color: #1f2937; }
.location-stat-label { font-size: 10px; color: #6b7280; text-transform: uppercase; }
.location-last-sync { font-size: 11px; color: #9ca3af; margin-top: 10px; }

/* Sync Actions */
.sync-actions { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.sync-actions h3 { color: #1f2937; margin: 0 0 15px 0; font-size: 18px; }
.action-buttons { display: flex; gap: 15px; flex-wrap: wrap; }
.sync-btn { 
    padding: 12px 24px; 
    border: none; 
    border-radius: 8px; 
    font-weight: 600; 
    font-size: 14px; 
    cursor: pointer; 
    transition: all 0.3s; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    text-decoration: none;
}
.sync-btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
.sync-btn-primary:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.sync-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.sync-btn-secondary { background: #f3f4f6; color: #374151; }
.sync-btn-secondary:hover { background: #e5e7eb; }
.sync-btn-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.sync-btn-danger:hover { background: #fee2e2; }
.sync-btn i { font-size: 16px; }
.sync-btn.loading i { animation: spin 1s linear infinite; }

/* Charts Section */
.charts-section { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px; }
@media (max-width: 992px) { .charts-section { grid-template-columns: 1fr; } }
.chart-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.chart-card h4 { margin: 0 0 20px 0; font-size: 16px; color: #1f2937; display: flex; align-items: center; gap: 10px; }
.chart-container { height: 250px; position: relative; }
.chart-legend { display: flex; justify-content: center; gap: 20px; margin-top: 15px; }
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; }

/* Pending Tables */
.pending-tables { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.pending-tables h3 { margin: 0 0 20px 0; font-size: 18px; color: #1f2937; }
.pending-table-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #f3f4f6; }
.pending-table-item:last-child { border-bottom: none; }
.pending-table-item:hover { background: #f9fafb; }
.pending-table-name { font-weight: 600; color: #374151; }
.pending-table-count { background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 13px; }

/* Conflicts Alert */
.conflicts-alert { 
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); 
    border: 1px solid #f59e0b; 
    border-radius: 12px; 
    padding: 20px; 
    margin-bottom: 30px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
}
.conflicts-alert-content { display: flex; align-items: center; gap: 15px; }
.conflicts-alert-icon { font-size: 32px; color: #d97706; }
.conflicts-alert h4 { margin: 0 0 5px 0; color: #92400e; }
.conflicts-alert p { margin: 0; color: #b45309; font-size: 14px; }

/* Alerts */
.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
.alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
.alert-info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
.alert i { font-size: 20px; }

/* Quick Links */
.quick-links { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
@media (max-width: 768px) { .quick-links { grid-template-columns: repeat(2, 1fr); } }
.quick-link { 
    background: white; 
    border-radius: 10px; 
    padding: 20px; 
    text-align: center; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
    text-decoration: none; 
    color: inherit;
    transition: all 0.2s;
}
.quick-link:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.quick-link-icon { font-size: 28px; margin-bottom: 10px; }
.quick-link-title { font-weight: 600; color: #1f2937; font-size: 14px; }
.quick-link-desc { font-size: 12px; color: #6b7280; margin-top: 5px; }

/* Animations */
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
@keyframes pulse-ring { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }

/* Sync Progress */
.sync-progress { margin-top: 15px; display: none; }
.sync-progress.show { display: block; }
.progress-bar { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6); border-radius: 4px; transition: width 0.3s; }
.progress-text { font-size: 12px; color: #6b7280; margin-top: 5px; }
</style>

<div class="sync-dashboard">
    <!-- Header -->
    <div class="sync-header">
        <h1><i class="fa fa-sync-alt"></i> Multi-Location Sync Dashboard</h1>
        <p>Monitor and control bidirectional synchronization across all locations</p>
        
        <!-- Real-time Indicator -->
        <div class="realtime-indicator <?php echo $realtime_enabled ? 'active' : 'inactive'; ?>" id="realtime-indicator">
            <div class="pulse-ring"></div>
            <span><?php echo $realtime_enabled ? 'Real-time Sync Active' : 'Real-time Sync Off'; ?></span>
        </div>
        
        <!-- Offline Status Badge -->
        <div class="offline-status-badge <?php echo $internet_online ? 'online' : 'offline'; ?>" id="offline-status-badge">
            <span class="status-dot"></span>
            <span><?php echo $internet_online ? 'Online' : 'Offline'; ?></span>
        </div>
        
        <!-- Header Stats -->
        <div class="header-stats">
            <div class="header-stat">
                <div class="header-stat-value"><?php echo $active_locations; ?>/<?php echo $total_locations; ?></div>
                <div class="header-stat-label">Active Locations</div>
            </div>
            <div class="header-stat">
                <div class="header-stat-value" id="header-pending"><?php echo number_format($pending_total); ?></div>
                <div class="header-stat-label">Pending Records</div>
            </div>
            <div class="header-stat">
                <div class="header-stat-value" id="header-conflicts"><?php echo number_format($conflicts_count); ?></div>
                <div class="header-stat-label">Conflicts</div>
            </div>
        </div>
    </div>

    <!-- Conflicts Alert -->
    <?php if ($conflicts_count > 0): ?>
    <div class="conflicts-alert">
        <div class="conflicts-alert-content">
            <div class="conflicts-alert-icon">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <div>
                <h4><?php echo $conflicts_count; ?> Sync Conflicts Detected</h4>
                <p>Some records require manual resolution before sync can complete.</p>
            </div>
        </div>
        <a href="<?php echo site_url('sync_conflicts'); ?>" class="sync-btn sync-btn-danger">
            <i class="fa fa-wrench"></i> Resolve Conflicts
        </a>
    </div>
    <?php endif; ?>

    <!-- Alerts -->
    <?php if (!$sync_enabled): ?>
    <div class="alert alert-warning">
        <i class="fa fa-exclamation-triangle"></i>
        <div>
            <strong>Sync Disabled</strong> - Automatic synchronization is currently disabled. Enable it in settings to resume sync operations.
        </div>
    </div>
    <?php endif; ?>

    <?php if ($pending_total > 100): ?>
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <div>
            <strong>High Pending Count</strong> - You have <?php echo number_format($pending_total); ?> pending records. Consider running a manual sync.
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Cards -->
    <div class="status-cards">
        <!-- Internet Connection -->
        <div class="status-card <?php echo $internet_online ? 'online' : 'offline'; ?>" id="connection-status-card">
            <div class="status-card-icon">
                <i class="fa fa-<?php echo $internet_online ? 'signal' : 'unlink'; ?>"></i>
            </div>
            <div class="status-card-label">Internet Connection</div>
            <div class="status-card-value" style="font-size: 20px;">
                <?php echo $internet_online ? 'Connected' : 'Offline'; ?>
            </div>
        </div>

        <!-- Active Locations -->
        <div class="status-card synced">
            <div class="status-card-icon">
                <i class="fa fa-building"></i>
            </div>
            <div class="status-card-label">Active Locations</div>
            <div class="status-card-value"><?php echo $active_locations; ?></div>
            <div class="status-card-sub">of <?php echo $total_locations; ?> total</div>
        </div>

        <!-- Pending Records -->
        <div class="status-card pending">
            <div class="status-card-icon">
                <i class="fa fa-clock"></i>
            </div>
            <div class="status-card-label">Pending Records</div>
            <div class="status-card-value" id="pending-count"><?php echo number_format($pending_total); ?></div>
        </div>

        <!-- Last Sync -->
        <div class="status-card synced">
            <div class="status-card-icon">
                <i class="fa fa-history"></i>
            </div>
            <div class="status-card-label">Last Sync</div>
            <div class="status-card-value" style="font-size: 16px;" id="last-sync-time">
                <?php echo $last_sync != 'Never' ? date('M d, H:i', strtotime($last_sync)) : 'Never'; ?>
            </div>
            <div class="status-card-sub"><?php echo str_replace('_', ' ', $last_sync_status); ?></div>
        </div>

        <!-- Conflicts -->
        <div class="status-card <?php echo $conflicts_count > 0 ? 'conflict' : 'synced'; ?>">
            <div class="status-card-icon">
                <i class="fa fa-exclamation-circle"></i>
            </div>
            <div class="status-card-label">Conflicts</div>
            <div class="status-card-value" id="conflicts-count"><?php echo number_format($conflicts_count); ?></div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
        <a href="<?php echo site_url('locations'); ?>" class="quick-link">
            <div class="quick-link-icon" style="color: #3b82f6;"><i class="fa fa-building"></i></div>
            <div class="quick-link-title">Manage Locations</div>
            <div class="quick-link-desc">Add, edit, or deactivate locations</div>
        </a>
        <a href="<?php echo site_url('sync_conflicts'); ?>" class="quick-link">
            <div class="quick-link-icon" style="color: #8b5cf6;"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="quick-link-title">Resolve Conflicts</div>
            <div class="quick-link-desc">Handle sync conflicts</div>
        </a>
        <a href="<?php echo site_url('sync_audit'); ?>" class="quick-link">
            <div class="quick-link-icon" style="color: #10b981;"><i class="fa fa-history"></i></div>
            <div class="quick-link-title">Audit Trail</div>
            <div class="quick-link-desc">View sync operation history</div>
        </a>
        <a href="<?php echo site_url('sync_config'); ?>" class="quick-link">
            <div class="quick-link-icon" style="color: #f59e0b;"><i class="fa fa-cog"></i></div>
            <div class="quick-link-title">Sync Settings</div>
            <div class="quick-link-desc">Configure sync options</div>
        </a>
    </div>

    <!-- Location Status -->
    <?php if (!empty($locations)): ?>
    <div class="location-section">
        <h3><i class="fa fa-building"></i> Location Status</h3>
        <div class="location-grid">
            <?php foreach ($locations as $loc): ?>
            <div class="location-card <?php echo $loc['is_online'] ? '' : 'offline'; ?>">
                <div class="location-header">
                    <span class="location-name"><?php echo htmlspecialchars($loc['location_name']); ?></span>
                    <span class="location-status <?php echo $loc['is_online'] ? 'online' : 'offline'; ?>">
                        <span class="location-status-dot"></span>
                        <?php echo $loc['is_online'] ? 'Online' : 'Offline'; ?>
                    </span>
                </div>
                <div class="location-stats">
                    <div class="location-stat">
                        <div class="location-stat-value"><?php echo number_format($loc['pending_count'] ?? 0); ?></div>
                        <div class="location-stat-label">Pending</div>
                    </div>
                    <div class="location-stat">
                        <div class="location-stat-value"><?php echo number_format($loc['synced_today'] ?? 0); ?></div>
                        <div class="location-stat-label">Synced Today</div>
                    </div>
                    <div class="location-stat">
                        <div class="location-stat-value"><?php echo number_format($loc['conflicts'] ?? 0); ?></div>
                        <div class="location-stat-label">Conflicts</div>
                    </div>
                </div>
                <div class="location-last-sync">
                    <i class="fa fa-clock"></i> Last sync: <?php echo $loc['last_sync_at'] ? date('M d, H:i', strtotime($loc['last_sync_at'])) : 'Never'; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sync Actions -->
    <div class="sync-actions">
        <h3><i class="fa fa-cog"></i> Sync Actions</h3>
        <div class="action-buttons">
            <button id="manual-sync-btn" class="sync-btn sync-btn-primary" onclick="triggerManualSync()">
                <i class="fa fa-sync-alt"></i>
                <span>Run Manual Sync</span>
            </button>
            <button class="sync-btn sync-btn-secondary" onclick="triggerPullSync()">
                <i class="fa fa-arrow-down"></i>
                <span>Pull from Remote</span>
            </button>
            <button class="sync-btn sync-btn-secondary" onclick="triggerPushSync()">
                <i class="fa fa-arrow-up"></i>
                <span>Push to Remote</span>
            </button>
            <a href="<?php echo site_url('sync_config'); ?>" class="sync-btn sync-btn-secondary">
                <i class="fa fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
        <div class="sync-progress" id="sync-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
            </div>
            <div class="progress-text" id="progress-text">Initializing sync...</div>
        </div>
        <span id="sync-message" style="margin-left: 15px; font-size: 14px; color: #6b7280;"></span>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-card">
            <h4><i class="fa fa-chart-line"></i> Sync Performance (Last 7 Days)</h4>
            <div class="chart-container">
                <canvas id="performance-chart"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background: #3b82f6;"></div>
                    <span>Push Operations</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background: #8b5cf6;"></div>
                    <span>Pull Operations</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background: #f59e0b;"></div>
                    <span>Conflicts</span>
                </div>
            </div>
        </div>
        <div class="chart-card">
            <h4><i class="fa fa-pie-chart"></i> Sync by Status</h4>
            <div class="chart-container">
                <canvas id="status-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Pending Tables -->
    <?php if (!empty($pending_by_table)): ?>
    <div class="pending-tables">
        <h3><i class="fa fa-table"></i> Pending Records by Table</h3>
        <div id="pending-tables-list">
            <?php 
            $shown = 0;
            foreach ($pending_by_table as $table => $count): 
                if ($shown++ >= 10) break;
            ?>
            <div class="pending-table-item">
                <span class="pending-table-name"><?php echo ucwords(str_replace('_', ' ', $table)); ?></span>
                <span class="pending-table-count"><?php echo number_format($count); ?> pending</span>
            </div>
            <?php endforeach; ?>
            <?php if (count($pending_by_table) > 10): ?>
            <div class="pending-table-item" style="justify-content: center;">
                <a href="<?php echo site_url('sync_table_management'); ?>" style="color: #3b82f6; font-size: 14px;">
                    View all <?php echo count($pending_by_table); ?> tables →
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modern UI Enhancements -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-modern-ui.css?v=<?php echo time(); ?>">

<!-- Chart.js -->
<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/sync-modern-ui.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo base_url(); ?>assets/backend/js/sync_dashboard.js?v=<?php echo time(); ?>"></script>
<script>
// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initPerformanceChart();
    initStatusChart();
});

function initPerformanceChart() {
    var ctx = document.getElementById('performance-chart');
    if (!ctx) return;
    
    // Get data from backend or use defaults
    var labels = <?php echo json_encode(array_map(function($i) { return date('M d', strtotime("-$i days")); }, range(6, 0))); ?>;
    var pushData = <?php echo json_encode($metrics['push_by_day'] ?? array_fill(0, 7, 0)); ?>;
    var pullData = <?php echo json_encode($metrics['pull_by_day'] ?? array_fill(0, 7, 0)); ?>;
    var conflictData = <?php echo json_encode($metrics['conflicts_by_day'] ?? array_fill(0, 7, 0)); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Push',
                    data: pushData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Pull',
                    data: pullData,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Conflicts',
                    data: conflictData,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function initStatusChart() {
    var ctx = document.getElementById('status-chart');
    if (!ctx) return;
    
    var successCount = <?php echo $metrics['success_count'] ?? 0; ?>;
    var failedCount = <?php echo $metrics['failed_count'] ?? 0; ?>;
    var conflictCount = <?php echo $metrics['conflict_count'] ?? 0; ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Success', 'Failed', 'Conflicts'],
            datasets: [{
                data: [successCount, failedCount, conflictCount],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '60%'
        }
    });
}
</script>
