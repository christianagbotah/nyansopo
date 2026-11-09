<?php
/**
 * ENTERPRISE: All cashiers combined dashboard for admin view
 */
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
$cashiers = $this->db->get_where('admin', ['level' => 4])->result_array();

// Check which fee modules are enabled
$feeding_enabled = is_fee_module_enabled('feeding');
$breakfast_enabled = is_fee_module_enabled('breakfast');
$classes_enabled = is_fee_module_enabled('classes');
$water_enabled = is_fee_module_enabled('water');
$transport_enabled = is_fee_module_enabled('transport');

// Build date filter - Parse dates in "M d, Y" format (e.g., "May 4, 2026")
$date_filter = '';
$params = [];
if ($date_from) {
    $date_filter .= " AND t.payment_date >= ?";
    $date_obj = DateTime::createFromFormat('M d, Y', $date_from);
    $params[] = $date_obj ? strtotime($date_obj->format('Y-m-d')) : strtotime($date_from);
}
if ($date_to) {
    $date_filter .= " AND t.payment_date < ?";
    $date_obj = DateTime::createFromFormat('M d, Y', $date_to);
    $params[] = ($date_obj ? strtotime($date_obj->format('Y-m-d')) : strtotime($date_to)) + 86400;
}

// Get total collections
$total_collections = $this->db->query("
    SELECT 
        SUM(feeding_amount) as feeding,
        SUM(breakfast_amount) as breakfast,
        SUM(classes_amount) as classes,
        SUM(water_amount) as water,
        SUM(transport_amount) as transport,
        COUNT(*) as transaction_count,
        COUNT(DISTINCT student_id) as unique_students
    FROM daily_fee_transactions t
    WHERE 1=1 {$date_filter}
", $params)->row();

$grand_total = ($total_collections->feeding ?? 0) + ($total_collections->breakfast ?? 0) + ($total_collections->classes ?? 0) + ($total_collections->water ?? 0) + ($total_collections->transport ?? 0);

// Get per-collector breakdown (includes cashiers and non-cashiers)
$collector_query = "
    SELECT 
        t.collected_by,
        a.name as collector_name,
        a.level as collector_level,
        SUM(t.feeding_amount + t.breakfast_amount + t.classes_amount + t.water_amount + t.transport_amount) as total,
        COUNT(*) as transactions
    FROM daily_fee_transactions t
    LEFT JOIN admin a ON t.collected_by = a.admin_id
    WHERE 1=1 {$date_filter}
    GROUP BY t.collected_by, a.name, a.level
    HAVING total > 0
    ORDER BY total DESC
";
$collector_data = $this->db->query($collector_query, $params)->result_array();
?>

<style>
.stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 24px; color: white; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); margin-bottom: 20px; }
.stat-card.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 10px 30px rgba(56, 239, 125, 0.3); }
.stat-card.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3); }
.fee-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.3s; }
.fee-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
.fee-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.cashier-table { width: 100%; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.cashier-table th { background: #f7fafc; padding: 15px; text-align: left; font-weight: 600; color: #4a5568; border-bottom: 2px solid #e2e8f0; font-size: 13px; }
.cashier-table td { padding: 15px; border-bottom: 1px solid #e2e8f0; }
.cashier-table tr:hover { background: #f7fafc; }
</style>

<div class="p-4">
    <div class="mb-4">
        <h3 style="color: #2d3748; font-weight: 700;">All Cashiers Combined Dashboard</h3>
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
                <div class="stat-label" style="font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">Total Collected</div>
                <div class="stat-value" style="font-size: 36px; font-weight: 700; margin: 10px 0;"><sup style="font-size: 18px;"><?php echo $currency; ?></sup><?php echo number_format($grand_total, 2); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card green">
                <div class="stat-label" style="font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">Total Transactions</div>
                <div class="stat-value" style="font-size: 36px; font-weight: 700; margin: 10px 0;"><?php echo $total_collections->transaction_count ?? 0; ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card purple">
                <div class="stat-label" style="font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">Students Served</div>
                <div class="stat-value" style="font-size: 36px; font-weight: 700; margin: 10px 0;"><?php echo $total_collections->unique_students ?? 0; ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h4 style="color: #2d3748; font-weight: 600; margin-bottom: 20px;">Fee Breakdown</h4>
        </div>
        <div class="col-lg-6 col-12">
            <h5 style="color: #22543d; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #c6f6d5;"><i class="fa fa-check-circle"></i> Collected</h5>
            <?php if($feeding_enabled): ?>
            <div class="fee-card" data-fee-type="feeding" data-category="collected" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #fed7d7; color: #c53030;"><i class="fa fa-utensils"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Feeding</div><div style="font-size: 12px; color: #718096;">Daily meals</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #c53030;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($total_collections->feeding ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($breakfast_enabled): ?>
            <div class="fee-card" data-fee-type="breakfast" data-category="collected" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #feebc8; color: #c05621;"><i class="fa fa-coffee"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Breakfast</div><div style="font-size: 12px; color: #718096;">Morning meals</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #c05621;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($total_collections->breakfast ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($classes_enabled): ?>
            <div class="fee-card" data-fee-type="classes" data-category="collected" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #e9d8fd; color: #6b46c1;"><i class="fa fa-book"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Classes</div><div style="font-size: 12px; color: #718096;">Class fees</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #6b46c1;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($total_collections->classes ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($water_enabled): ?>
            <div class="fee-card" data-fee-type="water" data-category="collected" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #bee3f8; color: #2c5282;"><i class="fa fa-tint"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Water</div><div style="font-size: 12px; color: #718096;">Water fees</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #2c5282;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($total_collections->water ?? 0, 2); ?></div>
            </div>
            <?php endif; ?>
            <?php if($transport_enabled): ?>
            <div class="fee-card" data-fee-type="transport" data-category="collected" data-cashier-id="all">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="fee-icon" style="background: #c6f6d5; color: #22543d;"><i class="fa fa-bus"></i></div>
                    <div><div style="font-weight: 600; color: #2d3748;">Transport</div><div style="font-size: 12px; color: #718096;">Transport fees</div></div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #22543d;"><sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($total_collections->transport ?? 0, 2); ?></div>
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

    <div class="row mt-4">
        <div class="col-md-12">
            <h4 style="color: #2d3748; font-weight: 600; margin-bottom: 20px;">Collection Performance</h4>
            <table class="cashier-table">
                <thead>
                    <tr>
                        <th>Collector Name</th>
                        <th style="text-align: center;">Role</th>
                        <th style="text-align: right;">Total Collected</th>
                        <th style="text-align: center;">Transactions</th>
                        <th style="text-align: right;">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Define role names
                    $role_names = [
                        1 => 'Super Admin',
                        2 => 'Admin',
                        3 => 'Teacher',
                        4 => 'Cashier',
                        5 => 'Accountant',
                        6 => 'Librarian'
                    ];
                    
                    foreach ($collector_data as $data): 
                        $percentage = $grand_total > 0 ? ($data['total'] / $grand_total) * 100 : 0;
                        $role_name = isset($role_names[$data['collector_level']]) ? $role_names[$data['collector_level']] : 'Unknown';
                        $is_cashier = ($data['collector_level'] == 4);
                    ?>
                    <tr>
                        <td style="font-weight: 600; color: #2d3748;">
                            <?php echo $data['collector_name'] ?? 'Unknown'; ?>
                        </td>
                        <td style="text-align: center;">
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; <?php echo $is_cashier ? 'background: #dbeafe; color: #1e40af;' : 'background: #fef3c7; color: #92400e;'; ?>">
                                <?php echo $role_name; ?>
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #2d3748; font-size: 16px;">
                            <sup style="font-size: 10px; color: #718096;"><?php echo $currency; ?></sup><?php echo number_format($data['total'], 2); ?>
                        </td>
                        <td style="text-align: center; color: #4a5568;"><?php echo $data['transactions']; ?></td>
                        <td style="text-align: right; color: #4a5568;"><?php echo number_format($percentage, 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f7fafc; font-weight: 700;">
                        <td colspan="2">TOTAL</td>
                        <td style="text-align: right; color: #2d3748; font-size: 18px;">
                            <sup style="font-size: 12px;"><?php echo $currency; ?></sup><?php echo number_format($grand_total, 2); ?>
                        </td>
                        <td style="text-align: center;"><?php echo $total_collections->transaction_count ?? 0; ?></td>
                        <td style="text-align: right;">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
