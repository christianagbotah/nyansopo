<?php
$running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
$running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
$teacher_id = $this->session->userdata('teacher_id');

// Get teacher's classes
$teacher_classes = $this->db->get_where('class', ['teacher_id' => $teacher_id])->result_array();
$class_ids = array_column($teacher_classes, 'class_id');

// Stats
$total_students = 0;
$present_today = 0;
$absent_today = 0;
$total_collected_today = 0;

if (!empty($class_ids)) {
    $this->db->where_in('class_id', $class_ids);
    $this->db->where('year', $running_year);
    $this->db->where('term', $running_term);
    $this->db->where('mute', '0');
    $total_students = $this->db->count_all_results('enroll');
    
    $this->db->where_in('class_id', $class_ids);
    $this->db->where('timestamp', strtotime(date('Y-m-d')));
    $this->db->where_in('status', [1, 3]);
    $present_today = $this->db->count_all_results('attendance');
    
    $this->db->where_in('class_id', $class_ids);
    $this->db->where('timestamp', strtotime(date('Y-m-d')));
    $this->db->where('status', 2);
    $absent_today = $this->db->count_all_results('attendance');
    
    // Daily fees collected today
    if ($this->db->table_exists('daily_fee_transactions')) {
        $this->db->select('SUM(feeding_amount) as feeding, SUM(breakfast_amount) as breakfast, SUM(classes_amount) as classes, SUM(water_amount) as water, SUM(transport_amount) as transport');
        $this->db->where('DATE(FROM_UNIXTIME(payment_date))', date('Y-m-d'));
        $fees = $this->db->get('daily_fee_transactions')->row();
        if ($fees) {
            $total_collected_today = ($fees->feeding ?? 0) + ($fees->breakfast ?? 0) + ($fees->classes ?? 0) + ($fees->water ?? 0) + ($fees->transport ?? 0);
        }
    }
}

// Get all daily fees collected by this teacher
$my_total_collections = 0;
$breakdown = (object)['feeding' => 0, 'breakfast' => 0, 'classes' => 0, 'water' => 0, 'transport' => 0];
if ($this->db->table_exists('daily_fee_transactions')) {
    $this->db->select('SUM(feeding_amount) as feeding, SUM(breakfast_amount) as breakfast, SUM(classes_amount) as classes, SUM(water_amount) as water, SUM(transport_amount) as transport');
    $this->db->where('collected_by', $teacher_id);
    $my_collections = $this->db->get('daily_fee_transactions')->row();
    if ($my_collections) {
        $my_total_collections = ($my_collections->feeding ?? 0) + ($my_collections->breakfast ?? 0) + ($my_collections->classes ?? 0) + ($my_collections->water ?? 0) + ($my_collections->transport ?? 0);
        $breakdown = $my_collections;
    }
}

$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    Welcome Back, <?php echo $this->db->get_where('teacher', ['teacher_id' => $teacher_id])->row()->name; ?>
                </h1>
                <p class="text-gray-600 mt-2"><?php echo date('l, F d, Y'); ?></p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Academic Year</div>
                <div class="text-lg font-semibold text-gray-800"><?php echo $running_year; ?> - Term <?php echo $running_term; ?></div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Students -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Students</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $total_students; ?></h3>
                    <p class="text-xs text-gray-400 mt-1">In my classes</p>
                </div>
                <div class="bg-blue-100 p-4 rounded-xl">
                    <i class="fas fa-users text-blue-600 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Attendance Today -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Present Today</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $present_today; ?></h3>
                    <p class="text-xs text-green-600 mt-1"><?php echo $total_students > 0 ? round(($present_today/$total_students)*100, 1) : 0; ?>% attendance</p>
                </div>
                <div class="bg-green-100 p-4 rounded-xl">
                    <i class="fas fa-check-circle text-green-600 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Absent Today -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-red-500 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Absent Today</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $absent_today; ?></h3>
                    <p class="text-xs text-red-600 mt-1"><?php echo $total_students > 0 ? round(($absent_today/$total_students)*100, 1) : 0; ?>% absent</p>
                </div>
                <div class="bg-red-100 p-4 rounded-xl">
                    <i class="fas fa-times-circle text-red-600 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Collections Today -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Collected Today</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-2"><?php echo $currency; ?> <?php echo number_format($total_collected_today, 2); ?></h3>
                    <p class="text-xs text-purple-600 mt-1">Daily fees</p>
                </div>
                <div class="bg-purple-100 p-4 rounded-xl">
                    <i class="fas fa-money-bill-wave text-purple-600 text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-bolt text-yellow-500 mr-3"></i>
            Quick Actions
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <button onclick="navigation('<?php echo site_url('attendance/dashboard'); ?>')" class="group bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl p-6 text-center transition-all hover:scale-105 shadow-md">
                <i class="fas fa-calendar-check text-4xl mb-3"></i>
                <p class="font-semibold">Attendance</p>
            </button>
            <button onclick="navigation('<?php echo site_url('teacher/marks_manage'); ?>')" class="group bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl p-6 text-center transition-all hover:scale-105 shadow-md">
                <i class="fas fa-graduation-cap text-4xl mb-3"></i>
                <p class="font-semibold">Enter Marks</p>
            </button>
            <button onclick="navigation('<?php echo site_url('teacher/student_information'); ?>')" class="group bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-xl p-6 text-center transition-all hover:scale-105 shadow-md">
                <i class="fas fa-users text-4xl mb-3"></i>
                <p class="font-semibold">Students</p>
            </button>
            <button onclick="navigation('<?php echo site_url('teacher/class_routine_view'); ?>')" class="group bg-gradient-to-br from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-xl p-6 text-center transition-all hover:scale-105 shadow-md">
                <i class="fas fa-calendar text-4xl mb-3"></i>
                <p class="font-semibold">Timetable</p>
            </button>
            <button onclick="navigation('<?php echo site_url('teacher/message'); ?>')" class="group bg-gradient-to-br from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-xl p-6 text-center transition-all hover:scale-105 shadow-md">
                <i class="fas fa-envelope text-4xl mb-3"></i>
                <p class="font-semibold">Messages</p>
            </button>
            <button onclick="navigation('<?php echo site_url('teacher/study_material'); ?>')" class="group bg-gradient-to-br from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl p-6 text-center transition-all hover:scale-105 shadow-md">
                <i class="fas fa-book text-4xl mb-3"></i>
                <p class="font-semibold">Study Material</p>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- My Collections -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-wallet text-green-500 mr-3"></i>
                My Total Collections
            </h2>
            <div class="space-y-4">
                <?php
                $fees_breakdown = [
                    ['name' => 'Feeding', 'amount' => $breakdown->feeding ?? 0, 'color' => 'blue'],
                    ['name' => 'Breakfast', 'amount' => $breakdown->breakfast ?? 0, 'color' => 'green'],
                    ['name' => 'Classes', 'amount' => $breakdown->classes ?? 0, 'color' => 'purple'],
                    ['name' => 'Water', 'amount' => $breakdown->water ?? 0, 'color' => 'cyan'],
                    ['name' => 'Transport', 'amount' => $breakdown->transport ?? 0, 'color' => 'orange']
                ];
                
                foreach ($fees_breakdown as $fee):
                ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all">
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full bg-<?php echo $fee['color']; ?>-500 mr-3"></div>
                        <span class="font-medium text-gray-700"><?php echo $fee['name']; ?></span>
                    </div>
                    <span class="text-lg font-bold text-gray-800"><?php echo $currency; ?> <?php echo number_format($fee['amount'], 2); ?></span>
                </div>
                <?php endforeach; ?>
                <div class="border-t-2 border-gray-200 pt-4 mt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xl font-bold text-gray-800">Total</span>
                        <span class="text-2xl font-bold text-green-600"><?php echo $currency; ?> <?php echo number_format($my_total_collections, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Collections -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-history text-blue-500 mr-3"></i>
                    Recent Collections
                </h2>
                <?php if (!empty($recent)): ?>
                <a href="<?php echo site_url('teacher/daily_payment_report'); ?>" class="text-blue-600 hover:text-blue-800 font-semibold text-sm flex items-center">
                    View All <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <?php endif; ?>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php
                $recent = [];
                if ($this->db->table_exists('daily_fee_transactions') && !empty($class_ids)) {
                    $this->db->select('t.id, t.student_id, t.payment_date, t.total_amount, s.name as student_name');
                    $this->db->from('daily_fee_transactions t');
                    $this->db->join('student s', 's.student_id = t.student_id', 'left');
                    $this->db->join('enroll e', 'e.student_id = t.student_id AND e.year = "' . $running_year . '" AND e.term = ' . $running_term, 'inner');
                    $this->db->where_in('e.class_id', $class_ids);
                    $this->db->order_by('t.payment_date', 'DESC');
                    $this->db->limit(10);
                    $recent = $this->db->get()->result_array();
                }
                
                if (empty($recent)):
                ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-inbox text-5xl mb-3"></i>
                    <p>No recent collections</p>
                </div>
                <?php else: foreach ($recent as $r): ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-blue-50 transition-all">
                    <div>
                        <p class="font-semibold text-gray-800"><?php echo $r['student_name']; ?></p>
                        <p class="text-sm text-gray-500"><?php echo date('M d, Y', $r['payment_date']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-green-600"><?php echo $currency; ?> <?php echo number_format($r['total_amount'], 2); ?></p>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- My Classes -->
    <?php if (!empty($teacher_classes)): ?>
    <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-chalkboard-teacher text-purple-500 mr-3"></i>
            My Classes
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($teacher_classes as $class): 
                $section = $this->db->get_where('section', ['class_id' => $class['class_id']])->row();
                $students_count = $this->db->where(['class_id' => $class['class_id'], 'year' => $running_year, 'term' => $running_term, 'mute' => '0'])->count_all_results('enroll');
            ?>
            <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200 hover:border-blue-400 transition-all cursor-pointer" onclick="navigation('<?php echo site_url('teacher/student_information/'.$class['class_id']); ?>')">
                <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo $class['name'] . ' ' . $class['name_numeric'] . ($section ? ' ' . $section->name : ''); ?></h3>
                <p class="text-gray-600"><i class="fas fa-users mr-2"></i><?php echo $students_count; ?> Students</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function navigation(url) {
    window.location.href = url;
}
</script>

<style>
@import url('<?php echo base_url(); ?>assets/cdn/fonts/inter.css');
body { font-family: 'Inter', sans-serif; }

/* Mobile Responsive Enhancements */
@media (max-width: 768px) {
    .min-h-screen { padding: 1rem !important; }
    h1 { font-size: 1.5rem !important; }
    .text-4xl { font-size: 1.5rem !important; }
    .text-3xl { font-size: 1.25rem !important; }
    .text-2xl { font-size: 1.125rem !important; }
    .text-xl { font-size: 1rem !important; }
    .grid { gap: 1rem !important; }
    .p-6 { padding: 1rem !important; }
    .rounded-2xl { border-radius: 1rem !important; }
    .shadow-lg { box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important; }
}

@media (max-width: 480px) {
    h1 { font-size: 1.25rem !important; }
    .text-4xl { font-size: 1.25rem !important; }
    .text-3xl { font-size: 1.125rem !important; }
    .text-2xl { font-size: 1rem !important; }
    .p-6 { padding: 0.75rem !important; }
    .gap-6 { gap: 0.75rem !important; }
}
</style>
