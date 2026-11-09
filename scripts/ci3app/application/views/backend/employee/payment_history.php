<!-- Payment History View (Task 16.5) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo get_phrase('payment_history'); ?> | <?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/entypo/css/entypo.css">
    
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        
        .history-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-header h2 {
            margin: 0;
            color: #333;
            display: inline-block;
        }
        
        .back-btn {
            float: right;
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .back-btn:hover {
            background: #5568d3;
        }
        
        .year-selector {
            float: right;
            margin-left: 15px;
        }
        
        .year-selector select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .ytd-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .ytd-title {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .ytd-stats {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }
        
        .ytd-stat {
            text-align: center;
            margin: 10px;
        }
        
        .ytd-label {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .ytd-value {
            font-size: 28px;
            font-weight: bold;
        }
        
        .monthly-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .monthly-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .monthly-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        
        .monthly-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .monthly-table tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-draft { background: #6c757d; color: white; }
        .status-approved { background: #5cb85c; color: white; }
        .status-paid { background: #337ab7; color: white; }
        .status-rejected { background: #d9534f; color: white; }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-data i {
            font-size: 72px;
            margin-bottom: 20px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="history-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="entypo-chart-line"></i> <?php echo get_phrase('payment_history'); ?></h2>
            <div class="year-selector">
                <select id="yearSelector" onchange="changeYear(this.value)">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $year) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <button class="back-btn" onclick="window.location.href='<?php echo base_url(); ?>index.php?employee/portal'">
                <i class="entypo-left-open-big"></i> <?php echo get_phrase('back_to_portal'); ?>
            </button>
            <div style="clear: both;"></div>
        </div>
        
        <!-- Year-to-Date Summary Card -->
        <div class="ytd-card">
            <div class="ytd-title">
                <i class="entypo-calendar"></i> <?php echo get_phrase('year_to_date_summary'); ?> - <?php echo $year; ?>
            </div>
            <div class="ytd-stats">
                <div class="ytd-stat">
                    <div class="ytd-label"><?php echo get_phrase('total_gross'); ?></div>
                    <div class="ytd-value">GH¢ <?php echo number_format($ytd_summary['ytd_gross'] ?: 0, 2); ?></div>
                </div>
                <div class="ytd-stat">
                    <div class="ytd-label"><?php echo get_phrase('total_deductions'); ?></div>
                    <div class="ytd-value">GH¢ <?php echo number_format($ytd_summary['ytd_deductions'] ?: 0, 2); ?></div>
                </div>
                <div class="ytd-stat">
                    <div class="ytd-label"><?php echo get_phrase('total_net'); ?></div>
                    <div class="ytd-value">GH¢ <?php echo number_format($ytd_summary['ytd_net'] ?: 0, 2); ?></div>
                </div>
                <div class="ytd-stat">
                    <div class="ytd-label"><?php echo get_phrase('average_monthly'); ?></div>
                    <div class="ytd-value">GH¢ <?php echo number_format($avg_monthly_pay, 2); ?></div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($monthly_breakdown)): ?>
            
            <!-- Monthly Breakdown Table -->
            <div class="monthly-card">
                <div class="section-title"><?php echo get_phrase('monthly_breakdown'); ?></div>
                <table class="monthly-table">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('month'); ?></th>
                            <th><?php echo get_phrase('gross_salary'); ?></th>
                            <th><?php echo get_phrase('deductions'); ?></th>
                            <th><?php echo get_phrase('net_salary'); ?></th>
                            <th><?php echo get_phrase('status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_breakdown as $month_data): ?>
                            <tr>
                                <td><?php echo date('F Y', mktime(0, 0, 0, $month_data['month'], 1, $month_data['year'])); ?></td>
                                <td>GH¢ <?php echo number_format($month_data['gross_salary'], 2); ?></td>
                                <td>GH¢ <?php echo number_format($month_data['total_deductions'], 2); ?></td>
                                <td><strong>GH¢ <?php echo number_format($month_data['net_salary'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $month_data['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $month_data['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Salary Trend Chart -->
            <div class="chart-container">
                <div class="section-title"><?php echo get_phrase('net_salary_trend'); ?></div>
                <canvas id="salaryTrendChart" style="height: 300px;"></canvas>
            </div>
            
        <?php else: ?>
            
            <!-- No Data Message -->
            <div class="monthly-card">
                <div class="no-data">
                    <i class="entypo-inbox"></i>
                    <h3><?php echo get_phrase('no_payment_records_for'); ?> <?php echo $year; ?></h3>
                    <p><?php echo get_phrase('select_a_different_year_to_view_payment_history'); ?></p>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
    
    <!-- Scripts -->
    <script src="<?php echo base_url(); ?>assets/js/jquery-1.11.0.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/bootstrap.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
    
    <script>
    function changeYear(year) {
        window.location.href = '<?php echo base_url(); ?>index.php?employee/payment_history&year=' + year;
    }
    
    <?php if (!empty($monthly_breakdown)): ?>
    // Render salary trend chart
    var ctx = document.getElementById('salaryTrendChart').getContext('2d');
    
    var monthLabels = [];
    var netSalaryData = [];
    var grossSalaryData = [];
    
    <?php foreach ($monthly_breakdown as $month_data): ?>
        monthLabels.push('<?php echo date('M', mktime(0, 0, 0, $month_data['month'], 1)); ?>');
        netSalaryData.push(<?php echo $month_data['net_salary']; ?>);
        grossSalaryData.push(<?php echo $month_data['gross_salary']; ?>);
    <?php endforeach; ?>
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    label: 'Net Salary',
                    data: netSalaryData,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Gross Salary',
                    data: grossSalaryData,
                    borderColor: '#5cb85c',
                    backgroundColor: 'rgba(92, 184, 92, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': GH¢ ' + context.parsed.y.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return 'GH¢ ' + value.toFixed(0).replace(/\d(?=(\d{3})+$)/g, '$&,');
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>
