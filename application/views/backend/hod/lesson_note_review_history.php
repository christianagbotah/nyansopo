<?php
/**
 * HOD Review History View
 * 
 * Displays history of lesson notes reviewed by the HOD.
 * 
 * Requirements: 10.7
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-history"></i> <?php echo get_phrase('review_history'); ?>
            <small><?php echo get_phrase('your_reviewed_lesson_notes'); ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url(); ?>hod/dashboard"><i class="fa fa-dashboard"></i> <?php echo get_phrase('home'); ?></a></li>
            <li class="active"><?php echo get_phrase('review_history'); ?></li>
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

        <!-- Review History -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-list"></i> <?php echo get_phrase('reviewed_lesson_notes'); ?></h3>
            </div>
            <div class="box-body table-responsive">
                <?php if (empty($lesson_notes)): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> <?php echo get_phrase('no_reviewed_lesson_notes_found'); ?>
                    </div>
                <?php else: ?>
                    <table class="table table-bordered table-striped table-hover" id="review-history-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th><?php echo get_phrase('teacher'); ?></th>
                                <th><?php echo get_phrase('subject'); ?></th>
                                <th><?php echo get_phrase('class'); ?></th>
                                <th><?php echo get_phrase('title'); ?></th>
                                <th><?php echo get_phrase('week'); ?></th>
                                <th><?php echo get_phrase('term'); ?></th>
                                <th><?php echo get_phrase('your_action'); ?></th>
                                <th><?php echo get_phrase('current_status'); ?></th>
                                <th><?php echo get_phrase('review_date'); ?></th>
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
                                    <td><?php echo character_limiter($lesson_note->title, 25); ?></td>
                                    <td><span class="badge bg-blue">Week <?php echo $lesson_note->week_number; ?></span></td>
                                    <td><span class="badge bg-purple">Term <?php echo $lesson_note->term; ?></span></td>
                                    <td>
                                        <?php if ($lesson_note->status == 'hod_reviewed' || $lesson_note->status == 'approved'): ?>
                                            <span class="label label-success">
                                                <i class="fa fa-check"></i> <?php echo get_phrase('endorsed'); ?>
                                            </span>
                                        <?php elseif ($lesson_note->status == 'revision_requested'): ?>
                                            <span class="label label-warning">
                                                <i class="fa fa-edit"></i> <?php echo get_phrase('revision_requested'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_icon = '';
                                        switch ($lesson_note->status) {
                                            case 'approved':
                                                $status_class = 'label-success';
                                                $status_icon = 'fa-check-circle';
                                                break;
                                            case 'hod_reviewed':
                                                $status_class = 'label-info';
                                                $status_icon = 'fa-forward';
                                                break;
                                            case 'revision_requested':
                                                $status_class = 'label-warning';
                                                $status_icon = 'fa-edit';
                                                break;
                                            default:
                                                $status_class = 'label-default';
                                                $status_icon = 'fa-question';
                                        }
                                        ?>
                                        <span class="label <?php echo $status_class; ?>">
                                            <i class="fa <?php echo $status_icon; ?>"></i> 
                                            <?php echo ucfirst(str_replace('_', ' ', $lesson_note->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y H:i', strtotime($lesson_note->hod_review_date)); ?></small>
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

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#review-history-table').DataTable({
        "order": [[9, "desc"]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [0] }
        ]
    });
});
</script>
