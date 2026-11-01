<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $staff_name; ?> - Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #CE1126;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #006B3F;
            margin-bottom: 5px;
        }
        .payslip-title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 15px;
            color: #FCD116;
            text-shadow: 1px 1px 2px #000;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-row {
            margin-bottom: 10px;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 5px;
        }
        .info-label {
            display: inline-block;
            width: 200px;
            font-weight: bold;
        }
        .section-title {
            background: #006B3F;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
        }
        .amount-label {
            font-weight: 500;
        }
        .amount-value {
            font-weight: bold;
        }
        .net-salary-row {
            background: #f0f8ff;
            border: 2px solid #006B3F;
            padding: 15px;
            margin-top: 20px;
            font-size: 18px;
        }
        .net-salary-label {
            font-weight: bold;
            color: #006B3F;
        }
        .net-salary-value {
            float: right;
            font-weight: bold;
            color: #006B3F;
            font-size: 22px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header with Ghana Flag Colors -->
    <div class="header">
        <div class="school-name"><?php echo $school_name; ?></div>
        <div style="color: #666; font-size: 14px;">Republic of Ghana</div>
        <div class="payslip-title">PAYSLIP</div>
        <div style="margin-top: 10px; font-size: 16px;">
            <?php echo date('F Y', mktime(0, 0, 0, $payslip['month'], 1, $payslip['year'])); ?>
        </div>
    </div>
    
    <!-- Employee Information -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Staff Name:</span>
            <span><?php echo $staff_name; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Staff Code:</span>
            <span><?php echo $staff_code; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment Period:</span>
            <span><?php echo date('F Y', mktime(0, 0, 0, $payslip['month'], 1, $payslip['year'])); ?></span>
        </div>
    </div>
    
    <!-- Earnings Section -->
    <div class="section-title">EARNINGS</div>
    <div class="amount-row">
        <span class="amount-label">Basic Salary</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['basic_salary'], 2); ?></span>
    </div>
    <?php if ($payslip['house_rent'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">House Rent Allowance</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['house_rent'], 2); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payslip['transport'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">Transport Allowance</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['transport'], 2); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payslip['medical'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">Medical Allowance</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['medical'], 2); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payslip['bonus'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">Bonus</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['bonus'], 2); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payslip['other_allowances'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">Other Allowances</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['other_allowances'], 2); ?></span>
    </div>
    <?php endif; ?>
    <div class="amount-row" style="background: #f5f5f5; font-weight: bold;">
        <span class="amount-label">GROSS SALARY</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['gross_salary'], 2); ?></span>
    </div>
    
    <!-- Deductions Section -->
    <div class="section-title" style="background: #CE1126;">DEDUCTIONS</div>
    <?php if ($payslip['ssnit_1'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">SSNIT Tier 1 (13.5%)</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['ssnit_1'], 2); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payslip['ssnit_2'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">SSNIT Tier 2 (5%)</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['ssnit_2'], 2); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payslip['income_tax'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">Income Tax (PAYE)</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['income_tax'], 2); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($payslip['other_deductions'] > 0): ?>
    <div class="amount-row">
        <span class="amount-label">Other Deductions</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['other_deductions'], 2); ?></span>
    </div>
    <?php endif; ?>
    <div class="amount-row" style="background: #f5f5f5; font-weight: bold;">
        <span class="amount-label">TOTAL DEDUCTIONS</span>
        <span class="amount-value">GH¢ <?php echo number_format($payslip['total_deductions'], 2); ?></span>
    </div>
    
    <!-- Net Salary -->
    <div class="net-salary-row">
        <span class="net-salary-label">NET SALARY</span>
        <span class="net-salary-value">GH¢ <?php echo number_format($payslip['net_salary'], 2); ?></span>
        <div style="clear: both;"></div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>This is a computer-generated payslip. No signature is required.</p>
        <p>Generated on: <?php echo date('d F Y, h:i A'); ?></p>
        <p>&copy; <?php echo date('Y'); ?> <?php echo $school_name; ?>. All rights reserved.</p>
    </div>
    
    <!-- Print Button (hidden when printing) -->
    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.print()" style="background: #006B3F; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            <i class="entypo-print"></i> Print Payslip
        </button>
        <button onclick="window.close()" style="background: #CE1126; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>
