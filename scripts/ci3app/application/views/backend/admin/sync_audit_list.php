<?php
/**
 * Sync Audit Log List View
 * 
 * Displays audit log entries with filtering options for compliance and tracking.
 * 
 * @package    School Manager
 * @subpackage Views
 * @category   Sync
 */
?>

<style>
.audit-container { padding: 20px; }
.audit-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
.audit-header h1 { margin: 0 0 10px 0; font-size: 28px; color: white; }
.audit-header p { margin: 0; opacity: 0.9; }

/* Filters */
.audit-filters { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.audit-filters h3 { margin: 0 0 20px 0; font-size: 18px; color: #1f2937; }
.filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
.filter-group { display: flex; flex-direction: column; }
.filter-group label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.filter-group select, .filter-group input { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; height: 38px; }
.filter-group .select2-container .select2-selection--single { height: 38px; border: 1px solid #d1d5db; border-radius: 6px; }
.filter-group .select2-container .select2-selection--single .select2-selection__rendered { line-height: 36px; padding-left: 12px; font-size: 14px; }
.filter-group .select2-container .select2-selection--single .select2-selection__arrow { height: 36px; }
.filter-actions { display: flex; gap: 10px; margin-top: 15px; }
.btn-filter { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-filter-primary { background: #3b82f6; color: white; }
.btn-filter-primary:hover { background: #2563eb; }
.btn-filter-secondary { background: #f3f4f6; color: #374151; }
.btn-filter-secondary:hover { background: #e5e7eb; }
.btn-export { background: #10b981; color: white; }
.btn-export:hover { background: #059669; }

/* Stats Cards */
.audit-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-left: 4px solid; }
.stat-card.success { border-left-color: #10b981; }
.stat-card.failed { border-left-color: #ef4444; }
.stat-card.conflict { border-left-color: #f59e0b; }
.stat-card.total { border-left-color: #3b82f6; }
.stat-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
.stat-value { font-size: 28px; font-weight: 700; color: #1f2937; }

/* Audit Table */
.audit-table-container { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.audit-table { width: 100%; border-collapse: collapse; }
.audit-table thead { background: #f9fafb; }
.audit-table th { padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; }
.audit-table td { padding: 12px; border-bottom: 1px solid #f3f4f6; font-size: 14px; color: #1f2937; }
.audit-table tbody tr:hover { background: #f9fafb; }

/* Operation Badges */
.operation-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.operation-badge.insert { background: #d1fae5; color: #065f46; }
.operation-badge.update { background: #dbeafe; color: #1e40af; }
.operation-badge.delete { background: #fee2e2; color: #991b1b; }
.operation-badge.conflict { background: #fef3c7; color: #92400e; }
.operation-badge.revert { background: #e9d5ff; color: #6b21a8; }

/* Status Badges */
.status-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.status-badge.success { background: #d1fae5; color: #065f46; }
.status-badge.failed { background: #fee2e2; color: #991b1b; }
.status-badge.conflict { background: #fef3c7; color: #92400e; }

/* Direction Badges */
.direction-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.direction-badge.push { background: #dbeafe; color: #1e40af; }
.direction-badge.pull { background: #e9d5ff; color: #6b21a8; }

/* Pagination */
.pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; }
.pagination a, .pagination span { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; }
.pagination a:hover { background: #f3f4f6; }
.pagination .active { background: #3b82f6; color: white; border-color: #3b82f6; }

/* Action Buttons */
.btn-view { padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-view:hover { background: #2563eb; }
</style>

<div class="audit-container">
    <!-- Header -->
    <div class="audit-header">
        <h1><i class="fa fa-history"></i> Sync Audit Log</h1>
        <p>Complete audit trail of all sync operations across locations</p>
    </div>

    <!-- Stats Cards -->
    <div class="audit-stats">
        <div class="stat-card total">
            <div class="stat-label">Total Entries</div>
            <div class="stat-value"><?php echo number_format($total_count); ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Successful</div>
            <div class="stat-value" id="success-count">-</div>
        </div>
        <div class="stat-card failed">
            <div class="stat-label">Failed</div>
            <div class="stat-value" id="failed-count">-</div>
        </div>
        <div class="stat-card conflict">
            <div class="stat-label">Conflicts</div>
            <div class="stat-value" id="conflict-count">-</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="audit-filters">
        <h3><i class="fa fa-filter"></i> Filters</h3>
        <form method="get" action="<?php echo site_url('sync_audit'); ?>" id="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Table</label>
                    <select name="table" id="filter-table">
                        <option value="">All Tables</option>
                        <?php foreach ($tables as $table): ?>
                        <option value="<?php echo $table; ?>" <?php echo (isset($filters['table_name']) && $filters['table_name'] === $table) ? 'selected' : ''; ?>>
                            <?php echo ucwords(str_replace('_', ' ', $table)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Operation</label>
                    <select name="operation" id="filter-operation">
                        <option value="">All Operations</option>
                        <?php foreach ($operations as $op): ?>
                        <option value="<?php echo $op; ?>" <?php echo (isset($filters['operation']) && $filters['operation'] === $op) ? 'selected' : ''; ?>>
                            <?php echo $op; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" id="filter-status">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo (isset($filters['status']) && $filters['status'] === $status) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Direction</label>
                    <select name="sync_direction" id="filter-direction">
                        <option value="">All Directions</option>
                        <?php foreach ($directions as $dir): ?>
                        <option value="<?php echo $dir; ?>" <?php echo (isset($filters['sync_direction']) && $filters['sync_direction'] === $dir) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($dir); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label>Location</label>
                    <select name="device_id" id="filter-location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo $loc['device_id']; ?>" <?php echo (isset($filters['device_id']) && $filters['device_id'] === $loc['device_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['location_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" id="filter-date-from" value="<?php echo $filters['date_from'] ?? ''; ?>">
                </div>
                
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" id="filter-date-to" value="<?php echo $filters['date_to'] ?? ''; ?>">
                </div>
                
                <div class="filter-group">
                    <label>Record ID</label>
                    <input type="number" name="record_id" id="filter-record-id" placeholder="Record ID" value="<?php echo $filters['record_id'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn-filter btn-filter-primary">
                    <i class="fa fa-search"></i> Apply Filters
                </button>
                <a href="<?php echo site_url('sync_audit'); ?>" class="btn-filter btn-filter-secondary">
                    <i class="fa fa-times"></i> Clear Filters
                </a>
                <button type="button" class="btn-filter btn-export" onclick="exportLogs('csv')">
                    <i class="fa fa-download"></i> Export CSV
                </button>
                <button type="button" class="btn-filter btn-export" onclick="exportLogs('json')">
                    <i class="fa fa-download"></i> Export JSON
                </button>
            </div>
        </form>
    </div>

    <!-- Audit Table -->
    <div class="audit-table-container">
        <table class="audit-table" id="audit-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Operation</th>
                    <th>Direction</th>
                    <th>Source Location</th>
                    <th>Status</th>
                    <th>Synced At</th>
                    <th>Duration</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px; color: #9ca3af;">
                        <i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                        No audit log entries found
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $log['id']; ?></td>
                    <td><?php echo ucwords(str_replace('_', ' ', $log['table_name'])); ?></td>
                    <td><?php echo $log['record_id']; ?></td>
                    <td>
                        <span class="operation-badge <?php echo strtolower($log['operation']); ?>">
                            <?php echo $log['operation']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="direction-badge <?php echo $log['sync_direction']; ?>">
                            <i class="fa fa-arrow-<?php echo $log['sync_direction'] === 'push' ? 'up' : 'down'; ?>"></i>
                            <?php echo ucfirst($log['sync_direction']); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $source_loc = null;
                        foreach ($locations as $loc) {
                            if ($loc['device_id'] === $log['source_device_id']) {
                                $source_loc = $loc;
                                break;
                            }
                        }
                        echo $source_loc ? htmlspecialchars($source_loc['location_name']) : $log['source_device_id'];
                        ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $log['status']; ?>">
                            <?php echo ucfirst($log['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y H:i:s', strtotime($log['synced_at'])); ?></td>
                    <td><?php echo $log['duration_ms']; ?> ms</td>
                    <td>
                        <a href="<?php echo site_url('sync_audit/view/' . $log['id']); ?>" class="btn-view">
                            <i class="fa fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
            <a href="<?php echo site_url('sync_audit?' . http_build_query(array_merge($filters, ['page' => $current_page - 1]))); ?>">
                <i class="fa fa-chevron-left"></i> Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
            <?php if ($i === $current_page): ?>
            <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="<?php echo site_url('sync_audit?' . http_build_query(array_merge($filters, ['page' => $i]))); ?>">
                <?php echo $i; ?>
            </a>
            <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo site_url('sync_audit?' . http_build_query(array_merge($filters, ['page' => $current_page + 1]))); ?>">
                Next <i class="fa fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize Select2 for table filter
$(document).ready(function() {
    $('#filter-table').select2({
        placeholder: 'All Tables',
        allowClear: true,
        width: '100%'
    });
});

// Load stats on page load
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
});

function loadStats() {
    var dateFrom = document.getElementById('filter-date-from').value || '';
    var dateTo = document.getElementById('filter-date-to').value || '';
    
    fetch('<?php echo site_url('sync_audit/stats'); ?>?date_from=' + dateFrom + '&date_to=' + dateTo)
        .then(response => response.json())
        .then(data => {
            // Update stat cards
            var successCount = 0, failedCount = 0, conflictCount = 0;
            
            data.status_counts.forEach(function(item) {
                if (item.status === 'success') successCount = item.count;
                if (item.status === 'failed') failedCount = item.count;
                if (item.status === 'conflict') conflictCount = item.count;
            });
            
            document.getElementById('success-count').textContent = successCount.toLocaleString();
            document.getElementById('failed-count').textContent = failedCount.toLocaleString();
            document.getElementById('conflict-count').textContent = conflictCount.toLocaleString();
        })
        .catch(error => {
            console.error('Error loading stats:', error);
        });
}

function exportLogs(format) {
    var form = document.getElementById('filter-form');
    var params = new URLSearchParams(new FormData(form));
    params.append('format', format);
    
    window.location.href = '<?php echo site_url('sync_audit/export'); ?>?' + params.toString();
}
</script>
