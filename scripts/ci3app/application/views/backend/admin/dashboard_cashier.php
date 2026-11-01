<?php
/**
 * ENTERPRISE-GRADE CASHIER DASHBOARD
 * Modern professional UX with comprehensive KPIs
 */
$cashier_id = $this->session->userdata('admin_id');
$cashier_name = $this->db->get_where('admin', ['admin_id' => $cashier_id])->row()->name;
$today = strtotime(date('Y-m-d'));
$today_end = $today + 86400;
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');

// Today's collections
$today_collections = $this->db->query("
    SELECT 
        SUM(feeding_amount) as feeding,
        SUM(breakfast_amount) as breakfast,
        SUM(classes_amount) as classes,
        SUM(water_amount) as water,
        SUM(transport_amount) as transport,
        COUNT(*) as transaction_count,
        COUNT(DISTINCT student_id) as unique_students
    FROM daily_fee_transactions
    WHERE collected_by = ? AND payment_date >= ? AND payment_date < ?
", [$cashier_id, $today, $today_end])->row();

$total_collected = ($today_collections->feeding ?? 0) + ($today_collections->breakfast ?? 0) + 
                   ($today_collections->classes ?? 0) + ($today_collections->water ?? 0) + 
                   ($today_collections->transport ?? 0);

// Week & Month collections
$week_start = strtotime('monday this week');
$month_start = strtotime(date('Y-m-01'));
$week_collections = $this->db->query("SELECT SUM(feeding_amount + breakfast_amount + classes_amount + water_amount + transport_amount) as total FROM daily_fee_transactions WHERE collected_by = ? AND payment_date >= ?", [$cashier_id, $week_start])->row()->total ?? 0;
$month_collections = $this->db->query("SELECT SUM(feeding_amount + breakfast_amount + classes_amount + water_amount + transport_amount) as total FROM daily_fee_transactions WHERE collected_by = ? AND payment_date >= ?", [$cashier_id, $month_start])->row()->total ?? 0;

// This term collections
$running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
$running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
$term_start = $this->db->get_where('terms', ['year' => $running_year, 'term' => $running_term])->row();
$term_start_date = $term_start ? strtotime($term_start->next_term_begins) : $month_start;
$term_collections = $this->db->query("SELECT SUM(feeding_amount + breakfast_amount + classes_amount + water_amount + transport_amount) as total FROM daily_fee_transactions WHERE collected_by = ? AND payment_date >= ?", [$cashier_id, $term_start_date])->row()->total ?? 0;

$avg_transaction = $today_collections->transaction_count > 0 ? $total_collected / $today_collections->transaction_count : 0;

// Total outstanding arrears across active enrolled students (mute = '0') and enabled modules only
$arrears_query_parts = [];
$arrears_select_parts = [];

if ($feeding_enabled) {
    $arrears_query_parts[] = "w.feeding_arrears > 0";
    $arrears_select_parts[] = "SUM(w.feeding_arrears)";
} else {
    $arrears_select_parts[] = "0";
}

if ($breakfast_enabled) {
    $arrears_query_parts[] = "w.breakfast_arrears > 0";
    $arrears_select_parts[] = "SUM(w.breakfast_arrears)";
} else {
    $arrears_select_parts[] = "0";
}

if ($classes_enabled) {
    $arrears_query_parts[] = "w.classes_arrears > 0";
    $arrears_select_parts[] = "SUM(w.classes_arrears)";
} else {
    $arrears_select_parts[] = "0";
}

if ($water_enabled) {
    $arrears_query_parts[] = "w.water_arrears > 0";
    $arrears_select_parts[] = "SUM(w.water_arrears)";
} else {
    $arrears_select_parts[] = "0";
}

if ($transport_enabled) {
    $arrears_query_parts[] = "w.transport_arrears > 0";
    $arrears_select_parts[] = "SUM(w.transport_arrears)";
} else {
    $arrears_select_parts[] = "0";
}

$arrears_where = count($arrears_query_parts) > 0 ? "AND (" . implode(" OR ", $arrears_query_parts) . ")" : "";
$arrears_select = "(" . implode(" + ", $arrears_select_parts) . ") as total";

$total_arrears = $this->db->query("
    SELECT $arrears_select
    FROM daily_fee_wallet w
    INNER JOIN enroll e ON w.student_id = e.student_id
    WHERE e.year = ? AND e.term = ? AND e.mute = '0'
    $arrears_where
", [$running_year, $running_term])->row()->total ?? 0;

// Arrears breakdown by fee type (active enrolled students and enabled modules only)
$breakdown_selects = [];
if ($feeding_enabled) $breakdown_selects[] = "SUM(w.feeding_arrears) as feeding";
else $breakdown_selects[] = "0 as feeding";

if ($breakfast_enabled) $breakdown_selects[] = "SUM(w.breakfast_arrears) as breakfast";
else $breakdown_selects[] = "0 as breakfast";

if ($classes_enabled) $breakdown_selects[] = "SUM(w.classes_arrears) as classes";
else $breakdown_selects[] = "0 as classes";

if ($water_enabled) $breakdown_selects[] = "SUM(w.water_arrears) as water";
else $breakdown_selects[] = "0 as water";

if ($transport_enabled) $breakdown_selects[] = "SUM(w.transport_arrears) as transport";
else $breakdown_selects[] = "0 as transport";

$breakdown_select = implode(", ", $breakdown_selects);

$arrears_breakdown = $this->db->query("
    SELECT $breakdown_select
    FROM daily_fee_wallet w
    INNER JOIN enroll e ON w.student_id = e.student_id
    WHERE e.year = ? AND e.term = ? AND e.mute = '0'
", [$running_year, $running_term])->row();

// Payment types
$payment_types = $this->db->query("SELECT payment_type, COUNT(*) as count, SUM(feeding_amount + breakfast_amount + classes_amount + water_amount + transport_amount) as total FROM daily_fee_transactions WHERE collected_by = ? AND payment_date >= ? AND payment_date < ? GROUP BY payment_type", [$cashier_id, $today, $today_end])->result_array();

// Recent transactions
$recent_transactions = $this->db->query("SELECT t.*, s.name as student_name, s.student_code FROM daily_fee_transactions t JOIN student s ON t.student_id = s.student_id WHERE t.collected_by = ? AND t.payment_date >= ? ORDER BY t.created_at DESC LIMIT 10", [$cashier_id, $today])->result_array();
?>

<style>
.cashier-dashboard { background: #f8f9fa; padding: 20px; }
.stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 24px; color: white; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: pointer; }
.stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4); }
.stat-card.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 10px 30px rgba(56, 239, 125, 0.3); }
.stat-card.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); box-shadow: 0 10px 30px rgba(245, 87, 108, 0.3); }
.stat-card.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3); }
.stat-value { font-size: 36px; font-weight: 700; margin: 10px 0; }
.stat-value sup { font-size: 18px; font-weight: 600; margin-right: 4px; }
.stat-label { font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
.stat-icon { font-size: 48px; opacity: 0.3; position: absolute; right: 20px; top: 20px; }
.performance-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; min-height: 180px; display: flex; flex-direction: column; }
.performance-card h4 { color: #2d3748; font-weight: 600; margin-bottom: 15px; }
.fee-breakdown-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f7fafc; border-radius: 8px; margin-bottom: 10px; transition: all 0.3s ease; cursor: pointer; }
.fee-breakdown-item:hover { background: #edf2f7; transform: translateX(5px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.fee-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.transaction-table { width: 100%; }
.transaction-table th { background: #f7fafc; padding: 12px; text-align: left; font-weight: 600; color: #4a5568; }
.transaction-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
.transaction-table tr:hover { background: #f7fafc; }
.badge-modern { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.badge-success { background: #c6f6d5; color: #22543d; }
.badge-danger { background: #fed7d7; color: #742a2a; }
.badge-warning { background: #feebc8; color: #7c2d12; }
.row-equal-height { display: flex; flex-wrap: wrap; }
.row-equal-height > [class*='col-'] { display: flex; flex-direction: column; }
.performance-card.full-height { height: 100%; display: flex; flex-direction: column; }
.performance-card.full-height > .row { flex: 1; }
@media (max-width: 768px) {
    .cashier-dashboard { padding: 10px; }
    .stat-card { padding: 15px; margin-bottom: 15px; }
    .stat-value { font-size: 24px; }
    .stat-icon { font-size: 32px; right: 10px; top: 10px; }
    .performance-card { padding: 15px; margin-bottom: 15px; min-height: auto; }
    .performance-card h4 { font-size: 16px; margin-bottom: 12px; }
    .performance-card h5 { font-size: 14px; }
    .fee-breakdown-item { 
        padding: 12px; 
        margin-bottom: 12px;
    }
    .fee-breakdown-item > div:first-child { 
        flex-direction: row !important; 
        gap: 10px !important; 
        width: 100%;
    }
    .fee-breakdown-item > div:last-child {
        text-align: right;
        min-width: 100px;
        font-size: 18px !important;
    }
    .fee-icon { width: 40px; height: 40px; font-size: 20px; }
    .transaction-table { font-size: 12px; display: block; overflow-x: auto; }
    .transaction-table th, .transaction-table td { padding: 8px; white-space: nowrap; }
    
    /* Force full width for performance cards on mobile */
    .row-equal-height > .col-lg-3 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    
    /* Stack performance cards vertically on mobile */
    .row-equal-height > [class*='col-'] { 
        margin-bottom: 0; 
    }
    
    /* Better spacing for mobile */
    .row { margin-left: -5px; margin-right: -5px; }
    .row > [class*='col-'] { padding-left: 5px; padding-right: 5px; }
    
    /* Mobile-specific card adjustments */
    .stat-card { position: relative; }
    .stat-label { font-size: 10px; }
    .stat-value sup { font-size: 12px; }
    .stat-card > div:last-child { font-size: 10px !important; }
    
    /* Welcome header mobile */
    .cashier-dashboard h2 { font-size: 20px; margin-bottom: 5px; }
    .cashier-dashboard h2 + p { font-size: 13px; }
    
    /* Fee breakdown responsive columns */
    .performance-card.full-height > .row > .col-lg-6 {
        margin-bottom: 20px;
    }
    .performance-card.full-height > .row > .col-lg-6:last-child {
        margin-bottom: 0;
    }
    
    /* Performance card values smaller on mobile */
    .performance-card > div[style*="font-size: 32px"] {
        font-size: 24px !important;
    }
    .performance-card > div[style*="font-size: 32px"] sup {
        font-size: 12px !important;
    }
}
</style>

<div class="cashier-dashboard">
    <!-- Welcome Header -->
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-md-12">
            <h2 style="color: #2d3748; font-weight: 700; margin-bottom: 5px;">Welcome back, <?php echo $cashier_name; ?>! 👋</h2>
            <p style="color: #718096; font-size: 16px;"><?php echo date('l, F j, Y'); ?> • Here's your performance today</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-6" style="margin-bottom: 20px;">
            <div class="stat-card" onclick="showDetailsModal('total')">
                <i class="fa fa-money-bill-wave stat-icon"></i>
                <div class="stat-label">Total Collected</div>
                <div class="stat-value"><sup><?php echo $currency; ?></sup><?php echo number_format($total_collected, 2); ?></div>
                <div style="font-size: 12px; opacity: 0.8; margin-top: 10px;"><i class="fa fa-arrow-up"></i> Today's collections</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-6" style="margin-bottom: 20px;">
            <div class="stat-card green" onclick="showDetailsModal('transactions')">
                <i class="fa fa-receipt stat-icon"></i>
                <div class="stat-label">Transactions</div>
                <div class="stat-value"><?php echo $today_collections->transaction_count ?? 0; ?></div>
                <div style="font-size: 12px; opacity: 0.8; margin-top: 10px;"><i class="fa fa-check-circle"></i> Processed today</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-6" style="margin-bottom: 20px;">
            <div class="stat-card orange" onclick="showDetailsModal('arrears')">
                <i class="fa fa-exclamation-circle stat-icon"></i>
                <div class="stat-label">Outstanding</div>
                <div class="stat-value"><sup><?php echo $currency; ?></sup><?php echo number_format($total_arrears, 2); ?></div>
                <div style="font-size: 12px; opacity: 0.8; margin-top: 10px;"><i class="fa fa-hand-holding-usd"></i> Total unpaid fees</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-6" style="margin-bottom: 20px;">
            <div class="stat-card purple" onclick="showDetailsModal('students')">
                <i class="fa fa-users stat-icon"></i>
                <div class="stat-label">Students Served</div>
                <div class="stat-value"><?php echo $today_collections->unique_students ?? 0; ?></div>
                <div style="font-size: 12px; opacity: 0.8; margin-top: 10px;"><i class="fa fa-user-check"></i> Unique students</div>
            </div>
        </div>
    </div>

    <!-- Performance & Fee Breakdown -->
    <div class="row row-equal-height">
        <div class="col-lg-3 col-md-6 col-sm-12 col-12" style="margin-bottom: 20px;">
            <div class="performance-card">
                <h4><i class="fa fa-calendar-week"></i> This Week</h4>
                <div style="font-size: 32px; font-weight: 700; color: #11998e; margin: 15px 0;"><sup style="font-size: 16px;"><?php echo $currency; ?></sup><?php echo number_format($week_collections, 2); ?></div>
                <p style="color: #718096; margin: 0;">Total collections this week</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 col-12" style="margin-bottom: 20px;">
            <div class="performance-card">
                <h4><i class="fa fa-calendar-alt"></i> This Month</h4>
                <div style="font-size: 32px; font-weight: 700; color: #4facfe; margin: 15px 0;"><sup style="font-size: 16px;"><?php echo $currency; ?></sup><?php echo number_format($month_collections, 2); ?></div>
                <p style="color: #718096; margin: 0;">Total collections this month</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 col-12" style="margin-bottom: 20px;">
            <div class="performance-card">
                <h4><i class="fa fa-calendar-check"></i> This Term</h4>
                <div style="font-size: 32px; font-weight: 700; color: #f5576c; margin: 15px 0;"><sup style="font-size: 16px;"><?php echo $currency; ?></sup><?php echo number_format($term_collections, 2); ?></div>
                <p style="color: #718096; margin: 0;">Total collections this term</p>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-sm-12 col-12" style="margin-bottom: 20px;">
            <div class="performance-card">
                <h4><i class="fa fa-chart-line"></i> Avg Transaction</h4>
                <div style="font-size: 32px; font-weight: 700; color: #667eea; margin: 15px 0;"><sup style="font-size: 16px;"><?php echo $currency; ?></sup><?php echo number_format($avg_transaction, 2); ?></div>
                <p style="color: #718096; margin: 0;">Average per transaction</p>
            </div>
        </div>
    </div>
    
    <!-- Fee Breakdown -->
    <div class="row">
        <div class="col-12">
            <div class="performance-card full-height">
                <h4 style="margin-bottom: 20px;"><i class="fa fa-chart-bar"></i> Fee Breakdown</h4>
                
                <div class="row">
                    <!-- Section 1: Collected Today -->
                    <div class="col-lg-6 col-md-12 col-12">
                        <h5 style="color: #22543d; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #c6f6d5;"><i class="fa fa-check-circle"></i> Collected Today</h5>
                    <?php if($feeding_enabled): ?>
                    <div class="fee-breakdown-item" onclick="showFeeDetails('feeding', 'collected')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #fed7d7; color: #c53030;"><i class="fa fa-utensils"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Feeding</div><div style="font-size: 12px; color: #718096;">Daily meals</div></div>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: #c53030;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($today_collections->feeding ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($breakfast_enabled): ?>
                    <div class="fee-breakdown-item" onclick="showFeeDetails('breakfast', 'collected')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #feebc8; color: #c05621;"><i class="fa fa-coffee"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Breakfast</div><div style="font-size: 12px; color: #718096;">Morning meals</div></div>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: #c05621;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($today_collections->breakfast ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($classes_enabled): ?>
                    <div class="fee-breakdown-item" onclick="showFeeDetails('classes', 'collected')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #e9d8fd; color: #6b46c1;"><i class="fa fa-book"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Classes</div><div style="font-size: 12px; color: #718096;">Class fees</div></div>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: #6b46c1;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($today_collections->classes ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($water_enabled): ?>
                    <div class="fee-breakdown-item" onclick="showFeeDetails('water', 'collected')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #bee3f8; color: #2c5282;"><i class="fa fa-tint"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Water</div><div style="font-size: 12px; color: #718096;">Water fees</div></div>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: #2c5282;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($today_collections->water ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transport_enabled): ?>
                    <div class="fee-breakdown-item" onclick="showFeeDetails('transport', 'collected')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #c6f6d5; color: #22543d;"><i class="fa fa-bus"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Transport</div><div style="font-size: 12px; color: #718096;">Transport fees</div></div>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: #22543d;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($today_collections->transport ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    </div>
                    
                    <!-- Section 2: Outstanding Arrears -->
                    <div class="col-lg-6 col-md-12 col-12">
                        <h5 style="color: #c53030; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #fed7d7;"><i class="fa fa-exclamation-triangle"></i> Outstanding</h5>
                    <?php if($feeding_enabled): ?>
                    <div class="fee-breakdown-item" style="background: #fff5f5;" onclick="showFeeDetails('feeding', 'arrears')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #fed7d7; color: #c53030;"><i class="fa fa-utensils"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Feeding</div><div style="font-size: 12px; color: #718096;">Unpaid feeding</div></div>
                        </div>
                        <div style="font-size: 20px; font-weight: 700; color: #c53030;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->feeding ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($breakfast_enabled): ?>
                    <div class="fee-breakdown-item" style="background: #fffaf0;" onclick="showFeeDetails('breakfast', 'arrears')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #feebc8; color: #c05621;"><i class="fa fa-coffee"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Breakfast</div><div style="font-size: 12px; color: #718096;">Unpaid breakfast</div></div>
                        </div>
                        <div style="font-size: 20px; font-weight: 700; color: #c05621;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->breakfast ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($classes_enabled): ?>
                    <div class="fee-breakdown-item" style="background: #faf5ff;" onclick="showFeeDetails('classes', 'arrears')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #e9d8fd; color: #6b46c1;"><i class="fa fa-book"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Classes</div><div style="font-size: 12px; color: #718096;">Unpaid classes</div></div>
                        </div>
                        <div style="font-size: 20px; font-weight: 700; color: #6b46c1;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->classes ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($water_enabled): ?>
                    <div class="fee-breakdown-item" style="background: #f0f9ff;" onclick="showFeeDetails('water', 'arrears')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #bee3f8; color: #2c5282;"><i class="fa fa-tint"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Water</div><div style="font-size: 12px; color: #718096;">Unpaid water</div></div>
                        </div>
                        <div style="font-size: 20px; font-weight: 700; color: #2c5282;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->water ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transport_enabled): ?>
                    <div class="fee-breakdown-item" style="background: #f0fff4;" onclick="showFeeDetails('transport', 'arrears')">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="fee-icon" style="background: #c6f6d5; color: #22543d;"><i class="fa fa-bus"></i></div>
                            <div><div style="font-weight: 600; color: #2d3748;">Transport</div><div style="font-size: 12px; color: #718096;">Unpaid transport</div></div>
                        </div>
                        <div style="font-size: 20px; font-weight: 700; color: #22543d;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->transport ?? 0, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-md-12">
            <div class="performance-card">
                <h4><i class="fa fa-history"></i> Recent Transactions</h4>
                <?php if (empty($recent_transactions)): ?>
                    <p style="text-align: center; color: #a0aec0; padding: 40px 0;"><i class="fa fa-info-circle"></i> No transactions today</p>
                <?php else: ?>
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Student</th>
                                <th>Receipt</th>
                                <th style="text-align: right;">Amount</th>
                                <th>Payment Method</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $txn): 
                                $amount = $txn['feeding_amount'] + $txn['breakfast_amount'] + $txn['classes_amount'] + $txn['water_amount'] + $txn['transport_amount'];
                            ?>
                            <tr>
                                <td style="color: #4a5568; font-weight: 600;"><?php echo date('h:i A', $txn['created_at']); ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #2d3748;"><?php echo $txn['student_name']; ?></div>
                                    <div style="font-size: 12px; color: #a0aec0;"><?php echo $txn['student_code']; ?></div>
                                </td>
                                <td><code style="background: #edf2f7; padding: 4px 8px; border-radius: 4px; color: #4a5568;"><?php echo $txn['receipt_number']; ?></code></td>
                                <td style="text-align: right; font-weight: 700; color: #2d3748; font-size: 16px;"><sup style="font-size: 10px; color: #718096;"><?php echo $currency; ?></sup><?php echo number_format($amount, 2); ?></td>
                                <td>
                                    <?php
                                    $payment_method = $this->db->get_where('payment_methods', ['id' => $txn['payment_method']])->row();
                                    if($payment_method) {
                                        echo '<i class="fa fa-' . $payment_method->icon . '" style="color: #718096; margin-right: 5px;"></i>';
                                        echo '<span style="color: #4a5568;">' . $payment_method->name . '</span>';
                                    } else {
                                        echo '<span style="color: #a0aec0;">N/A</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = $txn['payment_type'] == 'advance' ? 'badge-success' : ($txn['payment_type'] == 'arrears' ? 'badge-danger' : 'badge-warning');
                                    ?>
                                    <span class="badge-modern <?php echo $badge_class; ?>"><?php echo ucfirst($txn['payment_type']); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showDetailsModal(type) {
    console.log('Show details for:', type);
}

function showFeeDetails(feeType, category) {
    const titles = {
        feeding: 'Feeding Fee',
        breakfast: 'Breakfast Fee',
        classes: 'Classes Fee',
        water: 'Water Fee',
        transport: 'Transport Fee'
    };
    
    const icons = {
        feeding: 'utensils',
        breakfast: 'coffee',
        classes: 'book',
        water: 'tint',
        transport: 'bus'
    };
    
    const title = titles[feeType] + (category === 'collected' ? ' - Collected Today' : ' - Outstanding');
    const icon = icons[feeType];
    
    showAjaxModal_alert('Loading details...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/get_fee_details'); ?>',
        type: 'POST',
        data: { fee_type: feeType, category: category, cashier_id: <?php echo $cashier_id; ?> },
        dataType: 'json'
    }).done(function(response) {
        $('#modal_alert').modal('hide');
        if(response.status === 'success') {
            showModalWithContent('detailsModal', '<i class="fa fa-' + icon + '"></i> ' + title, response.html);
        } else {
            showAjaxModal_alert(response.message || 'Failed to load details', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred while loading details', 'error');
    });
}
</script>
