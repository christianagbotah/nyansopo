<?php
/**
 * Add Sync Location Modal
 * 
 * Modal form for adding a new sync location
 */
?>

<style>
.sync-form-group {
    margin-bottom: 20px;
}

.sync-form-label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: #374151;
    margin-bottom: 8px;
}

.sync-form-label.required::after {
    content: '*';
    color: #ef4444;
    margin-left: 4px;
}

.sync-form-input,
.sync-form-textarea,
.sync-form-select {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    color: #1f2937;
    transition: all 0.2s;
    font-family: inherit;
}

.sync-form-input:focus,
.sync-form-textarea:focus,
.sync-form-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.sync-form-textarea {
    resize: vertical;
    min-height: 80px;
}

.sync-form-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 6px;
}

.sync-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.sync-form-row .sync-form-group {
    margin-bottom: 0;
}

.sync-modal-footer-buttons {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.sync-modal-footer-buttons .btn {
    margin: 0 !important;
}

@media (max-width: 768px) {
    .sync-form-row {
        grid-template-columns: 1fr;
    }
    
    .sync-modal-footer-buttons {
        flex-direction: column;
    }
    
    .sync-modal-footer-buttons .btn {
        width: 100%;
    }
}
</style>

<div class="row">
    <div class="col-md-12">
        <?php echo form_open(site_url('admin/sync_locations/create'), array('class' => 'form-horizontal', 'id' => 'addLocationForm')); ?>
        
        <div class="form-group sync-form-group">
            <label class="sync-form-label required">Location Name</label>
            <input type="text" name="location_name" class="sync-form-input" placeholder="e.g., Main Campus, Branch Office" required>
            <div class="sync-form-help">A descriptive name for this sync location</div>
        </div>
        
        <div class="form-group sync-form-group">
            <label class="sync-form-label">Description</label>
            <textarea name="description" class="sync-form-textarea" placeholder="Optional description of this location"></textarea>
        </div>
        
        <div class="form-group sync-form-group">
            <label class="sync-form-label required">Device ID</label>
            <input type="text" name="device_id" class="sync-form-input" placeholder="e.g., DEVICE-001" required>
            <div class="sync-form-help">Unique identifier for this device/location</div>
        </div>
        
        <div class="sync-form-row">
            <div class="form-group sync-form-group">
                <label class="sync-form-label required">Status</label>
                <select name="status" class="sync-form-select" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            
            <div class="form-group sync-form-group">
                <label class="sync-form-label required">Priority</label>
                <input type="number" name="priority" class="sync-form-input" value="1" min="1" max="100" required>
                <div class="sync-form-help">1-100 (higher = more priority)</div>
            </div>
        </div>
        
        <div class="sync-form-row">
            <div class="form-group sync-form-group">
                <label class="sync-form-label required">Sync Enabled</label>
                <select name="sync_enabled" class="sync-form-select" required>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            
            <div class="form-group sync-form-group">
                <label class="sync-form-label">Sync Interval (minutes)</label>
                <input type="number" name="sync_interval" class="sync-form-input" value="15" min="1" max="1440">
            </div>
        </div>
        
        <div class="form-group sync-form-group">
            <label class="sync-form-label">API Endpoint</label>
            <input type="url" name="api_endpoint" class="sync-form-input" placeholder="https://example.com/api/sync">
            <div class="sync-form-help">Optional custom API endpoint for this location</div>
        </div>
        
        <div class="form-group" style="margin-top: 30px; margin-bottom: 0;">
            <div class="sync-modal-footer-buttons">
                <button type="button" class="btn btn-default btn-lg" data-dismiss="modal" style="padding: 12px 32px; font-weight: 600;">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary btn-lg" style="padding: 12px 32px; font-weight: 600;">
                    <i class="fa fa-check"></i> Add Location
                </button>
            </div>
        </div>
        
        <?php echo form_close(); ?>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Form submission handler
    $('#addLocationForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var actionUrl = $(this).attr('action');
        
        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
        
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Close modal
                    $('#modal_ajax').modal('hide');
                    
                    // Show success message
                    showAjaxModal_alert(response.message || 'Location added successfully', 'Success');
                    
                    // Reload page after 2 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    showAjaxModal_alert(response.message || 'Failed to add location', 'Error');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                var errorMsg = 'An error occurred while adding the location';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showAjaxModal_alert(errorMsg, 'Error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
