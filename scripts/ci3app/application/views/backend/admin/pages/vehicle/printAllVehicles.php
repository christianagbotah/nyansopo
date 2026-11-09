<?php
/**
 * Print All Vehicles — standalone printable page
 * Data: $page_title, $page_heading, $allVehicles (v_vehicle rows)
 * Resolves type/model/transporter/driver names via joins (legacy view pattern).
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';

// Resolve names directly from lookup tables
$type_names = array();
foreach ($this->db->get('v_vehicle_type')->result_array() as $t) $type_names[$t['type_id']] = $t['type_name'];
$model_names = array();
foreach ($this->db->get('v_vehicle_model')->result_array() as $m) $model_names[$m['model_id']] = $m['model_name'];
$transporter_names = array();
foreach ($this->db->get('v_transporter')->result_array() as $t) $transporter_names[$t['transporter_id']] = $t['transporter_name'];
$driver_names = array();
foreach ($this->db->get('v_driver')->result_array() as $d) {
    $driver_names[$d['driver_id']] = trim($d['first_name'] . ' ' . $d['last_name']);
}
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
        .head h1 { font-size: 19px; letter-spacing: .5px; text-transform: uppercase; }
        .head .sub { font-size: 11.5px; opacity: .92; margin-top: 4px; }
        .head .date { text-align: right; font-size: 11.5px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.4); border-radius: 8px; padding: 7px 13px; }
        .body { padding: 22px 26px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111827; color: #fff; text-align: left; padding: 9px 11px; font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px; }
        td { padding: 8px 11px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .num { width: 34px; text-align: center; font-weight: 700; }
        .reg { font-weight: 800; font-family: 'Courier New', monospace; }
        .pill { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 2px 10px; border-radius: 20px; }
        .active { background: #d1fae5; color: #065f46; }
        .inactive { background: #fef2f2; color: #b91c1c; }
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
            <table>
                <thead>
                    <tr><th class="num">#</th><th>Registration</th><th>Type</th><th>Model</th><th>Transporter</th><th>Driver</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($allVehicles)): ?>
                    <tr><td colspan="7" class="empty">No vehicles on record.</td></tr>
                    <?php else: $sn = 1; foreach ($allVehicles as $v):
                        $is_active = strtolower($v['status'] ?? '') === 'active' || $v['status'] == 1; ?>
                    <tr>
                        <td class="num"><?php echo $sn++; ?></td>
                        <td class="reg"><?php echo htmlspecialchars($v['registration_number']); ?></td>
                        <td><?php echo htmlspecialchars($type_names[$v['type_id']] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($model_names[$v['model_id']] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($transporter_names[$v['transporter_id']] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($driver_names[$v['driver_id']] ?? '—'); ?></td>
                        <td><span class="pill <?php echo $is_active ? 'active' : 'inactive'; ?>"><?php echo $is_active ? 'Active' : 'Inactive'; ?></span></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="foot">
            <div>Total: <strong><?php echo count($allVehicles); ?></strong> vehicle(s)</div>
            <div class="sig">Authorised Signature</div>
        </div>
    </div>
    <div class="toolbar"><button onclick="window.print()">Print</button></div>
</body>
</html>
