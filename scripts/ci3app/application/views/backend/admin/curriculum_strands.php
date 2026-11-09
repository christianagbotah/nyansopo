<?php
/**
 * GES Lesson Note System - Curriculum Strands Management
 * Requirements: 8.1, 8.5
 */
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-book"></i> <?php echo get_phrase('curriculum_strands'); ?>
                </div>
            </div>
            <div class="panel-body">
                <!-- Add New Strand Button -->
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-12">
                        <button class="btn btn-primary" onclick="showAddStrandModal()">
                            <i class="entypo-plus"></i> <?php echo get_phrase('add_new_strand'); ?>
                        </button>
                    </div>
                </div>

                <!-- Strands Table -->
                <table class="table table-bordered table-striped datatable" id="strands-table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('subject'); ?></th>
                            <th><?php echo get_phrase('class_level'); ?></th>
                            <th><?php echo get_phrase('strand_name'); ?></th>
                            <th><?php echo get_phrase('description'); ?></th>
                            <th><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($strands)): ?>
                            <?php foreach ($strands as $strand): ?>
                                <tr>
                                    <td><?php echo $strand->subject_name; ?></td>
                                    <td><?php echo $strand->class_name . ' ' . $strand->class_numeric; ?></td>
                                    <td><?php echo $strand->name; ?></td>
                                    <td><?php echo substr($strand->description, 0, 100) . (strlen($strand->description) > 100 ? '...' : ''); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showEditStrandModal(<?php echo $strand->id; ?>)">
                                            <i class="entypo-pencil"></i> <?php echo get_phrase('edit'); ?>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDeleteStrand(<?php echo $strand->id; ?>)">
                                            <i class="entypo-trash"></i> <?php echo get_phrase('delete'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center"><?php echo get_phrase('no_strands_found'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Strand Modal -->
<div class="modal fade" id="strandModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="strandModalTitle"><?php echo get_phrase('add_strand'); ?></h4>
            </div>
            <form id="strandForm" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo get_phrase('subject'); ?> *</label>
                        <select name="subject_id" id="subject_id" class="form-control" required>
                            <option value=""><?php echo get_phrase('select_subject'); ?></option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject->subject_id; ?>"><?php echo $subject->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('class_level'); ?> *</label>
                        <select name="class_level" id="class_level" class="form-control" required>
                            <option value=""><?php echo get_phrase('select_class'); ?></option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class->class_id; ?>"><?php echo $class->name . ' ' . $class->name_numeric; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('strand_name'); ?> *</label>
                        <input type="text" name="name" id="strand_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('description'); ?> *</label>
                        <textarea name="description" id="strand_description" class="form-control" rows="4" required></textarea>
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
var strandsData = <?php echo json_encode($strands); ?>;

function showAddStrandModal() {
    $('#strandModalTitle').text('<?php echo get_phrase('add_strand'); ?>');
    $('#strandForm').attr('action', '<?php echo site_url('admin/curriculum_strands/create'); ?>');
    $('#subject_id').val('');
    $('#class_level').val('');
    $('#strand_name').val('');
    $('#strand_description').val('');
    $('#strandModal').modal('show');
}

function showEditStrandModal(strandId) {
    var strand = strandsData.find(s => s.id == strandId);
    if (!strand) return;

    $('#strandModalTitle').text('<?php echo get_phrase('edit_strand'); ?>');
    $('#strandForm').attr('action', '<?php echo site_url('admin/curriculum_strands/update/'); ?>' + strandId);
    $('#subject_id').val(strand.subject_id);
    $('#class_level').val(strand.class_level);
    $('#strand_name').val(strand.name);
    $('#strand_description').val(strand.description);
    $('#strandModal').modal('show');
}

function confirmDeleteStrand(strandId) {
    showCustomConfirm(
        '<?php echo get_phrase('are_you_sure_delete_strand'); ?>',
        function() {
            // User clicked Yes - proceed with deletion
            window.location.href = '<?php echo site_url('admin/curriculum_strands/delete/'); ?>' + strandId;
        }
        // User clicked No - do nothing
    );
}

$(document).ready(function() {
    $('#strands-table').DataTable({
        "order": [[0, "asc"], [1, "asc"]],
        "pageLength": 25
    });
});
</script>
