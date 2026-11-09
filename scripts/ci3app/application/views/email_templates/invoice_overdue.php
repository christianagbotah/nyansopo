<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #f44336; color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: #f9f9f9; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
        .warning-box { background: #fff3cd; padding: 20px; margin: 20px 0; border-left: 4px solid #f44336; }
        .amount { font-size: 24px; color: #f44336; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo get_settings('system_name'); ?></h1>
            <p>Payment Reminder</p>
        </div>
        
        <div class="content">
            <h2>Invoice Overdue Notice</h2>
            <p>Dear Parent/Guardian,</p>
            <p>This is a reminder that the invoice for <strong><?php echo $student_name; ?></strong> is now overdue.</p>
            
            <div class="warning-box">
                <p><strong>Invoice Number:</strong> <?php echo $invoice_number; ?></p>
                <p><strong>Amount Due:</strong> <span class="amount">GHS <?php echo number_format($amount, 2); ?></span></p>
                <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($due_date)); ?></p>
                <p><strong>Days Overdue:</strong> <?php echo $days_overdue; ?> days</p>
            </div>
            
            <p>Please make payment at your earliest convenience to avoid any late fees.</p>
            <p>If you have already made payment, please disregard this notice.</p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo get_settings('system_name'); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
