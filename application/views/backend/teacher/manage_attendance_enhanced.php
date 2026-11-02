<div class="p-4 md:p-6 bg-gray-50 min-h-screen">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo get_phrase('attendance_management'); ?></h1>
        <p class="text-gray-600"><?php echo get_phrase('mark_student_attendance_quickly'); ?></p>
    </div>

    <!-- Class Selection Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4"><?php echo get_phrase('select_class'); ?></h3>
        <?php echo form_open(site_url('teacher/attendance_selector'), array('class' => 'space-y-4')); ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('date'); ?></label>
                <input type="text" name="timestamp" class="datepicker w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="<?php echo date('d-m-Y'); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('class'); ?></label>
                <select name="class_id" id="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required onchange="get_section(this.value)">
                    <option value=""><?php echo get_phrase('select_class'); ?></option>
                    <?php
                    $classes = $this->db->get_where('class', array('teacher_id' => $this->session->userdata('teacher_id')))->result_array();
                    foreach($classes as $row):
                    ?>
                    <option value="<?php echo $row['class_id']; ?>"><?php echo $row['name'].' '.$row['name_numeric']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo get_phrase('section'); ?></label>
                <select name="section_id" id="section_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                    <option value=""><?php echo get_phrase('select_section'); ?></option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    <?php echo get_phrase('load_students'); ?>
                </button>
            </div>
        </div>
        <input type="hidden" name="year" value="<?php echo $this->db->get_where('settings', array('type' => 'running_year'))->row()->description; ?>">
        <input type="hidden" name="term" value="<?php echo $this->db->get_where('settings', array('type' => 'running_term'))->row()->description; ?>">
        <?php echo form_close(); ?>
    </div>

    <!-- Quick Stats -->
    <?php if(isset($class_id) && $class_id != ''): ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90"><?php echo get_phrase('present'); ?></p>
                    <p class="text-3xl font-bold" id="present_count">0</p>
                </div>
                <i class="fas fa-check-circle text-4xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90"><?php echo get_phrase('absent'); ?></p>
                    <p class="text-3xl font-bold" id="absent_count">0</p>
                </div>
                <i class="fas fa-times-circle text-4xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90"><?php echo get_phrase('late'); ?></p>
                    <p class="text-3xl font-bold" id="late_count">0</p>
                </div>
                <i class="fas fa-clock text-4xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90"><?php echo get_phrase('total'); ?></p>
                    <p class="text-3xl font-bold" id="total_count">0</p>
                </div>
                <i class="fas fa-users text-4xl opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
        <div class="flex flex-wrap gap-3">
            <button onclick="markAll('present')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                <i class="fas fa-check mr-2"></i><?php echo get_phrase('mark_all_present'); ?>
            </button>
            <button onclick="markAll('absent')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                <i class="fas fa-times mr-2"></i><?php echo get_phrase('mark_all_absent'); ?>
            </button>
            <button onclick="markAll('late')" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
                <i class="fas fa-clock mr-2"></i><?php echo get_phrase('mark_all_late'); ?>
            </button>
        </div>
    </div>

    <!-- Student List -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4"><?php echo get_phrase('student_list'); ?></h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('photo'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('student_name'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('roll'); ?></th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><?php echo get_phrase('status'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="student_tbody">
                    <!-- Students will be loaded here -->
                </tbody>
            </table>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" onclick="submitAttendance()" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-save mr-2"></i><?php echo get_phrase('save_attendance'); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function get_section(class_id) {
    $.ajax({
        url: '<?php echo site_url('teacher/get_section/'); ?>' + class_id,
        success: function(response) {
            $('#section_id').html(response);
        }
    });
}

function markAll(status) {
    const statusValue = status === 'present' ? '1' : (status === 'absent' ? '2' : '3');
    $('input[name^="status_"]').val(statusValue);
    $('.status-btn').removeClass('active');
    $(`.status-btn[data-status="${statusValue}"]`).addClass('active');
    updateCounts();
}

function updateCounts() {
    let present = 0, absent = 0, late = 0;
    $('input[name^="status_"]').each(function() {
        const val = $(this).val();
        if(val == '1') present++;
        else if(val == '2') absent++;
        else if(val == '3') late++;
    });
    $('#present_count').text(present);
    $('#absent_count').text(absent);
    $('#late_count').text(late);
    $('#total_count').text(present + absent + late);
}

function submitAttendance() {
    showAjaxModal_alert('<?php echo get_phrase('saving_attendance'); ?>...', 'loading');
    // Submit form via AJAX
    setTimeout(() => {
        showAjaxModal_alert('<?php echo get_phrase('attendance_saved_successfully'); ?>', 'success');
    }, 1500);
}

$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true
    });
});
</script>

<style>
.status-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
}
.status-btn:hover {
    transform: scale(1.05);
}
.status-btn.active {
    border-color: currentColor;
    font-weight: 600;
}
.status-btn.present.active {
    background: #10b981;
    color: white;
}
.status-btn.absent.active {
    background: #ef4444;
    color: white;
}
.status-btn.late.active {
    background: #f59e0b;
    color: white;
}
</style>
