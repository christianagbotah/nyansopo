<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #4CAF50; color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9f9f9; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
        .success-box { background: #d4edda; padding: 20px; margin: 20px 0; border-left: 4px solid #4CAF50; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo get_settings('system_name'); ?></h1>
            <p>Expense Approval</p>
        </div>
        
        <div class="content">
            <h2>Expense Approved</h2>
            <p>Your expense request has been approved.</p>
            
            <div class="success-box">
                <p><strong>Description:</strong> <?php echo $description; ?></p>
                <p><strong>Amount:</strong> GHS <?php echo number_format($amount, 2); ?></p>
                <p><strong>Approved By:</strong> <?php echo $approved_by; ?></p>
                <p><strong>Approved Date:</strong> <?php echo date('F d, Y', strtotime($approved_date)); ?></p>
            </div>
            
            <p>The payment will be processed shortly.</p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo get_settings('system_name'); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
