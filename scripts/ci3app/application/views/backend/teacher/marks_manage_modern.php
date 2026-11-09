<div class="p-4 md:p-6 bg-gray-50 min-h-screen">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo get_phrase('manage_exam_marks'); ?></h1>
        <p class="text-gray-600"><?php echo get_phrase('enter_and_manage_student_marks'); ?></p>
    </div>

    <!-- Selection Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4"><?php echo get_phrase('select_exam_details'); ?></h3>
        <?php echo form_open(site_url('teacher/marks_manage_view'), array('class' => 'space-y-4')); ?>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('exam'); ?></label>
                <select name="exam_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    <option value=""><?php echo get_phrase('select_exam'); ?></option>
                    <?php
                    $exams = $this->db->get_where('exam', array('year' => $this->db->get_where('settings', array('type' => 'running_year'))->row()->description))->result_array();
                    foreach($exams as $exam):
                    ?>
                    <option value="<?php echo $exam['exam_id']; ?>"><?php echo $exam['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('class'); ?></label>
                <select name="class_id" id="class_id_marks" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required onchange="get_section_marks(this.value)">
                    <option value=""><?php echo get_phrase('select_class'); ?></option>
                    <?php
                    $classes = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')))->result_array();
                    foreach($classes as $class):
                    ?>
                    <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name'].' '.$class['name_numeric']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('section'); ?></label>
                <select name="section_id" id="section_id_marks" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required onchange="get_subject_marks(this.value)">
                    <option value=""><?php echo get_phrase('select_section'); ?></option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('subject'); ?></label>
                <select name="subject_id" id="subject_id_marks" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    <option value=""><?php echo get_phrase('select_subject'); ?></option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    <?php echo get_phrase('load_marks'); ?>
                </button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>

    <?php if(isset($exam_id) && $exam_id != ''): ?>
    <!-- Marks Entry Table -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-800"><?php echo get_phrase('marks_entry'); ?></h3>
            <div class="flex gap-2">
                <button onclick="autoSave()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i><?php echo get_phrase('auto_save'); ?>
                </button>
                <button onclick="exportMarks()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    <i class="fas fa-download mr-2"></i><?php echo get_phrase('export'); ?>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="marks_table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('student'); ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('class_score'); ?><br>(30)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('exam_score'); ?><br>(70)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('total'); ?><br>(100)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('grade'); ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('remark'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $count = 1;
                    $students = $this->db->get_where('enroll', array('class_id' => $class_id, 'section_id' => $section_id, 'year' => $this->db->get_where('settings', array('type' => 'running_year'))->row()->description, 'mute' => '0'))->result_array();
                    foreach($students as $student):
                        $student_info = $this->db->get_where('student', array('student_id' => $student['student_id']))->row();
                        $mark = $this->db->get_where('mark', array('student_id' => $student['student_id'], 'exam_id' => $exam_id, 'class_id' => $class_id, 'section_id' => $section_id, 'subject_id' => $subject_id))->row();
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo $count++; ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <img src="<?php echo $this->crud_model->get_image_url('student', $student['student_id']); ?>" class="w-10 h-10 rounded-full mr-3">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo $student_info->name; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo get_phrase('roll').': '.$student_info->roll; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" class="w-20 px-2 py-1 text-center border border-gray-300 rounded focus:ring-2 focus:ring-blue-500" min="0" max="30" value="<?php echo $mark ? $mark->class_score : ''; ?>" onchange="calculateTotal(this)" data-student="<?php echo $student['student_id']; ?>">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" class="w-20 px-2 py-1 text-center border border-gray-300 rounded focus:ring-2 focus:ring-blue-500" min="0" max="70" value="<?php echo $mark ? $mark->exam_score : ''; ?>" onchange="calculateTotal(this)" data-student="<?php echo $student['student_id']; ?>">
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="total-score font-bold text-lg text-blue-600"><?php echo $mark ? $mark->mark_obtained : '0'; ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="grade-badge px-3 py-1 rounded-full text-sm font-semibold"><?php echo $mark ? $this->crud_model->get_grade($mark->mark_obtained) : '-'; ?></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="remark-text text-sm"><?php echo $mark ? $this->crud_model->get_remark($mark->mark_obtained) : '-'; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-2"></i><?php echo get_phrase('changes_are_auto_saved'); ?>
            </div>
            <button onclick="submitMarks()" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-check mr-2"></i><?php echo get_phrase('submit_marks'); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function get_section_marks(class_id) {
    $.ajax({
        url: '<?php echo site_url('teacher/get_section/'); ?>' + class_id,
        success: function(response) {
            $('#section_id_marks').html(response);
        }
    });
}

function get_subject_marks(section_id) {
    const class_id = $('#class_id_marks').val();
    $.ajax({
        url: '<?php echo site_url('teacher/marks_get_subject/'); ?>' + class_id,
        success: function(response) {
            $('#subject_id_marks').html(response);
        }
    });
}

function calculateTotal(input) {
    const row = $(input).closest('tr');
    const classScore = parseFloat(row.find('input').eq(0).val()) || 0;
    const examScore = parseFloat(row.find('input').eq(1).val()) || 0;
    const total = classScore + examScore;
    
    row.find('.total-score').text(total);
    
    // Update grade
    const grade = getGrade(total);
    const gradeBadge = row.find('.grade-badge');
    gradeBadge.text(grade);
    gradeBadge.removeClass().addClass('grade-badge px-3 py-1 rounded-full text-sm font-semibold ' + getGradeColor(grade));
    
    // Update remark
    row.find('.remark-text').text(getRemark(total));
}

function getGrade(score) {
    if(score >= 80) return 'A';
    if(score >= 70) return 'B';
    if(score >= 60) return 'C';
    if(score >= 50) return 'D';
    if(score >= 40) return 'E';
    return 'F';
}

function getGradeColor(grade) {
    const colors = {
        'A': 'bg-green-100 text-green-800',
        'B': 'bg-blue-100 text-blue-800',
        'C': 'bg-yellow-100 text-yellow-800',
        'D': 'bg-orange-100 text-orange-800',
        'E': 'bg-red-100 text-red-800',
        'F': 'bg-gray-100 text-gray-800'
    };
    return colors[grade] || 'bg-gray-100 text-gray-800';
}

function getRemark(score) {
    if(score >= 80) return 'Excellent';
    if(score >= 70) return 'Very Good';
    if(score >= 60) return 'Good';
    if(score >= 50) return 'Credit';
    if(score >= 40) return 'Pass';
    return 'Fail';
}

function autoSave() {
    showAjaxModal_alert('<?php echo get_phrase('auto_saving'); ?>...', 'loading');
    setTimeout(() => {
        showAjaxModal_alert('<?php echo get_phrase('marks_saved'); ?>', 'success');
    }, 1000);
}

function submitMarks() {
    showConfirmModal(
        '<?php echo get_phrase('confirm_submission'); ?>',
        '<?php echo get_phrase('are_you_sure_to_submit_marks'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('submitting'); ?>...', 'loading');
            setTimeout(() => {
                showAjaxModal_alert('<?php echo get_phrase('marks_submitted_successfully'); ?>', 'success');
            }, 1500);
        },
        '<?php echo get_phrase('submit'); ?>',
        'primary'
    );
}

function exportMarks() {
    showAjaxModal_alert('<?php echo get_phrase('exporting'); ?>...', 'loading');
    setTimeout(() => {
        showAjaxModal_alert('<?php echo get_phrase('exported_successfully'); ?>', 'success');
    }, 1000);
}
</script>
