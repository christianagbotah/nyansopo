<?php
/**
 * GES Lesson Note System - Curriculum Learning Indicators Management
 * Requirements: 8.4, 8.8
 */
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-book"></i> <?php echo get_phrase('curriculum_learning_indicators'); ?>
                </div>
            </div>
            <div class="panel-body">
                <!-- Filter by Content Standard -->
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-6">
                        <form method="get" class="form-inline">
                            <div class="form-group">
                                <label><?php echo get_phrase('filter_by_content_standard'); ?>:</label>
                                <select name="content_standard_id" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?php echo get_phrase('all_content_standards'); ?></option>
                                    <?php foreach ($content_standards as $standard): ?>
                                        <option value="<?php echo $standard->id; ?>" <?php echo ($selected_content_standard == $standard->id) ? 'selected' : ''; ?>>
                                            <?php echo $standard->code . ' - ' . substr($standard->description, 0, 50) . (strlen($standard->description) > 50 ? '...' : ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-right">
                        <button class="btn btn-primary" onclick="showAddLearningIndicatorModal()">
                            <i class="entypo-plus"></i> <?php echo get_phrase('add_new_learning_indicator'); ?>
                        </button>
                    </div>
                </div>

                <!-- Learning Indicators Table -->
                <table class="table table-bordered table-striped datatable" id="learning-indicators-table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('code'); ?></th>
                            <th><?php echo get_phrase('content_standard'); ?></th>
                            <th><?php echo get_phrase('description'); ?></th>
                            <th><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($learning_indicators)): ?>
                            <?php foreach ($learning_indicators as $indicator): ?>
                                <tr>
                                    <td><strong><?php echo $indicator->code; ?></strong></td>
                                    <td>
                                        <small class="text-muted"><?php echo $indicator->content_standard_code; ?></small><br>
                                        <?php echo substr($indicator->content_standard_description, 0, 60) . (strlen($indicator->content_standard_description) > 60 ? '...' : ''); ?>
                                    </td>
                                    <td><?php echo $indicator->description; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showEditLearningIndicatorModal(<?php echo $indicator->id; ?>)">
                                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit'); ?>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDeleteLearningIndicator(<?php echo $indicator->id; ?>)">
                                            <i class="entypo-trash"></i> <?php echo get_phrase('delete'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center"><?php echo get_phrase('no_learning_indicators_found'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Learning Indicator Modal -->
<div class="modal fade" id="learningIndicatorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="learningIndicatorModalTitle"><?php echo get_phrase('add_learning_indicator'); ?></h4>
            </div>
            <form id="learningIndicatorForm" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo get_phrase('content_standard'); ?> *</label>
                        <select name="content_standard_id" id="indicator_content_standard_id" class="form-control" required>
                            <option value=""><?php echo get_phrase('select_content_standard'); ?></option>
                            <?php foreach ($content_standards as $standard): ?>
                                <option value="<?php echo $standard->id; ?>">
                                    <?php echo $standard->code . ' - ' . substr($standard->description, 0, 50) . (strlen($standard->description) > 50 ? '...' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('code'); ?> *</label>
                        <input type="text" name="code" id="indicator_code" class="form-control" placeholder="e.g., B4.1.1.1.1" required>
                        <small class="text-muted"><?php echo get_phrase('unique_code_for_learning_indicator'); ?></small>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('description'); ?> *</label>
                        <textarea name="description" id="indicator_description" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo get_phrase('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var learningIndicatorsData = <?php echo json_encode($learning_indicators); ?>;

function showAddLearningIndicatorModal() {
    $('#learningIndicatorModalTitle').text('<?php echo get_phrase('add_learning_indicator'); ?>');
    $('#learningIndicatorForm').attr('action', '<?php echo site_url('admin/curriculum_learning_indicators/create'); ?>');
    $('#indicator_content_standard_id').val('<?php echo $selected_content_standard; ?>');
    $('#indicator_code').val('');
    $('#indicator_description').val('');
    $('#learningIndicatorModal').modal('show');
}

function showEditLearningIndicatorModal(indicatorId) {
    var indicator = learningIndicatorsData.find(i => i.id == indicatorId);
    if (!indicator) return;

    $('#learningIndicatorModalTitle').text('<?php echo get_phrase('edit_learning_indicator'); ?>');
    $('#learningIndicatorForm').attr('action', '<?php echo site_url('admin/curriculum_learning_indicators/update/'); ?>' + indicatorId);
    $('#indicator_content_standard_id').val(indicator.content_standard_id);
    $('#indicator_code').val(indicator.code);
    $('#indicator_description').val(indicator.description);
    $('#learningIndicatorModal').modal('show');
}

function confirmDeleteLearningIndicator(indicatorId) {
    showCustomConfirm(
        '<?php echo get_phrase('are_you_sure_delete_learning_indicator'); ?>',
        function() {
            // User clicked Yes - proceed with deletion
            window.location.href = '<?php echo site_url('admin/curriculum_learning_indicators/delete/'); ?>' + indicatorId;
        }
        // User clicked No - do nothing
    );
}

$(document).ready(function() {
    $('#learning-indicators-table').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});
</script>
