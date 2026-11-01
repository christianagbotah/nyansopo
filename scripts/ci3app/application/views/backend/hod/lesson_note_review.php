<?php
/**
 * HOD Lesson Note Review View
 * 
 * Displays full lesson note details for HOD review with endorse
 * and request revision options.
 * 
 * Requirements: 10.3, 10.4, 10.5, 10.7
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-file-text-o"></i> <?php echo get_phrase('review_lesson_note'); ?>
            <small><?php echo $lesson_note->title; ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url(); ?>hod/dashboard"><i class="fa fa-dashboard"></i> <?php echo get_phrase('home'); ?></a></li>
            <li><a href="<?php echo base_url(); ?>hod/lesson_notes_pending"><?php echo get_phrase('pending_lesson_notes'); ?></a></li>
            <li class="active"><?php echo get_phrase('review'); ?></li>
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
            <!-- Main Content -->
            <div class="col-md-8">
                <!-- Lesson Note Details -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-book"></i> <?php echo get_phrase('lesson_note_details'); ?></h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 200px;"><?php echo get_phrase('teacher'); ?></th>
                                <td><?php echo $lesson_note->teacher_name; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('subject'); ?></th>
                                <td><?php echo $lesson_note->subject_name; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('class'); ?></th>
                                <td><?php echo $lesson_note->class_name; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('title'); ?></th>
                                <td><?php echo $lesson_note->title; ?></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('week'); ?></th>
                                <td><span class="badge bg-blue">Week <?php echo $lesson_note->week_number; ?></span></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('term'); ?></th>
                                <td><span class="badge bg-purple">Term <?php echo $lesson_note->term; ?></span></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('lesson_date'); ?></th>
                                <td><?php echo date('l, F d, Y', strtotime($lesson_note->lesson_date)); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo get_phrase('status'); ?></th>
                                <td>
                                    <span class="label label-warning">
                                        <i class="fa fa-clock-o"></i> <?php echo ucfirst($lesson_note->status); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Curriculum Details -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-sitemap"></i> <?php echo get_phrase('curriculum_details'); ?></h3>
                    </div>
                    <div class="box-body">
                        <?php if ($lesson_note->strand_name): ?>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;"><?php echo get_phrase('strand'); ?></th>
                                    <td><?php echo $lesson_note->strand_name; ?></td>
                                </tr>
                                <?php if ($lesson_note->sub_strand_name): ?>
                                    <tr>
                                        <th><?php echo get_phrase('sub_strand'); ?></th>
                                        <td><?php echo $lesson_note->sub_strand_name; ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($lesson_note->content_standard_code): ?>
                                    <tr>
                                        <th><?php echo get_phrase('content_standard'); ?></th>
                                        <td>
                                            <strong><?php echo $lesson_note->content_standard_code; ?></strong><br>
                                            <small class="text-muted"><?php echo $lesson_note->content_standard_description; ?></small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        <?php else: ?>
                            <p class="text-muted"><?php echo get_phrase('no_curriculum_data_assigned'); ?></p>
                        <?php endif; ?>

                        <!-- Learning Indicators -->
                        <?php if (!empty($lesson_note->learning_indicators)): ?>
                            <h5><strong><?php echo get_phrase('learning_indicators'); ?></strong></h5>
                            <ul class="list-group">
                                <?php foreach ($lesson_note->learning_indicators as $indicator): ?>
                                    <li class="list-group-item">
                                        <span class="badge bg-info"><?php echo $indicator->code; ?></span>
                                        <?php echo $indicator->description; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Core Competencies -->
                        <?php if (!empty($lesson_note->core_competencies)): ?>
                            <h5><strong><?php echo get_phrase('core_competencies'); ?></strong></h5>
                            <div class="row">
                                <?php foreach ($lesson_note->core_competencies as $competency): ?>
                                    <div class="col-md-6">
                                        <div class="alert alert-info" style="padding: 10px;">
                                            <strong><?php echo $competency->name; ?></strong>
                                            <?php if ($competency->description): ?>
                                                <br><small><?php echo $competency->description; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lesson Content -->
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bookmark"></i> <?php echo get_phrase('lesson_content'); ?></h3>
                    </div>
                    <div class="box-body">
                        <?php if ($lesson_note->lesson_objectives): ?>
                            <h5><strong><?php echo get_phrase('objectives'); ?></strong></h5>
                            <div class="well" style="background: #f9f9f9;">
                                <?php echo $lesson_note->lesson_objectives; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($lesson_note->lesson_activities): ?>
                            <h5><strong><?php echo get_phrase('activities'); ?></strong></h5>
                            <div class="well" style="background: #f9f9f9;">
                                <?php echo $lesson_note->lesson_activities; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($lesson_note->lesson_content): ?>
                            <h5><strong><?php echo get_phrase('content'); ?></strong></h5>
                            <div class="well" style="background: #f9f9f9;">
                                <?php echo $lesson_note->lesson_content; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Teaching Resources -->
                <?php if (!empty($lesson_note->resources)): ?>
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-cubes"></i> <?php echo get_phrase('teaching_learning_resources'); ?></h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo get_phrase('resource'); ?></th>
                                        <th><?php echo get_phrase('details'); ?></th>
                                        <th><?php echo get_phrase('quantity'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lesson_note->resources as $resource): ?>
                                        <tr>
                                            <td>
                                                <?php echo $resource->resource_name; ?>
                                                <?php if ($resource->is_custom): ?>
                                                    <span class="label label-info" title="Custom Resource">Custom</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $resource->resource_details ?: '-'; ?></td>
                                            <td><?php echo $resource->quantity ?: '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Assessment Methods -->
                <?php if (!empty($lesson_note->assessments)): ?>
                    <div class="box box-danger">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-check-square-o"></i> <?php echo get_phrase('assessment_methods'); ?></h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo get_phrase('method'); ?></th>
                                        <th><?php echo get_phrase('notes'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lesson_note->assessments as $assessment): ?>
                                        <tr>
                                            <td>
                                                <?php echo $assessment->method_name; ?>
                                                <?php if ($assessment->is_custom): ?>
                                                    <span class="label label-info">Custom</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $assessment->notes ?: '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reference Materials -->
                <?php if (!empty($lesson_note->references)): ?>
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-book"></i> <?php echo get_phrase('reference_materials'); ?></h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo get_phrase('title'); ?></th>
                                        <th><?php echo get_phrase('author'); ?></th>
                                        <th><?php echo get_phrase('publisher'); ?></th>
                                        <th><?php echo get_phrase('year'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lesson_note->references as $reference): ?>
                                        <tr>
                                            <td><?php echo $reference->title; ?></td>
                                            <td><?php echo $reference->author ?: '-'; ?></td>
                                            <td><?php echo $reference->publisher ?: '-'; ?></td>
                                            <td><?php echo $reference->year ?: '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Attached File -->
                <?php if ($lesson_note->file_path): ?>
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-paperclip"></i> <?php echo get_phrase('attached_file'); ?></h3>
                        </div>
                        <div class="box-body">
                            <p>
                                <i class="fa fa-file"></i> <?php echo $lesson_note->file_name; ?>
                                <a href="<?php echo base_url() . $lesson_note->file_path; ?>" class="btn btn-sm btn-info" target="_blank">
                                    <i class="fa fa-download"></i> <?php echo get_phrase('download'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar - Actions -->
            <div class="col-md-4">
                <!-- Action Panel -->
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-gavel"></i> <?php echo get_phrase('actions'); ?></h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted"><?php echo get_phrase('review_the_lesson_note_and_take_action'); ?></p>
                        
                        <div class="form-group">
                            <a href="<?php echo base_url(); ?>hod/lesson_note_endorse/<?php echo $lesson_note->lesson_note_id; ?>" 
                               class="btn btn-success btn-block btn-lg" id="btn-endorse">
                                <i class="fa fa-check"></i> <?php echo get_phrase('endorse'); ?>
                            </a>
                            <p class="text-center text-muted"><small><?php echo get_phrase('forward_to_admin_for_approval'); ?></small></p>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label><strong><?php echo get_phrase('request_revision'); ?></strong></label>
                            <p class="text-muted"><small><?php echo get_phrase('provide_feedback_for_the_teacher'); ?></small></p>
                            
                            <form action="<?php echo base_url(); ?>hod/lesson_note_request_revision/<?php echo $lesson_note->lesson_note_id; ?>" method="POST">
                                <div class="form-group">
                                    <textarea name="feedback" class="form-control" rows="4" 
                                              placeholder="<?php echo get_phrase('enter_your_feedback_here'); ?>" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fa fa-edit"></i> <?php echo get_phrase('request_revision'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Revision History -->
                <?php if (!empty($revision_history)): ?>
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-history"></i> <?php echo get_phrase('revision_history'); ?></h3>
                        </div>
                        <div class="box-body" style="max-height: 300px; overflow-y: auto;">
                            <ul class="timeline">
                                <?php foreach ($revision_history as $revision): ?>
                                    <li>
                                        <i class="fa fa-circle text-info"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fa fa-clock-o"></i> <?php echo date('M d, Y H:i', strtotime($revision->created_at)); ?></span>
                                            <h5 class="timeline-header">
                                                <strong><?php echo ucfirst($revision->action); ?></strong>
                                                <?php if ($revision->new_status): ?>
                                                    <span class="label label-default"><?php echo ucfirst(str_replace('_', ' ', $revision->new_status)); ?></span>
                                                <?php endif; ?>
                                            </h5>
                                            <?php if ($revision->feedback): ?>
                                                <div class="timeline-body">
                                                    <p class="text-muted"><?php echo $revision->feedback; ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Back Button -->
                <a href="<?php echo base_url(); ?>hod/lesson_notes_pending" class="btn btn-default btn-block">
                    <i class="fa fa-arrow-left"></i> <?php echo get_phrase('back_to_pending_list'); ?>
                </a>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Endorse confirmation
    $('#btn-endorse').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        showCustomConfirm('<?php echo get_phrase("are_you_sure_you_want_to_endorse_this_lesson_note"); ?>', function() {
            // User confirmed - submit the form
            $btn.closest('form').submit();
        });
    });
});
</script>
