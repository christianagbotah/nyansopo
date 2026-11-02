<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Bill Statement</title>
    <style>
        @media print {
            @page { margin: 0.5cm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            margin: 20px;
            background: white;
        }
        
        .bill-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 2px solid #000;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .school-name {
            font-size: 20pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a1a1a;
        }
        
        .bill-title {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 10px;
            color: #d32f2f;
        }
        
        .student-info {
            background: #f5f5f5;
            border: 1px solid #000;
            padding: 15px;
            margin: 15px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .info-item {
            display: flex;
        }
        
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        
        .info-value {
            flex: 1;
        }
        
        .section-title {
            background: #1976d2;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .arrears-section {
            background: #fff3e0;
            border: 2px solid #ff9800;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .arrears-title {
            color: #e65100;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 10px;
        }
        
        .arrears-amount {
            font-size: 24pt;
            font-weight: bold;
            color: #d32f2f;
            text-align: center;
            padding: 10px;
            background: white;
            border: 2px dashed #ff9800;
        }
        
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .bill-table th,
        .bill-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .bill-table th {
            background: #e3f2fd;
            font-weight: bold;
        }
        
        .bill-table td.amount {
            text-align: right;
            font-weight: bold;
        }
        
        .total-row {
            background: #c8e6c9;
            font-weight: bold;
            font-size: 12pt;
        }
        
        .grand-total-row {
            background: #ffcdd2;
            font-weight: bold;
            font-size: 14pt;
        }
        
        .summary-box {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            background: white;
            border-left: 4px solid #4caf50;
        }
        
        .summary-label {
            font-weight: bold;
        }
        
        .summary-value {
            font-weight: bold;
            color: #1b5e20;
        }
        
        .payment-notice {
            background: #fff9c4;
            border: 2px solid #fbc02d;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        
        .payment-notice-title {
            font-weight: bold;
            font-size: 13pt;
            color: #f57f17;
            margin-bottom: 10px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 9pt;
        }
        
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            text-align: center;
            margin-top: 50px;
        }
        
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="no-print" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Print Bill
    </button>

    <?php
    $student_id = $this->uri->segment(3);
    $year = $this->uri->segment(4);
    $term = $this->uri->segment(5);
    
    $student = $this->db->select('s.*, c.name as class_name')
        ->from('student s')
        ->join('class c', 'c.class_id = s.class_id')
        ->where('s.student_id', $student_id)
        ->get()->row();
    
    // Get latest invoice for current year/term (next term bill)
    $latest_invoice = $this->db->select('*')
        ->from('invoice')
        ->where('student_id', $student_id)
        ->where('year', $year)
        ->where('term', $term)
        ->order_by('creation_timestamp', 'DESC')
        ->limit(1)
        ->get()->row();
    
    // Get all payments
    $total_paid = $this->db->select('SUM(amount) as total')
        ->from('payment')
        ->where('student_id', $student_id)
        ->get()->row()->total ?? 0;
    
    // Get all invoices BEFORE current year/term (old arrears)
    $old_invoices_total = $this->db->select('SUM(amount) as total')
        ->from('invoice')
        ->where('student_id', $student_id)
        ->where('(year < "' . $year . '" OR (year = "' . $year . '" AND term < "' . $term . '"))')
        ->get()->row()->total ?? 0;
    
    // Calculate arrears
    $arrears = max(0, $old_invoices_total - $total_paid);
    $current_bill = $latest_invoice->amount ?? 0;
    $total_owing = $arrears + $current_bill;
    
    // Get invoice items
    $invoice_items = $this->db->select('ii.*, it.type as item_name')
        ->from('invoice_items ii')
        ->join('invoice_type it', 'it.invoice_type_id = ii.invoice_type_id')
        ->where('ii.invoice_id', $latest_invoice->invoice_id)
        ->get()->result();
    
    $school = $this->db->get_where('settings', ['type' => 'system_name'])->row();
    ?>

    <div class="bill-container">
        <!-- Header -->
        <div class="header">
            <div class="school-name"><?php echo $school->description; ?></div>
            <div style="font-size: 10pt; margin-top: 5px;">P.O. Box 123, Accra, Ghana | Tel: 0XX XXX XXXX</div>
            <div class="bill-title">STUDENT FINANCIAL STATEMENT</div>
            <div style="margin-top: 5px; font-size: 10pt;">Academic Year: <?php echo $year; ?> | Term: <?php echo $term; ?> | Date: <?php echo date('d M, Y'); ?></div>
        </div>

        <!-- Student Information -->
        <div class="student-info">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Student Name:</div>
                    <div class="info-value"><?php echo strtoupper($student->name); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Class:</div>
                    <div class="info-value"><?php echo $student->class_name; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Student ID:</div>
                    <div class="info-value"><?php echo $student->student_id; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Bill Date:</div>
                    <div class="info-value"><?php echo date('d M, Y', strtotime($latest_invoice->creation_timestamp)); ?></div>
                </div>
            </div>
        </div>

        <!-- Arrears Section (if any) -->
        <?php if($arrears > 0): ?>
        <div class="arrears-section">
            <div class="arrears-title">⚠️ OUTSTANDING ARREARS FROM PREVIOUS TERMS</div>
            <div class="arrears-amount">GH₵ <?php echo number_format($arrears, 2); ?></div>
            <div style="text-align: center; margin-top: 10px; color: #d32f2f; font-weight: bold;">
                Please settle arrears before next term
            </div>
        </div>
        <?php endif; ?>

        <!-- Current Term Bill -->
        <div class="section-title">CURRENT TERM BILL - <?php echo $latest_invoice->title; ?></div>
        
        <table class="bill-table">
            <thead>
                <tr>
                    <th style="width: 10%;">S/N</th>
                    <th style="width: 60%;">DESCRIPTION</th>
                    <th style="width: 30%;">AMOUNT (GH₵)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sn = 1;
                foreach($invoice_items as $item): 
                ?>
                <tr>
                    <td><?php echo $sn++; ?></td>
                    <td><?php echo $item->item_name; ?></td>
                    <td class="amount"><?php echo number_format($item->amount, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2">CURRENT TERM TOTAL</td>
                    <td class="amount">GH₵ <?php echo number_format($current_bill, 2); ?></td>
                </tr>
                <?php if($arrears > 0): ?>
                <tr style="background: #ffebee;">
                    <td colspan="2">ADD: ARREARS FROM PREVIOUS TERMS</td>
                    <td class="amount">GH₵ <?php echo number_format($arrears, 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total-row">
                    <td colspan="2">TOTAL AMOUNT DUE</td>
                    <td class="amount">GH₵ <?php echo number_format($total_owing, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Payment Summary -->
        <div class="summary-box">
            <div style="font-weight: bold; margin-bottom: 10px; font-size: 12pt;">PAYMENT SUMMARY</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Total Billed:</span>
                    <span class="summary-value">GH₵ <?php echo number_format($old_invoices_total + $current_bill, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Paid:</span>
                    <span class="summary-value">GH₵ <?php echo number_format($total_paid, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Arrears:</span>
                    <span class="summary-value">GH₵ <?php echo number_format($arrears, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Current Bill:</span>
                    <span class="summary-value">GH₵ <?php echo number_format($current_bill, 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Notice -->
        <div class="payment-notice">
            <div class="payment-notice-title">PAYMENT INSTRUCTIONS</div>
            <div>Please make payment to the school bursar or via bank transfer</div>
            <div style="margin-top: 10px;">
                <strong>Bank:</strong> ABC Bank | <strong>Account:</strong> 1234567890 | <strong>Branch:</strong> Accra
            </div>
        </div>

        <!-- Signatures -->
        <div class="signature-section">
            <div>
                <div class="signature-line">Bursar's Signature & Date</div>
            </div>
            <div>
                <div class="signature-line">Parent/Guardian's Signature & Date</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <strong>NOTE:</strong> This is a computer-generated statement. Please keep for your records.<br>
            For inquiries, contact the school bursar during office hours (8:00 AM - 4:00 PM)
        </div>
    </div>
</body>
</html>
