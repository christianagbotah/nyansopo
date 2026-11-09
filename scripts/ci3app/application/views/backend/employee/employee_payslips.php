<!-- Employee Payslips View (Task 16.3) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo get_phrase('my_payslips'); ?> | <?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/datatables.min.css">
    
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        
        .payslips-container {
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
        
        .payslips-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .payslip-row {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            transition: background 0.3s;
        }
        
        .payslip-row:hover {
            background: #f9f9f9;
        }
        
        .payslip-period {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .payslip-details {
            color: #666;
            font-size: 14px;
        }
        
        .payslip-amount {
            font-size: 24px;
            font-weight: 700;
            color: #5cb85c;
        }
        
        .download-btn {
            background: #337ab7;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .download-btn:hover {
            background: #286090;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-draft { background: #6c757d; color: white; }
        .status-approved { background: #5cb85c; color: white; }
        .status-paid { background: #337ab7; color: white; }
        .status-rejected { background: #d9534f; color: white; }
        
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
        
        .download-all-btn {
            background: #5cb85c;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .download-all-btn:hover {
            background: #449d44;
        }
    </style>
</head>
<body>
    <div class="payslips-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="entypo-doc-text"></i> <?php echo get_phrase('my_payslips'); ?></h2>
            <button class="back-btn" onclick="window.location.href='<?php echo base_url(); ?>index.php?employee/portal'">
                <i class="entypo-left-open-big"></i> <?php echo get_phrase('back_to_portal'); ?>
            </button>
            <div style="clear: both;"></div>
        </div>
        
        <!-- Payslips Card -->
        <div class="payslips-card">
            <?php if (!empty($payslips)): ?>
                
                <!-- Download All Button -->
                <?php if (count($payslips) > 1): ?>
                    <button class="download-all-btn" id="downloadAllBtn">
                        <i class="entypo-download"></i> <?php echo get_phrase('download_all_as_zip'); ?>
                    </button>
                <?php endif; ?>
                
                <!-- Payslips List -->
                <?php foreach ($payslips as $payslip): ?>
                    <div class="payslip-row">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="payslip-period">
                                    <?php echo date('F Y', mktime(0, 0, 0, $payslip['month'], 1, $payslip['year'])); ?>
                                    <span class="status-badge status-<?php echo $payslip['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $payslip['status'])); ?>
                                    </span>
                                </div>
                                <div class="payslip-details">
                                    <?php echo get_phrase('gross'); ?>: <strong>GH¢ <?php echo number_format($payslip['gross_salary'], 2); ?></strong> | 
                                    <?php echo get_phrase('deductions'); ?>: <strong>GH¢ <?php echo number_format($payslip['total_deductions'], 2); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-4 text-right">
                                <div style="color: #999; font-size: 12px; margin-bottom: 5px;">
                                    <?php echo get_phrase('net_salary'); ?>
                                </div>
                                <div class="payslip-amount">
                                    GH¢ <?php echo number_format($payslip['net_salary'], 2); ?>
                                </div>
                            </div>
                            <div class="col-md-2 text-right" style="padding-top: 20px;">
                                <button class="download-btn" onclick="downloadPayslip(<?php echo $payslip['pay_id']; ?>, '<?php echo $staff_name; ?>', <?php echo $payslip['month']; ?>, <?php echo $payslip['year']; ?>)">
                                    <i class="entypo-download"></i> <?php echo get_phrase('download'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            <?php else: ?>
                
                <!-- No Data Message -->
                <div class="no-data">
                    <i class="entypo-inbox"></i>
                    <h3><?php echo get_phrase('no_payslips_available_yet'); ?></h3>
                    <p><?php echo get_phrase('your_payslips_will_appear_here_once_processed'); ?></p>
                </div>
                
            <?php endif; ?>
        </div>
        
    </div>
    
    <!-- Scripts -->
    <script src="<?php echo base_url(); ?>assets/js/jquery-1.11.0.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/bootstrap.min.js"></script>
    
    <script>
    function downloadPayslip(payId, staffName, month, year) {
        var url = '<?php echo base_url(); ?>index.php?employee/download_payslip/' + payId;
        window.open(url, '_blank');
    }
    
    $('#downloadAllBtn').on('click', function() {
        var url = '<?php echo base_url(); ?>index.php?employee/download_all_payslips';
        window.location.href = url;
    });
    </script>
</body>
</html>
