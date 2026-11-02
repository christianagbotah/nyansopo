<?php
/**
 * Fuel Usage Assessment Report — standalone printable page
 * Data: $from, $to, $vehicle_id, $driver_id, $destination_id, $location_id,
 *       $supplier_id, $customer_id, $item_id, $waybill_number (filters)
 * Compares saved actual fuel usage (v_fuel_usage_report) against
 * expected usage derived from destination fuel rates × quantity hauled.
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';

$apply_filters = function (&$db) use ($from, $to, $vehicle_id, $driver_id, $destination_id, $location_id, $supplier_id, $customer_id, $item_id, $waybill_number) {
    if ($from != '' && $to != '') {
        $f = is_numeric($from) ? (int)$from : strtotime($from);
        $t = is_numeric($to) ? (int)$to : strtotime($to);
        $db->where('l.loading_date >=', min($f, $t))->where('l.loading_date <=', max($f, $t));
    }
    if ($vehicle_id != '' && $vehicle_id != 0) $db->where('l.vehicle_id', $vehicle_id);
    if ($driver_id != '' && $driver_id != 0) $db->where('l.driver_id', $driver_id);
    if ($destination_id != '' && $destination_id != 0) $db->where('l.destination_id', $destination_id);
    if ($location_id != '' && $location_id != 0) $db->where('l.location_id', $location_id);
    if ($supplier_id != '' && $supplier_id != 0) $db->where('l.supplier_id', $supplier_id);
    if ($customer_id != '' && $customer_id != 0) $db->where('l.customer_id', $customer_id);
    $clean_item = $item_id;
    if (is_array($clean_item)) $clean_item = reset($clean_item);
    if (is_string($clean_item) && strpos($clean_item, '-') !== false) {
        $parts = explode('-', $clean_item);
        $clean_item = $parts[0];
    }
    if ($clean_item != '' && $clean_item != 0) $db->where('l.item_id', $clean_item);
    if ($waybill_number != '' && $waybill_number != 0) $db->where('l.waybill_number', $waybill_number);
};

// Loadings with destination fuel rate (expected usage basis)
$db = $this->db->select('l.*, v.registration_number, CONCAT(d.first_name, " ", d.last_name) AS driver_name,
        dest.destination_name, dest.fuel_usage, i.item_name')
    ->from('v_loading l')
    ->join('v_vehicle v', 'v.vehicle_id = l.vehicle_id', 'left')
    ->join('v_driver d', 'd.driver_id = l.driver_id', 'left')
    ->join('v_destination dest', 'dest.destination_id = l.destination_id', 'left')
    ->join('items i', 'i.item_id = l.item_id', 'left');
$apply_filters($db);
$rows = $db->order_by('l.loading_date', 'DESC')->limit(500)->get()->result_array();

// Saved actual fuel usage entries covering the range
$fuel = array();
$fuel_db = $this->db->select('f.*')->from('v_fuel_usage_report f');
if ($vehicle_id != '' && $vehicle_id != 0) $fuel_db->where('f.vehicle_id', $vehicle_id);
if ($driver_id != '' && $driver_id != 0) $fuel_db->where('f.driver_id', $driver_id);
foreach ($fuel_db->get()->result_array() as $fr) {
    $fuel[$fr['vehicle_id']] = $fr;
}

$total_qty = 0; $total_expected = 0; $total_actual = 0;
foreach ($rows as $r) {
    $total_qty += (float)$r['quantity'];
    $total_expected += (float)$r['fuel_usage'] * (float)$r['quantity'];
}
foreach ($fuel as $fr) $total_actual += (float)$fr['actual_usage'];
$variance = $total_actual - $total_expected;
$has_range = ($from != '' && $to != '');
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fuel Usage Assessment Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12px; color: #111827; background: #e5e7eb; }
        .sheet { max-width: 1000px; margin: 24px auto; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.15); overflow: hidden; }
        .head { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; }
        .head h1 { font-size: 18px; letter-spacing: .5px; text-transform: uppercase; }
        .head .sub { font-size: 11.5px; opacity: .92; margin-top: 4px; }
        .head .date { text-align: right; font-size: 11.5px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.4); border-radius: 8px; padding: 7px 13px; }
        .range { display: inline-block; background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; border-radius: 20px; font-size: 11px; font-weight: 700; padding: 4px 14px; margin: 18px 26px 0; }
        .sum { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 14px 26px 0; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 15px; background: #f9fafb; }
        .card .k { font-size: 10px; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; font-weight: 700; }
        .card .v { font-size: 17px; font-weight: 800; margin-top: 4px; }
        .body { padding: 18px 26px 22px; overflow-x: auto; }
        h3.sec { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #4338ca; margin: 18px 0 8px; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111827; color: #fff; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: .6px; }
        th.r, td.r { text-align: right; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .num { width: 30px; text-align: center; font-weight: 700; }
        tr.tot td { background: #d1fae5; font-weight: 800; color: #065f46; border-top: 2px solid #10b981; border-bottom: none; }
        .toolbar { text-align: center; padding: 12px; }
        .toolbar button { background: #111827; color: #fff; border: none; border-radius: 8px; padding: 11px 26px; font-size: 14px; font-weight: 700; cursor: pointer; }
        .foot { border-top: 2px dashed #d1d5db; padding: 14px 26px 18px; display: flex; justify-content: space-between; font-size: 11px; color: #6b7280; }
        .sig { border-top: 1.5px solid #9ca3af; margin-top: 26px; padding-top: 5px; width: 170px; text-align: center; }
        .empty { text-align: center; padding: 40px; color: #6b7280; font-weight: 600; }
        .good { color: #059669; } .bad { color: #b91c1c; }
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <div>
                <h1>Fuel Usage Assessment Report</h1>
                <div class="sub"><?php echo htmlspecialchars($school_name); ?></div>
            </div>
            <div class="date"><?php echo date('d M Y'); ?></div>
        </div>
        <?php if ($has_range): ?>
        <div class="range">Period: <?php echo date('d M Y', is_numeric($from) ? (int)$from : strtotime($from)); ?> — <?php echo date('d M Y', is_numeric($to) ? (int)$to : strtotime($to)); ?></div>
        <?php endif; ?>

        <div class="sum">
            <div class="card"><div class="k">Trips Assessed</div><div class="v"><?php echo count($rows); ?></div></div>
            <div class="card"><div class="k">Expected Usage</div><div class="v"><?php echo number_format($total_expected, 2); ?> L</div></div>
            <div class="card"><div class="k">Actual Recorded</div><div class="v"><?php echo number_format($total_actual, 2); ?> L</div></div>
            <div class="card"><div class="k">Variance</div><div class="v <?php echo $variance > 0 ? 'bad' : 'good'; ?>"><?php echo number_format($variance, 2); ?> L</div></div>
        </div>

        <div class="body">
            <h3 class="sec">Saved Fuel Entries (actual usage)</h3>
            <table>
                <thead>
                    <tr><th class="num">#</th><th>Truck</th><th>Period</th><th>Destination</th><th class="r">Actual Usage (L)</th><th>Recorded By</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($fuel)): ?>
                    <tr><td colspan="6" class="empty">No saved fuel usage entries for the selected filters.</td></tr>
                    <?php else: $sn = 1; foreach ($fuel as $fr):
                        $truck = $this->db->get_where('v_vehicle', array('vehicle_id' => $fr['vehicle_id']))->row();
                        $dest = $this->db->get_where('v_destination', array('destination_id' => $fr['destination_id']))->row(); ?>
                    <tr>
                        <td class="num"><?php echo $sn++; ?></td>
                        <td style="font-weight:700;"><?php echo htmlspecialchars($truck->registration_number ?? '—'); ?></td>
                        <td><?php echo (!empty($fr['from_date']) ? date('d-m-Y', $fr['from_date']) : '—') . ' — ' . (!empty($fr['to_date']) ? date('d-m-Y', $fr['to_date']) : '—'); ?></td>
                        <td><?php echo htmlspecialchars($dest->destination_name ?? '—'); ?></td>
                        <td class="r" style="font-weight:700;"><?php echo number_format((float)$fr['actual_usage'], 2); ?></td>
                        <td><?php echo htmlspecialchars($fr['edited_user'] ?? $fr['created_user'] ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <h3 class="sec">Expected Usage Basis (loadings × destination fuel rate)</h3>
            <table>
                <thead>
                    <tr><th class="num">#</th><th>Date</th><th>Truck</th><th>Destination</th><th class="r">Quantity</th><th class="r">Rate (L/unit)</th><th class="r">Expected (L)</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="empty">No loading records for the selected filters.</td></tr>
                    <?php else: $sn = 1; foreach ($rows as $r):
                        $expected = (float)$r['fuel_usage'] * (float)$r['quantity']; ?>
                    <tr>
                        <td class="num"><?php echo $sn++; ?></td>
                        <td><?php echo !empty($r['loading_date']) ? date('d-m-Y', $r['loading_date']) : '—'; ?></td>
                        <td style="font-weight:700;"><?php echo htmlspecialchars($r['registration_number'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($r['destination_name'] ?? '—'); ?></td>
                        <td class="r"><?php echo number_format((float)$r['quantity'], 2); ?></td>
                        <td class="r"><?php echo number_format((float)$r['fuel_usage'], 2); ?></td>
                        <td class="r" style="font-weight:700;"><?php echo number_format($expected, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="tot">
                        <td colspan="6">TOTAL EXPECTED</td>
                        <td class="r"><?php echo number_format($total_expected, 2); ?> L</td>
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
