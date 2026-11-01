<?php
/**
 * HOD Dashboard View
 * 
 * Main dashboard for Head of Department showing overview of
 * lesson notes pending review and statistics.
 * 
 * Requirements: 10.1, 10.2
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-dashboard"></i> <?php echo get_phrase('hod_dashboard'); ?>
            <small><?php echo get_phrase('head_of_department_panel'); ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> <?php echo get_phrase('home'); ?></a></li>
            <li class="active"><?php echo get_phrase('dashboard'); ?></li>
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

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-4 col-xs-6">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3><?php echo $pending_count; ?></h3>
                        <p><?php echo get_phrase('pending_review'); ?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-clock-o"></i>
                    </div>
                    <a href="<?php echo base_url(); ?>hod/lesson_notes_pending" class="small-box-footer">
                        <?php echo get_phrase('view_all'); ?> <i class="fa fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-4 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3><?php echo $endorsed_count; ?></h3>
                        <p><?php echo get_phrase('endorsed_this_term'); ?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <a href="<?php echo base_url(); ?>hod/lesson_note_review_history" class="small-box-footer">
                        <?php echo get_phrase('view_history'); ?> <i class="fa fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-4 col-xs-6">
                <div class="small-box bg-orange">
                    <div class="inner">
                        <h3><?php echo $revision_count; ?></h3>
                        <p><?php echo get_phrase('revisions_requested'); ?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-edit"></i>
                    </div>
                    <a href="<?php echo base_url(); ?>hod/lesson_note_review_history" class="small-box-footer">
                        <?php echo get_phrase('view_history'); ?> <i class="fa fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Managed Subjects -->
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-book"></i> <?php echo get_phrase('managed_subjects'); ?></h3>
                    </div>
                    <div class="box-body">
                        <?php if (empty($subjects)): ?>
                            <p class="text-muted"><?php echo get_phrase('no_subjects_assigned'); ?></p>
                        <?php else: ?>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo get_phrase('subject'); ?></th>
                                        <th><?php echo get_phrase('class'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td><?php echo $subject->name; ?></td>
                                            <td><?php echo $subject->class_name; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bolt"></i> <?php echo get_phrase('quick_actions'); ?></h3>
                    </div>
                    <div class="box-body">
                        <div class="list-group">
                            <a href="<?php echo base_url(); ?>hod/lesson_notes_pending" class="list-group-item">
                                <i class="fa fa-file-text-o fa-fw"></i> <?php echo get_phrase('review_pending_lesson_notes'); ?>
                                <span class="badge bg-yellow pull-right"><?php echo $pending_count; ?></span>
                            </a>
                            <a href="<?php echo base_url(); ?>hod/lesson_note_review_history" class="list-group-item">
                                <i class="fa fa-history fa-fw"></i> <?php echo get_phrase('view_review_history'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Help Box -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-question-circle"></i> <?php echo get_phrase('help'); ?></h3>
                    </div>
                    <div class="box-body">
                        <p><strong><?php echo get_phrase('your_role_as_hod'); ?></strong></p>
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check text-green"></i> <?php echo get_phrase('review_lesson_notes_from_your_subjects'); ?></li>
                            <li><i class="fa fa-check text-green"></i> <?php echo get_phrase('endorse_quality_lesson_notes'); ?></li>
                            <li><i class="fa fa-check text-green"></i> <?php echo get_phrase('request_revisions_with_feedback'); ?></li>
                            <li><i class="fa fa-check text-green"></i> <?php echo get_phrase('endorsed_notes_go_to_admin'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
