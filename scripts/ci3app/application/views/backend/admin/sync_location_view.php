<?php
/**
 * View Sync Location Details
 */
?>

<div class="row">
    <div class="col-md-12">
        
        <!-- Location Details -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="pull-right">
                    <a href="<?php echo site_url('admin/sync_locations/edit/' . $location->id); ?>" class="btn btn-warning btn-sm">
                        <i class="fa fa-edit"></i> <?php echo get_phrase('edit'); ?>
                    </a>
                    <a href="<?php echo site_url('admin/sync_locations'); ?>" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> <?php echo get_phrase('back'); ?>
                    </a>
                </div>
                <h3 class="panel-title">
                    <i class="fa fa-building"></i> <?php echo htmlspecialchars($location->location_name); ?>
                </h3>
            </div>
            
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Device ID</th>
                                <td><code><?php echo htmlspecialchars($location->device_id); ?></code></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <?php if ($location->status == 'active'): ?>
                                        <span class="label label-success">Active</span>
                                    <?php elseif ($location->status == 'inactive'): ?>
                                        <span class="label label-default">Inactive</span>
                                    <?php else: ?>
                                        <span class="label label-warning">Suspended</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Sync Enabled</th>
                                <td>
                                    <?php if ($location->sync_enabled): ?>
                                        <span class="label label-success">Yes</span>
                                    <?php else: ?>
                                        <span class="label label-danger">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Priority</th>
                                <td><span class="badge"><?php echo $location->priority; ?></span></td>
                            </tr>
                            <tr>
                                <th>API Endpoint</th>
                                <td>
                                    <?php if ($location->api_endpoint): ?>
                                        <a href="<?php echo htmlspecialchars($location->api_endpoint); ?>" target="_blank">
                                            <?php echo htmlspecialchars($location->api_endpoint); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not configured</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Last Sync</th>
                                <td>
                                    <?php if ($location->last_sync_at): ?>
                                        <?php echo date('M d, Y H:i:s', strtotime($location->last_sync_at)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Last Sync Status</th>
                                <td>
                                    <?php if ($location->last_sync_status == 'success'): ?>
                                        <span class="label label-success">Success</span>
                                    <?php elseif ($location->last_sync_status == 'failed'): ?>
                                        <span class="label label-danger">Failed</span>
                                    <?php elseif ($location->last_sync_status == 'partial'): ?>
                                        <span class="label label-warning">Partial</span>
                                    <?php else: ?>
                                        <span class="label label-default">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Timezone</th>
                                <td><?php echo htmlspecialchars($location->timezone); ?></td>
                            </tr>
                            <tr>
                                <th>Contact Email</th>
                                <td>
                                    <?php if ($location->contact_email): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($location->contact_email); ?>">
                                            <?php echo htmlspecialchars($location->contact_email); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Contact Phone</th>
                                <td>
                                    <?php if ($location->contact_phone): ?>
                                        <?php echo htmlspecialchars($location->contact_phone); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if ($location->description): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Description</h4>
                            <p><?php echo nl2br(htmlspecialchars($location->description)); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sync History -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-history"></i> Recent Sync History (Last 50)
                </h4>
            </div>
            <div class="panel-body">
                <?php if (empty($sync_history)): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> No sync history available for this location.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Operation</th>
                                    <th>Table</th>
                                    <th>Status</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sync_history as $log): ?>
                                    <tr>
                                        <td><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td><span class="label label-info"><?php echo htmlspecialchars($log['operation_type']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($log['table_name']); ?></code></td>
                                        <td>
                                            <?php if ($log['status'] == 'success'): ?>
                                                <span class="label label-success">Success</span>
                                            <?php elseif ($log['status'] == 'failed'): ?>
                                                <span class="label label-danger">Failed</span>
                                            <?php else: ?>
                                                <span class="label label-warning"><?php echo htmlspecialchars($log['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $log['duration_ms']; ?>ms</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Conflicts -->
        <?php if (!empty($conflicts)): ?>
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <i class="fa fa-exclamation-triangle"></i> Pending Conflicts (<?php echo count($conflicts); ?>)
                    </h4>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Detected</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($conflicts as $conflict): ?>
                                    <tr>
                                        <td><?php echo date('M d, H:i', strtotime($conflict['detected_at'])); ?></td>
                                        <td><code><?php echo htmlspecialchars($conflict['table_name']); ?></code></td>
                                        <td><?php echo htmlspecialchars($conflict['record_id']); ?></td>
                                        <td><span class="label label-warning"><?php echo htmlspecialchars($conflict['conflict_type']); ?></span></td>
                                        <td><span class="label label-default"><?php echo htmlspecialchars($conflict['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>
