<!DOCTYPE html>
<html>
<head>
    <title>Payroll Register - <?php echo $month_name . ' ' . $year; ?></title>
    <style>
        @media print {
            @page { margin: 1cm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 5px 0;
            font-size: 18px;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        
        .report-info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        table thead {
            background-color: #333;
            color: white;
        }
        
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        table th {
            font-weight: bold;
        }
        
        table td.number {
            text-align: right;
        }
        
        table tfoot {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background-color: #337ab7;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button:hover {
            background-color: #286090;
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button class="print-button no-print" onclick="window.print()">Print / Save as PDF</button>
    
    <!-- Header -->
    <div class="header">
        <h1><?php echo get_settings('system_name'); ?></h1>
        <h2>Payroll Register</h2>
        <p><?php echo $month_name . ' ' . $year; ?></p>
        <p>Generated: <?php echo date('d F Y, h:i A'); ?></p>
    </div>
    
    <!-- Report Info -->
    <div class="report-info">
        <strong>Report Summary:</strong> 
        Total Staff: <?php echo count($results); ?> | 
        Total Gross: GH¢ <?php echo number_format($total_gross, 2); ?> | 
        Total Deductions: GH¢ <?php echo number_format($total_deductions, 2); ?> | 
        Total Net: GH¢ <?php echo number_format($total_net, 2); ?>
    </div>
    
    <!-- Payroll Table -->
    <table>
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Staff Code</th>
                <th>Category</th>
                <th>Gross Salary</th>
                <th>Deductions</th>
                <th>Net Salary</th>
                <th>Status</th>
                <th>Payment Method</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">
                        No payroll records found for the selected period
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo $row['staff_name']; ?></td>
                        <td><?php echo $row['staff_code']; ?></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $row['category'])); ?></td>
                        <td class="number">GH¢ <?php echo number_format($row['gross_salary'], 2); ?></td>
                        <td class="number">GH¢ <?php echo number_format($row['total_deductions'], 2); ?></td>
                        <td class="number">GH¢ <?php echo number_format($row['net_salary'], 2); ?></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $row['status'])); ?></td>
                        <td><?php echo $row['payment_method']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">TOTALS:</td>
                <td class="number">GH¢ <?php echo number_format($total_gross, 2); ?></td>
                <td class="number">GH¢ <?php echo number_format($total_deductions, 2); ?></td>
                <td class="number">GH¢ <?php echo number_format($total_net, 2); ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    
    <!-- Footer -->
    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>&copy; <?php echo date('Y') . ' ' . get_settings('system_name'); ?>. All rights reserved.</p>
    </div>
</body>
</html>
