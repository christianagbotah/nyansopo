<?php
/**
 * Sync Audit Trail View
 * 
 * Modern timeline interface for viewing sync operation history with:
 * - Timeline layout with date grouping
 * - Expandable audit entries with before/after comparison
 * - Filter controls (date range, table, location, operation type)
 * - Pagination controls
 * - Revert functionality for UPDATE and DELETE operations
 * 
 * Requirements: 9.1, 9.6, 9.8, 6.1
 * Task 7.1: Audit trail layout structure
 */

// Get audit data from controller
$audit_entries = $audit_entries ?? [];
$total_entries = $total_entries ?? 0;
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 50;
$total_pages = ceil($total_entries / $per_page);

// Get filter data
$tables = $tables ?? [];
$locations = $locations ?? [];
$date_from = $date_from ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $date_to ?? date('Y-m-d');
?>

<!-- Load Design System CSS -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-design-system.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/sync-audit-trail-ui.css?v=<?php echo time(); ?>">

<div class="sync-audit-container">
    <!-- Page Header with Gradient -->
    <div class="sync-audit-header">
        <div class="sync-audit-header-content">
            <h1>
                <i class="fa fa-history"></i>
                Sync Audit Trail
            </h1>
            <p>View historical sync operations and track changes across locations</p>
        </div>
        
        <!-- Header Actions -->
        <div class="sync-audit-header-actions">
            <button class="sync-btn sync-btn-secondary" id="refresh-audit-btn">
                <i class="fa fa-sync-alt"></i> Refresh
            </button>
            <button class="sync-btn sync-btn-secondary" id="export-audit-btn">
                <i class="fa fa-download"></i> Export CSV
            </button>
            <a href="<?php echo site_url('admin/sync_dashboard'); ?>" class="sync-btn sync-btn-ghost">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Controls Section -->
    <div class="sync-audit-filters">
        <div class="sync-filter-row">
            <!-- Date Range Filter -->
            <div class="sync-filter-group">
                <label for="filter-date-from">Date From:</label>
                <input type="date" id="filter-date-from" class="sync-filter-input" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="sync-filter-group">
                <label for="filter-date-to">Date To:</label>
                <input type="date" id="filter-date-to" class="sync-filter-input" value="<?php echo $date_to; ?>">
            </div>
            
            <!-- Table Filter -->
            <div class="sync-filter-group">
                <label for="filter-table">Table:</label>
                <select id="filter-table" class="sync-filter-select">
                    <option value="">All Tables</option>
                    <?php foreach ($tables as $table): ?>
                        <option value="<?php echo htmlspecialchars($table); ?>">
                            <?php echo htmlspecialchars($table); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Location Filter -->
            <div class="sync-filter-group">
                <label for="filter-location">Location:</label>
                <select id="filter-location" class="sync-filter-select">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>">
                            <?php echo htmlspecialchars($location['location_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Operation Type Filter -->
            <div class="sync-filter-group">
                <label for="filter-operation">Operation:</label>
                <select id="filter-operation" class="sync-filter-select">
                    <option value="">All Operations</option>
                    <option value="INSERT">INSERT</option>
                    <option value="UPDATE">UPDATE</option>
                    <option value="DELETE">DELETE</option>
                </select>
            </div>
            
            <!-- Search Input -->
            <div class="sync-filter-group sync-filter-search">
                <label for="search-audit">Search:</label>
                <input type="text" id="search-audit" class="sync-search-input" placeholder="Search by record ID or user...">
            </div>
            
            <!-- Apply Filters Button -->
            <div class="sync-filter-group">
                <button class="sync-btn sync-btn-primary" id="apply-filters-btn">
                    <i class="fa fa-filter"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Audit Summary Info -->
    <div class="sync-audit-summary">
        <div class="sync-audit-summary-item">
            <i class="fa fa-list"></i>
            <span class="sync-audit-summary-label">Total Entries:</span>
            <span class="sync-audit-summary-value" id="total-entries-count"><?php echo number_format($total_entries); ?></span>
        </div>
        <div class="sync-audit-summary-item">
            <i class="fa fa-calendar"></i>
            <span class="sync-audit-summary-label">Date Range:</span>
            <span class="sync-audit-summary-value"><?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?></span>
        </div>
        <div class="sync-audit-summary-item">
            <i class="fa fa-file-alt"></i>
            <span class="sync-audit-summary-label">Page:</span>
            <span class="sync-audit-summary-value"><?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
        </div>
    </div>

    <!-- Timeline Container -->
    <div class="sync-audit-timeline" id="audit-timeline">
        <?php if (empty($audit_entries)): ?>
            <!-- Empty State -->
            <div class="sync-empty-state">
                <div class="sync-empty-icon">
                    <i class="fa fa-history"></i>
                </div>
                <h3>No Audit Entries Found</h3>
                <p>No sync operations match your current filters. Try adjusting the date range or filters.</p>
            </div>
        <?php else: ?>
            <?php
            // Group entries by date
            $grouped_entries = [];
            foreach ($audit_entries as $entry) {
                $date = date('Y-m-d', strtotime($entry['created_at']));
                if (!isset($grouped_entries[$date])) {
                    $grouped_entries[$date] = [];
                }
                $grouped_entries[$date][] = $entry;
            }
            
            // Render timeline with date grouping
            foreach ($grouped_entries as $date => $entries):
                $formatted_date = date('l, F j, Y', strtotime($date));
            ?>
                <!-- Date Group Header -->
                <div class="sync-timeline-date-group">
                    <div class="sync-timeline-date-header">
                        <i class="fa fa-calendar-day"></i>
                        <span><?php echo $formatted_date; ?></span>
                    </div>
                    
                    <!-- Audit Entries for this Date -->
                    <div class="sync-timeline-entries">
                        <?php foreach ($entries as $entry): 
                            $entry_id = $entry['id'];
                            $table_name = $entry['table_name'];
                            $record_id = $entry['record_id'];
                            $operation = $entry['operation'];
                            $created_at = $entry['created_at'];
                            $user_name = $entry['user_name'] ?? 'System';
                            $location_name = $entry['location_name'] ?? 'Unknown';
                            $status = $entry['status'] ?? 'success';
                            $sync_duration = $entry['sync_duration'] ?? 0;
                            $before_data = json_decode($entry['before_data'] ?? '{}', true);
                            $after_data = json_decode($entry['after_data'] ?? '{}', true);
                            
                            // Determine operation badge class
                            $operation_class = '';
                            $operation_icon = '';
                            switch ($operation) {
                                case 'INSERT':
                                    $operation_class = 'sync-badge-info';
                                    $operation_icon = 'fa-plus-circle';
                                    break;
                                case 'UPDATE':
                                    $operation_class = 'sync-badge-warning';
                                    $operation_icon = 'fa-edit';
                                    break;
                                case 'DELETE':
                                    $operation_class = 'sync-badge-error';
                                    $operation_icon = 'fa-trash-alt';
                                    break;
                            }
                            
                            // Determine status badge
                            $status_class = $status === 'success' ? 'sync-badge-success' : 'sync-badge-error';
                            $status_icon = $status === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                        ?>
                            <!-- Task 7.2: Audit Entry Component -->
                            <div class="sync-timeline-entry" data-entry-id="<?php echo $entry_id; ?>" data-operation="<?php echo $operation; ?>">
                                <!-- Timeline Dot -->
                                <div class="sync-timeline-dot <?php echo $operation_class; ?>">
                                    <i class="fa <?php echo $operation_icon; ?>"></i>
                                </div>
                                
                                <!-- Entry Card -->
                                <div class="sync-timeline-card">
                                    <!-- Card Header -->
                                    <div class="sync-timeline-card-header">
                                        <div class="sync-timeline-card-info">
                                            <div class="sync-timeline-card-title">
                                                <span class="sync-badge <?php echo $operation_class; ?>">
                                                    <i class="fa <?php echo $operation_icon; ?>"></i>
                                                    <?php echo $operation; ?>
                                                </span>
                                                <span class="sync-timeline-table-name"><?php echo htmlspecialchars($table_name); ?></span>
                                                <span class="sync-timeline-record-id">#<?php echo $record_id; ?></span>
                                            </div>
                                            <div class="sync-timeline-card-meta">
                                                <span class="sync-timeline-meta-item">
                                                    <i class="fa fa-clock"></i>
                                                    <?php echo date('g:i A', strtotime($created_at)); ?>
                                                </span>
                                                <span class="sync-timeline-meta-item">
                                                    <i class="fa fa-user"></i>
                                                    <?php echo htmlspecialchars($user_name); ?>
                                                </span>
                                                <span class="sync-timeline-meta-item">
                                                    <i class="fa fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($location_name); ?>
                                                </span>
                                                <span class="sync-badge <?php echo $status_class; ?> sync-badge-sm">
                                                    <i class="fa <?php echo $status_icon; ?>"></i>
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                                <?php if ($sync_duration > 0): ?>
                                                    <span class="sync-timeline-meta-item">
                                                        <i class="fa fa-stopwatch"></i>
                                                        <?php echo number_format($sync_duration, 2); ?>s
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="sync-timeline-card-actions">
                                            <button class="sync-btn sync-btn-sm sync-btn-ghost expand-entry-btn" data-entry-id="<?php echo $entry_id; ?>">
                                                <i class="fa fa-chevron-down"></i>
                                                <span>Details</span>
                                            </button>
                                            <?php if (in_array($operation, ['UPDATE', 'DELETE']) && $status === 'success'): ?>
                                                <button class="sync-btn sync-btn-sm sync-btn-danger revert-entry-btn" data-entry-id="<?php echo $entry_id; ?>" data-table="<?php echo htmlspecialchars($table_name); ?>" data-record-id="<?php echo $record_id; ?>">
                                                    <i class="fa fa-undo"></i>
                                                    <span>Revert</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Expandable Details Section (Task 7.3: Before/After Comparison) -->
                                    <div class="sync-timeline-card-details" id="entry-details-<?php echo $entry_id; ?>" style="display: none;">
                                        <div class="sync-timeline-details-content">
                                            <?php if ($operation === 'INSERT'): ?>
                                                <!-- INSERT: Show only after data -->
                                                <div class="sync-data-comparison">
                                                    <div class="sync-data-section sync-data-full">
                                                        <h4 class="sync-data-section-title">
                                                            <i class="fa fa-plus-circle"></i>
                                                            Inserted Data
                                                        </h4>
                                                        <div class="sync-data-content">
                                                            <?php if (!empty($after_data)): ?>
                                                                <table class="sync-data-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Field</th>
                                                                            <th>Value</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($after_data as $field => $value): ?>
                                                                            <tr>
                                                                                <td class="sync-data-field"><?php echo htmlspecialchars($field); ?></td>
                                                                                <td class="sync-data-value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php else: ?>
                                                                <p class="sync-data-empty">No data available</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php elseif ($operation === 'DELETE'): ?>
                                                <!-- DELETE: Show only before data -->
                                                <div class="sync-data-comparison">
                                                    <div class="sync-data-section sync-data-full">
                                                        <h4 class="sync-data-section-title">
                                                            <i class="fa fa-trash-alt"></i>
                                                            Deleted Data
                                                        </h4>
                                                        <div class="sync-data-content">
                                                            <?php if (!empty($before_data)): ?>
                                                                <table class="sync-data-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Field</th>
                                                                            <th>Value</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($before_data as $field => $value): ?>
                                                                            <tr>
                                                                                <td class="sync-data-field"><?php echo htmlspecialchars($field); ?></td>
                                                                                <td class="sync-data-value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php else: ?>
                                                                <p class="sync-data-empty">No data available</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- UPDATE: Show side-by-side comparison -->
                                                <div class="sync-data-comparison sync-data-side-by-side">
                                                    <!-- Before Data -->
                                                    <div class="sync-data-section">
                                                        <h4 class="sync-data-section-title">
                                                            <i class="fa fa-history"></i>
                                                            Before
                                                        </h4>
                                                        <div class="sync-data-content">
                                                            <?php if (!empty($before_data)): ?>
                                                                <table class="sync-data-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Field</th>
                                                                            <th>Value</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($before_data as $field => $value): 
                                                                            $is_changed = isset($after_data[$field]) && $after_data[$field] != $value;
                                                                            $row_class = $is_changed ? 'sync-data-changed' : '';
                                                                        ?>
                                                                            <tr class="<?php echo $row_class; ?>">
                                                                                <td class="sync-data-field"><?php echo htmlspecialchars($field); ?></td>
                                                                                <td class="sync-data-value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php else: ?>
                                                                <p class="sync-data-empty">No data available</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- After Data -->
                                                    <div class="sync-data-section">
                                                        <h4 class="sync-data-section-title">
                                                            <i class="fa fa-check-circle"></i>
                                                            After
                                                        </h4>
                                                        <div class="sync-data-content">
                                                            <?php if (!empty($after_data)): ?>
                                                                <table class="sync-data-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Field</th>
                                                                            <th>Value</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($after_data as $field => $value): 
                                                                            $is_changed = isset($before_data[$field]) && $before_data[$field] != $value;
                                                                            $row_class = $is_changed ? 'sync-data-changed' : '';
                                                                        ?>
                                                                            <tr class="<?php echo $row_class; ?>">
                                                                                <td class="sync-data-field"><?php echo htmlspecialchars($field); ?></td>
                                                                                <td class="sync-data-value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php else: ?>
                                                                <p class="sync-data-empty">No data available</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
        <div class="sync-pagination">
            <div class="sync-pagination-info">
                Showing <?php echo (($current_page - 1) * $per_page) + 1; ?> to <?php echo min($current_page * $per_page, $total_entries); ?> of <?php echo number_format($total_entries); ?> entries
            </div>
            
            <div class="sync-pagination-controls">
                <!-- Previous Button -->
                <button class="sync-pagination-btn <?php echo $current_page <= 1 ? 'disabled' : ''; ?>" 
                        id="prev-page-btn" 
                        data-page="<?php echo $current_page - 1; ?>"
                        <?php echo $current_page <= 1 ? 'disabled' : ''; ?>>
                    <i class="fa fa-chevron-left"></i>
                    Previous
                </button>
                
                <!-- Page Numbers -->
                <div class="sync-pagination-pages">
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <button class="sync-pagination-page" data-page="1">1</button>
                        <?php if ($start_page > 2): ?>
                            <span class="sync-pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <button class="sync-pagination-page <?php echo $i === $current_page ? 'active' : ''; ?>" 
                                data-page="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="sync-pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <button class="sync-pagination-page" data-page="<?php echo $total_pages; ?>"><?php echo $total_pages; ?></button>
                    <?php endif; ?>
                </div>
                
                <!-- Next Button -->
                <button class="sync-pagination-btn <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>" 
                        id="next-page-btn" 
                        data-page="<?php echo $current_page + 1; ?>"
                        <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>>
                    Next
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
            
            <!-- Page Size Selector -->
            <div class="sync-pagination-size">
                <label for="page-size-select">Per page:</label>
                <select id="page-size-select" class="sync-filter-select">
                    <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Load JavaScript -->
<script src="<?php echo base_url(); ?>assets/backend/js/sync_audit_trail_ui.js?v=<?php echo time(); ?>"></script>
