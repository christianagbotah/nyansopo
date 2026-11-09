<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="border-b px-6 py-4">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800"><?php echo $lesson_note->title; ?></h2>
                <p class="text-gray-600 mt-1">
                    <?php echo get_phrase('week') . ' ' . $lesson_note->week_number . ' • ' . get_phrase('term') . ' ' . $lesson_note->term; ?>
                </p>
            </div>
            <div class="flex gap-3">
                <?php if ($lesson_note->status == 'approved'): ?>
                <a href="<?php echo site_url('teacher/lesson_note_print/' . $lesson_note->lesson_note_id); ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center gap-2">
                    <i class="entypo-print"></i> <?php echo get_phrase('print'); ?>
                </a>
                <?php endif; ?>
                <a href="<?php echo site_url('teacher/lesson_notes'); ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition">
                    <i class="entypo-left-open"></i> <?php echo get_phrase('back'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Status Banner -->
    <?php
    $status_config = array(
        'draft' => array('color' => 'gray', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => 'entypo-pencil'),
        'pending' => array('color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'entypo-clock'),
        'hod_reviewed' => array('color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'entypo-check'),
        'approved' => array('color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'icon' => 'entypo-check'),
        'declined' => array('color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'entypo-cancel'),
        'revision_requested' => array('color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'icon' => 'entypo-edit')
    );
    $status = isset($status_config[$lesson_note->status]) ? $status_config[$lesson_note->status] : $status_config['draft'];
    ?>
    <div class="<?php echo $status['bg']; ?> <?php echo $status['text']; ?> px-6 py-3 flex items-center gap-3">
        <i class="<?php echo $status['icon']; ?>"></i>
        <span class="font-medium"><?php echo get_phrase('status'); ?>: <?php echo get_phrase($lesson_note->status); ?></span>
        <?php if ($lesson_note->feedback): ?>
        <span class="ml-4">• <?php echo get_phrase('feedback'); ?>: <?php echo $lesson_note->feedback; ?></span>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Lesson Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('basic_information'); ?></h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-500"><?php echo get_phrase('class'); ?></label>
                            <p class="font-medium"><?php echo $lesson_note->class_name; ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500"><?php echo get_phrase('subject'); ?></label>
                            <p class="font-medium"><?php echo $lesson_note->subject_name; ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500"><?php echo get_phrase('lesson_date'); ?></label>
                            <p class="font-medium"><?php echo date('d M, Y', strtotime($lesson_note->lesson_date)); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500"><?php echo get_phrase('created'); ?></label>
                            <p class="font-medium"><?php echo date('d M, Y H:i', strtotime($lesson_note->created_at)); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Curriculum Details -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('curriculum_details'); ?></h3>
                    <div class="space-y-3">
                        <?php if ($lesson_note->strand_name): ?>
                        <div>
                            <label class="text-sm text-gray-500"><?php echo get_phrase('strand'); ?></label>
                            <p class="font-medium"><?php echo $lesson_note->strand_name; ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($lesson_note->sub_strand_name): ?>
                        <div>
                            <label class="text-sm text-gray-500"><?php echo get_phrase('sub_strand'); ?></label>
                            <p class="font-medium"><?php echo $lesson_note->sub_strand_name; ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($lesson_note->content_standard_code): ?>
                        <div>
                            <label class="text-sm text-gray-500"><?php echo get_phrase('content_standard'); ?></label>
                            <p class="font-medium"><?php echo $lesson_note->content_standard_code; ?> - <?php echo $lesson_note->content_standard_description; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Learning Indicators -->
                <?php if (!empty($lesson_note->learning_indicators)): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('learning_indicators'); ?></h3>
                    <ul class="space-y-2">
                        <?php foreach ($lesson_note->learning_indicators as $indicator): ?>
                        <li class="flex items-start gap-2">
                            <i class="entypo-check text-green-500 mt-1"></i>
                            <span><strong><?php echo $indicator->code; ?>:</strong> <?php echo $indicator->description; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Core Competencies -->
                <?php if (!empty($lesson_note->core_competencies)): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('core_competencies'); ?></h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($lesson_note->core_competencies as $competency): ?>
                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo $competency->name; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lesson Objectives -->
                <?php if ($lesson_note->lesson_objectives): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('lesson_objectives'); ?></h3>
                    <div class="prose prose-sm max-w-none">
                        <?php echo $lesson_note->lesson_objectives; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lesson Activities -->
                <?php if ($lesson_note->lesson_activities): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('lesson_activities'); ?></h3>
                    <div class="prose prose-sm max-w-none">
                        <?php echo $lesson_note->lesson_activities; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Teaching Resources -->
                <?php if (!empty($lesson_note->resources)): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('teaching_learning_resources'); ?></h3>
                    <ul class="space-y-2">
                        <?php foreach ($lesson_note->resources as $resource): ?>
                        <li class="flex items-center gap-2">
                            <i class="entypo-book text-blue-500"></i>
                            <span><?php echo $resource->resource_name; ?></span>
                            <?php if ($resource->quantity): ?>
                            <span class="text-gray-500 text-sm">(<?php echo $resource->quantity; ?>)</span>
                            <?php endif; ?>
                            <?php if ($resource->is_custom): ?>
                            <span class="bg-purple-100 text-purple-600 text-xs px-2 py-0.5 rounded"><?php echo get_phrase('custom'); ?></span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Assessment Methods -->
                <?php if (!empty($lesson_note->assessments)): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('assessment_methods'); ?></h3>
                    <ul class="space-y-2">
                        <?php foreach ($lesson_note->assessments as $assessment): ?>
                        <li class="flex items-start gap-2">
                            <i class="entypo-check text-green-500 mt-1"></i>
                            <div>
                                <span class="font-medium"><?php echo $assessment->method_name; ?></span>
                                <?php if ($assessment->notes): ?>
                                <p class="text-sm text-gray-600 mt-1"><?php echo $assessment->notes; ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Reference Materials -->
                <?php if (!empty($lesson_note->references)): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('reference_materials'); ?></h3>
                    <ul class="space-y-3">
                        <?php foreach ($lesson_note->references as $ref): ?>
                        <li class="border-l-4 border-blue-300 pl-3">
                            <p class="font-medium"><?php echo $ref->title; ?></p>
                            <?php if ($ref->author): ?>
                            <p class="text-sm text-gray-600"><?php echo get_phrase('by'); ?> <?php echo $ref->author; ?></p>
                            <?php endif; ?>
                            <?php if ($ref->publisher || $ref->year): ?>
                            <p class="text-sm text-gray-500">
                                <?php echo $ref->publisher; ?> <?php echo $ref->year ? '(' . $ref->year . ')' : ''; ?>
                                <?php if ($ref->page_numbers): ?>, <?php echo get_phrase('pages'); ?> <?php echo $ref->page_numbers; ?><?php endif; ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($ref->url): ?>
                            <a href="<?php echo $ref->url; ?>" target="_blank" class="text-sm text-blue-600 hover:underline"><?php echo $ref->url; ?></a>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Attached File -->
                <?php if ($lesson_note->file_path): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('attached_file'); ?></h3>
                    <a href="<?php echo base_url($lesson_note->file_path); ?>" target="_blank" class="inline-flex items-center gap-2 bg-blue-50 hover:bg-blue-100 text-blue-600 px-4 py-2 rounded-lg transition">
                        <i class="entypo-download"></i>
                        <?php echo $lesson_note->file_name; ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Revision History -->
            <div class="lg:col-span-1">
                <div class="bg-gray-50 rounded-lg p-4 sticky top-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="entypo-clock"></i>
                        <?php echo get_phrase('revision_history'); ?>
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">
                        <?php echo get_phrase('version'); ?>: <?php echo $lesson_note->version; ?> • 
                        <?php echo count($revision_history); ?> <?php echo get_phrase('revisions'); ?>
                    </p>

                    <?php if (!empty($revision_history)): ?>
                    <div class="space-y-4">
                        <?php foreach ($revision_history as $i => $revision): ?>
                        <div class="relative pl-6 pb-4 <?php echo $i < count($revision_history) - 1 ? 'border-l-2 border-gray-200' : ''; ?>">
                            <!-- Timeline dot -->
                            <div class="absolute left-0 top-0 w-3 h-3 rounded-full 
                                <?php 
                                $action_colors = array(
                                    'create' => 'bg-green-500',
                                    'update' => 'bg-blue-500',
                                    'submit' => 'bg-yellow-500',
                                    'approve' => 'bg-green-500',
                                    'decline' => 'bg-red-500',
                                    'endorse' => 'bg-blue-500',
                                    'request_revision' => 'bg-orange-500'
                                );
                                echo isset($action_colors[$revision->action]) ? $action_colors[$revision->action] : 'bg-gray-500';
                                ?>
                            " style="left: -7px;"></div>

                            <div class="text-sm">
                                <p class="font-medium text-gray-800">
                                    <?php echo get_phrase($revision->action); ?>
                                </p>
                                <p class="text-gray-500 text-xs mt-1">
                                    <?php echo date('d M, Y H:i', strtotime($revision->created_at)); ?>
                                </p>
                                <?php if ($revision->previous_status && $revision->new_status): ?>
                                <p class="text-gray-600 mt-1">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="px-2 py-0.5 rounded text-xs bg-gray-200"><?php echo get_phrase($revision->previous_status); ?></span>
                                        <i class="entypo-right-open text-xs"></i>
                                        <span class="px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700"><?php echo get_phrase($revision->new_status); ?></span>
                                    </span>
                                </p>
                                <?php endif; ?>
                                <?php if ($revision->feedback): ?>
                                <p class="text-gray-600 mt-1 italic">"<?php echo $revision->feedback; ?>"</p>
                                <?php endif; ?>
                                <p class="text-gray-400 text-xs mt-1">
                                    <?php echo get_phrase('by'); ?> <?php echo $revision->user_type; ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500 text-sm"><?php echo get_phrase('no_revision_history_available'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showFeedback(feedback) {
    showAjaxModal_alert(feedback, 'Info', false, true);
}
</script>
