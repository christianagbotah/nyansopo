<?php
$type = $type ?? 'school';
$date = $date ?? strtotime('today');
$student_id = $student_id ?? null;
$class_id = $class_id ?? null;
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$school_name = get_settings('system_name');
$currency = get_settings('currency');

// Get transactions based on type
$transactions = [];
if ($type == 'student' && $student_id) {
    $transactions = $this->db->query("
        SELECT t.*, s.name as student_name, s.student_code, c.name as class_name, pm.name as payment_method_name
        FROM daily_fee_transactions t
        JOIN student s ON s.student_id = t.student_id
        JOIN enroll e ON e.student_id = s.student_id AND e.year = ? AND e.term = ?
        JOIN class c ON c.class_id = e.class_id
        LEFT JOIN payment_methods pm ON pm.id = t.payment_method
        WHERE t.student_id = ? AND t.payment_date = ?
    ", [$running_year, $running_term, $student_id, $date])->result_array();
} elseif ($type == 'class' && $class_id) {
    $transactions = $this->db->query("
        SELECT t.*, s.name as student_name, s.student_code, c.name as class_name, pm.name as payment_method_name
        FROM daily_fee_transactions t
        JOIN student s ON s.student_id = t.student_id
        JOIN enroll e ON e.student_id = s.student_id AND e.year = ? AND e.term = ?
        JOIN class c ON c.class_id = e.class_id
        LEFT JOIN payment_methods pm ON pm.id = t.payment_method
        WHERE e.class_id = ? AND t.payment_date = ?
        ORDER BY s.name
    ", [$running_year, $running_term, $class_id, $date])->result_array();
} else {
    $transactions = $this->db->query("
        SELECT t.*, s.name as student_name, s.student_code, c.name as class_name, pm.name as payment_method_name
        FROM daily_fee_transactions t
        JOIN student s ON s.student_id = t.student_id
        JOIN enroll e ON e.student_id = s.student_id AND e.year = ? AND e.term = ?
        JOIN class c ON c.class_id = e.class_id
        LEFT JOIN payment_methods pm ON pm.id = t.payment_method
        WHERE t.payment_date = ?
        ORDER BY c.name, s.name
    ", [$running_year, $running_term, $date])->result_array();
}

// Check for split payments for each transaction
foreach ($transactions as &$transaction) {
    $splits = $this->db->query("
        SELECT dps.*, pm.name as payment_method_name
        FROM daily_fee_payment_splits dps
        JOIN payment_methods pm ON pm.id = dps.payment_method_id
        WHERE dps.transaction_id = ?
        ORDER BY dps.id
    ", [$transaction['id']])->result_array();
    
    $transaction['is_split_payment'] = !empty($splits);
    $transaction['payment_splits'] = $splits;
}
unset($transaction);

$total_collected = array_sum(array_column($transactions, 'total_amount'));
$total_feeding = array_sum(array_column($transactions, 'feeding_amount'));
$total_breakfast = array_sum(array_column($transactions, 'breakfast_amount'));
$total_classes = array_sum(array_column($transactions, 'classes_amount'));
$total_water = array_sum(array_column($transactions, 'water_amount'));
$total_transport = array_sum(array_column($transactions, 'transport_amount'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fee Collection Receipt - <?php echo $school_name; ?></title>
    <style>
        @media print {
            .no-print { display: none; }
        }
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; color: #666; }
        .info-section { margin: 20px 0; }
        .info-section table { width: 100%; }
        .info-section td { padding: 5px; }
        .transactions-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .transactions-table th, .transactions-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .transactions-table th { background-color: #f4f4f4; font-weight: bold; }
        .transactions-table td.amount { text-align: right; }
        .summary { margin-top: 30px; float: right; width: 300px; }
        .summary table { width: 100%; }
        .summary td { padding: 5px; }
        .summary .total { font-weight: bold; font-size: 18px; border-top: 2px solid #333; }
        .footer { clear: both; margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; }
        .print-btn { margin: 20px 0; padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px; }
        .print-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print Receipt</button>
    
    <div class="header">
        <h1><?php echo $school_name; ?></h1>
        <p>Daily Fee Collection Receipt</p>
        <p>Date: <?php echo date('l, F j, Y', $date); ?></p>
        <p>Academic Year: <?php echo $running_year; ?> | Term: <?php echo $running_term; ?></p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td><strong>Receipt Type:</strong></td>
                <td><?php echo ucfirst($type); ?> Receipt</td>
                <td><strong>Generated:</strong></td>
                <td><?php echo date('Y-m-d H:i:s'); ?></td>
            </tr>
            <?php if ($type == 'student' && $student_id): 
                $student = $this->db->get_where('student', ['student_id' => $student_id])->row();
            ?>
            <tr>
                <td><strong>Student:</strong></td>
                <td colspan="3"><?php echo $student->name; ?> (<?php echo $student->student_code; ?>)</td>
            </tr>
            <?php elseif ($type == 'class' && $class_id):
                $class = $this->db->get_where('class', ['class_id' => $class_id])->row();
            ?>
            <tr>
                <td><strong>Class:</strong></td>
                <td colspan="3"><?php echo $class->name; ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <?php if (empty($transactions)): ?>
        <p style="text-align: center; padding: 40px; color: #999;">No transactions found for the selected criteria.</p>
    <?php else: ?>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Receipt No</th>
                    <th>Payment Method</th>
                    <th class="amount">Feeding</th>
                    <th class="amount">Breakfast</th>
                    <th class="amount">Classes</th>
                    <th class="amount">Water</th>
                    <th class="amount">Transport</th>
                    <th class="amount">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $index => $t): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $t['student_name']; ?><br><small><?php echo $t['student_code']; ?></small></td>
                    <td><?php echo $t['class_name']; ?></td>
                    <td><?php echo $t['receipt_number'] ?? $t['transaction_code']; ?></td>
                    <td>
                        <?php if ($t['is_split_payment']): ?>
                            <strong style="color: #0066cc;">Split Payment:</strong><br>
                            <small style="line-height: 1.6;">
                                <?php foreach ($t['payment_splits'] as $split): ?>
                                    • <?php echo $split['payment_method_name']; ?>: <?php echo $currency; ?> <?php echo number_format($split['amount'], 2); ?>
                                    <?php if (!empty($split['reference_number'])): ?>
                                        <span style="color: #666;">(Ref: <?php echo $split['reference_number']; ?>)</span>
                                    <?php endif; ?>
                                    <br>
                                <?php endforeach; ?>
                            </small>
                        <?php else: ?>
                            <?php echo $t['payment_method_name'] ?? 'Cash'; ?>
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?php echo number_format($t['feeding_amount'], 2); ?></td>
                    <td class="amount"><?php echo number_format($t['breakfast_amount'], 2); ?></td>
                    <td class="amount"><?php echo number_format($t['classes_amount'], 2); ?></td>
                    <td class="amount"><?php echo number_format($t['water_amount'], 2); ?></td>
                    <td class="amount"><?php echo number_format($t['transport_amount'], 2); ?></td>
                    <td class="amount"><strong><?php echo number_format($t['total_amount'], 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary">
            <table>
                <tr>
                    <td>Feeding Total:</td>
                    <td class="amount"><?php echo $currency; ?> <?php echo number_format($total_feeding, 2); ?></td>
                </tr>
                <tr>
                    <td>Breakfast Total:</td>
                    <td class="amount"><?php echo $currency; ?> <?php echo number_format($total_breakfast, 2); ?></td>
                </tr>
                <tr>
                    <td>Classes Total:</td>
                    <td class="amount"><?php echo $currency; ?> <?php echo number_format($total_classes, 2); ?></td>
                </tr>
                <tr>
                    <td>Water Total:</td>
                    <td class="amount"><?php echo $currency; ?> <?php echo number_format($total_water, 2); ?></td>
                </tr>
                <tr>
                    <td>Transport Total:</td>
                    <td class="amount"><?php echo $currency; ?> <?php echo number_format($total_transport, 2); ?></td>
                </tr>
                <tr class="total">
                    <td>GRAND TOTAL:</td>
                    <td class="amount"><?php echo $currency; ?> <?php echo number_format($total_collected, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 10px; font-size: 12px; color: #666;">
                        Total Transactions: <?php echo count($transactions); ?>
                    </td>
                </tr>
            </table>
        </div>
    <?php endif; ?>

    <div class="footer">
        <p>This is a computer-generated receipt</p>
        <p><?php echo $school_name; ?> - Fee Collection System</p>
    </div>
</body>
</html>
