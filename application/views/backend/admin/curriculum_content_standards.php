<?php
/**
 * GES Lesson Note System - Curriculum Content Standards Management
 * Requirements: 8.3, 8.7
 */
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-book"></i> <?php echo get_phrase('curriculum_content_standards'); ?>
                </div>
            </div>
            <div class="panel-body">
                <!-- Filter by Sub-Strand -->
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-6">
                        <form method="get" class="form-inline">
                            <div class="form-group">
                                <label><?php echo get_phrase('filter_by_sub_strand'); ?>:</label>
                                <select name="sub_strand_id" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?php echo get_phrase('all_sub_strands'); ?></option>
                                    <?php foreach ($sub_strands as $sub_strand): ?>
                                        <option value="<?php echo $sub_strand->id; ?>" <?php echo ($selected_sub_strand == $sub_strand->id) ? 'selected' : ''; ?>>
                                            <?php echo $sub_strand->name . ' (' . $sub_strand->strand_name . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-right">
                        <button class="btn btn-primary" onclick="showAddContentStandardModal()">
                            <i class="entypo-plus"></i> <?php echo get_phrase('add_new_content_standard'); ?>
                        </button>
                    </div>
                </div>

                <!-- Content Standards Table -->
                <table class="table table-bordered table-striped datatable" id="content-standards-table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('code'); ?></th>
                            <th><?php echo get_phrase('sub_strand'); ?></th>
                            <th><?php echo get_phrase('description'); ?></th>
                            <th><?php echo get_phrase('learning_indicators_count'); ?></th>
                            <th><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($content_standards)): ?>
                            <?php foreach ($content_standards as $standard): ?>
                                <tr>
                                    <td><strong><?php echo $standard->code; ?></strong></td>
                                    <td><?php echo $standard->sub_strand_name; ?></td>
                                    <td><?php echo substr($standard->description, 0, 100) . (strlen($standard->description) > 100 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $standard->learning_indicators_count ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showEditContentStandardModal(<?php echo $standard->id; ?>)">
                                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit'); ?>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDeleteContentStandard(<?php echo $standard->id; ?>)">
                                            <i class="entypo-trash"></i> <?php echo get_phrase('delete'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center"><?php echo get_phrase('no_content_standards_found'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Content Standard Modal -->
<div class="modal fade" id="contentStandardModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="contentStandardModalTitle"><?php echo get_phrase('add_content_standard'); ?></h4>
            </div>
            <form id="contentStandardForm" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo get_phrase('sub_strand'); ?> *</label>
                        <select name="sub_strand_id" id="content_standard_sub_strand_id" class="form-control" required>
                            <option value=""><?php echo get_phrase('select_sub_strand'); ?></option>
                            <?php foreach ($sub_strands as $sub_strand): ?>
                                <option value="<?php echo $sub_strand->id; ?>"><?php echo $sub_strand->name . ' (' . $sub_strand->strand_name . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('code'); ?> *</label>
                        <input type="text" name="code" id="content_standard_code" class="form-control" placeholder="e.g., B4.1.1.1" required>
                        <small class="text-muted"><?php echo get_phrase('unique_code_for_content_standard'); ?></small>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('description'); ?> *</label>
                        <textarea name="description" id="content_standard_description" class="form-control" rows="4" required></textarea>
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
var contentStandardsData = <?php echo json_encode($content_standards); ?>;

function showAddContentStandardModal() {
    $('#contentStandardModalTitle').text('<?php echo get_phrase('add_content_standard'); ?>');
    $('#contentStandardForm').attr('action', '<?php echo site_url('admin/curriculum_content_standards/create'); ?>');
    $('#content_standard_sub_strand_id').val('<?php echo $selected_sub_strand; ?>');
    $('#content_standard_code').val('');
    $('#content_standard_description').val('');
    $('#contentStandardModal').modal('show');
}

function showEditContentStandardModal(standardId) {
    var standard = contentStandardsData.find(s => s.id == standardId);
    if (!standard) return;

    $('#contentStandardModalTitle').text('<?php echo get_phrase('edit_content_standard'); ?>');
    $('#contentStandardForm').attr('action', '<?php echo site_url('admin/curriculum_content_standards/update/'); ?>' + standardId);
    $('#content_standard_sub_strand_id').val(standard.sub_strand_id);
    $('#content_standard_code').val(standard.code);
    $('#content_standard_description').val(standard.description);
    $('#contentStandardModal').modal('show');
}

function confirmDeleteContentStandard(standardId) {
    showCustomConfirm(
        '<?php echo get_phrase('are_you_sure_delete_content_standard'); ?>\n<?php echo get_phrase('this_will_also_delete_related_learning_indicators'); ?>',
        function() {
            // User clicked Yes - proceed with deletion
            window.location.href = '<?php echo site_url('admin/curriculum_content_standards/delete/'); ?>' + standardId;
        }
        // User clicked No - do nothing
    );
}

$(document).ready(function() {
    $('#content-standards-table').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});
</script>
