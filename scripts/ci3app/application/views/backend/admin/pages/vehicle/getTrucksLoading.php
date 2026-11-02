<?php
/**
 * Trucks Loading List — standalone printable page
 * No controller data; fetches v_loading with display joins directly.
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';

$loadings = $this->db->select('l.*, s.supplier_name, loc.location_name, dest.destination_name, v.registration_number,
        CONCAT(d.first_name, " ", d.last_name) AS driver_name, c.customer_name, i.item_name')
    ->from('v_loading l')
    ->join('suppliers s', 's.supplier_id = l.supplier_id', 'left')
    ->join('v_location loc', 'loc.location_id = l.location_id', 'left')
    ->join('v_destination dest', 'dest.destination_id = l.destination_id', 'left')
    ->join('v_vehicle v', 'v.vehicle_id = l.vehicle_id', 'left')
    ->join('v_driver d', 'd.driver_id = l.driver_id', 'left')
    ->join('customers c', 'c.customer_id = l.customer_id', 'left')
    ->join('items i', 'i.item_id = l.item_id', 'left')
    ->order_by('l.loading_date', 'DESC')
    ->limit(300)
    ->get()
    ->result_array();

$total_qty = 0; $total_amt = 0;
foreach ($loadings as $l) { $total_qty += (float)$l['quantity']; $total_amt += (float)$l['total_amount']; }
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trucks Loading</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12px; color: #111827; background: #e5e7eb; }
        .sheet { max-width: 1080px; margin: 24px auto; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.15); overflow: hidden; }
        .head { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; }
        .head h1 { font-size: 18px; letter-spacing: .5px; text-transform: uppercase; }
        .head .sub { font-size: 11.5px; opacity: .92; margin-top: 4px; }
        .head .date { text-align: right; font-size: 11.5px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.4); border-radius: 8px; padding: 7px 13px; }
        .sum { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 18px 26px 0; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 15px; background: #f9fafb; }
        .card .k { font-size: 10px; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; font-weight: 700; }
        .card .v { font-size: 18px; font-weight: 800; margin-top: 4px; }
        .body { padding: 18px 26px 22px; overflow-x: auto; }
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
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <div>
                <h1>Trucks Loading</h1>
                <div class="sub"><?php echo htmlspecialchars($school_name); ?></div>
            </div>
            <div class="date"><?php echo date('d M Y'); ?></div>
        </div>
        <div class="sum">
            <div class="card"><div class="k">Loading Records</div><div class="v"><?php echo count($loadings); ?></div></div>
            <div class="card"><div class="k">Total Quantity</div><div class="v"><?php echo number_format($total_qty, 2); ?></div></div>
            <div class="card"><div class="k">Total Value</div><div class="v"><?php echo $currency; ?> <?php echo number_format($total_amt, 2); ?></div></div>
        </div>
        <div class="body">
            <table>
                <thead>
                    <tr>
                        <th class="num">#</th><th>Date</th><th>Waybill</th><th>Truck</th><th>Driver</th>
                        <th>Supplier</th><th>Destination</th><th>Item</th>
                        <th class="r">Qty</th><th class="r">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($loadings)): ?>
                    <tr><td colspan="10" class="empty">No loading records.</td></tr>
                    <?php else: $sn = 1; foreach ($loadings as $l): ?>
                    <tr>
                        <td class="num"><?php echo $sn++; ?></td>
                        <td><?php echo !empty($l['loading_date']) ? date('d-m-Y', $l['loading_date']) : '—'; ?></td>
                        <td style="font-family:'Courier New',monospace;font-weight:700;"><?php echo htmlspecialchars($l['waybill_number'] ?? '—'); ?></td>
                        <td style="font-weight:700;"><?php echo htmlspecialchars($l['registration_number'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($l['driver_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($l['supplier_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($l['destination_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($l['item_name'] ?? '—'); ?></td>
                        <td class="r"><?php echo number_format((float)$l['quantity'], 2); ?></td>
                        <td class="r" style="font-weight:700;"><?php echo number_format((float)$l['total_amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="tot">
                        <td colspan="8">TOTALS</td>
                        <td class="r"><?php echo number_format($total_qty, 2); ?></td>
                        <td class="r"><?php echo $currency; ?> <?php echo number_format($total_amt, 2); ?></td>
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
