<?php
/**
 * One-Time Sync — results page
 * Data: $results (table => array with synced/failed), $total_synced,
 *       $total_failed, $tables_processed
 */
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>One-Time Sync Results</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/font-awesome.min.css'); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 14px; color: #1f2937; background: #f3f4f6; padding: 26px 16px; }
        .card { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 12px 30px rgba(0,0,0,.1); overflow: hidden; }
        .head { background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 20px 28px; }
        .head h1 { font-size: 19px; }
        .head p { font-size: 12.5px; opacity: .92; margin-top: 4px; }
        .body { padding: 24px 28px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 22px; }
        .stat { border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; background: #f9fafb; }
        .stat .n { font-size: 24px; font-weight: 800; }
        .stat .l { font-size: 11px; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; font-weight: 700; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111827; color: #fff; text-align: left; padding: 9px 12px; font-size: 10.5px; text-transform: uppercase; letter-spacing: .6px; }
        th.r, td.r { text-align: right; }
        td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .tbl { font-family: 'Courier New', monospace; font-weight: 700; }
        .ok { color: #059669; font-weight: 700; }
        .fail { color: #b91c1c; font-weight: 700; }
        .back { text-align: center; margin-top: 20px; }
        .back a { display: inline-flex; align-items: center; gap: 8px; background: #111827; color: #fff; text-decoration: none; border-radius: 10px; padding: 11px 24px; font-weight: 700; font-size: 13.5px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <h1><i class="fa fa-check-circle"></i> One-Time Sync Complete</h1>
            <p>Sync run finished — summary below</p>
        </div>
        <div class="body">
            <div class="stats">
                <div class="stat"><div class="n"><?php echo intval($tables_processed); ?></div><div class="l">Tables Processed</div></div>
                <div class="stat"><div class="n" style="color:#059669;"><?php echo intval($total_synced); ?></div><div class="l">Records Synced</div></div>
                <div class="stat"><div class="n" style="color:<?php echo $total_failed > 0 ? '#b91c1c' : '#6b7280'; ?>;"><?php echo intval($total_failed); ?></div><div class="l">Failed</div></div>
            </div>

            <table>
                <thead>
                    <tr><th>Table</th><th class="r">Synced</th><th class="r">Failed</th><th class="r">Status</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:26px;color:#6b7280;">No results returned.</td></tr>
                    <?php else: foreach ($results as $table => $r):
                        $synced = isset($r['synced']) ? (int)$r['synced'] : 0;
                        $failed = isset($r['failed']) ? (int)$r['failed'] : 0; ?>
                    <tr>
                        <td class="tbl"><?php echo htmlspecialchars($table); ?></td>
                        <td class="r ok"><?php echo $synced; ?></td>
                        <td class="r <?php echo $failed ? 'fail' : ''; ?>"><?php echo $failed; ?></td>
                        <td class="r"><?php echo $failed == 0 ? '<i class="fa fa-check-circle ok"></i>' : '<i class="fa fa-exclamation-circle fail"></i>'; ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div class="back">
                <a href="<?php echo site_url('one_time_sync'); ?>"><i class="fa fa-arrow-left"></i> Back to Sync Tool</a>
            </div>
        </div>
    </div>
</body>
</html>
