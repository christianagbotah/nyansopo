<?php
/**
 * Sync Audit Log Detail View
 * 
 * Displays detailed information about a specific audit log entry with
 * before/after comparison and revert capability.
 * 
 * @package    School Manager
 * @subpackage Views
 * @category   Sync
 */
?>

<style>
.audit-detail-container { padding: 20px; max-width: 1400px; margin: 0 auto; }

/* Header */
.audit-detail-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
.audit-detail-header h1 { margin: 0 0 10px 0; font-size: 28px; color: white; }
.audit-detail-header p { margin: 0; opacity: 0.9; }
.back-link { display: inline-block; margin-bottom: 15px; color: white; text-decoration: none; opacity: 0.9; }
.back-link:hover { opacity: 1; }

/* Info Cards */
.info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
.info-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.info-card-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
.info-card-value { font-size: 18px; font-weight: 600; color: #1f2937; }

/* Comparison Section */
.comparison-section { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.comparison-section h3 { margin: 0 0 20px 0; font-size: 18px; color: #1f2937; }
.comparison-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.comparison-column { border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; }
.comparison-column h4 { margin: 0 0 15px 0; font-size: 16px; color: #374151; display: flex; align-items: center; gap: 8px; }
.comparison-column h4 i { font-size: 14px; }
.comparison-field { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f3f4f6; }
.comparison-field:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.field-label { font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 5px; }
.field-value { font-size: 14px; color: #1f2937; padding: 8px; background: #f9fafb; border-radius: 4px; word-break: break-word; }
.field-value.changed { background: #fef3c7; border-left: 3px solid #f59e0b; }
.field-value.null { color: #9ca3af; font-style: italic; }

/* Actions */
.action-section { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.action-section h3 { margin: 0 0 20px 0; font-size: 18px; color: #1f2937; }
.action-buttons { display: flex; gap: 15px; flex-wrap: wrap; }
.btn-action { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.btn-revert { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
.btn-revert:hover { background: #fde68a; }
.btn-revert:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-back { background: #f3f4f6; color: #374151; }
.btn-back:hover { background: #e5e7eb; }

/* Related Logs */
.related-logs-section { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
.related-logs-section h3 { margin: 0 0 20px 0; font-size: 18px; color: #1f2937; }
.related-log-item { padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
.related-log-item:hover { background: #f9fafb; }
.related-log-info { flex: 1; }
.related-log-operation { font-weight: 600; color: #1f2937; margin-bottom: 5px; }
.related-log-time { font-size: 12px; color: #6b7280; }
.related-log-action { }

/* Badges */
.operation-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.operation-badge.insert { background: #d1fae5; color: #065f46; }
.operation-badge.update { background: #dbeafe; color: #1e40af; }
.operation-badge.delete { background: #fee2e2; color: #991b1b; }
.operation-badge.conflict { background: #fef3c7; color: #92400e; }
.operation-badge.revert { background: #e9d5ff; color: #6b21a8; }

.status-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.status-badge.success { background: #d1fae5; color: #065f46; }
.status-badge.failed { background: #fee2e2; color: #991b1b; }
.status-badge.conflict { background: #fef3c7; color: #92400e; }

/* Alert */
.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
.alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
.alert-info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
.alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
.alert i { font-size: 20px; }

/* Modal */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
.modal-content { background: white; margin: 10% auto; padding: 30px; border-radius: 12px; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
.modal-header { margin-bottom: 20px; }
.modal-header h3 { margin: 0; font-size: 20px; color: #1f2937; }
.modal-body { margin-bottom: 20px; color: #374151; line-height: 1.6; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; }
</style>

<div class="audit-detail-container">
    <!-- Header -->
    <div class="audit-detail-header">
        <a href="<?php echo site_url('sync_audit'); ?>" class="back-link">
            <i class="fa fa-arrow-left"></i> Back to Audit Log
        </a>
        <h1><i class="fa fa-file-alt"></i> Audit Log Detail #<?php echo $log->id; ?></h1>
        <p>Detailed information about this sync operation</p>
    </div>

    <!-- Info Cards -->
    <div class="info-cards">
        <div class="info-card">
            <div class="info-card-label">Table</div>
            <div class="info-card-value"><?php echo ucwords(str_replace('_', ' ', $log->table_name)); ?></div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Record ID</div>
            <div class="info-card-value"><?php echo $log->record_id; ?></div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Operation</div>
            <div class="info-card-value">
                <span class="operation-badge <?php echo strtolower($log->operation); ?>">
                    <?php echo $log->operation; ?>
                </span>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Status</div>
            <div class="info-card-value">
                <span class="status-badge <?php echo $log->status; ?>">
                    <?php echo ucfirst($log->status); ?>
                </span>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Synced At</div>
            <div class="info-card-value"><?php echo date('M d, Y H:i:s', strtotime($log->synced_at)); ?></div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Duration</div>
            <div class="info-card-value"><?php echo $log->duration_ms; ?> ms</div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Source Location</div>
            <div class="info-card-value">
                <?php echo $source_location ? htmlspecialchars($source_location->location_name) : $log->source_device_id; ?>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Target Location</div>
            <div class="info-card-value">
                <?php echo $target_location ? htmlspecialchars($target_location->location_name) : ($log->target_device_id ?: 'N/A'); ?>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Synced By</div>
            <div class="info-card-value">
                <?php echo $synced_by_user ? htmlspecialchars($synced_by_user->name) : 'System'; ?>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-label">Direction</div>
            <div class="info-card-value">
                <i class="fa fa-arrow-<?php echo $log->sync_direction === 'push' ? 'up' : 'down'; ?>"></i>
                <?php echo ucfirst($log->sync_direction); ?>
            </div>
        </div>
    </div>

    <?php if ($log->error_message): ?>
    <div class="alert alert-warning">
        <i class="fa fa-exclamation-triangle"></i>
        <div>
            <strong>Error:</strong> <?php echo htmlspecialchars($log->error_message); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Comparison Section -->
    <?php if (!empty($diff)): ?>
    <div class="comparison-section">
        <h3><i class="fa fa-exchange-alt"></i> Before & After Comparison</h3>
        <div class="comparison-grid">
            <!-- Before (Old Value) -->
            <div class="comparison-column">
                <h4>
                    <i class="fa fa-history"></i> Before
                </h4>
                <?php foreach ($diff as $field => $values): ?>
                <div class="comparison-field">
                    <div class="field-label"><?php echo ucwords(str_replace('_', ' ', $field)); ?></div>
                    <div class="field-value <?php echo $values['is_different'] ? 'changed' : ''; ?> <?php echo is_null($values['old']) ? 'null' : ''; ?>">
                        <?php echo is_null($values['old']) ? 'NULL' : htmlspecialchars($values['old']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- After (New Value) -->
            <div class="comparison-column">
                <h4>
                    <i class="fa fa-check"></i> After
                </h4>
                <?php foreach ($diff as $field => $values): ?>
                <div class="comparison-field">
                    <div class="field-label"><?php echo ucwords(str_replace('_', ' ', $field)); ?></div>
                    <div class="field-value <?php echo $values['is_different'] ? 'changed' : ''; ?> <?php echo is_null($values['new']) ? 'null' : ''; ?>">
                        <?php echo is_null($values['new']) ? 'NULL' : htmlspecialchars($values['new']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="action-section">
        <h3><i class="fa fa-cog"></i> Actions</h3>
        
        <?php if ($can_revert): ?>
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            <div>
                This operation can be reverted. Reverting will restore the record to its previous state and mark it as PENDING for sync propagation.
            </div>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <?php if ($can_revert): ?>
            <button class="btn-action btn-revert" onclick="showRevertModal()">
                <i class="fa fa-undo"></i> Revert This Change
            </button>
            <?php else: ?>
            <button class="btn-action btn-revert" disabled>
                <i class="fa fa-undo"></i> Revert Not Available
            </button>
            <?php endif; ?>
            
            <a href="<?php echo site_url('sync_audit'); ?>" class="btn-action btn-back">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Related Logs -->
    <?php if (!empty($related_logs) && count($related_logs) > 1): ?>
    <div class="related-logs-section">
        <h3><i class="fa fa-history"></i> Related Audit Entries (History for Record #<?php echo $log->record_id; ?>)</h3>
        <?php foreach ($related_logs as $related): ?>
        <?php if ($related['id'] != $log->id): ?>
        <div class="related-log-item">
            <div class="related-log-info">
                <div class="related-log-operation">
                    <span class="operation-badge <?php echo strtolower($related['operation']); ?>">
                        <?php echo $related['operation']; ?>
                    </span>
                    <span class="status-badge <?php echo $related['status']; ?>">
                        <?php echo ucfirst($related['status']); ?>
                    </span>
                </div>
                <div class="related-log-time">
                    <?php echo date('M d, Y H:i:s', strtotime($related['synced_at'])); ?>
                </div>
            </div>
            <div class="related-log-action">
                <a href="<?php echo site_url('sync_audit/view/' . $related['id']); ?>" class="btn-action btn-back" style="padding: 8px 16px; font-size: 12px;">
                    <i class="fa fa-eye"></i> View
                </a>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Revert Confirmation Modal -->
<div id="revert-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa fa-exclamation-triangle"></i> Confirm Revert</h3>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to revert this change?</p>
            <p><strong>This will:</strong></p>
            <ul>
                <li>Restore the record to its previous state</li>
                <li>Mark the record as PENDING for sync</li>
                <li>Create a new audit log entry documenting the revert</li>
                <li>Propagate the revert to other locations during next sync</li>
            </ul>
            <p style="color: #dc2626; font-weight: 600;">This action cannot be undone automatically.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-action btn-back" onclick="closeRevertModal()">
                <i class="fa fa-times"></i> Cancel
            </button>
            <button class="btn-action btn-revert" onclick="confirmRevert()">
                <i class="fa fa-undo"></i> Yes, Revert
            </button>
        </div>
    </div>
</div>

<script>
function showRevertModal() {
    document.getElementById('revert-modal').style.display = 'block';
}

function closeRevertModal() {
    document.getElementById('revert-modal').style.display = 'none';
}

function confirmRevert() {
    // Show loading
    var btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Reverting...';
    
    // Send revert request
    fetch('<?php echo site_url('sync_audit/revert/' . $log->id); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'confirm=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Revert successful! The record has been restored to its previous state.');
            window.location.reload();
        } else {
            alert('Revert failed: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-undo"></i> Yes, Revert';
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-undo"></i> Yes, Revert';
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('revert-modal');
    if (event.target == modal) {
        closeRevertModal();
    }
}
</script>
