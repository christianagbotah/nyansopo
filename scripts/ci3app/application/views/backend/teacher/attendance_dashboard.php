<?php
$teacher_id = $this->session->userdata('teacher_id');
$running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
$running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;

// Get teacher's assigned classes
$this->db->select('c.*, s.section_id, s.name as section_name');
$this->db->from('teacher_class_assignment tca');
$this->db->join('class c', 'c.class_id = tca.class_id');
$this->db->join('section s', 's.class_id = c.class_id');
$this->db->where('tca.teacher_id', $teacher_id);
$this->db->where('tca.year', $running_year);
$this->db->where('tca.term', $running_term);
$this->db->group_by('c.class_id, s.section_id');
$classes = $this->db->get()->result_array();

// Get today's stats
$today = strtotime(date('Y-m-d'));
$total_present = 0;
$total_absent = 0;
$total_students = 0;

foreach($classes as $class) {
    $students = $this->db->where(['class_id' => $class['class_id'], 'section_id' => $class['section_id'], 'year' => $running_year, 'term' => $running_term, 'mute' => '0'])->count_all_results('enroll');
    $present = $this->db->where(['class_id' => $class['class_id'], 'timestamp' => $today, 'status' => 1])->count_all_results('attendance');
    $absent = $this->db->where(['class_id' => $class['class_id'], 'timestamp' => $today, 'status' => 2])->count_all_results('attendance');
    
    $total_students += $students;
    $total_present += $present;
    $total_absent += $absent;
}
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                <i class="fas fa-calendar-check mr-3"></i>Attendance Management
            </h1>
            <p class="text-gray-600"><?php echo date('l, F d, Y'); ?></p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Total Students</p>
                        <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $total_students; ?></h3>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-xl">
                        <i class="fas fa-users text-blue-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Present Today</p>
                        <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $total_present; ?></h3>
                        <p class="text-xs text-green-600 mt-1"><?php echo $total_students > 0 ? round(($total_present/$total_students)*100, 1) : 0; ?>%</p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-xl">
                        <i class="fas fa-check-circle text-green-600 text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Absent Today</p>
                        <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $total_absent; ?></h3>
                        <p class="text-xs text-red-600 mt-1"><?php echo $total_students > 0 ? round(($total_absent/$total_students)*100, 1) : 0; ?>%</p>
                    </div>
                    <div class="bg-red-100 p-4 rounded-xl">
                        <i class="fas fa-times-circle text-red-600 text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <a href="<?php echo site_url('teacher/manage_attendance'); ?>" class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white hover:shadow-xl transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">Mark Attendance</h3>
                        <p class="text-blue-100 text-sm">Take attendance for your classes</p>
                    </div>
                    <i class="fas fa-calendar-check text-5xl opacity-20"></i>
                </div>
            </a>
            
            <a href="<?php echo site_url('teacher/manage_mark'); ?>" class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white hover:shadow-xl transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">Report Card</h3>
                        <p class="text-purple-100 text-sm">Manage student grades & reports</p>
                    </div>
                    <i class="fas fa-file-alt text-5xl opacity-20"></i>
                </div>
            </a>
        </div>

        <!-- My Classes -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-chalkboard-teacher text-purple-600 mr-3"></i>My Classes
            </h2>
            
            <?php if (empty($classes)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No classes assigned</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($classes as $class): 
                    $students_count = $this->db->where(['class_id' => $class['class_id'], 'section_id' => $class['section_id'], 'year' => $running_year, 'term' => $running_term, 'mute' => '0'])->count_all_results('enroll');
                    $present_count = $this->db->where(['class_id' => $class['class_id'], 'timestamp' => $today, 'status' => 1])->count_all_results('attendance');
                ?>
                <a href="<?php echo site_url('admin/manage_attendance_view/' . $class['class_id'] . '/' . $class['section_id'] . '/' . $today); ?>" 
                   class="block bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200 hover:border-blue-400 hover:shadow-lg transition-all">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                        <?php echo $class['name'] . ' ' . $class['name_numeric'] . ' ' . $class['section_name']; ?>
                    </h3>
                    <div class="flex items-center justify-between text-sm text-gray-600 mb-3">
                        <span><i class="fas fa-users mr-2"></i><?php echo $students_count; ?> Students</span>
                        <span><i class="fas fa-check-circle text-green-600 mr-2"></i><?php echo $present_count; ?> Present</span>
                    </div>
                    <div class="mt-4 text-blue-600 font-semibold">
                        Mark Attendance <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .min-h-screen { padding: 1rem !important; }
    .p-6 { padding: 1.5rem !important; }
    .text-4xl { font-size: 1.5rem !important; }
    .text-3xl { font-size: 1.25rem !important; }
}
</style>
