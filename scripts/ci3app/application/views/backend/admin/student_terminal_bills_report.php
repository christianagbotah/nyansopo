<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Bills Report</title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #2c3e50;
            background: #ffffff;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        center {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        page {
            display: block;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15mm;
            position: relative;
            min-height: 210mm;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        page[size="A5"] {
            width: 148mm;
            height: auto;
            min-height: 210mm;
            max-width: 148mm;
        }
        
        .header-section {
            border-bottom: 2px solid #34495e;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        
        .school-logo {
            max-height: 60px;
            width: auto;
        }
        
        .school-info {
            text-align: center;
        }
        
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            text-decoration: underline;
            margin-bottom: 3px;
            line-height: 1.2;
        }
        
        .school-details {
            font-size: 9px;
            color: #34495e;
            line-height: 1.2;
        }
        
        .report-badge {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-info {
            font-size: 9px;
            font-weight: 600;
        }
        
        .student-info-section {
            background: linear-gradient(135deg, #ecf0f1, #d5dbdb);
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .student-info-table {
            width: 100%;
            max-width: 100%;
            font-size: 10px;
            table-layout: fixed;
        }
        
        .student-info-table td {
            padding: 2px 4px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .student-name {
            font-weight: bold;
            text-transform: uppercase;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            margin: 8px 0 5px 0;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 4px;
            border-radius: 3px;
        }
        
        .bills-table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 10px;
            table-layout: fixed;
        }
        
        .bills-table th {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
            padding: 4px 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .bills-table td {
            padding: 3px;
            border: 1px solid #bdc3c7;
            font-size: 9px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .bills-table .amount-cell {
            text-align: right;
            font-weight: 600;
        }
        
        .arrears-row {
            background: linear-gradient(135deg, #fadbd8, #f5b7b1);
            color: #c0392b;
            font-weight: bold;
        }
        
        .new-bill-row {
            background: linear-gradient(135deg, #d5f4e6, #a8e6cf);
            color: #27ae60;
        }
        
        .total-row {
            background: linear-gradient(135deg, #ecf0f1, #d5dbdb);
            font-weight: bold;
            font-size: 10px;
        }
        
        .grand-total-row {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            font-weight: bold;
            font-size: 11px;
        }
        
        .summary-section {
            margin-top: 10px;
            padding: 8px;
            background: linear-gradient(135deg, #fff9e6, #ffeaa7);
            border-radius: 4px;
            border: 2px solid #f39c12;
        }
        
        .summary-table {
            width: 100%;
            max-width: 100%;
            font-size: 10px;
            table-layout: fixed;
        }
        
        .summary-table td {
            padding: 3px 5px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .footer-note {
            margin-top: 10px;
            padding: 6px;
            background: #ecf0f1;
            border-left: 3px solid #e74c3c;
            font-size: 9px;
            font-style: italic;
        }
        
        .print-buttons {
            text-align: center;
            margin-top: 20px;
        }
        
        #main_report {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .report-content {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        table {
            max-width: 100%;
        }
        
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .print-buttons {
                display: none !important;
            }
            
            page {
                box-shadow: none;
                margin: 0;
                padding: 10mm 15mm 15mm 15mm;
                width: 148mm;
                height: auto;
                min-height: 210mm;
                page-break-after: always;
            }
            
            .header-section {
                margin-bottom: 8px;
                padding-bottom: 5px;
            }
            
            .school-name {
                font-size: 16px !important;
                margin-bottom: 2px !important;
            }
            
            .school-details {
                font-size: 8px !important;
                line-height: 1.1 !important;
            }
            
            .report-badge {
                padding: 3px 6px !important;
                font-size: 8px !important;
            }
            
            .report-info {
                font-size: 7px !important;
            }
            
            /* Force print colors and gradients */
            .bills-table th,
            .report-badge,
            .arrears-row,
            .new-bill-row,
            .total-row,
            .grand-total-row,
            .section-title,
            .summary-section,
            .student-info-section {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
<?php
// Get student and school information
$student_id = $this->uri->segment(3);
$year = $this->uri->segment(4);
$term = $this->uri->segment(5);

$student = $this->db->get_where('student', array('student_id' => $student_id))->row();
$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
$system_mail = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
$system_address = $this->db->get_where('settings', array('type' => 'address'))->row();
$system_address = $system_address ? $system_address->description : '';
$box_number = $this->db->get_where('settings', array('type' => 'box_number'))->row()->description;
$digital_address = $this->db->get_where('settings', array('type' => 'digital_address'))->row()->description;
$location = $this->db->get_where('settings', array('type' => 'location'))->row()->description;

// Get class information
$enroll = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $year))->row();
$class_name = $enroll ? $this->db->get_where('class', array('class_id' => $enroll->class_id))->row()->name : 'N/A';

// Get arrears and new bills
$arrears = $this->db->query(
    "SELECT SUM(amount - amount_paid) as total FROM invoice 
     WHERE student_id = ? AND year < ? AND status != 'paid'",
    array($student_id, $year)
)->row()->total ?? 0;

$new_bills = $this->db->query(
    "SELECT SUM(amount) as total FROM invoice 
     WHERE student_id = ? AND year = ? AND term = ?",
    array($student_id, $year, $term)
)->row()->total ?? 0;

$total_bill = $arrears + $new_bills;

// Get invoice items
$arrears_items = $this->db->query(
    "SELECT i.title, SUM(i.amount) as amount 
     FROM invoice_items i 
     JOIN invoice inv ON i.invoice_code = inv.invoice_code 
     WHERE inv.student_id = ? AND inv.year < ? AND inv.status != 'paid' 
     GROUP BY i.title",
    array($student_id, $year)
)->result_array();

$new_items = $this->db->query(
    "SELECT i.title, SUM(i.amount) as amount 
     FROM invoice_items i 
     JOIN invoice inv ON i.invoice_code = inv.invoice_code 
     WHERE inv.student_id = ? AND inv.year = ? AND inv.term = ? 
     GROUP BY i.title",
    array($student_id, $year, $term)
)->result_array();

$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description ?? 'GHS';
?>

<page size="A5">
    <div id="main_report">
        <!-- Header -->
        <div class="header-section">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 60px; vertical-align: top;">
                        <img src="<?php echo base_url(); ?>uploads/school_logo.png" class="school-logo" alt="Logo">
                    </td>
                    <td class="school-info">
                        <div class="school-name"><?php echo strtoupper($system_name); ?></div>
                        <div class="school-details">
                            <?php echo $location; ?> | <?php echo $system_phone; ?><br>
                            <?php echo $box_number; ?> | <?php echo $digital_address; ?>
                        </div>
                    </td>
                    <td style="width: 100px; text-align: right; vertical-align: top;">
                        <div class="report-badge">Terminal Bills</div>
                        <div class="report-info" style="margin-top: 4px;">
                            <?php echo $year; ?><br><?php echo $term; ?>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Student Info -->
        <div class="student-info-section">
            <table class="student-info-table">
                <tr>
                    <td style="width: 30%;"><strong>Student:</strong></td>
                    <td class="student-name"><?php echo strtoupper($student->name); ?></td>
                </tr>
                <tr>
                    <td><strong>ID:</strong></td>
                    <td><?php echo $student->student_code; ?></td>
                </tr>
                <tr>
                    <td><strong>Class:</strong></td>
                    <td><?php echo $class_name; ?></td>
                </tr>
            </table>
        </div>

        <div class="report-content">
            <!-- Arrears Section -->
            <?php if($arrears > 0): ?>
            <div class="section-title">Outstanding Arrears</div>
            <table class="bills-table">
                <thead>
                    <tr>
                        <th>Bill Item</th>
                        <th style="width: 80px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($arrears_items as $item): ?>
                    <tr class="arrears-row">
                        <td><?php echo $item['title']; ?></td>
                        <td class="amount-cell"><?php echo numfmt_format_currency($fmt, $item['amount'], $currency); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>Total Arrears</strong></td>
                        <td class="amount-cell"><strong><?php echo numfmt_format_currency($fmt, $arrears, $currency); ?></strong></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- New Bills Section -->
            <?php if($new_bills > 0): ?>
            <div class="section-title">Current Term Bills</div>
            <table class="bills-table">
                <thead>
                    <tr>
                        <th>Bill Item</th>
                        <th style="width: 80px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($new_items as $item): ?>
                    <tr class="new-bill-row">
                        <td><?php echo $item['title']; ?></td>
                        <td class="amount-cell"><?php echo numfmt_format_currency($fmt, $item['amount'], $currency); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>Current Term Total</strong></td>
                        <td class="amount-cell"><strong><?php echo numfmt_format_currency($fmt, $new_bills, $currency); ?></strong></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Grand Total -->
            <table class="bills-table" style="margin-top: 10px;">
                <tbody>
                    <tr class="grand-total-row">
                        <td><strong>TOTAL AMOUNT DUE</strong></td>
                        <td class="amount-cell" style="width: 80px;"><strong><?php echo numfmt_format_currency($fmt, $total_bill, $currency); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td style="width: 60%;"><strong>Outstanding Arrears:</strong></td>
                    <td style="text-align: right; font-weight: bold; color: #c0392b;"><?php echo numfmt_format_currency($fmt, $arrears, $currency); ?></td>
                </tr>
                <tr>
                    <td><strong>Current Term Bills:</strong></td>
                    <td style="text-align: right; font-weight: bold; color: #27ae60;"><?php echo numfmt_format_currency($fmt, $new_bills, $currency); ?></td>
                </tr>
                <tr style="border-top: 2px solid #f39c12;">
                    <td><strong>Total Amount Due:</strong></td>
                    <td style="text-align: right; font-weight: bold; font-size: 12px; color: #e74c3c;"><?php echo numfmt_format_currency($fmt, $total_bill, $currency); ?></td>
                </tr>
            </table>
        </div>

        <!-- Footer Note -->
        <div class="footer-note">
            <strong>Note:</strong> This is a computer-generated report. Please settle all outstanding bills promptly to avoid any inconvenience.
        </div>
    </div>

    <!-- Print Buttons -->
    <div class="print-buttons">
        <button onclick="window.print()" class="btn btn-print">
            <i class="fa fa-print"></i> Print Report
        </button>
        <button onclick="window.close()" class="btn btn-back">
            <i class="fa fa-times"></i> Close
        </button>
    </div>
</page>

</body>
</html>etFullClassName($enroll->class_id) : 'N/A';

// Currency formatter
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

// Get all unpaid/partially paid invoices (arrears)
$this->db->select('invoice.*, SUM(invoice.amount) as total_billed, SUM(invoice.amount_paid) as total_paid, SUM(invoice.due) as total_due');
$this->db->from('invoice');
$this->db->where('invoice.student_id', $student_id);
$this->db->where('invoice.due >', 0);
$this->db->where('invoice.can_delete !=', 'trash');
$this->db->group_by('invoice.invoice_code');
$this->db->order_by('invoice.year', 'ASC');
$this->db->order_by('invoice.term', 'ASC');
$arrears_invoices = $this->db->get()->result_array();

// Get current term invoice (new bills)
$this->db->select('invoice.*');
$this->db->from('invoice');
$this->db->where('invoice.student_id', $student_id);
$this->db->where('invoice.year', $year);
$this->db->where('invoice.term', $term);
$this->db->where('invoice.can_delete !=', 'trash');
$current_term_invoice = $this->db->get()->result_array();

// Calculate totals
$total_arrears = 0;
$total_new_bills = 0;

foreach($arrears_invoices as $arr) {
    // Only count arrears from previous terms
    if($arr['year'] != $year || $arr['term'] != $term) {
        $total_arrears += $arr['total_due'];
    }
}

foreach($current_term_invoice as $curr) {
    $total_new_bills += $curr['amount'];
}

$grand_total = $total_arrears + $total_new_bills;
?>

    <center>
    <page size="A5" id="bill_print">
    <div id="main_report">
        <div class="report-content">

        <div class="header-section">
            <div style="text-align: center; width: 100%; margin-bottom: 8px;">
                <div class="school-name"><?=$system_name;?></div>
            </div>
            
            <table style="width: 100%;">
                <tbody>
                    <tr>
                        <td width="15%" style="vertical-align: top;">
                            <img src="<?=base_url('uploads/school_logo.png');?>" class="school-logo">
                        </td>
                        <td width="55%" class="school-info" style="vertical-align: top; text-align: center;">
                            <div class="school-details">
                                <div><?=$location;?></div>
                                <?php if(!empty($system_address)): ?><div><?=nl2br($system_address);?></div><?php endif; ?>
                                <div><?=$box_number . ' | ' . $digital_address;?></div>
                                <div><?=$system_phone;?></div>
                                <?php if(!empty($system_mail)): ?><div><?=$system_mail;?></div><?php endif; ?>
                            </div>
                        </td>
                        <td width="30%" align="center" style="vertical-align: top;">
                            <div class="report-badge">Terminal Bills Report</div>
                            <table style="margin-top: 6px; width: 100%;">
                                <tbody>
                                    <tr>
                                        <td align="right" class="report-info">Year:</td>
                                        <td align="right" class="report-info"><strong><?=$year;?></strong></td>
                                    </tr>
                                    <tr>
                                        <td align="right" class="report-info">Term:</td>
                                        <td align="right" class="report-info"><strong><?=$term;?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="student-info-section">
            <table class="student-info-table">
                <tr>
                    <td width="30%"><strong>Student Name:</strong></td>
                    <td width="70%" class="student-name"><?=strtoupper($student->name);?></td>
                </tr>
                <tr>
                    <td><strong>Class:</strong></td>
                    <td><?=$class_name;?></td>
                </tr>
                <tr>
                    <td><strong>Report Date:</strong></td>
                    <td><?=date('d M, Y');?></td>
                </tr>
            </table>
        </div>

        <?php if(!empty($arrears_invoices) && $total_arrears > 0): ?>
        <div class="section-title">Outstanding Arrears from Previous Terms</div>
        <table class="bills-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Description</th>
                    <th style="text-align: center;">Year/Term</th>
                    <th style="text-align: right;">Billed</th>
                    <th style="text-align: right;">Paid</th>
                    <th style="text-align: right;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach($arrears_invoices as $arr): 
                    // Skip current term invoices in arrears section
                    if($arr['year'] == $year && $arr['term'] == $term) continue;
                ?>
                <tr class="arrears-row">
                    <td><?=$arr['title'];?></td>
                    <td style="text-align: center;"><?=$arr['year'].' / '.$arr['term'];?></td>
                    <td class="amount-cell"><?=numfmt_format_currency($fmt, $arr['total_billed'], $currency);?></td>
                    <td class="amount-cell"><?=numfmt_format_currency($fmt, $arr['total_paid'], $currency);?></td>
                    <td class="amount-cell"><?=numfmt_format_currency($fmt, $arr['total_due'], $currency);?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4"><strong>Total Arrears:</strong></td>
                    <td class="amount-cell"><strong><?=numfmt_format_currency($fmt, $total_arrears, $currency);?></strong></td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if(!empty($current_term_invoice)): ?>
        <div class="section-title">Current Term Bills (<?=$year.' - '.$term;?>)</div>
        <table class="bills-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($current_term_invoice as $curr): ?>
                <tr class="new-bill-row">
                    <td><?=$curr['title'];?></td>
                    <td class="amount-cell"><?=numfmt_format_currency($fmt, $curr['amount'], $currency);?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td><strong>Total Current Term Bills:</strong></td>
                    <td class="amount-cell"><strong><?=numfmt_format_currency($fmt, $total_new_bills, $currency);?></strong></td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>

        <table class="bills-table" style="margin-top: 10px;">
            <tr class="grand-total-row">
                <td><strong>GRAND TOTAL PAYABLE:</strong></td>
                <td class="amount-cell"><strong><?=numfmt_format_currency($fmt, $grand_total, $currency);?></strong></td>
            </tr>
        </table>

        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td width="60%"><strong>Total Outstanding Arrears:</strong></td>
                    <td width="40%" style="text-align: right; font-weight: bold; color: #c0392b;"><?=numfmt_format_currency($fmt, $total_arrears, $currency);?></td>
                </tr>
                <tr>
                    <td><strong>Current Term Bills:</strong></td>
                    <td style="text-align: right; font-weight: bold; color: #27ae60;"><?=numfmt_format_currency($fmt, $total_new_bills, $currency);?></td>
                </tr>
                <tr style="border-top: 2px solid #f39c12;">
                    <td><strong>TOTAL AMOUNT DUE:</strong></td>
                    <td style="text-align: right; font-weight: bold; font-size: 12px; color: #e74c3c;"><?=numfmt_format_currency($fmt, $grand_total, $currency);?></td>
                </tr>
            </table>
        </div>

        <div class="footer-note">
            <strong>Note:</strong> This bill statement shows all outstanding arrears from previous terms and current term bills. 
            Please ensure all payments are made before the next term begins. For payment inquiries, contact the accounts office.
        </div>

        </div>
    </div>
    </page>
    
    <div class="print-buttons">
        <button onclick="window.print()" class="btn btn-print">Print Bill</button>
        <button onclick="window.close()" class="btn btn-back">Close</button>
    </div>
    </center>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
