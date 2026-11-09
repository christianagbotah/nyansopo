<?php
$sync_status = $this->sync_status ?? [];
$internet_online = $sync_status['internet_online'] ?? false;
$last_sync = $sync_status['last_sync'] ?? 'Never';
$last_sync_status = $sync_status['last_sync_status'] ?? 'unknown';
$pending_total = $sync_status['pending_total'] ?? 0;
$pending_by_table = $sync_status['pending_by_table'] ?? [];
$sync_enabled = $sync_status['sync_enabled'] ?? true;
?>

<style>
.sync-dashboard { padding: 20px; }
.sync-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; position: relative; }
.sync-header h1 { margin: 0 0 10px 0; font-size: 28px; color: white; }
.sync-header p { margin: 0; opacity: 0.9; color: white; }
.offline-status-badge { position: absolute; top: 20px; right: 20px; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.offline-status-badge.online { background: rgba(16, 185, 129, 0.2); border: 2px solid rgba(16, 185, 129, 0.5); color: #10b981; }
.offline-status-badge.offline { background: rgba(239, 68, 68, 0.2); border: 2px solid rgba(239, 68, 68, 0.5); color: #ef4444; }
.offline-status-badge .status-dot { width: 8px; height: 8px; border-radius: 50%; }
.offline-status-badge.online .status-dot { background: #10b981; animation: pulse 2s infinite; }
.offline-status-badge.offline .status-dot { background: #ef4444; }
.status-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
@media (max-width: 1200px) { .status-cards { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .status-cards { grid-template-columns: 1fr; } }
.status-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-left: 4px solid; }
.status-card.online { border-left-color: #10b981; }
.status-card.offline { border-left-color: #ef4444; }
.status-card.pending { border-left-color: #f59e0b; }
.status-card.synced { border-left-color: #3b82f6; }
.status-card-label { font-size: 13px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
.status-card-value { font-size: 32px; font-weight: 700; color: #1f2937; }
.status-card-icon { font-size: 24px; margin-bottom: 10px; }
.sync-actions { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.sync-actions h3 { color: #1f2937; }
.sync-btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.sync-btn-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
.sync-btn-primary:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.sync-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.sync-btn i { font-size: 16px; }
.sync-btn.loading i { animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.pending-tables { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.pending-tables h3 { margin: 0 0 20px 0; font-size: 18px; color: #1f2937; }
.pending-table-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #f3f4f6; }
.pending-table-item:last-child { border-bottom: none; }
.pending-table-name { font-weight: 600; color: #374151; }
.pending-table-count { background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 13px; }
.sync-log { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.sync-log h3 { margin: 0 0 20px 0; font-size: 18px; color: #1f2937; }
.sync-log-empty { text-align: center; padding: 40px; color: #9ca3af; }
.connection-indicator { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
.connection-indicator.online { background: #d1fae5; color: #065f46; }
.connection-indicator.offline { background: #fee2e2; color: #991b1b; }
.connection-dot { width: 8px; height: 8px; border-radius: 50%; }
.connection-dot.online { background: #10b981; animation: pulse 2s infinite; }
.connection-dot.offline { background: #ef4444; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
.alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
.alert-info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
.alert i { font-size: 20px; }
</style>

<div class="sync-dashboard">
    <!-- Header -->
    <div class="sync-header">
        <h1><i class="fa fa-sync-alt"></i> Sync Dashboard</h1>
        <p>Monitor and control synchronization between local and cloud servers</p>
        
        <!-- Offline Status Badge -->
        <div class="offline-status-badge <?php echo $internet_online ? 'online' : 'offline'; ?>" id="offline-status-badge">
            <span class="status-dot <?php echo $internet_online ? 'online' : 'offline'; ?>"></span>
            <span><?php echo $internet_online ? 'Online' : 'Offline'; ?></span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!$sync_enabled): ?>
    <div class="alert alert-warning">
        <i class="fa fa-exclamation-triangle"></i>
        <div>
            <strong>Sync Disabled</strong><br>
            Automatic synchronization is currently disabled. Enable it in settings to resume sync operations.
        </div>
    </div>
    <?php endif; ?>

    <?php if ($pending_total > 100): ?>
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <div>
            <strong>High Pending Count</strong><br>
            You have <?php echo $pending_total; ?> pending records. Consider running a manual sync.
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Cards -->
    <div class="status-cards">
        <!-- Internet Connection -->
        <div class="status-card <?php echo $internet_online ? 'online' : 'offline'; ?>" id="connection-status-card">
            <div class="status-card-icon">
                <i class="fa fa-<?php echo $internet_online ? 'signal' : 'unlink'; ?>" id="connection-icon"></i>
            </div>
            <div class="status-card-label">Internet Connection</div>
            <div class="status-card-value">
                <span class="connection-indicator <?php echo $internet_online ? 'online' : 'offline'; ?>" id="connection-indicator">
                    <span class="connection-dot <?php echo $internet_online ? 'online' : 'offline'; ?>" id="connection-dot"></span>
                    <span id="connection-text"><?php echo $internet_online ? 'Online' : 'Offline'; ?></span>
                </span>
            </div>
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
                <?php echo $last_sync != 'Never' ? date('M d, Y H:i', strtotime($last_sync)) : 'Never'; ?>
            </div>
        </div>

        <!-- Sync Status -->
        <div class="status-card <?php echo $last_sync_status == 'success' ? 'online' : 'offline'; ?>">
            <div class="status-card-icon">
                <i class="fa fa-<?php echo $last_sync_status == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            </div>
            <div class="status-card-label">Last Sync Status</div>
            <div class="status-card-value" style="font-size: 18px; text-transform: capitalize;" id="last-sync-status">
                <?php echo str_replace('_', ' ', $last_sync_status); ?>
            </div>
        </div>
    </div>

    <!-- Sync Actions -->
    <div class="sync-actions">
        <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #1f2937;">
            <i class="fa fa-cog"></i> Sync Actions
        </h3>
        <button id="manual-sync-btn" class="sync-btn sync-btn-primary" onclick="triggerManualSync()">
            <i class="fa fa-sync-alt"></i>
            <span>Run Manual Sync</span>
        </button>
        <span id="sync-message" style="margin-left: 15px; font-size: 14px; color: #6b7280;"></span>
    </div>

    <!-- Pending Tables -->
    <?php if (!empty($pending_by_table)): ?>
    <div class="pending-tables">
        <h3><i class="fa fa-table"></i> Pending Records by Table</h3>
        <div id="pending-tables-list">
            <?php foreach ($pending_by_table as $table => $count): ?>
            <div class="pending-table-item">
                <span class="pending-table-name"><?php echo ucwords(str_replace('_', ' ', $table)); ?></span>
                <span class="pending-table-count"><?php echo number_format($count); ?> pending</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sync Log -->
    <div class="sync-log">
        <h3><i class="fa fa-list"></i> Recent Sync Activity</h3>
        <div id="sync-log-content">
            <div class="sync-log-empty">
                <i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>No recent sync activity</p>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/backend/js/sync_dashboard.js?v=<?php echo time(); ?>"></script>
