<?php
/**
 * One-Time Sync — admin tool interface
 * Data: $tables_without_sync (array of table names lacking sync columns)
 */
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>One-Time Sync Tool</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/font-awesome.min.css'); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 14px; color: #1f2937; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px 16px; }
        .card { max-width: 860px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.25); overflow: hidden; }
        .head { background: linear-gradient(135deg, #111827, #374151); color: #fff; padding: 22px 28px; display: flex; align-items: center; gap: 14px; }
        .head h1 { font-size: 20px; }
        .head p { font-size: 12.5px; opacity: .85; margin-top: 4px; }
        .body { padding: 26px 28px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 22px; }
        .stat { border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; background: #f9fafb; }
        .stat .n { font-size: 24px; font-weight: 800; }
        .stat .l { font-size: 11px; text-transform: uppercase; letter-spacing: .7px; color: #6b7280; font-weight: 700; margin-top: 2px; }
        .tbl-list { max-height: 320px; overflow-y: auto; border: 1.5px solid #e5e7eb; border-radius: 12px; margin-bottom: 22px; }
        .tbl-row { display: flex; align-items: center; gap: 10px; padding: 9px 14px; border-bottom: 1px solid #f3f4f6; font-family: 'Courier New', monospace; font-size: 13px; font-weight: 600; }
        .tbl-row i { color: #f59e0b; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; border: none; border-radius: 10px; padding: 12px 24px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .2s; }
        .btn-dry { background: #f9fafb; color: #374151; border: 2px solid #d1d5db; }
        .btn-dry:hover { background: #f3f4f6; }
        .btn-run { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; box-shadow: 0 6px 16px rgba(102,126,234,.4); }
        .btn-run:hover { transform: translateY(-2px); }
        .warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 10px; padding: 12px 16px; font-size: 13px; margin-bottom: 20px; line-height: 1.6; }
        .ok { text-align: center; padding: 50px 20px; }
        .ok .big { font-size: 46px; color: #10b981; }
        .ok h3 { font-size: 18px; margin: 12px 0 6px; }
        .ok p { color: #6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <i class="fa fa-sync fa-2x"></i>
            <div>
                <h1>One-Time Sync Tool</h1>
                <p>Adds sync columns and backfills sync metadata for tables that lack them</p>
            </div>
        </div>
        <div class="body">
            <?php if (empty($tables_without_sync)): ?>
            <div class="ok">
                <div class="big"><i class="fa fa-check-circle"></i></div>
                <h3>All tables are synced</h3>
                <p>No tables are missing sync columns. Nothing to do.</p>
            </div>
            <?php else: ?>
            <div class="stats">
                <div class="stat"><div class="n"><?php echo count($tables_without_sync); ?></div><div class="l">Tables without sync</div></div>
                <div class="stat"><div class="n" style="color:#f59e0b;">!</div><div class="l">Backup first</div></div>
                <div class="stat"><div class="n">CLI</div><div class="l">Also available</div></div>
            </div>

            <div class="warn">
                <strong><i class="fa fa-exclamation-triangle"></i> Before running:</strong> take a full database backup.
                The sync run adds columns and updates records in every table listed below. Use
                <strong>Dry Run</strong> first to preview what would happen without changing data.
            </div>

            <div class="tbl-list">
                <?php foreach ($tables_without_sync as $t): ?>
                <div class="tbl-row"><i class="fa fa-table"></i> <?php echo htmlspecialchars($t); ?></div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <a class="btn btn-dry" href="<?php echo site_url('one_time_sync/run/1'); ?>"><i class="fa fa-eye"></i> Dry Run</a>
                <a class="btn btn-run" href="<?php echo site_url('one_time_sync/run/0'); ?>" onclick="return confirm('Run the real sync now? Make sure you have a database backup.');"><i class="fa fa-play"></i> Run Full Sync</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
