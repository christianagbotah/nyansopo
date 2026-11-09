<link rel="stylesheet" href="<?php echo base_url('assets/css/lesson_notes.css'); ?>">
<div class="ln-container">
    <div class="ln-card">
        <div class="ln-card-header">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                <div>
                    <h2 class="ln-title"><?php echo get_phrase('lesson_notes');?></h2>
                    <p class="ln-subtitle"><?php echo get_phrase('create_and_manage_ges_compliant_lesson_notes');?></p>
                </div>
                <div class="ln-btn-group">
                    <a href="<?php echo site_url('teacher/lesson_note_copy_select');?>" class="ln-btn ln-btn-secondary">
                        <i class="entypo-copy"></i> <?php echo get_phrase('copy_from_previous'); ?>
                    </a>
                    <a href="<?php echo site_url('teacher/lesson_note_create');?>" class="ln-btn ln-btn-primary">
                        <i class="entypo-plus"></i> <?php echo get_phrase('create_lesson_note'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="ln-card-body border-b border-gray-200">
            <?php echo form_open('teacher/lesson_notes', array('method' => 'GET')); ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div class="ln-form-group mb-0">
                        <label><?php echo get_phrase('status');?></label>
                        <select name="status" class="ln-form-control">
                            <option value=""><?php echo get_phrase('all_statuses');?></option>
                            <option value="draft" <?php echo ($this->input->get('status') == 'draft') ? 'selected' : '';?>><?php echo get_phrase('draft');?></option>
                            <option value="pending" <?php echo ($this->input->get('status') == 'pending') ? 'selected' : '';?>><?php echo get_phrase('pending');?></option>
                            <option value="hod_reviewed" <?php echo ($this->input->get('status') == 'hod_reviewed') ? 'selected' : '';?>><?php echo get_phrase('hod_reviewed');?></option>
                            <option value="revision_requested" <?php echo ($this->input->get('status') == 'revision_requested') ? 'selected' : '';?>><?php echo get_phrase('revision_requested');?></option>
                            <option value="approved" <?php echo ($this->input->get('status') == 'approved') ? 'selected' : '';?>><?php echo get_phrase('approved');?></option>
                            <option value="declined" <?php echo ($this->input->get('status') == 'declined') ? 'selected' : '';?>><?php echo get_phrase('declined');?></option>
                        </select>
                    </div>
                    <div class="ln-form-group mb-0">
                        <label><?php echo get_phrase('subject');?></label>
                        <select name="subject_id" class="ln-form-control">
                            <option value=""><?php echo get_phrase('all_subjects');?></option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject->subject_id;?>" <?php echo ($this->input->get('subject_id') == $subject->subject_id) ? 'selected' : '';?>><?php echo $subject->name;?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ln-form-group mb-0">
                        <label><?php echo get_phrase('class');?></label>
                        <select name="class_id" class="ln-form-control">
                            <option value=""><?php echo get_phrase('all_classes');?></option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class->class_id;?>" <?php echo ($this->input->get('class_id') == $class->class_id) ? 'selected' : '';?>><?php echo $class->name;?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ln-form-group mb-0">
                        <label><?php echo get_phrase('term');?></label>
                        <select name="term" class="ln-form-control">
                            <option value=""><?php echo get_phrase('all_terms');?></option>
                            <option value="1" <?php echo ($this->input->get('term') == '1') ? 'selected' : '';?>><?php echo get_phrase('term_1');?></option>
                            <option value="2" <?php echo ($this->input->get('term') == '2') ? 'selected' : '';?>><?php echo get_phrase('term_2');?></option>
                            <option value="3" <?php echo ($this->input->get('term') == '3') ? 'selected' : '';?>><?php echo get_phrase('term_3');?></option>
                        </select>
                    </div>
                    <div class="ln-form-group mb-0">
                        <label><?php echo get_phrase('week');?></label>
                        <select name="week_number" class="ln-form-control">
                            <option value=""><?php echo get_phrase('all_weeks');?></option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i;?>" <?php echo ($this->input->get('week_number') == $i) ? 'selected' : '';?>><?php echo get_phrase('week') . ' ' . $i;?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="ln-form-group mb-0 flex items-end">
                        <button type="submit" class="ln-btn ln-btn-primary w-full">
                            <i class="entypo-search"></i> <?php echo get_phrase('filter');?>
                        </button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>

        <!-- Status Summary Cards -->
        <div class="ln-card-body border-b border-gray-200">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-gray-50 rounded-xl p-5 text-center border border-gray-100">
                    <div class="text-3xl font-bold text-gray-800 mb-1"><?php echo $status_counts['draft'] + $status_counts['pending'] + $status_counts['hod_reviewed'] + $status_counts['approved'] + $status_counts['declined'] + $status_counts['revision_requested'];?></div>
                    <div class="text-sm font-medium text-gray-500"><?php echo get_phrase('total');?></div>
                </div>
                <div class="bg-amber-50 rounded-xl p-5 text-center border border-amber-100">
                    <div class="text-3xl font-bold text-amber-600 mb-1"><?php echo $status_counts['pending'];?></div>
                    <div class="text-sm font-medium text-amber-600"><?php echo get_phrase('pending');?></div>
                </div>
                <div class="bg-blue-50 rounded-xl p-5 text-center border border-blue-100">
                    <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo $status_counts['hod_reviewed'];?></div>
                    <div class="text-sm font-medium text-blue-600"><?php echo get_phrase('hod_reviewed');?></div>
                </div>
                <div class="bg-green-50 rounded-xl p-5 text-center border border-green-100">
                    <div class="text-3xl font-bold text-green-600 mb-1"><?php echo $status_counts['approved'];?></div>
                    <div class="text-sm font-medium text-green-600"><?php echo get_phrase('approved');?></div>
                </div>
                <div class="bg-red-50 rounded-xl p-5 text-center border border-red-100">
                    <div class="text-3xl font-bold text-red-600 mb-1"><?php echo $status_counts['declined'];?></div>
                    <div class="text-sm font-medium text-red-600"><?php echo get_phrase('declined');?></div>
                </div>
                <div class="bg-purple-50 rounded-xl p-5 text-center border border-purple-100">
                    <div class="text-3xl font-bold text-purple-600 mb-1"><?php echo $status_counts['revision_requested'];?></div>
                    <div class="text-sm font-medium text-purple-600"><?php echo get_phrase('revision_requested');?></div>
                </div>
            </div>
        </div>

        <!-- Lesson Notes Table -->
        <div class="ln-card-body p-0">
            <div class="ln-table-container border-0 rounded-none">
                <table class="ln-table" id="lesson-notes-table">
                    <thead>
                        <tr>
                            <th class="w-32"><?php echo get_phrase('created');?></th>
                            <th class="w-28 text-center"><?php echo get_phrase('week_term');?></th>
                            <th><?php echo get_phrase('title');?></th>
                            <th class="w-36"><?php echo get_phrase('class');?></th>
                            <th class="w-36"><?php echo get_phrase('subject');?></th>
                            <th class="w-40"><?php echo get_phrase('strand');?></th>
                            <th class="w-32"><?php echo get_phrase('status');?></th>
                            <th class="w-40"><?php echo get_phrase('actions');?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lesson_notes)): ?>
                            <?php foreach ($lesson_notes as $row): ?>
                                <?php 
                                $status_config = array(
                                    'draft' => array('class' => 'ln-badge-draft'),
                                    'pending' => array('class' => 'ln-badge-pending'),
                                    'hod_reviewed' => array('class' => 'ln-badge-hod_reviewed'),
                                    'approved' => array('class' => 'ln-badge-approved'),
                                    'declined' => array('class' => 'ln-badge-declined'),
                                    'revision_requested' => array('class' => 'ln-badge-revision_requested')
                                );
                                $status = isset($status_config[$row->status]) ? $status_config[$row->status] : $status_config['draft'];
                                ?>
                                <tr>
                                    <td>
                                        <span class="text-gray-600"><?php echo date('d M, Y', strtotime($row->created_at)); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="font-semibold text-gray-800"><?php echo get_phrase('week') . ' ' . $row->week_number;?></div>
                                        <div class="text-xs text-gray-400"><?php echo get_phrase('term') . ' ' . $row->term;?></div>
                                    </td>
                                    <td>
                                        <div class="font-medium text-gray-900"><?php echo $row->title;?></div>
                                        <?php if ($row->source_lesson_note_id): ?>
                                        <span class="inline-flex items-center gap-1 text-xs text-blue-500 mt-1">
                                            <i class="entypo-copy"></i> <?php echo get_phrase('copied');?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-gray-600"><?php echo $row->class_name;?></td>
                                    <td class="text-gray-600"><?php echo $row->subject_name;?></td>
                                    <td class="text-gray-600"><?php echo $row->strand_name ?: '-';?></td>
                                    <td>
                                        <span class="ln-badge <?php echo $status['class'];?>">
                                            <?php echo get_phrase($row->status);?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex gap-2 flex-wrap">
                                            <?php if ($row->status == 'approved'): ?>
                                                <a href="<?php echo site_url('teacher/lesson_note_print/' . $row->lesson_note_id);?>" target="_blank" class="ln-btn ln-btn-sm ln-btn-secondary">
                                                    <i class="entypo-print"></i> <?php echo get_phrase('print');?>
                                                </a>
                                            <?php elseif (in_array($row->status, array('draft', 'revision_requested'))): ?>
                                                <a href="<?php echo site_url('teacher/lesson_note_edit/' . $row->lesson_note_id);?>" class="ln-btn ln-btn-sm ln-btn-success">
                                                    <i class="entypo-pencil"></i> <?php echo get_phrase('edit');?>
                                                </a>
                                                <a href="#" onclick="event.preventDefault(); showCustomConfirm('<?php echo get_phrase('confirm_delete_lesson_note');?>', function() { window.location.href='<?php echo site_url('teacher/lesson_note_delete/' . $row->lesson_note_id);?>'; }); return false;" class="ln-btn ln-btn-sm ln-btn-danger">
                                                    <i class="entypo-trash"></i>
                                                </a>
                                            <?php elseif ($row->status == 'declined'): ?>
                                                <button onclick="showFeedback('<?php echo htmlspecialchars($row->feedback, ENT_QUOTES);?>');" class="ln-btn ln-btn-sm ln-btn-danger">
                                                    <i class="entypo-info"></i> <?php echo get_phrase('feedback');?>
                                                </button>
                                                <a href="<?php echo site_url('teacher/lesson_note_edit/' . $row->lesson_note_id);?>" class="ln-btn ln-btn-sm ln-btn-success">
                                                    <i class="entypo-pencil"></i> <?php echo get_phrase('edit');?>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo site_url('teacher/lesson_note_view/' . $row->lesson_note_id);?>" class="ln-btn ln-btn-sm ln-btn-secondary">
                                                    <i class="entypo-eye"></i> <?php echo get_phrase('view');?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-16">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center">
                                            <i class="entypo-documents text-4xl text-gray-300"></i>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-lg mb-2"><?php echo get_phrase('no_lesson_notes_found');?></p>
                                            <a href="<?php echo site_url('teacher/lesson_note_create');?>" class="ln-btn ln-btn-primary">
                                                <i class="entypo-plus"></i> <?php echo get_phrase('create_your_first_lesson_note');?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div id="feedback-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center" style="display: none;">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden">
        <div class="p-5 border-b border-gray-200 bg-red-50">
            <h3 class="text-lg font-semibold text-red-800 flex items-center gap-2">
                <i class="entypo-info-circled"></i> <?php echo get_phrase('decline_feedback');?>
            </h3>
        </div>
        <div class="p-6">
            <p id="feedback-text" class="text-gray-700 text-base leading-relaxed"></p>
        </div>
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <button onclick="closeFeedbackModal();" class="ln-btn ln-btn-secondary w-full">
                <?php echo get_phrase('close');?>
            </button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() { 
    $("#lesson-notes-table").dataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "<?php echo get_phrase('search');?>:",
            lengthMenu: "<?php echo get_phrase('show');?> _MENU_ <?php echo get_phrase('entries');?>",
            info: "<?php echo get_phrase('showing');?> _START_ <?php echo get_phrase('to');?> _END_ <?php echo get_phrase('of');?> _TOTAL_ <?php echo get_phrase('entries');?>",
            paginate: {
                first: "<?php echo get_phrase('first');?>",
                last: "<?php echo get_phrase('last');?>",
                next: "<?php echo get_phrase('next');?>",
                previous: "<?php echo get_phrase('previous');?>"
            }
        }
    }); 
});

function showFeedback(feedback) {
    $('#feedback-text').text(feedback);
    $('#feedback-modal').css('display', 'flex');
}

function closeFeedbackModal() {
    $('#feedback-modal').css('display', 'none');
}

// Close modal on outside click
$('#feedback-modal').on('click', function(e) {
    if (e.target === this) {
        closeFeedbackModal();
    }
});
</script>
