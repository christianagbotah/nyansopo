<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-building"></i> <?php echo get_phrase('locations'); ?>
                    <a href="<?php echo site_url('locations/add'); ?>" class="btn btn-success btn-sm pull-right">
                        <i class="fa fa-plus"></i> <?php echo get_phrase('add_location'); ?>
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <!-- Statistics Cards -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo get_phrase('active'); ?></span>
                                <span class="info-box-number"><?php echo $stats['active']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-gray">
                            <span class="info-box-icon"><i class="fa fa-pause-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo get_phrase('inactive'); ?></span>
                                <span class="info-box-number"><?php echo $stats['inactive']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-red">
                            <span class="info-box-icon"><i class="fa fa-ban"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo get_phrase('suspended'); ?></span>
                                <span class="info-box-number"><?php echo $stats['suspended']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-orange">
                            <span class="info-box-icon"><i class="fa fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo get_phrase('stale_24h'); ?></span>
                                <span class="info-box-number"><?php echo $stats['stale']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Locations Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('location_name'); ?></th>
                                <th><?php echo get_phrase('device_id'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('last_sync'); ?></th>
                                <th><?php echo get_phrase('sync_status'); ?></th>
                                <th><?php echo get_phrase('priority'); ?></th>
                                <th><?php echo get_phrase('contact'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($locations)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fa fa-building" style="font-size: 24px;"></i>
                                        <p><?php echo get_phrase('no_locations_registered'); ?></p>
                                        <a href="<?php echo site_url('locations/add'); ?>" class="btn btn-primary">
                                            <i class="fa fa-plus"></i> <?php echo get_phrase('add_first_location'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($locations as $location): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $location['location_name']; ?></strong>
                                            <?php if ($location['description']): ?>
                                                <br><small class="text-muted"><?php echo substr($location['description'], 0, 50); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code><?php echo $location['device_id']; ?></code>
                                        </td>
                                        <td>
                                            <?php if ($location['status'] === 'active'): ?>
                                                <span class="label label-success">
                                                    <i class="fa fa-check"></i> <?php echo get_phrase('active'); ?>
                                                </span>
                                            <?php elseif ($location['status'] === 'inactive'): ?>
                                                <span class="label label-default">
                                                    <i class="fa fa-pause"></i> <?php echo get_phrase('inactive'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="label label-danger">
                                                    <i class="fa fa-ban"></i> <?php echo get_phrase('suspended'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($location['last_sync_at']): ?>
                                                <?php 
                                                $last_sync = strtotime($location['last_sync_at']);
                                                $now = time();
                                                $diff = $now - $last_sync;
                                                $is_stale = $diff > 86400; // 24 hours
                                                ?>
                                                <span class="<?php echo $is_stale ? 'text-warning' : 'text-success'; ?>">
                                                    <?php echo date('M d, H:i', $last_sync); ?>
                                                    <?php if ($is_stale): ?>
                                                        <i class="fa fa-exclamation-triangle" title="Stale"></i>
                                                    <?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted"><?php echo get_phrase('never'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_colors = [
                                                'success' => 'success',
                                                'failed' => 'danger',
                                                'partial' => 'warning',
                                                'pending' => 'default'
                                            ];
                                            $color = $status_colors[$location['last_sync_status']] ?? 'default';
                                            ?>
                                            <span class="label label-<?php echo $color; ?>">
                                                <?php echo $location['last_sync_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge"><?php echo $location['priority']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($location['contact_email']): ?>
                                                <i class="fa fa-envelope"></i> <?php echo $location['contact_email']; ?>
                                            <?php endif; ?>
                                            <?php if ($location['contact_phone']): ?>
                                                <br><i class="fa fa-phone"></i> <?php echo $location['contact_phone']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo site_url('locations/edit/' . $location['id']); ?>" 
                                                   class="btn btn-default btn-sm" title="<?php echo get_phrase('edit'); ?>">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <?php if ($location['status'] === 'active'): ?>
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="deactivateLocation(<?php echo $location['id']; ?>)" 
                                                            title="<?php echo get_phrase('deactivate'); ?>">
                                                        <i class="fa fa-pause"></i>
                                                    </button>
                                                <?php elseif ($location['status'] === 'inactive'): ?>
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="activateLocation(<?php echo $location['id']; ?>)" 
                                                            title="<?php echo get_phrase('activate'); ?>">
                                                        <i class="fa fa-play"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($location['api_endpoint']): ?>
                                                    <button type="button" class="btn btn-info btn-sm" 
                                                            onclick="testConnection(<?php echo $location['id']; ?>)" 
                                                            title="<?php echo get_phrase('test_connection'); ?>">
                                                        <i class="fa fa-plug"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Connection Test Modal -->
<div class="modal fade" id="connectionTestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-plug"></i> <?php echo get_phrase('connection_test'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div id="connectionTestResult">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p><?php echo get_phrase('testing_connection'); ?>...</p>
                    </div>
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

<script>
function activateLocation(id) {
    if (!confirm('Are you sure you want to activate this location?')) {
        return;
    }
    window.location.href = '<?php echo site_url("locations/activate/"); ?>' + id;
}

function deactivateLocation(id) {
    if (!confirm('Are you sure you want to deactivate this location? It will stop syncing.')) {
        return;
    }
    window.location.href = '<?php echo site_url("locations/deactivate/"); ?>' + id;
}

function testConnection(id) {
    $('#connectionTestModal').modal('show');
    $('#connectionTestResult').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p><?php echo get_phrase("testing_connection"); ?>...</p></div>');
    
    $.ajax({
        url: '<?php echo site_url("locations/test_connection/"); ?>' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var html = '';
            if (response.success) {
                html = '<div class="alert alert-success">';
                html += '<i class="fa fa-check-circle fa-2x"></i>';
                html += '<h4><?php echo get_phrase("connection_successful"); ?></h4>';
                html += '<p><?php echo get_phrase("response_time"); ?>: ' + response.duration_ms + 'ms</p>';
                html += '</div>';
            } else {
                html = '<div class="alert alert-danger">';
                html += '<i class="fa fa-times-circle fa-2x"></i>';
                html += '<h4><?php echo get_phrase("connection_failed"); ?></h4>';
                html += '<p>' + response.message + '</p>';
                html += '</div>';
            }
            $('#connectionTestResult').html(html);
        },
        error: function() {
            $('#connectionTestResult').html('<div class="alert alert-danger"><i class="fa fa-times-circle fa-2x"></i><h4><?php echo get_phrase("connection_failed"); ?></h4></div>');
        }
    });
}
</script>
