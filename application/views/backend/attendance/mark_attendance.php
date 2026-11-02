<?php
$login_type = $this->session->userdata('login_type');
$user_id = $this->session->userdata('login_user_id');
$collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';

// Determine if fee collection should be shown in attendance portal
// Show fees if: 
// 1. Mode is 'classroom' (fees only collected during attendance)
// 2. Mode is 'hybrid' (fees can be collected both during attendance and by cashier)
// AND user has permission to collect fees
$show_fee_collection = in_array($collection_mode, ['classroom', 'hybrid']) && $permissions['can_collect_fees'];

$teacher_id = ($login_type == 'teacher') ? $user_id : '';

// Get enabled fee modules
$enabled_modules = [];
foreach(['feeding', 'classes', 'transport', 'breakfast', 'water'] as $module) {
    if(is_fee_module_enabled($module)) {
        $enabled_modules[] = $module;
    }
}

// Get currency - use Ghana Cedis symbol
$currency_setting = $this->db->get_where('settings', ['type' => 'currency'])->row()->description ?? 'GH₵';
// Convert GHC or GHS to the Ghana Cedis symbol (₵)
$currency = '₵'; // Always use the Ghana Cedis symbol
?>

<style>
/* Page Loading Preloader */
#attendance-page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    opacity: 1;
    transition: opacity 0.3s ease-out;
}
#attendance-page-loader.hidden {
    opacity: 0;
    pointer-events: none;
}
.loader-spinner {
    width: 60px;
    height: 60px;
    border: 6px solid #e5e7eb;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.loader-text {
    margin-top: 20px;
    font-size: 18px;
    font-weight: 600;
    color: #667eea;
}
.attendance-container { max-width: 1600px; margin: 0 auto; padding: 24px; }
.header-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
.filter-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
.filter-card .form-control { padding: 14px 18px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; transition: all 0.3s; height: 48px; }
.filter-card .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); outline: none; }
.filter-card label { font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; }
.btn-load { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 24px; border-radius: 10px; font-weight: 600; border: none; transition: all 0.3s; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3); height: 48px; font-size: 16px; }
.btn-load:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); color: white; }
/* Modern Filter Inputs */
.modern-filter-group { position: relative; }
.modern-filter-group label { 
    font-size: 13px; 
    font-weight: 600; 
    color: #374151; 
    display: block; 
    margin-bottom: 6px;
    letter-spacing: 0.02em;
}
.modern-filter-input {
    width: 100%;
    height: 46px;
    padding: 10px 14px;
    font-size: 15px;
    font-weight: 500;
    color: #1f2937;
    background-color: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.2s ease;
    outline: none;
}
.modern-filter-input:hover {
    border-color: #d1d5db;
    background-color: #fff;
}
.modern-filter-input:focus {
    border-color: #667eea;
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}
.modern-filter-input::placeholder {
    color: #9ca3af;
    font-weight: 400;
}
/* Select dropdown styling */
.modern-filter-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
    padding-right: 40px;
    cursor: pointer;
}
.modern-filter-select option {
    font-size: 15px;
    font-weight: 500;
    color: #1f2937;
    padding: 10px;
}
.student-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
@media (min-width: 768px) and (max-width: 1199px) { .student-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1200px) { .student-grid { grid-template-columns: repeat(4, 1fr); } }
.student-card { background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px; transition: all 0.2s; position: relative; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); }
.student-card:hover { border-color: #667eea; box-shadow: 0 4px 16px rgba(102, 126, 234, 0.25); }
.student-card.has-payment { border-color: #10b981; border-width: 3px; box-shadow: 0 2px 12px rgba(16, 185, 129, 0.2); }
.student-checkbox { position: absolute; top: 12px; right: 12px; width: 20px; height: 20px; cursor: pointer; }
.student-card.unselected { opacity: 0.5; border-color: #d1d5db; }
.payment-indicator { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; box-shadow: 0 1px 3px rgba(16, 185, 129, 0.3); margin-left: 8px; }
.payment-indicator i { font-size: 9px; }
.select-all-container { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 16px 20px; border-radius: 10px; margin-bottom: 16px; display: none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); border: 2px solid #f59e0b; cursor: pointer; transition: all 0.3s; }
.select-all-container.show { display: flex; align-items: center; gap: 12px; }
.select-all-container:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4); }
.select-all-container label { color: #1f2937 !important; font-size: 16px; margin: 0 !important; cursor: pointer; user-select: none; }
.select-all-container .fa { font-size: 20px; color: #1f2937; }
.status-select { 
    width: 100%; 
    padding: 12px; 
    border: 2px solid #e5e7eb; 
    border-radius: 8px; 
    font-weight: 600; 
    font-size: 15px; 
    color: #1f2937;
    transition: all 0.3s ease;
}

/* Status color theming */
.status-select.status-1 { 
    background-color: #d1fae5; 
    border-color: #10b981; 
    color: #065f46; 
}

.status-select.status-2 { 
    background-color: #fee2e2; 
    border-color: #ef4444; 
    color: #991b1b; 
}

.status-select.status-3 { 
    background-color: #fef3c7; 
    border-color: #f59e0b; 
    color: #78350f; 
}

.status-select.status-4 { 
    background-color: #e0e7ff; 
    border-color: #818cf8; 
    color: #3730a3; 
}

.status-select.status-5 { 
    background-color: #ddd6fe; 
    border-color: #a78bfa; 
    color: #5b21b6; 
}

.status-select.status-6 { 
    background-color: #fbcfe8; 
    border-color: #ec4899; 
    color: #831843; 
}
.fee-input { width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 6px; font-weight: 600; font-size: 14px; }
.owing-badge { padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 13px; }
.owing-positive { background: #fee2e2; color: #991b1b; }
.owing-zero { background: #d1fae5; color: #065f46; }
.owing-negative { background: #dbeafe; color: #1e40af; }
.discount-badge { background: #d1fae5; border: 1px solid #10b981; padding: 8px; border-radius: 6px; margin-bottom: 12px; }
.fee-dashboard { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; position: sticky; top: 0; z-index: 100; }
.fee-total-card { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 16px; border-radius: 10px; text-align: center; }
.toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 28px; }
.toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; }
input:checked + .toggle-slider { background-color: #10b981; }
input:checked + .toggle-slider:before { transform: translateX(24px); }
.btn-enterprise { padding: 12px 24px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-success { background: #10b981; color: white; }
.btn-danger { background: #ef4444; color: white; }
@media print {
    .payment-indicator, .filter-card, .fee-dashboard, .btn-enterprise { display: none !important; }
    .student-card.has-payment { border-color: #10b981 !important; }
}
</style>

<div class="attendance-container">
    <div class="header-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0 0 8px 0; font-size: 24px; color: white;"><i class="fa fa-check-square"></i> <?php echo get_phrase('mark_attendance'); ?></h2>
                <p style="margin: 0; opacity: 0.9; color: white;"><?php echo date('l, F j, Y', strtotime($date)); ?></p>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <?php if($login_type == 'teacher' || $login_type == 'admin'): ?>
                <button class="btn-enterprise btn-success" onclick="printDiningCoupon()">
                    <i class="fa fa-ticket"></i> <strong><?php echo get_phrase('print_dining_coupon'); ?></strong>
                </button>
                <?php endif; ?>
                <button class="btn-enterprise btn-success" onclick="markAllPresent()">
                    <i class="fa fa-check"></i> <strong><?php echo get_phrase('mark_all_present'); ?></strong>
                </button>
                <button class="btn-enterprise btn-danger" onclick="markAllAbsent()">
                    <i class="fa fa-times"></i> <strong><?php echo get_phrase('mark_all_absent'); ?></strong>
                </button>
            </div>
        </div>
    </div>

    <?php if($show_fee_collection): ?>
    <div class="fee-dashboard" id="fee_dashboard">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <h3 id="fee_dashboard_title" style="margin: 0; font-size: 18px; font-weight: 700;"><i class="fa fa-credit-card"></i> <?php echo get_phrase('fee_collection_dashboard'); ?></h3>
            
            <!-- Filters moved inside the dashboard card -->
            <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                <div class="modern-filter-group" style="min-width: 200px;">
                    <label><?php echo get_phrase('select_class'); ?></label>
                    <select id="filter_class" class="modern-filter-input modern-filter-select">
                        <option value=""><?php echo get_phrase('select_class'); ?></option>
                        <?php getFullClassList($teacher_id); ?>
                    </select>
                </div>
                <div class="modern-filter-group" style="min-width: 170px;">
                    <label><?php echo get_phrase('select_date'); ?></label>
                    <input type="date" id="filter_date" value="<?php echo $date; ?>" class="modern-filter-input">
                </div>
                <div class="modern-filter-group" style="min-width: 220px;">
                    <label><?php echo get_phrase('search_students'); ?></label>
                    <input type="text" id="student_filter" placeholder="<?php echo get_phrase('type_student_name'); ?>..." class="modern-filter-input">
                </div>
                <button type="button" onclick="loadAttendance()" class="btn-load" style="height: 46px; padding: 10px 24px;">
                    <i class="fa fa-search"></i> <?php echo get_phrase('load'); ?>
                </button>
            </div>
        </div>
        
        <?php 
        // Fee module colors
        $colors = [
            'feeding' => ['from' => '#10b981', 'to' => '#059669'],
            'classes' => ['from' => '#3b82f6', 'to' => '#2563eb'],
            'transport' => ['from' => '#8b5cf6', 'to' => '#7c3aed'],
            'breakfast' => ['from' => '#ec4899', 'to' => '#db2777'],
            'water' => ['from' => '#06b6d4', 'to' => '#0891b2']
        ];
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
            <?php foreach(['feeding', 'classes', 'transport', 'breakfast', 'water'] as $module): ?>
                <?php if($permissions['can_collect_' . $module] && in_array($module, $enabled_modules)): ?>
                <?php $color = $colors[$module]; ?>
                <div class="fee-total-card" style="background: linear-gradient(135deg, <?php echo $color['from']; ?> 0%, <?php echo $color['to']; ?> 100%);">
                    <div style="font-size: 12px; opacity: 0.9; margin-bottom: 4px;"><?php echo ucfirst($module); ?></div>
                    <div style="font-size: 20px; font-weight: 700;" id="total_<?php echo $module; ?>"><?php echo $currency; ?> 0.00</div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- Show filters even when fee collection is disabled -->
    <div class="fee-dashboard" id="fee_dashboard">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700;"><i class="fa fa-calendar-check"></i> <?php echo get_phrase('attendance_filters'); ?></h3>
            
            <!-- Filters -->
            <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                <div class="modern-filter-group" style="min-width: 200px;">
                    <label><?php echo get_phrase('select_class'); ?></label>
                    <select id="filter_class" class="modern-filter-input modern-filter-select">
                        <option value=""><?php echo get_phrase('select_class'); ?></option>
                        <?php getFullClassList($teacher_id); ?>
                    </select>
                </div>
                <div class="modern-filter-group" style="min-width: 170px;">
                    <label><?php echo get_phrase('select_date'); ?></label>
                    <input type="date" id="filter_date" value="<?php echo $date; ?>" class="modern-filter-input">
                </div>
                <div class="modern-filter-group" style="min-width: 220px;">
                    <label><?php echo get_phrase('search_students'); ?></label>
                    <input type="text" id="student_filter" placeholder="<?php echo get_phrase('type_student_name'); ?>..." class="modern-filter-input">
                </div>
                <button type="button" onclick="loadAttendance()" class="btn-load" style="height: 46px; padding: 10px 24px;">
                    <i class="fa fa-search"></i> <?php echo get_phrase('load'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="select-all-container <?php echo !empty($attendance_records) ? 'show' : ''; ?>" id="select_all_container" style="margin-bottom: 20px;">
        <input type="checkbox" id="select_all" class="student-checkbox" checked style="width: 24px; height: 24px; pointer-events: none;">
        <label for="select_all">
            <i class="fa fa-check-square"></i> <strong><?php echo get_phrase('select_all_students'); ?></strong>
            <span style="opacity: 0.85; font-weight: 500; margin-left: 8px;">(<?php echo get_phrase('uncheck_to_update_specific_students_only'); ?>)</span>
        </label>
    </div>

    <?php echo form_open('attendance/save', ['id' => 'attendance_form']); ?>
    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
    <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
    <input type="hidden" name="date" value="<?php echo $date; ?>">
    
    <div class="student-grid" id="student_grid">
        <?php foreach($students as $student): 
            $student_id = $student['student_id'];
            $existing = $attendance_records[$student_id] ?? null;
            $current_status = $existing ? $existing['status'] : 2;
            
            // Flag: Is this the first time marking attendance for this date?
            // If $existing is null, it means attendance hasn't been marked yet
            $is_first_time = !$existing;
            
            // Get payment status
            $payment_info = $payment_status[$student_id] ?? ['has_payment' => false, 'payment_type' => null];
            $has_payment = $payment_info['has_payment'];
            $payment_type = $payment_info['payment_type'];
            
            // Get paid amounts for this student
            $paid_txn = $paid_amounts[$student_id] ?? null;
            $feeding_paid = $paid_txn['feeding_paid'] ?? null;
            $breakfast_paid = $paid_txn['breakfast_paid'] ?? null;
            $classes_paid = $paid_txn['classes_paid'] ?? null;
            $water_paid = $paid_txn['water_paid'] ?? null;
            $transport_paid = $paid_txn['transport_paid'] ?? null;
            
            // Get wallet with both prepaid balance and arrears
            $wallet = $this->db->get_where('daily_fee_wallet', ['student_id' => $student_id])->row();
            
            // Prepaid balances (credit) - raw wallet values
            $feeding_balance = $wallet ? $wallet->feeding_balance : 0;
            $breakfast_balance = $wallet ? ($wallet->breakfast_balance ?? 0) : 0;
            $classes_balance = $wallet ? $wallet->classes_balance : 0;
            $water_balance = $wallet ? ($wallet->water_balance ?? 0) : 0;
            $transport_balance = $wallet ? $wallet->transport_balance : 0;
            
            // Arrears (debt) - raw wallet values
            $feeding_arrears = $wallet ? $wallet->feeding_arrears : 0;
            $breakfast_arrears = $wallet ? ($wallet->breakfast_arrears ?? 0) : 0;
            $classes_arrears = $wallet ? $wallet->classes_arrears : 0;
            $water_arrears = $wallet ? ($wallet->water_arrears ?? 0) : 0;
            $transport_arrears = $wallet ? $wallet->transport_arrears : 0;
            
            // Get discounts - same logic as Fee_collection portal
            $this->load->model('Discount_model');
            $timestamp = strtotime($date);
            
            // Check if student has 100% discount on all daily fees
            $has_full_discount_all_fees = in_array($student_id, $students_with_full_discount);
            
            $feeding_info = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $class_id, 'feeding', $timestamp);
            $classes_info = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $class_id, 'classes', $timestamp);
            $breakfast_info = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $class_id, 'breakfast', $timestamp);
            $water_info = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $class_id, 'water', $timestamp);
            
            // Calculate discount amounts
            $feeding_discount = 0;
            $classes_discount = 0;
            $breakfast_discount = 0;
            $water_discount = 0;
            $has_discount = false;
            
            if ($feeding_info['has_discount']) {
                $has_discount = true;
                if ($feeding_info['discount_method'] == 'percentage') {
                    $feeding_discount = ($fee_rates['feeding_rate'] * $feeding_info['discount_value']) / 100;
                } else {
                    $feeding_discount = $feeding_info['discount_value'];
                }
            }
            
            if ($classes_info['has_discount']) {
                $has_discount = true;
                if ($classes_info['discount_method'] == 'percentage') {
                    $classes_discount = ($fee_rates['classes_rate'] * $classes_info['discount_value']) / 100;
                } else {
                    $classes_discount = $classes_info['discount_value'];
                }
            }
            
            // Get breakfast and water rates early for discount calculation
            $breakfast_rate = isset($fee_rates['breakfast_rate']) ? $fee_rates['breakfast_rate'] : 0;
            $water_rate = isset($fee_rates['water_rate']) ? $fee_rates['water_rate'] : 0;
            
            if ($breakfast_info['has_discount']) {
                $has_discount = true;
                if ($breakfast_info['discount_method'] == 'percentage') {
                    $breakfast_discount = ($breakfast_rate * $breakfast_info['discount_value']) / 100;
                } else {
                    $breakfast_discount = $breakfast_info['discount_value'];
                }
            }
            
            if ($water_info['has_discount']) {
                $has_discount = true;
                if ($water_info['discount_method'] == 'percentage') {
                    $water_discount = ($water_rate * $water_info['discount_value']) / 100;
                } else {
                    $water_discount = $water_info['discount_value'];
                }
            }
            
            // Determine if fee rows should be hidden (100% discount)
            // If student has full discount on all daily fees, hide everything
            if ($has_full_discount_all_fees) {
                $hide_feeding = true;
                $hide_classes = true;
                $hide_breakfast = true;
                $hide_water = true;
            } else {
                $hide_feeding = ($feeding_discount >= $fee_rates['feeding_rate']);
                $hide_classes = ($classes_discount >= $fee_rates['classes_rate']);
                $hide_breakfast = ($breakfast_discount >= $breakfast_rate);
                $hide_water = ($water_discount >= $water_rate);
            }
            
            $feeding_final = max(0, $fee_rates['feeding_rate'] - $feeding_discount);
            $classes_final = max(0, $fee_rates['classes_rate'] - $classes_discount);
            $breakfast_final = max(0, $breakfast_rate - $breakfast_discount);
            $water_final = max(0, $water_rate - $water_discount);
            
            // Calculate DISPLAY balances based on whether attendance was already marked
            // If first time marking: add today's charge to arrears (or deduct from prepaid)
            // If already marked: show wallet values as-is
            
            // Feeding display balances
            if ($is_first_time && $current_status != 2 && $current_status != 4 && $current_status != 5) {
                // First time marking and student is Present/Late - apply today's charge
                if ($feeding_balance >= $feeding_final) {
                    $display_feeding_prepaid = $feeding_balance - $feeding_final;
                    $display_feeding_arrears = $feeding_arrears;
                } else if ($feeding_balance > 0) {
                    $display_feeding_prepaid = 0;
                    $display_feeding_arrears = $feeding_arrears + ($feeding_final - $feeding_balance);
                } else {
                    $display_feeding_prepaid = 0;
                    $display_feeding_arrears = $feeding_arrears + $feeding_final;
                }
            } else {
                // Already marked or Absent/Sick - show wallet values as-is
                $display_feeding_prepaid = $feeding_balance;
                $display_feeding_arrears = $feeding_arrears;
            }
            
            // Classes display balances
            if ($is_first_time && $current_status != 2 && $current_status != 4 && $current_status != 5) {
                if ($classes_balance >= $classes_final) {
                    $display_classes_prepaid = $classes_balance - $classes_final;
                    $display_classes_arrears = $classes_arrears;
                } else if ($classes_balance > 0) {
                    $display_classes_prepaid = 0;
                    $display_classes_arrears = $classes_arrears + ($classes_final - $classes_balance);
                } else {
                    $display_classes_prepaid = 0;
                    $display_classes_arrears = $classes_arrears + $classes_final;
                }
            } else {
                $display_classes_prepaid = $classes_balance;
                $display_classes_arrears = $classes_arrears;
            }
            
            // Transport display balances
            $transport_final = isset($fee_rates['transport_rate']) ? $fee_rates['transport_rate'] : 0; // No discount for transport
            if ($is_first_time && $current_status != 2 && $current_status != 4 && $current_status != 5) {
                if ($transport_balance >= $transport_final) {
                    $display_transport_prepaid = $transport_balance - $transport_final;
                    $display_transport_arrears = $transport_arrears;
                } else if ($transport_balance > 0) {
                    $display_transport_prepaid = 0;
                    $display_transport_arrears = $transport_arrears + ($transport_final - $transport_balance);
                } else {
                    $display_transport_prepaid = 0;
                    $display_transport_arrears = $transport_arrears + $transport_final;
                }
            } else {
                $display_transport_prepaid = $transport_balance;
                $display_transport_arrears = $transport_arrears;
            }
            
            // Breakfast display balances
            if ($is_first_time && $current_status != 2 && $current_status != 4 && $current_status != 5) {
                if ($breakfast_balance >= $breakfast_final) {
                    $display_breakfast_prepaid = $breakfast_balance - $breakfast_final;
                    $display_breakfast_arrears = $breakfast_arrears;
                } else if ($breakfast_balance > 0) {
                    $display_breakfast_prepaid = 0;
                    $display_breakfast_arrears = $breakfast_arrears + ($breakfast_final - $breakfast_balance);
                } else {
                    $display_breakfast_prepaid = 0;
                    $display_breakfast_arrears = $breakfast_arrears + $breakfast_final;
                }
            } else {
                $display_breakfast_prepaid = $breakfast_balance;
                $display_breakfast_arrears = $breakfast_arrears;
            }
            
            // Water display balances
            if ($is_first_time && $current_status != 2 && $current_status != 4 && $current_status != 5) {
                if ($water_balance >= $water_final) {
                    $display_water_prepaid = $water_balance - $water_final;
                    $display_water_arrears = $water_arrears;
                } else if ($water_balance > 0) {
                    $display_water_prepaid = 0;
                    $display_water_arrears = $water_arrears + ($water_final - $water_balance);
                } else {
                    $display_water_prepaid = 0;
                    $display_water_arrears = $water_arrears + $water_final;
                }
            } else {
                $display_water_prepaid = $water_balance;
                $display_water_arrears = $water_arrears;
            }
        ?>
        <div class="student-card <?php echo $has_payment ? 'has-payment' : ''; ?>" data-student-name="<?php echo $student['name']; ?>" data-student-id="<?php echo $student_id; ?>">
            <?php if($existing): ?>
            <input type="checkbox" class="student-checkbox student-select" data-student="<?php echo $student_id; ?>" checked>
            <?php endif; ?>
            <div style="margin-bottom: 12px;">
                <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700;">
                    <a href="<?php echo base_url('admin/student_profile/' . $student_id); ?>" target="_blank" style="color: #1f2937; text-decoration: none;" onmouseover="this.style.color='#667eea';" onmouseout="this.style.color='#1f2937';">
                        <?php echo $student['name']; ?>
                    </a>
                </h4>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <p style="margin: 0; color: #6b7280; font-size: 13px;"><?php echo $student['student_code']; ?></p>
                    <?php if($has_payment): ?>
                    <div class="payment-indicator">
                        <i class="fa fa-check-circle"></i>
                        <?php echo $payment_type; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($has_discount): ?>
            <div class="discount-badge">
                <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 700; color: #065f46;">
                    <i class="fa fa-gift"></i> <?php echo get_phrase('discount_applied'); ?>
                </p>
                <?php if($feeding_discount > 0): ?>
                    <?php 
                    $feeding_discount_display = $feeding_info['discount_method'] == 'percentage' 
                        ? $feeding_info['discount_value'] . '%' 
                        : $currency . number_format($feeding_discount, 2);
                    ?>
                    <p style="margin: 3px 0; font-size: 12px; color: #059669;">
                        <strong>Feeding:</strong> <?php echo $feeding_discount_display; ?><?php if($hide_feeding): ?> <span style="color: #10b981; font-weight: 700;">(FREE)</span><?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if($breakfast_discount > 0): ?>
                    <?php 
                    $breakfast_discount_display = $breakfast_info['discount_method'] == 'percentage' 
                        ? $breakfast_info['discount_value'] . '%' 
                        : $currency . number_format($breakfast_discount, 2);
                    ?>
                    <p style="margin: 3px 0; font-size: 12px; color: #059669;">
                        <strong>Breakfast:</strong> <?php echo $breakfast_discount_display; ?><?php if($hide_breakfast): ?> <span style="color: #10b981; font-weight: 700;">(FREE)</span><?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if($classes_discount > 0): ?>
                    <?php 
                    $classes_discount_display = $classes_info['discount_method'] == 'percentage' 
                        ? $classes_info['discount_value'] . '%' 
                        : $currency . number_format($classes_discount, 2);
                    ?>
                    <p style="margin: 3px 0; font-size: 12px; color: #059669;">
                        <strong>Classes:</strong> <?php echo $classes_discount_display; ?><?php if($hide_classes): ?> <span style="color: #10b981; font-weight: 700;">(FREE)</span><?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if($water_discount > 0): ?>
                    <?php 
                    $water_discount_display = $water_info['discount_method'] == 'percentage' 
                        ? $water_info['discount_value'] . '%' 
                        : $currency . number_format($water_discount, 2);
                    ?>
                    <p style="margin: 3px 0; font-size: 12px; color: #059669;">
                        <strong>Water:</strong> <?php echo $water_discount_display; ?><?php if($hide_water): ?> <span style="color: #10b981; font-weight: 700;">(FREE)</span><?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px;"><?php echo get_phrase('attendance_status'); ?></label>
                <select name="attendance[<?php echo $student_id; ?>]" class="status-select" data-student="<?php echo $student_id; ?>" onchange="togglePermissionReason(<?php echo $student_id; ?>, this.value)">
                    <option value="1" <?php echo $current_status == 1 ? 'selected' : ''; ?>>Present (P)</option>
                    <option value="2" <?php echo $current_status == 2 ? 'selected' : ''; ?>>Absent (A)</option>
                    <option value="3" <?php echo $current_status == 3 ? 'selected' : ''; ?>>Late (L)</option>
                    <option value="4" <?php echo $current_status == 4 ? 'selected' : ''; ?>>Sick-Home (S)</option>
                    <option value="5" <?php echo $current_status == 5 ? 'selected' : ''; ?>>Sick-Clinic (C)</option>
                    <option value="6" <?php echo $current_status == 6 ? 'selected' : ''; ?>>Excused (E)</option>
                </select>
            </div>
            
            <!-- Permission Reason field (only shown when Excused is selected) -->
            <div id="permission_reason_<?php echo $student_id; ?>" style="margin-bottom: 12px; display: <?php echo $current_status == 6 ? 'block' : 'none'; ?>;">
                <label style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: #667eea;">
                    <i class="fa fa-comment"></i> <?php echo get_phrase('reason_for_excused_absence'); ?>
                </label>
                <textarea 
                    name="permission_reason[<?php echo $student_id; ?>]" 
                    class="fee-input" 
                    rows="2" 
                    placeholder="<?php echo get_phrase('enter_reason'); ?>"
                    style="resize: vertical; min-height: 60px; font-size: 13px;"
                ><?php echo htmlspecialchars($existing['permission_reason'] ?? ''); ?></textarea>
                <small style="color: #6b7280; font-size: 11px; display: block; margin-top: 4px;">
                    <i class="fa fa-info-circle"></i> <?php echo get_phrase('e_g_medical_appointment_family_emergency'); ?>
                </small>
            </div>
            
            <?php if($has_full_discount_all_fees): ?>
            <!-- Student has 100% discount on all daily fees - no fee collection needed -->
            <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border: 2px solid #10b981; border-radius: 8px; padding: 12px; margin-bottom: 12px; text-align: center;">
                <div style="font-size: 24px; color: #059669; margin-bottom: 6px;">
                    <i class="fa fa-gift"></i>
                </div>
                <div style="font-size: 13px; font-weight: 700; color: #065f46; margin-bottom: 4px;">
                    100% DISCOUNT APPLIED
                </div>
                <div style="font-size: 11px; color: #047857;">
                    No fees required for this student
                </div>
            </div>
            <?php endif; ?>
            
            <?php if($show_fee_collection && !$has_full_discount_all_fees): ?>
            <div class="fee-section">
                <?php 
                // Get student preferences for optional fee items (breakfast, water)
                $student_prefs = $this->db->get_where('student_daily_fee_preferences', ['student_id' => $student_id])->row();
                $breakfast_subscribed = $student_prefs ? ($student_prefs->breakfast_subscribed ?? 0) : 0;
                $water_subscribed = $student_prefs ? ($student_prefs->water_subscribed ?? 1) : 1;
                
                // Check if water was already paid this week (water is charged once per week)
                $water_paid_this_week = in_array($student_id, $students_water_paid_this_week);
                
                $breakfast_enabled = !isset($fee_rates['breakfast_enabled']) || $fee_rates['breakfast_enabled'];
                $water_enabled = !isset($fee_rates['water_enabled']) || $fee_rates['water_enabled'];
                ?>
                
                <?php if($permissions['can_collect_feeding'] && in_array('feeding', $enabled_modules) && !$hide_feeding): ?>
                <div class="feeding-fee fee-input-row" style="margin-bottom: 8px;">
                    <?php if($feeding_discount > 0): ?>
                    <div style="background: #d1fae5; padding: 4px 8px; border-radius: 4px; margin-bottom: 4px; font-size: 11px; color: #065f46;">
                        <i class="fa fa-gift"></i> Discount: <?php echo $currency . number_format($feeding_discount, 2); ?> off
                    </div>
                    <?php endif; ?>
                    <label style="font-size: 12px; font-weight: 600; color: #374151;">Feeding <?php if($feeding_final > 0): ?>(<?php echo $currency . number_format($feeding_final, 2); ?>)<?php endif; ?></label>
                    
                    <!-- Input and Balance Row -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px;">
                        <div>
                            <input type="number" name="fees[<?php echo $student_id; ?>][feeding_paid]" step="0.01" class="fee-input" placeholder="<?php echo $feeding_final; ?>" value="<?php echo $feeding_paid !== null ? $feeding_paid : ''; ?>" data-student="<?php echo $student_id; ?>" data-type="feeding" data-charge="<?php echo $feeding_final; ?>" data-arrears="<?php echo $feeding_arrears; ?>" data-prepaid="<?php echo $feeding_balance; ?>" data-is-first-time="<?php echo $is_first_time ? '1' : '0'; ?>" data-original-paid="<?php echo $feeding_paid !== null ? $feeding_paid : '0'; ?>" data-original-status="<?php echo $current_status; ?>" <?php if($feeding_paid !== null && $feeding_paid > 0) echo 'style="background: #d1fae5;"'; ?>>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <div style="background: <?php echo $display_feeding_prepaid > 0 ? '#d1fae5' : '#dbeafe'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_feeding_prepaid > 0 ? '#065f46' : '#1e40af'; ?>; font-size: 10px; text-align: center;">
                                <i class="fa fa-plus-circle"></i> Prepaid: <span id="prepaid_feeding_<?php echo $student_id; ?>"><?php echo number_format($display_feeding_prepaid, 2); ?></span>
                            </div>
                            <div style="background: <?php echo $display_feeding_arrears > 0 ? '#fee2e2' : '#d1fae5'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_feeding_arrears > 0 ? '#991b1b' : '#065f46'; ?>; font-size: 10px; text-align: center;">
                                <i class="fa fa-exclamation-circle"></i> Arrears: <span id="arrears_feeding_<?php echo $student_id; ?>"><?php echo number_format($display_feeding_arrears, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($permissions['can_collect_breakfast'] && in_array('breakfast', $enabled_modules) && $breakfast_enabled && !$hide_breakfast): ?>
                <div class="breakfast-fee fee-input-row" style="margin-bottom: 8px;">
                    <!-- Breakfast Opt-In Toggle -->
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; padding: 6px 10px; background: #fef3c7; border-radius: 6px; border: 1px solid #fbbf24;">
                        <label style="font-size: 11px; font-weight: 600; color: #92400e; margin: 0; display: flex; align-items: center; gap: 6px;">
                            <i class="fa fa-coffee"></i> Opt-in for Breakfast
                        </label>
                        <label class="toggle-switch" style="margin: 0;">
                            <!-- Hidden input ensures breakfast_opted is always sent (0 if unchecked, 1 if checked) -->
                            <input type="hidden" name="fees[<?php echo $student_id; ?>][breakfast_opted]" value="0">
                            <input type="checkbox" name="fees[<?php echo $student_id; ?>][breakfast_opted]" class="breakfast-toggle" data-student="<?php echo $student_id; ?>" value="1" <?php echo $breakfast_subscribed ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="breakfast-content" style="<?php echo !$breakfast_subscribed ? 'display: none;' : ''; ?>">
                        <?php if($breakfast_discount > 0): ?>
                        <div style="background: #d1fae5; padding: 4px 8px; border-radius: 4px; margin-bottom: 4px; font-size: 11px; color: #065f46;">
                            <i class="fa fa-gift"></i> Discount: <?php echo $currency . number_format($breakfast_discount, 2); ?> off
                        </div>
                        <?php endif; ?>
                        <label style="font-size: 12px; font-weight: 600; color: #374151;">Breakfast <?php if($breakfast_final > 0): ?>(<?php echo $currency . number_format($breakfast_final, 2); ?>)<?php endif; ?></label>
                        
                        <!-- Input and Balance Row -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px;">
                            <div>
                                <input type="number" name="fees[<?php echo $student_id; ?>][breakfast_paid]" step="0.01" class="fee-input breakfast-input" placeholder="<?php echo $breakfast_final; ?>" value="<?php echo $breakfast_paid !== null ? $breakfast_paid : ''; ?>" data-student="<?php echo $student_id; ?>" data-type="breakfast" data-charge="<?php echo $breakfast_final; ?>" data-arrears="<?php echo $breakfast_arrears; ?>" data-prepaid="<?php echo $breakfast_balance; ?>" data-is-first-time="<?php echo $is_first_time ? '1' : '0'; ?>" data-original-paid="<?php echo $breakfast_paid !== null ? $breakfast_paid : '0'; ?>" data-original-status="<?php echo $current_status; ?>" <?php if($breakfast_paid !== null && $breakfast_paid > 0) echo 'style="background: #d1fae5;"'; ?>>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <div style="background: <?php echo $display_breakfast_prepaid > 0 ? '#d1fae5' : '#dbeafe'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_breakfast_prepaid > 0 ? '#065f46' : '#1e40af'; ?>; font-size: 10px; text-align: center;">
                                    <i class="fa fa-plus-circle"></i> Prepaid: <span id="prepaid_breakfast_<?php echo $student_id; ?>"><?php echo number_format($display_breakfast_prepaid, 2); ?></span>
                                </div>
                                <div style="background: <?php echo $display_breakfast_arrears > 0 ? '#fee2e2' : '#d1fae5'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_breakfast_arrears > 0 ? '#991b1b' : '#065f46'; ?>; font-size: 10px; text-align: center;">
                                    <i class="fa fa-exclamation-circle"></i> Arrears: <span id="arrears_breakfast_<?php echo $student_id; ?>"><?php echo number_format($display_breakfast_arrears, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($permissions['can_collect_classes'] && in_array('classes', $enabled_modules) && !$hide_classes): ?>
                <div class="classes-fee fee-input-row" style="margin-bottom: 8px;">
                    <?php if($classes_discount > 0): ?>
                    <div style="background: #d1fae5; padding: 4px 8px; border-radius: 4px; margin-bottom: 4px; font-size: 11px; color: #065f46;">
                        <i class="fa fa-gift"></i> Discount: <?php echo $currency . number_format($classes_discount, 2); ?> off
                    </div>
                    <?php endif; ?>
                    <label style="font-size: 12px; font-weight: 600; color: #374151;">Classes <?php if($classes_final > 0): ?>(<?php echo $currency . number_format($classes_final, 2); ?>)<?php endif; ?></label>
                    
                    <!-- Input and Balance Row -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px;">
                        <div>
                            <input type="number" name="fees[<?php echo $student_id; ?>][classes_paid]" step="0.01" class="fee-input" placeholder="<?php echo $classes_final; ?>" value="<?php echo $classes_paid !== null ? $classes_paid : ''; ?>" data-student="<?php echo $student_id; ?>" data-type="classes" data-charge="<?php echo $classes_final; ?>" data-arrears="<?php echo $classes_arrears; ?>" data-prepaid="<?php echo $classes_balance; ?>" data-is-first-time="<?php echo $is_first_time ? '1' : '0'; ?>" data-original-paid="<?php echo $classes_paid !== null ? $classes_paid : '0'; ?>" data-original-status="<?php echo $current_status; ?>" <?php if($classes_paid !== null && $classes_paid > 0) echo 'style="background: #d1fae5;"'; ?>>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <div style="background: <?php echo $display_classes_prepaid > 0 ? '#d1fae5' : '#dbeafe'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_classes_prepaid > 0 ? '#065f46' : '#1e40af'; ?>; font-size: 10px; text-align: center;">
                                <i class="fa fa-plus-circle"></i> Prepaid: <span id="prepaid_classes_<?php echo $student_id; ?>"><?php echo number_format($display_classes_prepaid, 2); ?></span>
                            </div>
                            <div style="background: <?php echo $display_classes_arrears > 0 ? '#fee2e2' : '#d1fae5'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_classes_arrears > 0 ? '#991b1b' : '#065f46'; ?>; font-size: 10px; text-align: center;">
                                <i class="fa fa-exclamation-circle"></i> Arrears: <span id="arrears_classes_<?php echo $student_id; ?>"><?php echo number_format($display_classes_arrears, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if($permissions['can_collect_water'] && in_array('water', $enabled_modules) && $water_enabled && $water_subscribed && !$hide_water): ?>
                <div class="water-fee fee-input-row" style="margin-bottom: 8px; <?php if($water_paid_this_week) echo 'position: relative;'; ?>">
                    <?php if($water_paid_this_week): ?>
                    <div style="position: absolute; top: 0; left: 0; right: 0; background: #10b981; color: white; text-align: center; padding: 3px; font-size: 10px; font-weight: 600; border-radius: 4px 4px 0 0; z-index: 10;">
                        ✓ PAID THIS WEEK
                    </div>
                    <div style="padding-top: 20px;"></div>
                    <?php endif; ?>
                    
                    <?php if($water_discount > 0): ?>
                    <div style="background: #d1fae5; padding: 4px 8px; border-radius: 4px; margin-bottom: 4px; font-size: 11px; color: #065f46;">
                        <i class="fa fa-gift"></i> Discount: <?php echo $currency . number_format($water_discount, 2); ?> off
                    </div>
                    <?php endif; ?>
                    <label style="font-size: 12px; font-weight: 600; color: #374151;">Water <?php if($water_final > 0): ?>(<?php echo $currency . number_format($water_final, 2); ?>)<?php endif; ?></label>
                    
                    <!-- Input and Balance Row -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px;">
                        <div>
                            <input type="number" name="fees[<?php echo $student_id; ?>][water_paid]" step="0.01" class="fee-input" placeholder="<?php echo $water_final; ?>" value="<?php echo $water_paid !== null ? $water_paid : ''; ?>" data-student="<?php echo $student_id; ?>" data-type="water" data-charge="<?php echo $water_final; ?>" data-arrears="<?php echo $water_arrears; ?>" data-prepaid="<?php echo $water_balance; ?>" data-is-first-time="<?php echo $is_first_time ? '1' : '0'; ?>" data-original-paid="<?php echo $water_paid !== null ? $water_paid : '0'; ?>" data-original-status="<?php echo $current_status; ?>" <?php if($water_paid !== null && $water_paid > 0) echo 'style="background: #d1fae5;"'; ?> <?php if($water_paid_this_week) echo 'disabled readonly style="background: #f3f4f6; cursor: not-allowed; opacity: 0.6;"'; ?>>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <div style="background: <?php echo $display_water_prepaid > 0 ? '#d1fae5' : '#dbeafe'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_water_prepaid > 0 ? '#065f46' : '#1e40af'; ?>; font-size: 10px; text-align: center;">
                                <i class="fa fa-plus-circle"></i> Prepaid: <span id="prepaid_water_<?php echo $student_id; ?>"><?php echo number_format($display_water_prepaid, 2); ?></span>
                            </div>
                            <div style="background: <?php echo $display_water_arrears > 0 ? '#fee2e2' : '#d1fae5'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_water_arrears > 0 ? '#991b1b' : '#065f46'; ?>; font-size: 10px; text-align: center;">
                                <i class="fa fa-exclamation-circle"></i> Arrears: <span id="arrears_water_<?php echo $student_id; ?>"><?php echo number_format($display_water_arrears, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php 
                // Transport fee section
                $hide_transport = false;
                if($permissions['can_collect_transport'] && in_array('transport', $enabled_modules) && $student['transport_id'] > 0): 
                    $transport = $this->db->get_where('transport', ['transport_id' => $student['transport_id']])->row();
                    $transport_rate = $transport ? $transport->route_fare : 0;
                    $transport_total_owing = $transport_arrears + $transport_rate;
                    
                    // Get transport discount
                    $transport_discount_data = $this->Discount_model->get_student_discount_for_date($student_id, $running_year, $running_term, $class_id, 'transport', $timestamp);
                    $transport_discount = $transport_discount_data['discount_value'] ?? 0;
                    $transport_final = max(0, $transport_rate - $transport_discount);
                    
                    // Determine if transport should be hidden (100% discount)
                    $hide_transport = ($transport_discount >= $transport_rate);
                    
                    // Check existing bus attendance for today
                    $bus_attendance = $this->db->get_where('bus_attendance', [
                        'student_id' => $student_id,
                        'attendance_date' => $timestamp
                    ])->row();
                    $current_direction = ($bus_attendance && isset($bus_attendance->direction)) ? $bus_attendance->direction : 'none';
                    $is_boarded = ($bus_attendance && isset($bus_attendance->status)) ? ($bus_attendance->status == 'boarded') : false;
                ?>
                <?php if(!$hide_transport): ?>
                <div class="transport-fee fee-input-row" style="margin-bottom: 8px;">
                    <?php if($transport_discount > 0): ?>
                    <div style="background: #d1fae5; padding: 4px 8px; border-radius: 4px; margin-bottom: 4px; font-size: 11px; color: #065f46;">
                        <i class="fa fa-gift"></i> Discount: <?php echo $currency . number_format($transport_discount, 2); ?> off
                    </div>
                    <?php endif; ?>
                    
                    <!-- Transport Direction Selector -->
                    <div style="margin-bottom: 8px;">
                        <label style="font-size: 11px; font-weight: 600; color: #6b7280; display: block; margin-bottom: 4px;">
                            <i class="fa fa-bus"></i> Transport Direction
                        </label>
                        <!-- Hidden input ensures parameter is sent even if select is not changed -->
                        <input type="hidden" name="fees[<?php echo $student_id; ?>][transport_direction]" value="none">
                        <select name="fees[<?php echo $student_id; ?>][transport_direction]" class="form-control" style="padding: 6px 10px; font-size: 12px; border-radius: 6px;" data-student="<?php echo $student_id; ?>">
                            <option value="none" <?php echo $current_direction == 'none' ? 'selected' : ''; ?>>Not Using Transport</option>
                            <option value="in" <?php echo $current_direction == 'in' ? 'selected' : ''; ?>>Morning In (Pickup)</option>
                            <option value="out" <?php echo $current_direction == 'out' ? 'selected' : ''; ?>>Afternoon Out (Drop-off)</option>
                            <option value="both" <?php echo $current_direction == 'both' ? 'selected' : ''; ?>>Both Ways (Round Trip)</option>
                        </select>
                    </div>
                    
                    <!-- Boarded Checkbox -->
                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="fees[<?php echo $student_id; ?>][transport_boarded]" value="1" <?php echo $is_boarded ? 'checked' : ''; ?> id="boarded_<?php echo $student_id; ?>" style="width: 16px; height: 16px;">
                        <label for="boarded_<?php echo $student_id; ?>" style="font-size: 11px; font-weight: 600; color: #374151; margin: 0;">
                            <i class="fa fa-check-circle"></i> Student Boarded Bus
                        </label>
                    </div>
                    
                    <label style="font-size: 12px; font-weight: 600; color: #374151;">Transport <?php if($transport_final > 0): ?>(<?php echo $currency . number_format($transport_final, 2); ?>/trip)<?php endif; ?></label>
                    
                    <!-- Input and Balance Row -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px;">
                        <div>
                            <input type="number" name="fees[<?php echo $student_id; ?>][transport_paid]" step="0.01" class="fee-input" placeholder="<?php echo $transport_final; ?>" value="<?php echo $transport_paid !== null ? $transport_paid : ''; ?>" data-student="<?php echo $student_id; ?>" data-type="transport" data-charge="<?php echo $transport_final; ?>" data-arrears="<?php echo $transport_arrears; ?>" data-prepaid="<?php echo $transport_balance; ?>" data-is-first-time="<?php echo $is_first_time ? '1' : '0'; ?>" data-original-paid="<?php echo $transport_paid !== null ? $transport_paid : '0'; ?>" data-original-status="<?php echo $current_status; ?>" <?php if($transport_paid !== null && $transport_paid > 0) echo 'style="background: #d1fae5;"'; ?>>
                            <input type="hidden" name="fees[<?php echo $student_id; ?>][transport_id]" value="<?php echo $student['transport_id']; ?>">
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <div style="background: <?php echo $display_transport_prepaid > 0 ? '#d1fae5' : '#dbeafe'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_transport_prepaid > 0 ? '#065f46' : '#1e40af'; ?>; font-size: 10px; text-align: center;">
                                <i class="fa fa-plus-circle"></i> Prepaid: <span id="prepaid_transport_<?php echo $student_id; ?>"><?php echo number_format($display_transport_prepaid, 2); ?></span>
                            </div>
                            <div style="background: <?php echo $display_transport_arrears > 0 ? '#fee2e2' : '#d1fae5'; ?>; padding: 2px 6px; border-radius: 4px; color: <?php echo $display_transport_arrears > 0 ? '#991b1b' : '#065f46'; ?>; font-size: 10px; text-align: center;">
                                <i class="fa fa-exclamation-circle"></i> Arrears: <span id="arrears_transport_<?php echo $student_id; ?>"><?php echo number_format($display_transport_arrears, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <input type="hidden" name="fees[<?php echo $student_id; ?>][feeding_charge]" value="<?php echo $feeding_final; ?>">
                <input type="hidden" name="fees[<?php echo $student_id; ?>][classes_charge]" value="<?php echo $classes_final; ?>">
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Collapsible FAB Save Button -->
    <div id="fab_container" style="position: fixed; bottom: 24px; right: 24px; z-index: 100;">
        <button type="submit" id="fab_save_btn" class="btn-enterprise btn-primary fab-button">
            <i class="fa fa-save fab-icon"></i>
            <span class="fab-text"><?php echo get_phrase('save_attendance'); ?></span>
        </button>
    </div>
    
    <style>
        .fab-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            font-size: 18px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            overflow: hidden;
            min-width: 56px;
            height: 56px;
            cursor: pointer;
        }
        
        .fab-button:hover {
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
            transform: scale(1.05);
        }
        
        .fab-button.expanded {
            padding: 16px 32px;
        }
        
        .fab-icon {
            font-size: 20px;
            transition: margin-right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-right: 0;
        }
        
        .fab-button.expanded .fab-icon {
            margin-right: 8px;
        }
        
        .fab-text {
            max-width: 0;
            opacity: 0;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
        }
        
        .fab-button.expanded .fab-text {
            max-width: 200px;
            opacity: 1;
            margin-left: 0;
        }
        
        /* Animation for pulse effect on page load */
        @keyframes fab-pulse {
            0%, 100% {
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            }
            50% {
                box-shadow: 0 4px 30px rgba(102, 126, 234, 0.6);
            }
        }
        
        .fab-button.pulse {
            animation: fab-pulse 2s ease-in-out 3;
        }
    </style>
    <?php echo form_close(); ?>
</div>

<script>
$(document).ready(function() {
    // FAB (Floating Action Button) behavior
    var $fabBtn = $('#fab_save_btn');
    var isExpanded = false;
    var collapseTimer = null;
    
    // Add pulse animation on page load to draw attention
    setTimeout(function() {
        $fabBtn.addClass('pulse');
        setTimeout(function() {
            $fabBtn.removeClass('pulse');
        }, 6000);
    }, 500);
    
    // Expand on hover
    $fabBtn.on('mouseenter', function() {
        clearTimeout(collapseTimer);
        if (!isExpanded) {
            $(this).addClass('expanded');
            isExpanded = true;
        }
    });
    
    // Collapse after mouse leaves (with delay)
    $fabBtn.on('mouseleave', function() {
        collapseTimer = setTimeout(function() {
            $fabBtn.removeClass('expanded');
            isExpanded = false;
        }, 1000);
    });
    
    // Toggle on click (mobile friendly)
    $fabBtn.on('click', function(e) {
        // Don't prevent default - we want the form to submit
        // But expand if collapsed for visual feedback
        if (!isExpanded) {
            e.preventDefault();
            clearTimeout(collapseTimer);
            $(this).addClass('expanded');
            isExpanded = true;
            
            // Submit after showing expanded state
            setTimeout(function() {
                $fabBtn.closest('form').submit();
            }, 300);
        }
    });
    
    // Status color theming - apply initial colors and handle changes
    function applyStatusColor($select) {
        var status = $select.val();
        // Remove all status classes
        $select.removeClass('status-1 status-2 status-3 status-4 status-5 status-6');
        // Add current status class
        if (status) {
            $select.addClass('status-' + status);
        }
    }
    
    // Apply colors on page load
    $('.status-select').each(function() {
        applyStatusColor($(this));
    });
    
    // Apply colors when status changes
    $('.status-select').on('change', function() {
        applyStatusColor($(this));
    });
    
    toggleFeeInputs();
    updateTotals();
    
    // Populate class and date from URL parameters
    <?php if($class_id): ?>
    $('#filter_class').val('<?php echo $class_id; ?>');
    <?php endif; ?>
    
    // DO NOT recalculate balances on page load
    // The prepaid/arrears values are already set from the database in the PHP
    // JavaScript should only handle status changes and payment input changes
    
    // Breakfast toggle handler
    $('.breakfast-toggle').on('change', function() {
        const student = $(this).data('student');
        const isChecked = $(this).is(':checked');
        const $card = $(this).closest('.student-card');
        const $breakfastContent = $card.find('.breakfast-content');
        const $breakfastInput = $card.find('.breakfast-input');
        
        if (isChecked) {
            // Show breakfast section
            $breakfastContent.slideDown(200);
        } else {
            // Hide breakfast section and clear payment
            $breakfastContent.slideUp(200);
            $breakfastInput.val('');
            // Update balance preview and totals
            updateBalancePreview(student);
            updateTotals();
        }
    });
    
    // Sticky dashboard scroll behavior
    var $dashboard = $('#fee_dashboard');
    var $dashboardTitle = $('#fee_dashboard_title');
    var dashboardOffset = $dashboard.offset ? $dashboard.offset().top : 0;
    var headerHeight = 80; // Approximate header height
    
    // Initially show the title when page loads
    $dashboardTitle.show();
    
    $(window).on('scroll', function() {
        var scrollTop = $(this).scrollTop();
        
        if (scrollTop > dashboardOffset - headerHeight) {
            // Dashboard is sticky - hide title to give more space for filters
            $dashboard.css({
                'top': headerHeight + 'px',
                'box-shadow': '0 4px 20px rgba(0,0,0,0.15)'
            });
            $dashboardTitle.hide();
        } else {
            // Dashboard is at normal position - show title
            $dashboard.css({
                'top': '0',
                'box-shadow': '0 2px 8px rgba(0,0,0,0.08)'
            });
            $dashboardTitle.show();
        }
    });
});

$('#student_filter').on('input', function() {
    const filter = $(this).val().toLowerCase();
    $('.student-card').each(function() {
        const name = $(this).data('student-name').toLowerCase();
        $(this).toggle(name.includes(filter));
    });
});

$('.status-select').on('change', function() {
    const student = $(this).data('student');
    const status = $(this).val();
    const isAbsent = (status != '1' && status != '3');
    
    // REMOVED: No longer disable payment fields when absent
    // Parents can still make payments even if student is absent
    // Payment will be added to prepaid balance
    
    // Clear values when changing to absent (optional - user can still enter amount)
    if(isAbsent) {
        // Just remove the highlight, don't disable or clear
        $(`.fee-input[data-student="${student}"]`).removeClass('bg-gray-100');
    } else {
        $(`.fee-input[data-student="${student}"]`).removeClass('bg-gray-100');
    }
    
    // CRITICAL: Update balance preview when status changes
    // This recalculates balances based on new status (present/absent)
    updateBalancePreview(student);
    
    // Update totals when status changes
    updateTotals();
});

$('.fee-input').on('input', function() {
    const student = $(this).data('student');
    updateBalancePreview(student);
    updateTotals();
});

/**
 * Update balance preview when payment amount changes
 * This shows the PROJECTED balances after the payment is saved
 * Uses the original wallet values from database as the base
 */
function updateBalancePreview(student) {
    // Check if student is absent (status is not Present or Late)
    const statusSelect = $(`.status-select[data-student="${student}"]`);
    const status = statusSelect.val();
    const isAbsent = (status != '1' && status != '3');
    
    $('.fee-input[data-student="' + student + '"]').each(function() {
        const type = $(this).data('type');
        const charge = parseFloat($(this).data('charge')) || 0;
        const paid = parseFloat($(this).val()) || 0;
        const isFirstTime = $(this).data('is-first-time') == '1';
        const originalPaid = parseFloat($(this).data('original-paid')) || 0;
        
        // Get the ORIGINAL wallet values from data attributes
        // These are set by PHP and represent the actual database values
        const walletPrepaid = parseFloat($(this).data('prepaid')) || 0;
        const walletArrears = parseFloat($(this).data('arrears')) || 0;
        
        // STEP 1: Get the TRUE original wallet state (before today's charge and payment)
        // The wallet values from DB depend on the original status:
        // - If originally ABSENT: Wallet = original_wallet + original_payment (NO charge applied)
        // - If originally PRESENT/LATE: Wallet = original_wallet - charge + original_payment (charge WAS applied)
        let basePrepaid = walletPrepaid;
        let baseArrears = walletArrears;
        
        if (!isFirstTime) {
            // This is an edit - need to get back to the original wallet state
            const originalStatus = $(this).data('original-status');
            const wasAbsent = (originalStatus != '1' && originalStatus != '3');
            
            // Step 1a: Reverse the original payment
            if (originalPaid > 0) {
                if (originalPaid <= walletPrepaid) {
                    // Original payment went entirely to prepaid, just subtract it
                    basePrepaid = walletPrepaid - originalPaid;
                } else {
                    // Original payment reduced arrears and added to prepaid
                    // Reverse: remove prepaid, add back to arrears
                    const arrearsReduction = originalPaid - walletPrepaid;
                    basePrepaid = 0;
                    baseArrears = walletArrears + arrearsReduction;
                }
            }
            
            // Step 1b: Add back the charge ONLY if it was originally applied
            // If student was originally Present/Late, the charge was applied
            // If student was originally Absent, NO charge was applied
            if (!wasAbsent && charge > 0) {
                // Charge was applied - add it back to get original wallet
                if (charge <= baseArrears) {
                    // Charge came from arrears, reduce arrears
                    baseArrears = baseArrears - charge;
                } else if (baseArrears > 0) {
                    // Charge partially from arrears, rest from prepaid
                    const fromPrepaid = charge - baseArrears;
                    baseArrears = 0;
                    basePrepaid = basePrepaid + fromPrepaid;
                } else {
                    // Charge came entirely from prepaid
                    basePrepaid = basePrepaid + charge;
                }
            }
            // If wasAbsent is true, we don't add back the charge because it was never applied
        }
        
        // STEP 2: Calculate effective balances after applying today's charge
        let effectivePrepaid = basePrepaid;
        let effectiveArrears = baseArrears;
        
        // Apply today's charge ONLY if student is Present/Late
        if (!isAbsent) {
            // Student is Present/Late - today's charge applies
            if (basePrepaid >= charge) {
                // Prepaid can cover the full charge
                effectivePrepaid = basePrepaid - charge;
                effectiveArrears = baseArrears;
            } else if (basePrepaid > 0) {
                // Prepaid can partially cover the charge
                effectivePrepaid = 0;
                effectiveArrears = baseArrears + (charge - basePrepaid);
            } else {
                // No prepaid, charge goes to arrears
                effectivePrepaid = 0;
                effectiveArrears = baseArrears + charge;
            }
        }
        
        // STEP 3: Apply the NEW payment amount
        let projectedPrepaid = effectivePrepaid;
        let projectedArrears = effectiveArrears;
        
        if (paid > 0) {
            // Payment reduces arrears first, then adds to prepaid
            if (paid >= effectiveArrears) {
                // Payment covers all arrears, excess goes to prepaid
                projectedPrepaid = effectivePrepaid + (paid - effectiveArrears);
                projectedArrears = 0;
            } else {
                // Payment partially covers arrears
                projectedArrears = effectiveArrears - paid;
                projectedPrepaid = effectivePrepaid;
            }
        }
        
        // Update prepaid and arrears display
        const prepaidSpan = $(`#prepaid_${type}_${student}`);
        const arrearsSpan = $(`#arrears_${type}_${student}`);
        
        if (prepaidSpan.length) {
            prepaidSpan.text(projectedPrepaid.toFixed(2));
            // Update parent div color based on value
            const prepaidDiv = prepaidSpan.parent();
            if (projectedPrepaid > 0) {
                prepaidDiv.css('background', '#d1fae5').css('color', '#065f46');
            } else {
                prepaidDiv.css('background', '#dbeafe').css('color', '#1e40af');
            }
        }
        
        if (arrearsSpan.length) {
            arrearsSpan.text(projectedArrears.toFixed(2));
            // Update parent div color based on value
            const arrearsDiv = arrearsSpan.parent();
            if (projectedArrears > 0) {
                arrearsDiv.css('background', '#fee2e2').css('color', '#991b1b');
            } else {
                arrearsDiv.css('background', '#d1fae5').css('color', '#065f46');
            }
        }
    });
}

function updateTotals() {
    const feeTypes = ['feeding', 'classes', 'transport', 'breakfast', 'water'];
    
    feeTypes.forEach(function(type) {
        let total = 0;
        $(`.fee-input[data-type="${type}"]:not(:disabled)`).each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $(`#total_${type}`).text('<?php echo $currency; ?> ' + total.toFixed(2));
    });
}

// Toggle fee inputs for all fee types
$('#collect_feeding, #collect_classes, #collect_transport, #collect_breakfast, #collect_water').on('change', toggleFeeInputs);

function toggleFeeInputs() {
    const feeTypes = ['feeding', 'classes', 'transport', 'breakfast', 'water'];
    
    feeTypes.forEach(function(type) {
        const checkbox = $(`#collect_${type}`);
        if(checkbox.length) {
            const isChecked = checkbox.is(':checked');
            $(`.${type}-fee`).toggle(isChecked);
            
            // Enable/disable inputs based on toggle
            $(`.${type}-fee .fee-input`).prop('disabled', !isChecked);
        }
    });
}

// Update transport charge based on direction selection
$('select[name$="[transport_direction]"]').on('change', function() {
    const studentId = $(this).data('student');
    const direction = $(this).val();
    const baseCharge = parseFloat($(`input[name="fees[${studentId}][transport_paid]"].fee-input`).data('charge')) || 0;
    
    let multiplier = 0;
    switch(direction) {
        case 'in':
        case 'out':
            multiplier = 1;
            break;
        case 'both':
            multiplier = 2;
            break;
        default:
            multiplier = 0;
    }
    
    const newCharge = baseCharge * multiplier;
    $(`input[name="fees[${studentId}][transport_paid]"].fee-input`).attr('placeholder', newCharge.toFixed(2));
    
    // Auto-check "boarded" checkbox when direction is selected (not 'none')
    if (direction !== 'none') {
        $(`#boarded_${studentId}`).prop('checked', true);
    } else {
        $(`#boarded_${studentId}`).prop('checked', false);
    }
    
    // Only update totals, do NOT modify prepaid/arrears display
    // The prepaid/arrears values come from the database
    updateTotals();
});

function markAllPresent() {
    $('.status-select').val('1').trigger('change');
}

function markAllAbsent() {
    $('.status-select').val('2').trigger('change');
}

/**
 * Toggle permission reason field based on attendance status
 * Shows the reason textarea only when "Excused" (status 6) is selected
 */
function togglePermissionReason(studentId, status) {
    const reasonDiv = $('#permission_reason_' + studentId);
    const reasonTextarea = reasonDiv.find('textarea');
    
    if(status == '6') {
        // Show reason field for Excused absence
        reasonDiv.slideDown(300);
        reasonTextarea.prop('required', true);
    } else {
        // Hide and clear reason field for other statuses
        reasonDiv.slideUp(300);
        reasonTextarea.prop('required', false);
        if(status != '6') {
            reasonTextarea.val(''); // Clear reason if not excused
        }
    }
}

$('#attendance_form').on('submit', function(e) {
    e.preventDefault();
    
    // Check if any students are selected (only if checkboxes are visible)
    if($('.student-select').length > 0) {
        const selectedCount = $('.student-select:checked').length;
        if(selectedCount === 0) {
            showAjaxModal_alert('<?php echo get_phrase('please_select_at_least_one_student'); ?>', 'error');
            return;
        }
    }
    
    showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
    
    // Build data object for selected students only
    const formData = {
        class_id: $('input[name="class_id"]').val(),
        section_id: $('input[name="section_id"]').val(),
        date: $('input[name="date"]').val(),
        attendance: {},
        permission_reason: {}, // Add permission reasons
        fees: {}
    };
    
    // Add CSRF token from form_open()
    const csrfToken = $('input[name^="csrf_"]');
    if(csrfToken.length) {
        formData[csrfToken.attr('name')] = csrfToken.val();
    }
    
    $('.student-card').each(function() {
        const studentId = $(this).data('student-id');
        const checkbox = $(this).find('.student-select');
        
        // If checkboxes exist and this student is not selected, skip
        if(checkbox.length > 0 && !checkbox.is(':checked')) {
            return;
        }
        
        // Add attendance status
        const status = $(this).find('select[name="attendance[' + studentId + ']"]').val();
        formData.attendance[studentId] = status;
        
        // Add permission reason if status is Excused (6)
        if(status == '6') {
            const reason = $(this).find('textarea[name="permission_reason[' + studentId + ']"]').val();
            formData.permission_reason[studentId] = reason || '';
        }
        
        // Add fee data if exists
        formData.fees[studentId] = {};
        $(this).find('input[name^="fees[' + studentId + ']"]').each(function() {
            const name = $(this).attr('name');
            const match = name.match(/fees\[\d+\]\[([^\]]+)\]/);
            if(match) {
                const fieldName = match[1];
                const value = $(this).val() || 0;
                formData.fees[studentId][fieldName] = value;
            }
        });
    });
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            // Show success message WITHOUT auto-close - modal will stay until page reloads
            showAjaxModal_alert(response.message, response.status, false, false);
            // Reload immediately - modal stays visible during reload
            location.reload();
        } else {
            // For errors, show with auto-close (default behavior)
            showAjaxModal_alert(response.message, response.status);
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
    });
});

function loadAttendance() {
    const classId = $('#filter_class').val();
    const date = $('#filter_date').val();
    
    if(!classId) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_class'); ?>');
        return;
    }
    
    // Show loading indicator
    const loadBtn = $('.btn-load');
    const originalHtml = loadBtn.html();
    loadBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo get_phrase("loading"); ?>...');
    
    const sectionId = $('#filter_class option:selected').data('section') || 1;
    window.location.href = '<?php echo site_url('attendance/mark'); ?>?class_id=' + classId + '&section_id=' + sectionId + '&date=' + date;
}

// Select all checkbox functionality
$('#select_all_container').on('click', function(e) {
    e.preventDefault();
    const checkbox = $('#select_all');
    const newState = !checkbox.is(':checked');
    checkbox.prop('checked', newState);
    $('.student-select').prop('checked', newState);
    updateStudentCardStyles();
});

$('.student-select').on('change', function() {
    updateStudentCardStyles();
    
    // Update select all checkbox
    const total = $('.student-select').length;
    const checked = $('.student-select:checked').length;
    $('#select_all').prop('checked', total === checked);
});

function updateStudentCardStyles() {
    $('.student-card').each(function() {
        const checkbox = $(this).find('.student-select');
        if(checkbox.length > 0) {
            if(checkbox.is(':checked')) {
                $(this).removeClass('unselected');
            } else {
                $(this).addClass('unselected');
            }
        }
    });
}

function printDiningCoupon() {
    const date = $('#filter_date').val();
    const loginType = '<?php echo $login_type; ?>';
    
    if(!date) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_a_date"); ?>', 'error');
        return;
    }
    
    // Show modal to select fee type
    var modalHtml = `
        <div id="coupon_fee_type_modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Print Coupon</h3>
                <div class="mb-4">
                    <label class="block text-base font-bold mb-2">Fee Type</label>
                    <select id="coupon_fee_type" class="w-full px-4 py-3 border-2 rounded-lg text-lg">
                        <option value="feeding">Feeding/Lunch</option>
                        <option value="breakfast">Breakfast</option>
                        <option value="transport">Transport</option>
                        <option value="classes">Classes</option>
                        <option value="water">Water</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button onclick="$('#coupon_fee_type_modal').remove();" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 rounded-lg text-lg">Cancel</button>
                    <button onclick="generateCouponWithFeeType()" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg text-lg">Generate</button>
                </div>
            </div>
        </div>
    `;
    $('body').append(modalHtml);
}

function generateCouponWithFeeType() {
    const date = $('#filter_date').val();
    const loginType = '<?php echo $login_type; ?>';
    const feeType = $('#coupon_fee_type').val();
    
    $('#coupon_fee_type_modal').remove();
    showAjaxModal_alert('<?php echo get_phrase("generating_coupon"); ?>...', 'loading');
    
    // Admin uses cashier endpoint with current class filter, teacher uses teacher endpoint
    var url, data;
    if(loginType === 'admin') {
        url = '<?php echo site_url("dining_coupon/generateCashierCoupon"); ?>';
        var classId = $('#filter_class').val();
        data = { date: date, class_filter: classId || 'all', fee_type: feeType };
    } else {
        url = '<?php echo site_url("dining_coupon/generateTeacherCoupon"); ?>';
        data = { date: date, fee_type: feeType };
    }
    
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert('<?php echo get_phrase("coupon_generated_successfully"); ?>', 'success');
            // Open print window
            const printWindow = window.open('', '_blank');
            printWindow.document.write(response.html);
            printWindow.document.close();
            setTimeout(function() {
                printWindow.print();
            }, 500);
        } else if(response.status === 'warning') {
            showAjaxModal_alert(response.message, 'warning');
        } else {
            showAjaxModal_alert(response.message || '<?php echo get_phrase("an_error_occurred"); ?>', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase("an_error_occurred"); ?>', 'error');
    });
}
</script>


<!-- Page Loading Preloader -->
<div id="attendance-page-loader">
    <div class="loader-spinner"></div>
    <div class="loader-text"><?php echo get_phrase('loading_attendance'); ?>...</div>
</div>

<script>
// Hide preloader when page is fully loaded
$(window).on('load', function() {
    setTimeout(function() {
        $('#attendance-page-loader').addClass('hidden');
        setTimeout(function() {
            $('#attendance-page-loader').remove();
        }, 300);
    }, 500);
});

// Also hide if taking too long (fallback)
setTimeout(function() {
    if($('#attendance-page-loader').length) {
        $('#attendance-page-loader').addClass('hidden');
        setTimeout(function() {
            $('#attendance-page-loader').remove();
        }, 300);
    }
}, 5000);
</script>
