<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$running_year = get_settings('running_year');

// Get timestamp from URL parameter
$timestamp = $this->uri->segment(3) ?? strtotime(date('d-m-Y'));
$fee_type_filter = $this->input->get('fee_type') ?? 'all';
$class_filter = $this->input->get('class_id') ?? 'all';
$source_filter = $this->input->get('source') ?? 'all';

$date_start = strtotime(date('Y-m-d 00:00:00', $timestamp));
$date_end = strtotime(date('Y-m-d 23:59:59', $timestamp));
$date_string = date('Y-m-d', $timestamp);

// Load the model and get payment details
$this->load->model('Daily_fee_model');

// Get daily fee transactions
$daily_fee_payments = [];
if ($source_filter == 'all' || $source_filter == 'daily_fees') {
    $daily_fee_payments = $this->Daily_fee_model->get_daily_revenue_details($timestamp, $fee_type_filter, $class_filter);
}

// Get inventory sales
$inventory_sales = [];
if (($source_filter == 'all' || $source_filter == 'inventory') && $this->db->table_exists('inventory_sales')) {
    $this->db->select('is.*, s.name as student_name, c.name as class_name, c.name_numeric');
    $this->db->from('inventory_sales is');
    $this->db->join('student s', 's.student_id = is.student_id', 'left');
    $this->db->join('enroll e', 'e.student_id = is.student_id AND e.year = "' . $running_year . '"', 'left');
    $this->db->join('class c', 'c.class_id = e.class_id', 'left');
    $this->db->where('DATE(is.sale_date)', $date_string);
    if ($class_filter != 'all') {
        $this->db->where('e.class_id', $class_filter);
    }
    $this->db->group_by('is.id');
    $this->db->order_by('is.sale_date', 'DESC');
    $inventory_sales = $this->db->get()->result_array();
}

// Get invoice payments - FIX: Use subquery to avoid cartesian product from LEFT JOINs
$invoice_payments = [];
if ($source_filter == 'all' || $source_filter == 'invoices') {
    // Step 1: Get DISTINCT payment records only (no joins to avoid duplicates)
    $sql = "SELECT 
                p.payment_id,
                p.receipt_code, 
                p.student_id,
                p.invoice_code,
                p.amount,
                p.timestamp, 
                p.payment_method
            FROM payment p
            WHERE p.payment_type = 'income'
            AND p.timestamp >= ?
            AND p.timestamp <= ?
            AND p.can_delete != 'trash'";
    
    $params = [$date_start, $date_end];
    
    // If class filter, use subquery to avoid JOIN multiplication
    if ($class_filter != 'all') {
        $sql .= " AND p.student_id IN (
            SELECT student_id FROM enroll WHERE class_id = ? AND year = ?
        )";
        $params[] = $class_filter;
        $params[] = $running_year;
    }
    
    $sql .= " ORDER BY p.timestamp DESC";
    
    $all_payments = $this->db->query($sql, $params)->result_array();
    
    // Step 2: Get student/class/section info - use MAX(enroll_id) for current enrollment
    $student_info = [];
    if (!empty($all_payments)) {
        $student_ids = array_unique(array_column($all_payments, 'student_id'));
        $student_ids_str = implode(',', array_map('intval', $student_ids));
        
        // Get the most recent enrollment (MAX enroll_id) for each student
        $info_sql = "SELECT 
                        s.student_id,
                        s.name as student_name,
                        c.name as class_name,
                        c.name_numeric,
                        sec.name as section_name
                    FROM student s
                    LEFT JOIN enroll e ON e.student_id = s.student_id 
                        AND e.year = ?
                        AND e.enroll_id = (
                            SELECT MAX(enroll_id) 
                            FROM enroll 
                            WHERE student_id = s.student_id AND year = ?
                        )
                    LEFT JOIN class c ON c.class_id = e.class_id
                    LEFT JOIN section sec ON sec.section_id = e.section_id
                    WHERE s.student_id IN ($student_ids_str)";
        
        $student_data = $this->db->query($info_sql, [$running_year, $running_year])->result_array();
        foreach ($student_data as $data) {
            $student_info[$data['student_id']] = $data;
        }
    }
    
    // Step 3: Merge student info with payments
    foreach ($all_payments as &$payment) {
        if (isset($student_info[$payment['student_id']])) {
            $payment['student_name'] = $student_info[$payment['student_id']]['student_name'];
            $payment['class_name'] = $student_info[$payment['student_id']]['class_name'];
            $payment['name_numeric'] = $student_info[$payment['student_id']]['name_numeric'];
            $payment['section_name'] = $student_info[$payment['student_id']]['section_name'];
        } else {
            $payment['student_name'] = '-';
            $payment['class_name'] = '';
            $payment['name_numeric'] = '';
            $payment['section_name'] = '';
        }
    }
    unset($payment);
    
    // Step 4: Group by receipt_code in PHP
    $grouped = [];
    foreach ($all_payments as $payment) {
        $key = $payment['receipt_code'];
        
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'receipt_code' => $payment['receipt_code'],
                'student_id' => $payment['student_id'],
                'invoice_code' => $payment['invoice_code'],
                'student_name' => $payment['student_name'],
                'class_name' => $payment['class_name'],
                'name_numeric' => $payment['name_numeric'],
                'section_name' => $payment['section_name'],
                'total_amount' => 0,
                'timestamp' => $payment['timestamp'],
                'payment_method' => $payment['payment_method']
            ];
        }
        
        // Sum the amounts
        $grouped[$key]['total_amount'] += (float)$payment['amount'];
        
        // Keep the latest timestamp
        if ($payment['timestamp'] > $grouped[$key]['timestamp']) {
            $grouped[$key]['timestamp'] = $payment['timestamp'];
        }
    }
    
    // Convert back to indexed array
    $invoice_payments = array_values($grouped);
    
    // Sort by timestamp descending
    usort($invoice_payments, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
}

// Calculate totals
$daily_fees_total = 0;
foreach ($daily_fee_payments as $p) {
    // If filtering by specific fee type, only count that fee type
    if ($fee_type_filter != 'all' && $fee_type_filter != null) {
        switch ($fee_type_filter) {
            case 'feeding':
                $daily_fees_total += $p['feeding_amount'];
                break;
            case 'breakfast':
                $daily_fees_total += $p['breakfast_amount'];
                break;
            case 'classes':
                $daily_fees_total += $p['classes_amount'];
                break;
            case 'water':
                $daily_fees_total += $p['water_amount'];
                break;
            case 'transport':
                $daily_fees_total += $p['transport_amount'];
                break;
        }
    } else {
        // Count all fee types
        $daily_fees_total += $p['feeding_amount'] + $p['breakfast_amount'] + $p['classes_amount'] + $p['water_amount'] + $p['transport_amount'];
    }
}

$inventory_total = 0;
foreach ($inventory_sales as $s) {
    $inventory_total += $s['total_amount'];
}

$invoices_total = 0;
foreach ($invoice_payments as $p) {
    // Use 'amount' if 'total_amount' doesn't exist (for compatibility)
    $invoices_total += isset($p['total_amount']) ? $p['total_amount'] : $p['amount'];
}

$total_revenue = $daily_fees_total + $inventory_total + $invoices_total;
$total_transactions = count($daily_fee_payments) + count($inventory_sales) + count($invoice_payments);

// Build unified transactions array
$unified_transactions = [];

// Add daily fee payments
foreach ($daily_fee_payments as $p) {
    // Calculate row total based on fee type filter
    $row_total = 0;
    $fee_details = [];
    
    // If filtering by specific fee type, only show that fee type
    if ($fee_type_filter != 'all' && $fee_type_filter != null) {
        // Only include the filtered fee type
        switch ($fee_type_filter) {
            case 'feeding':
                $row_total = $p['feeding_amount'];
                if ($p['feeding_amount'] > 0) $fee_details[] = 'Feeding: ' . $currency . number_format($p['feeding_amount'], 2);
                break;
            case 'breakfast':
                $row_total = $p['breakfast_amount'];
                if ($p['breakfast_amount'] > 0) $fee_details[] = 'Breakfast: ' . $currency . number_format($p['breakfast_amount'], 2);
                break;
            case 'classes':
                $row_total = $p['classes_amount'];
                if ($p['classes_amount'] > 0) $fee_details[] = 'Classes: ' . $currency . number_format($p['classes_amount'], 2);
                break;
            case 'water':
                $row_total = $p['water_amount'];
                if ($p['water_amount'] > 0) $fee_details[] = 'Water: ' . $currency . number_format($p['water_amount'], 2);
                break;
            case 'transport':
                $row_total = $p['transport_amount'];
                if ($p['transport_amount'] > 0) $fee_details[] = 'Transport: ' . $currency . number_format($p['transport_amount'], 2);
                break;
        }
    } else {
        // Show all fee types
        $row_total = $p['feeding_amount'] + $p['breakfast_amount'] + $p['classes_amount'] + $p['water_amount'] + $p['transport_amount'];
        if ($p['feeding_amount'] > 0) $fee_details[] = 'Feeding: ' . $currency . number_format($p['feeding_amount'], 2);
        if ($p['breakfast_amount'] > 0) $fee_details[] = 'Breakfast: ' . $currency . number_format($p['breakfast_amount'], 2);
        if ($p['classes_amount'] > 0) $fee_details[] = 'Classes: ' . $currency . number_format($p['classes_amount'], 2);
        if ($p['water_amount'] > 0) $fee_details[] = 'Water: ' . $currency . number_format($p['water_amount'], 2);
        if ($p['transport_amount'] > 0) $fee_details[] = 'Transport: ' . $currency . number_format($p['transport_amount'], 2);
    }
    
    // Use receipt_number or transaction_code for receipt
    $receipt_code = $p['receipt_number'] ?? $p['transaction_code'] ?? null;
    
    // Build class display with section
    $class_display = '-';
    if ($p['class_name']) {
        $class_display = $p['class_name'];
        if ($p['name_numeric']) {
            $class_display .= ' ' . $p['name_numeric'];
        }
        if ($p['section_name']) {
            $class_display .= ' (' . $p['section_name'] . ')';
        }
    }
    
    $unified_transactions[] = [
        'source' => 'Daily Fees',
        'source_icon' => 'fa-utensils',
        'source_color' => 'primary',
        'student_name' => $p['student_name'],
        'class_name' => $class_display,
        'amount' => $row_total,
        'payment_method' => get_payment_method_name($p['payment_method'] ?? null),
        'receipt_code' => $receipt_code,
        'student_id' => $p['student_id'],
        'timestamp' => $p['payment_date'], // Use payment_date (Unix timestamp) directly
        'details' => implode(', ', $fee_details),
        'time' => date('H:i', $p['payment_date'])
    ];
}

// Add inventory sales
foreach ($inventory_sales as $s) {
    // Determine sale ID - check for both 'id' and 'sale_id' columns
    $sale_id = '-';
    if (isset($s['id'])) {
        $sale_id = $s['id'];
    } elseif (isset($s['sale_id'])) {
        $sale_id = $s['sale_id'];
    }
    
    $class_display = '-';
    if ($s['class_name']) {
        $class_display = $s['class_name'];
        if ($s['name_numeric']) {
            $class_display .= ' ' . $s['name_numeric'];
        }
    }
    
    $unified_transactions[] = [
        'source' => 'Inventory',
        'source_icon' => 'fa-shopping-cart',
        'source_color' => 'warning',
        'student_name' => $s['student_name'] ?? 'Walk-in',
        'class_name' => $class_display,
        'amount' => $s['total_amount'] ?? 0,
        'payment_method' => get_payment_method_name($s['payment_method'] ?? null),
        'receipt_code' => $s['receipt_code'] ?? null,
        'student_id' => $s['student_id'] ?? null,
        'timestamp' => strtotime($s['sale_date']),
        'details' => 'Sale ID: ' . $sale_id,
        'time' => date('H:i', strtotime($s['sale_date']))
    ];
}

// Add invoice payments (now grouped by receipt_code)
foreach ($invoice_payments as $p) {
    $class_display = '-';
    if ($p['class_name']) {
        $class_display = $p['class_name'];
        if ($p['name_numeric']) {
            $class_display .= ' ' . $p['name_numeric'];
        }
        if ($p['section_name']) {
            $class_display .= ' (' . $p['section_name'] . ')';
        }
    }
    
    // Build details showing both invoice and receipt
    $details_text = 'Invoice: ' . ($p['invoice_code'] ?? '-');
    if ($p['receipt_code']) {
        $details_text .= '<br><small>Receipt: ' . $p['receipt_code'] . '</small>';
    }
    
    $unified_transactions[] = [
        'source' => 'Invoice',
        'source_icon' => 'fa-file-invoice-dollar',
        'source_color' => 'info',
        'student_name' => $p['student_name'] ?? '-',
        'class_name' => $class_display,
        'amount' => isset($p['total_amount']) ? $p['total_amount'] : $p['amount'],
        'payment_method' => get_payment_method_name($p['payment_method'] ?? null),
        'receipt_code' => $p['receipt_code'] ?? null,
        'student_id' => $p['student_id'],
        'timestamp' => $p['timestamp'],
        'details' => $details_text,
        'time' => date('H:i', $p['timestamp'])
    ];
}

// Sort by time (most recent first)
usort($unified_transactions, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

// Format date for display
$date_display = ($timestamp == strtotime(date('d-m-Y'))) ? 'Today' : date('F d, Y', $timestamp);
?>

<!-- Load Mobile-Responsive CSS -->
<link href="<?php echo base_url(); ?>assets/css/daily-revenue-modal-mobile.css?v=<?php echo time(); ?>" rel="stylesheet" type="text/css" />

<style>
/* Core Styles (non-mobile specific) */
.daily-revenue-modal .summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 16px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.daily-revenue-modal .summary-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.daily-revenue-modal .summary-card.total { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.daily-revenue-modal .summary-card.daily-fees { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.daily-revenue-modal .summary-card.inventory { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.daily-revenue-modal .summary-card.invoices { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.daily-revenue-modal .summary-card .card-icon {
    font-size: 24px;
    opacity: 0.8;
    margin-bottom: 8px;
}
.daily-revenue-modal .summary-card .card-label {
    font-size: 12px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.daily-revenue-modal .summary-card .card-value {
    font-size: 22px;
    font-weight: 700;
    margin-top: 4px;
}
.daily-revenue-modal .filter-group {
    flex: 1;
    min-width: 150px;
}
.daily-revenue-modal .filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #555;
    margin-bottom: 4px;
    display: block;
}
.daily-revenue-modal .filter-group .form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 13px;
    height: 38px;
}
.daily-revenue-modal .filter-group .btn {
    border-radius: 8px;
    font-size: 13px;
    height: 38px;
    min-width: 100px;
}
.daily-revenue-modal .export-buttons .btn {
    border-radius: 8px;
    font-size: 13px;
    padding: 8px 16px;
}
.daily-revenue-modal .transactions-table thead th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}
.daily-revenue-modal .transactions-table tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.daily-revenue-modal .transactions-table tbody tr:hover {
    background: #f8f9fa;
}
.daily-revenue-modal .source-badge.primary { background: #e3f2fd; color: #1976d2; }
.daily-revenue-modal .source-badge.warning { background: #fff3e0; color: #f57c00; }
.daily-revenue-modal .source-badge.info { background: #e0f7fa; color: #00838f; }
.daily-revenue-modal .tfoot-total {
    background: #e8f5e9;
    font-weight: 700;
}
.daily-revenue-modal .tfoot-total td {
    padding: 12px;
    border-top: 2px solid #4caf50;
}
.daily-revenue-modal .btn-print-receipt {
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 4px;
}

/* Preloader Overlay - Modern Design */
.daily-revenue-preloader {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
}
.daily-revenue-preloader.show {
    display: block;
}

/* Content wrapper - perfectly centered */
.daily-revenue-preloader .spinner-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

/* Modern 3-dot spinner */
.daily-revenue-preloader .spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
}
.daily-revenue-preloader .spinner .dot {
    width: 14px;
    height: 14px;
    background: linear-gradient(135deg, #4caf50, #81c784);
    border-radius: 50%;
    animation: dr-bounce 1.4s infinite ease-in-out both;
    box-shadow: 0 0 20px rgba(76, 175, 80, 0.6);
}
.daily-revenue-preloader .spinner .dot:nth-child(1) {
    animation-delay: -0.32s;
}
.daily-revenue-preloader .spinner .dot:nth-child(2) {
    animation-delay: -0.16s;
}
.daily-revenue-preloader .spinner .dot:nth-child(3) {
    animation-delay: 0s;
}

.daily-revenue-preloader .loading-text {
    font-size: 16px;
    color: #fff;
    font-weight: 500;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

@keyframes dr-bounce {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1.3);
        opacity: 1;
    }
}
</style>

<div class="daily-revenue-modal">
    <!-- Preloader Overlay - Modern Design -->
    <div class="daily-revenue-preloader" id="daily_revenue_preloader">
        <div class="spinner-container">
            <div class="spinner">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
            <div class="loading-text">Loading data...</div>
        </div>
    </div>

    <!-- Header -->
    <div style="margin-bottom: 20px;">
        <h4 style="margin: 0; color: #333; font-weight: 600;">
            <i class="fa fa-chart-line" style="color: #4caf50;"></i> Daily Revenue Details
        </h4>
        <p style="margin: 4px 0 0; color: #666; font-size: 13px;"><?php echo $date_display; ?></p>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card total">
            <div class="card-icon"><i class="fa fa-coins"></i></div>
            <div class="card-label">Total Revenue</div>
            <div class="card-value"><?php echo $currency; ?><?php echo number_format($total_revenue, 2); ?></div>
        </div>
        <div class="summary-card daily-fees">
            <div class="card-icon"><i class="fa fa-utensils"></i></div>
            <div class="card-label">Daily Fees</div>
            <div class="card-value"><?php echo $currency; ?><?php echo number_format($daily_fees_total, 2); ?></div>
        </div>
        <div class="summary-card inventory">
            <div class="card-icon"><i class="fa fa-shopping-cart"></i></div>
            <div class="card-label">Inventory Sales</div>
            <div class="card-value"><?php echo $currency; ?><?php echo number_format($inventory_total, 2); ?></div>
        </div>
        <div class="summary-card invoices">
            <div class="card-icon"><i class="fa fa-file-invoice-dollar"></i></div>
            <div class="card-label">Invoice Payments</div>
            <div class="card-value"><?php echo $currency; ?><?php echo number_format($invoices_total, 2); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-row">
        <div class="filter-group">
            <label>Date</label>
            <input type="text" id="dr_date" class="form-control datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y', $timestamp); ?>" onchange="filterDailyRevenue()">
        </div>
        <div class="filter-group">
            <label>Revenue Source</label>
            <select id="dr_source" class="form-control" onchange="filterDailyRevenue()">
                <option value="all" <?php echo $source_filter == 'all' ? 'selected' : ''; ?>>All Sources</option>
                <option value="daily_fees" <?php echo $source_filter == 'daily_fees' ? 'selected' : ''; ?>>Daily Fees</option>
                <option value="inventory" <?php echo $source_filter == 'inventory' ? 'selected' : ''; ?>>Inventory Sales</option>
                <option value="invoices" <?php echo $source_filter == 'invoices' ? 'selected' : ''; ?>>Invoice Payments</option>
            </select>
        </div>
        <div class="filter-group" id="fee_type_filter" style="<?php echo ($source_filter == 'daily_fees') ? '' : 'display: none;'; ?>">
            <label>Fee Type</label>
            <select id="dr_fee_type" class="form-control" onchange="filterDailyRevenue()">
                <option value="all" <?php echo $fee_type_filter == 'all' ? 'selected' : ''; ?>>All Fee Types</option>
                <option value="feeding" <?php echo $fee_type_filter == 'feeding' ? 'selected' : ''; ?>>Feeding</option>
                <option value="breakfast" <?php echo $fee_type_filter == 'breakfast' ? 'selected' : ''; ?>>Breakfast</option>
                <option value="classes" <?php echo $fee_type_filter == 'classes' ? 'selected' : ''; ?>>Classes</option>
                <option value="water" <?php echo $fee_type_filter == 'water' ? 'selected' : ''; ?>>Water</option>
                <option value="transport" <?php echo $fee_type_filter == 'transport' ? 'selected' : ''; ?>>Transport</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Class</label>
            <select id="dr_class" class="form-control" onchange="filterDailyRevenue()">
                <option value="all" <?php echo $class_filter == 'all' ? 'selected' : ''; ?>>All Classes</option>
                <?php echo getFullClassList('', $class_filter); ?>
            </select>
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-default" onclick="resetFilters()">
                <i class="fa fa-refresh"></i> Reset
            </button>
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-success" onclick="goToToday()">
                <i class="fa fa-calendar-day"></i> Today
            </button>
        </div>
    </div>

    <!-- Hidden field for timestamp -->
    <input type="hidden" id="dr_timestamp" value="<?php echo $timestamp; ?>">

    <?php if($total_transactions > 0): ?>
    
    <!-- Export Buttons -->
    <div class="export-buttons">
        <button type="button" class="btn btn-info" onclick="printDailyRevenue()">
            <i class="fa fa-print"></i> Print
        </button>
        <button type="button" class="btn btn-success" onclick="exportToExcel()">
            <i class="fa fa-file-excel"></i> Export to Excel
        </button>
    </div>

    <!-- Transactions Table -->
    <div style="font-size: 14px; font-weight: 600; color: #333; margin-bottom: 12px;">
        <i class="fa fa-list"></i> All Transactions (<?php echo $total_transactions; ?>)
    </div>
    <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border-radius: 8px; border: 1px solid #eee;">
        <table class="transactions-table" id="unified_transactions_table">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th width="60">Time</th>
                    <th width="100">Source</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Details</th>
                    <th width="100">Amount</th>
                    <th width="100">Method</th>
                    <th width="60">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                foreach($unified_transactions as $transaction): 
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo $transaction['time']; ?></td>
                    <td>
                        <span class="source-badge <?php echo $transaction['source_color']; ?>">
                            <i class="fa <?php echo $transaction['source_icon']; ?>"></i> <?php echo $transaction['source']; ?>
                        </span>
                    </td>
                    <td><?php echo $transaction['student_name']; ?></td>
                    <td><?php echo $transaction['class_name']; ?></td>
                    <td><small><?php echo $transaction['details']; ?></small></td>
                    <td class="amount-cell"><?php echo $currency . number_format($transaction['amount'], 2); ?></td>
                    <td><?php echo $transaction['payment_method']; ?></td>
                    <td>
                        <?php if($transaction['source'] == 'Daily Fees' && $transaction['student_id'] && $transaction['timestamp']): ?>
                            <button onclick="printFeeReceipt(<?php echo $transaction['student_id']; ?>, <?php echo $transaction['timestamp']; ?>)" class="btn btn-xs btn-info btn-print-receipt" title="Print Receipt">
                                <i class="fa fa-print"></i>
                            </button>
                        <?php elseif($transaction['source'] == 'Invoice' && $transaction['receipt_code']): ?>
                            <button onclick="printInvoiceReceipt('<?php echo $transaction['receipt_code']; ?>', <?php echo $transaction['student_id']; ?>, <?php echo $transaction['amount']; ?>, <?php echo $transaction['timestamp']; ?>)" class="btn btn-xs btn-info btn-print-receipt" title="Print Receipt">
                                <i class="fa fa-print"></i>
                            </button>
                        <?php elseif($transaction['source'] == 'Inventory' && $transaction['receipt_code']): ?>
                            <button onclick="printInventoryReceipt('<?php echo $transaction['receipt_code']; ?>')" class="btn btn-xs btn-info btn-print-receipt" title="Print Receipt">
                                <i class="fa fa-print"></i>
                            </button>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tfoot-total">
                    <td colspan="6" class="text-right">Total Revenue:</td>
                    <td colspan="3"><strong><?php echo $currency . number_format($total_revenue, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php else: ?>
    <div class="no-data">
        <i class="fa fa-inbox"></i>
        <p>No payments found for this date.</p>
    </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Initialize datepicker
    $('#dr_date').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true
    }).on('changeDate', function(e) {
        filterDailyRevenue();
    });
    
    // Show/hide fee type filter based on source selection
    $('#dr_source').on('change', function() {
        var source = $(this).val();
        if (source === 'daily_fees') {
            $('#fee_type_filter').show();
        } else {
            $('#fee_type_filter').hide();
        }
    });
});

function filterDailyRevenue() {
    // Show preloader
    $('#daily_revenue_preloader').addClass('show');
    
    var dateStr = $('#dr_date').val();
    var source = $('#dr_source').val();
    var feeType = $('#dr_fee_type').val();
    var classId = $('#dr_class').val();
    
    // Convert date string to timestamp
    var timestamp = 0;
    if (dateStr) {
        var parts = dateStr.split('-');
        if (parts.length === 3) {
            var dateObj = new Date(parts[2], parts[1] - 1, parts[0]);
            timestamp = Math.floor(dateObj.getTime() / 1000);
        }
    }
    
    // If no valid timestamp, use today
    if (timestamp === 0) {
        timestamp = <?php echo strtotime(date('d-m-Y')); ?>;
    }
    
    // Reload the modal with new filter parameters
    $.ajax({
        url: '<?php echo site_url("modal/popup_daily_revenue/"); ?>' + timestamp + '?source=' + source + '&fee_type=' + feeType + '&class_id=' + classId,
        success: function(response) {
            $('#modal_alert .modal-body').html(response);
        },
        error: function() {
            // Hide preloader on error
            $('#daily_revenue_preloader').removeClass('show');
            alert('Error loading data. Please try again.');
        }
    });
}

function resetFilters() {
    // Show preloader
    $('#daily_revenue_preloader').addClass('show');
    
    $('#dr_date').val('<?php echo date('d-m-Y'); ?>');
    $('#dr_source').val('all');
    $('#dr_fee_type').val('all');
    $('#dr_class').val('all');
    $('#fee_type_filter').hide();
    filterDailyRevenue();
}

function goToToday() {
    // Show preloader
    $('#daily_revenue_preloader').addClass('show');
    
    $('#dr_date').val('<?php echo date('d-m-Y'); ?>');
    filterDailyRevenue();
}

// Print receipt functions - opens in new window for direct printing
function printFeeReceipt(studentId, timestamp) {
    window.open('<?php echo site_url('admin/print_fee_receipt'); ?>/' + studentId + '/' + timestamp, '_blank');
}

function printInvoiceReceipt(receiptCode, studentId, amount, timestamp) {
    window.open('<?php echo site_url('admin/receipt/'); ?>' + receiptCode + '/' + studentId + '/' + (amount || 0) + '/' + (timestamp || Math.floor(Date.now()/1000)), '_blank');
}

function printInventoryReceipt(receiptCode) {
    window.open('<?php echo site_url('inventory/sale_receipt/'); ?>' + receiptCode, '_blank');
}

// Print daily revenue report - only the table
function printDailyRevenue() {
    var table = document.getElementById('unified_transactions_table');
    if (!table) {
        alert('No table to print');
        return;
    }
    
    // Clone the table
    var printTable = table.cloneNode(true);
    
    // Remove receipt buttons from the table
    var receiptBtns = printTable.querySelectorAll('.btn-print-receipt');
    receiptBtns.forEach(function(btn) {
        btn.parentNode.innerHTML = '-';
    });
    
    // Create print window
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Daily Revenue Report - <?php echo $date_display; ?></title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; }');
    printWindow.document.write('h2 { margin-bottom: 10px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
    printWindow.document.write('th, td { border: 1px solid #333; padding: 8px; text-align: left; font-size: 12px; }');
    printWindow.document.write('th { background: #333; color: #fff; font-weight: bold; }');
    printWindow.document.write('.tfoot-total { background: #e8f5e9; font-weight: bold; }');
    printWindow.document.write('.tfoot-total td { border-top: 2px solid #333; }');
    printWindow.document.write('@media print { body { margin: 0; } }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2>Daily Revenue Report - <?php echo $date_display; ?></h2>');
    printWindow.document.write('<p><strong>Total Revenue: </strong><?php echo $currency; ?><?php echo number_format($total_revenue, 2); ?> | <strong>Transactions: </strong><?php echo $total_transactions; ?></p>');
    printWindow.document.write(printTable.outerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// Export to Excel
function exportToExcel() {
    var table = document.getElementById('unified_transactions_table');
    var html = table.outerHTML;
    
    // Create a blob with the table HTML
    var blob = new Blob(['\ufeff', 
        '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' +
        '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>' +
        '<x:Name>Daily Revenue</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>' +
        '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>' + html + '</body></html>'
    ], { type: 'application/vnd.ms-excel' });
    
    // Create download link
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = 'Daily_Revenue_<?php echo date('Y-m-d', $timestamp); ?>.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
