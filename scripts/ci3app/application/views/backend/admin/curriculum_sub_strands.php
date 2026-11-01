<?php
/**
 * GES Lesson Note System - Curriculum Sub-Strands Management
 * Requirements: 8.2, 8.6
 */
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-book"></i> <?php echo get_phrase('curriculum_sub_strands'); ?>
                </div>
            </div>
            <div class="panel-body">
                <!-- Filter by Strand -->
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-6">
                        <form method="get" class="form-inline">
                            <div class="form-group">
                                <label><?php echo get_phrase('filter_by_strand'); ?>:</label>
                                <select name="strand_id" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?php echo get_phrase('all_strands'); ?></option>
                                    <?php foreach ($strands as $strand): ?>
                                        <option value="<?php echo $strand->id; ?>" <?php echo ($selected_strand == $strand->id) ? 'selected' : ''; ?>>
                                            <?php echo $strand->name . ' (' . $strand->subject_name . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-right">
                        <button class="btn btn-primary" onclick="showAddSubStrandModal()">
                            <i class="entypo-plus"></i> <?php echo get_phrase('add_new_sub_strand'); ?>
                        </button>
                    </div>
                </div>

                <!-- Sub-Strands Table -->
                <table class="table table-bordered table-striped datatable" id="sub-strands-table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('strand'); ?></th>
                            <th><?php echo get_phrase('sub_strand_name'); ?></th>
                            <th><?php echo get_phrase('description'); ?></th>
                            <th><?php echo get_phrase('content_standards_count'); ?></th>
                            <th><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sub_strands)): ?>
                            <?php foreach ($sub_strands as $sub_strand): ?>
                                <tr>
                                    <td><?php echo $sub_strand->strand_name; ?></td>
                                    <td><?php echo $sub_strand->name; ?></td>
                                    <td><?php echo substr($sub_strand->description, 0, 100) . (strlen($sub_strand->description) > 100 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $sub_strand->content_standards_count ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showEditSubStrandModal(<?php echo $sub_strand->id; ?>)">
                                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit'); ?>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDeleteSubStrand(<?php echo $sub_strand->id; ?>)">
                                            <i class="entypo-trash"></i> <?php echo get_phrase('delete'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center"><?php echo get_phrase('no_sub_strands_found'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Sub-Strand Modal -->
<div class="modal fade" id="subStrandModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="subStrandModalTitle"><?php echo get_phrase('add_sub_strand'); ?></h4>
            </div>
            <form id="subStrandForm" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo get_phrase('strand'); ?> *</label>
                        <select name="strand_id" id="sub_strand_id" class="form-control" required>
                            <option value=""><?php echo get_phrase('select_strand'); ?></option>
                            <?php foreach ($strands as $strand): ?>
                                <option value="<?php echo $strand->id; ?>"><?php echo $strand->name . ' (' . $strand->subject_name . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('sub_strand_name'); ?> *</label>
                        <input type="text" name="name" id="sub_strand_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('description'); ?> *</label>
                        <textarea name="description" id="sub_strand_description" class="form-control" rows="4" required></textarea>
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
var subStrandsData = <?php echo json_encode($sub_strands); ?>;

function showAddSubStrandModal() {
    $('#subStrandModalTitle').text('<?php echo get_phrase('add_sub_strand'); ?>');
    $('#subStrandForm').attr('action', '<?php echo site_url('admin/curriculum_sub_strands/create'); ?>');
    $('#sub_strand_id').val('<?php echo $selected_strand; ?>');
    $('#sub_strand_name').val('');
    $('#sub_strand_description').val('');
    $('#subStrandModal').modal('show');
}

function showEditSubStrandModal(subStrandId) {
    var subStrand = subStrandsData.find(s => s.id == subStrandId);
    if (!subStrand) return;

    $('#subStrandModalTitle').text('<?php echo get_phrase('edit_sub_strand'); ?>');
    $('#subStrandForm').attr('action', '<?php echo site_url('admin/curriculum_sub_strands/update/'); ?>' + subStrandId);
    $('#sub_strand_id').val(subStrand.strand_id);
    $('#sub_strand_name').val(subStrand.name);
    $('#sub_strand_description').val(subStrand.description);
    $('#subStrandModal').modal('show');
}

function confirmDeleteSubStrand(subStrandId) {
    const message = '<?php echo get_phrase('are_you_sure_delete_sub_strand'); ?><br><br>' +
                    '<strong class="text-warning"><?php echo get_phrase('this_will_also_delete_related_content_standards'); ?></strong>';
    showCustomConfirm(message, function() {
        window.location.href = '<?php echo site_url('admin/curriculum_sub_strands/delete/'); ?>' + subStrandId;
    });
}

$(document).ready(function() {
    $('#sub-strands-table').DataTable({
        "order": [[0, "asc"], [1, "asc"]],
        "pageLength": 25
    });
});
</script>
