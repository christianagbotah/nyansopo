<?php
$students_ids = explode('-', $student_id);
$login_type = $this->session->userdata('login_type');
$user_id = $this->session->userdata('login_user_id');

// Check collection mode setting
$collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
$show_fee_collection = ($collection_mode == 'classroom');

// Get user permissions
$can_mark_attendance = in_array($login_type, ['admin', 'teacher']);
$can_collect_fees = false;
$can_collect_feeding = false;
$can_collect_classes = false;
$can_collect_transport = false;
$admin_level = 0;

if ($login_type == 'admin') {
    $admin_level = $this->db->get_where('admin', ['admin_id' => $user_id])->row()->level ?? 0;
    $can_collect_fees = in_array($admin_level, [1, 2, 3]); // Super Admin, Admin, Accountant
    $can_collect_feeding = $can_collect_fees;
    $can_collect_classes = $can_collect_fees;
    $can_collect_transport = $can_collect_fees;
    
    // Accountant can only collect fees, not mark attendance
    if ($admin_level == 3) {
        $can_mark_attendance = false;
    }
} elseif ($login_type == 'teacher') {
    // Check fee collection permissions using new system
    $running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
    $running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
    $mode = $this->db->get_where('settings', ['type' => 'teacher_fee_collection_mode'])->row()->description ?? 'restricted';
    
    if ($mode == 'all_allowed') {
        $can_collect_fees = true;
        $can_collect_feeding = true;
        $can_collect_classes = true;
        $can_collect_transport = true;
    } elseif ($mode == 'selective') {
        $assignment = $this->db->get_where('fee_collection_assignments', [
            'teacher_id' => $user_id,
            'class_id' => $class_id,
            'year' => $running_year,
            'term' => $running_term
        ])->row();
        
        if ($assignment) {
            $can_collect_feeding = $assignment->can_collect_feeding == 1;
            $can_collect_classes = $assignment->can_collect_classes == 1;
            $can_collect_transport = $assignment->can_collect_transport == 1;
            $can_collect_fees = $can_collect_feeding || $can_collect_classes || $can_collect_transport;
        }
    }
    // If mode is 'restricted', all remain false
}

// Get class fees from daily_fee_rates
$year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$fee_rates = $this->db->get_where('daily_fee_rates', array('class_id' => $class_id, 'year' => $year, 'term' => $term))->row();
$feeding_fee = ($fee_rates && isset($fee_rates->feeding_rate)) ? $fee_rates->feeding_rate : 0;
$breakfast_fee = ($fee_rates && isset($fee_rates->breakfast_rate)) ? $fee_rates->breakfast_rate : 0;
$classes_fee = ($fee_rates && isset($fee_rates->classes_rate)) ? $fee_rates->classes_rate : 0;
$water_fee = ($fee_rates && isset($fee_rates->water_rate)) ? $fee_rates->water_rate : 0;
$breakfast_enabled = ($fee_rates && isset($fee_rates->breakfast_enabled)) ? $fee_rates->breakfast_enabled : 0;
$water_enabled = ($fee_rates && isset($fee_rates->water_enabled)) ? $fee_rates->water_enabled : 0;

// Check for existing payments today and calculate cumulative owing
$existing_payments = [];
$cumulative_owing = [];
$has_any_feeding = false;
$has_any_classes = false;
$has_any_transport = false;

foreach($students_ids as $sid) {
    $payment = $this->db->get_where('daily_fee_transactions', ['student_id' => $sid, 'payment_date' => $timestamp])->row();
    
    if ($payment) {
        $payment_data = [];
        if ($payment->feeding_amount > 0) {
            $payment_data['feeding'] = ['amount' => $payment->feeding_amount];
            $has_any_feeding = true;
        }
        if ($payment->classes_amount > 0) {
            $payment_data['classes'] = ['amount' => $payment->classes_amount];
            $has_any_classes = true;
        }
        if ($payment->transport_amount > 0) {
            $payment_data['transport'] = ['amount' => $payment->transport_amount];
            $has_any_transport = true;
        }
        if (!empty($payment_data)) {
            $existing_payments[$sid] = $payment_data;
        }
    }
}
    
    // Get wallet balances (arrears)
    $wallet = $this->db->get_where('daily_fee_wallet', ['student_id' => $sid])->row();
    
    $cumulative_owing[$sid] = [
        'feeding' => $wallet ? $wallet->feeding_arrears : 0,
        'breakfast' => $wallet ? $wallet->breakfast_arrears : 0,
        'classes' => $wallet ? $wallet->classes_arrears : 0,
        'water' => $wallet ? $wallet->water_arrears : 0,
        'transport' => $wallet ? $wallet->transport_arrears : 0
    ];

?>

<div class="max-w-[1600px] mx-auto px-4 py-6">
    <?php if (!empty($existing_payments)): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
        <p class="text-yellow-700 font-semibold">
            <i class="fa fa-exclamation-triangle"></i> Warning: Some students already have fee payments recorded for this date.
            <br><small>Collecting fees again will override existing records.</small>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-700 font-semibold">
            <i class="fa fa-info-circle"></i> Attendance & Fee Collection
            <br><small>
                <?php if ($can_mark_attendance && $can_collect_fees): ?>
                    You can mark attendance and collect fees.
                <?php elseif ($can_mark_attendance): ?>
                    You can mark attendance only. Fee collection is restricted.
                <?php elseif ($can_collect_fees): ?>
                    You can collect fees only.
                <?php endif; ?>
            </small>
        </p>
    </div>

    <?php echo form_open(site_url('admin/attendance_selector/'), array('id' => 'att_selector_form')); ?>
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Class</label>
                <select name="class_id" id="class_selection" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg h-[46px]" onchange="select_section(this.value); select_students(this.value)">
                    <option value="">Select Class</option>
                    <?php getFullClassList('', $class_id); ?>
                </select>
            </div>
            <div id="section_holder">
                <label class="block text-sm font-bold text-gray-700 mb-2">Section</label>
                <select name="section_id" id="section_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg h-[46px]">
                    <?php
                    $sections = $this->db->get_where('section', array('class_id' => $class_id))->result_array();
                    foreach ($sections as $row):
                    ?>
                        <option value="<?php echo $row['section_id']; ?>" <?php if ($section_id == $row['section_id']) echo 'selected'; ?>>
                            <?php echo $row['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Date</label>
                <input type="text" name="timestamp" class="datepicker w-full px-4 py-3 border-2 border-gray-300 rounded-lg h-[46px]" data-end-date="<?= date('d-m-Y');?>"  data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y', $timestamp); ?>">
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-lg h-[46px]">
                    Manage Attendance
                </button>
            </div>
        </div>
        <input type="hidden" name="year" value="<?php echo $running_year; ?>">
        <input type="hidden" name="term" value="<?php echo $running_term; ?>">
    </div>
    <?php echo form_close(); ?>

    <div id="students_holder"></div>

    <?php echo form_open(site_url('admin/attendance_update/'.$class_id.'/'.$section_id.'/'.$timestamp), array('id' => 'attendance_form')); ?>
    
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6 sticky top-0 z-20">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <label class="text-sm font-bold text-gray-700">Filter Students:</label>
                <input type="text" id="student_filter" class="px-4 py-2 border-2 border-gray-300 rounded-lg" placeholder="Search by name or code...">
            </div>
            <div class="flex gap-2">
                <button type="button" id="select_all_btn" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-sm">
                    <i class="fa fa-check-double"></i> Select All Present
                </button>
                <button type="button" id="deselect_all_btn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm">
                    <i class="fa fa-times"></i> Mark All Absent
                </button>
            </div>
        </div>
    </div>
    
    <?php if ($show_fee_collection && $can_collect_fees): ?>
    <style>
    .toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 28px; }
    .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; }
    input:checked + .toggle-slider { background-color: #10b981; }
    input:checked + .toggle-slider:before { transform: translateX(24px); }
    .toggle-slider:hover { background-color: #94a3b8; }
    input:checked + .toggle-slider:hover { background-color: #059669; }
    </style>
    
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6 sticky top-[72px] z-10">
        <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="mdi mdi-cash"></i> Fee Collection Dashboard</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white shadow-lg">
                <div class="text-xs font-semibold mb-1 opacity-90">Feeding</div>
                <div class="text-xl font-bold" id="total_feeding">GH₵ 0.00</div>
            </div>
            <?php if($breakfast_enabled): ?>
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white shadow-lg">
                <div class="text-xs font-semibold mb-1 opacity-90">Breakfast</div>
                <div class="text-xl font-bold" id="total_breakfast">GH₵ 0.00</div>
            </div>
            <?php endif; ?>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                <div class="text-xs font-semibold mb-1 opacity-90">Classes</div>
                <div class="text-xl font-bold" id="total_classes">GH₵ 0.00</div>
            </div>
            <?php if($water_enabled): ?>
            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-4 text-white shadow-lg">
                <div class="text-xs font-semibold mb-1 opacity-90">Water</div>
                <div class="text-xl font-bold" id="total_water">GH₵ 0.00</div>
            </div>
            <?php endif; ?>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                <div class="text-xs font-semibold mb-1 opacity-90">Transport</div>
                <div class="text-xl font-bold" id="total_transport">GH₵ 0.00</div>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">Enable Fee Collection:</p>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <?php if ($can_collect_feeding): ?>
                <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-gray-200">
                    <div>
                        <p class="font-semibold text-gray-800">Feeding Fee</p>
                        <p class="text-sm text-gray-500">GH₵ <?php echo number_format($feeding_fee, 2); ?> per day</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="collect_feeding" id="collect_feeding" value="1" <?php echo $has_any_feeding ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <?php endif; ?>
                
                <?php if ($can_collect_feeding && $breakfast_enabled): ?>
                <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-gray-200">
                    <div>
                        <p class="font-semibold text-gray-800">Breakfast Fee</p>
                        <p class="text-sm text-gray-500">GH₵ <?php echo number_format($breakfast_fee, 2); ?> per day</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="collect_breakfast" id="collect_breakfast" value="1">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <?php endif; ?>
                
                <?php if ($can_collect_classes): ?>
                <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-gray-200">
                    <div>
                        <p class="font-semibold text-gray-800">Classes Fee</p>
                        <p class="text-sm text-gray-500">GH₵ <?php echo number_format($classes_fee, 2); ?> per day</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="collect_classes" id="collect_classes" value="1" <?php echo $has_any_classes ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <?php endif; ?>
                
                <?php if ($can_collect_classes && $water_enabled): ?>
                <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-gray-200">
                    <div>
                        <p class="font-semibold text-gray-800">Water Fee</p>
                        <p class="text-sm text-gray-500">GH₵ <?php echo number_format($water_fee, 2); ?> per week</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="collect_water" id="collect_water" value="1">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <?php endif; ?>
                
                <?php if ($can_collect_transport): ?>
                <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-gray-200">
                    <div>
                        <p class="font-semibold text-gray-800">Transport Fare</p>
                        <p class="text-sm text-gray-500">Varies by route</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="collect_transport" id="collect_transport" value="1" <?php echo $has_any_transport ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div id="attendance_grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
        $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
        
        $this->db->where_in('attendance.student_id', $students_ids);
        $this->db->join('student', 'student.student_id = attendance.student_id');
        $this->db->join('enroll', 'enroll.student_id = student.student_id AND enroll.year = "'.$running_year.'" AND enroll.term = "'.$running_term.'"');
        $this->db->order_by('name', 'asc');
        $attendance = $this->db->get_where('attendance', array(
            'attendance.class_id' => $class_id,
            'attendance.section_id' => $section_id,
            'attendance.year' => $running_year,
            'attendance.term' => $running_term,
            'attendance.timestamp' => $timestamp
        ))->result_array();

        if(count($attendance) == 0):
            ?>
            <div class="col-span-full bg-red-50 border-l-4 border-red-500 p-6 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-red-500 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-red-700 font-bold text-lg">No attendance records found!</p>
                        <p class="text-red-600 text-sm mt-1">Please go back and select students to mark attendance.</p>
                        <a href="<?php echo site_url('admin/manage_attendance'); ?>" class="inline-block mt-3 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">
                            <i class="fa fa-arrow-left"></i> Go Back
                        </a>
                    </div>
                </div>
            </div>
            <?php
        endif;

        foreach($attendance as $row):
            $student = $this->db->get_where('student', array('student_id' => $row['student_id']))->row();
            $has_existing = isset($existing_payments[$row['student_id']]);
            $transport_id = $this->db->get_where('enroll', ['student_id' => $row['student_id'], 'year' => $running_year, 'term' => $running_term])->row()->transport_id ?? 0;
        ?>
        <div class="bg-white rounded-xl shadow-md border-2 <?php echo $has_existing ? 'border-yellow-400' : 'border-gray-200'; ?> p-4">
            <?php if ($has_existing): ?>
            <div class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-2 py-1 rounded mb-2">
                <i class="fa fa-warning"></i> Has payments today
            </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <p class="font-bold text-gray-900"><?php echo $student->name; ?></p>
                <p class="text-sm text-gray-600"><?php echo $student->student_code; ?></p>
            </div>
            
            <?php if ($can_mark_attendance): ?>
            <div class="mb-3">
                <label class="text-sm font-bold text-gray-700">Attendance</label>
                <select name="status_<?php echo $row['attendance_id']; ?>" class="attendance-select w-full px-3 py-2 border-2 border-gray-300 rounded-lg font-semibold text-sm" data-student="<?php echo $row['student_id']; ?>">
                    <option value="1" <?php if($row['status'] == 1) echo 'selected'; ?>>Present (P)</option>
                    <option value="2" <?php if($row['status'] == 2) echo 'selected'; ?>>Absent (A)</option>
                    <option value="3" <?php if($row['status'] == 3) echo 'selected'; ?>>Busy (B)</option>
                    <option value="4" <?php if($row['status'] == 4) echo 'selected'; ?>>Sick-Home (S)</option>
                    <option value="5" <?php if($row['status'] == 5) echo 'selected'; ?>>Sick-Clinic (C)</option>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($show_fee_collection && $can_collect_fees): 
                $feeding_owing_base = $cumulative_owing[$row['student_id']]['feeding'];
                $breakfast_owing_base = $cumulative_owing[$row['student_id']]['breakfast'];
                $classes_owing_base = $cumulative_owing[$row['student_id']]['classes'];
                $water_owing_base = $cumulative_owing[$row['student_id']]['water'];
                $transport_owing_base = $cumulative_owing[$row['student_id']]['transport'];
                
                // NEW Discount System - Complete Replacement
                $feeding_discount = 0;
                $breakfast_discount = 0;
                $classes_discount = 0;
                $water_discount = 0;
                $hide_feeding = false;
                $hide_breakfast = false;
                $hide_classes = false;
                $hide_water = false;
                $has_discount = false;
                
                $this->load->model('Discount_model');
                $student_discounts = $this->Discount_model->get_student_discounts($row['student_id'], $running_year, $running_term);
                
                if(!empty($student_discounts)) {
                    $has_discount = true;
                    
                    foreach($student_discounts as $profile) {
                        $this->db->select('*');
                        $this->db->from('discount_profile_rules');
                        $this->db->where('profile_id', $profile['profile_id']);
                        $this->db->group_start();
                            $this->db->where('class_id', $class_id);
                            $this->db->or_where('class_id IS NULL');
                        $this->db->group_end();
                        $rules = $this->db->get()->result_array();
                        
                        foreach($rules as $rule) {
                            $value = $rule['discount_value'];
                            $fee_type = $rule['fee_type'] ?? null;
                            
                            if($profile['discount_type'] == 'percentage') {
                                if($fee_type == 'feeding' || $fee_type == null) {
                                    $feeding_discount += ($feeding_fee * $value) / 100;
                                    if($value >= 100) $hide_feeding = true;
                                }
                                if($fee_type == 'breakfast' || $fee_type == null) {
                                    $breakfast_discount += ($breakfast_fee * $value) / 100;
                                    if($value >= 100) $hide_breakfast = true;
                                }
                                if($fee_type == 'classes' || $fee_type == null) {
                                    $classes_discount += ($classes_fee * $value) / 100;
                                    if($value >= 100) $hide_classes = true;
                                }
                                if($fee_type == 'water' || $fee_type == null) {
                                    $water_discount += ($water_fee * $value) / 100;
                                    if($value >= 100) $hide_water = true;
                                }
                            } else {
                                if($fee_type == 'feeding' || $fee_type == null) {
                                    $feeding_discount += $value;
                                    if($feeding_discount >= $feeding_fee) $hide_feeding = true;
                                }
                                if($fee_type == 'breakfast' || $fee_type == null) {
                                    $breakfast_discount += $value;
                                    if($breakfast_discount >= $breakfast_fee) $hide_breakfast = true;
                                }
                                if($fee_type == 'classes' || $fee_type == null) {
                                    $classes_discount += $value;
                                    if($classes_discount >= $classes_fee) $hide_classes = true;
                                }
                                if($fee_type == 'water' || $fee_type == null) {
                                    $water_discount += $value;
                                    if($water_discount >= $water_fee) $hide_water = true;
                                }
                            }
                        }
                    }
                }
                
                $feeding_fee_final = max(0, $feeding_fee - $feeding_discount);
                $breakfast_fee_final = max(0, $breakfast_fee - $breakfast_discount);
                $classes_fee_final = max(0, $classes_fee - $classes_discount);
                $water_fee_final = max(0, $water_fee - $water_discount);
                $feeding_total_owing = $feeding_owing_base + $feeding_fee_final;
                $breakfast_total_owing = $breakfast_owing_base + $breakfast_fee_final;
                $classes_total_owing = $classes_owing_base + $classes_fee_final;
                $water_total_owing = $water_owing_base + $water_fee_final;
                $transport_total_owing = $transport_owing_base;
            ?>
            <?php if($has_discount): ?>
            <div class="bg-green-50 border border-green-200 rounded p-2 mb-2">
                <p class="text-xs font-bold text-green-800"><i class="fa fa-gift"></i> Discount Profile Applied</p>
                <?php if($feeding_discount > 0 || $hide_feeding): ?>
                <p class="text-xs text-green-700">Feeding: <?php echo $hide_feeding ? '100% Discount (Free)' : 'GH₵'.number_format($feeding_fee, 2).' - GH₵'.number_format($feeding_discount, 2).' = GH₵'.number_format($feeding_fee_final, 2); ?></p>
                <?php endif; ?>
                <?php if($breakfast_discount > 0 || $hide_breakfast): ?>
                <p class="text-xs text-green-700">Breakfast: <?php echo $hide_breakfast ? '100% Discount (Free)' : 'GH₵'.number_format($breakfast_fee, 2).' - GH₵'.number_format($breakfast_discount, 2).' = GH₵'.number_format($breakfast_fee_final, 2); ?></p>
                <?php endif; ?>
                <?php if($classes_discount > 0 || $hide_classes): ?>
                <p class="text-xs text-green-700">Classes: <?php echo $hide_classes ? '100% Discount (Free)' : 'GH₵'.number_format($classes_fee, 2).' - GH₵'.number_format($classes_discount, 2).' = GH₵'.number_format($classes_fee_final, 2); ?></p>
                <?php endif; ?>
                <?php if($water_discount > 0 || $hide_water): ?>
                <p class="text-xs text-green-700">Water: <?php echo $hide_water ? '100% Discount (Free)' : 'GH₵'.number_format($water_fee, 2).' - GH₵'.number_format($water_discount, 2).' = GH₵'.number_format($water_fee_final, 2); ?></p>
                <?php endif; ?>
                <?php if($feeding_discount == 0 && $breakfast_discount == 0 && $classes_discount == 0 && $water_discount == 0 && !$hide_feeding && !$hide_breakfast && !$hide_classes && !$hide_water): ?>
                <p class="text-xs text-orange-700">No discount configured for this class</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="space-y-2">
                <?php if(!$hide_feeding): ?>
                <div class="feeding-fee-input" style="display:<?php echo $has_any_feeding ? 'block' : 'none'; ?>;">
                    <label class="text-sm font-bold text-gray-700 mb-1 block">Feeding Fee</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-sm text-gray-600">Amount Paid</label>
                            <input type="number" name="feeding_<?php echo $row['student_id']; ?>" step="0.01" 
                                   class="fee-input w-full px-2 py-1 border rounded text-sm" 
                                   data-student="<?php echo $row['student_id']; ?>" 
                                   data-type="feeding" 
                                   data-base="<?php echo $feeding_owing_base; ?>" 
                                   data-charge="<?php echo $feeding_fee_final; ?>" 
                                   placeholder="<?php echo $feeding_fee_final; ?>" 
                                   value="<?php echo isset($existing_payments[$row['student_id']]['feeding']) ? $existing_payments[$row['student_id']]['feeding']['amount'] : ''; ?>">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Owing</label>
                            <?php 
                            $feeding_display_owing = isset($existing_payments[$row['student_id']]['feeding']) ? $existing_payments[$row['student_id']]['feeding']['due'] : $feeding_total_owing;
                            ?>
                            <div class="owing-display text-sm px-2 py-1 rounded font-semibold <?php echo $feeding_display_owing == 0 ? 'bg-green-100 text-green-800' : ($feeding_display_owing > 0 ? 'bg-red-100 text-red-800' : 'bg-sky-100 text-sky-800'); ?>" 
                                 id="owing_feeding_<?php echo $row['student_id']; ?>">
                                <?php echo number_format($feeding_display_owing, 2); ?>
                            </div>
                            <input type="hidden" name="feeding_owing_<?php echo $row['student_id']; ?>" id="feeding_owing_hidden_<?php echo $row['student_id']; ?>" value="<?php echo $feeding_display_owing; ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(!$hide_breakfast && $breakfast_enabled): ?>
                <div class="breakfast-fee-input" style="display:none;">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-bold text-gray-700">Breakfast Fee</label>
                        <label class="relative inline-block w-10 h-5">
                            <input type="checkbox" name="breakfast_<?php echo $row['attendance_id']; ?>" class="breakfast-optin sr-only" id="breakfast_optin_<?php echo $row['student_id']; ?>" data-student="<?php echo $row['student_id']; ?>" value="1" checked>
                            <span class="absolute cursor-pointer inset-0 rounded-full transition" style="background-color: #10b981;"></span>
                            <span class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition" style="transform: translateX(20px);"></span>
                        </label>
                    </div>
                    <div class="breakfast-payment-fields">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-sm text-gray-600">Amount Paid</label>
                                <input type="number" name="breakfast_<?php echo $row['student_id']; ?>" step="0.01" 
                                       class="fee-input w-full px-2 py-1 border rounded text-sm" 
                                       data-student="<?php echo $row['student_id']; ?>" 
                                       data-type="breakfast" 
                                       data-base="<?php echo $breakfast_owing_base; ?>" 
                                       data-charge="<?php echo $breakfast_fee_final; ?>" 
                                       placeholder="<?php echo $breakfast_fee_final; ?>">
                            </div>
                            <div>
                                <label class="text-sm text-gray-600">Owing</label>
                                <div class="owing-display text-sm px-2 py-1 rounded font-semibold <?php echo $breakfast_total_owing == 0 ? 'bg-green-100 text-green-800' : ($breakfast_total_owing > 0 ? 'bg-red-100 text-red-800' : 'bg-sky-100 text-sky-800'); ?>" 
                                     id="owing_breakfast_<?php echo $row['student_id']; ?>">
                                    <?php echo number_format($breakfast_total_owing, 2); ?>
                                </div>
                                <input type="hidden" name="breakfast_owing_<?php echo $row['student_id']; ?>" id="breakfast_owing_hidden_<?php echo $row['student_id']; ?>" value="<?php echo $breakfast_total_owing; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(!$hide_classes): ?>
                <div class="classes-fee-input" style="display:<?php echo $has_any_classes ? 'block' : 'none'; ?>;">
                    <label class="text-sm font-bold text-gray-700 mb-1 block">Classes Fee</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-sm text-gray-600">Amount Paid</label>
                            <input type="number" name="classes_<?php echo $row['student_id']; ?>" step="0.01" 
                                   class="fee-input w-full px-2 py-1 border rounded text-sm" 
                                   data-student="<?php echo $row['student_id']; ?>" 
                                   data-type="classes" 
                                   data-base="<?php echo $classes_owing_base; ?>" 
                                   data-charge="<?php echo $classes_fee_final; ?>" 
                                   placeholder="<?php echo $classes_fee_final; ?>" 
                                   value="<?php echo isset($existing_payments[$row['student_id']]['classes']) ? $existing_payments[$row['student_id']]['classes']['amount'] : ''; ?>">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Owing</label>
                            <?php 
                            $classes_display_owing = isset($existing_payments[$row['student_id']]['classes']) ? $existing_payments[$row['student_id']]['classes']['due'] : $classes_total_owing;
                            ?>
                            <div class="owing-display text-sm px-2 py-1 rounded font-semibold <?php echo $classes_display_owing == 0 ? 'bg-green-100 text-green-800' : ($classes_display_owing > 0 ? 'bg-red-100 text-red-800' : 'bg-sky-100 text-sky-800'); ?>" 
                                 id="owing_classes_<?php echo $row['student_id']; ?>">
                                <?php echo number_format($classes_display_owing, 2); ?>
                            </div>
                            <input type="hidden" name="classes_owing_<?php echo $row['student_id']; ?>" id="classes_owing_hidden_<?php echo $row['student_id']; ?>" value="<?php echo $classes_display_owing; ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(!$hide_water && $water_enabled): ?>
                <div class="water-fee-input" style="display:none;">
                    <label class="text-sm font-bold text-gray-700 mb-1 block">Water Fee</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-sm text-gray-600">Amount Paid</label>
                            <input type="number" name="water_<?php echo $row['student_id']; ?>" step="0.01" 
                                   class="fee-input w-full px-2 py-1 border rounded text-sm" 
                                   data-student="<?php echo $row['student_id']; ?>" 
                                   data-type="water" 
                                   data-base="<?php echo $water_owing_base; ?>" 
                                   data-charge="<?php echo $water_fee_final; ?>" 
                                   placeholder="<?php echo $water_fee_final; ?>">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Owing</label>
                            <div class="owing-display text-sm px-2 py-1 rounded font-semibold <?php echo $water_total_owing == 0 ? 'bg-green-100 text-green-800' : ($water_total_owing > 0 ? 'bg-red-100 text-red-800' : 'bg-sky-100 text-sky-800'); ?>" 
                                 id="owing_water_<?php echo $row['student_id']; ?>">
                                <?php echo number_format($water_total_owing, 2); ?>
                            </div>
                            <input type="hidden" name="water_owing_<?php echo $row['student_id']; ?>" id="water_owing_hidden_<?php echo $row['student_id']; ?>" value="<?php echo $water_total_owing; ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($transport_id > 0): 
                    $transport_rate = $this->db->get_where('transport', ['transport_id' => $transport_id])->row()->route_fare ?? 0;
                    $transport_total_owing = $transport_owing_base + $transport_rate;
                ?>
                <div class="transport-fee-input" style="display:<?php echo $has_any_transport ? 'block' : 'none'; ?>;">
                    <label class="text-sm font-bold text-gray-700 mb-1 block">Transport Fare</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-sm text-gray-600">Amount Paid</label>
                            <input type="number" name="transport_<?php echo $row['student_id']; ?>" step="0.01" 
                                   class="fee-input w-full px-2 py-1 border rounded text-sm" 
                                   data-student="<?php echo $row['student_id']; ?>" 
                                   data-type="transport" 
                                   data-base="<?php echo $transport_owing_base; ?>" 
                                   data-charge="<?php echo $transport_rate; ?>" 
                                   placeholder="<?php echo $transport_rate; ?>" 
                                   value="<?php echo isset($existing_payments[$row['student_id']]['transport']) ? $existing_payments[$row['student_id']]['transport']['amount'] : ''; ?>">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Owing</label>
                            <?php 
                            $transport_display_owing = isset($existing_payments[$row['student_id']]['transport']) ? $existing_payments[$row['student_id']]['transport']['due'] : $transport_total_owing;
                            ?>
                            <div class="owing-display text-sm px-2 py-1 rounded font-semibold <?php echo $transport_display_owing == 0 ? 'bg-green-100 text-green-800' : ($transport_display_owing > 0 ? 'bg-red-100 text-red-800' : 'bg-sky-100 text-sky-800'); ?>" 
                                 id="owing_transport_<?php echo $row['student_id']; ?>">
                                <?php echo number_format($transport_display_owing, 2); ?>
                            </div>
                            <input type="hidden" name="transport_owing_<?php echo $row['student_id']; ?>" id="transport_owing_hidden_<?php echo $row['student_id']; ?>" value="<?php echo $transport_display_owing; ?>">
                        </div>
                    </div>
                    <input type="hidden" name="transport_id_<?php echo $row['student_id']; ?>" value="<?php echo $transport_id; ?>">
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="fixed bottom-6 right-6">
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-8 py-4 rounded-lg shadow-lg">
            <i class="fa fa-check"></i> Save Attendance
        </button>
    </div>
    <input type="hidden" name="students_ids" value="<?php echo $student_id; ?>">
    <?php echo form_close(); ?>
</div>

<script>
// Student filter
$('#student_filter').on('keyup', function() {
    const filter = $(this).val().toLowerCase();
    $('#attendance_grid > div').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(filter) > -1);
    });
});

// Select All Present
$('#select_all_btn').click(function() {
    $('.attendance-select').each(function() {
        $(this).val('1').trigger('change');
    });
});

// Mark All Absent
$('#deselect_all_btn').click(function() {
    $('.attendance-select').each(function() {
        $(this).val('2').trigger('change');
    });
});

// Handle attendance status change
$(document).on('change', '.attendance-select', function() {
    const student = $(this).data('student');
    const status = $(this).val();
    const isAbsent = status != '1';
    
    // Handle breakfast opt-in checkbox
    const breakfastOptin = $('#breakfast_optin_' + student);
    if (isAbsent) {
        breakfastOptin.prop('checked', false).prop('disabled', true).trigger('change');
    } else {
        breakfastOptin.prop('disabled', false);
    }
    
    // Find all fee inputs for this student
    $('.fee-input[data-student="' + student + '"]').each(function() {
        const type = $(this).data('type');
        const base = parseFloat($(this).data('base')) || 0;
        const charge = parseFloat($(this).data('charge')) || 0;
        const owingDiv = $('#owing_' + type + '_' + student);
        
        // Skip breakfast if not opted in
        if (type === 'breakfast' && !breakfastOptin.is(':checked')) {
            return;
        }
        
        if (isAbsent) {
            // Store current payment value before clearing
            const currentVal = parseFloat($(this).val()) || 0;
            if (currentVal > 0) {
                $(this).attr('data-stored-payment', currentVal);
            }
            
            // Disable input and clear value
            $(this).prop('disabled', true).val('').addClass('bg-gray-100');
            
            // Set owing to base only (no charge since absent)
            const newOwing = base;
            owingDiv.text(newOwing.toFixed(2));
            $('#' + type + '_owing_hidden_' + student).val(newOwing.toFixed(2));
            
            owingDiv.removeClass('bg-green-100 text-green-800 bg-red-100 text-red-800 bg-sky-100 text-sky-800');
            if (newOwing == 0) {
                owingDiv.addClass('bg-green-100 text-green-800');
            } else if (newOwing > 0) {
                owingDiv.addClass('bg-red-100 text-red-800');
            } else {
                owingDiv.addClass('bg-sky-100 text-sky-800');
            }
        } else {
            // Enable input
            $(this).prop('disabled', false).removeClass('bg-gray-100');
            
            // Restore stored payment if any
            const storedPayment = parseFloat($(this).attr('data-stored-payment')) || 0;
            if (storedPayment > 0 && $(this).val() == '') {
                $(this).val(storedPayment);
                $(this).removeAttr('data-stored-payment');
            }
            
            // Recalculate owing with charge
            const paid = parseFloat($(this).val()) || 0;
            const newOwing = base + charge - paid;
            owingDiv.text(newOwing.toFixed(2));
            $('#' + type + '_owing_hidden_' + student).val(newOwing.toFixed(2));
            
            owingDiv.removeClass('bg-green-100 text-green-800 bg-red-100 text-red-800 bg-sky-100 text-sky-800');
            if (newOwing == 0) {
                owingDiv.addClass('bg-green-100 text-green-800');
            } else if (newOwing > 0) {
                owingDiv.addClass('bg-red-100 text-red-800');
            } else {
                owingDiv.addClass('bg-sky-100 text-sky-800');
            }
        }
    });
    
    updateTotals();
});

// Calculate totals
function updateTotals() {
    let feedingTotal = 0;
    let breakfastTotal = 0;
    let classesTotal = 0;
    let waterTotal = 0;
    let transportTotal = 0;
    
    $('input[name^="feeding_"].fee-input').each(function() {
        if (!$(this).prop('disabled') && $(this).val() !== '') {
            let val = parseFloat($(this).val()) || 0;
            feedingTotal += val;
            console.log('Feeding input:', $(this).attr('name'), 'value:', val);
        }
    });
    $('input[name^="breakfast_"].fee-input').each(function() {
        if (!$(this).prop('disabled') && $(this).val() !== '') {
            let val = parseFloat($(this).val()) || 0;
            breakfastTotal += val;
            console.log('Breakfast input:', $(this).attr('name'), 'value:', val);
        }
    });
    $('input[name^="classes_"].fee-input').each(function() {
        if (!$(this).prop('disabled') && $(this).val() !== '') {
            let val = parseFloat($(this).val()) || 0;
            classesTotal += val;
            console.log('Classes input:', $(this).attr('name'), 'value:', val);
        }
    });
    $('input[name^="water_"].fee-input').each(function() {
        if (!$(this).prop('disabled') && $(this).val() !== '') {
            let val = parseFloat($(this).val()) || 0;
            waterTotal += val;
            console.log('Water input:', $(this).attr('name'), 'value:', val);
        }
    });
    $('input[name^="transport_"].fee-input').each(function() {
        if (!$(this).prop('disabled') && $(this).val() !== '') {
            let val = parseFloat($(this).val()) || 0;
            transportTotal += val;
            console.log('Transport input:', $(this).attr('name'), 'value:', val);
        }
    });
    
    console.log('Total feeding:', feedingTotal, 'breakfast:', breakfastTotal, 'classes:', classesTotal, 'water:', waterTotal, 'transport:', transportTotal);
    
    $('#total_feeding').text('GH₵ ' + feedingTotal.toFixed(2));
    $('#total_breakfast').text('GH₵ ' + breakfastTotal.toFixed(2));
    $('#total_classes').text('GH₵ ' + classesTotal.toFixed(2));
    $('#total_water').text('GH₵ ' + waterTotal.toFixed(2));
    $('#total_transport').text('GH₵ ' + transportTotal.toFixed(2));
}

// Real-time owing calculation
$(document).on('input', '.fee-input', function() {
    const student = $(this).data('student');
    const type = $(this).data('type');
    const base = parseFloat($(this).data('base')) || 0;
    const charge = parseFloat($(this).data('charge')) || 0;
    const paid = parseFloat($(this).val()) || 0;
    
    const newOwing = base + charge - paid;
    const owingDiv = $('#owing_' + type + '_' + student);
    
    owingDiv.text(newOwing.toFixed(2));
    $('#' + type + '_owing_hidden_' + student).val(newOwing.toFixed(2));
    
    owingDiv.removeClass('bg-green-100 text-green-800 bg-red-100 text-red-800 bg-sky-100 text-sky-800');
    if (newOwing == 0) {
        owingDiv.addClass('bg-green-100 text-green-800');
    } else if (newOwing > 0) {
        owingDiv.addClass('bg-red-100 text-red-800');
    } else {
        owingDiv.addClass('bg-sky-100 text-sky-800');
    }
    
    updateTotals();
});

// Toggle fee input fields
$('#collect_feeding').change(function() {
    $('.feeding-fee-input').toggle(this.checked);
});
$('#collect_breakfast').change(function() {
    $('.breakfast-fee-input').toggle(this.checked);
});
$('#collect_classes').change(function() {
    $('.classes-fee-input').toggle(this.checked);
});
$('#collect_water').change(function() {
    $('.water-fee-input').toggle(this.checked);
});
$('#collect_transport').change(function() {
    $('.transport-fee-input').toggle(this.checked);
});

// Breakfast opt-in toggle handler
$(document).on('change', '.breakfast-optin', function() {
    const student = $(this).data('student');
    const isChecked = $(this).is(':checked');
    const container = $(this).closest('.breakfast-fee-input');
    const input = $('input[name="breakfast_' + student + '"]');
    const bg = $(this).next('span');
    const slider = bg.next('span');
    
    if (!isChecked) {
        container.css('opacity', '0.5');
        bg.css('background-color', '#9ca3af');
        slider.css('transform', 'translateX(0)');
        input.val('').prop('disabled', true);
        const base = parseFloat(input.data('base')) || 0;
        $('#owing_breakfast_' + student).text(base.toFixed(2));
        $('#breakfast_owing_hidden_' + student).val(base.toFixed(2));
        updateTotals();
    } else {
        container.css('opacity', '1');
        bg.css('background-color', '#10b981');
        slider.css('transform', 'translateX(20px)');
        input.prop('disabled', false);
    }
});

$('#attendance_form').submit(function(e) {
    e.preventDefault();
    
    const collectFeeding = $('#collect_feeding').is(':checked');
    const collectClasses = $('#collect_classes').is(':checked');
    const collectTransport = $('#collect_transport').is(':checked');
    
    // Check for existing payments
    const existingPayments = <?php echo json_encode($existing_payments); ?>;
    const hasExisting = Object.keys(existingPayments).length > 0;
    
    if (hasExisting && (collectFeeding || collectClasses || collectTransport)) {
        showConfirmModal(
            'Override Existing Payments?',
            'Some students already have fee payments for this date. Do you want to override them?',
            function() {
                submitForm();
            },
            'Yes, Override',
            'warning'
        );
    } else {
        submitForm();
    }
});

function submitForm() {
    showAjaxModal_alert('Processing...', 'loading');
    
    $.ajax({
        url: $('#attendance_form').attr('action'),
        type: 'POST',
        data: $('#attendance_form').serialize(),
        success: function(response) {
            const data = JSON.parse(response);
            if(data.status === 'success') {
                showAjaxModal_alert(data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAjaxModal_alert(data.message, 'error');
            }
        },
        error: function(err) {
            showAjaxModal_alert('An error occurred. ' + err.responseText, 'error');
        }
    });
}

function select_section(class_id) {
    if(class_id !== '') {
        $.ajax({
            url: '<?php echo site_url('admin/get_section/'); ?>' + class_id,
            success: function(response) {
                $('#section_holder').html(response);
            }
        });
    }
}

function select_students(class_id) {
    if(class_id !== '') {
        $.ajax({
            url: '<?php echo site_url('admin/get_multi_select_students/'); ?>' + class_id + '/<?=$student_id ?>',
            success: function(response) {
                $('#students_holder').slideDown('slow').html(response);
                initStudentSelection();
            }
        });
    } else {
        $('#students_holder').slideUp('slow');
    }
}

function initStudentSelection() {
    const searchInput = $('#student_search');
    const selectAllBtn = $('#select_all_btn');
    const deselectAllBtn = $('#deselect_all_btn');
    
    function updateSelectedCount() {
        const total = $('.check').length;
        const selected = $('.check:checked').length;
        $('#selected_count').text(selected);
        $('#student_count').text(total);
    }
    
    if(searchInput.length) {
        searchInput.off('keyup').on('keyup', function() {
            const filter = $(this).val().toLowerCase();
            $('.student-card').each(function() {
                const name = $(this).data('student-name').toLowerCase();
                $(this).toggle(name.indexOf(filter) > -1);
            });
        });
    }
    
    if(selectAllBtn.length) {
        selectAllBtn.off('click').on('click', function() {
            $('.student-card:visible .check').prop('checked', true);
            updateSelectedCount();
        });
    }
    
    if(deselectAllBtn.length) {
        deselectAllBtn.off('click').on('click', function() {
            $('.check').prop('checked', false);
            updateSelectedCount();
        });
    }
    
    $('.check').off('change').on('change', updateSelectedCount);
    updateSelectedCount();
}

$(document).ready(function() {
    // Initialize all breakfast toggles as enabled
    $('.breakfast-optin').prop('checked', true);
    
    const classId = $('#class_selection').val();
    if(classId) {
        select_students(classId);
    }
    
    $('.datepicker').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true,
        orientation: 'bottom',
        endDate: new Date()
    });
    
    // Call updateTotals() with a small delay to ensure DOM is fully rendered
    setTimeout(function() {
        updateTotals();
        console.log('Totals updated on page load');
    }, 100);

});

$('#att_selector_form').submit(function(event) {
	event.preventDefault();

	let item_checked = $('.check').filter(':checked').length;
	if(item_checked < 1) {
		showAjaxModal_alert('No student was selected!', 'Error');
		return false;
	}

	let student_ids = [];
	$('.check:checked').each(function() {
		student_ids.push($(this).val());
	});

	$('#main_page').html(`
		<div class="flex justify-center items-center h-screen">
			<div class="text-center">
				<svg class="animate-spin h-16 w-16 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
					<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
				</svg>
				<p class="text-xl font-semibold text-gray-700">Fetching Data...</p>
			</div>
		</div>
	`);

	let formData = new FormData(this);
	student_ids.forEach(id => formData.append('students_ids[]', id));

	$.ajax({
		url: '<?php echo site_url('admin/attendance_selector/'); ?>',
		type: 'POST',
		dataType: 'html',
		data: formData,
		cache: false,
		contentType: false,
		processData: false
	})
	.done(function(data) {
		
		if(data == 'promotion error term') {
			showAjaxModal_confirm('Make sure students were promoted during the previous term. For further assistance, kindly contact the system administrator.', 'Error');
			navigation('<?php echo site_url('admin/manage_attendance'); ?>');
		} else if(data == 'promotion error sem') {
			showAjaxModal_confirm('Make sure students were promoted during the previous semester. For further assistance, kindly contact the system administrator.', 'Error');
			navigation('<?php echo site_url('admin/manage_attendance'); ?>');
		} else {
			$('#main_page').empty();
			navigation(data);
			$('#pre_notice').fadeOut('400', function() {
				$('#pre_notice').remove();
			}); 
		}
	});
});
</script>
