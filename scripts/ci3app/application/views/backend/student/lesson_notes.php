<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo get_phrase('lesson_notes');?></h2>
            <p class="text-gray-600"><?php echo get_phrase('view_approved_lesson_notes_from_your_teachers');?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <form method="GET" action="<?php echo site_url('student/lesson_notes');?>" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo get_phrase('subject');?></label>
                <select name="subject_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value=""><?php echo get_phrase('all_subjects');?></option>
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?php echo $subject->subject_id;?>" <?php echo ($filter_subject == $subject->subject_id) ? 'selected' : '';?>><?php echo $this->db->get_where('subject', array('subject_id' => $subject->subject_id))->row()->name;?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[100px]">
                <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo get_phrase('term');?></label>
                <select name="term" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value=""><?php echo get_phrase('all_terms');?></option>
                    <option value="1" <?php echo ($filter_term == '1') ? 'selected' : '';?>><?php echo get_phrase('term_1');?></option>
                    <option value="2" <?php echo ($filter_term == '2') ? 'selected' : '';?>><?php echo get_phrase('term_2');?></option>
                    <option value="3" <?php echo ($filter_term == '3') ? 'selected' : '';?>><?php echo get_phrase('term_3');?></option>
                </select>
            </div>
            <div class="flex-1 min-w-[100px]">
                <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo get_phrase('week');?></label>
                <select name="week_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value=""><?php echo get_phrase('all_weeks');?></option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i;?>" <?php echo ($filter_week == $i) ? 'selected' : '';?>><?php echo get_phrase('week') . ' ' . $i;?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                    <i class="entypo-search"></i> <?php echo get_phrase('filter');?>
                </button>
            </div>
        </form>
    </div>

    <!-- Lesson Notes by Subject -->
    <?php if (!empty($grouped_notes)): ?>
        <?php foreach ($grouped_notes as $subject_name => $notes): ?>
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="entypo-book text-blue-500"></i>
                <?php echo $subject_name; ?>
                <span class="text-sm font-normal text-gray-500">(<?php echo count($notes); ?> <?php echo get_phrase('notes');?>)</span>
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($notes as $note): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-2">
                        <h4 class="font-medium text-gray-900"><?php echo $note->title; ?></h4>
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded"><?php echo get_phrase('approved');?></span>
                    </div>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><i class="entypo-calendar text-gray-400"></i> <?php echo date('d M, Y', strtotime($note->lesson_date)); ?></p>
                        <p><i class="entypo-clock text-gray-400"></i> <?php echo get_phrase('week') . ' ' . $note->week_number; ?> • <?php echo get_phrase('term') . ' ' . $note->term; ?></p>
                    </div>
                    <?php if ($note->file_path): ?>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <a href="<?php echo base_url($note->file_path); ?>" target="_blank" class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1">
                            <i class="entypo-download"></i> <?php echo get_phrase('download_material');?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a href="<?php echo site_url('student/lesson_note_view/' . $note->lesson_note_id); ?>" class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1">
                            <i class="entypo-eye"></i> <?php echo get_phrase('view_details');?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center py-12">
            <i class="entypo-documents text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-700 mb-2"><?php echo get_phrase('no_lesson_notes_found');?></h3>
            <p class="text-gray-500"><?php echo get_phrase('your_teachers_have_not_published_any_lesson_notes_yet');?></p>
        </div>
    <?php endif; ?>
</div>
