<?php
/**
 * Print Expenditure Report — standalone printable page
 * Data: $page_title, $page_heading, $from, $to, $type, $vehicle, $driver
 * Filters: expenditure_date range, type, vehicle, driver.
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';

$exp = $this->db->select('e.*, v.registration_number, CONCAT(d.first_name, " ", d.last_name) AS driver_name')
    ->from('v_expenditure e')
    ->join('v_vehicle v', 'v.vehicle_id = e.vehicle_id', 'left')
    ->join('v_driver d', 'd.driver_id = e.driver_id', 'left');

if ($from != '' && $to != '') {
    $f = is_numeric($from) ? (int)$from : strtotime($from);
    $t = is_numeric($to) ? (int)$to : strtotime($to);
    $exp = $exp->where('e.expenditure_date >=', min($f, $t))->where('e.expenditure_date <=', max($f, $t));
}
if ($type != '' && $type != '0') $exp = $exp->where('e.expenditure_type', $type);
if ($vehicle != '' && $vehicle != 0) $exp = $exp->where('e.vehicle_id', $vehicle);
if ($driver != '' && $driver != 0) $exp = $exp->where('e.driver_id', $driver);

$rows = $exp->order_by('e.expenditure_date', 'DESC')->get()->result_array();
$total_amount = 0;
foreach ($rows as $r) $total_amount += (float)$r['expenditure_amount'];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12.5px; color: #111827; background: #e5e7eb; }
        .sheet { max-width: 920px; margin: 24px auto; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.15); overflow: hidden; }
        .head { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; }
        .head h1 { font-size: 18px; letter-spacing: .5px; text-transform: uppercase; }
        .head .sub { font-size: 11.5px; opacity: .92; margin-top: 4px; }
        .head .date { text-align: right; font-size: 11.5px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.4); border-radius: 8px; padding: 7px 13px; }
        .range { display: inline-block; background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; border-radius: 20px; font-size: 11px; font-weight: 700; padding: 4px 14px; margin-bottom: 12px; }
        .sum { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 18px; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 15px; background: #f9fafb; }
        .card .k { font-size: 10px; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; font-weight: 700; }
        .card .v { font-size: 18px; font-weight: 800; margin-top: 4px; color: #111827; }
        .body { padding: 22px 26px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111827; color: #fff; text-align: left; padding: 9px 11px; font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px; }
        th.r, td.r { text-align: right; }
        td { padding: 8px 11px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .num { width: 34px; text-align: center; font-weight: 700; }
        tr.tot td { background: #d1fae5; font-weight: 800; color: #065f46; border-top: 2px solid #10b981; border-bottom: none; }
        .toolbar { text-align: center; padding: 12px; }
        .toolbar button { background: #111827; color: #fff; border: none; border-radius: 8px; padding: 11px 26px; font-size: 14px; font-weight: 700; cursor: pointer; }
        .foot { border-top: 2px dashed #d1d5db; padding: 16px 26px 20px; display: flex; justify-content: space-between; font-size: 11px; color: #6b7280; }
        .sig { border-top: 1.5px solid #9ca3af; margin-top: 30px; padding-top: 5px; width: 170px; text-align: center; }
        .empty { text-align: center; padding: 40px; color: #6b7280; font-weight: 600; }
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <div>
                <h1><?php echo htmlspecialchars($page_heading); ?></h1>
                <div class="sub"><?php echo htmlspecialchars($school_name); ?></div>
            </div>
            <div class="date"><?php echo date('d M Y'); ?></div>
        </div>
        <div class="body">
            <?php if ($from != '' && $to != ''): ?>
            <div class="range">Period: <?php echo date('d M Y', is_numeric($from) ? (int)$from : strtotime($from)); ?> — <?php echo date('d M Y', is_numeric($to) ? (int)$to : strtotime($to)); ?></div>
            <?php endif; ?>

            <div class="sum">
                <div class="card"><div class="k">Records</div><div class="v"><?php echo count($rows); ?></div></div>
                <div class="card"><div class="k">Total Expenditure</div><div class="v"><?php echo $currency; ?> <?php echo number_format($total_amount, 2); ?></div></div>
                <div class="card"><div class="k">Average</div><div class="v"><?php echo $currency; ?> <?php echo number_format(count($rows) ? $total_amount / count($rows) : 0, 2); ?></div></div>
            </div>

            <table>
                <thead>
                    <tr><th class="num">#</th><th>Date</th><th>Expenditure</th><th>Type</th><th>Vehicle</th><th>Driver</th><th class="r">Amount</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="empty">No expenditure records for the selected filters.</td></tr>
                    <?php else: $sn = 1; foreach ($rows as $r): ?>
                    <tr>
                        <td class="num"><?php echo $sn++; ?></td>
                        <td><?php echo !empty($r['expenditure_date']) ? date('d-m-Y', $r['expenditure_date']) : '—'; ?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($r['expenditure_name']); ?></td>
                        <td><?php echo htmlspecialchars(strtoupper($r['expenditure_type'] ?? '—')); ?></td>
                        <td><?php echo htmlspecialchars($r['registration_number'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($r['driver_name'] ?? '—'); ?></td>
                        <td class="r" style="font-weight:700;color:#b91c1c;"><?php echo number_format((float)$r['expenditure_amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="tot">
                        <td colspan="6">TOTAL</td>
                        <td class="r"><?php echo $currency; ?> <?php echo number_format($total_amount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="foot">
            <div>Generated <?php echo date('d M Y, h:i A'); ?></div>
            <div class="sig">Authorised Signature</div>
        </div>
    </div>
    <div class="toolbar"><button onclick="window.print()">Print</button></div>
</body>
</html>
