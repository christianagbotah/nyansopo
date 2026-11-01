<?php
/**
 * HOD Subject Assignments Management View
 * 
 * Allows admin to assign subjects to HODs for lesson note review.
 * 
 * Requirements: 10.8
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-user-plus"></i> <?php echo get_phrase('hod_subject_assignments'); ?>
            <small><?php echo get_phrase('assign_subjects_to_heads_of_department'); ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url(); ?>admin/dashboard"><i class="fa fa-dashboard"></i> <?php echo get_phrase('home'); ?></a></li>
            <li><a href="#"><?php echo get_phrase('academic'); ?></a></li>
            <li class="active"><?php echo get_phrase('hod_subject_assignments'); ?></li>
        </ol>
    </section>

    <section class="content">
        <!-- Flash Messages -->
        <?php if ($this->session->flashdata('flash_message')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fa fa-check"></i> <?php echo $this->session->flashdata('flash_message'); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($this->session->flashdata('error_message')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fa fa-ban"></i> <?php echo $this->session->flashdata('error_message'); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Add Assignment Form -->
            <div class="col-md-4">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-plus"></i> <?php echo get_phrase('add_assignment'); ?></h3>
                    </div>
                    <div class="box-body">
                        <form action="<?php echo base_url(); ?>admin/hod_subject_assignments/create" method="POST">
                            <div class="form-group">
                                <label><?php echo get_phrase('select_hod'); ?> <span class="text-red">*</span></label>
                                <select name="hod_id" class="form-control select2" required style="width: 100%;">
                                    <option value=""><?php echo get_phrase('select_hod'); ?></option>
                                    <?php foreach ($hods as $hod): ?>
                                        <option value="<?php echo $hod->teacher_id; ?>">
                                            <?php echo $hod->name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><?php echo get_phrase('select_subject'); ?> <span class="text-red">*</span></label>
                                <select name="subject_id" class="form-control select2" required style="width: 100%;">
                                    <option value=""><?php echo get_phrase('select_subject'); ?></option>
                                    <?php 
                                    $current_class = '';
                                    foreach ($subjects as $subject): 
                                        $class_name = $this->db->get_where('class', array('class_id' => $subject->class_id))->row()->name ?? '';
                                        if ($current_class != $class_name):
                                            if ($current_class != '') echo '</optgroup>';
                                            echo '<optgroup label="' . $class_name . '">';
                                            $current_class = $class_name;
                                        endif;
                                    ?>
                                        <option value="<?php echo $subject->subject_id; ?>">
                                            <?php echo $subject->name; ?> (<?php echo $subject->year; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-plus"></i> <?php echo get_phrase('add_assignment'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Help Box -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-info-circle"></i> <?php echo get_phrase('information'); ?></h3>
                    </div>
                    <div class="box-body">
                        <p><strong><?php echo get_phrase('what_does_this_do'); ?></strong></p>
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check text-green"></i> <?php echo get_phrase('assigns_subjects_to_hods_for_review'); ?></li>
                            <li><i class="fa fa-check text-green"></i> <?php echo get_phrase('hods_can_only_see_their_assigned_subjects'); ?></li>
                            <li><i class="fa fa-check text-green"></i> <?php echo get_phrase('multiple_subjects_can_be_assigned_to_one_hod'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Current Assignments -->
            <div class="col-md-8">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list"></i> <?php echo get_phrase('current_assignments'); ?></h3>
                        <div class="box-tools pull-right">
                            <span class="label label-primary"><?php echo count($assignments); ?> <?php echo get_phrase('assignments'); ?></span>
                        </div>
                    </div>
                    <div class="box-body table-responsive">
                        <?php if (empty($assignments)): ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> <?php echo get_phrase('no_hod_subject_assignments_found'); ?>
                            </div>
                        <?php else: ?>
                            <table class="table table-bordered table-striped table-hover" id="assignments-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th><?php echo get_phrase('hod_name'); ?></th>
                                        <th><?php echo get_phrase('subject'); ?></th>
                                        <th><?php echo get_phrase('assigned_date'); ?></th>
                                        <th style="width: 100px;"><?php echo get_phrase('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td>
                                                <span class="label label-info"><?php echo $assignment->hod_name; ?></span>
                                            </td>
                                            <td><?php echo $assignment->subject_name; ?></td>
                                            <td>
                                                <small><?php echo date('M d, Y', strtotime($assignment->created_at)); ?></small>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm btn-delete" 
                                                        data-id="<?php echo $assignment->id; ?>"
                                                        data-hod="<?php echo $assignment->hod_name; ?>"
                                                        data-subject="<?php echo $assignment->subject_name; ?>"
                                                        title="<?php echo get_phrase('remove'); ?>">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="modal-delete" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo get_phrase('remove_assignment'); ?></h4>
            </div>
            <div class="modal-body">
                <p><?php echo get_phrase('are_you_sure_you_want_to_remove_this_assignment'); ?></p>
                <p><strong id="delete-hod"></strong> - <strong id="delete-subject"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('cancel'); ?></button>
                <a href="#" id="btn-confirm-delete" class="btn btn-danger">
                    <i class="fa fa-trash"></i> <?php echo get_phrase('remove'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#assignments-table').DataTable({
        "order": [[1, "asc"], [2, "asc"]],
        "pageLength": 25
    });

    // Initialize Select2
    $('.select2').select2();

    // Delete button click
    $('.btn-delete').on('click', function() {
        var id = $(this).data('id');
        var hod = $(this).data('hod');
        var subject = $(this).data('subject');
        
        $('#delete-hod').text(hod);
        $('#delete-subject').text(subject);
        $('#btn-confirm-delete').attr('href', '<?php echo base_url(); ?>admin/hod_subject_assignments/delete/' + id);
        $('#modal-delete').modal('show');
    });
});
</script>
