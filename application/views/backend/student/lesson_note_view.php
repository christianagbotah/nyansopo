<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="border-b px-6 py-4">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800"><?php echo $lesson_note->title; ?></h2>
                <p class="text-gray-600 mt-1">
                    <?php echo $lesson_note->subject_name; ?> • 
                    <?php echo get_phrase('week') . ' ' . $lesson_note->week_number . ' • ' . get_phrase('term') . ' ' . $lesson_note->term; ?>
                </p>
            </div>
            <a href="<?php echo site_url('student/lesson_notes'); ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition">
                <i class="entypo-left-open"></i> <?php echo get_phrase('back'); ?>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Basic Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('lesson_details'); ?></h3>
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
                            <label class="text-sm text-gray-500"><?php echo get_phrase('teacher'); ?></label>
                            <p class="font-medium"><?php echo $lesson_note->teacher_name; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Curriculum Details -->
                <?php if ($lesson_note->strand_name || $lesson_note->sub_strand_name || $lesson_note->content_standard_code): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('curriculum_reference'); ?></h3>
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
                <?php endif; ?>

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
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
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
                            <span><?php echo $assessment->method_name; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Attached File -->
                <?php if ($lesson_note->file_path): ?>
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><?php echo get_phrase('attached_material'); ?></h3>
                    <a href="<?php echo base_url($lesson_note->file_path); ?>" target="_blank" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="entypo-download"></i>
                        <?php echo get_phrase('download'); ?> <?php echo $lesson_note->file_name; ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
