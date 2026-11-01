<?php
/**
 * Sync Conflicts Resolution View
 * 
 * Modern interface for resolving sync conflicts between locations with:
 * - Expandable conflict cards with side-by-side comparison
 * - Manual merge capabilities
 * - Bulk resolution controls
 * - Conflict summary cards
 * 
 * Requirements: 8.1, 8.9, 6.1
 * Task 5.1: Conflict resolution layout structure
 */

// Get conflict data from controller
$conflicts = $conflicts ?? [];
$conflict_summary = $conflict_summary ?? [];
$total_conflicts = $total_conflicts ?? 0;
$pending_conflicts = $pending_conflicts ?? 0;
$resolved_today = $resolved_today ?? 0;
?>

<!-- Load Design System CSS -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-design-system.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-conflicts-ui.css?v=<?php echo time(); ?>">

<div class="sync-conflicts-container">
    <!-- Page Header with Gradient -->
    <div class="sync-conflicts-header">
        <div class="sync-conflicts-header-content">
            <h1>
                <i class="fa fa-exclamation-triangle"></i>
                Sync Conflict Resolution
            </h1>
            <p>Review and resolve data conflicts between locations</p>
        </div>
        
        <!-- Header Actions -->
        <div class="sync-conflicts-header-actions">
            <button class="sync-btn sync-btn-secondary" id="refresh-conflicts-btn">
                <i class="fa fa-sync-alt"></i> Refresh
            </button>
            <a href="<?php echo site_url('admin/sync_dashboard'); ?>" class="sync-btn sync-btn-ghost">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Conflict Summary Cards -->
    <div class="sync-conflicts-summary">
        <div class="sync-summary-card sync-card-error">
            <div class="sync-summary-icon">
                <i class="fa fa-exclamation-circle"></i>
            </div>
            <div class="sync-summary-content">
                <div class="sync-summary-label">Total Conflicts</div>
                <div class="sync-summary-value" id="total-conflicts-count"><?php echo number_format($total_conflicts); ?></div>
            </div>
        </div>
        
        <div class="sync-summary-card sync-card-warning">
            <div class="sync-summary-icon">
                <i class="fa fa-clock"></i>
            </div>
            <div class="sync-summary-content">
                <div class="sync-summary-label">Pending Resolution</div>
                <div class="sync-summary-value" id="pending-conflicts-count"><?php echo number_format($pending_conflicts); ?></div>
            </div>
        </div>
        
        <div class="sync-summary-card sync-card-success">
            <div class="sync-summary-icon">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="sync-summary-content">
                <div class="sync-summary-label">Resolved Today</div>
                <div class="sync-summary-value" id="resolved-today-count"><?php echo number_format($resolved_today); ?></div>
            </div>
        </div>
        
        <div class="sync-summary-card sync-card-info">
            <div class="sync-summary-icon">
                <i class="fa fa-table"></i>
            </div>
            <div class="sync-summary-content">
                <div class="sync-summary-label">Affected Tables</div>
                <div class="sync-summary-value"><?php echo count($conflict_summary); ?></div>
            </div>
        </div>
    </div>

    <!-- Filter Controls and Bulk Actions -->
    <div class="sync-conflicts-controls">
        <div class="sync-conflicts-filters">
            <div class="sync-filter-group">
                <label for="filter-table">Filter by Table:</label>
                <select id="filter-table" class="sync-filter-select">
                    <option value="">All Tables</option>
                    <?php foreach ($conflict_summary as $table => $count): ?>
                        <option value="<?php echo htmlspecialchars($table); ?>">
                            <?php echo htmlspecialchars($table); ?> (<?php echo $count; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="sync-filter-group">
                <label for="filter-status">Filter by Status:</label>
                <select id="filter-status" class="sync-filter-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="resolved">Resolved</option>
                    <option value="ignored">Ignored</option>
                </select>
            </div>
            
            <div class="sync-filter-group">
                <input type="text" id="search-conflicts" class="sync-search-input" placeholder="Search conflicts...">
            </div>
        </div>
        
        <!-- Bulk Action Section -->
        <div class="sync-bulk-actions" id="bulk-actions-container" style="display: none;">
            <div class="sync-bulk-info">
                <span id="selected-count">0</span> conflicts selected
            </div>
            <div class="sync-bulk-buttons">
                <button class="sync-btn sync-btn-sm sync-btn-secondary" id="select-all-btn">
                    <i class="fa fa-check-square"></i> Select All
                </button>
                <button class="sync-btn sync-btn-sm sync-btn-secondary" id="deselect-all-btn">
                    <i class="fa fa-square"></i> Deselect All
                </button>
                <div class="sync-bulk-resolve-dropdown">
                    <button class="sync-btn sync-btn-sm sync-btn-primary" id="resolve-selected-btn">
                        <i class="fa fa-check"></i> Resolve Selected
                        <i class="fa fa-caret-down"></i>
                    </button>
                    <div class="sync-bulk-resolve-menu" id="bulk-resolve-menu">
                        <button class="sync-bulk-resolve-option" data-strategy="keep-local">
                            <i class="fa fa-laptop"></i> Keep Local Version
                        </button>
                        <button class="sync-bulk-resolve-option" data-strategy="keep-remote">
                            <i class="fa fa-cloud"></i> Keep Remote Version
                        </button>
                        <button class="sync-bulk-resolve-option" data-strategy="ignore">
                            <i class="fa fa-eye-slash"></i> Ignore Conflicts
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflict List Container -->
    <div class="sync-conflicts-list" id="conflicts-list">
        <?php if (empty($conflicts)): ?>
            <!-- Empty State -->
            <div class="sync-empty-state">
                <div class="sync-empty-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h3>No Conflicts Found</h3>
                <p>All sync operations are running smoothly. There are no conflicts to resolve at this time.</p>
                <a href="<?php echo site_url('admin/sync_dashboard'); ?>" class="sync-btn sync-btn-primary">
                    <i class="fa fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php else: ?>
            <!-- Conflict Cards will be rendered here -->
            <?php foreach ($conflicts as $conflict): ?>
                <?php 
                $conflict_id = $conflict['id'];
                $table_name = $conflict['table_name'];
                $record_id = $conflict['record_id'];
                $conflict_type = $conflict['conflict_type'] ?? 'data_mismatch';
                $created_at = $conflict['created_at'];
                $status = $conflict['status'] ?? 'pending';
                $local_data = json_decode($conflict['local_data'], true);
                $remote_data = json_decode($conflict['remote_data'], true);
                $source_location = $conflict['source_location_name'] ?? 'Unknown';
                
                // Count differences
                $diff_count = 0;
                if (is_array($local_data) && is_array($remote_data)) {
                    foreach ($local_data as $key => $value) {
                        if (!isset($remote_data[$key]) || $remote_data[$key] != $value) {
                            $diff_count++;
                        }
                    }
                }
                ?>
                
                <!-- Task 5.2: Conflict Card Component -->
                <div class="sync-conflict-card" data-conflict-id="<?php echo $conflict_id; ?>" data-table="<?php echo htmlspecialchars($table_name); ?>" data-status="<?php echo $status; ?>">
                    <!-- Card Header -->
                    <div class="sync-conflict-card-header">
                        <div class="sync-conflict-checkbox">
                            <input type="checkbox" class="conflict-checkbox" value="<?php echo $conflict_id; ?>">
                        </div>
                        
                        <div class="sync-conflict-info">
                            <div class="sync-conflict-title">
                                <i class="fa fa-table"></i>
                                <strong><?php echo htmlspecialchars($table_name); ?></strong>
                                <span class="sync-conflict-separator">•</span>
                                <span class="sync-conflict-record-id">Record #<?php echo $record_id; ?></span>
                            </div>
                            <div class="sync-conflict-meta">
                                <span class="sync-conflict-meta-item">
                                    <i class="fa fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($source_location); ?>
                                </span>
                                <span class="sync-conflict-meta-item">
                                    <i class="fa fa-clock"></i>
                                    <?php echo date('M d, Y H:i', strtotime($created_at)); ?>
                                </span>
                                <span class="sync-conflict-meta-item">
                                    <i class="fa fa-exchange-alt"></i>
                                    <?php echo $diff_count; ?> field<?php echo $diff_count != 1 ? 's' : ''; ?> differ
                                </span>
                            </div>
                        </div>
                        
                        <div class="sync-conflict-status">
                            <span class="sync-badge sync-badge-<?php echo $status == 'pending' ? 'warning' : ($status == 'resolved' ? 'success' : 'gray'); ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                        
                        <div class="sync-conflict-expand">
                            <button class="sync-expand-btn" data-conflict-id="<?php echo $conflict_id; ?>">
                                <i class="fa fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Card Body (Expandable) - Task 5.3: Side-by-side comparison view -->
                    <div class="sync-conflict-card-body" id="conflict-body-<?php echo $conflict_id; ?>" style="display: none;">
                        <!-- Side-by-side comparison with metadata - Requirements 8.3, 8.4, 8.5 -->
                        <div class="sync-conflict-comparison">
                            <!-- Local Version Column -->
                            <div class="sync-comparison-column sync-comparison-local">
                                <div class="sync-comparison-header">
                                    <i class="fa fa-laptop"></i>
                                    <strong>Local Version</strong>
                                    <span class="sync-comparison-label">Current Data</span>
                                </div>
                                
                                <!-- Metadata Section - Requirement 8.5 -->
                                <div class="sync-comparison-metadata">
                                    <div class="sync-metadata-item">
                                        <i class="fa fa-database"></i>
                                        <span class="sync-metadata-label">Source:</span>
                                        <span class="sync-metadata-value">Local Database</span>
                                    </div>
                                    <div class="sync-metadata-item">
                                        <i class="fa fa-clock"></i>
                                        <span class="sync-metadata-label">Last Modified:</span>
                                        <span class="sync-metadata-value">
                                            <?php 
                                            $local_timestamp = isset($local_data['updated_at']) ? $local_data['updated_at'] : 
                                                              (isset($local_data['created_at']) ? $local_data['created_at'] : 'Unknown');
                                            echo htmlspecialchars($local_timestamp);
                                            ?>
                                        </span>
                                    </div>
                                    <?php if (isset($local_data['modified_by']) || isset($local_data['created_by'])): ?>
                                    <div class="sync-metadata-item">
                                        <i class="fa fa-user"></i>
                                        <span class="sync-metadata-label">Modified By:</span>
                                        <span class="sync-metadata-value">
                                            <?php 
                                            $local_user = isset($local_data['modified_by']) ? $local_data['modified_by'] : 
                                                         (isset($local_data['created_by']) ? $local_data['created_by'] : 'System');
                                            echo htmlspecialchars($local_user);
                                            ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Data Fields with Syntax Highlighting -->
                                <div class="sync-comparison-data" id="local-data-<?php echo $conflict_id; ?>">
                                    <?php if (is_array($local_data)): ?>
                                        <?php foreach ($local_data as $key => $value): ?>
                                            <?php 
                                            $has_diff = !isset($remote_data[$key]) || $remote_data[$key] != $value;
                                            $is_json = is_array($value) || (is_string($value) && @json_decode($value) !== null);
                                            ?>
                                            <div class="sync-data-field <?php echo $has_diff ? 'sync-data-diff' : ''; ?>">
                                                <div class="sync-data-key"><?php echo htmlspecialchars($key); ?></div>
                                                <div class="sync-data-value <?php echo $is_json ? 'sync-json-value' : ''; ?>" data-json="<?php echo $is_json ? 'true' : 'false'; ?>">
                                                    <?php 
                                                    if (is_array($value)) {
                                                        echo '<pre class="sync-json-pre">' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
                                                    } else {
                                                        echo htmlspecialchars($value);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="sync-data-empty">No data available</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Divider -->
                            <div class="sync-comparison-divider">
                                <i class="fa fa-arrows-alt-h"></i>
                            </div>
                            
                            <!-- Remote Version Column -->
                            <div class="sync-comparison-column sync-comparison-remote">
                                <div class="sync-comparison-header">
                                    <i class="fa fa-cloud"></i>
                                    <strong>Remote Version</strong>
                                    <span class="sync-comparison-label">From <?php echo htmlspecialchars($source_location); ?></span>
                                </div>
                                
                                <!-- Metadata Section - Requirement 8.5 -->
                                <div class="sync-comparison-metadata">
                                    <div class="sync-metadata-item">
                                        <i class="fa fa-map-marker-alt"></i>
                                        <span class="sync-metadata-label">Source:</span>
                                        <span class="sync-metadata-value"><?php echo htmlspecialchars($source_location); ?></span>
                                    </div>
                                    <div class="sync-metadata-item">
                                        <i class="fa fa-clock"></i>
                                        <span class="sync-metadata-label">Last Modified:</span>
                                        <span class="sync-metadata-value">
                                            <?php 
                                            $remote_timestamp = isset($remote_data['updated_at']) ? $remote_data['updated_at'] : 
                                                               (isset($remote_data['created_at']) ? $remote_data['created_at'] : 'Unknown');
                                            echo htmlspecialchars($remote_timestamp);
                                            ?>
                                        </span>
                                    </div>
                                    <?php if (isset($remote_data['modified_by']) || isset($remote_data['created_by'])): ?>
                                    <div class="sync-metadata-item">
                                        <i class="fa fa-user"></i>
                                        <span class="sync-metadata-label">Modified By:</span>
                                        <span class="sync-metadata-value">
                                            <?php 
                                            $remote_user = isset($remote_data['modified_by']) ? $remote_data['modified_by'] : 
                                                          (isset($remote_data['created_by']) ? $remote_data['created_by'] : 'System');
                                            echo htmlspecialchars($remote_user);
                                            ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Data Fields with Syntax Highlighting -->
                                <div class="sync-comparison-data" id="remote-data-<?php echo $conflict_id; ?>">
                                    <?php if (is_array($remote_data)): ?>
                                        <?php foreach ($remote_data as $key => $value): ?>
                                            <?php 
                                            $has_diff = !isset($local_data[$key]) || $local_data[$key] != $value;
                                            $is_json = is_array($value) || (is_string($value) && @json_decode($value) !== null);
                                            ?>
                                            <div class="sync-data-field <?php echo $has_diff ? 'sync-data-diff' : ''; ?>">
                                                <div class="sync-data-key"><?php echo htmlspecialchars($key); ?></div>
                                                <div class="sync-data-value <?php echo $is_json ? 'sync-json-value' : ''; ?>" data-json="<?php echo $is_json ? 'true' : 'false'; ?>">
                                                    <?php 
                                                    if (is_array($value)) {
                                                        echo '<pre class="sync-json-pre">' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
                                                    } else {
                                                        echo htmlspecialchars($value);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="sync-data-empty">No data available</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="sync-conflict-actions">
                            <button class="sync-btn sync-btn-success" onclick="resolveConflict(<?php echo $conflict_id; ?>, 'keep-local')">
                                <i class="fa fa-laptop"></i> Keep Local
                            </button>
                            <button class="sync-btn sync-btn-info" onclick="resolveConflict(<?php echo $conflict_id; ?>, 'keep-remote')">
                                <i class="fa fa-cloud"></i> Keep Remote
                            </button>
                            <button class="sync-btn sync-btn-primary" onclick="openMergeModal(<?php echo $conflict_id; ?>)">
                                <i class="fa fa-code-branch"></i> Merge Manually
                            </button>
                            <button class="sync-btn sync-btn-secondary" onclick="resolveConflict(<?php echo $conflict_id; ?>, 'ignore')">
                                <i class="fa fa-eye-slash"></i> Ignore
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_conflicts > 25): ?>
    <div class="sync-pagination">
        <div class="sync-pagination-info">
            Showing <span id="showing-from">1</span> to <span id="showing-to">25</span> of <span id="total-count"><?php echo $total_conflicts; ?></span> conflicts
        </div>
        <div class="sync-pagination-controls">
            <button class="sync-btn sync-btn-sm sync-btn-secondary" id="prev-page-btn" disabled>
                <i class="fa fa-chevron-left"></i> Previous
            </button>
            <span class="sync-pagination-pages" id="pagination-pages">
                <!-- Page numbers will be generated by JavaScript -->
            </span>
            <button class="sync-btn sync-btn-sm sync-btn-secondary" id="next-page-btn">
                Next <i class="fa fa-chevron-right"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Load Conflict Resolution JavaScript -->
<script src="<?php echo base_url(); ?>assets/backend/js/sync_conflicts_ui.js?v=<?php echo time(); ?>"></script>
