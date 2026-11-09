<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Bill Report</title>
    <style>
        @media print {
            @page { size: A4 portrait; margin: 15mm; }
            body { margin: 0; }
            .no-print { display: none !important; }
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .bill-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .bill-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .school-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .school-logo img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .bill-title {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .bill-body {
            padding: 30px;
        }
        
        .student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .bill-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .bill-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .bill-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        
        .bill-table tr:hover {
            background: #f8f9fa;
        }
        
        .amount {
            text-align: right;
            font-weight: 600;
        }
        
        .amount.credit {
            color: #28a745;
        }
        
        .amount.debit {
            color: #dc3545;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-size: 18px;
            font-weight: bold;
            padding-top: 15px;
        }
        
        .summary-label {
            font-size: 14px;
        }
        
        .summary-value {
            font-size: 16px;
            font-weight: 600;
        }
        
        .bill-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        
        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.owing {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.credit {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <!-- Header -->
        <div class="bill-header">
            <div class="school-logo">
                <?php if(file_exists('uploads/school_logo.png')): ?>
                    <img src="<?php echo base_url('uploads/school_logo.png'); ?>" alt="School Logo">
                <?php else: ?>
                    <span style="font-size: 40px; color: #667eea;">🎓</span>
                <?php endif; ?>
            </div>
            <div class="school-name"><?php echo $school_name; ?></div>
            <div class="bill-title">Student Bill Report</div>
            <div style="margin-top: 10px; font-size: 14px;">
                Generated: <?php echo date('d M Y, h:i A'); ?>
            </div>
        </div>
        
        <!-- Body -->
        <div class="bill-body">
            <!-- Student Information -->
            <div class="student-info">
                <div class="info-item">
                    <span class="info-label">Student Name</span>
                    <span class="info-value"><?php echo $student['name']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Student ID</span>
                    <span class="info-value"><?php echo $student['student_code']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Class</span>
                    <span class="info-value"><?php echo $student['class_name']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Academic Year</span>
                    <span class="info-value"><?php echo $running_year; ?></span>
                </div>
            </div>
            
            <!-- Invoice Items -->
            <?php if(!empty($invoices)): ?>
            <div class="bill-section">
                <div class="section-title">📋 Invoice Items</div>
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="amount">Amount</th>
                            <th class="amount">Paid</th>
                            <th class="amount">Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_invoice = 0;
                        $total_paid = 0;
                        foreach($invoices as $invoice): 
                            $balance = $invoice['total_amount'] - $invoice['paid_amount'];
                            $total_invoice += $invoice['total_amount'];
                            $total_paid += $invoice['paid_amount'];
                        ?>
                        <tr>
                            <td><?php echo $invoice['title']; ?></td>
                            <td class="amount">GH₵ <?php echo number_format($invoice['total_amount'], 2); ?></td>
                            <td class="amount">GH₵ <?php echo number_format($invoice['paid_amount'], 2); ?></td>
                            <td class="amount <?php echo $balance > 0 ? 'debit' : 'credit'; ?>">
                                GH₵ <?php echo number_format(abs($balance), 2); ?>
                            </td>
                            <td>
                                <?php if($invoice['status'] == 'paid'): ?>
                                    <span class="status-badge paid">Paid</span>
                                <?php else: ?>
                                    <span class="status-badge owing">Owing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Daily Fees -->
            <?php if(!empty($daily_fees)): ?>
            <div class="bill-section">
                <div class="section-title">💰 Daily Fees</div>
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Fee Type</th>
                            <th class="amount">Prepaid Balance</th>
                            <th class="amount">Arrears</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_prepaid = 0;
                        $total_arrears = 0;
                        foreach($daily_fees as $fee): 
                            $total_prepaid += max(0, $fee['balance']);
                            $total_arrears += max(0, -$fee['balance']);
                        ?>
                        <tr>
                            <td><?php echo ucfirst($fee['type']); ?></td>
                            <td class="amount credit">
                                GH₵ <?php echo number_format(max(0, $fee['balance']), 2); ?>
                            </td>
                            <td class="amount debit">
                                GH₵ <?php echo number_format(max(0, -$fee['balance']), 2); ?>
                            </td>
                            <td>
                                <?php if($fee['balance'] > 0): ?>
                                    <span class="status-badge credit">Credit</span>
                                <?php elseif($fee['balance'] < 0): ?>
                                    <span class="status-badge owing">Owing</span>
                                <?php else: ?>
                                    <span class="status-badge paid">Paid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Summary -->
            <div class="summary-box">
                <div class="summary-row">
                    <span class="summary-label">Total Invoice Amount:</span>
                    <span class="summary-value">GH₵ <?php echo number_format($total_invoice ?? 0, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Paid (Invoices):</span>
                    <span class="summary-value">GH₵ <?php echo number_format($total_paid ?? 0, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Daily Fees Prepaid:</span>
                    <span class="summary-value">GH₵ <?php echo number_format($total_prepaid ?? 0, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Daily Fees Arrears:</span>
                    <span class="summary-value">GH₵ <?php echo number_format($total_arrears ?? 0, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">TOTAL OUTSTANDING:</span>
                    <span class="summary-value">
                        GH₵ <?php echo number_format((($total_invoice ?? 0) - ($total_paid ?? 0)) + ($total_arrears ?? 0), 2); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bill-footer">
            <p>This is a computer-generated bill report. For inquiries, please contact the school office.</p>
            <p style="margin-top: 10px;">© <?php echo date('Y'); ?> <?php echo $school_name; ?>. All rights reserved.</p>
        </div>
    </div>
    
    <!-- Print Button -->
    <button class="print-button no-print" onclick="window.print()">
        🖨️ Print Bill
    </button>
</body>
</html>
