<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report - <?php echo $system_name; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { padding: 15px; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 15px; }
        .header h1 { color: #667eea; font-size: 20px; margin-bottom: 3px; }
        .header h2 { color: #666; font-size: 14px; margin-bottom: 8px; }
        .header p { color: #888; font-size: 11px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .stat-box { border: 2px solid #e5e7eb; padding: 10px; text-align: center; border-radius: 6px; }
        .stat-box.green { border-color: #10b981; }
        .stat-box.red { border-color: #ef4444; }
        .stat-box.orange { border-color: #f59e0b; }
        .stat-box.blue { border-color: #3b82f6; }
        .stat-label { font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 3px; }
        .stat-value { font-size: 20px; font-weight: bold; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
        th { background: #f9fafb; padding: 6px 4px; text-align: left; font-weight: 600; border: 1px solid #e5e7eb; font-size: 10px; }
        td { padding: 5px 4px; border: 1px solid #e5e7eb; font-size: 10px; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .footer { margin-top: 20px; text-align: center; color: #888; font-size: 10px; }
        .btn-print { margin: 10px 0; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
        @media print {
            .no-print { display: none; }
            body { padding: 10px; }
            .header { margin-bottom: 15px; padding-bottom: 10px; }
            .stats { gap: 8px; margin-bottom: 15px; }
            table { margin-top: 10px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo $system_name; ?></h1>
        <h2>Attendance Report</h2>
        <p>Period: <strong><?php echo $start_date . ' to ' . $end_date; ?></strong></p>
        <button onclick="window.print()" class="btn-print no-print">🖨️ Print Report</button>
    </div>

    <div class="stats">
        <div class="stat-box blue">
            <div class="stat-label">Total Days</div>
            <div class="stat-value"><?php echo $stats['total_days']; ?></div>
        </div>
        <div class="stat-box green">
            <div class="stat-label">Present</div>
            <div class="stat-value"><?php echo $stats['total_present']; ?></div>
        </div>
        <div class="stat-box red">
            <div class="stat-label">Absent</div>
            <div class="stat-value"><?php echo $stats['total_absent']; ?></div>
        </div>
        <div class="stat-box orange">
            <div class="stat-label">Attendance Rate</div>
            <div class="stat-value"><?php echo $stats['attendance_rate']; ?>%</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Student Name</th>
                <th style="width: 15%;">Class</th>
                <th style="width: 7%; text-align: center;">Present</th>
                <th style="width: 7%; text-align: center;">Absent</th>
                <th style="width: 7%; text-align: center;">Late</th>
                <th style="width: 9%; text-align: center;">Sick-Home</th>
                <th style="width: 9%; text-align: center;">Sick-Clinic</th>
                <th style="width: 8%; text-align: center;">Excused</th>
                <th style="width: 8%; text-align: center;">Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($students as $s): 
                $rate = $s['percentage'];
                $badgeClass = $rate >= 90 ? 'badge-success' : ($rate >= 75 ? 'badge-warning' : 'badge-danger');
            ?>
            <tr>
                <td style="font-weight: 600; font-size: 10px;"><?php echo $s['name']; ?></td>
                <td style="font-size: 10px;">
                    <?php echo $s['class_name']; ?>
                    <?php if($s['section_name']): ?>
                        - <span style="color: #6b7280;"><?php echo $s['section_name']; ?></span>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;"><span class="badge badge-success"><?php echo $s['present']; ?></span></td>
                <td style="text-align: center;"><span class="badge badge-danger"><?php echo $s['absent']; ?></span></td>
                <td style="text-align: center;"><span class="badge badge-warning"><?php echo $s['late']; ?></span></td>
                <td style="text-align: center;"><span class="badge" style="background: #fef3c7; color: #92400e;"><?php echo $s['sick_home']; ?></span></td>
                <td style="text-align: center;"><span class="badge" style="background: #dbeafe; color: #1e40af;"><?php echo $s['sick_clinic']; ?></span></td>
                <td style="text-align: center;"><span class="badge" style="background: #ede9fe; color: #5b21b6;"><?php echo $s['excused'] ?? 0; ?></span></td>
                <td style="text-align: center;"><span class="badge <?php echo $badgeClass; ?>"><?php echo $rate; ?>%</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on <?php echo date('F d, Y \a\t h:i A'); ?></p>
        <p><?php echo $system_name; ?> - Attendance Management System</p>
    </div>
</body>
</html>
