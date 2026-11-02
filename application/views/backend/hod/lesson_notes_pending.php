<?php
/**
 * HOD Pending Lesson Notes View
 * 
 * Displays lesson notes pending HOD review for assigned subjects.
 * 
 * Requirements: 10.2, 10.3
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-file-text-o"></i> <?php echo get_phrase('pending_lesson_notes'); ?>
            <small><?php echo get_phrase('review_and_endorse'); ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url(); ?>hod/dashboard"><i class="fa fa-dashboard"></i> <?php echo get_phrase('home'); ?></a></li>
            <li class="active"><?php echo get_phrase('pending_lesson_notes'); ?></li>
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

        <!-- Filters -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-filter"></i> <?php echo get_phrase('filters'); ?></h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="box-body">
                <form method="GET" action="<?php echo base_url(); ?>hod/lesson_notes_pending" class="form-inline">
                    <div class="form-group mr-10">
                        <label><?php echo get_phrase('subject'); ?>:</label>
                        <select name="subject_id" class="form-control select2" style="width: 200px;">
                            <option value=""><?php echo get_phrase('all_subjects'); ?></option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject->subject_id; ?>" <?php echo $filters['subject_id'] == $subject->subject_id ? 'selected' : ''; ?>>
                                    <?php echo $subject->name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mr-10">
                        <label><?php echo get_phrase('class'); ?>:</label>
                        <select name="class_id" class="form-control select2" style="width: 150px;">
                            <option value=""><?php echo get_phrase('all_classes'); ?></option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class->class_id; ?>" <?php echo $filters['class_id'] == $class->class_id ? 'selected' : ''; ?>>
                                    <?php echo $class->name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mr-10">
                        <label><?php echo get_phrase('term'); ?>:</label>
                        <select name="term" class="form-control" style="width: 100px;">
                            <option value="1" <?php echo $filters['term'] == 1 ? 'selected' : ''; ?>>Term 1</option>
                            <option value="2" <?php echo $filters['term'] == 2 ? 'selected' : ''; ?>>Term 2</option>
                            <option value="3" <?php echo $filters['term'] == 3 ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>

                    <div class="form-group mr-10">
                        <label><?php echo get_phrase('week'); ?>:</label>
                        <select name="week_number" class="form-control" style="width: 100px;">
                            <option value=""><?php echo get_phrase('all_weeks'); ?></option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filters['week_number'] == $i ? 'selected' : ''; ?>>
                                    Week <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i> <?php echo get_phrase('filter'); ?>
                    </button>
                    <a href="<?php echo base_url(); ?>hod/lesson_notes_pending" class="btn btn-default">
                        <i class="fa fa-refresh"></i> <?php echo get_phrase('reset'); ?>
                    </a>
                </form>
            </div>
        </div>

        <!-- Pending Lesson Notes List -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-list"></i> <?php echo get_phrase('lesson_notes_awaiting_review'); ?>
                    <span class="badge bg-yellow"><?php echo count($lesson_notes); ?></span>
                </h3>
            </div>
            <div class="box-body table-responsive">
                <?php if (empty($lesson_notes)): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> <?php echo get_phrase('no_pending_lesson_notes_found'); ?>
                    </div>
                <?php else: ?>
                    <table class="table table-bordered table-striped table-hover" id="pending-lesson-notes-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th><?php echo get_phrase('teacher'); ?></th>
                                <th><?php echo get_phrase('subject'); ?></th>
                                <th><?php echo get_phrase('class'); ?></th>
                                <th><?php echo get_phrase('title'); ?></th>
                                <th><?php echo get_phrase('week'); ?></th>
                                <th><?php echo get_phrase('term'); ?></th>
                                <th><?php echo get_phrase('submitted'); ?></th>
                                <th style="width: 180px;"><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach ($lesson_notes as $lesson_note): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td>
                                        <span class="label label-info"><?php echo $lesson_note->teacher_name; ?></span>
                                    </td>
                                    <td><?php echo $lesson_note->subject_name; ?></td>
                                    <td><?php echo $lesson_note->class_name; ?></td>
                                    <td>
                                        <a href="<?php echo base_url(); ?>hod/lesson_note_review/<?php echo $lesson_note->lesson_note_id; ?>">
                                            <?php echo character_limiter($lesson_note->title, 30); ?>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-blue">Week <?php echo $lesson_note->week_number; ?></span></td>
                                    <td><span class="badge bg-purple">Term <?php echo $lesson_note->term; ?></span></td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($lesson_note->created_at)); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo base_url(); ?>hod/lesson_note_review/<?php echo $lesson_note->lesson_note_id; ?>" 
                                               class="btn btn-info btn-sm" title="<?php echo get_phrase('review'); ?>">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-success btn-sm btn-endorse" 
                                                    data-id="<?php echo $lesson_note->lesson_note_id; ?>"
                                                    data-title="<?php echo htmlspecialchars($lesson_note->title); ?>"
                                                    title="<?php echo get_phrase('endorse'); ?>">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Endorse Confirmation Modal -->
<div class="modal fade" id="modal-endorse" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo get_phrase('endorse_lesson_note'); ?></h4>
            </div>
            <div class="modal-body">
                <p><?php echo get_phrase('are_you_sure_you_want_to_endorse_this_lesson_note'); ?></p>
                <p><strong id="endorse-title"></strong></p>
                <p class="text-muted"><small><?php echo get_phrase('endorsed_notes_will_be_forwarded_to_admin'); ?></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('cancel'); ?></button>
                <a href="#" id="btn-confirm-endorse" class="btn btn-success">
                    <i class="fa fa-check"></i> <?php echo get_phrase('endorse'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#pending-lesson-notes-table').DataTable({
        "order": [[7, "desc"]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [0, 8] }
        ]
    });

    // Initialize Select2
    $('.select2').select2();

    // Endorse button click
    $('.btn-endorse').on('click', function() {
        var id = $(this).data('id');
        var title = $(this).data('title');
        
        $('#endorse-title').text(title);
        $('#btn-confirm-endorse').attr('href', '<?php echo base_url(); ?>hod/lesson_note_endorse/' + id);
        $('#modal-endorse').modal('show');
    });
});
</script>
