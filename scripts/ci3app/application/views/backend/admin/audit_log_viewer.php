<!-- Audit Log Viewer (Task 13.1) -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-list-add"></i>
                    <?php echo get_phrase('audit_logs'); ?>
                </div>
                <div class="panel-options">
                    <a href="#" class="btn btn-sm btn-success" id="exportCsvBtn">
                        <i class="entypo-download"></i> <?php echo get_phrase('export_to_csv'); ?>
                    </a>
                </div>
            </div>
            <div class="panel-body">
                
                <!-- Filters (Task 13.3) -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo get_phrase('module'); ?>:</label>
                            <select name="filter_module" id="filter_module" class="form-control">
                                <option value=""><?php echo get_phrase('all_modules'); ?></option>
                                <?php foreach($modules as $module): ?>
                                    <option value="<?php echo $module; ?>"><?php echo ucwords($module); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?php echo get_phrase('user'); ?>:</label>
                            <select name="filter_user" id="filter_user" class="form-control">
                                <option value=""><?php echo get_phrase('all_users'); ?></option>
                                <?php foreach($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo $user['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?php echo get_phrase('date_from'); ?>:</label>
                            <input type="date" name="filter_date_from" id="filter_date_from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?php echo get_phrase('date_to'); ?>:</label>
                            <input type="date" name="filter_date_to" id="filter_date_to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="applyFiltersBtn">
                                <i class="entypo-search"></i> <?php echo get_phrase('apply_filters'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <table class="table table-bordered datatable" id="audit_logs_table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('log_id'); ?></th>
                            <th><?php echo get_phrase('module'); ?></th>
                            <th><?php echo get_phrase('action'); ?></th>
                            <th><?php echo get_phrase('user'); ?></th>
                            <th><?php echo get_phrase('record_id'); ?></th>
                            <th><?php echo get_phrase('ip_address'); ?></th>
                            <th><?php echo get_phrase('date_time'); ?></th>
                            <th><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via AJAX -->
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<!-- View Details Modal (Task 13.4) -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="entypo-info-circled"></i> <?php echo get_phrase('audit_log_details'); ?>
                </h4>
            </div>
            <div class="modal-body" id="modalDetailsContent">
                <div class="text-center">
                    <i class="entypo-spin entypo-cycle"></i> <?php echo get_phrase('loading'); ?>...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo get_phrase('close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Audit Log Viewer -->
<script type="text/javascript">
$(document).ready(function() {
    // Initialize DataTable
    var auditTable = $('#audit_logs_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo base_url(); ?>index.php?admin/get_audit_logs_ajax',
            type: 'POST',
            data: function(d) {
                d.module = $('#filter_module').val();
                d.user_id = $('#filter_user').val();
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
                d.<?php echo $this->security->get_csrf_token_name(); ?> = '<?php echo $this->security->get_csrf_hash(); ?>';
            }
        },
        columns: [
            { data: 'log_id' },
            { data: 'module' },
            { data: 'action' },
            { data: 'user_name' },
            { data: 'record_id' },
            { data: 'ip_address' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[6, 'desc']], // Order by date descending
        pageLength: 50
    });
    
    // Apply filters button
    $('#applyFiltersBtn').on('click', function() {
        auditTable.ajax.reload();
    });
    
    // Export to CSV button (Task 13.5)
    $('#exportCsvBtn').on('click', function() {
        var module = $('#filter_module').val();
        var user_id = $('#filter_user').val();
        var date_from = $('#filter_date_from').val();
        var date_to = $('#filter_date_to').val();
        
        var params = [];
        if (module) params.push('module=' + module);
        if (user_id) params.push('user_id=' + user_id);
        if (date_from) params.push('date_from=' + date_from);
        if (date_to) params.push('date_to=' + date_to);
        
        var url = '<?php echo base_url(); ?>index.php?admin/export_audit_logs_csv';
        if (params.length > 0) {
            url += '&' + params.join('&');
        }
        
        window.location.href = url;
    });
    
    // View details button click (Task 13.4)
    $(document).on('click', '.view-details-btn', function() {
        var logId = $(this).data('log-id');
        
        // Show modal
        $('#viewDetailsModal').modal('show');
        
        // Show loading
        $('#modalDetailsContent').html('<div class="text-center"><i class="entypo-spin entypo-cycle"></i> Loading...</div>');
        
        // Fetch details
        $.ajax({
            url: '<?php echo base_url(); ?>index.php?admin/get_audit_log_details',
            type: 'POST',
            data: {
                log_id: logId,
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var log = response.log;
                    
                    // Build details HTML
                    var html = '<div class="row">';
                    html += '<div class="col-md-6"><strong>Log ID:</strong> ' + log.log_id + '</div>';
                    html += '<div class="col-md-6"><strong>Module:</strong> ' + log.module + '</div>';
                    html += '</div><hr>';
                    html += '<div class="row">';
                    html += '<div class="col-md-6"><strong>Action:</strong> ' + log.action + '</div>';
                    html += '<div class="col-md-6"><strong>User:</strong> ' + log.user_name + '</div>';
                    html += '</div><hr>';
                    html += '<div class="row">';
                    html += '<div class="col-md-6"><strong>Record ID:</strong> ' + log.record_id + '</div>';
                    html += '<div class="col-md-6"><strong>Date/Time:</strong> ' + log.created_at + '</div>';
                    html += '</div><hr>';
                    html += '<div class="row">';
                    html += '<div class="col-md-6"><strong>IP Address:</strong> ' + log.ip_address + '</div>';
                    html += '<div class="col-md-6"><strong>User Agent:</strong> ' + (log.user_agent || 'N/A') + '</div>';
                    html += '</div><hr>';
                    
                    // Before data
                    html += '<div class="row">';
                    html += '<div class="col-md-12">';
                    html += '<h4>Before Data:</h4>';
                    if (log.before_data && Object.keys(log.before_data).length > 0) {
                        html += '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;">';
                        html += JSON.stringify(log.before_data, null, 2);
                        html += '</pre>';
                    } else {
                        html += '<p class="text-muted">No data (likely a creation action)</p>';
                    }
                    html += '</div>';
                    html += '</div><hr>';
                    
                    // After data
                    html += '<div class="row">';
                    html += '<div class="col-md-12">';
                    html += '<h4>After Data:</h4>';
                    if (log.after_data && Object.keys(log.after_data).length > 0) {
                        html += '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;">';
                        html += JSON.stringify(log.after_data, null, 2);
                        html += '</pre>';
                    } else {
                        html += '<p class="text-muted">No data (likely a deletion action)</p>';
                    }
                    html += '</div>';
                    html += '</div>';
                    
                    $('#modalDetailsContent').html(html);
                } else {
                    $('#modalDetailsContent').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#modalDetailsContent').html('<div class="alert alert-danger">Failed to load log details. Please try again.</div>');
            }
        });
    });
});
</script>

<!-- Custom CSS -->
<style>
    .tile-stats {
        padding: 20px;
        border-radius: 4px;
        color: white;
        margin-bottom: 20px;
    }
    .tile-green { background: #27ae60; }
    .tile-red { background: #e74c3c; }
    .tile-primary { background: #3498db; }
    .tile-aqua { background: #1abc9c; }
    .tile-stats .icon { font-size: 48px; opacity: 0.5; }
    .tile-stats .num { font-size: 32px; font-weight: bold; margin: 10px 0; }
    .tile-stats h3 { margin: 0; font-size: 14px; text-transform: uppercase; }
</style>
