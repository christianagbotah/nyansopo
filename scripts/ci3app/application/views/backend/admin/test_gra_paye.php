<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRA PAYE Calculation Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: #2c5f2d;
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
        }
        .info-box {
            background: #fff3cd;
            padding: 20px;
            border-left: 4px solid #ffc107;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .info-box strong {
            display: block;
            margin-bottom: 10px;
            color: #856404;
        }
        .test-case {
            background: white;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .test-case h3 {
            color: #2c5f2d;
            margin-top: 0;
            font-size: 22px;
        }
        .test-case p {
            color: #666;
            margin: 5px 0 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #2c5f2d;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .highlight-row {
            background: #e8f5e9 !important;
            font-weight: bold;
        }
        .breakdown {
            background: #f9f9f9;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2c5f2d;
            border-radius: 4px;
        }
        .breakdown strong {
            display: block;
            margin-bottom: 15px;
            color: #2c5f2d;
            font-size: 16px;
        }
        .summary {
            background: #e8f5e9;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .summary h3 {
            color: #2c5f2d;
            margin-top: 0;
        }
        .summary ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .summary li {
            margin: 8px 0;
        }
        .btn-back {
            display: inline-block;
            background: #2c5f2d;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn-back:hover {
            background: #1f4320;
        }
        .total-row {
            background: #f0f0f0 !important;
            font-weight: bold;
            border-top: 2px solid #2c5f2d;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="<?php echo site_url('admin/payroll_system'); ?>" class="btn-back">← Back to Payroll System</a>
    
    <div class="header">
        <h1>🇬🇭 Ghana Revenue Authority (GRA) PAYE Calculation Test</h1>
        <p>Testing payroll system against official GRA Revised Annual PAYE Schedule (2024)</p>
    </div>

    <div class="info-box">
        <strong>⚙️ Current System Settings & GRA PAYE Rules:</strong><br>
        <strong style="color: #d32f2f;">IMPORTANT:</strong> Only <strong>SSNIT Tier 2 (<?php echo $ssnit_tier2_rate; ?>%)</strong> is deductible for PAYE calculation.<br>
        SSNIT Tier 1 (<?php echo $ssnit_tier1_rate; ?>%) is <strong>NOT deductible</strong> according to GRA rules.<br><br>
        
        <strong>Employee SSNIT Deductions:</strong><br>
        • Tier 1 (Employee): <?php echo $ssnit_tier1_rate; ?>% of Basic Salary - <strong>Not tax deductible</strong><br>
        • Tier 2 (Pension): <?php echo $ssnit_tier2_rate; ?>% of Basic Salary - <strong>Tax deductible</strong><br>
        • <strong>Total Deducted from Employee:</strong> <?php echo $total_ssnit_employee_rate; ?>%<br>
        • <strong>Used for PAYE Calculation:</strong> <?php echo $tax_deductible_ssnit_rate; ?>% (Tier 2 only)
    </div>

    <?php foreach ($test_results as $result): ?>
    <div class="test-case">
        <h3>📊 <?php echo $result['name']; ?></h3>
        <p><?php echo $result['description']; ?></p>
        
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: right;">Monthly (GH¢)</th>
                    <th style="text-align: right;">Annual (GH¢)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Basic/Gross Salary</strong></td>
                    <td style="text-align: right;"><?php echo number_format($result['monthly_gross'], 2); ?></td>
                    <td style="text-align: right;"><?php echo number_format($result['annual_gross'], 2); ?></td>
                </tr>
                <tr style="background: #fff9e6;">
                    <td colspan="3"><strong>SSNIT Deductions (from Basic Salary):</strong></td>
                </tr>
                <tr>
                    <td>&nbsp;&nbsp;• Tier 1 Employee (<?php echo $ssnit_tier1_rate; ?>%) - <em>Not tax deductible</em></td>
                    <td style="text-align: right;"><?php echo number_format($result['monthly_ssnit_tier1'], 2); ?></td>
                    <td style="text-align: right;"><?php echo number_format($result['annual_ssnit_tier1'], 2); ?></td>
                </tr>
                <tr>
                    <td>&nbsp;&nbsp;• Tier 2 Pension (<?php echo $ssnit_tier2_rate; ?>%) - <em style="color: #2c5f2d;">Tax deductible</em></td>
                    <td style="text-align: right;"><?php echo number_format($result['monthly_ssnit_tier2'], 2); ?></td>
                    <td style="text-align: right;"><?php echo number_format($result['annual_ssnit_tier2'], 2); ?></td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td><strong>Total SSNIT Deducted</strong></td>
                    <td style="text-align: right;"><strong><?php echo number_format($result['monthly_total_ssnit'], 2); ?></strong></td>
                    <td style="text-align: right;"><strong><?php echo number_format($result['annual_total_ssnit'], 2); ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Taxable Income</strong> <em>(Gross - Tier 2 only)</em></td>
                    <td style="text-align: right;"><?php echo number_format($result['monthly_taxable'], 2); ?></td>
                    <td style="text-align: right;"><?php echo number_format($result['annual_taxable'], 2); ?></td>
                </tr>
                <tr class="highlight-row">
                    <td><strong>PAYE (Income Tax)</strong></td>
                    <td style="text-align: right;"><strong><?php echo number_format($result['monthly_paye'], 2); ?></strong></td>
                    <td style="text-align: right;"><strong><?php echo number_format($result['annual_paye'], 2); ?></strong></td>
                </tr>
                <tr class="highlight-row">
                    <td><strong>Net Pay</strong> <em>(Gross - Total SSNIT - PAYE)</em></td>
                    <td style="text-align: right;"><strong><?php echo number_format($result['monthly_net'], 2); ?></strong></td>
                    <td style="text-align: right;"><strong><?php echo number_format($result['annual_net'], 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <?php if (!empty($result['breakdown'])): ?>
        <div class="breakdown">
            <strong>📋 Tax Breakdown (Progressive Calculation):</strong>
            <table>
                <thead>
                    <tr>
                        <th>Bracket</th>
                        <th>Income Range</th>
                        <th style="text-align: right;">Taxable Amount</th>
                        <th style="text-align: right;">Rate</th>
                        <th style="text-align: right;">Tax</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['breakdown'] as $bracket): ?>
                    <tr>
                        <td><?php echo $bracket['bracket_name']; ?></td>
                        <td><?php echo $bracket['income_range']; ?></td>
                        <td style="text-align: right;">GH¢<?php echo number_format($bracket['taxable_amount'], 2); ?></td>
                        <td style="text-align: right;"><?php echo $bracket['tax_rate']; ?>%</td>
                        <td style="text-align: right;">GH¢<?php echo number_format($bracket['tax_amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4"><strong>TOTAL ANNUAL TAX</strong></td>
                        <td style="text-align: right;"><strong>GH¢<?php echo number_format($result['annual_paye'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <p><strong>Effective Tax Rate:</strong> <?php echo number_format($result['effective_rate'], 2); ?>% of taxable income</p>
    </div>
    <?php endforeach; ?>

    <div class="summary">
        <h3>✅ Test Summary</h3>
        <p>All test cases have been calculated using the latest GRA tax brackets.</p>
        <p><strong>Verification Steps:</strong></p>
        <ol>
            <li>Compare the calculated PAYE with GRA official calculators</li>
            <li>Verify that tax-free threshold is GH¢7,056 annually (GH¢588 monthly)</li>
            <li>Confirm progressive tax rates: 0%, 5%, 10%, 17.5%, 25%, 30%, 35%</li>
            <li><strong>Verify that only SSNIT Tier 2 (<?php echo $ssnit_tier2_rate; ?>%) is deducted for PAYE calculation</strong></li>
            <li>Confirm SSNIT Tier 1 (<?php echo $ssnit_tier1_rate; ?>%) is NOT deductible from taxable income</li>
        </ol>
    </div>

    <div class="info-box">
        <strong>📝 Important Notes:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This test uses BASIC SALARY = GROSS SALARY (no allowances) for simplicity</li>
            <li>In actual payroll, GROSS = BASIC + ALLOWANCES</li>
            <li>SSNIT is calculated on BASIC SALARY only, not gross</li>
            <li><strong style="color: #d32f2f;">CRITICAL:</strong> Only SSNIT Tier 2 (<?php echo $ssnit_tier2_rate; ?>%) is deductible for PAYE calculation</li>
            <li><strong style="color: #d32f2f;">SSNIT Tier 1 is NOT tax deductible</strong> - This is GRA rule</li>
            <li>Taxable Income = Gross Salary - SSNIT Tier 2 only</li>
            <li>Net Pay = Gross - (Tier 1 + Tier 2) - PAYE</li>
            <li>Tax brackets are based on GRA Revised Annual PAYE Schedule (2024)</li>
        </ul>
    </div>
</div>

</body>
</html>
