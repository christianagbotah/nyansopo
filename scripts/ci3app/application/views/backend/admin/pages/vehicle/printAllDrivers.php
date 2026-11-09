<?php
/**
 * Print All Drivers — standalone printable page
 * Data: $page_title, $page_heading, $allDrivers (v_driver rows)
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12.5px; color: #111827; background: #e5e7eb; }
        .sheet { max-width: 860px; margin: 24px auto; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.15); overflow: hidden; }
        .head { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; }
        .head h1 { font-size: 19px; letter-spacing: .5px; text-transform: uppercase; }
        .head .sub { font-size: 11.5px; opacity: .92; margin-top: 4px; }
        .head .date { text-align: right; font-size: 11.5px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.4); border-radius: 8px; padding: 7px 13px; }
        .body { padding: 22px 26px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111827; color: #fff; text-align: left; padding: 9px 11px; font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px; }
        td { padding: 8px 11px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .num { width: 34px; text-align: center; font-weight: 700; }
        .name { font-weight: 700; }
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
                    <tr><th class="num">#</th><th>Name</th><th>Mobile</th><th>Email</th><th>Created</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($allDrivers)): ?>
                    <tr><td colspan="5" class="empty">No drivers on record.</td></tr>
                    <?php else: $sn = 1; foreach ($allDrivers as $d): ?>
                    <tr>
                        <td class="num"><?php echo $sn++; ?></td>
                        <td class="name"><?php echo htmlspecialchars(trim($d['first_name'] . ' ' . ($d['middle_name'] ?? '') . ' ' . $d['last_name'])); ?></td>
                        <td><?php echo htmlspecialchars($d['mobile_1'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($d['email'] ?? '—'); ?></td>
                        <td><?php echo !empty($d['date_created']) ? date('d-m-Y', $d['date_created']) : '—'; ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="foot">
            <div>Total: <strong><?php echo count($allDrivers); ?></strong> driver(s)</div>
            <div class="sig">Authorised Signature</div>
        </div>
    </div>
    <div class="toolbar"><button onclick="window.print()">Print</button></div>
</body>
</html>
