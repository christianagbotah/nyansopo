<!-- Employee Self-Service Portal (Task 16.2) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo get_phrase('employee_portal'); ?> | <?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/entypo/css/entypo.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .portal-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .portal-header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .welcome-message {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .staff-info {
            color: #666;
            font-size: 16px;
        }
        
        .portal-nav {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .nav-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            color: white;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        
        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .nav-card i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }
        
        .nav-card h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .nav-card p {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .nav-card.payslips {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .nav-card.history {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .logout-btn {
            background: #d9534f;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c9302c;
        }
        
        .footer-text {
            text-align: center;
            color: white;
            margin-top: 30px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="portal-container">
        
        <!-- Portal Header -->
        <div class="portal-header">
            <div class="welcome-message">
                <i class="entypo-user" style="font-size: 48px; color: #667eea;"></i>
                <div style="margin-top: 15px;">
                    <?php echo get_phrase('welcome'); ?>, <strong><?php echo $staff_name; ?></strong>
                </div>
            </div>
            <div class="staff-info">
                <?php echo get_phrase('staff_code'); ?>: <strong><?php echo $staff_code; ?></strong> | 
                <?php echo get_phrase('employment_category'); ?>: <strong><?php echo ucwords(str_replace('_', ' ', $employment_category)); ?></strong>
            </div>
        </div>
        
        <!-- Navigation Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="nav-card payslips" onclick="window.location.href='<?php echo base_url(); ?>index.php?employee/view_payslips'">
                    <i class="entypo-doc-text"></i>
                    <h3><?php echo get_phrase('view_payslips'); ?></h3>
                    <p><?php echo get_phrase('access_your_monthly_payslips'); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="nav-card history" onclick="window.location.href='<?php echo base_url(); ?>index.php?employee/payment_history'">
                    <i class="entypo-chart-line"></i>
                    <h3><?php echo get_phrase('payment_history'); ?></h3>
                    <p><?php echo get_phrase('view_your_payment_history_and_trends'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Logout Button -->
        <div style="text-align: center; margin-top: 30px;">
            <button class="logout-btn" onclick="window.location.href='<?php echo base_url(); ?>index.php?login/logout'">
                <i class="entypo-logout"></i> <?php echo get_phrase('logout'); ?>
            </button>
        </div>
        
        <!-- Footer -->
        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> <?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?>
            <br>
            <?php echo get_phrase('employee_self_service_portal'); ?>
        </div>
        
    </div>
    
    <!-- Scripts -->
    <script src="<?php echo base_url(); ?>assets/js/jquery-1.11.0.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/bootstrap.min.js"></script>
</body>
</html>
