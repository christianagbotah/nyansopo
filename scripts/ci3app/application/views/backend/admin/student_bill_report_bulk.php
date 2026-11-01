<?php
/**
 * Bulk Student Bill Report
 * Generate multiple student bills in one document
 */

$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$system_title = $this->db->get_where('settings', array('type' => 'system_title'))->row()->description;
$address = $this->db->get_where('settings', array('type' => 'address'))->row()->description;
$phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
$system_email = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;

$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

// Get students based on class_id and section_id
$students_query = $this->db->select('student.student_id, student.student_code, student.name, student.parent_id')
    ->from('enroll')
    ->join('student', 'student.student_id = enroll.student_id')
    ->where('enroll.class_id', $class_id)
    ->where('enroll.section_id', $section_id)
    ->where('enroll.year', $running_year)
    ->where('enroll.term', $running_term)
    ->where('enroll.mute', '0')
    ->order_by('student.name', 'ASC')
    ->get();

$students = $students_query->result();
$total_students = count($students);
$current_student = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Student Bill Reports - <?php echo $class_name; ?></title>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #333;
            background: #f5f5f5;
        }

        .bill-container {
            max-width: 210mm;
            margin: 20px auto;
            background: white;
        }

        .bill-page {
            padding: 12mm;
            min-height: 277mm;
            position: relative;
            background: white;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.02);
            z-index: 0;
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .bill-header {
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 6px 6px 0 0;
            margin: -12mm -12mm 20px -12mm;
        }

        .school-logo {
            text-align: center;
            margin-bottom: 10px;
        }

        .school-logo img {
            max-height: 60px;
            max-width: 180px;
        }

        .school-info {
            text-align: center;
        }

        .school-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .school-title {
            font-size: 12px;
            margin-bottom: 8px;
            opacity: 0.95;
        }

        .school-contact {
            font-size: 10px;
            opacity: 0.9;
        }

        .bill-title {
            text-align: center;
            margin: 20px 0;
            padding: 12px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 6px;
        }

        .bill-title h2 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .bill-title .session-info {
            font-size: 11px;
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #667eea;
        }

        .info-block h3 {
            font-size: 12px;
            color: #667eea;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-row {
            display: flex;
            margin-bottom: 6px;
            font-size: 10pt;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 100px;
        }

        .info-value {
            color: #333;
            flex: 1;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .summary-card {
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .card-billed { background: #e3f2fd; border: 1px solid #2196f3; }
        .card-paid { background: #e8f5e9; border: 1px solid #4caf50; }
        .card-due { background: #fff3e0; border: 1px solid #ff9800; }
        .card-credit { background: #f3e5f5; border: 1px solid #9c27b0; }

        .card-label {
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .card-amount {
            font-size: 16px;
            font-weight: bold;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin: 20px 0 10px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
            text-transform: uppercase;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .invoice-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .invoice-table th {
            padding: 8px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
        }

        .invoice-table td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-paid { background: #4caf50; color: white; }
        .status-partial { background: #ff9800; color: white; }
        .status-unpaid { background: #f44336; color: white; }

        .totals-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 2px solid #667eea;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 10pt;
        }

        .total-row.grand-total {
            border-top: 2px solid #667eea;
            margin-top: 8px;
            padding-top: 10px;
            font-size: 14pt;
            font-weight: bold;
            color: #667eea;
        }

        .bill-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
        }

        .footer-notes {
            background: #fff3cd;
            border-left: 3px solid #ffc107;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 9pt;
        }

        .footer-notes h4 {
            font-size: 10px;
            margin-bottom: 8px;
            color: #856404;
        }

        .footer-notes ul {
            margin-left: 15px;
            color: #856404;
        }

        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 25px;
        }

        .signature-block {
            text-align: center;
        }

        .signature-line {
            border-top: 2px solid #333;
            margin-top: 35px;
            padding-top: 8px;
            font-size: 10pt;
            font-weight: 600;
        }

        .print-info {
            text-align: center;
            font-size: 8pt;
            color: #999;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
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
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            z-index: 1000;
        }

        .amount {
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .amount-positive { color: #4caf50; }
        .amount-negative { color: #f44336; }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <i class="fa fa-print"></i> Print All Bills (<?php echo $total_students; ?> Students)
    </button>

    <?php foreach($students as $student): 
        $current_student++;
        
        // Get student details
        $enroll = $this->db->get_where('enroll', array('student_id' => $student->student_id, 'year' => $running_year, 'term' => $running_term, 'mute' => '0'))->row();
        $class = $this->db->get_where('class', array('class_id' => $enroll->class_id))->row();
        $parent = $this->db->get_where('parent', array('parent_id' => $student->parent_id))->row();
        
        // Get invoices (ONLY INVOICE ITEMS - NOT DAILY FEES)
        $invoices = $this->db->select('DISTINCT invoice_code')
            ->where('student_id', $student->student_id)
            ->where('year', $running_year)
            ->where('term', $running_term)
            ->get('invoice')
            ->result();
        
        $total_billed = 0;
        $total_paid = 0;
        $total_due = 0;
        $credit_balance = 0;
        $invoice_items = array();
        
        // Get invoice items only (excludes daily fees)
        foreach($invoices as $inv) {
            $items = $this->db->where('invoice_code', $inv->invoice_code)
                ->where('student_id', $student->student_id)
                ->get('invoice')
                ->result_array();
            
            foreach($items as $item) {
                $invoice_items[] = $item;
                $total_billed += $item['amount'];
                $total_paid += $item['amount_paid'];
                $total_due += $item['due'];
            }
        }
        
        if($total_due < 0) {
            $credit_balance = abs($total_due);
            $total_due = 0;
        }
    ?>
    
    <div class="bill-container">
        <div class="bill-page <?php echo ($current_student < $total_students) ? 'page-break' : ''; ?>">
            <div class="watermark"><?php echo strtoupper($system_name); ?></div>
            
            <div class="content">
                <div class="bill-header">
                    <div class="school-logo">
                        <img src="<?php echo base_url(); ?>uploads/logo.png" alt="School Logo">
                    </div>
                    <div class="school-info">
                        <div class="school-name"><?php echo $system_name; ?></div>
                        <div class="school-title"><?php echo $system_title; ?></div>
                        <div class="school-contact">
                            <?php echo $address; ?> | Tel: <?php echo $phone; ?> | Email: <?php echo $system_email; ?>
                        </div>
                    </div>
                </div>

                <div class="bill-title">
                    <h2>STUDENT BILL REPORT</h2>
                    <div class="session-info">
                        Academic Year: <?php echo $running_year; ?> | Term: <?php echo $running_term; ?>
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-block">
                        <h3>Student Information</h3>
                        <div class="info-row">
                            <span class="info-label">Student ID:</span>
                            <span class="info-value"><?php echo $student->student_code; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo strtoupper($student->name); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Class:</span>
                            <span class="info-value"><?php echo $class->name . ' ' . $class->name_numeric; ?></span>
                        </div>
                    </div>

                    <div class="info-block">
                        <h3>Parent/Guardian</h3>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo strtoupper($parent->name); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo $parent->phone; ?></span>
                        </div>
                    </div>
                </div>

                <div class="summary-cards">
                    <div class="summary-card card-billed">
                        <div class="card-label">Total Billed</div>
                        <div class="card-amount"><?php echo numfmt_format_currency($fmt, $total_billed, $currency); ?></div>
                    </div>
                    <div class="summary-card card-paid">
                        <div class="card-label">Total Paid</div>
                        <div class="card-amount"><?php echo numfmt_format_currency($fmt, $total_paid, $currency); ?></div>
                    </div>
                    <div class="summary-card card-due">
                        <div class="card-label">Amount Due</div>
                        <div class="card-amount"><?php echo numfmt_format_currency($fmt, $total_due, $currency); ?></div>
                    </div>
                    <div class="summary-card card-credit">
                        <div class="card-label">Credit Balance</div>
                        <div class="card-amount"><?php echo numfmt_format_currency($fmt, $credit_balance, $currency); ?></div>
                    </div>
                </div>

                <div class="section-title">Bill Items</div>
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach($invoice_items as $item): 
                            $status = 'unpaid';
                            if($item['due'] == 0) $status = 'paid';
                            elseif($item['amount_paid'] > 0 && $item['due'] > 0) $status = 'partial';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo $item['title']; ?></strong></td>
                            <td class="text-right amount"><?php echo numfmt_format_currency($fmt, $item['amount'], $currency); ?></td>
                            <td class="text-right amount amount-positive"><?php echo numfmt_format_currency($fmt, $item['amount_paid'], $currency); ?></td>
                            <td class="text-right amount <?php echo $item['due'] > 0 ? 'amount-negative' : ''; ?>">
                                <?php echo numfmt_format_currency($fmt, $item['due'], $currency); ?>
                            </td>
                            <td class="text-center">
                                <span class="status-badge status-<?php echo $status; ?>"><?php echo strtoupper($status); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="totals-section">
                    <div class="total-row">
                        <span>Subtotal (Billed):</span>
                        <span class="amount"><?php echo numfmt_format_currency($fmt, $total_billed, $currency); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Total Paid:</span>
                        <span class="amount amount-positive"><?php echo numfmt_format_currency($fmt, $total_paid, $currency); ?></span>
                    </div>
                    <?php if($credit_balance > 0): ?>
                    <div class="total-row">
                        <span>Credit Balance:</span>
                        <span class="amount"><?php echo numfmt_format_currency($fmt, $credit_balance, $currency); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row grand-total">
                        <span>AMOUNT DUE:</span>
                        <span class="amount"><?php echo numfmt_format_currency($fmt, $total_due, $currency); ?></span>
                    </div>
                </div>

                <div class="bill-footer">
                    <div class="footer-notes">
                        <h4>IMPORTANT NOTES:</h4>
                        <ul>
                            <li><strong>This bill includes ONLY invoiced items (School Fees, Admission, PTA, Exam Fees, etc.)</strong></li>
                            <li><strong>Daily fees (Feeding, Water, Classes, Transport) are NOT included</strong></li>
                            <li>All payments should be made to the school's accounts office</li>
                            <li>Please quote the student ID when making payments</li>
                            <?php if($credit_balance > 0): ?>
                            <li><strong>Credit balance of <?php echo numfmt_format_currency($fmt, $credit_balance, $currency); ?> will be applied to future bills</strong></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="signature-section">
                        <div class="signature-block">
                            <div class="signature-line">Accounts Officer</div>
                        </div>
                        <div class="signature-block">
                            <div class="signature-line">Parent/Guardian</div>
                        </div>
                    </div>

                    <div class="print-info">
                        Generated: <?php echo date('d M, Y g:i A'); ?> | Student <?php echo $current_student; ?> of <?php echo $total_students; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endforeach; ?>
</body>
</html>
