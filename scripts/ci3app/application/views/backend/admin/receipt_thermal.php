<!doctype html>
<html>
<head>
    <title>Thermal Receipt - <?php echo $receipt_code; ?></title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #f5f5f5;
        }

        .thermal-wrapper {
            max-width: 80mm;
            margin: 20px auto;
            background: #fff;
            padding: 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .thermal-receipt {
            width: 100%;
            padding: 10px;
            background: #fff;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .receipt-header img {
            max-height: 60px;
            max-width: 100%;
            margin-bottom: 5px;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 5px 0;
            letter-spacing: 1px;
        }

        .school-contact {
            font-size: 10px;
            margin: 3px 0;
        }

        .receipt-title {
            background: #000;
            color: #fff;
            padding: 6px;
            margin: 10px 0;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            border-radius: 4px;
        }

        .receipt-info {
            margin: 10px 0;
            font-size: 11px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            padding: 2px 0;
        }

        .info-label {
            font-weight: bold;
            flex: 0 0 40%;
        }

        .info-value {
            flex: 1;
            text-align: right;
            word-wrap: break-word;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .divider-thick {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        .items-table {
            width: 100%;
            margin: 10px 0;
            font-size: 11px;
        }

        .items-header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            display: flex;
        }

        .items-header .col-sn {
            flex: 0 0 30px;
        }

        .items-header .col-item {
            flex: 1;
        }

        .items-header .col-amount {
            flex: 0 0 80px;
            text-align: right;
        }

        .item-row {
            display: flex;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
            font-size: 10px;
        }

        .item-row .col-sn {
            flex: 0 0 15px;
            padding-right: 5px;
        }

        .item-row .col-item {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-row .col-amount {
            flex: 0 0 80px;
            text-align: right;
            font-weight: bold;
        }

        .totals-section {
            margin: 10px 0;
            font-size: 11px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            padding: 3px 0;
        }

        .total-label {
            font-weight: bold;
        }

        .total-value {
            font-weight: bold;
        }

        .grand-total {
            background: #f0f0f0;
            padding: 6px;
            margin: 5px 0;
            font-size: 13px;
            border-radius: 3px;
        }

        .arrears-alert {
            background: #ffe6e6;
            padding: 6px;
            margin: 5px 0;
            font-size: 12px;
            border-radius: 3px;
            border-left: 3px solid #ff0000;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 15px;
            border-top: 2px dashed #000;
            padding-top: 10px;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 10px;
        }

        .footer-label {
            font-weight: bold;
            text-align: left;
        }

        .footer-value {
            text-align: right;
            border-bottom: 1px solid #000;
            min-width: 100px;
        }

        .thank-you {
            margin: 10px 0;
            font-weight: bold;
            font-size: 12px;
        }

        .slogan {
            font-style: italic;
            font-size: 10px;
            margin: 5px 0;
        }

        .developer-info {
            font-size: 8px;
            color: #666;
            margin-top: 8px;
        }

        #watermark {
            position: absolute;
            font-size: 24px;
            transform: rotate(-35deg);
            color: rgba(160, 158, 159, 0.3);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            z-index: 1;
            white-space: nowrap;
            font-weight: bold;
            pointer-events: none;
        }

        .print-buttons {
            text-align: center;
            margin: 20px 0;
        }

        .print-buttons button {
            padding: 12px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .btn-print:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-back {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        @media print {
            body {
                background: #fff;
                margin: 0;
                padding: 0;
            }

            .thermal-wrapper {
                max-width: 80mm;
                width: 80mm;
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }

            .thermal-receipt {
                padding: 5mm;
            }

            .print-buttons {
                display: none;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }

        @media screen and (max-width: 600px) {
            .thermal-wrapper {
                margin: 10px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
<?php
// Get receipt data
$is_multi_invoice = false;
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get invoice codes for this receipt
$this->db->select('invoice_code');
$this->db->distinct();
$this->db->where('receipt_code', $receipt_code);
$this->db->where('student_id', $student_id);
$invoice_array = $this->db->get('payment')->result_array();

$invoice_array2 = array();
$year_array = array();
$term_array = array();
$sem_array = array();

foreach ($invoice_array as $irow) {
    array_push($invoice_array2, $irow['invoice_code']);
}

if (count($invoice_array2) > 1) {
    $is_multi_invoice = true;
    
    // Find all the various years, terms or semesters
    for ($i = 0; $i < count($invoice_array2); $i++) {
        $yr = $this->db->get_where('invoice', array('invoice_code' => $invoice_array2[$i], 'student_id' => $student_id))->row()->year;
        array_push($year_array, $yr);
        $year_array = array_unique($year_array);
        
        $tr = $this->db->get_where('invoice', array('invoice_code' => $invoice_array2[$i], 'student_id' => $student_id))->row()->term;
        array_push($term_array, $tr);
        $term_array = array_unique($term_array);
        
        $sr = $this->db->get_where('invoice', array('invoice_code' => $invoice_array2[$i], 'student_id' => $student_id))->row()->sem;
        array_push($sem_array, $sr);
        $sem_array = array_unique($sem_array);
    }
    
    $invoice_code = implode(', ', $invoice_array2);
    $year = implode(', ', $year_array);
    $term = implode(', ', $term_array);
    $sem = implode(', ', $sem_array);
} else {
    $invoice_code = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row()->invoice_code;
    $invoice_query = $this->db->get_where('invoice', array('invoice_code' => $invoice_code))->row();
    $year = $invoice_query->year;
    $term = $invoice_query->term;
    $sem = $invoice_query->sem;
}

$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$running_sem = get_settings('running_sem');

$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
$system_mail = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
$system_slogan = $this->db->get_where('settings', array('type' => 'system_title'))->row()->description;

$student_name = $this->db->get_where('student', array('student_id' => $student_id))->row()->name;
$class_id = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $running_year))->row()->class_id;
$class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
$class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;

// Get balance
$this->db->select_sum('due');
$this->db->where('receipt_code', $receipt_code);
$this->db->where('student_id', $student_id);
$balance_owe = $this->db->get('payment')->row()->due;

// Add section if multiple sections
$section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
$class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
$sec_name = '';
if ($class_has_more_sections > 1) {
    $sec_name = $section_name;
}

$class = '';
if ($class_name == 'CRECHE') {
    $class = ucwords(strtolower($class_name));
} else {
    $class = ucwords(strtolower($class_name)) . ' ' . $class_name_numeric . $sec_name;
}

// Get issuer info
$issuer_id = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row()->issuer_id;
$issuer_name = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row()->name;
$account_type = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->row()->account_type;

if (!empty($issuer_name) && !empty($account_type)) {
    $issuer_name = $issuer_name;
} else {
    $issuer_name = 'Not Available';
}

// Get payment method
$payment_row = $this->db->get_where('payment', array('receipt_code' => $receipt_code))->row();
$method = $payment_row->payment_method;
if ($method == 1) {
    $payment_method = 'Cash';
} else if ($method == 2) {
    $payment_method = 'Cheque';
} else if ($method == 3) {
    $payment_method = 'Card';
} else if ($method == 4) {
    $payment_method = 'Mobile Money';
} else {
    $payment_method = 'N/A';
}
?>

<div class="print-buttons">
    <button onclick="printReceipt()" class="btn-print">
        <i class="fa fa-print"></i> Print Receipt
    </button>
    <button onclick="goBack()" class="btn-back">
        <i class="fa fa-arrow-left"></i> Back
    </button>
</div>

<div class="thermal-wrapper">
    <div class="thermal-receipt" id="thermal_print">
        <?php
        // Show watermark for multi-invoices or arrears
        if ($is_multi_invoice) {
            echo '<div id="watermark">MULTI-INVOICE PAYMENT</div>';
        } else {
            if ($class_name == 'JHSS') {
                if ($year != $running_year || $sem != $running_sem) {
                    echo '<div id="watermark">ARREARS PAYMENT</div>';
                }
            } else {
                if ($year != $running_year || $term != $running_term) {
                    echo '<div id="watermark">ARREARS PAYMENT</div>';
                }
            }
        }
        ?>
        
        <!-- Header -->
        <div class="receipt-header">
            <img src="<?php echo base_url('uploads/school_logo.png'); ?>" alt="School Logo">
            <div class="school-name"><?php echo strtoupper($system_name); ?></div>
            <div class="school-contact"><?php echo $system_phone; ?></div>
            <div class="school-contact"><?php echo $system_mail; ?></div>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">OFFICIAL RECEIPT</div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <div class="info-row">
                <span class="info-label">Received From:</span>
                <span class="info-value"><?php echo ucwords(strtolower($student_name)); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Class:</span>
                <span class="info-value"><?php echo $class; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <?php echo $class_name == 'JHSS' ? 'Year | Sem:' : 'Year | Term:'; ?>
                </span>
                <span class="info-value">
                    <?php echo $class_name == 'JHSS' ? $year . ' | ' . $sem : $year . ' | ' . $term; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Paid On:</span>
                <span class="info-value"><?php echo date('d/m/Y H:i:s', $date); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Receipt No.:</span>
                <span class="info-value"><?php echo $receipt_code; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Invoice No.:</span>
                <span class="info-value"><?php echo $invoice_code; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Currency:</span>
                <span class="info-value"><?php echo $currency; ?></span>
            </div>
        </div>

        <div class="divider-thick"></div>

        <!-- Items -->
        <div class="items-table">
            <div class="items-header">
                <div class="col-sn">S/N</div>
                <div class="col-item">ITEM</div>
                <div class="col-amount">PAID</div>
            </div>
            <div class="divider"></div>

            <?php
            $receipt_query = $this->db->get_where('payment', array('receipt_code' => $receipt_code, 'student_id' => $student_id))->result_array();
            $i = 1;
            foreach ($receipt_query as $row) {
                ?>
                <div class="item-row">
                    <div class="col-sn"><?php echo $i; ?></div>
                    <div class="col-item"><?php echo $row['title']; ?></div>
                    <div class="col-amount"><?php echo number_format($row['amount'], 2); ?></div>
                </div>
                <?php
                $i++;
            }
            ?>
        </div>

        <div class="divider-thick"></div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span class="total-label">Sub-total:</span>
                <span class="total-value"><?php echo number_format($total_amount_paid, 2); ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">Tax:</span>
                <span class="total-value"><?php echo number_format(0, 2); ?></span>
            </div>
            <div class="divider"></div>
            <div class="total-row grand-total">
                <span class="total-label">Net Amount Paid:</span>
                <span class="total-value"><?php echo $currency . ' ' . number_format($total_amount_paid, 2); ?></span>
            </div>
            
            <?php if ($balance_owe > 0): ?>
            <div class="total-row arrears-alert">
                <span class="total-label">Outstanding Arrears:</span>
                <span class="total-value"><?php echo $currency . ' ' . number_format($balance_owe, 2); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="divider-thick"></div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="footer-info">
                <div style="flex: 1;">
                    <div class="footer-label" style="text-align: left;">Cashier:</div>
                    <div class="footer-value" style="text-align: left;"><?php echo ucwords(strtolower($issuer_name)); ?></div>
                </div>
                <div style="flex: 1;">
                    <div class="footer-label" style="text-align: right;">Method:</div>
                    <div class="footer-value" style="text-align: right;"><?php echo $payment_method; ?></div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="thank-you">THANK YOU!</div>
            <div class="slogan"><?php echo $system_slogan; ?></div>
            
            <div class="developer-info">
                Powered by iEdupro<br>
                Software by Lightworldtech - 0243618186
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
<script type="text/javascript">
    function printReceipt() {
        var printContents = document.getElementById('thermal_print').innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload();
    }

    function goBack() {
        window.location.href = '<?php echo site_url($this->session->userdata('login_type') . '/income?id=' . $class_id); ?>';
    }
</script>
</body>
</html>
