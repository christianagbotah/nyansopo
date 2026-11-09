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
        .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #4CAF50; }
        .amount { font-size: 24px; color: #4CAF50; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo get_settings('system_name'); ?></h1>
            <p>Payment Confirmation</p>
        </div>
        
        <div class="content">
            <h2>Payment Received Successfully</h2>
            <p>Dear Parent/Guardian,</p>
            <p>We have received your payment for <strong><?php echo $student_name; ?></strong>.</p>
            
            <div class="info-box">
                <p><strong>Receipt Number:</strong> <?php echo $receipt_number; ?></p>
                <p><strong>Amount Paid:</strong> <span class="amount">GHS <?php echo number_format($amount, 2); ?></span></p>
                <p><strong>Payment Date:</strong> <?php echo date('F d, Y', strtotime($payment_date)); ?></p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst($payment_method); ?></p>
            </div>
            
            <p>Thank you for your prompt payment.</p>
            <p>If you have any questions, please contact our finance office.</p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo get_settings('system_name'); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
