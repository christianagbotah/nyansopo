<?php
/**
 * 404 Page — standalone (Login.php::four_zero_four())
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$system_name = $school_row ? $school_row->description : 'School Management System';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>404 — Page Not Found</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/font-awesome.min.css'); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px;
        }
        .card {
            background: #fff; border-radius: 20px; max-width: 560px; width: 100%;
            padding: 56px 40px; text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,.25);
        }
        .code {
            font-size: 92px; font-weight: 800; line-height: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        }
        h1 { font-size: 22px; color: #111827; margin: 14px 0 8px; }
        p { color: #6b7280; font-size: 14.5px; line-height: 1.6; margin-bottom: 28px; }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
            font-weight: 700; font-size: 14px; border-radius: 10px; padding: 12px 26px; transition: all .2s;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(102,126,234,.45); }
        .btn-ghost { background: #fff; color: #374151; border: 2px solid #d1d5db; }
        .btn-ghost:hover { background: #f9fafb; }
        .brand { margin-top: 26px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">404</div>
        <h1>Oops! Page not found</h1>
        <p>The page you are looking for might have been removed, renamed,<br>or is temporarily unavailable.</p>
        <div class="actions">
            <a class="btn btn-primary" href="<?php echo site_url('login'); ?>"><i class="fa fa-home"></i> Go to Login</a>
            <a class="btn btn-ghost" href="javascript:history.back()"><i class="fa fa-arrow-left"></i> Go Back</a>
        </div>
        <div class="brand"><?php echo htmlspecialchars($system_name); ?></div>
    </div>
</body>
</html>
