<?php
/**
 * Daily Fee Payment Receipt (standalone printable page)
 * Data: $transaction (daily_fee_transactions row), $student (+ class_name),
 *       $cashier (admin row), $school_name
 */
$method_names = array(1 => 'Cash', 2 => 'Cheque', 3 => 'Mobile Money', 4 => 'Bank Transfer');
$pm = $transaction['payment_method'];
$method_label = isset($method_names[$pm]) ? $method_names[$pm] : ($pm ?: 'Cash');
$type_labels = array('arrears' => 'Arrears Payment', 'current' => 'Current Fees', 'advance' => 'Advance Payment', 'mixed' => 'Mixed Payment');
$ptype = $transaction['payment_type'];
$type_label = isset($type_labels[$ptype]) ? $type_labels[$ptype] : 'Fee Payment';

$fee_lines = array(
    array('Feeding (Lunch)', $transaction['feeding_amount'], 'fa-utensils'),
    array('Breakfast', $transaction['breakfast_amount'], 'fa-mug-hot'),
    array('Classes Fee', $transaction['classes_amount'], 'fa-school'),
    array('Water', $transaction['water_amount'], 'fa-tint'),
    array('Transport', $transaction['transport_amount'], 'fa-bus')
);
$active_lines = array();
foreach ($fee_lines as $fl) {
    if (floatval($fl[1]) > 0) $active_lines[] = $fl;
}
$system_phone = '';
$phone_row = $this->db->get_where('settings', array('type' => 'phone'))->row();
if ($phone_row) $system_phone = $phone_row->description;
$currency_row = $this->db->get_where('settings', array('type' => 'system_currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Fee Receipt - <?php echo htmlspecialchars($transaction['receipt_number'] ?: $transaction['transaction_code']); ?></title>
    <link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/font-awesome.min.css'); ?>">
    <style type="text/css">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px; color: #1f2937; background: #e5e7eb;
        }
        .sheet {
            max-width: 720px; margin: 24px auto; background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,.15); border-radius: 8px; overflow: hidden;
        }
        .rcpt-head {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; padding: 22px 28px; display: flex; justify-content: space-between; align-items: center;
        }
        .rcpt-head .school { display: flex; align-items: center; gap: 14px; }
        .rcpt-head img { max-height: 58px; background: #fff; border-radius: 8px; padding: 4px; }
        .rcpt-head h1 { font-size: 19px; letter-spacing: .5px; }
        .rcpt-head .sub { font-size: 11.5px; opacity: .92; margin-top: 3px; }
        .rcpt-badge {
            text-align: right; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.4);
            padding: 8px 14px; border-radius: 10px;
        }
        .rcpt-badge .code { font-size: 15px; font-weight: 700; font-family: 'Courier New', monospace; }
        .rcpt-badge .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: .85; }
        .rcpt-body { padding: 24px 28px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 22px; margin-bottom: 20px; }
        .info-item { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #f9fafb; }
        .info-item .k { font-size: 10px; text-transform: uppercase; letter-spacing: .8px; color: #6b7280; font-weight: 700; }
        .info-item .v { font-size: 14px; font-weight: 600; margin-top: 3px; color: #111827; }
        table.fees { width: 100%; border-collapse: collapse; margin: 6px 0 18px; }
        table.fees th {
            background: #111827; color: #fff; text-align: left; font-size: 11px;
            text-transform: uppercase; letter-spacing: .8px; padding: 9px 12px;
        }
        table.fees th.r, table.fees td.r { text-align: right; }
        table.fees td { padding: 9px 12px; border-bottom: 1px solid #e5e7eb; font-size: 13.5px; }
        table.fees tr.total td {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5); font-weight: 800;
            font-size: 15px; border-bottom: none; border-top: 2px solid #10b981;
        }
        .tagline { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px; margin-left: 6px; }
        .tag-arrears { background: #fef2f2; color: #b91c1c; }
        .tag-current { background: #ecfdf5; color: #065f46; }
        .tag-advance { background: #eff6ff; color: #1d4ed8; }
        .tag-mixed { background: #fefce8; color: #a16207; }
        .rcpt-foot {
            border-top: 2px dashed #d1d5db; margin-top: 8px; padding: 16px 28px 22px;
            display: flex; justify-content: space-between; align-items: flex-end;
        }
        .rcpt-foot .note { font-size: 11px; color: #6b7280; max-width: 380px; line-height: 1.5; }
        .sig { text-align: center; }
        .sig .line { width: 180px; border-top: 1.5px solid #9ca3af; margin-top: 34px; padding-top: 5px; font-size: 11px; color: #6b7280; }
        .toolbar { text-align: center; padding: 12px; }
        .toolbar button {
            background: #111827; color: #fff; border: none; border-radius: 8px;
            padding: 11px 26px; font-size: 14px; font-weight: 700; cursor: pointer;
        }
        .toolbar button:hover { background: #374151; }
        @media print {
            body { background: #fff; }
            .sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="rcpt-head">
            <div class="school">
                <img src="<?php echo base_url('uploads/school_logo.png'); ?>" alt="logo" onerror="this.style.display='none'">
                <div>
                    <h1><?php echo htmlspecialchars($school_name); ?></h1>
                    <div class="sub">Daily Fee Payment Receipt<?php if ($system_phone): ?> &middot; <?php echo htmlspecialchars($system_phone); ?><?php endif; ?></div>
                </div>
            </div>
            <div class="rcpt-badge">
                <div class="lbl">Receipt No.</div>
                <div class="code"><?php echo htmlspecialchars($transaction['receipt_number'] ?: $transaction['transaction_code']); ?></div>
            </div>
        </div>

        <div class="rcpt-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="k">Student</div>
                    <div class="v"><?php echo htmlspecialchars($student['name'] ?? '—'); ?></div>
                </div>
                <div class="info-item">
                    <div class="k">Student Code / Class</div>
                    <div class="v"><?php echo htmlspecialchars($student['student_code'] ?? '—'); ?> &middot; <?php echo htmlspecialchars($student['class_name'] ?? '—'); ?></div>
                </div>
                <div class="info-item">
                    <div class="k">Date Paid</div>
                    <div class="v"><?php echo date('d M Y, h:i A', $transaction['payment_date']); ?></div>
                </div>
                <div class="info-item">
                    <div class="k">Payment Method / Point</div>
                    <div class="v"><?php echo $method_label; ?> &middot; <?php echo ucfirst($transaction['collection_point'] ?: 'office'); ?></div>
                </div>
                <div class="info-item">
                    <div class="k">Term / Year</div>
                    <div class="v"><?php echo htmlspecialchars(($transaction['term'] ?: '—') . ' / ' . ($transaction['year'] ?: '—')); ?></div>
                </div>
                <div class="info-item">
                    <div class="k">Residence</div>
                    <div class="v"><?php echo htmlspecialchars(ucfirst($transaction['residence_type'] ?: 'Day')); ?></div>
                </div>
            </div>

            <table class="fees">
                <thead>
                    <tr>
                        <th>Fee Item</th>
                        <th class="r">Amount (<?php echo htmlspecialchars($currency); ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_lines)): ?>
                    <tr><td colspan="2" style="text-align:center;color:#6b7280;">No itemised fees on this receipt</td></tr>
                    <?php else: foreach ($active_lines as $fl): ?>
                    <tr>
                        <td><?php echo $fl[0]; ?></td>
                        <td class="r"><?php echo number_format((float)$fl[1], 2); ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    <tr class="total">
                        <td>TOTAL PAID
                            <span class="tagline tag-<?php echo htmlspecialchars($ptype ?: 'current'); ?>"><?php echo $type_label; ?></span>
                        </td>
                        <td class="r"><?php echo htmlspecialchars($currency); ?> <?php echo number_format((float)$transaction['total_amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if (!empty($transaction['notes'])): ?>
            <div class="info-item" style="margin-bottom:16px;">
                <div class="k">Notes</div>
                <div class="v" style="font-weight:400;font-size:12.5px;"><?php echo htmlspecialchars($transaction['notes']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="rcpt-foot">
            <div class="note">
                Served by: <strong><?php echo htmlspecialchars($cashier['name'] ?? 'Cashier'); ?></strong><br>
                Transaction code: <?php echo htmlspecialchars($transaction['transaction_code']); ?><br>
                Please keep this receipt safe. Payments are non-refundable once processed.
            </div>
            <div class="sig">
                <div class="line">Authorised Signature</div>
            </div>
        </div>
    </div>

    <div class="toolbar">
        <button onclick="window.print()"><i class="fa fa-print"></i> Print Receipt</button>
    </div>
    <script>window.onload = function() { setTimeout(function(){ window.print(); }, 400); };</script>
</body>
</html>
