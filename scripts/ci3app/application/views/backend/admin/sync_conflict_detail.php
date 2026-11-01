<div class="row conflict-detail-page">
    <div class="col-lg-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-exclamation-triangle"></i> <?php echo get_phrase('conflict_detail'); ?>
                    <a href="<?php echo site_url('sync_conflicts'); ?>" class="btn btn-default btn-sm pull-right">
                        <i class="fa fa-arrow-left"></i> <?php echo get_phrase('back_to_list'); ?>
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <!-- Conflict Info -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="150"><?php echo get_phrase('table'); ?></th>
                                <td><span class="label label-primary"><?php echo $conflict->table_name; ?></span></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('record_id'); ?></th>
                                <td><?php echo $conflict->record_id; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('conflict_strategy'); ?></th>
                                <td><span class="label label-default"><?php echo $conflict->conflict_strategy; ?></span></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('status'); ?></th>
                                <td>
                                    <?php if ($conflict->status === 'pending'): ?>
                                        <span class="label label-warning"><?php echo get_phrase('pending'); ?></span>
                                    <?php elseif ($conflict->status === 'resolved'): ?>
                                        <span class="label label-success"><?php echo get_phrase('resolved'); ?></span>
                                    <?php else: ?>
                                        <span class="label label-default"><?php echo get_phrase('ignored'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="150"><?php echo get_phrase('created'); ?></th>
                                <td><?php echo date('M d, Y H:i:s', strtotime($conflict->created_at)); ?></td>
                            </tr>
                            <?php if ($conflict->resolved_at): ?>
                            <tr>
                                <th><?php echo get_phrase('resolved'); ?></th>
                                <td><?php echo date('M d, Y H:i:s', strtotime($conflict->resolved_at)); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('resolution'); ?></th>
                                <td><span class="label label-info"><?php echo $conflict->resolution; ?></span></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <?php if ($conflict->status === 'pending'): ?>
                <!-- Resolution Buttons -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 
                            <?php echo get_phrase('select_which_version_to_keep_or_merge_manually'); ?>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning btn-lg" onclick="resolveConflict('local')">
                                <i class="fa fa-arrow-left"></i> <?php echo get_phrase('keep_local_version'); ?>
                            </button>
                            <button type="button" class="btn btn-info btn-lg" onclick="resolveConflict('remote')">
                                <i class="fa fa-arrow-right"></i> <?php echo get_phrase('keep_remote_version'); ?>
                            </button>
                            <a href="<?php echo site_url('sync_conflicts/merge_view/' . $conflict->id); ?>" class="btn btn-success btn-lg">
                                <i class="fa fa-code-fork"></i> <?php echo get_phrase('merge_manually'); ?>
                            </a>
                            <button type="button" class="btn btn-default btn-lg" onclick="ignoreConflict()">
                                <i class="fa fa-ban"></i> <?php echo get_phrase('ignore'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Side by Side Comparison -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <i class="fa fa-server"></i> <?php echo get_phrase('local_version'); ?>
                                    <small class="pull-right">
                                        <?php echo $local_location ? $local_location->location_name : $conflict->local_device_id; ?>
                                        | <?php echo date('M d, Y H:i:s', strtotime($conflict->local_modified_at)); ?>
                                    </small>
                                </h3>
                            </div>
                            <div class="panel-body">
                                <table class="table table-condensed">
                                    <?php foreach ($diff as $field => $values): ?>
                                    <tr class="<?php echo $values['is_different'] ? 'warning' : ''; ?>">
                                        <th width="150"><?php echo $field; ?></th>
                                        <td>
                                            <?php if ($values['is_different']): ?>
                                                <strong class="text-warning"><?php echo $this->format_value($values['local']); ?></strong>
                                            <?php else: ?>
                                                <?php echo $this->format_value($values['local']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <i class="fa fa-cloud"></i> <?php echo get_phrase('remote_version'); ?>
                                    <small class="pull-right">
                                        <?php echo $remote_location ? $remote_location->location_name : $conflict->remote_device_id; ?>
                                        | <?php echo date('M d, Y H:i:s', strtotime($conflict->remote_modified_at)); ?>
                                    </small>
                                </h3>
                            </div>
                            <div class="panel-body">
                                <table class="table table-condensed">
                                    <?php foreach ($diff as $field => $values): ?>
                                    <tr class="<?php echo $values['is_different'] ? 'info' : ''; ?>">
                                        <th width="150"><?php echo $field; ?></th>
                                        <td>
                                            <?php if ($values['is_different']): ?>
                                                <strong class="text-info"><?php echo $this->format_value($values['remote']); ?></strong>
                                            <?php else: ?>
                                                <?php echo $this->format_value($values['remote']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Differences Summary -->
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <i class="fa fa-list-alt"></i> <?php echo get_phrase('differences_summary'); ?>
                                </h3>
                            </div>
                            <div class="panel-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo get_phrase('field'); ?></th>
                                            <th><?php echo get_phrase('local_value'); ?></th>
                                            <th><?php echo get_phrase('remote_value'); ?></th>
                                            <th><?php echo get_phrase('action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $diff_count = 0; ?>
                                        <?php foreach ($diff as $field => $values): ?>
                                            <?php if ($values['is_different']): ?>
                                                <?php $diff_count++; ?>
                                                <tr>
                                                    <td><strong><?php echo $field; ?></strong></td>
                                                    <td class="warning"><?php echo $this->format_value($values['local']); ?></td>
                                                    <td class="info"><?php echo $this->format_value($values['remote']); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-xs">
                                                            <button type="button" class="btn btn-warning" 
                                                                    onclick="selectFieldVersion('<?php echo $field; ?>', 'local')">
                                                                <i class="fa fa-arrow-left"></i> Local
                                                            </button>
                                                            <button type="button" class="btn btn-info" 
                                                                    onclick="selectFieldVersion('<?php echo $field; ?>', 'remote')">
                                                                Remote <i class="fa fa-arrow-right"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if ($diff_count === 0): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    <?php echo get_phrase('no_differences_found'); ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Merge Modal -->
<div class="modal fade" id="mergeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-code-fork"></i> <?php echo get_phrase('merge_versions'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <form id="mergeForm">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="150"><?php echo get_phrase('field'); ?></th>
                                <th><?php echo get_phrase('value'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diff as $field => $values): ?>
                                <?php if ($values['is_different']): ?>
                                    <tr>
                                        <th><?php echo $field; ?></th>
                                        <td>
                                            <div class="input-group">
                                                <div class="input-group-btn">
                                                    <button type="button" class="btn btn-warning" 
                                                            onclick="$('#merge_<?php echo $field; ?>').val('<?php echo htmlspecialchars($values['local'], ENT_QUOTES); ?>')">
                                                        <i class="fa fa-arrow-left"></i>
                                                    </button>
                                                </div>
                                                <input type="text" class="form-control" 
                                                       id="merge_<?php echo $field; ?>" 
                                                       name="<?php echo $field; ?>"
                                                       value="<?php echo htmlspecialchars($values['local'], ENT_QUOTES); ?>">
                                                <div class="input-group-btn">
                                                    <button type="button" class="btn btn-info" 
                                                            onclick="$('#merge_<?php echo $field; ?>').val('<?php echo htmlspecialchars($values['remote'], ENT_QUOTES); ?>')">
                                                        <i class="fa fa-arrow-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <input type="hidden" name="<?php echo $field; ?>" 
                                           value="<?php echo htmlspecialchars($values['local'], ENT_QUOTES); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo get_phrase('cancel'); ?>
                </button>
                <button type="button" class="btn btn-success" onclick="submitMerge()">
                    <i class="fa fa-check"></i> <?php echo get_phrase('apply_merge'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var conflictId = <?php echo $conflict->id; ?>;

function resolveConflict(version) {
    var url = version === 'local' 
        ? '<?php echo site_url("sync_conflicts/keep_local/"); ?>' + conflictId
        : '<?php echo site_url("sync_conflicts/keep_remote/"); ?>' + conflictId;
    
    if (!confirm('Are you sure you want to keep the ' + version + ' version?')) {
        return;
    }
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                window.location.href = '<?php echo site_url("sync_conflicts"); ?>';
            } else {
                alert(response.message);
            }
        }
    });
}

function showMergeModal() {
    $('#mergeModal').modal('show');
}

function submitMerge() {
    var formData = $('#mergeForm').serialize();
    
    $.ajax({
        url: '<?php echo site_url("sync_conflicts/merge/"); ?>' + conflictId,
        type: 'POST',
        data: { merged_data: JSON.stringify(formDataToObject(formData)) },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                window.location.href = '<?php echo site_url("sync_conflicts"); ?>';
            } else {
                alert(response.message);
            }
        }
    });
}

function ignoreConflict() {
    if (!confirm('Are you sure you want to ignore this conflict? No changes will be made.')) {
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url("sync_conflicts/ignore/"); ?>' + conflictId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                window.location.href = '<?php echo site_url("sync_conflicts"); ?>';
            } else {
                alert(response.message);
            }
        }
    });
}

function formDataToObject(formData) {
    var obj = {};
    var pairs = formData.split('&');
    for (var i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split('=');
        var key = decodeURIComponent(pair[0]);
        var value = decodeURIComponent(pair[1] || '');
        obj[key] = value;
    }
    return obj;
}

function selectFieldVersion(field, version) {
    // This would update the merge form field
    var localVal = '<?php echo json_encode($conflict->local_data); ?>';
    var remoteVal = '<?php echo json_encode($conflict->remote_data); ?>';
    var data = version === 'local' ? JSON.parse(localVal) : JSON.parse(remoteVal);
    
    if ($('#merge_' + field).length) {
        $('#merge_' + field).val(data[field]);
    }
}
</script>

<!-- Sync Conflicts JavaScript -->
<script src="<?php echo base_url(); ?>assets/backend/js/sync_conflicts.js?v=<?php echo time(); ?>"></script>

<?php
// Helper function to format values
function format_value($value) {
    if (is_null($value)) {
        return '<span class="text-muted">NULL</span>';
    }
    if (is_array($value) || is_object($value)) {
        return '<code>' . json_encode($value) . '</code>';
    }
    if (strlen($value) > 100) {
        return htmlspecialchars(substr($value, 0, 100)) . '...';
    }
    return htmlspecialchars($value);
}
$this->format_value = 'format_value';
?>
