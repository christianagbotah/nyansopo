<?php
$currency = get_settings('currency');
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');

// Students Data
$this->db->where('enroll.year', $running_year)->where('enroll.term', $running_term)->where('enroll.mute', '0');
$total_students = $this->db->get('enroll')->num_rows();
$this->db->where('enroll.year', $running_year)->where('enroll.term', $running_term)->where('enroll.mute', '0');
$this->db->join('student', 'student.student_id = enroll.student_id');
$this->db->where('student.sex', 'male');
$male_students = $this->db->get('enroll')->num_rows();
$female_students = $total_students - $male_students;

// Teachers & Staff
$total_teachers = $this->db->get_where('teacher', ['active_status' => 1])->num_rows();
$total_classes = $this->db->get('class')->num_rows();

// Attendance Today
$today = strtotime(date('d-m-Y'));
$this->db->where('timestamp', $today)->where('status', '1');
$today_attendance = $this->db->get('attendance')->num_rows();
$attendance_rate = $total_students > 0 ? round(($today_attendance / $total_students) * 100, 1) : 0;

// Financial Data
$this->db->select_sum('amount')->where('year', $running_year)->where('payment_type', 'income');
$total_revenue = $this->db->get('payment')->row()->amount ?? 0;
$this->db->select_sum('amount')->where('year', $running_year)->where('payment_type', 'expense');
$total_expenses = $this->db->get('payment')->row()->amount ?? 0;
$this->db->select_sum('due')->where('year', $running_year)->where('due >', 0);
$total_outstanding = $this->db->get('invoice')->row()->due ?? 0;
$this->db->where('year', $running_year)->where('status', 'unpaid');
$pending_invoices = $this->db->get('invoice')->num_rows();

// Recent Activities
$this->db->order_by('payment_id', 'DESC')->limit(5);
$recent_payments = $this->db->get('payment')->result_array();
?>

<div class="p-4 sm:p-6 lg:p-8 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen mt-16 md:mt-20">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-3xl lg:text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-2">
                    <?php echo get_phrase('executive_dashboard'); ?>
                </h1>
                <p class="text-sm text-gray-600 flex items-center gap-2">
                    <i class="mdi mdi-calendar-clock"></i>
                    <?php echo date('l, F d, Y'); ?> • <?php echo date('h:i A'); ?>
                </p>
            </div>
            <div class="flex gap-3">
                <button onclick="location.reload()" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all shadow-sm">
                    <i class="mdi mdi-refresh mr-2"></i>
                    <span class="hidden sm:inline"><?php echo get_phrase('refresh'); ?></span>
                </button>
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/30">
                    <i class="mdi mdi-printer mr-2"></i>
                    <span class="hidden sm:inline"><?php echo get_phrase('export'); ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Students KPI -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white opacity-10"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                        <i class="mdi mdi-account-group text-3xl"></i>
                    </div>
                    <span class="text-xs font-semibold bg-white bg-opacity-20 px-3 py-1 rounded-full"><?php echo get_phrase('active'); ?></span>
                </div>
                <h3 class="text-4xl font-bold mb-1"><?php echo number_format($total_students); ?></h3>
                <p class="text-sm opacity-90 mb-3"><?php echo get_phrase('total_students'); ?></p>
                <div class="flex items-center gap-4 text-xs">
                    <span><i class="mdi mdi-gender-male mr-1"></i><?php echo $male_students; ?> <?php echo get_phrase('male'); ?></span>
                    <span><i class="mdi mdi-gender-female mr-1"></i><?php echo $female_students; ?> <?php echo get_phrase('female'); ?></span>
                </div>
            </div>
        </div>

        <!-- Attendance KPI -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white opacity-10"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                        <i class="mdi mdi-check-circle text-3xl"></i>
                    </div>
                    <span class="text-xs font-semibold bg-white bg-opacity-20 px-3 py-1 rounded-full"><?php echo $attendance_rate; ?>%</span>
                </div>
                <h3 class="text-4xl font-bold mb-1"><?php echo number_format($today_attendance); ?></h3>
                <p class="text-sm opacity-90 mb-3"><?php echo get_phrase('present_today'); ?></p>
                <div class="w-full bg-white bg-opacity-20 rounded-full h-2">
                    <div class="bg-white h-2 rounded-full" style="width: <?php echo $attendance_rate; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Revenue KPI -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white opacity-10"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                        <i class="mdi mdi-cash-multiple text-3xl"></i>
                    </div>
                    <span class="text-xs font-semibold bg-white bg-opacity-20 px-3 py-1 rounded-full"><i class="mdi mdi-trending-up"></i></span>
                </div>
                <h3 class="text-4xl font-bold mb-1"><?php echo $currency . number_format($total_revenue, 0); ?></h3>
                <p class="text-sm opacity-90 mb-3"><?php echo get_phrase('total_revenue'); ?></p>
                <p class="text-xs opacity-75"><?php echo get_phrase('expenses'); ?>: <?php echo $currency . number_format($total_expenses, 0); ?></p>
            </div>
        </div>

        <!-- Outstanding KPI -->
        <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white opacity-10"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-xl backdrop-blur-sm">
                        <i class="mdi mdi-alert-circle text-3xl"></i>
                    </div>
                    <span class="text-xs font-semibold bg-white bg-opacity-20 px-3 py-1 rounded-full"><?php echo $pending_invoices; ?> <?php echo get_phrase('pending'); ?></span>
                </div>
                <h3 class="text-4xl font-bold mb-1"><?php echo $currency . number_format($total_outstanding, 0); ?></h3>
                <p class="text-sm opacity-90 mb-3"><?php echo get_phrase('outstanding'); ?></p>
                <p class="text-xs opacity-75"><?php echo get_phrase('requires_attention'); ?></p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Left Column - Modules & Quick Stats -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Module Navigation -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900"><?php echo get_phrase('system_modules'); ?></h2>
                    <span class="text-sm text-gray-500"><?php echo get_phrase('quick_access'); ?></span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php
                    $modules = [
                        ['icon' => 'school', 'name' => 'academic', 'url' => 'admin/all_students', 'color' => 'blue'],
                        ['icon' => 'cash-multiple', 'name' => 'finance', 'url' => 'finance/dashboard', 'color' => 'green'],
                        ['icon' => 'calculator', 'name' => 'accounts', 'url' => 'accounts/dashboard', 'color' => 'purple'],
                        ['icon' => 'calendar-check', 'name' => 'attendance', 'url' => 'admin/manage_attendance', 'color' => 'orange'],
                        ['icon' => 'file-document-edit', 'name' => 'exams', 'url' => 'admin/marks_manage', 'color' => 'red'],
                        ['icon' => 'chart-bar', 'name' => 'reports', 'url' => 'admin/financial_reports', 'color' => 'indigo'],
                        ['icon' => 'bus-school', 'name' => 'transport', 'url' => 'admin/transport', 'color' => 'cyan'],
                        ['icon' => 'home-group', 'name' => 'boarding', 'url' => 'boarding/dashboard', 'color' => 'teal']
                    ];
                    foreach ($modules as $module):
                    ?>
                    <a href="<?php echo site_url($module['url']); ?>" class="group relative p-4 bg-gradient-to-br from-<?php echo $module['color']; ?>-50 to-<?php echo $module['color']; ?>-100 rounded-xl hover:shadow-lg transition-all duration-300 border border-<?php echo $module['color']; ?>-200">
                        <div class="flex flex-col items-center text-center">
                            <div class="p-3 bg-<?php echo $module['color']; ?>-500 text-white rounded-xl mb-3 group-hover:scale-110 transition-transform">
                                <i class="mdi mdi-<?php echo $module['icon']; ?> text-2xl"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-900"><?php echo get_phrase($module['name']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-600 mb-1"><?php echo get_phrase('classes'); ?></p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_classes; ?></p>
                </div>
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
                    <p class="text-sm text-gray-600 mb-1"><?php echo get_phrase('teachers'); ?></p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_teachers; ?></p>
                </div>
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-purple-500">
                    <p class="text-sm text-gray-600 mb-1"><?php echo get_phrase('avg_attendance'); ?></p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $attendance_rate; ?>%</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4 border-l-4 border-orange-500">
                    <p class="text-sm text-gray-600 mb-1"><?php echo get_phrase('collection_rate'); ?></p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_revenue > 0 ? round((($total_revenue - $total_outstanding) / $total_revenue) * 100) : 0; ?>%</p>
                </div>
            </div>
        </div>

        <!-- Right Column - Recent Activity & Quick Actions -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo get_phrase('quick_actions'); ?></h2>
                <div class="space-y-3">
                    <button onclick="location.href='<?php echo site_url('admin/student_add'); ?>'" class="w-full flex items-center gap-3 p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="mdi mdi-account-plus text-2xl"></i>
                        <span class="font-semibold"><?php echo get_phrase('add_student'); ?></span>
                    </button>
                    <button onclick="location.href='<?php echo site_url('admin/manage_attendance'); ?>'" class="w-full flex items-center gap-3 p-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg shadow-green-500/30">
                        <i class="mdi mdi-check-circle text-2xl"></i>
                        <span class="font-semibold"><?php echo get_phrase('mark_attendance'); ?></span>
                    </button>
                    <button onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/0'); ?>')" class="w-full flex items-center gap-3 p-4 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:from-purple-600 hover:to-pink-700 transition-all shadow-lg shadow-purple-500/30">
                        <i class="mdi mdi-cash-register text-2xl"></i>
                        <span class="font-semibold"><?php echo get_phrase('collect_payment'); ?></span>
                    </button>
                    <button onclick="location.href='<?php echo site_url('admin/marks_manage'); ?>'" class="w-full flex items-center gap-3 p-4 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl hover:from-orange-600 hover:to-red-700 transition-all shadow-lg shadow-orange-500/30">
                        <i class="mdi mdi-file-edit text-2xl"></i>
                        <span class="font-semibold"><?php echo get_phrase('enter_marks'); ?></span>
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo get_phrase('recent_activity'); ?></h2>
                <div class="space-y-4">
                    <?php if (!empty($recent_payments)): ?>
                        <?php foreach (array_slice($recent_payments, 0, 5) as $payment): ?>
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="mdi mdi-cash text-green-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate"><?php echo get_phrase('payment_received'); ?></p>
                                <p class="text-xs text-gray-600"><?php echo $currency . number_format($payment['amount'], 2); ?></p>
                                <p class="text-xs text-gray-400"><?php echo date('M d, Y', $payment['day_timestamp']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center py-4"><?php echo get_phrase('no_recent_activity'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status Footer -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-gray-600"><?php echo get_phrase('system_online'); ?></span>
                </div>
                <div class="text-sm text-gray-400">|</div>
                <span class="text-sm text-gray-600"><?php echo get_phrase('academic_year'); ?>: <?php echo explode('-', $running_year)[1]; ?></span>
                <div class="text-sm text-gray-400">|</div>
                <span class="text-sm text-gray-600"><?php echo get_phrase('term'); ?>: <?php echo $running_term; ?></span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <i class="mdi mdi-shield-check text-green-600"></i>
                <span><?php echo get_phrase('all_systems_operational'); ?></span>
            </div>
        </div>
    </div>
</div>
