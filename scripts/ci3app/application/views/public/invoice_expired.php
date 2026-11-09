<?php
/**
 * Public Invoice — expired/invalid token page (no auth context)
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Link Expired — <?php echo htmlspecialchars($school_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; }
        .card { background: #fff; border-radius: 20px; max-width: 480px; width: 100%; padding: 48px 36px; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,.3); }
        .ico { width: 84px; height: 84px; margin: 0 auto 20px; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center; }
        .ico span { font-size: 40px; }
        h1 { font-size: 22px; color: #111827; margin-bottom: 10px; }
        p { color: #6b7280; font-size: 14.5px; line-height: 1.65; margin-bottom: 26px; }
        .brand { margin-top: 22px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="ico"><span>&#9203;</span></div>
        <h1>This invoice link has expired</h1>
        <p>
            The invoice link you followed is no longer valid. Invoice links are
            time-limited for security. Please contact the school accounts office
            to request a new link or a printed copy of your invoice.
        </p>
        <div class="brand"><?php echo htmlspecialchars($school_name); ?></div>
    </div>
</body>
</html>
