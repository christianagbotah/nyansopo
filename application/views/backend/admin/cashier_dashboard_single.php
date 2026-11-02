<?php
/**
 * ENTERPRISE: Single cashier dashboard for admin view
 */
$cashier = $this->db->get_where('admin', ['admin_id' => $cashier_id])->row();
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;

// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');

// Build date filter - Parse dates in "M d, Y" format (e.g., "May 4, 2026")
$date_filter = '';
$params = [$cashier_id];

// Debug logging
error_log("CASHIER DASHBOARD DEBUG - Single Cashier");
error_log("Date From (raw): " . $date_from);
error_log("Date To (raw): " . $date_to);

if ($date_from) {
    $date_filter .= " AND t.payment_date >= ?";
    $date_obj = DateTime::createFromFormat('M d, Y', $date_from);
    $timestamp_from = $date_obj ? strtotime($date_obj->format('Y-m-d')) : strtotime($date_from);
    $params[] = $timestamp_from;
    error_log("Date From timestamp: " . $timestamp_from . " (" . date('Y-m-d H:i:s', $timestamp_from) . ")");
}
if ($date_to) {
    $date_filter .= " AND t.payment_date < ?";
    $date_obj = DateTime::createFromFormat('M d, Y', $date_to);
    $timestamp_to = ($date_obj ? strtotime($date_obj->format('Y-m-d')) : strtotime($date_to)) + 86400;
    $params[] = $timestamp_to;
    error_log("Date To timestamp: " . $timestamp_to . " (" . date('Y-m-d H:i:s', $timestamp_to) . ")");
}

error_log("Query params: " . print_r($params, true));
error_log("Date filter: " . $date_filter);

// Get collections
$query_sql = "
    SELECT 
        SUM(feeding_amount) as feeding,
        SUM(breakfast_amount) as breakfast,
        SUM(classes_amount) as classes,
        SUM(water_amount) as water,
        SUM(transport_amount) as transport,
        COUNT(*) as transaction_count,
        COUNT(DISTINCT student_id) as unique_students
    FROM daily_fee_transactions t
    WHERE t.collected_by = ? {$date_filter}
";
$collections = $this->db->query($query_sql, $params)->row();

// DEBUG: Output to HTML comment
echo "<!-- DEBUG INFO:\n";
echo "Date From (raw): " . $date_from . "\n";
echo "Date To (raw): " . $date_to . "\n";
echo "Query SQL: " . $query_sql . "\n";
echo "Params: " . print_r($params, true) . "\n";
echo "Collections: " . print_r($collections, true) . "\n";
echo "-->";

$total = ($collections->feeding ?? 0) + ($collections->breakfast ?? 0) + ($collections->classes ?? 0) + ($collections->water ?? 0) + ($collections->transport ?? 0);
?>

<style>
.stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 24px; color: white; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); margin-bottom: 20px; min-height: 140px; display: flex; flex-direction: column; justify-content: center; }
.stat-card.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 10px 30px rgba(56, 239, 125, 0.3); }
.stat-card.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); box-shadow: 0 10px 30px rgba(245, 87, 108, 0.3); }
.stat-card.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3); }
.stat-value { font-size: 36px; font-weight: 700; margin: 10px 0; }
.stat-label { font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
.fee-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.3s; min-height: 90px; }
.fee-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
.fee-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
</style>

<div class="p-4">
    <div class="mb-4">
        <h3 style="color: #2d3748; font-weight: 700;"><?php echo $cashier->name; ?>'s Dashboard</h3>
        <p style="color: #718096;">
            <?php if ($date_from && $date_to): ?>
                Period: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
            <?php elseif ($date_from): ?>
                From: <?php echo date('M d, Y', strtotime($date_from)); ?>
            <?php elseif ($date_to): ?>
                Up to: <?php echo date('M d, Y', strtotime($date_to)); ?>
            <?php else: ?>
                All Time
            <?php endif; ?>
        </p>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-label">Total Collected</div>
                <div class="stat-value"><sup style="font-size: 18px;"><?php echo $currency; ?></sup><?php echo number_format($total, 2); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card green">
                <div class="stat-label">Transactions</div>
                <div class="stat-value"><?php echo $collections->transaction_count ?? 0; ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card purple">
                <div class="stat-label">Students Served</div>
                <div class="stat-value"><?php echo $collections->unique_students ?? 0; ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h4 style="color: #2d3748; font-weight: 600; margin-bottom: 20px;">Fee Breakdown</h4>
        </div>
        <div class="col-lg-6 col-12">
            <h5 style="color: #22543d; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-border: 2px solid #c6f6d5;"><i class="fa fa-check-circle"></i> Collected</h5>
            <?php if($feeding_enabled): ?>
            <div class="fee-card" data-fee-type="feeding" data-category="collected" data-cashier-id="<?php echo $cashier_id; ?>">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #fed7d7; color: #c53030;"><i class="fa fa-utensils"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Feeding</div><div style="font-size: 12px; color: #718096;">Daily meals</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #c53030;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($collections->feeding ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($breakfast_enabled): ?>
            <div class="fee-card" data-fee-type="breakfast" data-category="collected" data-cashier-id="<?php echo $cashier_id; ?>">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #feebc8; color: #c05621;"><i class="fa fa-coffee"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Breakfast</div><div style="font-size: 12px; color: #718096;">Morning meals</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #c05621;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($collections->breakfast ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($classes_enabled): ?>
            <div class="fee-card" data-fee-type="classes" data-category="collected" data-cashier-id="<?php echo $cashier_id; ?>">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #e9d8fd; color: #6b46c1;"><i class="fa fa-book"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Classes</div><div style="font-size: 12px; color: #718096;">Class fees</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #6b46c1;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($collections->classes ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($water_enabled): ?>
            <div class="fee-card" data-fee-type="water" data-category="collected" data-cashier-id="<?php echo $cashier_id; ?>">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #bee3f8; color: #2c5282;"><i class="fa fa-tint"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Water</div><div style="font-size: 12px; color: #718096;">Water fees</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #2c5282;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($collections->water ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($transport_enabled): ?>
            <div class="fee-card" data-fee-type="transport" data-category="collected" data-cashier-id="<?php echo $cashier_id; ?>">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #c6f6d5; color: #22543d;"><i class="fa fa-bus"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Transport</div><div style="font-size: 12px; color: #718096;">Transport fees</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #22543d;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($collections->transport ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-6 col-12">
            <h5 style="color: #c53030; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #fed7d7;"><i class="fa fa-exclamation-triangle"></i> Outstanding</h5>
            <?php
            // Get arrears for active students only (mute = 0) across all terms/years
            $arrears_breakdown = $this->db->query("
                SELECT 
                    SUM(w.feeding_arrears) as feeding,
                    SUM(w.breakfast_arrears) as breakfast,
                    SUM(w.classes_arrears) as classes,
                    SUM(w.water_arrears) as water,
                    SUM(w.transport_arrears) as transport
                FROM daily_fee_wallet w
                INNER JOIN enroll e ON w.student_id = e.student_id 
                    AND w.year = e.year 
                    AND w.term = e.term
                WHERE e.mute = '0'
                  AND (w.feeding_arrears > 0 OR w.breakfast_arrears > 0 OR w.classes_arrears > 0 OR w.water_arrears > 0 OR w.transport_arrears > 0)
            ")->row();
            ?>
            <?php if($feeding_enabled): ?>
            <div class="fee-card" style="background: #fff5f5;" data-fee-type="feeding" data-category="arrears" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #fed7d7; color: #c53030;"><i class="fa fa-utensils"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Feeding</div><div style="font-size: 12px; color: #718096;">Unpaid feeding</div></div>
                </div>
                <div style="font-size: 20px; font-weight: 700; color: #c53030;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->feeding ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($breakfast_enabled): ?>
            <div class="fee-card" style="background: #fffaf0;" data-fee-type="breakfast" data-category="arrears" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #feebc8; color: #c05621;"><i class="fa fa-coffee"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Breakfast</div><div style="font-size: 12px; color: #718096;">Unpaid breakfast</div></div>
                </div>
                <div style="font-size: 20px; font-weight: 700; color: #c05621;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->breakfast ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($classes_enabled): ?>
            <div class="fee-card" style="background: #faf5ff;" data-fee-type="classes" data-category="arrears" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #e9d8fd; color: #6b46c1;"><i class="fa fa-book"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Classes</div><div style="font-size: 12px; color: #718096;">Unpaid classes</div></div>
                </div>
                <div style="font-size: 20px; font-weight: 700; color: #6b46c1;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->classes ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($water_enabled): ?>
            <div class="fee-card" style="background: #f0f9ff;" data-fee-type="water" data-category="arrears" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #bee3f8; color: #2c5282;"><i class="fa fa-tint"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Water</div><div style="font-size: 12px; color: #718096;">Unpaid water</div></div>
                </div>
                <div style="font-size: 20px; font-weight: 700; color: #2c5282;"><sup style="font-size: 11px;"><?php echo $currency; ?></sup><?php echo number_format($arrears_breakdown->water ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($transport_enabled): ?>
            <div class="fee-card" style="background: #f0fff4;" data-fee-type="transport" data-category="arrears" data-cashier-id="all">
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
